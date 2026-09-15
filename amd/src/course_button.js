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
 * course_button.js
 *
 * @package   local_missingstudents
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(["jquery"], function($) {
    return {
        init: function(config) {
            if (!config || !config.url || $("#local-missingstudents-course-button").length) {
                return;
            }

            var $button = $("<a>", {
                id: "local-missingstudents-course-button",
                href: config.url,
                class: "btn btn-outline-primary local-missingstudents-course-button",
                text: config.label
            });

            var $header = $("#page-header");
            var $target = $header.find(".page-header-headings").first().parent();

            if (!$target.length) {
                $target = $header.find(".d-flex").first();
            }
            if (!$target.length) {
                $target = $(".secondary-navigation").first();
            }
            if (!$target.length) {
                return;
            }

            $button.appendTo($target);
        }
    };
});
