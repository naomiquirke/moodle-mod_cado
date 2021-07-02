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
 * mod_cado data generator
 *
 * @package    mod_cado
 * @category   test
 * @copyright  2021 Naomi Quirke
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
global $CFG;
require_once($CFG->dirroot.'/mod/cado/lib.php');

/**
 * mod_cado data generator class
 *
 * @package    mod_cado
 * @category   test
 * @copyright  2021 Naomi Quirke
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_cado_generator extends testing_module_generator {
    /**
     * Create cado instance.
     *
     * @param object|array|null $record
     * @param array|null $options
     * @return stdClass
     * @throws coding_exception
     */
    public function create_instance($record = null, array $options = null) {

        $record = (object)(array)$record;

        $defaultsettings = array(
            'timemodified' => time(),
            'timegenerated' => 0,
            'generateuser' => 0,
            'cadointro' => ['text' => 'Intro', 'format' => FORMAT_MOODLE],
            'cadobiblio' => ['text' => 'Biblio', 'format' => FORMAT_MOODLE],
            'cadocomment' => ['text' => 'Comment', 'format' => FORMAT_MOODLE],
        );

        foreach ($defaultsettings as $name => $value) {
            if (!isset($record->{$name})) {
                $record->{$name} = $value;
            }
        }

        return parent::create_instance($record, (array)$options);
    }
}
