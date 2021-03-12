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
 * Settings for this plugin.
 */

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    /**
     * Add a local settings page
     */
    $ADMIN->add(
        'localplugins',
        new admin_category(
            'local_chat_attachments_settings',
            new lang_string('pluginname', 'local_chat_attachments')
        )
    );
    $page = new admin_settingpage(
        'managelocalchatattachments',
        new lang_string('manage', 'local_chat_attachments')
    );
    if ($ADMIN->fulltree) {
        $page->add(
            new admin_setting_configtext(
                'local_chat_attachments/messaging_url',
                new lang_string('messaging_url', 'local_chat_attachments'),
                new lang_string('messaging_url_desc', 'local_chat_attachments'),
                ''
            )
        );
        $page->add(
            new admin_setting_configtext(
                'local_chat_attachments/messaging_token',
                new lang_string('messaging_token', 'local_chat_attachments'),
                new lang_string('messaging_token_desc', 'local_chat_attachments'),
                ''
            )
        );
    }

    $ADMIN->add('localplugins', $page);
}
