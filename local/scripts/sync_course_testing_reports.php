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
 * If you want to use on command line, use `php sync_course_testing_reports.php`.
 */
define('CLI_SCRIPT', true);

set_time_limit(0);

require_once(dirname(dirname(dirname(__FILE__))) . DIRECTORY_SEPARATOR . 'config.php');
require_once(dirname(dirname(dirname(__FILE__))) . DIRECTORY_SEPARATOR . 'course' . DIRECTORY_SEPARATOR . 'lib.php');
require_once(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'classes' . DIRECTORY_SEPARATOR . 'serializers' . DIRECTORY_SEPARATOR . 'AssignmentSerializer.php');
require_once(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'classes' . DIRECTORY_SEPARATOR . 'serializers' . DIRECTORY_SEPARATOR . 'FeedbackSerializer.php');
require_once(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'classes' . DIRECTORY_SEPARATOR . 'serializers' . DIRECTORY_SEPARATOR . 'QuizSerializer.php');
require_once(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'classes' . DIRECTORY_SEPARATOR . 'serializers' . DIRECTORY_SEPARATOR . 'SurveySerializer.php');

$courses = get_courses();
$approvedActivities = ['assign', 'feedback', 'quiz', 'survey'];

$assignments = [];
$feedback = [];
$quizes = [];
$surveys = [];
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
        $serializer = null;
        if ($activity->mod === 'quiz') {
            $serializer = new QuizSerializer($activity->id, $DB);
            $activityDetails = $serializer->details();
            if (!empty($activityDetails)) {
                $activityDetails['results'] = $serializer->results($course->id);
            }
        } else if ($activity->mod === 'survey') {
            $serializer = new SurveySerializer($activity->id, $DB);
            $activityDetails = $serializer->details();
            if (!empty($activityDetails)) {
                $activityDetails['results'] = $serializer->results($course->id, $activity->cm);
            }
        } else if ($activity->mod === 'assign') {
            $serializer = new AssignmentSerializer($activity->id, $DB);
            $activityDetails = $serializer->details();
            if (!empty($activityDetails)) {
                $activityDetails['results'] = $serializer->results($course->id, $activity->cm);
            }
        } else if ($activity->mod === 'feedback') {
            $serializer = new FeedbackSerializer($activity->id, $DB);
            $activityDetails = $serializer->details();
            if (!empty($activityDetails)) {
                $activityDetails['results'] = $serializer->results();
            }
        }
        if (!empty($activityDetails)) {
            $data = [
                'course'    =>  $courseDetails,
                'activity'  =>  $activityDetails
            ];
            if ($activity->mod === 'quiz') {
                $quizes[] = $data;
            } else if ($activity->mod === 'survey') {
                $surveys[] = $data;
            } else if ($activity->mod === 'assign') {
                $assignments[] = $data;
            } else if ($activity->mod === 'feedback') {
                $feedback[] = $data;
            }
        }
    }
}
echo "ASSIGNMENTS:\r\n";
print_r(json_encode($assignments, JSON_NUMERIC_CHECK));
echo "\r\nFEEDBACK:\r\n";
print_r(json_encode($feedback, JSON_NUMERIC_CHECK));
echo "\r\nQUIZES:\r\n";
print_r(json_encode($quizes, JSON_NUMERIC_CHECK));
echo "\r\nSURVEYS:\r\n";
print_r(json_encode($surveys, JSON_NUMERIC_CHECK));