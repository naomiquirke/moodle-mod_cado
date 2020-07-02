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
 * CADO plugin upgrade code.
 *
 * @package    mod_cado
 * @copyright  2020 Naomi Quirke
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Code run to upgrade the cado database tables.
 *
 * @param int $oldversion
 * @return bool always true
 */
function xmldb_cado_upgrade($oldversion = 0) {

    global $DB;

    $dbman = $DB->get_manager(); // Loads ddl manager and xmldb classes.

    $result = true;

    if ($oldversion < 2020070205) {
        upgrade_mod_savepoint(true, 2020070205, 'cado');
    }

    return $result;
}