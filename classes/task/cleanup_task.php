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

use core\task\adhoc_task;

/**
 * Cleanup task for cleaning up the "qbank_questiongen" table and old adhoc tasks.
 *
 * @package   qbank_questiongen
 * @copyright 2025 ISB Bayern
 * @author    Philipp Memmel
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class cleanup_task extends \core\task\scheduled_task {

    /** @var \core\clock $clock dependency injected clock object. */
    private \core\clock $clock;

    /**
     * Constructor only initializing a clock object for this class.
     */
    public function __construct() {
        $this->clock = \core\di::get(\core\clock::class);
    }

    #[\Override]
    public function get_name(): string {
        return get_string('cleanuptask', 'qbank_questiongen');
    }

    #[\Override]
    public function execute(): void {
        global $DB;
        $cleanupdelay = get_config('qbank_questiongen', 'cleanupdelay');
        $idstocleanup = $DB->get_fieldset_select('qbank_questiongen', 'id', 'timemodified < ?',
                [$this->clock->time() - $cleanupdelay]);

        $chunks = array_chunk($idstocleanup, 100);
        $deleted = 0;
        foreach ($chunks as $chunk) {
            $DB->delete_records_list('qbank_questiongen', 'id', $chunk);
            $deleted += count($chunk);
        }
        mtrace('Deleted ' . $deleted . ' records from qbank_questiongen table.');

        // Now clean up old adhoc tasks.
        $tasks = \core\task\manager::get_adhoc_tasks(generate_questions::class);

        /** @var adhoc_task $task */
        foreach ($tasks as $task) {
            if ($task->get_attempts_available() === 0 && $task->get_timestarted() < $this->clock->time() - $cleanupdelay) {
                mtrace('Deleting old task ' . $task->get_id() . ' from task_adhoc table.');
                mtrace('Customdata: ' . json_encode($task->get_custom_data()));
                $DB->delete_records('task_adhoc', ['id' => $task->get_id()]);
            }
        }
    }
}
