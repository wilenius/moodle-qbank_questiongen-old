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

namespace qbank_questiongen\form;

defined('MOODLE_INTERNAL') || die;

global $CFG;
require_once($CFG->libdir . '/formslib.php');

/**
 * Form for editing a preset.
 *
 * @package    qbank_questiongen
 * @copyright  2025 ISB Bayern
 * @author     Philipp Memmel
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class edit_preset_form extends \moodleform {


    #[\Override]
    public function definition() {
        $mform = &$this->_form;
        if (!empty($this->_customdata['id'])) {
            $mform->addElement('hidden', 'id', $this->_customdata['id']);
            $mform->setType('id', PARAM_INT);
        }

        $textelementparams = ['style' => 'width: 100%'];
        $textareaparams = ['rows' => 10, 'style' => 'width: 100%'];

        $mform->addElement('text', 'name', get_string('name', 'qbank_questiongen'), $textelementparams);
        $mform->setType('name', PARAM_TEXT);

        $mform->addElement('textarea', 'primer', get_string('primer', 'qbank_questiongen'), $textareaparams);
        $mform->setType('primer', PARAM_RAW);

        $mform->addElement('textarea', 'instructions', get_string('instructions', 'qbank_questiongen'), $textareaparams);
        $mform->setType('instructions', PARAM_RAW);

        $mform->addElement('textarea', 'example', get_string('example', 'qbank_questiongen'), $textareaparams);
        $mform->setType('example', PARAM_RAW);

        $this->add_action_buttons();

    }

    #[\Override]
    public function validation($data, $files): array {
        $errors = [];
        if (empty(trim($data['name']))) {
            $errors['name'] = get_string('errorformfieldempty', 'qbank_questiongen');
        }
        if (empty(trim($data['primer']))) {
            $errors['primer'] = get_string('errorformfieldempty', 'qbank_questiongen');
        }
        if (empty(trim($data['instructions']))) {
            $errors['instructions'] = get_string('errorformfieldempty', 'qbank_questiongen');
        }
        if (trim(empty($data['example']))) {
            $errors['example'] = get_string('errorformfieldempty', 'qbank_questiongen');
        }
        return $errors;
    }
}
