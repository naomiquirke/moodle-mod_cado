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

/**
 * Version 1.0
 * Renderer for cado report.
 *
 * @package    mod_cado
 * @copyright  2020 Naomi Quirke
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
*/

 class mod_cado_renderer extends plugin_renderer_base {

/**
 * Applies cado.mustache.
 *
 * @param stdClass $data
 */
 public function render_course($data) {
        if ($data) {
            return $this->render_from_template('mod_cado/cado', $data);
        }
    }

    public function render_form_header() {
        echo $this->output->header();
    }
    public function render_form_footer() {
        echo $this->output->footer();
        }

    public function rendered_already($already) {
        echo $already;
    }
    public function render_compare($data) {
        echo $this->render_from_template('mod_cado/cadocompare', $data);
    }

/**
 * Applies cadostate.mustache.
 *
 * @param stdClass $data
 */
function render_state($data) {
        if ($data) {
            echo $this->output->render_from_template('mod_cado/cadostate', $data);
        }
    }

 }