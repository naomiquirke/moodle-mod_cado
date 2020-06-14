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
* Version 1.0
 * Choose the course that is wanted to compare against origin
 *
 * @package   mod_cado
 * @copyright 2020 Naomi Quirke
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

class mod_cado_compare_form extends moodleform {
    protected $instance; //cado id

    function __construct($actionurl, $origin) {
        $this->instance = $origin;
        parent::__construct($actionurl);
    }

    public function definition () {
        
        $mform = $this->_form;
        
        $chosenyear = $this->_customdata['yearstart'];
        $chosenyear = (isset($chosenyear) ? $chosenyear : date("Y"));
        $coursename = $this->_customdata['coursepart'];
        $coursename = (isset($coursename) ? $coursename : '');

        $mform->addElement('header', 'formheader', get_string('titleforlegend', 'cado'));
        $mform->setExpanded('formheader'); //set to false to make it closed on page load, default is false for optional params, true for required
        $mform->addElement('html', '<div id="cado_nq" style="height:30pt"> </div>');
     
        $dateoptions = $this->get_cado_years(date("Y")-5, date("Y")+1);
        $yearselector = $mform->addElement('select', 'courseyearstart', get_string('dateinstruction', 'cado'), $dateoptions);     
        $yearselector -> setSelected((isset($chosenyear) ? $chosenyear : date("Y")));

        $mform->addElement('text', 'coursename', get_string('nameinstruction', 'cado'));
        $mform->setType('coursename', PARAM_ALPHANUM);

        $courselist = $this->choose_course($this->cado_getrange($chosenyear),$coursename);

        $courseselector = $mform->addElement('select', 'cadoid', get_string('courseinstruction', 'cado'), $courselist);     
        $courseselector -> setSelected("default");
 
        $this->add_action_buttons();

     
    }

/*    function definition_after_data(){

        parent::definition_after_data();
        $mform = $this->_form;

    }
*/

    /**
     * Returns an array of years.
     *
     * @param int $minyear
     * @param int $maxyear
     * @return array the years.
     */
    private function get_cado_years($minyear = null, $maxyear = null) {
        if (is_null($minyear)) {
            $minyear =1990;
        }
  
        if (is_null($maxyear)) {
            $maxyear = 2100;
        }
  
        $years = array();
        for ($i = $minyear; $i <= $maxyear; $i++) {
            $years[$i] = $i;
        }
  
        return $years;
    }
  
/**
 * Returns an array of starttime and endtime in timestamp format.
 *
 * @param int $cadoyear
 * @param int $cadosem ; assume that this will be either 1, 2, or 3
 * NQ at some point will have to put semester month start in settings
 */
private function cado_getrange($cadoyear = null, $cadosem = 0) {

    //NQ at some point following will have to put semester month start in settings
    //This gives the earliest possible start month for the semester as value, semester given as key
    $semstartmonth[1]=2;
    $semstartmonth[2]=6;
    $semstartmonth[3]=11;
    $semstartmonth[4]=12;

    $theyearbegin = (isset($cadoyear) ? $cadoyear : date("Y"));
    $thesembegin = ($cadosem<>0 ? $semstartmonth[$cadosem] : 1);
    $thesembeginuntil = ($cadosem<>0 ? $semstartmonth[$cadosem+1] : 12);

    $timeresult["start"] = strtotime('01-' . $thesembegin . '-' . $theyearbegin);
    $timeresult["finish"] = strtotime('28-' . $thesembeginuntil . '-' . $theyearbegin);
    return $timeresult;

}

/**
 * Select a list of courses that matches the chosen requirements
 * 
 * @param int $chosentime this is just year
 * @param string $chosennamepart default = ''
 */
function choose_course($chosentime, $chosennamepart='') {

        global $DB;
        $params["cadotimestart"] = intval($chosentime["start"]);
        $params["cadotimeend"] = intval($chosentime["finish"]);
        $params["part1"] = $params["part2"] = '%' . $chosennamepart . '%';
        $params["currentcado"] = $this->instance;
        $sql = "SELECT cado.id, c.shortname, c.fullname, c.startdate, cado.name
                FROM {cado} as cado
                JOIN {course} AS c on c.id = cado.course
                WHERE cado.timegenerated > 0 AND cado.id <> :currentcado
                    AND c.startdate >= :cadotimestart AND  c.startdate <= :cadotimeend
                    AND (" . $DB->sql_like('c.shortname', ':part1') . " OR " . $DB->sql_like('c.fullname', ':part2') . 
                ")";  //the last line performs a moodle multi database type like query
        $courseresult = $DB->get_records_sql($sql, $params); //
        foreach ($courseresult as $thisresult) {
            $chosencourses[$thisresult -> id] = $thisresult -> shortname . ', ' . $thisresult -> name;
        }
        $chosencourses["default"] = get_string('coursenotchosen', 'cado'); //add the not chosen option
        return $chosencourses;
    
    }
}