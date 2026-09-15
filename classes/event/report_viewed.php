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
 * Report viewed event.
 *
 * @package local_missingstudents
 * @copyright 2026 Eduardo Kraus
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_missingstudents\event;

/**
 * Event fired when the report is viewed.
 */
class report_viewed extends \core\event\base {
    /**
     * init
     *
     * @return void
     */
    protected function init(): void {
        $this->data["crud"] = "r";
        $this->data["edulevel"] = self::LEVEL_OTHER;
    }

    /**
     * get_name
     *
     * @return string
     * @throws \coding_exception
     */
    public static function get_name(): string {
        return get_string("eventreportviewed", "local_missingstudents");
    }

    /**
     * get_description
     *
     * @return string
     */
    public function get_description(): string {
        return "The user with id '{$this->userid}' viewed the missing students report for course id '{$this->courseid}'.";
    }

    /**
     * get_url
     *
     * @return \moodle_url
     * @throws \core\exception\moodle_exception
     */
    public function get_url(): \moodle_url {
        return new \moodle_url("/local/missingstudents/index.php", ["id" => $this->courseid]);
    }
}
