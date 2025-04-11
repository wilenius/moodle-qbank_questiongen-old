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
 * Class to handle xml format.
 *
 * @package    qbank_genai
 * @copyright  ISB Bayern, 2024
 * @author     Dr. Peter Mayer
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


namespace qbank_genai\local;

/**
 * Class to handle xml format.
 *
 * @package    qbank_genai
 * @copyright  ISB Bayern, 2024
 * @author     Dr. Peter Mayer
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class xml {

    /**
     * Parse the xml questions.
     *
     * @param int $categoryid
     * @param object $llmresponse
     * @param int $numofquestions
     * @param int $userid
     * @param int $genaiid
     * @param bool $addidentifier
     * @return false|object[]
     */
    public static function parse_questions(
        int $categoryid,
        object $llmresponse,
        int $numofquestions,
        int $userid,
        bool $addidentifier,
        int $genaiid
    ) {
        global $CFG;

        require_once($CFG->dirroot . '/question/format/xml/format.php');

        // Work out if this is an uploaded file.
        // Or one from the filesarea.

        $search = strstr($llmresponse->text, '<?xml version', true);
        $cleandxml = str_replace($search, '', $llmresponse->text);
        $cleandxml = str_replace("```", '', $cleandxml);

        \local_debugger\performance\debugger::print_debug('test', 'cleandxml', $cleandxml);
        $fileformat = 'xml';
        $filedir = make_request_directory();
        $realfilename = uniqid() . "." . $fileformat;
        $importfile = $filedir . '/' . $realfilename;
        $filecreated = file_put_contents($importfile, $cleandxml);

        // $realfilename = $importform->get_new_filename('newfile');
        // $importfile = make_request_directory() . "/{$realfilename}";
        // if (!$result = $importform->save_file('newfile', $importfile, true)) {
        //     throw new moodle_exception('uploadproblem');
        // }


        $classname = 'qformat_xml';
        $qformat = new $classname();

        // Load data into class.
        $qformat->setCategory($categoryid);
        $qformat->setContexts($contexts->having_one_edit_tab_cap('import'));
        $qformat->setFilename($importfile);
        $qformat->setRealfilename($realfilename);
        // $qformat->setMatchgrades($form->matchgrades);
        // $qformat->setCatfromfile(!empty($form->catfromfile));
        // $qformat->setContextfromfile(!empty($form->contextfromfile));
        $qformat->setStoponerror(true);

        // Do anything before that we need to.
        if (!$qformat->importpreprocess()) {
            throw new \moodle_exception('cannotimport', '', $thispageurl->out());
        }

        // Process the uploaded file.
        if (!$qformat->importprocess()) {
            throw new \moodle_exception('cannotimport', '', $thispageurl->out());
        }

        // In case anything needs to be done after.
        if (!$qformat->importpostprocess()) {
            throw new \moodle_exception('cannotimport', '', $thispageurl->out());
        }

        // Log the import into this category.
        $eventparams = [
            'contextid' => $qformat->category->contextid,
            'other' => ['format' => $fileformat, 'categoryid' => $qformat->category->id],
        ];

        // --- End Adaption.

        $event = \core\event\questions_imported::create($eventparams);
        $event->trigger();
    }
}
