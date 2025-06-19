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

/**
 * Delete file content from the qbank_questiongen_resource_cache table if the corresponding file has been deleted.
 *
 * Will only be deleted if no other file with this content hash exists.
 *
 * @param stdClass $file the file record from the "files" table
 */
function qbank_questiongen_after_file_deleted(stdClass $file): void {
    global $DB;
    // If there is still another file that has the identical contenthash we keep our cached contents.
    if ($DB->record_exists('files', ['contenthash' => $file->contenthash])) {
        return;
    }
    $DB->delete_records('qbank_questiongen_resource_cache', ['contenthash' => $file->contenthash]);
}
