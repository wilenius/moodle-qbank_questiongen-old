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

/**
 * Hook listener callbacks.
 *
 * @package    qbank_questiongen
 * @copyright  2025 ISB Bayern
 * @author     Philipp Memmel
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class hook_callbacks {

    /**
     * Provide additional information about which purposes are being used by this plugin.
     *
     * @param \local_ai_manager\hook\purpose_usage $hook the purpose_usage hook object
     */
    public static function handle_purpose_usage(\local_ai_manager\hook\purpose_usage $hook): void {
        $hook->set_component_displayname('qbank_questiongen',
                get_string('pluginname_userfaced', 'qbank_questiongen'));
        $hook->add_purpose_usage_description('questiongeneration', 'qbank_questiongen',
                get_string('purposeplacedescription_questiongeneration', 'qbank_questiongen'));
        $hook->add_purpose_usage_description('itt', 'qbank_questiongen',
                get_string('purposeplacedescription_itt', 'qbank_questiongen'));
    }
}
