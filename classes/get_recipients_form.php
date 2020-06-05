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
 * Get recipients for CADO notification form
 *
 * @package   mod_cado
 * @copyright 2020 Naomi Quirke
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.'); // It must be included from a Moodle page.
}


require_once($CFG->libdir . '/formslib.php');

class mod_cado_get_recipients_form extends moodleform {
    /* from https://docs.moodle.org/dev/lib/formslib.php_Form_Definition#select */


    public function definition () {
        
        $mform = $this->_form;
        
        if ($this->_customdata['purpose'] = 'propose') {
            $querystring = get_string('chooseapprover', 'cado');
            $possiblelist = $this->getapproveusers($this->_customdata['context']);
            $multiplerecipient = FALSE;
        } else {}

        $possibleselector = $mform->addElement('select', 'possiblelist', $querystring, $possiblelist);  
        $possibleselector->setMultiple($multiplerecipient);

        $this->add_action_buttons();

    }
/**
 * Create a list of people that can approve a generated CADO
 * 
 * @param  $context
 */
    function getapproveusers($context) {
        $approveusers = get_users_by_capability($context, "mod/cado:approve", "u.id,u.firstname,u.lastname,u.username","u.firstname");
        $approvelist = [];
        foreach ($approveusers as $thisuser) {
            $approvelist = $approvelist + [$thisuser->id => "$thisuser->firstname $thisuser->lastname ($thisuser->username)"];
        }
        return $approvelist;

    }

}