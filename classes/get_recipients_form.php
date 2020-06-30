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
 * Get recipients and create CADO approval form
 *
 * @package   mod_cado
 * @copyright 2020 Naomi Quirke
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
require_once($CFG->libdir . '/formslib.php');

/**
 * Class for form to get recipients for sending request for approval
 *
 * @package   mod_cado
 * @copyright 2020 Naomi Quirke
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_cado_get_recipients_form extends moodleform {
    /* from https://docs.moodle.org/dev/lib/formslib.php_Form_Definition#select */

    /**
     * Define the get recipients for sending approval form
     *
     * @return void
     */
    public function definition () {

        $mform = $this->_form;

        if ($this->_customdata['purpose'] = 'propose') {
            $querystring = get_string('chooseapprover', 'cado');
            $possiblelist = $this->getapproveusers($this->_customdata['context']);
            $multiplerecipient = false;
        } else {
            debugging('This is not implemented');
        }

        $possibleselector = $mform->addElement('select', 'possiblelist', $querystring, $possiblelist);
        $possibleselector->setMultiple($multiplerecipient);

        $this->add_action_buttons();

    }
    /**
     * Create a list of people that can approve a generated CADO
     *
     * @param stdClass $context context object
     */
    private function getapproveusers($context) {
        $approveusers = get_users_by_capability($context, "mod/cado:approve", "u.id, u.username", "u.firstname");
        $approvelist = [];
        foreach ($approveusers as $thisuser) {
            $approvelist = $approvelist + [$thisuser->id => mod_cado_check::getusername($thisuser->id) . " ($thisuser->username)"];
        }
        return $approvelist;

    }

}
