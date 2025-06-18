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
 * Unit tests for the qbank_questiongen utility functions.
 *
 * @package   qbank_questiongen
 * @copyright 2025 ISB Bayern
 * @author    Philipp Memmel
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class utils_test extends \advanced_testcase {

    /**
     * Tests the functionality that substitutes certain placeholders in a string.
     *
     * @covers \qbank_questiongen\local\utils::filter_prompts
     */
    public function test_filter_prompts(): void {
        global $USER;
        $teststring = 'This is a teststring that includes the language placeholder {{currentlang}}, which should be replaced';
        $expectedresultstring = 'This is a teststring that includes the language placeholder English, which should be replaced';
        $this->assertEquals($expectedresultstring, utils::filter_prompts($teststring));

        $USER->lang = 'de';
        $expectedresultstring = 'This is a teststring that includes the language placeholder German, which should be replaced';
        $this->assertEquals($expectedresultstring, utils::filter_prompts($teststring));
    }
}
