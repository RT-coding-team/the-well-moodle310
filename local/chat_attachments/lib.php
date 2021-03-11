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
 * Functions for the plugin
 */
function local_chat_attachments_pluginfile(
    $course,
    $cm,
    $context,
    $filearea,
    $args,
    $forcedownload,
    array $options=array()
) {
    /**
     * Is it the correct filearea we support?
     */
    if ($filearea !== 'chat_attachment') {
        return false;
    }
    $systemContext = context_system::instance();
    $itemId = array_shift($args);
    $fileName = array_pop($args);
    if (!$args) {
        $filepath = '/';
    } else {
        $filepath = '/' . implode('/', $args) . '/';
    }
    $fs = get_file_storage();
    $file = $fs->get_file(
        $systemContext->id,
        'local_chat_attachments',
        $filearea,
        $itemId,
        $filepath,
        $fileName
    );
    if (!$file) {
        /**
         * Try moving the file from Draft to Our Context and check again
         */
        file_save_draft_area_files(
            $itemId,
            $systemContext->id,
            'local_chat_attachments',
            $filearea,
            $itemId,
            []
        );

        $file = $fs->get_file(
            $systemContext->id,
            'local_chat_attachments',
            $filearea,
            $itemId,
            $filepath,
            $fileName
        );
        if (!$file) {
            return false;
        }
    }
    send_stored_file($file, 86400, 0, $forcedownload, $options);
}
