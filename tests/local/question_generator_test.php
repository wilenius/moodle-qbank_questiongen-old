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

use context_module;
use core_question\local\bank\question_bank_helper;
use qbank_questiongen\form\story_form;
use stdClass;

/**
 * Unit tests for the question_generator class.
 *
 * @package   qbank_questiongen
 * @copyright 2025 ISB Bayern
 * @author    Philipp Memmel
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class question_generator_test extends \advanced_testcase {

    /**
     * Tests the functionality that substitutes certain placeholders in a string.
     *
     * @covers \qbank_questiongen\local\question_generator::generate_question
     */
    public function test_generate_question(): void {
        global $CFG;
        $this->resetAfterTest();
        set_config('provider', 'local_ai_manager', 'qbank_questiongen');

        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();
        $qbankcminfo = question_bank_helper::create_default_open_instance($course, 'testquestionbank');
        $questionplugingenerator = $this->getDataGenerator()->get_plugin_generator('core_question');
        $questioncategory = $questionplugingenerator->create_question_category(['contextid' => $qbankcminfo->context->id]);
        $questioncategory2 = $questionplugingenerator->create_question_category(['contextid' => $qbankcminfo->context->id]);

        $generatedxmlfixture = file_get_contents($CFG->dirroot . '/question/bank/questiongen/tests/fixtures/multichoice.xml');
        $questiongenerator = $this->getMockBuilder(question_generator::class)
            ->setConstructorArgs([$qbankcminfo->context->id])->onlyMethods(['retrieve_llm_response'])->getMock();
        $questiongenerator->method('retrieve_llm_response')->willReturn(['generatedquestiontext' => $generatedxmlfixture,
                'errormessage' => '']);

        $dataobject = new stdClass();
        $dataobject->mode = story_form::QUESTIONGEN_MODE_TOPIC;
        $dataobject->category = $questioncategory->id;
        $dataobject->numoftries = 3;
        $dataobject->story = 'French revolution';
        // We import our initial presets and use the first one (for multiple choice question) for testing.
        // In reality the user is able to manipulate each of the preset entries, but we don't want to test that here.
        $presetjson = json_decode(file_get_contents($CFG->dirroot . '/question/bank/questiongen/db/initial_presets.json'))[0];
        $this->assertEquals('Multiple choice question', $presetjson->name);
        $dataobject->primer = $presetjson->primer;
        $dataobject->instructions = $presetjson->instructions;
        $dataobject->example = $presetjson->example;

        $questionobject = $questiongenerator->generate_question($dataobject, false);
        $this->assertEquals($generatedxmlfixture, $questionobject->text);
        $this->assertEquals($presetjson->primer, $questionobject->primer);
        $this->assertEquals($presetjson->instructions, $questionobject->instructions);
        $this->assertEquals($presetjson->example, $questionobject->example);
        $expectedstoryprompt = 'Create a question about the following topic. Use your own training data to generate it: "' .
                $dataobject->story . '"';
        $this->assertEquals($expectedstoryprompt, $questionobject->storyprompt);
        $this->assertEmpty($questionobject->questiontextsinqbankprompt);

        // Now test if sending questions as context works.
        $questionplugingenerator->create_question('essay', null,
                ['category' => $questioncategory->id, 'name' => 'Test question 1',
                        'questiontext' => ['text' => 'Write some intelligent stuff', 'format' => FORMAT_MOODLE]]);
        $questionplugingenerator->create_question('essay', null,
                ['category' => $questioncategory->id, 'name' => 'Test question 2',
                        'questiontext' => ['text' => 'Write some more intelligent stuff', 'format' => FORMAT_MOODLE]]);
        $questionplugingenerator->create_question('essay', null,
                ['category' => $questioncategory2->id, 'name' => 'Test question 3',
                        'questiontext' => ['text' => 'This question should not be sent, because it\'s in a different category',
                                'format' => FORMAT_MOODLE]]);
        $questionobject = $questiongenerator->generate_question($dataobject, true);
        $this->assertEquals($generatedxmlfixture, $questionobject->text);
        $this->assertEquals($presetjson->primer, $questionobject->primer);
        $this->assertEquals($presetjson->instructions, $questionobject->instructions);
        $this->assertEquals($presetjson->example, $questionobject->example);
        $expectedstoryprompt = 'Create a question about the following topic. Use your own training data to generate it: "' .
                $dataobject->story . '"';
        $this->assertEquals($expectedstoryprompt, $questionobject->storyprompt);
        $this->assertStringContainsString('The question that will be generated by you has to be different '
                . 'from all of the following questions in this JSON string: "', $questionobject->questiontextsinqbankprompt);
        $this->assertStringContainsString('Test question 1', $questionobject->questiontextsinqbankprompt);
        $this->assertStringContainsString('Write some intelligent stuff', $questionobject->questiontextsinqbankprompt);
        $this->assertStringContainsString('Test question 2', $questionobject->questiontextsinqbankprompt);
        $this->assertStringContainsString('Write some more intelligent stuff', $questionobject->questiontextsinqbankprompt);
        $this->assertStringNotContainsString('Test question 3', $questionobject->questiontextsinqbankprompt);
        $this->assertStringNotContainsString('This question should not be sent, because it\'s in a different category',
                $questionobject->questiontextsinqbankprompt);

        // Test story mode.
        $dataobject->mode = story_form::QUESTIONGEN_MODE_STORY;
        $dataobject->story = 'This is a lot of content that the LLM can use to generate questions from.';
        $questionobject = $questiongenerator->generate_question($dataobject, false);
        $this->assertEquals($generatedxmlfixture, $questionobject->text);
        $this->assertEquals($presetjson->primer, $questionobject->primer);
        $this->assertEquals($presetjson->instructions, $questionobject->instructions);
        $this->assertEquals($presetjson->example, $questionobject->example);
        $expectedstoryprompt = 'Create a question from the following contents. '
                . 'Only use this content and do not use any training data: "' . $dataobject->story . '"';
        $this->assertEquals($expectedstoryprompt, $questionobject->storyprompt);
        $this->assertEmpty($questionobject->questiontextsinqbankprompt);

        // Test course contents mode.
        // We do not really test the generation of text from course contents, this is being done by the test for
        // the method create_story_from_cms.
        $dataobject->mode = story_form::QUESTIONGEN_MODE_COURSECONTENTS;
        $dataobject->story = 'This is a lot of content that the LLM can use to generate questions from.';
        $questionobject = $questiongenerator->generate_question($dataobject, false);
        $this->assertEquals($generatedxmlfixture, $questionobject->text);
        $this->assertEquals($presetjson->primer, $questionobject->primer);
        $this->assertEquals($presetjson->instructions, $questionobject->instructions);
        $this->assertEquals($presetjson->example, $questionobject->example);
        $expectedstoryprompt = 'Create a question from the following contents. '
                . 'Only use this content and do not use any training data: "' . $dataobject->story . '"';
        $this->assertEquals($expectedstoryprompt, $questionobject->storyprompt);
        $this->assertEmpty($questionobject->questiontextsinqbankprompt);
    }

    /**
     * Tests the extracting of content from course modules.
     *
     * @covers \qbank_questiongen\local\question_generator::extract_content_from_cm
     */
    public function test_extract_content_from_cm(): void {
        global $CFG;
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();
        $this->setAdminUser();
        $qbankcminfo = question_bank_helper::create_default_open_instance($course, 'testquestionbank');
        $questiongenerator = new question_generator($qbankcminfo->context->id);

        // Test mod_page.
        $testcontent = 'Very interesting content in a page to generate questions from';
        $pagegenerator = $this->getDataGenerator()->get_plugin_generator('mod_page');
        $page = $pagegenerator->create_instance(['course' => $course->id, 'name' => 'testpage', 'content' => $testcontent]);
        $content = $questiongenerator->extract_content_from_cm(get_fast_modinfo($course)->get_cm($page->cmid));
        $this->assertEquals($testcontent, $content);

        // Test mod_label.
        $testcontent = 'Very interesting content in a label to generate questions from';
        $labelgenerator = $this->getDataGenerator()->get_plugin_generator('mod_label');
        $label = $labelgenerator->create_instance(['course' => $course->id, 'name' => 'testlabel', 'intro' => $testcontent]);
        $content = $questiongenerator->extract_content_from_cm(get_fast_modinfo($course)->get_cm($label->cmid));
        $this->assertEquals($testcontent, $content);

        // Test mod_resource.
        $testcontent = 'Very interesting content in a resource to generate questions from';
        $resourcegenerator = $this->getDataGenerator()->get_plugin_generator('mod_resource');
        $resource = $resourcegenerator->create_instance(['course' => $course->id, 'name' => 'testresource']);
        $context = context_module::instance($resource->cmid);
        $fs = get_file_storage();
        // First of all, cleanup generated files from module generator so we can add our own one.
        foreach ($fs->get_area_files($context->id, 'mod_resource', 'content') as $file) {
            $file->delete();
        }

        // Now test different file types.
        // We start with simple .txt file.
        $filerecord = ['component' => 'mod_resource', 'filearea' => 'content',
                'contextid' => $context->id, 'itemid' => 0, 'filepath' => '/'];
        $filerecord['filename'] = 'testfile.txt';
        $file = $fs->create_file_from_string($filerecord, $testcontent);

        $questiongenerator = $this->getMockBuilder(question_generator::class)
            ->setConstructorArgs([$qbankcminfo->context->id])
            ->onlyMethods(['extract_content_from_pdf_or_image'])
            ->getMock();
        $questiongenerator->method('extract_content_from_pdf_or_image')
            ->willReturn('Extracted PDF or image content');
        $content = $questiongenerator->extract_content_from_cm(get_fast_modinfo($course)->get_cm($resource->cmid));
        $this->assertEquals($testcontent, $content);
        $file->delete();

        // Test a PDF file. We mock the extraction of the content from the PDF. We actually just check if the correct method is
        // being called and assume the extraction of the content (which is done by an external LLM) will return the text.
        // The fixture PDF is only being used to determine its mimetype and call the method.
        $filerecord['filename'] = 'testpdf.pdf';
        $file = $fs->create_file_from_string($filerecord,
                file_get_contents($CFG->dirroot . '/question/bank/questiongen/tests/fixtures/testpdf.pdf'));
        $content = $questiongenerator->extract_content_from_cm(get_fast_modinfo($course)->get_cm($resource->cmid));
        $this->assertEquals('Extracted PDF or image content', $content);
        $file->delete();

        $filerecord['filename'] = 'testimage.png';
        $file = $fs->create_file_from_string($filerecord, file_get_contents($CFG->dirroot . '/pix/s/approve.png'));
        $content = $questiongenerator->extract_content_from_cm(get_fast_modinfo($course)->get_cm($resource->cmid));
        $this->assertEquals('Extracted PDF or image content', $content);
        $file->delete();

        // Test mod_folder.
        $testcontent = 'Very interesting content in a file in a folder to generate questions from';
        $foldergenerator = $this->getDataGenerator()->get_plugin_generator('mod_folder');
        $folder = $foldergenerator->create_instance(['course' => $course->id, 'name' => 'testfolder']);
        $context = context_module::instance($folder->cmid);
        $fs = get_file_storage();
        $filerecord = ['component' => 'mod_folder', 'filearea' => 'content', 'contextid' => $context->id, 'itemid' => 0,
                'filepath' => '/'];
        $filerecord['filename'] = 'testfile.txt';
        $file1 = $fs->create_file_from_string($filerecord, $testcontent);
        $filerecord['filename'] = 'testpdf.pdf';
        $file2 = $fs->create_file_from_string($filerecord,
                file_get_contents($CFG->dirroot . '/question/bank/questiongen/tests/fixtures/testpdf.pdf'));
        $filerecord['filename'] = 'testimage.png';
        $file3 = $fs->create_file_from_string($filerecord, file_get_contents($CFG->dirroot . '/pix/s/approve.png'));

        $this->assertEquals($testcontent . "\n\n" . 'Extracted PDF or image content' . "\n\n" . 'Extracted PDF or image content',
                $questiongenerator->extract_content_from_cm(get_fast_modinfo($course)->get_cm($folder->cmid)));
        $file1->delete();
        $file2->delete();
        $file3->delete();

        // Test mod_lesson.
        $testcontent1 = 'Very interesting content in a lesson, page 1';
        $testcontent2 = 'Very interesting content in a lesson, page 2';
        $lessongenerator = $this->getDataGenerator()->get_plugin_generator('mod_lesson');
        $lesson = $lessongenerator->create_instance(['course' => $course->id, 'name' => 'testlesson']);
        $contentrecord1 = [
                'contents_editor' => [
                        'text' => $testcontent1,
                        'format' => FORMAT_MOODLE,
                        'itemid' => 0,
                ],
        ];
        $contentrecord2 = [
                'contents_editor' => [
                        'text' => $testcontent2,
                        'format' => FORMAT_MOODLE,
                        'itemid' => 0,
                ],
        ];
        $lessongenerator->create_content($lesson, $contentrecord1);
        $lessongenerator->create_content($lesson, $contentrecord2);
        $content = $questiongenerator->extract_content_from_cm(get_fast_modinfo($course)->get_cm($lesson->cmid));
        $this->assertEquals($testcontent2 . "\n\n" . $testcontent1, $content);

        // Test mod_book.
        $testtitle1 = 'Chapter 1 title';
        $testtitle2 = 'Chapter 2 title';
        $testcontent1 = 'Very interesting content in a book, chapter 1';
        $testcontent2 = 'Very interesting content in a book, chapter 2';
        $bookgenerator = $this->getDataGenerator()->get_plugin_generator('mod_book');
        $book = $bookgenerator->create_instance(['course' => $course->id, 'name' => 'testbook']);
        $contentrecord1 = ['title' => $testtitle1, 'content' => $testcontent1];
        $contentrecord2 = ['title' => $testtitle2, 'content' => $testcontent2];
        $bookgenerator->create_content($book, $contentrecord1);
        $bookgenerator->create_content($book, $contentrecord2);
        $content = $questiongenerator->extract_content_from_cm(get_fast_modinfo($course)->get_cm($book->cmid));
        $this->assertEquals($testtitle2 . "\n" . $testcontent2 . "\n\n" . $testtitle1 . "\n" . $testcontent1, $content);
    }

    /**
     * Tests the formatting of the extract cm content.
     *
     * @param string $content the content to format
     * @param string $expected the expected formatted content
     * @dataProvider format_extracted_cm_content_provider
     * @covers \qbank_questiongen\local\question_generator::format_extracted_cm_content
     */
    public function test_format_extracted_cm_content(string $content, string $expected): void {
        $this->assertEquals($expected, question_generator::format_extracted_cm_content($content));
    }

    /**
     * Data provider for test_format_extracted_cm_content test function
     *
     * @return array[] array of test cases
     */
    public static function format_extracted_cm_content_provider(): array {
        return [
                'remove_p_tags' => [
                        'content' => '<p style="color:red">test text</p>',
                        'expected' => 'test text' . "\n",
                ],
                'multiple_paragraphs' => [
                        'content' => '<p style="color: red;">test text</p><div>Some text in between</div><p>another test text</p>',
                        'expected' => 'test text' . "\n\n" . 'Some text in between' . "\n" . 'another test text' . "\n",
                ],
                'line_breaks' => [
                        'content' => 'test text<br/>another test text<br>final test text',
                        'expected' => 'test text' . "\n" . 'another test text' . "\n" . 'final test text',
                ],
                'general_removal_of_tags' => [
                        'content' => '<style>p { color: red; }</style>test <b>text</b> with <span>tags</span>',
                    // The word "text" is emphasized.
                        'expected' => 'test TEXT with tags',
                ],
                'trailingwhitespaces' => [
                        'content' => '   test text  ',
                        'expected' => 'test text',
                ],
        ];
    }

    /**
     * Tests the extraction of content from PDF or image with an external AI system.
     *
     * @covers \qbank_questiongen\local\question_generator::extract_content_from_pdf_or_image
     * @covers \qbank_questiongen\local\question_generator::convert_pdf_to_images
     */
    public function test_extract_content_from_pdf_or_image(): void {
        global $CFG, $DB;
        $this->resetAfterTest();
        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();
        $qbankcminfo = question_bank_helper::create_default_open_instance($course, 'testquestionbank');
        $fs = get_file_storage();
        // That's just a fake file record for testing purposes. We just need a stored_file.
        $filerecord = ['component' => 'qbank_questiongen', 'filearea' => 'test', 'contextid' => $qbankcminfo->context->id,
                'itemid' => 0, 'filepath' => '/', 'filename' => 'testpdf.pdf'];
        $file = $fs->create_file_from_string($filerecord,
                file_get_contents($CFG->dirroot . '/question/bank/questiongen/tests/fixtures/testpdf.pdf'));
        $questiongenerator = $this->getMockBuilder(question_generator::class)
            ->setConstructorArgs([$qbankcminfo->context->id])
            ->onlyMethods(['retrieve_file_content_from_ai_system', 'is_mimetype_supported_by_ai_system'])->getMock();
        $questiongenerator->method('retrieve_file_content_from_ai_system')
            // Only return 'content from file' once.
            ->willReturnOnConsecutiveCalls('content from file', '');
        // We have to fake the check if the external AI system supports the mimetype of the file we want to extract content from.
        $questiongenerator->method('is_mimetype_supported_by_ai_system')->with('application/pdf')
            ->willReturn(true);

        $this->assertEquals('content from file', $questiongenerator->extract_content_from_pdf_or_image($file));
        $cachedrecord = $DB->get_record('qbank_questiongen_resource_cache', ['contenthash' => $file->get_contenthash()]);
        $this->assertEquals('content from file', $cachedrecord->extractedcontent);
        // The mock method only returns the content ONCE.
        // If we call it a second time and if we receive the same result, that means that the caching mechanism works.
        $this->assertEquals('content from file', $questiongenerator->extract_content_from_pdf_or_image($file));
        // Empty the cache for next test.
        $DB->delete_records('qbank_questiongen_resource_cache', ['contenthash' => $file->get_contenthash()]);
        $this->assertEmpty($DB->get_records('qbank_questiongen_resource_cache', ['contenthash' => $file->get_contenthash()]));

        $questiongenerator = $this->getMockBuilder(question_generator::class)
            ->setConstructorArgs([$qbankcminfo->context->id])
            ->onlyMethods(['retrieve_file_content_from_ai_system', 'is_mimetype_supported_by_ai_system'])->getMock();
        $questiongenerator->method('retrieve_file_content_from_ai_system')
            // Only return 'content from file' once.
            ->willReturnOnConsecutiveCalls('content from file', '');
        // We have to fake the check if the external AI system supports the mimetype of the file we want to extract content from.
        // This time we simulate an external AI system that does not support PDF.
        // This will make the PDF being converted into images. So we're also testing the method convert_pdf_to_images here.
        $questiongenerator->method('is_mimetype_supported_by_ai_system')->with('application/pdf')
            ->willReturn(false);
        $this->assertEquals('content from file', $questiongenerator->extract_content_from_pdf_or_image($file));
        $cachedrecord = $DB->get_record('qbank_questiongen_resource_cache', ['contenthash' => $file->get_contenthash()]);
        $this->assertEquals('content from file', $cachedrecord->extractedcontent);
    }
}
