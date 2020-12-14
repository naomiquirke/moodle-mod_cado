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

/**
 * Mobile cado view
 *
 * @package    mod_cado
 * @copyright  2020 Naomi Quirke
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$addons = array(
    "mod_cado" => array(
        "handlers" => array( // Different places where the add-on will display content.
            'cado' => array( // Handler unique name (can be anything).
                'displaydata' => array(
                    'title' => 'pluginname',
                    'icon' => $CFG->wwwroot . '/mod/cado/pix/icon.svg',
                    'class' => '',
                ),
                'delegate' => 'CoreCourseModuleDelegate', // Delegate (where to display the link to the add-on).
                'method' => 'mobile_course_view', // Main function in \mod_cado\output\mobile.
                'offlinefunctions' => array(
                    'mobile_course_view' => array(),
                ), // Function needs caching for offline.
            )
        ),
        'lang' => array(
            array('pluginname', 'cado'),
            array('notavailable', 'cado'),
            array('approvedon', 'cado'),
        )
    )
);
