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
echo 'Sending Requests to: ' . $url . '<br>';

if ($url === '') {
    echo 'No URL provided!<br>';
    exit;
}

$curl = new CurlUtility($url);

echo 'Sending request to ' . $url . 'messageStatus/1<br>';
$lastSync = $curl->makeRequest('messageStatus/1', 'GET');
echo 'Last Sync Time: ' . date('F j, Y H:i:s', $lastSync) . '(' . $lastSync . ')<br>';
