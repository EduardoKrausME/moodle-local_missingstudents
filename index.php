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
 * Course missing students dashboard.
 *
 * @package local_missingstudents
 * @copyright 2026 Eduardo Kraus
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . "/../../config.php");
require_once($CFG->libdir . "/csvlib.class.php");

$courseid = optional_param("id", 0, PARAM_INT);
$days = optional_param("days", 0, PARAM_INT);
$scope = optional_param("scope", "course", PARAM_ALPHA);
$download = optional_param("download", "", PARAM_ALPHA);

if ($courseid === 0) {
    require_login();

    $context = context_system::instance();
    $courses = get_user_capability_course(
        "local/missingstudents:viewreport",
        null,
        true,
        "fullname,shortname,visible",
        "fullname ASC"
    );

    $courselist = [];
    foreach ($courses ?: [] as $courseitem) {
        if ((int)$courseitem->id === SITEID) {
            continue;
        }

        $coursecontext = context_course::instance($courseitem->id);
        if (empty($courseitem->visible) && !has_capability("moodle/course:viewhiddencourses", $coursecontext)) {
            continue;
        }

        $courselist[] = [
            "id" => (int)$courseitem->id,
            "fullname" => format_string($courseitem->fullname, true, ["context" => $coursecontext]),
            "shortname" => format_string($courseitem->shortname, true, ["context" => $coursecontext]),
            "url" => (new moodle_url("/local/missingstudents/", ["id" => $courseitem->id]))->out(false),
            "hidden" => empty($courseitem->visible),
        ];
    }

    $PAGE->set_url(new moodle_url("/local/missingstudents/"));
    $PAGE->set_context($context);
    $PAGE->set_pagelayout("report");
    $PAGE->set_title(get_string("courselist", "local_missingstudents"));
    $PAGE->set_heading(get_string("pluginname", "local_missingstudents"));

    echo $OUTPUT->header();
    echo $OUTPUT->render_from_template("local_missingstudents/course_list", [
        "courses" => $courselist,
        "hascourses" => !empty($courselist),
        "coursecount" => count($courselist),
    ]);
    echo $OUTPUT->footer();
    exit;
}

$course = get_course($courseid);
require_login($course);

$context = context_course::instance($course->id);
require_capability("local/missingstudents:viewreport", $context);

if ($days <= 0) {
    $days = (int)get_config("local_missingstudents", "defaultdays");
    if ($days <= 0) {
        $days = 10;
    }
}
$days = min(3650, max(1, $days));
$scope = $scope === "site" ? "site" : "course";

$service = new \local_missingstudents\report_service($course, $context, $days, $scope);

if ($download === "csv") {
    $filename = clean_filename("missing-students-{$course->shortname}-{$days}-days");
    $csv = new csv_export_writer();
    $csv->set_filename($filename);
    $csv->add_data([
        get_string("student", "local_missingstudents"),
        get_string("email"),
        get_string("idnumber"),
        get_string("daysmissingcsv", "local_missingstudents"),
        get_string("relevantaccesscsv", "local_missingstudents"),
        get_string("courseaccess", "local_missingstudents"),
        get_string("siteaccess", "local_missingstudents"),
        get_string("lastlogin", "local_missingstudents"),
    ]);

    foreach ($service->get_missing_students_for_export() as $row) {
        $csv->add_data([
            $row["fullname"],
            $row["email"],
            $row["idnumber"],
            $row["daysmissing"] === null ? get_string("never", "local_missingstudents") : $row["daysmissing"],
            $row["relevantaccess"] > 0 ? userdate($row["relevantaccess"]) : get_string("never", "local_missingstudents"),
            $row["courseaccess"] > 0 ? userdate($row["courseaccess"]) : get_string("never", "local_missingstudents"),
            $row["siteaccess"] > 0 ? userdate($row["siteaccess"]) : get_string("never", "local_missingstudents"),
            $row["lastlogin"] > 0 ? userdate($row["lastlogin"]) : get_string("never", "local_missingstudents"),
        ]);
    }
    $csv->download_file();
    exit;
}

$PAGE->set_url(new moodle_url("/local/missingstudents/", [
    "id" => $course->id,
    "days" => $days,
    "scope" => $scope,
]));
$PAGE->set_context($context);
$PAGE->set_course($course);
$PAGE->set_pagelayout("report");
$PAGE->set_title(get_string("pluginname", "local_missingstudents"));
$PAGE->set_heading($course->fullname);
$PAGE->requires->js_call_amd("local_missingstudents/dashboard", "init");

$event = \local_missingstudents\event\report_viewed::create([
    "context" => $context,
    "courseid" => $course->id,
]);
$event->trigger();

$data = $service->get_dashboard_data();

echo $OUTPUT->header();
echo $OUTPUT->render_from_template("local_missingstudents/dashboard", $data);
echo $OUTPUT->footer();
