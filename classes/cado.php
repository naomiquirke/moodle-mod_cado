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
 * Library of functions and constants for CADO class
 *
 * @package   mod_cado
 * @copyright 2020 Naomi Quirke
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

/**
 * Creates and manages the generation and storage of a CADO report
 *
 * @package   mod_cado
 * @copyright 2020 Naomi Quirke
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_cado_cado {
    /** @var object instance of cado */
    public $instance;

    /** @var object context of cado */
    private $context;

    /** @var object course of cado */
    public $course;

    /** @var int groupingid of cado */
    public $groupingid;

    /**
     * Constructs a CADO instance
     *
     * @param stdClass $context context object
     * @param stdClass $coursemodule course module object
     * @param stdClass $course course object
     */
    public function __construct($context, $coursemodule, $course) {
            $this->context = $context;
            $this->course = $course;
            $this->instance = $coursemodule ? self::getcadorecord($coursemodule->instance) : null;
            $this->groupingid = $coursemodule->groupingid;
    }

    /**
     * Get cado instance from the database.
     *
     * @param integer $recordid as instance
     */
    public static function getcadorecord($recordid) {
        global $DB;
        return $DB->get_record('cado', ['id' => $recordid]);
    }

    /**
     * Delete this cado instance from the database.
     *
     * @param integer $id as instance
     */
    public static function delete_instance( $id) {
        global $DB;
        return $DB->delete_records('cado', array('id' => $id));
    }

    /**
     * Update cado instance in database due to an edit settings event.
     *
     * @param stdClass $update as data
     */
    public static function updatecadorecord(stdClass $update) {
        global $DB;
        $update->timemodified = time();
        return $DB->update_record('cado', $update);
    }

    /**
     * Update cado instance in database due to a proposal event.
     *
     * @param int $chosenapprover userid
     */
    public function proposeupdate(int $chosenapprover) {
        global $USER;
        $this->instance->timeproposed = time();
        $this->instance->timeapproved = 0; // This should be 0 already.
        $this->instance->generateuser = $USER->id;
        $this->instance->approveuser = $chosenapprover;
        self::updatecadorecord($this->instance);
    }
    /**
     * Update cado instance in database due to an approval / not-approval event, or an update to comments.
     *
     * @param stdClass $data as data from form
     */
    public function approveupdate(stdClass $data) {
        global $USER;
        $prev = $this->instance;
        $commenttag = '<p class="approvecommentreviewed">'
        . get_string('approvecommentreviewed', 'cado', ['user' => fullname($USER), 'date' => userdate(time())])
        . '</p>';
        $thehistory = $prev->approvecomment;
        $thisapprovecomment = format_text($data->comment['text'], $data->comment['format']);
        if ($data->allowedit == 1) {
            $thehistory = format_text($data->history['text'], $data->history['format']);
            if ($prev->approvecomment <> $thehistory) { // Then the approval history has been edited.
                $thehistory = $thehistory
                    . '<p class="approvecommentreviewed">'
                    . get_string('approvehistoryedited', 'cado', ['user' => fullname($USER), 'date' => userdate(time())])
                    . '</p>';
            }
        }
        if ($data->approved) {
            if ($prev->timeapproved == 0) {
                // Was not approved and changed to approved.
                $this->instance->timeapproved = time();
                // If it hasn't been proposed before, Approver === Proposer.
                if ($prev->timeproposed == 0) {
                    $this->instance->timeproposed = time();
                }
            }
        } else {
            // Set to 0 to indicate need for edit.
            $this->instance->timeproposed = 0;
            // Reset to 0 if change from approved back to not approved.
            $this->instance->timeapproved = 0;
        }
        $this->instance->approvecomment = $thisapprovecomment . $commenttag . $thehistory;
        $this->instance->approveuser = $USER->id;
        self::updatecadorecord($this->instance);
    }

    /**
     * Add this instance to the database.
     *
     * @param stdClass $formdata The data submitted from the form
     */
    public static function add_instance(stdClass $formdata) {
        global $DB;
        // Add the database record.
        $newcadorec = new stdClass();
        $newcadorec->name = $formdata->name;
        $newcadorec->course = $formdata->course;
        $newcadorec->timegenerated = 0;
        $newcadorec->cadointro = format_text($formdata->cadointro['text'] , $formdata->cadointro['format']);
        if (mod_cado_check::options('cadobiblio', 'cadooptions')) {
            $newcadorec->cadobiblio = format_text($formdata->cadobiblio['text'], $formdata->cadobiblio['format']);
        }
        if (mod_cado_check::options('cadocomment', 'cadooptions')) {
            $newcadorec->cadocomment = format_text($formdata->cadocomment['text'], $formdata->cadocomment['format']);
        }
        return $DB->insert_record('cado', $newcadorec);
    }

    /**
     * Update this instance to the database, checks to ensure updates are valid to make.
     *
     * @param stdClass $formdata The data submitted from the form
     */
    public static function update_instance(stdClass $formdata) {
        $update = new stdClass();
        $update->name = $formdata->name;
        $update->id = $formdata->instance;
        if (!$formdata->isapproved) { // Only regenerates if not approved.
            $update->timegenerated = 0;
            // Leave any old information in database, in case it is required later.
            // We have to test for options, because if it is not available then it won't be present in the form,
            // and we don't want to overwrite with nulls.
            if (mod_cado_check::options('cadobiblio', 'cadooptions')) {
                $update->cadobiblio = format_text($formdata->cadobiblio['text'], $formdata->cadobiblio['format']);
            }
            if (mod_cado_check::options('cadocomment', 'cadooptions')) {
                $update->cadocomment = format_text($formdata->cadocomment['text'], $formdata->cadocomment['format']);
            }
            $update->cadointro = format_text($formdata->cadointro['text'] , $formdata->cadointro['format']);
                // Grouping handled by cm.
        }
        return self::updatecadorecord($update);
    }

    /**
     * Generate a CADO report for this instance.
     *
     * @param mod_cado_renderer $reportrenderer
     */
    public function cadogenerate($reportrenderer) {
        global $USER;
        $siteoptions = get_config('cado');
        $chosenset = explode (",", $siteoptions->cadooptions);
        $siteoptions->cadooptions = $chosenset;

        $genwhat = $this->report_course();
        /* Not sufficient to just use existence of content as the boolean.
        Because if site options change off then on, want to retrieve the text. */
        $genwhat->commentexists = in_array( 'cadocomment' , $chosenset );
        $genwhat->biblioexists = in_array( 'cadobiblio' , $chosenset );
        // Keep this because it is not obvious why an activity may have been included or not in the future.
        $genwhat->inchidden = $siteoptions->inchidden;
        $genwhat->summary = in_array( 'summary' , $chosenset ) ? $this->course->summary : null;
        $genwhat->sitecomment = mod_cado_check::sitecomment();
        $genwhat->fullname = $this->course->fullname;
        $this->instance->generatedjson = json_encode($genwhat,
            JSON_FORCE_OBJECT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK);

        if ($siteoptions->storegeneratedhtml) {
            $genwhat->logourl = $siteoptions->showlogo ? $reportrenderer->get_logo_url() : null;
            $genwhat->cadointro = $this->instance->cadointro;
            $genwhat->cadocomment = $genwhat->commentexists ? $this->instance->cadocomment : null;
            $genwhat->cadobiblio = $genwhat->biblioexists ? $this->instance->cadobiblio : null;
            $this->instance->generatedpage = $reportrenderer->render_cado($genwhat);
        }

        $this->instance->timegenerated = time();
        $this->instance->timeproposed = 0; // Set to 0 to reset the proposal time back to the 'not proposed' value of 0.
        $this->instance->generateuser = $USER->id;
        $success = self::updatecadorecord($this->instance);
        return $success;
    }

    /**
     * Generate the rubric report for an assignment or forum.
     *
     * @param integer $cmid is course module id
     */
    private function get_rubric($cmid) {
        global $DB;
        $sql = "SELECT levels.id
                        , crit.id as crit
                        , garea.activemethod
                        , crit.description
                        , levels.definition
                        , levels.score
                    FROM {context} ctx
                    JOIN {grading_areas} garea ON ctx.id = garea.contextid
                    JOIN {grading_definitions} gd ON garea.id = gd.areaid
                    JOIN {gradingform_rubric_criteria} crit ON gd.id = crit.definitionid
                    JOIN {gradingform_rubric_levels} levels ON levels.criterionid = crit.id
                    WHERE garea.activemethod = ? AND ctx.instanceid = ?
                    ORDER BY crit, score desc";
            $returnedsql = $DB->get_records_sql($sql, ['rubric', $cmid]);

            // Now loop through, rearranging into nested arrays for mustache table layout.
            $criterion = [];
            $lastcriterion = -1;
            $counter = -1;
            // For some unknown reason, mustache can't take any array keys unless automatically created,
            // otherwise it treats the array as an object.
        foreach ($returnedsql as $item) {
            if (($lastcriterion == -1) || ($lastcriterion <> $item->crit)) {
                // First time through for each criterion (item->crit gives the current criterion).
                $counter ++;
                $lastcriterion = intval($item->crit); // Crit = criterion id.
                $lastdesc = $item->description;
                $levels = []; // Reset for each criterion.
                $levels[] = ['leveldesc' => $item->definition, 'points' => sprintf("%-1.5g", $item->score)];
                $criterion[] = array('critdesc' => $lastdesc, 'levels' => $levels, 'totalpoints' => 0 - $item->score);
                // The first $item->score will be the greatest because it has been ordered so in the SQL,
                // the later sort is ascending,so make negative.
            } else {
                $levels[] = ['leveldesc' => $item->definition, 'points' => sprintf("%-1.5g", $item->score)];
                $criterion[$counter]['levels'] = $levels; // Update criterion with new array after it has been added to.
            }
        }
        return $this->cadosort($criterion, 'totalpoints'); // Now sort the criteria by the most negative.
    }

    /**
     * Sorts a multidimensional array by the given key
     *
     * @param array $sortarray is the array we want to sort
     * @param string $sortkey is the key we want the array to be sorted by
     */
    private function cadosort($sortarray, $sortkey) {

        $callback = function ($a, $b) use ($sortkey) {
            $al = $a[$sortkey];
            $bl = $b[$sortkey];
            if ($al == $bl) {
                return 0;
            }
            return ($al > $bl) ? +1 : -1;
        };

        usort($sortarray, $callback);
        return $sortarray;
    }

    /**
     * Generate the module specific elements for the CADO report and deal with grouping.
     *
     */
    private function report_course() {
        global $DB;

        $courseid = $this->course->id;
        $grouping = $this->groupingid;
        $siteoptions = get_config('cado');
        $visible = $siteoptions->inchidden == 1 ? 0 : 1;

        $courseext = new stdClass;
        $courseext->cadoversion = $this->get_version();
        $courseext->groupingname = $grouping ? $DB->get_record('groupings', array('id' => $grouping), 'name')->name : null;

        // SCHEDULE and TAGS SETUP.
        $sched = new mod_cado_check($courseid);
        list($schedule, $sections) = $sched->schedulesetup ? $this->startschedule2() : null;
        $courseext->weekly = $this->course->format == "weeks";
        // So that schedule can have week information removed if not relevant.
        $modlist = $siteoptions->activityoptions;
        $modarray = explode(',', $modlist);
        $allmodinfo = $this->getcadodata($modarray, ['course' => $courseid, 'groupingid' => $grouping, 'visible' => $visible]);
        foreach (['forum', 'quiz', 'assign'] as $thistype) { // Include *all* possible, because need to get exists boolean below.
            $temparray = [];
            foreach ($allmodinfo as $thismod) {
                if ($thismod->modtype == $thistype) {
                    $temparray[] = $this->getmoddetails($thistype, $thismod, $sched, $schedule, $sections);
                }
            }
            $exists = $thistype . 'exists';
            $courseext->$exists = !empty($temparray);
            $courseext->$thistype = $temparray;
        }

        // ALL.
        if ($sched->schedulesetup) {
            ksort($schedule, SORT_NUMERIC);
            $courseext->schedule = $schedule;
            $courseext->scheduleexists = true;
            if ((is_object($sched->tagset) || is_array($sched->tagset)) && $sched->tagsinsched) {
                // Checks to see if there actually are any relevant tags, when tags are turned on in the schedule.
                foreach ($sched->tagset as $tagkey => $tag) {
                    if (isset($sched->schedtag[$tagkey]) && $sched->schedtag[$tagkey]) {
                        $heading = 'head' . $tagkey;
                        $courseext->$heading = $tag;
                        $courseext->tagsinsched = true;
                    }
                }
            }
            $courseext->schedheads = [
                "section" => $courseext->weekly ? get_string('week', 'cado') : get_string('section', 'cado'),
                "startdate" => get_string('weekdate', 'cado'),
                "name" => get_string('topic', 'cado'),
                "task" => get_string('task', 'cado'),
                "date" => get_string('datedue', 'cado'),
            ];
        }

        return $courseext;
    }
    /**
     * Generate the start of the schedule table, topic headings and weeks.
     *
     */
    private function startschedule2() {
        global $DB;
        $weekly = $this->course->format == "weeks";
        $weeks = [];
        $returned = $DB->get_records('course_sections', ['course' => $this->course->id]);
        foreach ($returned as $topic) {
            $descriptor = $topic->name ? $topic->name : strip_tags($topic->summary);
            // Use the topic name in the schedule, if empty use the summary.
            $descriptor = strtr($descriptor, array_flip(get_html_translation_table(HTML_ENTITIES, ENT_QUOTES)));
            $week = [
                'section' => $topic->section,
                'name' => $descriptor,
                'startdate' => $weekly && ($topic->section != "0") ?
                    $this->usetime(strtotime( '+' . ($topic->section - 1) . ' weeks', $this->course->startdate), -1) : null,
                'tasks' => [],
                'sum' => 0
            ];
            $weeks[(int)$topic->section] = $week;
        }
        $numweeks = 0;
        $configuration = get_config('cado');
        if ($configuration->sumschedule && $configuration->tagschedule) {
            $numweeks = count($weeks);
            $weeks[$numweeks] = ['section' => "", 'name' => get_string('schedulesum', 'cado'), 'tasks' => [], 'sum' => true];
        }
        return [$weeks, $numweeks];
    }
    /**
     * Generate the module sections of the report.
     *
     * @param string $modtype is the module type, either 'quiz', 'forum', or 'assign'
     * @param stdClass $thismod is the module database record
     * @param mod_cado_check $sched which gets updated with tag entries
     * @param array $schedule contains all the schedule info
     * @param array $totalrow contains number of sections
     */
    private function getmoddetails($modtype, $thismod, $sched, &$schedule, $totalrow) {
        $contents = []; // Returned.
        $thisrubric = $modtype == 'assign' || $modtype == 'forum' ? $this->get_rubric($thismod->id) : [];

        $dates = [];
        $completion = [];
        if ($modtype == 'forum') {
            $this->labelout($dates, get_string('duedate', 'forum'), $this->usetime($thismod->forumduedate));
            $this->labelout($dates, get_string('cutoffdate', 'forum'), $this->usetime($thismod->forumcutoffdate));
            $this->labelout($completion, get_string('completiondiscussions', 'forum'), $thismod->completiondiscussions);
            $this->labelout($completion, get_string('completionreplies', 'forum'), $thismod->completionreplies);
            $this->labelout($completion, get_string('completionposts', 'forum'), $thismod->completionposts);
        };
        if ($modtype == 'assign') {
            $this->labelout($dates, get_string('duedate', 'assign'), $this->usetime($thismod->assignduedate));
            $this->labelout($dates, get_string('cutoffdate', 'assign'), $this->usetime($thismod->assigncutoffdate));
        };
        if ($modtype == 'quiz') {
            $this->labelout($dates, get_string('quizclose', 'quiz'), $this->usetime($thismod->timeclose));
            $this->labelout($dates, get_string('quizopen', 'quiz'), $this->usetime($thismod->timeopen));
            $this->labelout($completion, get_string('timelimit', 'quiz'),
                $thismod->timelimit == "0" ? get_string('notapplicable', 'cado') : intval($thismod->timelimit / 60) );
            $this->labelout($completion, get_string('attempts', 'quiz'),
                $thismod->attempts == "0" ? get_string('notapplicable', 'cado') : $thismod->attempts );
            $thismod->quizduedate = $thismod->timeclose ? $thismod->timeclose :
                ($thismod->timeopen ? $thismod->timeopen +
                ($thismod->timelimit ? $thismod->timelimit : 0) : 0);
            $thismod->quizcutoffdate = 0;
        };
        $this->labelout($dates, get_string("{$modtype}expectcompleted", 'cado'), $this->usetime($thismod->completionexpected));
        $intro = "{$modtype}intro";
        $name = "{$modtype}name";
        $contents = [ // Seems to need automatically defined keys for mustache.
            'module' => $modtype,
            'cmodid' => $thismod->id,
            'name' => htmlspecialchars_decode($thismod->$name),
            'link' => new moodle_url("/mod/{$modtype}/view.php", ['id' => $thismod->id]),
            'intro' => $thismod->$intro,
            'introexists' => $intro == true,
            'dates' => $dates,
            'datesexists' => !empty($dates),
            'completion' => $completion,
            'completionexists' => !empty($completion),
            'extra' => $sched->get_tags($thismod->id),
            'rubric' => $thisrubric,
            'rubricexists' => !empty($thisrubric)
        ];
        // Store the link as a string.
        $contents['link'] = $contents['link']->out();

        if ($sched->schedulesetup) {
            $duedate = "{$modtype}duedate";
            $cutoffdate = "{$modtype}cutoffdate";
            $priority1 = $thismod->$duedate;
            $priority2 = $thismod->$cutoffdate;
            $priority3 = $thismod->completionexpected;
            $orderdate = $priority1 ? $priority1 : ($priority2 ? $priority2 : $priority3);
            $scheduleentry = [
                'name' => $contents['name'],
                'date' => $this->usetime($orderdate, 1)
            ];
            if ($contents['extra']) {
                foreach ($contents['extra'] as $tagdetails) {
                    $scheduleentry = array_merge($scheduleentry , [$tagdetails['tagcode'] => $tagdetails['value']]);
                    if ( is_numeric($tagdetails['value']) && $totalrow) { // Then add up tag values.
                        if (!isset($schedule[$totalrow]['tasks'][$tagdetails['tagcode']])) {
                            $schedule[$totalrow]['tasks'][$tagdetails['tagcode']] = 0;
                        }
                        $schedule[$totalrow]['tasks'][$tagdetails['tagcode']] += $tagdetails['value'];
                    }
                }
            }
            $contents["extraexists"] = !empty($contents['extra']);
            $schedule[(int)$thismod->section]["tasks"][] = $scheduleentry;
        }
        return $contents;
    }
    /**
     * Does a check that a value exists and is non-zero, and if so adds a label and appends to results array.
     *
     * @param array $addarray results output.
     * @param string $thislabel the label to add.
     * @param string $thisvalue the value to add.
     */
    private function labelout(&$addarray, $thislabel, $thisvalue) {
        if ($thisvalue) {
            $addarray[] = ["label" => $thislabel, "value" => $thisvalue];
        }
    }
    /**
     * From unix time, returns either userdate or "".
     *
     * @param int $inttime is the time given: null, 0 or other int.
     * @param int $short is the format to use; 1 for with time, -1 for no time, 0 for full.
     */
    private function usetime($inttime, $short = 0) {
        if ($inttime === null) {
            return null;
        } else if ($inttime == 0) {
            return null;
        } else if ($short == 1) {
            return userdate($inttime, get_string('strftimedatetimeshort', 'langconfig'));
        } else if ($short == -1) {
            return userdate($inttime, get_string('strftimedatefullshort', 'langconfig'));
        } else {
            return userdate($inttime, get_string('strftimedatetime', 'langconfig'));
        }
    }
    /**
     * Runs the SQL Query.
     *
     * @param array $modarray list of modules to be included.
     * @param array $sqlparams = ['course' => $courseid, 'groupingid' => $grouping, 'visible' => $visible].
     */
    private function getcadodata($modarray, $sqlparams) {
        global $DB;
        if (empty($modarray)) {
            return [];
        }
        list($insql, $inparams) = $DB->get_in_or_equal($modarray, SQL_PARAMS_NAMED, 'type');
        $sqlparams += $inparams;
        $forum = in_array('forum', $modarray);
        $quiz = in_array('quiz', $modarray);
        $assign = in_array('assign', $modarray);

        // MySQL prior to v8 can't handle 'with' constructs.

        // Get all the groups that may access each activity module.
        $modgroups = "SELECT cm.id cmod, cm.instance, gm.id modgroup, mo.name modtype, cm.groupingid,
            cm.completionexpected, cs.section
            FROM {course} c
                JOIN {course_modules} cm on cm.course = c.id
                JOIN {modules} mo on mo.id = cm.module
                JOIN {course_sections} cs on cm.course = cs.course and cm.section = cs.id
                LEFT JOIN {groupings} ggm on ggm.id =  cm.groupingid
                LEFT JOIN {groupings_groups} gggm on gggm.groupingid = ggm.id
                LEFT JOIN {groups} gm on gggm.groupid = gm.id
            WHERE c.id=:course and cm.visible >= :visible and mo.name $insql
                and ((cm.completion <> 0 and c.enablecompletion = 1) or c.enablecompletion = 0)";

        // Get all the groups that are in our target grouping.
        $coursegrouping = "SELECT g.id coursegroup
            FROM {groupings} gg
                JOIN {groupings_groups} ggg on ggg.groupingid = gg.id
                JOIN {groups} g on  ggg.groupid = g.id
            WHERE gg.id = :groupingid";

        // Find all the activities that have groups accessing that activity that are in our target grouping,
        // or activities that do not have grouping restrictions.
        $chosenmods = "SELECT distinct mg.cmod id, mg.instance, mg.modtype, mg.completionexpected, mg.section
            FROM ( $modgroups ) mg
                LEFT JOIN ( $coursegrouping ) cg on cg.coursegroup = mg.modgroup
            WHERE mg.groupingid= 0 or cg.coursegroup = mg.modgroup";

        // Access all the mod info now given that we have already gathered half the information.
        $sqlselect = "SELECT cm.* ";
        $sqlfroms = "
            FROM ($chosenmods ) cm ";
        $sqlorderby = "
            ORDER BY cm.modtype ";
        if ($forum) {
            $sqlselect .= ", f.name forumname, f.intro forumintro
                , f.duedate forumduedate, f.cutoffdate forumcutoffdate
                , completiondiscussions, completionreplies, completionposts";
            $sqlfroms .= "
                LEFT JOIN {forum} f on f.id = cm.instance and cm.modtype = 'forum' ";
            $sqlorderby .= ", f.duedate";
        }
        if ($quiz) {
            $sqlselect .= ", q.name quizname, q.intro quizintro
                , timeclose, timeopen, timelimit, attempts ";
            $sqlfroms .= "
                LEFT JOIN {quiz} q on q.id = cm.instance and cm.modtype = 'quiz' ";
            $sqlorderby .= ", timeclose";
        }
        if ($assign) {
            $sqlselect .= ", a.name assignname, a.intro assignintro
                , a.duedate assignduedate, a.cutoffdate assigncutoffdate ";
            $sqlfroms .= "
                LEFT JOIN {assign} a on a.id = cm.instance and cm.modtype = 'assign' ";
            $sqlorderby .= ", a.duedate";
        }
        $sqlorderby .= ", cm.completionexpected";

        $sql = $sqlselect . $sqlfroms . $sqlorderby;
        return $DB->get_records_sql($sql, $sqlparams);
    }

    /**
     * Obtains version number from version.php for this plugin.
     *
     * @return string Version number
     */
    protected function get_version() {
        global $CFG;
        $plugin = new stdClass;
        $plugin->version = null;
        include($CFG->dirroot . '/mod/cado/version.php');
        return $plugin->version;
    }
}
