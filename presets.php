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
 * Management site: Manage, create and edit presets.
 *
 * @package    qbank_questiongen
 * @copyright  2025 ISB Bayern
 * @author     Philipp Memmel
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../../config.php');

require_login();

global $DB, $OUTPUT, $PAGE;

$url = new moodle_url('/qestion/bank/presets.php', []);
$PAGE->set_url($url);
$PAGE->set_context(context_system::instance());
$PAGE->set_heading(get_string('managepresets', 'qbank_questiongen'));

require_capability('qbank/questiongen:manage', context_system::instance());

echo $OUTPUT->header();

$presetsrecords = $DB->get_records('qbank_questiongen_preset');
$presets = [];
foreach ($presetsrecords as $preset) {
    $presets[] = [
            'id' => $preset->id,
            'name' => $preset->name,
            'primer' => format_text($preset->primer, FORMAT_PLAIN, ['filter' => false]),
            'instructions' => format_text($preset->instructions, FORMAT_PLAIN, ['filter' => false]),
            'example' => '<pre><code>' . s($preset->example) . '</code></pre>',
    ];
}

echo $OUTPUT->render_from_template('qbank_questiongen/presets', ['presets' => array_values($presets)]);

echo $OUTPUT->footer();
