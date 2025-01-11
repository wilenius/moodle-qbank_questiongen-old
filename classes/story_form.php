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
 * Story Form Class is defined here.
 *
 * @package     qbank_genai
 * @category    admin
 * @copyright   2023 Ruthy Salomon <ruthy.salomon@gmail.com> , Yedidia Klein <yedidia@openapp.co.il>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


namespace qbank_genai;

defined('MOODLE_INTERNAL') || die();
require_once($CFG->libdir . '/formslib.php');

/**
 * Form to get the story from the user.
 *
 * @package     qbank_genai
 * @category    admin
 */
class story_form extends \moodleform {
    /**
     * Defines forms elements
     */
    public function definition() {

        $mform = $this->_form;
        $contexts = $this->_customdata['contexts']->having_cap('moodle/question:add');

        $courseid = optional_param('courseid', 0, PARAM_INT);
        $mform->addElement('hidden', 'courseid', $courseid);

        // Question category.
        $mform->addElement('questioncategory', 'category', get_string('category', 'question'), ['contexts' => $contexts]);
        $mform->addHelpButton('category', 'category', 'qbank_genai');

        // Number of questions.
        $defaultnumofquestions = 4;
        $select = $mform->addElement(
            'select',
            'numofquestions',
            get_string('numofquestions', 'qbank_genai'),
            ['1' => 1, '2' => 2, '3' => 3, '4' => 4, '5' => 5, '6' => 6, '7' => 7, '8' => 8, '9' => 9, '10' => 10]
        );
        $select->setSelected($defaultnumofquestions);
        $mform->setType('numofquestions', PARAM_INT);

        // Story.
        $mform->addElement(
            'textarea',
            'story',
            get_string('story', 'qbank_genai'),
            'wrap="virtual" rows="10" cols="50"'
        ); // This model's maximum context length is 4097 tokens. We limit the story to 4096 tokens.
        $mform->setType('story', PARAM_RAW);
        $mform->addHelpButton('story', 'story', 'qbank_genai');

        // Use course contents instead.
        $mform->addElement('checkbox', 'coursecontents', get_string('use_coursecontents', 'qbank_genai'));
        $mform->setDefault('coursecontents', 0); // Default of "no"
        $mform->setType('coursecontents', PARAM_BOOL);


        $courseactivities = [
            'History of astro physics before 1990',
            'History of astro physics between 1990 and 2000',
            'History of astro physics between 2000 and 2010',
            'History of astro physics between 2010 and 2020',
            'History of astro physics after 2020',
        ];
        $mform->addElement('select', 'courseactivities', get_string('activitylist', 'qbank_genai'), $courseactivities);
        $mform->hideif('courseactivities', 'coursecontents');



        // Add "GPT-created" to question name.
        $mform->addElement('checkbox', 'addidentifier', get_string('addidentifier', 'qbank_genai'));
        $mform->setDefault('addidentifier', 1); // Default of "yes"
        $mform->setType('addidentifier', PARAM_BOOL);

        // Preset.
        $presets = [];
        for ($i = 0; $i < 10; $i++) {
            if ($presetname = get_config('qbank_genai', 'presetname' . $i)) {
                $presets[] = $presetname;
            }
        }
        $mform->addElement('select', 'preset', get_string('preset', 'qbank_genai'), $presets);

        // Edit preset.
        $mform->addElement('checkbox', 'editpreset', get_string('editpreset', 'qbank_genai'));
        $mform->addElement('html', get_string('shareyourprompts', 'qbank_genai'));

        // Format.
        $formatoptions = [
            \qbank_genai\task\questions::PARAM_GENAI_GIFT => get_string('gift_format', 'qbank_genai'),
            \qbank_genai\task\questions::PARAM_GENAI_XML => get_string('xml_format', 'qbank_genai'),
        ];
        $mform->addElement('select', 'presetformat', get_string('presetformat', 'qbank_genai'), $formatoptions);
        $mform->setDefault('presetformat', \qbank_genai\task\questions::PARAM_GENAI_GIFT);
        $mform->addHelpButton('presetformat', 'example', 'qbank_genai');
        $mform->hideif('presetformat', 'editpreset');

        // Create elements for all presets.
        for ($i = 0; $i < 10; $i++) {

            $primer = $i + 1;

            // Primer.
            $mform->addElement(
                'textarea',
                'primer' . $i,
                get_string('primer', 'qbank_genai'),
                'wrap="virtual" rows="10" cols="50"'
            );
            $mform->setType('primer' . $i, PARAM_RAW);
            $mform->setDefault('primer' . $i, get_config('qbank_genai', 'presettprimer' . $primer));
            $mform->addHelpButton('primer' . $i, 'primer', 'qbank_genai');
            $mform->hideif('primer' . $i, 'editpreset');
            $mform->hideif('primer' . $i, 'preset', 'neq', $i);

            // Instructions.
            $mform->addElement(
                'textarea',
                'instructions' . $i,
                get_string('instructions', 'qbank_genai'),
                'wrap="virtual" rows="10" cols="50"'
            );
            $mform->setType('instructions' . $i, PARAM_RAW);
            $mform->setDefault('instructions' . $i, get_config('qbank_genai', 'presetinstructions' . $primer));
            $mform->addHelpButton('instructions' . $i, 'instructions', 'qbank_genai');
            $mform->hideif('instructions' . $i, 'editpreset');
            $mform->hideif('instructions' . $i, 'preset', 'neq', $i);

            // Example.
            $mform->addElement(
                'textarea',
                'example' . $i,
                get_string('example', 'qbank_genai'),
                'wrap="virtual" rows="10" cols="50"'
            );
            $mform->setType('example' . $i, PARAM_RAW);
            $mform->setDefault('example' . $i, get_config('qbank_genai', 'presetexample' . $primer));
            $mform->addHelpButton('example' . $i, 'example', 'qbank_genai');
            $mform->hideif('example' . $i, 'editpreset');
            $mform->hideif('example' . $i, 'preset', 'neq', $i);
        }

        // Cmid.
        $mform->addElement('hidden', 'cmid', $this->_customdata['cmid']);
        $mform->setType('cmid', PARAM_INT);

        $buttonarray = [];
        $buttonarray[] = &$mform->createElement('submit', 'submitbutton', get_string('generate', 'qbank_genai'));
        $buttonarray[] = &$mform->createElement('cancel', 'cancel', get_string('backtocourse', 'qbank_genai'));
        $mform->addGroup($buttonarray, 'buttonar', '', [' '], false);
    }
    /**
     * Form validation
     *
     * @param array $data
     * @param array $files
     * @return array
     */
    public function validation($data, $files) {
        return [];
    }
}
