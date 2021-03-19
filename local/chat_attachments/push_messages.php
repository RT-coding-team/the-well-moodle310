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
 * @TODO Limit messages based on the provided Timestamp
 * @TODO Uncomment the push of attachments to the API
 * @TODO Send the request to update the time synced
 */
require_once(dirname(dirname(dirname(__FILE__))) . DIRECTORY_SEPARATOR . 'config.php');
require_once($CFG->libdir . DIRECTORY_SEPARATOR . 'filelib.php');
require_once(dirname(__FILE__) .DIRECTORY_SEPARATOR . 'classes' . DIRECTORY_SEPARATOR . 'CurlUtility.php');
require_once(dirname(__FILE__) .DIRECTORY_SEPARATOR . 'classes' . DIRECTORY_SEPARATOR . 'Attachment.php');

$url = get_config('local_chat_attachments', 'messaging_url');
$token = get_config('local_chat_attachments', 'messaging_token');
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

$curl = new CurlUtility($url, $token, $boxId);

/**
 * Retrieve the last time we synced
 */
echo 'Sending GET request to ' . $url . 'messageStatus<br>';
$lastSync = $curl->makeRequest('messageStatus', 'GET', []);
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
 * Send the course payload to the API
 */
echo 'Sending POST request to ' . $url . 'courseRosters<br>';
$curl->makeRequest('courseRosters', 'POST', json_encode($payload), null, true);
echo 'The response was ' . $curl->responseCode . '<br>';

/**
 * Gather up the messages to send to the API
 */
$payload = [];
$attachments = [];
$query = 'SELECT m.id, m.conversationid, m.subject, m.fullmessagehtml, m.timecreated, s.id as sender_id, ' .
        's.username as sender_username, s.email as sender_email, r.id as recipient_id, r.username as recipient_username, ' .
        'r.email as recipient_email FROM {messages} AS m INNER JOIN {message_conversation_members} AS mcm ON m.conversationid=mcm.conversationid ' .
        'INNER JOIN {user} AS s ON mcm.userid = s.id INNER JOIN {user} AS r ON m.useridfrom = r.id ' .
        'WHERE m.useridfrom <> mcm.userid ORDER BY m.timecreated ASC';
$chats = $DB->get_records_sql($query);
foreach ($chats as $chat) {
    $message = htmlspecialchars_decode($chat->fullmessagehtml);
    $attachment = null;
    if (Attachment::isAttachment($message)) {
        $attachment = new Attachment($message);
        $attachments[] = $attachment;
    }
    $data = [
        'id'                =>  intval($chat->id),
        'conversation_id'   =>  intval($chat->conversationid),
        'subject'           =>  $chat->subject,
        'message'           =>  $message,
        'sender'            =>  [
            'id'        =>  intval($chat->sender_id),
            'username'  =>  $chat->sender_username,
            'email'     =>  $chat->sender_email
        ],
        'recipient'            =>  [
            'id'        =>  intval($chat->recipient_id),
            'username'  =>  $chat->recipient_username,
            'email'     =>  $chat->recipient_email
        ],
        'attachment'    =>  null,
        'created_on'    =>  intval($chat->timecreated)
    ];
    if ($attachment) {
        $data['attachment'] = $attachment->toArray();
    }
    $payload[] = $data;
}
echo 'Our Chat Payload:<br><pre>';
echo json_encode($payload, JSON_PRETTY_PRINT);
echo '</pre><br>';

/**
 * Send the message payload to the API
 */
echo 'Sending POST request to ' . $url . 'messages/<br>';
$curl->makeRequest('messages', 'POST', json_encode($payload), null, true);
echo 'The response was ' . $curl->responseCode . '<br>';

/**
 * Send each attachment to the API
 *
 */
echo 'Total Attachments to send: ' . count($attachments) . '<br>';
$fs = get_file_storage();
$systemContext = context_system::instance();
echo 'Sending attachments<br>';
foreach ($attachments as $attachment) {
    $filepath = $attachment->getFilePath($fs, $systemContext->id, 'chat_attachment');
    if ((!$filepath) || (!file_exists($filepath))) {
        continue;
    }
    //Uncomment when the API is working
    $response = $curl->makeRequest('attachments', 'POST', $attachment->toArray(), $filepath);
    echo 'File: ' . basename($filepath) . ' status: ' . $curl->responseCode . '<br>';
    echo 'Send Attachment: ' . basename($filepath) . '<br>';
}

/**
 * Now request new messages from the API
 */
echo 'Retrieving new messages<br>';
echo 'Sending GET request to ' . $url . 'messages/' . $lastSync . '<br>';
$response = $curl->makeRequest('messages/' . $lastSync, 'GET', [], null, true);
echo 'The Received Response:<br><pre>';
echo json_encode(json_decode($response), JSON_PRETTY_PRINT);
echo '</pre><br>';
$newMessages = json_decode($response);
if (count($newMessages) == 0) {
    echo 'There are no new messages.<br>';
    exit();
}

/**
 * For each message, retrieve the attachment, save it to moodle, and save the new message.
 */
foreach ($newMessages as $message) {
    $content = $message->message;
    $html = htmlspecialchars_decode($message->message);
    if (Attachment::isAttachment($content)) {
        $attachment = new Attachment($content);
        /**
         * Download and save the attachment
         */
        if ($attachment->id <= 0) {
            // cannot get the attachment.  Move along.
            continue;
        }
        echo 'Saving New Attachment: ' . basename($attachment->filename) . '<br>';
        $tempPath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . $attachment->filename;
        $downloaded = $curl->downloadFile('attachments/' . $attachment->id, $tempPath);
        if (!$downloaded) {
            echo '<p>&#10060; Unable to download the file: ' . $attachment->filename . '</p>';
            continue;
        }
        $attachment->store($fs, $systemContext->id, 'chat_attachment', $tempPath);
        $content = $attachment->toString();
    }
    // Location in messages/classes/api.php
    \core_message\api::send_message_to_conversation($message->sender->id, $message->conversation_id, htmlspecialchars($content), FORMAT_HTML);
}
/**
 * Script finished
 */
echo '<p>&#129299;&#128077; Script Complete!</p>';
