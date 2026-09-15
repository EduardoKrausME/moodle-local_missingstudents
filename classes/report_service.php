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
 * Report data service.
 *
 * @package local_missingstudents
 * @copyright 2026 Eduardo Kraus
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_missingstudents;

/**
 * Builds the missing students report for one course.
 */
class report_service {
    /** @var \stdClass */
    private $course;

    /** @var \context_course */
    private $context;

    /** @var int */
    private $days;

    /** @var string */
    private $scope;

    /** @var int */
    private $now;

    /**
     * Construct
     * @param \stdClass $course
     * @param \context_course $context
     * @param int $days
     * @param string $scope
     */
    public function __construct(\stdClass $course, \context_course $context, int $days, string $scope) {
        $this->course = $course;
        $this->context = $context;
        $this->days = max(1, $days);
        $this->scope = $scope === "site" ? "site" : "course";
        $this->now = time();
    }

    /**
     * Returns all tracked students and their latest access information.
     *
     * Uses Moodle's enrolment SQL and $DB->get_records_sql() intentionally so the
     * report remains efficient and does not touch the very large log table.
     *
     * @return array
     */
    public function get_students(): array {
        global $DB;

        [$enrolledsql, $params] = get_enrolled_sql(
            $this->context,
            "moodle/course:isincompletionreports",
            0,
            true
        );

        $sql = "SELECT u.id,
                       u.firstname,
                       u.lastname,
                       u.firstnamephonetic,
                       u.lastnamephonetic,
                       u.middlename,
                       u.alternatename,
                       u.picture,
                       u.imagealt,
                       u.email,
                       u.idnumber,
                       u.lastaccess AS siteaccess,
                       u.lastlogin,
                       COALESCE(ula.timeaccess, 0) AS courseaccess
                  FROM ({$enrolledsql}) enrolled
                  JOIN {user} u ON u.id = enrolled.id
             LEFT JOIN {user_lastaccess} ula
                    ON ula.userid = u.id
                   AND ula.courseid = :missingstudentscourseid
                 WHERE u.deleted = 0
                   AND u.suspended = 0
              ORDER BY u.lastname ASC, u.firstname ASC";

        $params["missingstudentscourseid"] = $this->course->id;
        return $DB->get_records_sql($sql, $params);
    }

