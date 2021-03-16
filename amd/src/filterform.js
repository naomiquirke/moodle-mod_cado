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
define([],
    function () {
        /**
         * @alias module:mod_cado/filterform
         */

        return {
            init: function (cadolist) {
                // cadolist has records from Cado: id and name; from Course: shortname, fullname, startdate.
                const cadoids = Object.entries(cadolist);
                const dateselects = document.querySelectorAll("select[id^='id_compare']");
                dateselects.forEach(function (dateselect) {
                    dateselect.addEventListener('change', updatechoices);
                });

                const coursename = document.getElementById('id_coursename');
                coursename.addEventListener('change', updatechoices);
                const cadochoice = document.getElementById('id_cadoid');
                updatechoices();

                // Changing the content of cadochoice selector.
                function updatechoices() {
                    // First remove existing.
                    while (cadochoice.firstChild) {
                        cadochoice.removeChild(cadochoice.firstChild);
                    }
                    cadochoice.insertAdjacentHTML('afterbegin', `<option value="0">---</option>`);
                    cadochoice.selectedIndex = 0;
                    let sdate = new Date(document.getElementById('id_comparestartdate_year').value,
                        document.getElementById('id_comparestartdate_month').value - 1,
                        document.getElementById('id_comparestartdate_day').value);
                    let startdate = Math.floor(sdate.getTime() / 1000);
                    let edate = new Date(document.getElementById('id_compareenddate_year').value,
                        document.getElementById('id_compareenddate_month').value,
                        document.getElementById('id_compareenddate_day').value);
                    let enddate = Math.floor(edate.getTime() / 1000);

                    cadoids.forEach(function (value) {
                        if ((value[1].startdate >= startdate) &&
                            (value[1].startdate <= enddate) &&
                            (value[1].shortname.indexOf(coursename.value) >= 0)) {
                            cadochoice.insertAdjacentHTML('afterbegin',
                                `<option value=${value[0]}>${value[1].shortname} --- ${value[1].name}</option>`);
                        }
                    });
                }

            }
        };
    }
);
