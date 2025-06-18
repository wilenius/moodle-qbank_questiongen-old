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

namespace qbank_questiongen\local;

use core_question\local\bank\question_bank_helper;
use question_bank;
use question_definition;
use stdClass;

/**
 * Unit tests for the xml_importer class.
 *
 * @package   qbank_questiongen
 * @copyright 2025 ISB Bayern
 * @author    Philipp Memmel
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers    \local_ai_manager\local\observers
 */
final class xml_importer_test extends \advanced_testcase {

    /**
     * Tests the functionality that substitutes certain placeholders in a string.
     *
     * @covers \qbank_questiongen\local\xml_importer::parse_questions
     * @covers \qbank_questiongen\local\xml_importer::add_aiidentifiers
     */
    public function test_parse_questions(): void {
        global $CFG;
        require_once($CFG->dirroot . '/question/engine/bank.php');
        $this->resetAfterTest();

        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();
        $qbankcminfo = question_bank_helper::create_default_open_instance($course, 'testquestionbank');
        question_get_top_category($qbankcminfo->context->id, true);

        $qgen = $this->getDataGenerator()->get_plugin_generator('core_question');

        $qcat = $qgen->create_question_category(['contextid' => $qbankcminfo->context->id]);

        $questionidsincategory = question_bank::get_finder()->get_questions_from_categories([$qcat->id], null);
        $this->assertCount(0, $questionidsincategory);

        $question = $this->import_question($qcat->id, false);

        $this->assertEquals('French Revolution Cause', $question->name);
        $this->assertEquals(
                '<p>Which of the following was a major contributing factor to the outbreak of the French Revolution?</p>',
                $question->questiontext);

        // Reset, so we can run the test again with different aiidentifier parameter.
        question_delete_question($question->id);

        // Now both global configs are empty, that means we have no aiidentifier no matter what is passed to the function.
        set_config('aiidentifier', '', 'qbank_questiongen');
        set_config('aiidentifiertag', '', 'qbank_questiongen');
        $question = $this->import_question($qcat->id, true);
        $this->assertEquals('French Revolution Cause', $question->name);
        $this->assertEmpty(\core_tag_tag::get_item_tags('core_question', 'question', $question->id));

        // Reset, so we can run the test again with different aiidentifier parameter.
        question_delete_question($question->id);

        // Now both global configs are empty, that means we have no aiidentifier no matter what is passed to the function.
        set_config('aiidentifier', '', 'qbank_questiongen');
        set_config('aiidentifiertag', 'aigenerated', 'qbank_questiongen');
        $question = $this->import_question($qcat->id, true);
        $this->assertEquals('French Revolution Cause', $question->name);
        $tags = \core_tag_tag::get_item_tags('core_question', 'question', $question->id);
        $this->assertCount(1, $tags);
        $this->assertEquals('aigenerated', reset($tags)->get_display_name());

        // Reset, so we can run the test again with different aiidentifier parameter.
        question_delete_question($question->id);

        set_config('aiidentifier', 'AI generated: ', 'qbank_questiongen');
        set_config('aiidentifiertag', 'aigenerated', 'qbank_questiongen');
        $question = $this->import_question($qcat->id, true);
        $tags = \core_tag_tag::get_item_tags('core_question', 'question', $question->id);
        $this->assertCount(1, $tags);
        $this->assertEquals('AI generated: French Revolution Cause', $question->name);
    }

    /**
     * Helper function importing a fixture question and returns the imported question.
     *
     * @param int $qcatid The question category id to which the question should be imported
     * @param bool $addidentifier if a prefix should be added to the title
     * @return question_definition the imported question as question_definition object
     */
    private function import_question(int $qcatid, bool $addidentifier): question_definition {
        global $CFG;
        $xml = file_get_contents($CFG->dirroot . '/question/bank/questiongen/tests/fixtures/multichoice.xml');
        $question = new stdClass();
        $question->text = $xml;
        ob_start();
        xml_importer::parse_questions($qcatid, $question, $addidentifier);
        ob_end_clean();
        $questionidsincategory = question_bank::get_finder()->get_questions_from_categories([$qcatid], null);
        $this->assertCount(1, $questionidsincategory);
        return question_bank::load_question(reset($questionidsincategory));
    }
}
