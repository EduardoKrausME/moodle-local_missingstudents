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
 * Administration settings.
 *
 * @package local_missingstudents
 * @copyright 2026 Eduardo Kraus
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    $settings = new admin_settingpage(
        "local_missingstudents",
        get_string("pluginname", "local_missingstudents")
    );

    $settings->add(new admin_setting_configselect(
        "local_missingstudents/defaultdays",
        get_string("defaultdays", "local_missingstudents"),
        get_string("defaultdays_help", "local_missingstudents"),
        10,
        [
            10 => get_string("daysoption", "local_missingstudents", 10),
            15 => get_string("daysoption", "local_missingstudents", 15),
            20 => get_string("daysoption", "local_missingstudents", 20),
            30 => get_string("daysoption", "local_missingstudents", 30),
            45 => get_string("daysoption", "local_missingstudents", 45),
            60 => get_string("daysoption", "local_missingstudents", 60),
        ]
    ));

    $settings->add(new admin_setting_configcheckbox(
        "local_missingstudents/showcoursebutton",
        get_string("showcoursebutton", "local_missingstudents"),
        get_string("showcoursebutton_help", "local_missingstudents"),
        1
    ));

    $ADMIN->add("localplugins", $settings);
}
