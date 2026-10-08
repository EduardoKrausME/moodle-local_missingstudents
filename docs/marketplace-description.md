# Moodle Plugins Directory listing: Missing students

Missing students (`local_missingstudents`) helps teachers and course managers identify learners who have stopped accessing their courses. Its early-warning dashboard highlights students who may benefit from follow-up before they disengage further.

The plugin uses Moodle's existing enrolment and access records. It can measure inactivity based on the last visit to a specific course or the last access anywhere in Moodle. The report shows key indicators, risk categories, inactivity bands and a searchable student list, and results can be downloaded as CSV for follow-up or retention workflows.

Teachers, editing teachers, course creators and managers can view the report by default, using the `local/missingstudents:viewreport` capability. The monitored students are defined by Moodle's `moodle/course:isincompletionreports` capability. In courses using Separate groups, staff without `moodle/site:accessallgroups` can only see students in their own groups, including in CSV exports.

Site administrators can set the default inactivity threshold and enable or disable the shortcut displayed on course pages. Authorized report viewers can change the threshold and choose between course inactivity and Moodle-wide inactivity.

**Requirements:** Moodle 4.1 or newer. Install as `local/missingstudents`. For ZIP installation, use the `missingstudents.zip` asset attached to a GitHub release rather than GitHub's automatically generated source archive.

**Documentation:** https://github.com/EduardoKrausME/moodle-local_missingstudents

---

**Publisher note:** Copy this description into the Moodle Plugins Directory listing. Editing a GitHub repository does not update the directory listing automatically.
