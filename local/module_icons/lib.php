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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Plugin lib.
 */
defined('MOODLE_INTERNAL') || die();

require_once(dirname(dirname(dirname(__FILE__))) . DIRECTORY_SEPARATOR . 'config.php');

/**
 * Inject the custom fields elements into all moodle module settings forms.
 *
 * @param moodleform $formwrapper The moodle quickforms wrapper object.
 * @param MoodleQuickForm $mform The actual form object (required to modify the form).
 */
function local_module_icons_coursemodule_standard_elements($formwrapper, $mform) {
    $path = dirname(__FILE__) .DIRECTORY_SEPARATOR . 'pix';
    $files = array_diff(scandir($path), array('.', '..'));
    $icons = [];
    foreach ($files as $file) {
        $name = substr($file, 0, strrpos($file, '.'));
        $name = str_replace('_', ' ', $name);
        $icons[$file] = ucwords($name);
    }
    $mform->addElement('header', 'mod_handler_header', get_string('fieldheader', 'local_module_icons'));
    $mform->setExpanded('mod_handler_header', true);
    $mform->addElement('select', 'icon_selector', get_string('icon-selector-text', 'local_module_icons'), $icons);
}

/**
 * Saves the data of custom fields elements of all moodle module settings forms.
 *
 * @param object $moduleinfo the module info
 * @param object $course the course of the module
 */
function local_module_icons_coursemodule_edit_post_actions($moduleinfo, $course) {
    global $DB;
    $courseId = $moduleinfo->course;
    $moduleId = $moduleinfo->coursemodule;
    $icon = $moduleinfo->icon_selector;
    $data = (object) [
        'course_id'         =>  $courseId,
        'course_module_id'  =>  $moduleId,
        'icon'              =>  $icon
    ];
    $record = $DB->get_record(
        'local_module_icons',
        ['course_id' => $courseId, 'course_module_id' => $moduleId]
    );
    if ($record) {
        $data->id = $record->id;
        $DB->update_record('local_module_icons', $data);
    } else {
        $DB->insert_record('local_module_icons', $data, false);
    }
    return $moduleinfo;
}
