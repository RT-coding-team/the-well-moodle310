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
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.
/**
 * Sync the current course tests and quiz reports with the cloud.
 *
 * If you want to use on command line, use `php sync.php true 'PASSWORD'`. Use single quotes on the password to allow special characters.
 */
$cliScript = false;
if ((isset($argv)) && (isset($argv[1]))) {
    $cliScript = boolval($argv[1]);
}

define('CLI_SCRIPT', $cliScript);

set_time_limit(0);

require_once(dirname(dirname(dirname(__FILE__))) . DIRECTORY_SEPARATOR . 'config.php');
require_once(dirname(dirname(dirname(__FILE__))) . DIRECTORY_SEPARATOR . 'course' . DIRECTORY_SEPARATOR . 'lib.php');
require_once(dirname(dirname(dirname(__FILE__))) . DIRECTORY_SEPARATOR . 'mod' . DIRECTORY_SEPARATOR . 'quiz' . DIRECTORY_SEPARATOR . 'locallib.php');
require_once(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'utilities.php');

$quizQuery = "SELECT DISTINCT '' || u.id || '#' || COALESCE(quiza.attempt, 0) AS uniqueid, " . 
"(CASE WHEN (quiza.state = 'finished' AND NOT EXISTS ( SELECT 1 FROM {quiz_attempts} qa2 " .
"WHERE qa2.quiz = quiza.quiz AND qa2.userid = quiza.userid AND qa2.state = 'finished' AND " .
"( COALESCE(qa2.sumgrades, 0) > COALESCE(quiza.sumgrades, 0) OR (COALESCE(qa2.sumgrades, 0) = COALESCE(quiza.sumgrades, 0) " .
"AND qa2.attempt < quiza.attempt) ))) THEN 1 ELSE 0 END) AS gradedattempt, quiza.uniqueid AS usageid, quiza.id AS attempt, " .
"u.id AS userid, u.idnumber, u.firstnamephonetic,u.lastnamephonetic,u.middlename,u.alternatename,u.firstname,u.lastname, " .
"u.institution, u.department, u.email, quiza.state, quiza.sumgrades, quiza.timefinish, quiza.timestart, CASE WHEN quiza.timefinish = 0 " .
"THEN null WHEN quiza.timefinish > quiza.timestart THEN quiza.timefinish - quiza.timestart ELSE 0 END AS duration, " .
"COALESCE(( SELECT MAX(qqr.regraded) FROM {quiz_overview_regrades} qqr WHERE qqr.questionusageid = quiza.uniqueid ), -1) " .
"AS regraded FROM {user} u LEFT JOIN {quiz_attempts} quiza ON quiza.userid = u.id AND quiza.quiz = :quizid JOIN {user_enrolments} " .
"ej1_ue ON ej1_ue.userid = u.id JOIN {enrol} ej1_e ON (ej1_e.id = ej1_ue.enrolid AND ej1_e.courseid = :courseid) JOIN " .
"(SELECT DISTINCT userid FROM {role_assignments} WHERE contextid IN (1,565,717,738) AND roleid IN (5) ) ra ON ra.userid = u.id " .
"WHERE quiza.preview = 0 AND quiza.id IS NOT NULL AND u.deleted = 0 AND u.id <> 1 AND u.deleted = 0";

$courses = get_courses();
$approvedActivities = ['choice', 'quiz', 'feedback', 'survey'];

$tests = [];
foreach ($courses as $course) {
    if (intval($course->id) === 1) {
        continue;
    }
    $courseDetails = [
        'id'            =>  $course->id,
        'fullname'      =>  $course->fullname,
        'shortname'     =>  $course->shortname,
        'summary'       =>  strip_tags($course->summary),
        'created_on'    =>  $course->timecreated,
        'modified_on'   =>  $course->timemodified
    ];
    $activities = get_array_of_activities($course->id);
    foreach ($activities as $activity) {
        if (!in_array($activity->mod, $approvedActivities)) {
            continue;
        }
        $activityDetails = null;
        if ($activity->mod === 'quiz') {
            $quiz = $DB->get_record('quiz', array('id' => $activity->id));
            if (!$quiz) {
                continue;
            }
            $activityDetails = [
                'id'            =>  $quiz->id,
                'type'          =>  'quiz',
                'name'          =>  $quiz->name,
                'intro'         =>  strip_tags($quiz->intro),
                'max_grade'     =>  $quiz->grade,
                'created_on'    =>  $quiz->timecreated,
                'modified_on'   =>  $quiz->timemodified,
                'results'       =>  []
            ];
            $results = $DB->get_records_sql($quizQuery, ['quizid'  =>  $quiz->id, 'courseid' => $course->id]);
            foreach ($results as $result) {
                $activityDetails['results'][] = [
                    'firstname'     =>  $result->firstname,
                    'lastname'      =>  $result->lastname,
                    'duration'      =>  $result->duration,
                    'grade'         =>  quiz_rescale_grade($result->sumgrades, $quiz),
                    'state'         =>  quiz_attempt_state_name($result->state),
                    'started_on'    =>  $result->timestart,
                    'finished_on'   =>  $result->timefinish,
                ];
            }
        }
        if ($activityDetails) {
            $tests[] = [
                'course'    =>  $courseDetails,
                'activity'  =>  $activityDetails
            ];
        }
    }
}
print_r($tests);