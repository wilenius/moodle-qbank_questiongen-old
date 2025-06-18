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

namespace qbank_questiongen;

use core\task\file_temp_cleanup_task;
use core_question\local\bank\question_bank_helper;
use qbank_questiongen\local\question_generator;

/**
 * Unit tests for lib.php of qbank_questiongen.
 *
 * @package   qbank_questiongen
 * @copyright 2025 ISB Bayern
 * @author    Philipp Memmel
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class lib_test extends \advanced_testcase {

    /**
     * Tests the functionality of the after_file_deleted callback.
     *
     * @covers \qbank_questiongen_after_file_deleted
     */
    public function test_qbank_questiongen_after_file_deleted(): void {
        global $CFG, $DB;

        $this->resetAfterTest();
        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();
        $qbankcminfo = question_bank_helper::create_default_open_instance($course, 'testquestionbank');
        $fs = get_file_storage();
        // That's just a fake file record for testing purposes. We just need a stored_file.
        $filerecord = ['component' => 'qbank_questiongen', 'filearea' => 'test', 'contextid' => $qbankcminfo->context->id,
                'itemid' => 0, 'filepath' => '/', 'filename' => 'testpdf.pdf'];
        $file1 = $fs->create_file_from_string($filerecord,
                file_get_contents($CFG->dirroot . '/question/bank/questiongen/tests/fixtures/testpdf.pdf'));
        // We create a second identical file with different name, so they have the same content hash.
        $filerecord['filename'] = 'testpdf2.pdf';
        $file2 = $fs->create_file_from_string($filerecord,
                file_get_contents($CFG->dirroot . '/question/bank/questiongen/tests/fixtures/testpdf.pdf'));
        $questiongenerator = new question_generator($qbankcminfo->context->id);
        $questiongenerator->store_to_record_cache($file1, 'fake extracted file content');
        $this->assertCount(1, $DB->get_records('qbank_questiongen_resource_cache'));
        // Storing the second file should have no additional effect, because both files have the same contenthash.
        $questiongenerator->store_to_record_cache($file2, 'fake extracted file content');
        $this->assertCount(1, $DB->get_records('qbank_questiongen_resource_cache'));
        $record = $DB->get_record('qbank_questiongen_resource_cache', ['contenthash' => $file1->get_contenthash()]);
        $this->assertEquals('fake extracted file content', $record->extractedcontent);
        $file1->delete();
        // We have to trigger the course_delete_modules adhoc task to really delete the file.
        $this->runAdhocTasks();
        // As there is a second reference to the contenthash, the cache entry is not being deleted.
        $this->assertTrue($DB->record_exists('qbank_questiongen_resource_cache', ['contenthash' => $file1->get_contenthash()]));

        $file2->delete();
        // We have to trigger the course_delete_modules adhoc task to really delete the file.
        $this->runAdhocTasks();
        // Now the cache entry has been deleted.
        $this->assertFalse($DB->record_exists('qbank_questiongen_resource_cache', ['contenthash' => $file1->get_contenthash()]));
    }
}
