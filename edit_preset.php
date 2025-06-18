<?php
// This file is part of Moodle - http://moodle.org/
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
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Configuration page for tenants.
 *
 * @package    qbank_questiongen
 * @copyright  2025 ISB Bayern
 * @author     Philipp Memmel
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(__FILE__) . '/../../../config.php');
require_login();

global $CFG, $DB, $PAGE, $OUTPUT, $USER;

$systemcontext = context_system::instance();
$PAGE->set_context($systemcontext);
$PAGE->set_url('/question/bank/questiongen/edit_preset.php');
$PAGE->set_pagelayout('admin');

require_capability('qbank/questiongen:manage', $systemcontext);

$id = optional_param('id', 0, PARAM_INT);
$del = optional_param('del', 0, PARAM_INT);

$returnurl = new moodle_url('/question/bank/questiongen/presets.php');

if (!empty($del)) {
    if (empty($id)) {
        throw new moodle_exception('exception_presetidmissing', 'qbank_questiongen');
    }
    require_sesskey();

    $preset = $DB->get_record('qbank_questiongen_preset', ['id' => $id]);
    if (!$preset) {
        throw new moodle_exception('exception_presetnotfound', 'qbank_questiongen', '', $id);
    }
    $preset = $DB->delete_records('qbank_questiongen_preset', ['id' => $id]);

    redirect($returnurl, get_string('presetdeleted', 'qbank_questiongen'));
}

$options = [];
if (!empty($id)) {
    $options['id'] = $id;
}

$actionurl = new moodle_url('/question/bank/questiongen/edit_preset.php', $options);

$preseteditform = new \qbank_questiongen\form\edit_preset_form($actionurl, $options);

// Standard form processing if statement.
if ($preseteditform->is_cancelled()) {
    redirect($returnurl);
} else if ($data = $preseteditform->get_data()) {
    $record = new stdClass();
    if (isset($data->id)) {
        $record->id = $data->id;
    }
    $record->name = trim($data->name);
    $record->primer = trim($data->primer);
    $record->instructions = trim($data->instructions);
    $record->example = trim($data->example);
    if (!empty($record->id)) {
        $DB->update_record('qbank_questiongen_preset', $record);
    } else {
        $DB->insert_record('qbank_questiongen_preset', $record);
    }

    redirect($returnurl, get_string('presetsaved', 'qbank_questiongen'));
} else {
    if (!empty($id)) {
        $PAGE->set_url('/question/bank/questiongen/edit_preset.php', ['id' => $id]);
        $record = $DB->get_record('qbank_questiongen_preset', ['id' => $id]);
        $data = new stdClass();
        $data->name = $record->name;
        $data->primer = $record->primer;
        $data->instructions = $record->instructions;
        $data->example = $record->example;
        $preseteditform->set_data($data);
    }
    echo $OUTPUT->header();
    echo html_writer::start_div('w-75 d-flex flex-column align-items-center ml-auto mr-auto');
    echo $OUTPUT->render_from_template('qbank_questiongen/edit_preset_heading',
            [
                    'heading' => $OUTPUT->heading(get_string('configurepreset', 'qbank_questiongen')),
                    'showdeletebutton' => !empty($id),
                    'deleteurl' => new moodle_url('/question/bank/questiongen/edit_preset.php',
                            ['id' => $id, 'del' => 1, 'sesskey' => sesskey()]),
            ]);


    echo html_writer::start_div('w-75');
    $preseteditform->display();
    echo html_writer::end_div();
    echo html_writer::end_div();
}

echo $OUTPUT->footer();