    /**
     * Returns the complete dashboard data prepared for Mustache.
     *
     * @param \core_renderer $output
     * @return array
     */
    public function get_dashboard_data(\core_renderer $output): array {
        $students = $this->get_students();
        $missing = [];
        $active = 0;
        $never = 0;
        $critical = 0;
        $totaldays = 0;
        $totaldayscount = 0;

        foreach ($students as $student) {
            $access = $this->get_relevant_access($student);
            $ismissing = $access === 0 || $access < ($this->now - ($this->days * DAYSECS));

            if (!$ismissing) {
                $active++;
                continue;
            }

            $view = $this->build_student_view($student, $output);
            $missing[] = $view;

            if ($view["never"]) {
                $never++;
                $critical++;
            } else {
                $totaldays += $view["daysmissing"];
                $totaldayscount++;
                if ($view["daysmissing"] >= 30) {
                    $critical++;
                }
            }
        }

        usort($missing, static function(array $a, array $b): int {
            if ($a["never"] !== $b["never"]) {
                return $a["never"] ? -1 : 1;
            }
            return $b["daysmissing"] <=> $a["daysmissing"];
        });

        $missingcount = count($missing);
        $trackedcount = count($students);
        $missingpercent = $trackedcount > 0 ? round(($missingcount / $trackedcount) * 100, 1) : 0;
        $activepercent = $trackedcount > 0 ? round(($active / $trackedcount) * 100, 1) : 0;
        $average = $totaldayscount > 0 ? (int)round($totaldays / $totaldayscount) : 0;

        $buckets = $this->build_buckets($missing, $missingcount);
        $riskgroups = $this->build_risk_groups($missing, $missingcount);
        $topstudents = array_slice($missing, 0, 8);
        $maxdays = 0;
        foreach ($topstudents as $student) {
            if (!$student["never"]) {
                $maxdays = max($maxdays, $student["daysmissing"]);
            }
        }
        foreach ($topstudents as &$student) {
            $student["barpercent"] = $student["never"] ? 100 : ($maxdays > 0
                ? max(12, (int)round(($student["daysmissing"] / $maxdays) * 100))
                : 12);
        }
        unset($student);

        return [
            "courseid" => $this->course->id,
            "coursename" => format_string($this->course->fullname),
            "days" => $this->days,
            "scopecourse" => $this->scope === "course",
            "scopesite" => $this->scope === "site",
            "scopelabel" => get_string($this->scope === "course" ? "scope_course" : "scope_site", "local_missingstudents"),
            "trackedcount" => $trackedcount,
            "missingcount" => $missingcount,
            "activecount" => $active,
            "nevercount" => $never,
            "criticalcount" => $critical,
            "missingpercent" => $missingpercent,
            "activepercent" => $activepercent,
            "averagedays" => $average,
            "students" => $missing,
            "hasstudents" => $missingcount > 0,
            "nostudents" => $missingcount === 0,
            "buckets" => $buckets,
            "riskgroups" => $riskgroups,
            "topstudents" => $topstudents,
            "hastopstudents" => !empty($topstudents),
            "thresholdoptions" => $this->get_threshold_options(),
            "downloadurl" => (new \moodle_url("/local/missingstudents/index.php", [
                "id" => $this->course->id,
                "days" => $this->days,
                "scope" => $this->scope,
                "download" => "csv",
            ]))->out(false),
            "coursescopeurl" => (new \moodle_url("/local/missingstudents/index.php", [
                "id" => $this->course->id,
                "days" => $this->days,
                "scope" => "course",
            ]))->out(false),
            "sitescopeurl" => (new \moodle_url("/local/missingstudents/index.php", [
                "id" => $this->course->id,
                "days" => $this->days,
                "scope" => "site",
            ]))->out(false),
        ];
    }

    /**
     * Returns report rows for CSV export.
     *
     * @return array
     */
    public function get_missing_students_for_export(): array {
        $rows = [];
        foreach ($this->get_students() as $student) {
            $access = $this->get_relevant_access($student);
            if ($access !== 0 && $access >= ($this->now - ($this->days * DAYSECS))) {
                continue;
            }

            $daysmissing = $access === 0 ? null : max(0, (int)floor(($this->now - $access) / DAYSECS));
            $rows[] = [
                "fullname" => fullname($student),
                "email" => $student->email,
                "idnumber" => $student->idnumber,
                "daysmissing" => $daysmissing,
                "relevantaccess" => $access,
                "courseaccess" => (int)$student->courseaccess,
                "siteaccess" => (int)$student->siteaccess,
                "lastlogin" => (int)$student->lastlogin,
            ];
        }
        return $rows;
    }

    /**
     * get_relevant_access
     *
     * @param \stdClass $student
     * @return int
     */
    private function get_relevant_access(\stdClass $student): int {
        return $this->scope === "site" ? (int)$student->siteaccess : (int)$student->courseaccess;
    }

    /**
     * build_student_view
     *
     * @param \stdClass $student
     * @param \core_renderer $output
     * @return array
     * @throws \coding_exception
     * @throws \core\exception\moodle_exception
     */
    private function build_student_view(\stdClass $student, \core_renderer $output): array {
        $access = $this->get_relevant_access($student);
        $never = $access === 0;
        $daysmissing = $never ? 99999 : max(0, (int)floor(($this->now - $access) / DAYSECS));
        $risk = $this->get_risk($never, $daysmissing);

        return [
            "id" => $student->id,
            "fullname" => fullname($student),
            "email" => $student->email,
            "idnumber" => $student->idnumber,
            "profileurl" => (new \moodle_url("/user/view.php", [
                "id" => $student->id,
                "course" => $this->course->id,
            ]))->out(false),
            "picture" => $output->user_picture($student, ["size" => 48, "link" => false]),
            "never" => $never,
            "daysmissing" => $never ? 0 : $daysmissing,
            "dayssort" => $never ? 99999 : $daysmissing,
            "dayslabel" => $never
                ? get_string("neveraccessed", "local_missingstudents")
                : get_string("dayswithoutaccess", "local_missingstudents", $daysmissing),
            "relevantaccess" => $never ? get_string("never", "local_missingstudents") : userdate($access),
            "courseaccess" => (int)$student->courseaccess > 0
                ? userdate((int)$student->courseaccess)
                : get_string("never", "local_missingstudents"),
            "siteaccess" => (int)$student->siteaccess > 0
                ? userdate((int)$student->siteaccess)
                : get_string("never", "local_missingstudents"),
            "lastlogin" => (int)$student->lastlogin > 0
                ? userdate((int)$student->lastlogin)
                : get_string("never", "local_missingstudents"),
            "riskkey" => $risk["key"],
            "risklabel" => $risk["label"],
            "riskclass" => $risk["class"],
        ];
    }

