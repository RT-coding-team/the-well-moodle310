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
$password = '';
if ((isset($argv)) && (isset($argv[1]))) {
    $cliScript = boolval($argv[1]);
}
if ((isset($argv)) && (isset($argv[2]))) {
    $password = $argv[2];
} else {
    echo "You must provide a valid password!\r\n";
    exit;
}

define('CLI_SCRIPT', $cliScript);

set_time_limit(0);

require_once(dirname(dirname(dirname(__FILE__))) . DIRECTORY_SEPARATOR . 'config.php');
require_once(dirname(dirname(dirname(__FILE__))) . DIRECTORY_SEPARATOR . 'course' . DIRECTORY_SEPARATOR . 'lib.php');

/**
 * Login the account and download the given file
 * 
 * @param string $siteUrl   The URL for the website
 * @param string $username  The username to log into
 * @param string $password  The password of the given user
 * @param string $remoteFile    The remote file to download
 * @param string $localFile The local file where to store the contents
 * 
 * @return void
 */
function cts_get_file($siteUrl, $username, $password, $remoteFile, $localFile) {
    $cookieFile = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'cookies.txt';
    $tokenPattern = '/<input(?:.*?)name=\"logintoken\"(?:.*)value=\"([^"]+).*>/i';
    /**
     * Setup cURL. Since we need to get the logintoken, then login the user, and then get the file,
     * we need to use the same initialized cURL instance.  It will fail otherwise.
     */
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_COOKIEJAR, $cookieFile);
    curl_setopt($curl, CURLOPT_COOKIEFILE, $cookieFile);
    curl_setopt($curl, CURLOPT_COOKIESESSION, true);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 60);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($curl, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.13) Gecko/20080311 Firefox/2.0.0.13');
    curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($curl, CURLOPT_AUTOREFERER, true);
    curl_setopt($curl, CURLOPT_TIMEOUT, 60);
    /**
     *  First retrieve the logintoken
     */
    curl_setopt($curl, CURLOPT_URL, $siteUrl . '/login/index.php');
    curl_setopt($curl, CURLOPT_POST, false);
    $content = curl_exec($curl);
    preg_match($tokenPattern, $content, $matches);
    if (count($matches) <= 1) {
        echo "Unable to get the login token. \r\n";
        exit;
    }
    $payload = [
        'username'      =>  $username,
        'password'      =>  $password,
        'logintoken'    =>  $matches[1]
    ];
    curl_setopt($curl, CURLOPT_URL, $siteUrl . '/login/index.php');
    curl_setopt($curl, CURLOPT_POST, true);
    //@link https://stackoverflow.com/a/15023426
    curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($payload, '', '&'));
    curl_exec($curl);

    // get the file
    curl_setopt($curl, CURLOPT_URL, $remoteFile);
    curl_setopt($curl, CURLOPT_POST, false);
    $contents = curl_exec($curl);
    file_put_contents($localFile, $contents);
    curl_close($curl);
}
cts_get_file(
    $CFG->wwwroot,
    'admin',
    $password,
    $CFG->wwwroot . '/mod/quiz/report.php?download=csv&id=543&mode=responses&attempts=enrolled_with&onlygraded=&qtext=1&resp=1&right=1',
    dirname(__FILE__) . DIRECTORY_SEPARATOR . 'response-543.csv'
);
// $courses = get_courses();
// $approvedActivities = ['choice', 'quiz', 'feedback', 'survey'];

// foreach ($courses as $course) {
//     if (intval($course->id) === 1) {
//         continue;
//     }
//     $activities = get_array_of_activities($course->id);
//     foreach ($activities as $activity) {
//         if (!in_array($activity->mod, $approvedActivities)) {
//             continue;
//         }
//         if ($activity->mod === 'quiz') {

//         }
//     }
// }
