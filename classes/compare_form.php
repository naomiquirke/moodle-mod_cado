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
 * Choose the course that is wanted to compare against origin
 *
 * @package   mod_cado
 * @copyright 2020 Naomi Quirke
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

/**
 * Class form to allow choice of CADO wanted to compare against origin.
 *
 * @package   mod_cado
 * @copyright 2020 Naomi Quirke
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_cado_compare_form extends moodleform {
    /** @var object instance of cado */
    protected $instance;
    /** @var string course name */
    protected $coursename;
    /** @var integer timestamp */
    protected $chosenstartdate;
    /** @var integer timestamp */
    protected $chosenenddate;

    /**
     * Get base info for get recipients for sending approval form
     *
     * @param moodle_url $actionurl
     * @param mod_cado_cado $origin
     * @param string $coursename
     * @param integer $chosenstartdate
     * @param integer $chosenenddate
     * @return void
     */
    public function __construct($actionurl, $origin, $coursename = '', $chosenstartdate = null, $chosenenddate = null) {
        $this->instance = $origin;
        $this->coursename = $coursename;
        $this->chosenstartdate = $chosenstartdate;
        $this->chosenenddate = $chosenenddate;
        parent::__construct($actionurl);
    }

    /**
     * Define the form for get recipients for sending approval form
     *
     * @return void
     */
    public function definition () {

        $mform = $this->_form;

        $mform->addElement('header', 'formheader', get_string('titleforlegend', 'cado'));
        $mform->setExpanded('formheader');
        // Set to false to make it closed on page load, default is false for optional params, true for required.
        $mform->addElement('html', '<div id="cado_nq" style="height:30pt"> </div>');

        $mform->addElement('date_selector', 'comparestartdate', get_string('datestartselector', 'cado'));
        $chosenstartdate = $this->chosenstartdate ? $this->chosenstartdate : date("U", strtotime('first day of january'));
        $mform->setDefault('comparestartdate', $chosenstartdate);

        $mform->addElement('date_selector', 'compareenddate', get_string('dateendselector', 'cado'));
        $chosenenddate = $this->chosenenddate ? $this->chosenenddate : date("U", strtotime('last day of december'));
        $mform->setDefault('compareenddate', $chosenenddate);

        $mform->addElement('text', 'coursename', get_string('nameinstruction', 'cado'));
        $mform->setType('coursename', PARAM_ALPHANUM);

        $courselist = $this->choose_course(['start' => $chosenstartdate, 'finish' => $chosenenddate], $this->coursename);

        $courseselector = $mform->addElement('select', 'cadoid', get_string('courseinstruction', 'cado'), $courselist);
        $courseselector ->setSelected("default");

        $this->add_action_buttons();
    }

    /**
     * Select a list of courses that matches the chosen requirements
     *
     * @param int $chosentime this is just year
     * @param string $chosennamepart default = ''
     */
    public function choose_course($chosentime, $chosennamepart='') {

        global $DB;
        $params["cadotimestart"] = intval($chosentime["start"]);
        $params["cadotimeend"] = intval($chosentime["finish"]);
        $params["part1"] = $params["part2"] = '%' . $chosennamepart . '%';
        $params["currentcado"] = $this->instance;
        $sql = "SELECT cado.id, c.shortname, c.fullname, c.startdate, cado.name
                FROM {cado} cado
                JOIN {course} c on c.id = cado.course
                WHERE cado.timegenerated > 0 AND cado.id <> :currentcado
                    AND c.startdate >= :cadotimestart AND  c.startdate <= :cadotimeend
                    AND (" . $DB->sql_like('c.shortname', ':part1') . " OR " . $DB->sql_like('c.fullname', ':part2') .
                ")";  // The last line performs a moodle multi database type like query.
        $courseresult = $DB->get_records_sql($sql, $params); //
        foreach ($courseresult as $thisresult) {
            $chosencourses[$thisresult ->id] = $thisresult ->shortname . ', ' . $thisresult ->name;
        }
        $chosencourses["default"] = get_string('coursenotchosen', 'cado'); // Add the not chosen option.
        return $chosencourses;
    }
}
