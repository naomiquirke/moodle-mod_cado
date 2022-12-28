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
 * Misc set of functions used to get a) cado module configuration settings, b) names of users, and c) schedule info.
 *
 * @package   mod_cado
 * @copyright 2020 Naomi Quirke
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Misc class used to get a) cado module configuration settings, b) names of users, and c) schedule info.
 *
 * @package   mod_cado
 * @copyright 2020 Naomi Quirke
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_cado_check {

    /** @var array list of tags */
    public $taglist;
    /** @var array list of tag keys */
    public $tagset;
    /** @var int schedtag 0 for tags not in schedule, 1 in schedule */
    public $schedtag;
    /** @var int schedulesetup 0 for no schedule, 1 for schedule */
    public $schedulesetup;
    /** @var int tagsinsched = 0 for no tags in schedule, 1 otherwise */
    public $tagsinsched;

    /**
     * Fills out the options information into the public variables, building the tag set etc.
     *
     * @param integer $courseid course id of CADO
     */
    public function __construct($courseid) {
        global $DB;
        $config = get_config('cado');
        $this->schedulesetup = $this->options('schedule', 'cadooptions') ? 1 : 0; // Schedule wanted.
        $this->tagsinsched = $this->schedulesetup && $config->tagschedule ? 1 : 0; // Tags in schedule or not.
        $gettags = $this->options('tags', 'cadooptions');
        if ($gettags && $config->tagslist) { // Check to see if tags wanted at all and tags are present.

            $tags = explode (",", $config->tagslist);
            foreach ($tags as $key => $value) {
                $this->tagset['tag' . $key] = trim($value);
            }
            $this->schedtag['tag0'] = $this->schedtag['tag1'] = $this->schedtag['tag2'] = false;
            // Will set these to TRUE if some activity has this tag, used to determine whether to include heading in schedule.

            $sql = 'SELECT ti.id, t.rawname, ti.itemid FROM {tag} t
                JOIN {tag_instance} ti on t.id = ti.tagid
                join {course_modules} cm on cm.id = ti.itemid
                WHERE ti.itemtype = :item and cm.course = :course';

            $modresult = $DB->get_records_sql($sql, ['item' => 'course_modules', 'course' => $courseid]);
            $this->taglist = [];
            foreach ($modresult as $thisresult) {
                $tagdetails = new stdClass;
                $thistaglist = explode ("::", $thisresult->rawname);
                $tagkey = array_search($thistaglist[0] , $this->tagset, true);
                if ($tagkey !== false) {
                    if (isset($this->schedtag[$tagkey])) { // Then it may be included in schedule.
                        $this->schedtag[$tagkey] = true;
                    }
                    $tagdetails->tagcode = $tagkey;
                    $tagdetails->label = $this->tagset[$tagkey];
                    $tagdetails->value = $thistaglist[1];
                    $tagdetails->cmid = $thisresult->itemid;
                    $this->taglist[] = $tagdetails;
                }
            }

        } else {
            $this->taglist = null;
        }
    }

    /**
     * Get heading tags and contents for a module, from $taglist, in the form of an array suitable for mustache
     *
     * @param integer $mod is course module id
     */
    public function get_tags($mod) {
        if (is_object($this->taglist) || is_array($this->taglist)) {  // Checks to see if there actually are any relevant tags.
            $thisarray = [];
            foreach ($this->taglist as $tagdetails) {
                if ($tagdetails->cmid == $mod) {
                    $thisarray[] = ['label' => $tagdetails->label, 'value' => $tagdetails->value,
                        'tagcode' => $tagdetails->tagcode];
                }
            }
            return $thisarray;
        } else {
            return null;
        }
    }

    /**
     * Check into the multiselect and comma delimited options, to see if an option is present.
     *
     * @param string $optionname is item to be searched for
     * @param string $optionset is the setting to be searched in: one of activityoptions,cadooptions,tagslist
     */
    public static function options( $optionname, $optionset) {
        $chosenset = explode (",", get_config('cado')->$optionset);
        return in_array( $optionname , $chosenset );
    }

    /**
     * Find out if sitecomment is allowed and if so what it is.
     *
     */
    public static function sitecomment() {
        $com = get_config('cado')->sitecomment;
        return self::options('sitecomment', 'cadooptions') ? ($com ? $com : null) : null;
    }


    /**
     * Get full name of user given their id, when the user whose name we want is not $USER
     *
     * @param int $userid is user whose name we want to get
     */
    public static function getusername(int $userid) {
        global $DB;
        if ($userid == 0) {
            return get_string('useranonymous', 'cado');
        } // This is the case if the privacy component has deleted a user record.
        $thisuser = $DB->get_record('user', ['id' => $userid]);
        return fullname($thisuser);
    }

}
