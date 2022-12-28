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
 * Creates list of CADOs.
 *
 * @package   mod_cado
 * @copyright 2021 Naomi Quirke
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Creates list of CADOs.
 *
 * @package   mod_cado
 * @copyright 2021 Naomi Quirke
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_cado_list {
    /** @var object array of cados indexed by cado id */
    public $courseresult;

    /**
     * Creates a list of CADOs with useful information about their related course.
     * @param int $current CADO not to be included in list
     */
    public function __construct($current = 0) {
        global $DB;
        $sql = "SELECT cado.id, c.shortname, c.fullname, c.startdate, cado.name
                FROM {cado} cado
                JOIN {course} c on c.id = cado.course
                WHERE cado.timegenerated > 0 AND cado.id <> :currentcado";

        $courseresult = $DB->get_records_sql($sql, ["currentcado" => $current]);
        $this->courseresult = $courseresult;
    }
    /**
     * Creates a list of CADOs, just their coursename and CADO name.
     */
    public function chosencourses() {
        $chosencourses["0"] = "---";
        foreach ($this->courseresult as $thisresult) {
            $chosencourses[$thisresult->id] = $thisresult->shortname . ' --- ' . $thisresult->name;
        }
        return $chosencourses;
    }

}
