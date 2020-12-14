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

namespace mod_cado\output;

defined('MOODLE_INTERNAL') || die();

use context_module;

/**
 * Mobile output class for cado
 *
 * @package    mod_cado
 * @copyright  2020 Naomi Quirke
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mobile {

    /**
     * Returns the cado course view for the mobile app.
     * @param  array $args Arguments from tool_mobile_get_content WS
     *
     * @return array       HTML, javascript and otherdata
     */
    public static function mobile_course_view($args) {
        global $OUTPUT, $USER, $DB;

        $args = (object) $args;
        $cm = get_coursemodule_from_id('cado', $args->cmid);

        // Capabilities check.
        require_login($args->courseid , false , $cm, true, true);

        $context = context_module::instance($cm->id);

        require_capability ('mod/cado:view', $context);
        $cadoinstance = $DB->get_record('cado', array('id' => $cm->instance));
        $showcentral = 1;
        $cs = new stdClass();
        $cs->statecomment = null;
        $cs->showtime = null;
        $cs->approvecomment = null;
        if (!$cadoinstance->timeapproved) {  // If not approved.
            $cs->statecomment = get_string('notavailable', 'cado');
        } else {
            if (has_capability('mod/cado:generate', $context) || has_capability('mod/cado:approve', $context)) {
                // Note can show workflow status.
                $cs->statecomment = get_string('approvedon', 'cado',
                    ['approver' => mod_cado_check::getusername($viewedcado->instance->approveuser)]);
                $cs->approvecomment = $viewedcado->instance->approvecomment;
                $cs->showtime = $viewedcado->instance->timeapproved;
                // Note add time separately so that it can be formatted by user specification.
            } else { // Note else only has view rights, no approval information.
                $cs = null;
            }

        }

        return array(
            'templates' => array(
                array(
                    'id' => 'main',
                    'html' => $OUTPUT->render_from_template('mod_cado/cadostate', $cs) . $cadoinstance->generatedpage,
                ),
            ),
            'javascript' => '',
            'otherdata' => '',
            'files' => ''
        );
    }
}
