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
 * Report group visibility regression coverage.
 *
 * @package local_missingstudents
 * @copyright 2026 Eduardo Kraus
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_missingstudents;

/**
 * Tests that group-restricted reports do not expose unrelated students.
 *
 * @covers \local_missingstudents\report_service
 */
final class report_service_test extends \advanced_testcase {
    /**
     * Separate group membership applies to both report queries and CSV export.
     */
    public function test_separate_groups_restrict_report_and_export(): void {
        global $DB;

        $this->resetAfterTest();
        $generator = $this->getDataGenerator();
        $course = $generator->create_course(["groupmode" => SEPARATEGROUPS]);
        $context = \context_course::instance($course->id);

        $teacher = $generator->create_user();
        $teacherwithoutgroup = $generator->create_user();
        $studentone = $generator->create_user();
        $studenttwo = $generator->create_user();

        $generator->enrol_user($teacher->id, $course->id, "teacher");
        $generator->enrol_user($teacherwithoutgroup->id, $course->id, "teacher");
        $generator->enrol_user($studentone->id, $course->id, "student");
        $generator->enrol_user($studenttwo->id, $course->id, "student");

        $groupone = $generator->create_group(["courseid" => $course->id]);
        $grouptwo = $generator->create_group(["courseid" => $course->id]);
        $generator->create_group_member(["groupid" => $groupone->id, "userid" => $teacher->id]);
        $generator->create_group_member(["groupid" => $groupone->id, "userid" => $studentone->id]);
        $generator->create_group_member(["groupid" => $grouptwo->id, "userid" => $studenttwo->id]);

        $role = $DB->get_record("role", ["shortname" => "teacher"], "id", MUST_EXIST);
        assign_capability("moodle/site:accessallgroups", CAP_PROHIBIT, $role->id, $context->id);

        $this->setUser($teacher);
        $report = new report_service($course, $context, 10, "course");
        $this->assertSame([$studentone->id], array_keys($report->get_students()));
        $this->assertCount(1, $report->get_missing_students_for_export());

        // Passing [] to get_enrolled_sql() means all groups; never do that here.
        $this->setUser($teacherwithoutgroup);
        $report = new report_service($course, $context, 10, "course");
        $this->assertSame([], $report->get_students());
        $this->assertSame([], $report->get_missing_students_for_export());

        $this->setAdminUser();
        $report = new report_service($course, $context, 10, "course");
        $this->assertCount(2, $report->get_students());
        $this->assertCount(2, $report->get_missing_students_for_export());
    }
}
