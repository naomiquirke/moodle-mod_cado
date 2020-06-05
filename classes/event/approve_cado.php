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

namespace mod_cado\event;

defined('MOODLE_INTERNAL') || die();

/**
 * The mod_cado approval event class.
 *
 * @property-read array $other {
 *      Extra information about the event.
 *      - int groupmode: The grouping id the cado has been made for, or 0 for none.
 *      - int courseid: The course id the cado belongs to.
 * }
 *
 * @package    mod_cado
 * @copyright  2020 Naomi Quirke
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class approve_cado extends \core\event\base {
    /**
     * Init method.
     *
     * @return void
     */
    protected function init() {
        $this->data['crud'] = 'u';
        $this->data['edulevel'] = self::LEVEL_OTHER;
        $this->data['objecttable'] = 'cado';
    }

    /**
     * Returns description of what happened.
     *
     * @return string
     */
    public function get_description() {
        $extra = $this->other['groupmode'] ? " for grouping {$this->other['groupmode']}" : '';
        return "The user with id '$this->userid' has approved the cado with id '$this->objectid' 
            and course module id '$this->contextinstanceid' in course id '{$this->other['courseid']}'{$extra}.";
    }

    /**
     * Return localised event name.
     *
     * @return string
     */
    public static function get_name() {
        return get_string('eventapprovecado', 'mod_cado');
    }

    /**
     * Get URL related to the action
     *
     * @return \moodle_url
     */
    public function get_url() {
        $url = new \moodle_url('/mod/cado/view.php', ['id' => $this->contextinstanceid]);
        return $url;
    }

    public static function get_objectid_mapping() {
        return array('db' => 'cado', 'restore' => 'cado');
    }

/*    public static function get_other_mapping() {
        $othermapped = array();
        $othermapped['forumid'] = array('db' => 'forum', 'restore' => 'forum');
        $othermapped['discussionid'] = array('db' => 'forum_discussions', 'restore' => 'forum_discussion');

        return $othermapped;
    }
    */
}
