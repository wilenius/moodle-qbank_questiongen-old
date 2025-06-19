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

use core_question\local\bank\navigation_node_base;

/**
 * Plugin entrypoint for qbank.
 *
 * @package    qbank_questiongen
 * @copyright  ISB Bayern, 2024
 * @author     Dr. Peter Mayer
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class plugin_feature extends \core_question\local\bank\plugin_features_base {

    #[\Override]
    public function get_navigation_node(): ?navigation_node_base {
        global $PAGE, $USER;

        $provider = get_config('qbank_questiongen', 'provider');
        if ($provider === 'local_ai_manager') {
            $aiconfig = \local_ai_manager\ai_manager_utils::get_ai_config($USER, $PAGE->context->id, null, ['questiongeneration']);
            if ($aiconfig['availability']['available'] === \local_ai_manager\ai_manager_utils::AVAILABILITY_HIDDEN
                    || $aiconfig['purposes'][0]['available'] === \local_ai_manager\ai_manager_utils::AVAILABILITY_HIDDEN) {
                return null;
            }
        }

        if (!has_capability('moodle/question:add', $PAGE->context)) {
            return null;
        }

        return new navigation();
    }
}
