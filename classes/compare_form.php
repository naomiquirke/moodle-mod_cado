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

    /**
     * Define the form for get recipients for sending approval form
     *
     * @return void
     */
    public function definition () {
        global $PAGE;

        $mform = $this->_form;

        $mform->addElement('header', 'formheader', get_string('titleforlegend', 'cado'));
        $mform->setExpanded('formheader');
        // Set to false to make it closed on page load, default is false for optional params, true for required.

        $mform->addElement('date_selector', 'comparestartdate', get_string('datestartselector', 'cado'));
        $chosenstartdate = date("U", strtotime('first day of january'));
        $mform->setDefault('comparestartdate', $chosenstartdate);

        $mform->addElement('date_selector', 'compareenddate', get_string('dateendselector', 'cado'));
        $chosenenddate = date("U", strtotime('last day of december'));
        $mform->setDefault('compareenddate', $chosenenddate);

        $mform->addElement('text', 'coursename', get_string('nameinstruction', 'cado'));
        $mform->setType('coursename', PARAM_ALPHANUM);

        $mform->addElement('select', 'cadoid', get_string('courseinstruction', 'cado'), $this->_customdata['chosencourses']);
        $PAGE->requires->js_call_amd('mod_cado/filterform', 'init', ["#id_cadolist"]);

        $this->add_action_buttons();
    }

}
