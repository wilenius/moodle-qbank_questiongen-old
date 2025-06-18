<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Page to configure and start the generation of questions.
 *
 * @package     qbank_questiongen
 * @copyright   2023 Ruthy Salomon <ruthy.salomon@gmail.com> , Yedidia Klein <yedidia@openapp.co.il>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use qbank_questiongen\form\story_form;

require(__DIR__ . '/../../../config.php');
require_once($CFG->dirroot . '/question/editlib.php');

defined('MOODLE_INTERNAL') || die();

core_question\local\bank\helper::require_plugin_enabled('qbank_questiongen');

[$thispageurl, $contexts, $cmid, $cm, $module, $pagevars] =
        question_edit_setup('import', '/question/bank/questiongen/story.php');

[$catid, $catcontext] = explode(',', $pagevars['cat']);
if (!$qbankcategory = $DB->get_record('question_categories', ['id' => $catid])) {
    throw new moodle_exception('nocategory', 'question');
}

$categorycontext = context::instance_by_id($qbankcategory->contextid);
$qbankcategory->context = $categorycontext;

// This page can be called without courseid or cmid in which case.
// We get the context from the category object.
if ($contexts === null) { // Need to get the course from the chosen category.
    $contexts = new core_question\local\bank\question_edit_contexts($categorycontext);
    $thiscontext = $contexts->lowest();
    if ($thiscontext->contextlevel == CONTEXT_COURSE) {
        require_login($thiscontext->instanceid, false);
    } else if ($thiscontext->contextlevel == CONTEXT_MODULE) {
        [$module, $cm] = get_module_from_cmid($thiscontext->instanceid);
        require_login($cm->course, false, $cm);
    }
    $contexts->require_one_edit_tab_cap('import');
}

$PAGE->set_url($thispageurl);

require_once("$CFG->libdir/formslib.php");

$PAGE->set_heading(get_string('pluginname', 'qbank_questiongen'));
$PAGE->set_title(get_string('pluginname', 'qbank_questiongen'));
$PAGE->set_pagelayout('standard');

$mform = new \qbank_questiongen\form\story_form(null, ['contexts' => $contexts, 'cmid' => $cmid]);
$provider = get_config('qbank_questiongen', 'provider');

if ($mform->is_cancelled()) {
    redirect($CFG->wwwroot . '/question/edit.php?cmid=' . $cmid);
} else if ($data = $mform->get_data()) {

    // Call the adhoc task.
    // We need the courseid anyway so get it from cmid.
    $cm = get_coursemodule_from_id('', $cmid);
    if ($cm) {
        $courseid = $cm->course;
    } else {
        $courseid = required_param('courseid', PARAM_INT);
    }

    $questiongenids = \qbank_questiongen\local\utils::store_questiongen_data($data);

    $customdata = [
            'contextid' => \context_module::instance($cm->id)->id,
            'sendexistingquestionsascontext' => !empty($data->sendexistingquestionsascontext),
    ];

    if (intval($data->mode) === story_form::QUESTIONGEN_MODE_COURSECONTENTS) {
        $customdata['courseactivities'] = $data->courseactivities;
    }

    // We intentionally do not queue one task for each question generation here, because we want the question generations to run
    // one after each other. That of course takes longer, but allows us to send als the newly created question as context for the
    // next one so the LLM does not create the same question again. Also it's easier to track the progress of one task in the
    // frontend instead of multiple ones.

    $task = new \qbank_questiongen\task\generate_questions();
    $task->set_userid($USER->id);
    $customdata['questiongenids'] = $questiongenids;
    // We need to re-query the adhoc task once queued to get the correct id for showing the progress bar.
    // Therefore, we need something to identify the adhoc tasks.
    $uniqadhoctaskid = uniqid();
    $customdata['uniqadhoctaskid'] = $uniqadhoctaskid;
    $task->set_custom_data($customdata);
    \core\task\manager::queue_adhoc_task($task);
    $currentadhoctasks = \core\task\manager::get_adhoc_tasks($task::class);
    $adhoctask = array_values(array_filter($currentadhoctasks,
            fn($currentadhoctask) => isset($currentadhoctask->get_custom_data()->uniqadhoctaskid) &&
                    $currentadhoctask->get_custom_data()->uniqadhoctaskid === $uniqadhoctaskid))[0];
    $adhoctask->initialise_stored_progress();
    $adhoctask->set_initial_progress();

    $adhoctaskprogressidnumber =
            \core\output\stored_progress_bar::convert_to_idnumber(\qbank_questiongen\task\generate_questions::class,
                    $adhoctask->get_id());
    $adhoctaskprogressbar = \core\output\stored_progress_bar::get_by_idnumber($adhoctaskprogressidnumber);

    // Check if the cron is overdue.
    $lastcron = get_config('tool_task', 'lastcronstart');
    $cronoverdue = ($lastcron < time() - 3600 * 24);

    // Prepare the data for the template.
    $datafortemplate = [
            'wwwroot' => $CFG->wwwroot,
            'userid' => $USER->id,
            'courseid' => $courseid,
            'cmid' => $cmid,
            'cron' => $cronoverdue,
            'progressbar' => $adhoctaskprogressbar->get_content(),
    ];
    echo $OUTPUT->header();
    $renderer = $PAGE->get_renderer('core_question', 'bank');
    $qbankaction = new \core_question\output\qbank_action_menu($thispageurl);
    echo $renderer->render($qbankaction);

    // Load the ready template.
    echo $OUTPUT->render_from_template('qbank_questiongen/loading', $datafortemplate);
    if ($provider === 'local_ai_manager') {
        $PAGE->requires->js_call_amd('local_ai_manager/warningbox', 'renderWarningBox', ['#ai_manager_warningbox']);
    }
} else {
    echo $OUTPUT->header();
    $renderer = $PAGE->get_renderer('core_question', 'bank');
    $qbankaction = new \core_question\output\qbank_action_menu($thispageurl);
    echo $renderer->render($qbankaction);
    echo $OUTPUT->render_from_template('qbank_questiongen/intro', []);

    if ($provider === 'local_ai_manager') {
        $PAGE->requires->js_call_amd('local_ai_manager/infobox', 'renderInfoBox',
                ['qbank_questiongen', $USER->id, '#ai_manager_infobox', ['questiongeneration', 'itt']]);
    }
    $mform->display();
}

echo $OUTPUT->footer();
