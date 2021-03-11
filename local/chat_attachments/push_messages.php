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
 * Sends messages to Rocketchat
 */
require_once(dirname(dirname(dirname(__FILE__))) . DIRECTORY_SEPARATOR . 'config.php');
require_once($CFG->libdir . DIRECTORY_SEPARATOR . 'filelib.php');
require_once(dirname(__FILE__) .DIRECTORY_SEPARATOR . 'classes' . DIRECTORY_SEPARATOR . 'CurlUtility.php');

$url = get_config('local_chat_attachments', 'messaging_url');
$machineIdFile = DIRECTORY_SEPARATOR . 'etc' . DIRECTORY_SEPARATOR . 'machine-id';
$boxId = null;
if (file_exists($machineIdFile)) {
    $boxId = trim(file_get_contents($machineIdFile));
}
if ((!$boxId) || ($boxId === '')) {
    echo 'Unable to retrieve the Box ID<br>';
    exit;
}
echo 'Sending Requests to: ' . $url . '<br>';

if ($url === '') {
    echo 'No URL provided!<br>';
    exit;
}

$curl = new CurlUtility($url);

echo 'Sending request to ' . $url . 'messageStatus/' . $boxId . '<br>';
$lastSync = $curl->makeRequest('messageStatus/' . $boxId, 'GET');
echo 'Last Sync Time: ' . date('F j, Y H:i:s', $lastSync) . '(' . $lastSync . ')<br>';
/**
 * Create the course payload to send to the API
 */
$payload = [];
$courses = get_courses();
$studentRole = $DB->get_record('role', ['shortname' =>  'student']);
$teacherRole = $DB->get_record('role', ['shortname' =>  'teacher']);
$editingTeacherRole = $DB->get_record('role', ['shortname' =>  'editingteacher']);
foreach ($courses as $course) {
    $context = context_course::instance($course->id);
    $data = [
        'id'            =>  intval($course->id),
        'course_name'   =>  $course->fullname,
        'summary'       =>  $course->summary,
        'created_on'    =>  intval($course->timecreated),
        'updated_on'    =>  intval($course->timemodified),
        'students'      =>  [],
        'teachers'      =>  []
    ];
    $students = get_role_users($studentRole->id, $context);
    foreach ($students as $student) {
        $data['students'][] = [
            'id'            =>  intval($student->id),
            'username'      =>  $student->username,
            'first_name'    =>  $student->firstname,
            'last_name'     =>  $student->lastname,
            'email'         =>  $student->email,
            'last_accessed' =>  intval($student->lastaccess),
            'language'      =>  $student->lang
        ];
    }
    $teachers = get_role_users($teacherRole->id, $context);
    foreach ($teachers as $teacher) {
        $data['teachers'][] = [
            'id'            =>  intval($teacher->id),
            'username'      =>  $teacher->username,
            'first_name'    =>  $teacher->firstname,
            'last_name'     =>  $teacher->lastname,
            'email'         =>  $teacher->email,
            'last_accessed' =>  intval($teacher->lastaccess),
            'language'      =>  $teacher->lang
        ];
    }
    $editingTeachers = get_role_users($editingTeacherRole->id, $context);
    foreach ($editingTeachers as $teacher) {
        $data['teachers'][] = [
            'id'            =>  intval($teacher->id),
            'username'      =>  $teacher->username,
            'first_name'    =>  $teacher->firstname,
            'last_name'     =>  $teacher->lastname,
            'email'         =>  $teacher->email,
            'last_accessed' =>  intval($teacher->lastaccess),
            'language'      =>  $teacher->lang
        ];
    }
    $payload[] = $data;
}
echo 'Our Course Payload:<br><pre>';
echo json_encode($payload, JSON_PRETTY_PRINT);
echo '</pre><br>';
/**
 * Send the payload to the API
 */
echo 'Sending request to ' . $url . 'courseRosters/' . $boxId . '<br>';
$curl->makeRequest('courseRosters/' . $boxId, 'POST', [], json_encode($payload));
echo 'The response was ' . $curl->responseCode . '<br>';
