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
 * Steps definitions related to mod_cado.
 *
 * @package   mod_cado
 * @category  test
 * @copyright 2023 Naomi Quirke
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// NOTE: no MOODLE_INTERNAL test here, this file may be required by behat before including /config.php.

require_once(__DIR__ . '/../../../../lib/behat/behat_base.php');
require_once(__DIR__ . '/../../../../question/tests/behat/behat_question_base.php');

use Behat\Gherkin\Node\TableNode as TableNode;

use Behat\Mink\Exception\ExpectationException as ExpectationException;

/**
 * Steps definitions related to mod_cado.
 *
 * @copyright 2023 Naomi Quirke
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class behat_mod_cado extends behat_question_base {

    /**
     * Convert page names to URLs for steps like 'When I am on the "[page name]" page'.
     *
     * Recognised page names are:
     * | None so far!      |                                                              |
     *
     * @param string $page name of the page, with the component name removed e.g. 'Admin notification'.
     * @return moodle_url the corresponding URL.
     * @throws Exception with a meaningful error message if the specified page cannot be found.
     */
    protected function resolve_page_url(string $page): moodle_url {
        switch (strtolower($page)) {
            default:
                throw new Exception('Unrecognised cado page type "' . $page . '."');
        }
    }

    /**
     * Convert page names to URLs for steps like 'When I am on the "[identifier]" "[page type]" page'.
     *
     * Recognised page names are:
     * | pagetype          | name meaning                                | description                                   |
     * | View              | cado name                                   | The cado page (view.php)                      |
     * | Compare           | cado name                                   | The compare page for a cado  (compare.php)    |
     *
     * @param string $type identifies which type of page this is, e.g. 'Attempt review'.
     * @param string $identifier identifies the particular page, e.g. 'Test cado > student > Attempt 1'.
     * @return moodle_url the corresponding URL.
     * @throws Exception with a meaningful error message if the specified page cannot be found.
     */
    protected function resolve_page_instance_url(string $type, string $identifier): moodle_url {
        global $DB;

        switch (strtolower($type)) {
            case 'view':
                return new moodle_url('/mod/cado/view.php',
                        ['id' => $this->get_cm_by_cado_name($identifier)->id]);


            case 'question bank':
                return new moodle_url('/mod/cado/compare.php', [
                    'cmid' => $this->get_cm_by_cado_name($identifier)->id,
                ]);


            default:
                throw new Exception('Unrecognised cado page type "' . $type . '."');
        }
    }

    /**
     * Get a cado by name.
     *
     * @param string $name cado name.
     * @return stdClass the corresponding DB row.
     */
    protected function get_cado_by_name(string $name): stdClass {
        global $DB;
        return $DB->get_record('cado', array('name' => $name), '*', MUST_EXIST);
    }

    /**
     * Get a cado cmid from the cado name.
     *
     * @param string $name cado name.
     * @return stdClass cm from get_coursemodule_from_instance.
     */
    protected function get_cm_by_cado_name(string $name): stdClass {
        $cado = $this->get_cado_by_name($name);
        return get_coursemodule_from_instance('cado', $cado->id, $cado->course);
    }

}
