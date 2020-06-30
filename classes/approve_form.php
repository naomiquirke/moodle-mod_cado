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

defined('MOODLE_INTERNAL') || die();
require_once($CFG->libdir . '/formslib.php');

/**
 * CADO approval form
 *
 * @package   mod_cado
 * @copyright 2020 Naomi Quirke
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

class mod_cado_approve_form extends moodleform {
    /* from https://docs.moodle.org/dev/lib/formslib.php_Form_Definition#select */

    public function definition () {
        $mform = $this->_form;

        $mform->addElement('header', 'formheader', get_string('titleforlegend', 'cado'));
        $mform->setExpanded('formheader');
        // Set to false to make it closed on page load, default is false for optional params, true for required.

        $mform->addElement('editor', 'comment', get_string('approvecomment', 'cado'));
        $mform->setType('comment', PARAM_RAW);

        $mform->addElement('advcheckbox', 'approved', get_string('approveagree', 'cado'));

        $this->add_action_buttons(true);

    }

    /**
     * Fill in previous approval information.
     */
    public function set_last_data($thiscado) {
        $data = $thiscado->instance;
        $checkboxval = $data->timeapproved == 0 ? 0 : 1;
        $this->_form->setDefault('comment', ['text' => '<p>' . get_string('update', 'cado') . '</p><div class="prevapprovecomment">'
            . $data->approvecomment . '</div>']); // Add update line at beginning to make it easy to add stuff.
        $this->_form->setDefault('approved', $checkboxval);
    }
}
