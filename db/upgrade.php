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
 * Version 1.1
 * Database upgrade stub
 *
 * @package   mod_cado
 * @copyright 2020 onwards  Naomi Quirke
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
 /*
function xmldb_cado_upgrade($oldversion) {
    global $DB;
    $dbman = $DB->get_manager();
    $result = TRUE;
 
    if ($oldversion < 2020022100) {

        // Define field id to be added to cado.
        $table = new xmldb_table('cado');
        $field = new xmldb_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null, null);

        // Conditionally launch add field id.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Cado savepoint reached.
        upgrade_mod_savepoint(true, 2020022100, 'cado');
    }

    
    return $result;
}
*/