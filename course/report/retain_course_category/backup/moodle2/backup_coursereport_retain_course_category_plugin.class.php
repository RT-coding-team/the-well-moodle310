<?php
// This file is part of Moodle - http://moodle.org/
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

class backup_coursereport_retain_course_category_plugin  extends backup_coursereport_plugin {

    /**
     * Set up the structure for XML data
     */
    protected function define_course_plugin_structure() {
        $plugin = $this->get_plugin_element();
        $wrapper = new backup_nested_element($this->get_recommended_name());
        $plugin->add_child($wrapper);
        $course = new backup_nested_element('course_details', null, ['id']);
        $wrapper->add_child($course);
        $category = new backup_nested_element('course_category', null, ['path']);
        $course->set_source_table('course', array('id' => backup::VAR_COURSEID));
        $category->set_source_sql('SELECT c.category, cc.path
                                    FROM {course} c
                                    JOIN {course_categories} cc ON c.category = cc.id
                                    WHERE c.id = ?', array(backup::VAR_COURSEID));
        $course->add_child($category);
        $categories = new backup_nested_element('categories', null, ['id', 'name', 'description', 'descriptionformat', 'parent', 'path', 'depth']);
        $wrapper->add_child($categories);
        $categories->set_source_table('course_categories', array());
        return $plugin;
    }

}