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

defined('MOODLE_INTERNAL') || die;

/**
 * Version 1.0
 * Misc set of functions used to get a) cado module configuration settings, b) names of users, c) debugging, and d) the constructor used for schedule info
 *
 * @package   mod_cado
 * @copyright 2020 Naomi Quirke
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
*/


function cadosorter($key) {
    return function ($a, $b) use ($key) {
		$al = $a[$key];
		$bl = $b[$key];
		if ($al == $bl) {
			return 0;
		}
		return ($al > $bl) ? +1 : -1;
	};
}
function cadosort($sortarray,$sortkey) {
	usort($sortarray, cadosorter($sortkey));
	return $sortarray;
}  
class mod_cado_check {

    public $taglist;
    public $tagset;
    public $schedtag;
    public $schedulesetup;
    public $tagsinsched;

    /**
     * If tag information to be included in CADO, whether or not it should be in schedule, then this is put into the var $taglist.
     * Find out if a schedule is to be generated, and if so, whether it includes various tag information or is null.  
     * $schedulesetup 0 for no schedule, 1 for schedule
     * $tagsinsched = 0 for no tags in schedule, 1 otherwise
     * @param integer $courseid course id of CADO
     */
    public function __construct($courseid) {
        global $DB;
        $this->schedulesetup = $this->options('schedule','cadooptions') ? 1 : 0; //schedule wanted
        $this->tagsinsched = $this->schedulesetup and get_config('cado')->tagschedule ? 1: 0; //tags in schedule or not
        $gettags = $this->options('tags','cadooptions');
        if ($gettags && get_config('cado')->tagslist) { //check to see if tags wanted at all and tags are present                

            $tags =  explode (",", get_config('cado')->tagslist);   
            foreach ($tags as $key => $value) {
                $this->tagset['tag' . $key] = trim($value);
            }
            $this->schedtag['tag0'] = $this->schedtag['tag1'] = $this->schedtag['tag2'] = FALSE; //will set these to TRUE if some activity has this tag, used to determine whether to include heading in schedule

            $sql = 'SELECT ti.id, t.rawname, ti.itemid FROM {tag} t
                JOIN {tag_instance} ti on t.id = ti.tagid
                join {course_modules} cm on cm.id = ti.itemid
                WHERE ti.itemtype = :item and cm.course = :course';

            $modresult = $DB->get_records_sql($sql,['item'=>'course_modules','course'=>$courseid]);  
            $this->taglist = [];
            foreach ($modresult as $thisresult) {
                $tagdetails = new stdClass;    
                $thistaglist = explode ("::",$thisresult->rawname); 
                $tagkey = array_search($thistaglist[0] , $this->tagset, TRUE);
                if ($tagkey !== FALSE) {
                    if (isset($this->schedtag[$tagkey])) { // then it may be included in schedule
                        $this->schedtag[$tagkey] = TRUE;
                    }
                    $tagdetails->tagcode = $tagkey;
                    $tagdetails->tagheading = $this->tagset[$tagkey];
                    $tagdetails->tagcontent = $thistaglist[1];
                    $tagdetails->cmid = $thisresult->itemid;
                    $this->taglist[] = $tagdetails;
                }
            }

        } else { $this->taglist = null;}
    }

    /**
     * Get heading tags and contents for a module, from $taglist, in the form of an array suitable for mustache
     * 
     * @param integer $mod is course module id
     */
    function get_tags($mod) {
        if (is_object($this->taglist) || is_array($this->taglist)) {  //checks to see if there actually are any relevant tags
            $thisarray = [];
            foreach ($this->taglist as $tagdetails) {
                if ($tagdetails->cmid == $mod)
                    $thisarray[] = ['tagheading'=>$tagdetails->tagheading, 'tagcontent'=>$tagdetails->tagcontent, 'tagcode'=>$tagdetails->tagcode];
            }
            return $thisarray ;
        } else {return null;}
    }

    /**
     * Check into the multiselect and comma delimited options, to see if an option is present
     * 
     * @param string $optionname is item to be searched for
     * @param string $optionset is the setting to be searched in: one of activityoptions,cadooptions,tagslist
     */
    public static function options( $optionname, $optionset) {
        $chosenset = explode (",", get_config('cado')->$optionset);
        return in_array( $optionname , $chosenset );
    }
    
    public static function logo() {
        return get_config('cado')->showlogo;
    }
    
    public static function includehidden() {
        return get_config('cado')->inchidden;
    }

    public static function sitecomment() {
        $com = get_config('cado')->sitecomment;
        return mod_cado_check::options('sitecomment','cadooptions') ? ($com ? $com: null) : null;
    }

    public static function sumschedule() {
        //this only gets called after check if there is a schedule
        return get_config('cado')->sumschedule;
    }

/*    static function cmp_obj($a, $b)
    {
        $al = $a->section;
        $bl = $b->section;
        if ($al == $bl) {
            return 0;
        }
        return ($al > $bl) ? +1 : -1;
    } */


/**
 * Get full name of user given their id, when the user whose name we want is not $USER
 * 
 * @param int $userid is user whose name we want to get
 */
    public static function getusername(int $userid) {
        global $DB;
        if ($userid == 0) {return "Anonymous";} //this is the case if the privacy component has deleted a user record
        $thisuser = $DB->get_record('user', ['id'=> $userid]);
        return fullname($thisuser);
    }

    public static function console( $data ){
        echo '<script>';
        echo 'console.log('. json_encode( $data ) .')';
        echo '</script>';
    }
} 