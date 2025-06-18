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

namespace qbank_questiongen\task;

use qbank_questiongen\form\story_form;
use stdClass;

/**
 * Unit tests for the cleanup task.
 *
 * @package   qbank_questiongen
 * @copyright 2025 ISB Bayern
 * @author    Philipp Memmel
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers    \local_ai_manager\local\observers
 */
final class cleanup_task_test extends \advanced_testcase {

    /**
     * Tests the functionality of the cleanup task.
     *
     * @covers \qbank_questiongen\task\cleanup_task::execute
     */
    public function test_execute(): void {
        global $DB;
        $this->resetAfterTest();

        $user = $this->getDataGenerator()->create_user();

        set_config('cleanupdelay', DAYSECS * 11, 'qbank_questiongen');
        // Set the clock to the past to simulate that a generate question task was created 10 days ago.
        $presenttime = time();
        $clock = $this->mock_clock_with_frozen($presenttime - DAYSECS * 10);

        $record = new stdClass();
        $record->category = 15;
        $record->userid = $user->id;
        $record->mode = story_form::QUESTIONGEN_MODE_TOPIC;
        $record->story = 'French revolution';
        $record->numoftries = 3;
        $record->llmresponse = '';
        $record->success = 0;
        $record->timecreated = $clock->time();
        $record->timemodified = $clock->time();
        $DB->insert_record('qbank_questiongen', $record);

        // Return to the present.
        $this->mock_clock_with_frozen($presenttime);

        // Let's run the task. Because the task is supposed to only delete entries with timemodified older than 11 days
        // and our entry is only 10 days old, it should not be deleted.
        ob_start();
        $task = new cleanup_task();
        $task->execute();
        ob_end_clean();

        $this->assertCount(1, $DB->get_records('qbank_questiongen'));

        // Two more days have passed, so now the DB entry should be 12 days old and should be deleted.
        $this->mock_clock_with_frozen($presenttime + DAYSECS * 2);
        ob_start();
        $task = new cleanup_task();
        $task->execute();
        ob_end_clean();
        $this->assertCount(0, $DB->get_records('qbank_questiongen'));
    }
}
