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
     * Returns the cado view for the mobile app, currently no different than browser view.
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
        $args->cadoid = $cadoinstance->id;
        if (!$cadoinstance->timeapproved) {  // If not approved.
            $args->approved = false;
            $args->data = get_string('notavailable', 'cado');
        } else {
            $args->approved = true;
/*            $mobcado = str_replace('>&#9741;', ' core-link capture="true" >&#9741;', $cadoinstance->generatedpage);
            $args->data = $mobcado;*/
            $args->data = (object) json_decode($cadoinstance->generatedjson, true);
            $args->data->sitecomment = trim($siteoptions->sitecomment);
            $args->data->cadocomment = in_array( 'cadocomment' , $siteoptionset ) ? $cadoinstance->cadocomment : null;
            $args->data->cadobiblio = in_array( 'cadobiblio' , $siteoptionset ) ? $cadoinstance->cadobiblio : null;
            $args->data->cadointro = $cadoinstance->cadointro;
            $args->data->mobileapp = 1;
        }
        $myjavascript = "
            console.log('Helooo! DOM is available now!');
        ";
/*
            var cadocollapse = document.querySelectorAll('.cadohide');
            for (let i = 0; i < cadocollapse.length; ++i) {
                cadocollapse[i].id = 'cado-collapse-' + i;
                cadocollapse[i].style.display = 'none';
                cadocollapse[i].parentElement.cadochild = i;
                cadocollapse[i].parentElement.addEventListener('click', function () {
                    el = document.querySelector(`#cado-collapse-${this.cadochild}`);
                    togglehide(el);
                    if (el.style.display === 'block') {
                    }
                });
            }
            function togglehide(targeted) {
                if (targeted.style.display === 'none') {
                    for (let i = 0; i < cadocollapse.length; ++i) {
                        cadocollapse[i].style.display = 'none';
                    }
                    targeted.style.display = 'block';
                    targeted.addEventListener('click', function () {
                        togglehide(this);
                    });

                } else {
                    targeted.style.display = 'none';
                }
            }
*/
        return array(
            'templates' => array(
                array(
                    'id' => 'main',
                    'html' => $OUTPUT->render_from_template('mod_cado/mobile_cadoview', $args),
                ),
            ),
            'javascript' => "setTimeout(function() { $myjavascript });",
            'otherdata' => '',
            'files' => ''
        );
    }
}
