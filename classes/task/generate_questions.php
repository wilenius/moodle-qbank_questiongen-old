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

namespace qbank_questiongen\task;

use qbank_questiongen\local\question_generator;

/**
 * Adhoc task for questions generation.
 *
 * @package     qbank_questiongen
 * @category    admin
 * @copyright   2023 Ruthy Salomon <ruthy.salomon@gmail.com> , Yedidia Klein <yedidia@openapp.co.il>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class generate_questions extends \core\task\adhoc_task {

    use \core\task\stored_progress_task_trait;

    #[\Override]
    public function execute() {
        global $DB;

        try {
            $customdata = $this->get_custom_data();
            $questiongenids = $customdata->questiongenids;
            [$insql, $inparams] = $DB->get_in_or_equal($questiongenids);
            $questiongenrecords = $DB->get_records_select('qbank_questiongen', "id $insql", $inparams);
            $this->start_stored_progress();
            if (empty($questiongenrecords)) {
                // It should not really happen that we have no questions here.
                // Exception will be caught at the end. The task will finish silently.
                throw new \moodle_exception('errornogenerateentriesfound', 'qbank_questiongen');
            }
            $questionstocreatecount = count($questiongenrecords);
            $this->progress->update(0, $questionstocreatecount, get_string('questiongeneratingstatus', 'qbank_questiongen',
                    ['current' => 0, 'total' => $questionstocreatecount]));

            // Before creating questions we need to check, if we need to generate the story from the course content first.
            if (property_exists($customdata, 'courseactivities') && !empty($customdata->courseactivities)) {
                $questiongenerator = new question_generator($customdata->contextid);
                $story = $questiongenerator->create_story_from_cms($customdata->courseactivities);

                foreach ($questiongenrecords as $dbrecord) {
                    $dbrecord->story = $story;
                    $DB->update_record('qbank_questiongen', $dbrecord);
                }
                if (empty(trim($story))) {
                    $this->progress->update_full(100, '');
                    $this->progress->error(get_string('errorcoursecontentsempty', 'qbank_questiongen'));
                    return;
                }
            }

            // Create questions.
            mtrace("[qbank_questiongen] Creating Questions with AI...\n");

            $i = 1;
            $maxtries = get_config('qbank_questiongen', 'numoftries');
            foreach ($questiongenids as $questiongenid) {
                $created = false;
                $error = ''; // Error message.
                $update = new \stdClass();

                $dbrecord = $DB->get_record('qbank_questiongen', ['id' => $questiongenid]);
                mtrace("[qbank_questiongen] Creating Question $i ...\n");

                while (!$created && $dbrecord->tries <= $maxtries) {
                    // Get questions from AI API.
                    $questiongenerator = new question_generator($customdata->contextid);
                    $question = $questiongenerator->generate_question($dbrecord, $customdata->sendexistingquestionsascontext);
                    if (!is_object($question)) {
                        // An error occurred.
                        // We do not retry here, because if the subsystem returns an error it's very likely that it's a general
                        // one. Retries are only meant to create slightly different questions in case of XML parsing fails.
                        $update->id = $dbrecord->id;
                        $update->datemodified = time();
                        $update->success = 0;
                        $DB->update_record('qbank_questiongen', $update);
                        $this->progress->update_full(100, '');
                        $this->progress->error($question);
                        return;
                    }

                    $update->id = $dbrecord->id;
                    $update->datemodified = time();
                    $update->llmresponse = $question->text;
                    $DB->update_record('qbank_questiongen', $update);

                    $created = \qbank_questiongen\local\xml_importer::parse_questions(
                            $dbrecord->category,
                            $question,
                            !empty($dbrecord->aiidentifier),
                    );

                    // If questions were not created.
                    if (!$created) {
                        // Insert error info to DB.
                        $update = new \stdClass();
                        $update->id = $dbrecord->id;
                        $update->tries = $dbrecord->tries++;
                        $update->timemodified = time();
                        $DB->update_record('qbank_questiongen', $update);
                    }

                    // Print error message.
                    // It will be shown on cron/adhoc output (file/whatever).
                    if ($error != '') {
                        mtrace('[qbank_questiongen adhoc_task]' . $error);
                    }

                }

                // Write success state to DB.
                $update = new \stdClass();
                $update->id = $dbrecord->id;
                $update->success = $created ? 1 : 0;
                $DB->update_record('qbank_questiongen', $update);
                $this->progress->update($i, $questionstocreatecount, get_string('questiongeneratingstatus', 'qbank_questiongen',
                        ['current' => $i, 'total' => $questionstocreatecount]));
                $i++;
            }
            $this->progress->update_full(100,
                    get_string('questiongeneratingfinished', 'qbank_questiongen', $questionstocreatecount));
            $successstates = $DB->get_fieldset_select('qbank_questiongen', 'success', "id $insql", $inparams);
            $failedquestionscount = count(array_filter($successstates, fn($state) => intval($state) === 0));
            if ($failedquestionscount > 0) {
                $this->progress->error(get_string('errorcreatingquestions', 'qbank_questiongen',
                        ['failed' => $failedquestionscount, 'total' => $questionstocreatecount]));
            }

        } catch (\Exception $exception) {
            set_debugging(DEBUG_DEVELOPER, true);
            $usererrormessage = get_string('errorcreatingquestionscritical', 'qbank_questiongen');
            if ($exception instanceof \qbank_questiongen\local\questiongen_exception) {
                // If we have a questiongen_exception, we overwrite the user-faced message with the one of
                // the questiongen_exception.
                $usererrormessage = $exception->getMessage();
            }
            mtrace('Exception thrown during task. Task will not be requeued. This is just for debugging purposes.');
            mtrace('Exception message: ' . $exception->getMessage());
            mtrace('Exception stack trace:');
            mtrace($exception->getTraceAsString());
            if ($this->progress->get_percent() === 0.0) {
                // If no progress has been made yet, set it to 100% so it's clear that the process is done and the user can see the
                // red color signaling an error.
                $this->progress->update_full(100, '');
            }
            $this->progress->error($usererrormessage);
        }
    }

    /**
     * Sets the initial progress of the associated progress bar.
     *
     * It adds a message that currently one is waiting for the adhoc task to be picked up.
     */
    public function set_initial_progress(): void {
        $this->progress->update_full(0, get_string('waitingforadhoctaskstart', 'qbank_questiongen'));
    }

    #[\Override]
    public function retry_until_success(): bool {
        // We don't want to retry this task.
        return false;
    }
}
