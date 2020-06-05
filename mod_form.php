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
 * CADO mod settings page
 *
 * @package   mod_cado
 * @copyright 2020 Naomi Quirke
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;
require_once($CFG->dirroot.'/course/moodleform_mod.php');
require_once($CFG->dirroot.'/mod/cado/lib.php');

class mod_cado_mod_form extends moodleform_mod {
 
    function definition() {
        global $CFG, $OUTPUT;
 
        $mform =& $this->_form;
 
        $mform->addElement('text', 'name', get_string('cadoname', 'cado'), array('size'=>'64'));
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required', null, 'client');

        $mform->addElement('editor', 'cadointro', get_string('cadointro', 'cado'));
        $mform->setType('cadointro', PARAM_RAW);

        
        if ($this->comment = mod_cado_check::options('cadocomment','cadooptions')) {
            $mform->addElement('editor', 'cadocomment', get_string('cadocomment', 'cado'));
            $mform->setType('cadocomment', PARAM_RAW);
        }
        
        if ($this->biblio = mod_cado_check::options('cadobiblio','cadooptions')) {
            $mform->addElement('editor', 'cadobiblio', get_string('cadobiblio', 'cado'));
            $mform->setType('cadobiblio', PARAM_RAW);
        }
        
        $mform->addElement('hidden', 'isapproved', 0);
        $mform->setType('isapproved', PARAM_INT);

        //$this->standard_hidden_coursemodule_elements();
        $this->standard_coursemodule_elements();
        $this->add_action_buttons();
    }

    public function definition_after_data() {
        global $DB;

        parent::definition_after_data();
        $mform = $this->_form;

        //never allow editing of groupmode, because we want to allow editing of grouping
        $mform->getElement('groupmode')->_values[0] = "1";
        $mform->freeze('groupmode');

        $id = $mform->_defaultValues['instance'];
        
        if ($id) { //then it is not new
            $instance =  mod_cado_cado::getcadorecord($id);
            $mform->setDefault('isapproved', $instance->timeapproved ? 1 : 0);
            if ($this->comment) {$mform->setDefault('cadocomment', array('text'=>$instance->cadocomment));}
            if ($this->biblio) { $mform->setDefault('cadobiblio', array('text'=>$instance->cadobiblio));}
            $mform->setDefault('cadointro', array('text'=>$instance->cadointro));

            if ($instance->timeapproved) { //check for approved, if approved don't allow editing of these
                $mform->freeze('groupingid');
                $mform->freeze('cadointro');

                if ($this->comment) { $mform->freeze('cadocomment'); }
                if ($this->biblio) { $mform->freeze('cadobiblio'); }
            }
        }
    }
}
