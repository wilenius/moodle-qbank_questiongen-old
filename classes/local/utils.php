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

use Locale;
use qbank_questiongen\form\story_form;
use stdClass;

/**
 * Utility class for qbank_questiongen.
 *
 * @package    qbank_questiongen
 * @copyright  2025 ISB Bayern
 * @author     Philipp Memmel
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class utils {

    /**
     * Stores the data from the story_form.php form in the qbank_questiongen table.
     *
     * @param stdClass $data the data submitted by the form
     * @return array the questiongen ids of the records that have been inserted into the DB
     */
    public static function store_questiongen_data(stdClass $data): array {
        global $DB, $USER;
        // ID of the selected preset.
        $preset = $data->preset;

        // Create the DB entry.
        $dbrecord = new \stdClass();
        $dbrecord->mode = $data->mode;
        $dbrecord->numoftries = get_config('qbank_questiongen', 'numoftries');
        $dbrecord->aiidentifier = !empty($data->addidentifier) ? 1 : 0;
        $dbrecord->category = explode(',', $data->category)[0];
        $dbrecord->userid = $USER->id;
        $dbrecord->timecreated = time();
        $dbrecord->timemodified = time();
        $dbrecord->tries = 1;

        if (intval($data->mode) === story_form::QUESTIONGEN_MODE_TOPIC) {
            $dbrecord->story = self::filter_prompts($data->topic);
        } else if (intval($data->mode) === story_form::QUESTIONGEN_MODE_STORY) {
            $dbrecord->story = self::filter_prompts($data->story);
        } else {
            // If the story should be created from course contents, we just leave the story field empty.
            // It will be filled from inside the adhoc task later on.
            $dbrecord->story = '';
        }

        $dbrecord->llmresponse = '';
        $dbrecord->success = '';
        $dbrecord->primer = self::filter_prompts($data->{'primer' . $preset});
        $dbrecord->instructions = self::filter_prompts($data->{'instructions' . $preset});
        $dbrecord->example = $data->{'example' . $preset};

        $i = 0;
        $questiongenids = [];
        while ($i < $data->numofquestions) {
            $dbrecord->uniqid = uniqid($USER->id, true);

            $insertedid = $DB->insert_record('qbank_questiongen', $dbrecord);
            if ($insertedid === 0) {
                throw new \moodle_exception('There was an error when storing the genai processing data to db.');
            }
            $questiongenids[] = $insertedid;

            $i++;
        }
        return $questiongenids;
    }

    /**
     * Applies local filters to the prompts.
     *
     * For example, replaces {{currentlang}} by the current language of the user.
     *
     * @param string $prompt The prompt to filter
     * @return string The filtered prompt
     */
    public static function filter_prompts(string $prompt): string {
        return str_replace('{{currentlang}}', Locale::getDisplayLanguage(current_language(), 'en'), $prompt);
    }
}
