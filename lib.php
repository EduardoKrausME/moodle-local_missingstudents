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
 * Moodle callbacks for course integration.
 *
 * @package local_missingstudents
 * @copyright 2026 Eduardo Kraus
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Adds the course report shortcut and loads the course header button.
 *
 * @param navigation_node $navigation
 * @return void
 */
function local_missingstudents_extend_navigation_course(navigation_node $navigation): void {
    global $COURSE, $PAGE;

    if ((int)$COURSE->id === SITEID) {
        return;
    }

    $context = context_course::instance($COURSE->id);
    if (!has_capability("local/missingstudents:viewreport", $context)) {
        return;
    }

    $url = new moodle_url("/local/missingstudents/index.php", ["id" => $COURSE->id]);

    $node = navigation_node::create(
        get_string("coursenavigation", "local_missingstudents"),
        $url,
        navigation_node::TYPE_SETTING,
        null,
        "local_missingstudents",
        new pix_icon("i/report", get_string("coursenavigation", "local_missingstudents"))
    );
    $navigation->add_node($node);

    if ((int)get_config("local_missingstudents", "showcoursebutton") === 0) {
        return;
    }

    $PAGE->requires->js_call_amd("local_missingstudents/course_button", "init", [[
        "url" => $url->out(false),
        "label" => get_string("coursebutton", "local_missingstudents"),
    ]]);
}
