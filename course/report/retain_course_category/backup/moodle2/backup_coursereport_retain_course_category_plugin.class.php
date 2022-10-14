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
     * Set up the structure for XML data. Example of the result stored in course/course.xml:
     * 
     * <plugin_coursereport_retain_course_category_course>
     *     <course_details>
     *       <id>10</id>
     *       <course_category>
     *         <path>/2/3</path>
     *       </course_category>
     *     </course_details>
     *     <categories>
     *       <single_category>
     *         <id>2</id>
     *         <name>Plants</name>
     *         <description>&lt;p dir="ltr" style="text-align: left;"&gt;Plant Anatomy&lt;br&gt;&lt;/p&gt;</description>
     *         <descriptionformat>1</descriptionformat>
     *         <parent>0</parent>
     *         <path>/2</path>
     *         <depth>1</depth>
     *       </single_category>
     *       <single_category>
     *         <id>1</id>
     *         <name>Miscellaneous</name>
     *         <description>$@NULL@$</description>
     *         <descriptionformat>0</descriptionformat>
     *         <parent>0</parent>
     *         <path>/1</path>
     *         <depth>1</depth>
     *       </single_category>
     *       <single_category>
     *         <id>3</id>
     *         <name>Vines</name>
     *         <description>&lt;p dir="ltr" style="text-align: left;"&gt;Vineology&lt;br&gt;&lt;/p&gt;</description>
     *         <descriptionformat>1</descriptionformat>
     *         <parent>2</parent>
     *         <path>/2/3</path>
     *         <depth>2</depth>
     *       </single_category>
     *     </categories>
     *   </plugin_coursereport_retain_course_category_course>
     */
    protected function define_course_plugin_structure() {
        $plugin = $this->get_plugin_element();
        $wrapper = new backup_nested_element($this->get_recommended_name());
        $plugin->add_child($wrapper);
        $course = new backup_nested_element('course_details', null, ['id']);
        $wrapper->add_child($course);
        $categoryPath = new backup_nested_element('course_category', null, ['path']);
        $course->set_source_table('course', array('id' => backup::VAR_COURSEID));
        $categoryPath->set_source_sql('SELECT c.category, cc.path
                                    FROM {course} c
                                    JOIN {course_categories} cc ON c.category = cc.id
                                    WHERE c.id = ?', array(backup::VAR_COURSEID));
        $course->add_child($categoryPath);
        $categories = new backup_nested_element('categories');
        $children = new backup_nested_element('single_category', null, ['id', 'name', 'description', 'descriptionformat', 'parent', 'path', 'depth']);
        $wrapper->add_child($categories);
        $categories->add_child($children);
        $children->set_source_table('course_categories', array());
        return $plugin;
    }

}