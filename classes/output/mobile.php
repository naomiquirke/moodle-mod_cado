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
     * Returns the cado view for the mobile app.
     * @param  array $args Arguments from tool_mobile_get_content WS
     *
     * @return array       HTML, javascript and otherdata
     */
    public static function mobile_course_view($args) {
        global $OUTPUT, $DB;

        $args = (object) $args;
        $cm = get_coursemodule_from_id('cado', $args->cmid);

        // Capabilities check.
        require_login($args->courseid , false , $cm, true, true);

        $context = context_module::instance($cm->id);

        require_capability ('mod/cado:view', $context);
        $cadoinstance = $DB->get_record('cado', array('id' => $cm->instance));
        $args->cadoid = $cadoinstance->id;
        if (!$cadoinstance->timeapproved) {  // If not approved.
            $args->approved = false;
            $args->data = get_string('notavailable', 'cado');
        } else {
            $args->approved = true;
            $args->data = (object) json_decode($cadoinstance->generatedjson, true);
            $args->data->cadointro = format_text($cadoinstance->cadointro, $cadoinstance->cadointroformat);
            // Include these based on site settings at time of generation.
            $args->data->cadocomment = $args->data->commentexists ?
                format_text($cadoinstance->cadocomment, $cadoinstance->cadocommentformat) : null;
            $args->data->cadobiblio = $args->data->biblioexists ?
                format_text($cadoinstance->cadobiblio, $cadoinstance->cadobiblioformat) : null;

            $args->data->mobileapp = 1;
        }

        return array(
            'templates' => array(
                array(
                    'id' => 'main',
                    'html' => $OUTPUT->render_from_template('mod_cado/mobile_cadoview', $args),
                ),
            ),
            'javascript' => '',
            'otherdata' => '',
            'files' => ''
        );
    }
}
