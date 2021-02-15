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

/*
 * Provides interface for users to edit courseflow activity flow on the
 * courseflow mod editing form.
 *
 * @package    mod_courseflow
 * @copyright  2020 Naomi Quirke
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
*/

/**
 * @module mod_cado/filterform
 */
define(['jquery'],
    function ($) {
        /**
         * @alias module:mod_cado/filterform
         */

        return {
            init: function (cadolist) {
                // cadolist has records from Cado: id and name; from Course: shortname, fullname, startdate.
                const dateselects = document.querySelectorAll("select[id^='id_compare']");
                dateselects.forEach(function (dateselect) {
                    dateselect.addEventListener('change', updatechoices);
                });

                const coursename = $("#id_coursename");
                coursename.on('change', () => updatechoices());
                const cadochoice = $("#id_cadoid");
                updatechoices();

                // Changing the content of cadochoice selector.
                function updatechoices() {
                    cadochoice.empty();
                    cadochoice.append(`<option value="0">---</option>`);
                    cadochoice.prop('selectedIndex', 0);
                    let sdate = new Date($("#id_comparestartdate_year").val(),
                        $("#id_comparestartdate_month").val(),
                        $("#id_comparestartdate_day").val());
                    let startdate = Math.floor(sdate.getTime() / 1000);
                    let edate = new Date($("#id_compareenddate_year").val(),
                        $("#id_compareenddate_month").val(),
                        $("#id_compareenddate_day").val());
                    let enddate = Math.floor(edate.getTime() / 1000);
                    $.each(cadolist, function (index, value) {
                        if ((value.startdate >= startdate) &&
                            (value.startdate <= enddate) &&
                            (value.shortname.indexOf(coursename.val()) >= 0)) {
                            cadochoice.append(`<option value=${index}>${value.shortname} --- ${value.name}</option>`);
                        }
                    });
                }

            }
        };
    }
);
