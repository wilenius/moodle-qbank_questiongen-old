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
 * Plugin administration pages are defined here.
 *
 * @package     qbank_questiongen
 * @category    admin
 * @copyright   2023 Ruthy Salomon <ruthy.salomon@gmail.com> , Yedidia Klein <yedidia@openapp.co.il>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    $settings = new admin_settingpage('qbank_questiongen_settings', new lang_string('pluginname', 'qbank_questiongen'));

    $provideroptions = [
            'local_ai_manager' => 'Plugin "AI Manager"',
    ];
    $settings->add(new admin_setting_configselect(
            'qbank_questiongen/provider',
            get_string('provider', 'qbank_questiongen'),
            get_string('providerdesc', 'qbank_questiongen'),
            'local_ai_manager',
            $provideroptions
    ));
    // TODO Implement other backends.

    // Number of tries.
    $settings->add(new admin_setting_configtext(
            'qbank_questiongen/numoftries',
            get_string('numoftriesset', 'qbank_questiongen'),
            get_string('numoftriesdesc', 'qbank_questiongen'),
            3,
            PARAM_INT,
            10
    ));

    $settings->add(new admin_setting_configtext(
            'qbank_questiongen/aiidentifier',
            get_string('aiidentifiersetting', 'qbank_questiongen'),
            get_string('aiidentifiersettingdesc', 'qbank_questiongen'),
            'AI generated - ',
            PARAM_TEXT
    ));

    $settings->add(new admin_setting_configtext(
            'qbank_questiongen/aiidentifiertag',
            get_string('aiidentifiertagsetting', 'qbank_questiongen'),
            get_string('aiidentifiertagsettingdesc', 'qbank_questiongen'),
            'aigenerated',
            PARAM_TEXT
    ));

    $settings->add(new admin_setting_configduration(
            'qbank_questiongen/cleanupdelay',
            get_string('cleanupdelay', 'qbank_questiongen'),
            get_string('cleanupdelay', 'qbank_questiongen'),
            30 * DAYSECS,
            DAYSECS
    ));

    // Add text with link to management as setting.
    $settings->add(new admin_setting_description(
            'qbank_questiongen/managepresetspage',
            get_string('linktomanagepresetspage', 'qbank_questiongen'),
            html_writer::link(new moodle_url('/question/bank/questiongen/presets.php'),
                    get_string('managepresets', 'qbank_questiongen'), ['class' => 'btn btn-secondary mb-5'])
    ));
}