    /**
     * get_risk
     *
     * @param bool $never
     * @param int $days
     * @return array
     * @throws \coding_exception
     */
    private function get_risk(bool $never, int $days): array {
        if ($never) {
            return [
                "key" => "never",
                "label" => get_string("risk_never", "local_missingstudents"),
                "class" => "risk-never",
            ];
        }
        if ($days >= 60) {
            return [
                "key" => "critical",
                "label" => get_string("risk_critical", "local_missingstudents"),
                "class" => "risk-critical",
            ];
        }
        if ($days >= 30) {
            return [
                "key" => "high",
                "label" => get_string("risk_high", "local_missingstudents"),
                "class" => "risk-high",
            ];
        }
        if ($days >= 15) {
            return [
                "key" => "medium",
                "label" => get_string("risk_medium", "local_missingstudents"),
                "class" => "risk-medium",
            ];
        }
        return [
            "key" => "attention",
            "label" => get_string("risk_attention", "local_missingstudents"),
            "class" => "risk-attention",
        ];
    }

    /**
     * build_buckets
     *
     * @param array $students
     * @param int $total
     * @return array
     * @throws \coding_exception
     */
    private function build_buckets(array $students, int $total): array {
        $definitions = [
            "attention" => ["label" => get_string("bucket_10_14", "local_missingstudents"), "count" => 0],
            "medium" => ["label" => get_string("bucket_15_29", "local_missingstudents"), "count" => 0],
            "high" => ["label" => get_string("bucket_30_59", "local_missingstudents"), "count" => 0],
            "critical" => ["label" => get_string("bucket_60_plus", "local_missingstudents"), "count" => 0],
            "never" => ["label" => get_string("bucket_never", "local_missingstudents"), "count" => 0],
        ];

        foreach ($students as $student) {
            $definitions[$student["riskkey"]]["count"]++;
        }

        $result = [];
        foreach ($definitions as $key => $definition) {
            $count = $definition["count"];
            $result[] = [
                "key" => $key,
                "label" => $definition["label"],
                "count" => $count,
                "percent" => $total > 0 ? round(($count / $total) * 100, 1) : 0,
            ];
        }
        return $result;
    }

    /**
     * build_risk_groups
     *
     * @param array $students
     * @param int $total
     * @return array
     * @throws \coding_exception
     */
    private function build_risk_groups(array $students, int $total): array {
        $groups = [
            "attention" => 0,
            "medium" => 0,
            "high" => 0,
            "critical" => 0,
            "never" => 0,
        ];
        foreach ($students as $student) {
            $groups[$student["riskkey"]]++;
        }

        $result = [];
        foreach ($groups as $key => $count) {
            $result[] = [
                "key" => $key,
                "count" => $count,
                "percent" => $total > 0 ? round(($count / $total) * 100, 2) : 0,
                "label" => get_string("risk_{$key}", "local_missingstudents"),
            ];
        }
        return $result;
    }

    /**
     * get_threshold_options
     *
     * @return array
     * @throws \coding_exception
     */
    private function get_threshold_options(): array {
        $options = [];
        foreach ([10, 15, 20, 30, 45, 60] as $value) {
            $options[] = [
                "value" => $value,
                "label" => get_string("daysoption", "local_missingstudents", $value),
                "selected" => $value === $this->days,
            ];
        }
        return $options;
    }
}
