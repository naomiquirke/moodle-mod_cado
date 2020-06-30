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
    private $course;

    /** @var object coursemodule info of cado */
    private $coursemodule; // Cm_info.

    /**
     * Constructs a CADO instance
     *
     * @param stdClass $context context object
     * @param stdClass $coursemodule course module object
     * @param stdClass $course course object
     */
    public function __construct($context, $coursemodule, $course) {
            $this->context = $context;
            $this->coursemodule = $coursemodule;
            $this->course = $course;
            $this->instance = $this->coursemodule ? self::getcadorecord($this->coursemodule->instance) : null;
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
     * Update cado instance in database due to an approval / not-approval event.
     *
     * @param stdClass $data as data from form
     */
    public function approveupdate(stdClass $data) {
        global $USER;
        if ($data->approved) {
            $this->instance->timeapproved = time();
        } else {
            $this->instance->timeapproved = 0; // Should be 0 already.
            $this->instance->timeproposed = 0;
        }
            $this->instance->approveuser = $USER->id;
            $this->instance->approvecomment = format_text($data->comment['text'], $data->comment['format'])
            . '<p class="approvecommentreviewed">'
            . get_string('approvecommentreviewed', 'cado', ['user' => fullname($USER), 'date' => userdate(time())])
            . '</p>';
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
        $genwhat = $this->report_course();

        $genwhat->logourl = get_config('cado')->showlogo ? $reportrenderer->get_logo_url() : null;
        $genwhat->sitecomment = mod_cado_check::sitecomment();
        $genwhat->fullname = $this->course->fullname;
        $genwhat->cadointro = $this->instance->cadointro;
        $genwhat->summary = mod_cado_check::options('summary', 'cadooptions') ? $this->course->summary : null;
        $genwhat->cadocomment = mod_cado_check::options('cadocomment', 'cadooptions') ? $this->instance->cadocomment : null;
        $genwhat->cadobiblio = mod_cado_check::options('cadobiblio', 'cadooptions') ? $this->instance->cadobiblio : null;

        $this->instance->generatedpage = $reportrenderer->render_course($genwhat);
        $this->instance->timegenerated = time();
        $this->instance->timeproposed = 0; // Set to 0 to reset the proposal time back to the 'not proposed' value of 0.
        $this->instance->generateuser = $USER->id;
        $success = self::updatecadorecord($this->instance);
        return $success;
    }

    /**
     * Generate the module specific elements for the CADO report and deal with grouping.
     *
     */
    private function report_course() {
        global $DB;
        $courseid = $this->course->id;
        $grouping = $this->coursemodule->groupingid;
        $visible = get_config('cado')->inchidden == 1 ? 0 : 1;

        $courseext = new stdClass;
        $courseext->groupingname = $grouping ? $DB->get_record('groupings', array('id' => $grouping), 'name')->name : null;
        // SCHEDULE and TAGS SETUP.
        $sched = new mod_cado_check($courseid);
        $schedule = $sched->schedulesetup ? $this->startschedule($sched) : null;
        $courseext ->weekly = $this->course->format == "weeks";
        // So that schedule can have week information removed if not relevant.

        // COMBINED
        $sql = "WITH mod_groups AS ( " .  // Get all the groups that may access each activity module.
                "SELECT cm.id cmod, cm.instance, gm.id modgroup, mo.name modtype, cm.groupingid, cm.completionexpected, cm.section
                FROM {course} c
                    JOIN {course_modules} cm on cm.course = c.id
                    JOIN {modules} mo on mo.id = cm.module
                    LEFT JOIN {groupings} ggm on ggm.id =  cm.groupingid
                    LEFT JOIN {groupings_groups} gggm on gggm.groupingid = ggm.id
                    LEFT JOIN {groups} gm on gggm.groupid = gm.id
                WHERE c.id=:course and cm.completion<>0 and cm.visible >= :visible and mo.name in ( 'assign' , 'forum' , 'quiz')
            )

            , course_grouping AS ( ". // Get all the groups that are in our target grouping.
                "SELECT g.id coursegroup
                FROM {groupings} gg
                    JOIN {groupings_groups} ggg on ggg.groupingid = gg.id
                    JOIN {groups} g on  ggg.groupid = g.id
                WHERE gg.id = :grouping
            )

            , chosen_mods AS ( ".
            // Find all the activities that have groups accessing that activity that are in our target grouping,
            // or activities that do not have grouping restrictions.
                "SELECT distinct mg.cmod id, mg.instance, mg.modtype, mg.completionexpected, mg.section
                FROM mod_groups mg
                    LEFT JOIN course_grouping cg on cg.coursegroup = mg.modgroup
                WHERE mg.groupingid= 0 or cg.coursegroup = mg.modgroup
            )
            " . // Access all the mod info now given that we have already gathered half the information.
            "SELECT cm.*
                , f.name fname, f.intro fintro, f.duedate fduedate, f.cutoffdate fcutoffdate, completiondiscussions
                , completionreplies, completionposts, q.name qname, q.intro qintro, timeclose, timeopen, timelimit, attempts
                , a.name aname, a.intro aintro, a.duedate aduedate, a.cutoffdate acutoffdate
            FROM chosen_mods cm
            LEFT JOIN {forum} f on f.id = cm.instance and cm.modtype = 'forum'
            LEFT JOIN {quiz} q on q.id = cm.instance and cm.modtype = 'quiz'
            LEFT JOIN {assign} a on a.id = cm.instance and cm.modtype = 'assign'
            ORDER BY cm.modtype, timeclose, f.duedate, a.duedate, cm.completionexpected, fcutoffdate, acutoffdate";

        $allmodinfo = $DB->get_records_sql($sql, ['course' => $courseid, 'grouping' => $grouping, 'visible' => $visible]);

        // FORUM.
        $temparray = [];
        if (mod_cado_check::options('forum', 'activityoptions')) {
            foreach ($allmodinfo as $thismod) {
                if ($thismod->modtype == 'forum') {
                    $temparray[] = self::getmoddetails('forum', $thismod, $sched, $schedule); // Sched is updated directly.
                }
            }

            $courseext ->forumexists = ($temparray == true); // Include for mustache header.
            $courseext ->forum = $temparray;
        }
        // QUIZ.
        if (mod_cado_check::options('quiz', 'activityoptions')) {

            $temparray = [];
            foreach ($allmodinfo as $thismod) {
                if ($thismod->modtype == 'quiz') {
                    $temparray[] = self::getmoddetails('quiz', $thismod, $sched, $schedule); // Sched is updated directly.
                }
            }
            $courseext ->quizexists = ($temparray == true);
            $courseext ->quiz = $temparray;
        }
        // ASSIGN.
        if (mod_cado_check::options('assign', 'activityoptions')) {

            $temparray = [];
            foreach ($allmodinfo as $thismod) {
                if ($thismod->modtype == 'assign') {
                    $temparray[] = self::getmoddetails('assign', $thismod, $sched, $schedule); // Sched is updated directly.
                }
            }
            $courseext ->assignexists = ($temparray == true);
            $courseext ->assign = $temparray;
        }
        // ALL.
        if ($sched->schedulesetup) {
            $courseext ->schedule = $this->cadosort($schedule, 'section');
            $courseext ->scheduleexists = 1;
            if ((is_object($sched->tagset) || is_array($sched->tagset)) and $sched->tagsinsched) {
                // Checks to see if there actually are any relevant tags, when tags are turned on in the schedule.
                foreach ($sched->tagset as $tagkey => $tag) {
                    if (isset($sched->schedtag[$tagkey]) && $sched->schedtag[$tagkey]) {
                        $heading = 'head' . $tagkey;
                        $courseext ->$heading = $tag;
                        $courseext ->tagsinsched = true;
                    }
                }
            }

        }
        return $courseext;
    }
    /**
     * Generate the start of the schedule table, topic headings and weeks.
     *
     */
    private function startschedule() {
        global $DB;
        $weekly = $this->course->format == "weeks";
        $weeks = [];
        $returned = $DB->get_records('course_sections', ['course' => $this->course->id]);
        foreach ($returned as $topic) {
            $descriptor = $topic->name ? $topic->name : strip_tags($topic->summary);
            // Use the topic name in the schedule, if empty use the summary.
            $descriptor = strtr($descriptor, array_flip(get_html_translation_table(HTML_ENTITIES, ENT_QUOTES)));
            $week = [
                'section' => (int)$topic->section,
                'name' => $descriptor,
                'startdate' => $weekly && ($topic->section != "0") ?
                    strtotime( '+' . ($topic->section - 1) . ' weeks', $this->course->startdate) : null,
                'tasks' => null,
                'sum' => false
            ];
            $weeks[$topic->id] = $week;
        }
        if (get_config('cado')->sumschedule) {
            $weeks[1000] = ['section' => 1000, 'name' => get_string('schedulesum', 'cado'), 'tasks' => [], 'sum' => true];
        }
        return $weeks;
    }
    /**
     * Generate the module sections of the report.
     *
     * @param string $modtype is the module type, either 'quiz', 'forum', or 'assign'
     * @param stdClass $thismod is the module database record
     * @param mod_cado_check $sched which gets updated with tag entries
     * @param array &$schedule contains all the schedule info
     */
    private function getmoddetails($modtype, $thismod, $sched, &$schedule) {
        $quiz = $modtype == 'quiz';
        $forum = $modtype == 'forum';
        $contents = array(); // Returned.
        // For forum and assign take as !$quiz; no options have assign alone in below.

        $thisrubric = $quiz ? false : $this->get_rubric($thismod->id);

        // From the sql we get either null or the matching details so we can use max.
        $thismod->intro = max($thismod->fintro, $thismod->qintro, $thismod->aintro);
        $thismod->duedate = max($thismod->fduedate, $thismod->aduedate);
        $thismod->cutoffdate = max($thismod->fcutoffdate, $thismod->acutoffdate);
        $thismod->name = max($thismod->fname, $thismod->qname, $thismod->aname);

        $contents = [ // Seems to need automatically defined keys for mustache.
            'cmodid' => $thismod->id,
            'name' => htmlspecialchars_decode($thismod->name),
            'intro' => $thismod->intro,
            'date' => ($thismod->completionexpected == "0" ? false : $thismod->completionexpected),
            'extra' => $sched->get_tags($thismod->id),
            'link' => $quiz ? new moodle_url('/mod/quiz/view.php', ['id' => $thismod->id]) :
                ($forum ? new moodle_url('/mod/forum/view.php',  ['id' => $thismod->id]) :
                    new moodle_url('/mod/assign/view.php',  ['id' => $thismod->id]) ),

            // Forum and assign.
            'duedate' => $quiz ? false : ($thismod->duedate == "0" ? false : $thismod->duedate),
            'cutoffdate' => $quiz ? false : ($thismod->cutoffdate == "0" ? false : $thismod->cutoffdate),
            'rubric' => $quiz ? false : $thisrubric,
            'rubricexists' => $quiz ? false : $thisrubric == true,

            // Forum.
            'completiondiscussions' => $forum ? $thismod->completiondiscussions : false,
            'completionreplies' => $forum ? $thismod->completionreplies : false,
            'completionposts' => $forum ? $thismod->completionposts : false,

            // Quiz.
            'timeclose' => $quiz ? ($thismod->timeclose == "0" ? false : $thismod->timeclose) : false,
            'timeopen' => $quiz ? ($thismod->timeopen == "0" ? false : $thismod->timeopen) : false,
            'timelimit' => $quiz ? ($thismod->timelimit == "0" ? get_string('notapplicable', 'cado') :
                intval($thismod->timelimit / 60)) : false,
            'attempts' => $quiz ? ($thismod->attempts == "0" ? get_string('notapplicable', 'cado') : $thismod->attempts) : false

        ];
        if ($sched->schedulesetup) {
            $orderdate = $quiz ? ($thismod->timeclose ? $thismod->timeclose :
                ($thismod->completionexpected ? $thismod->completionexpected :
                0) ) :
                    ($thismod->duedate ? $thismod->duedate :
                        ($thismod->completionexpected ? $thismod->completionexpected :
                            ($thismod->cutoffdate ? $thismod->cutoffdate :
                                 0)));

            $scheduleentry = [
                'name' => $contents['name'],
                'date' => $orderdate == 0 ? null : $orderdate
            ];

            if ($contents['extra']) {
                foreach ($contents['extra'] as $tagdetails) {
                    $scheduleentry = array_merge($scheduleentry , [$tagdetails['tagcode'] => $tagdetails['tagcontent']]);
                    if ( is_numeric($tagdetails['tagcontent']) && get_config('cado')->sumschedule) { // Then add up tag values.
                        if (!isset($schedule[1000]['tasks'][$tagdetails['tagcode']])) {
                            $schedule[1000]['tasks'][$tagdetails['tagcode']] = 0;
                        }
                        $schedule[1000]['tasks'][$tagdetails['tagcode']] += $tagdetails['tagcontent'];
                    }
                }
            }
            $schedule[(int)$thismod->section]["tasks"][] = $scheduleentry;
        }
        return $contents;
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
     * Why can't I find this built into PHP?  Am I blind?
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
     * Generate the comparison between cados, using generated html.
     *
     * @param integer $otherid is cmid for other cado
     */
    public function compare($otherid) {
        global $DB;
        $othercmod = $DB->get_record('course_modules', ['id' => $otherid])->instance;
        $otherinstance = self::getcadorecord($othercmod);
        $othergenerated = $otherinstance->generatedpage;
        $otherapprovecomment = $otherinstance->approvecomment;
        $origingenerated = $this->instance->generatedpage;
        $originapprovecomment = $this->instance->approvecomment;
        $stringparam = new stdClass;
        $stringparam->origin = $this->instance->name;
        $stringparam->other = $otherinstance->name;
        $compareheader = get_string('comparing', 'cado', $stringparam);
        if (strcasecmp($otherapprovecomment, $originapprovecomment) != 0) {
            $compareheader = $compareheader . '<br>' . get_string('approvalcomparisondifferent', 'cado');
        }

        $allmatched = true; // Just to see if everything matches except cmid, this is the case for an exact backup between courses.

        // Check to see if not all identical.  Complete identicality will only occur in the same course,
        // otherwise the cmids will be different. Note we don't care about case, otherwise use strcmp.
        if (strcasecmp($origingenerated, $othergenerated) != 0) {

            // Now to setup the id labels arrays.
            $outerlabels = ['grouping', 'intro', 'coursesummary', 'schedule', 'comment', 'forum', 'quiz'
                , 'assign', 'sitecomment', 'biblio'];
            $originarray = explode('id="cado-', $origingenerated);
            $otherarray = explode('id="cado-', $othergenerated);

            (int) $splitcount = 1;
            foreach ($outerlabels as $value) {
                // NQ note comparing schedule is really of doubtful value until we get a finer grain compare than we have now.
                $originstart = strpos($originarray[$splitcount], ">", 0);
                $origincontent = substr($originarray[$splitcount], $originstart + 1);
                // Need to keep tags here so we can break down further.

                $otherstart = strpos($otherarray[$splitcount], ">", 0);
                $othercontent = substr($otherarray[$splitcount], $otherstart + 1);
                $a1 = self::baseclean($origincontent);
                $a2 = self::baseclean($othercontent);
                $result = strcasecmp( $a1 , $a2 );
                if ($result != 0) {
                    if ( in_array($value, ['forum', 'quiz', 'assign'])) {
                        $origininnerarray = explode('id="cadoi-', $origincontent);
                        $ori1 = array_map('mod_cado_cado::cleanline', $origininnerarray);
                        $otherinnerarray = explode('id="cadoi-', $othercontent);
                        $oth1 = array_map('mod_cado_cado::cleanline', $otherinnerarray);

                        // Make new arrays with key as cmid.
                        $origininner = [];
                        foreach ($ori1 as $value1) {
                            if (!is_numeric($value1['cmid'])) {
                                continue;
                            } // Skip the blurb at the beginning of the section; there will be no cmid found for this section.
                            $origininner[$value1['cmid']][$value1['modtype']] = $value1['content'];
                        }
                        $otherinner = [];
                        foreach ($oth1 as $value2) {
                            if (!is_numeric($value2['cmid'])) {
                                continue;
                            } //Skip the blurb at the beginning of the section; there will be no cmid found for this section.
                            $otherinner[$value2['cmid']][$value2['modtype']] = $value2['content'];
                        }

                        // Now check for matches from origin to other.
                        foreach ($origininner as $orikey1 => $orival1) {
                            foreach ($otherinner as $othkey1 => $othval1) {
                                if (array_values($orival1) == array_values($othval1)) {// Content match.
                                    $otherinner[$othkey1]['name'] = 'done';
                                    continue 2; // Now go to next $orival1.
                                }
                                if (isset($orival1['name']) && isset($othval1['name']) && ($orival1['name'] == $othval1['name'])) {
                                    // Then activities match, but we know the content doesn't.
                                    foreach ($orival1 as $orikey2 => $orival2) { // The key here is the descriptor name.
                                        if (!isset($othval1[$orikey2])) {// A component of an activity is missing.
                                            $class = ' class="cado-othermissing"';
                                            $idname = $value . '-' . $orikey2 . '_' . $orikey1 . '"';
                                            $originarray[$splitcount] = self::addalert($originarray[$splitcount], $idname, $class);
                                            $allmatched = false;
                                        } else if (strcmp($orival2 , $othval1[$orikey2]) != 0) {
                                            // Mark the descriptor as different.
                                            $class = ' class="cado-different"';
                                            $idname = $value . '-' . $orikey2 . '_' . $orikey1 . '"';
                                            $originarray[$splitcount] = self::addalert($originarray[$splitcount], $idname, $class);
                                            $allmatched = false;
                                        }
                                    }
                                    // Now set the name as 'done' so that we don't find it again.
                                    $otherinner[$othkey1]['name'] = 'done';
                                    continue 2; // Now go to next $orival1.
                                }
                                // Otherwise see if can find match on next loop; if not, then note after the second foreach.
                            }
                            // If arrived here then missing entire activities from other.
                            $class = ' class="cado-othermissing"';
                            $idname = $value . '-name_' . $orikey1 . '"';
                            $originarray[$splitcount] = self::addalert($originarray[$splitcount], $idname, $class);
                            $allmatched = false;
                        }
                        // Now check for missing matches from other to origin.
                        foreach ($otherinner as $othval2) {
                            if (!isset($othval2['name']) or $othval2['name'] == 'done') {
                                continue;
                            } // Not proper section, or already matched.
                            $classandcomment = '<br><div class="cado-originmissing">'
                                . get_string('comparemissing', 'cado') . $othval2['name'] .
                                '</div><br>'; // Add a new comment and apply class to it.
                            $insertposition = strpos($originarray[$splitcount], '</h2>');
                            // If there is no appropriate heading, then the above will be 0,
                            // so just insert after first div of the section instead;
                            // need to insert after search sequence, so add number of characters in the search sequence.
                            $insertposition = $insertposition == false ? strpos($originarray[$splitcount], '</div>') + 6
                                : $insertposition + 5;
                            $originarray[$splitcount] = substr_replace($originarray[$splitcount], $classandcomment
                                , $insertposition, 0);
                            $allmatched = false;
                        }
                    } else { // Not a module so don't have to do such exhaustive treatment if there is no match.
                        $class = ' class="cado-different"';
                        $originarray[$splitcount] = substr_replace($originarray[$splitcount], $class, $originstart, 0);
                        $allmatched = false;
                    }
                }
                $splitcount++;
            }
            $updated = implode('id="cado-', $originarray);
        }
        if ($allmatched) {
            return ['header' => $compareheader, 'subheader' => get_string('comparisonidentical', 'cado')
                , 'content' => $origingenerated];
        }
        return ['header' => $compareheader, 'subheader' => get_string('comparisondifferent', 'cado') , 'content' => $updated];
    }

    /**
     * To find the core text to be compared
     *
     * @param string $oa is the string to label and tidy
     * Returns array with the broad section the text is in (=modtype), the course module i
     *
     */
    public static function cleanline($oa) {
        $firstbreak = strpos($oa, '-', 1);
        if ($firstbreak === false) {
            return false;
        } //Then there are no recognizable ids in here at all.
        $secondbreak = strpos($oa, '_');
        $thirdbreak = strpos($oa, '">');
        $thistype = substr( $oa, $firstbreak + 1 , $secondbreak - $firstbreak - 1);

        $ok = substr( $oa, $thirdbreak + 2 ); // It is +2 because we have two characters being searched for rather than one.
        $ok = strip_tags($ok);
        $chars = array("\r\n", "\n", "\r", "\t", "\0", "\x0B", "&#9741;"); // Remove the last character in list because I added it.
        $ok = str_replace($chars, "", $ok);
        $ok = preg_replace('/\xc2\xa0/', ' ', $ok);

        if ($thistype <> "name") {
            // If it is a name, then leave the white space in it because it gets used for display when there is an item missing.
            $ok = str_replace(" ", "", $ok);
        } else {
            trim($ok);
        }
        $arraytoreturn = [
            'modtype' => $thistype
            , 'cmid' => substr( $oa, $secondbreak + 1, $thirdbreak - $secondbreak - 1)
            , 'content' => $ok
        ];
        return $arraytoreturn;
    }

    /**
     * This gets used for the quick whole document check.
     *
     * @param string $oa is the string in which to add a string
     */
    private function baseclean($oa) {
        $ok = strip_tags($oa);
        $chars = array("\r\n", "\n", "\r", "\t", "\0", "\x0B", " ");
        $ok = str_replace($chars, "", $ok);
        $ok = preg_replace('/\xc2\xa0/', '', $ok);
        return $ok;
    }

    /**
     * Insert a string into string after a particular string
     *
     * @param string $base is the string in which to add a string
     * @param string $positioning is the string after which $note should be added
     * @param string $note is the string to be inserted
     *
     */
    private function addalert($base, $positioning, $note) {
        $lengthid = strlen($positioning);
        $noteplace = strpos($base, $positioning);
        return substr_replace($base, $note, $noteplace + $lengthid, 0);
    }


    /**
     * Set up a CADO related notification
     *
     * @param string $notifytype is the notification type
     * @param string $url is the contexturl for the message
     * @param int $successful is used to specify whether an approve workflow is successful or not
     * @param array $recipients is used to give the recipient list from a form
     */
    public function workflownotify($notifytype, $url, $successful = 0, $recipients = null) {
        if ($notifytype == 'approve') {
            if ($successful) {
                $subjectline = get_string('approvesubjectline', 'cado');
                $fullmessage = '<p><strong>' . get_string('approvesubjectline', 'cado') . '</strong></p><br>' .
                $this->instance->approvecomment;
            } else {
                $subjectline = get_string('notapprovesubjectline', 'cado');
                $fullmessage = '<p><strong>' . get_string('notapprovesubjectline', 'cado') . '</strong></p><br>' .
                    $this->instance->approvecomment;
            }
            $subjectline = $subjectline . $this->instance->name;
            $userfrom = $this->instance->approveuser;
            $userto = $this->instance->generateuser;
            $fullmessage = '<p><strong>' . get_string('course') . ": " . '</strong>'. $this->course->shortname . "</p><br>" .
            '<p><strong>' . get_string('cado', 'cado') . ": " . '</strong>'. $this->instance->name . "</p><br>" .
                $fullmessage;
            self::notify($userfrom, $userto, $fullmessage, $subjectline, $url);
        }
        if ($notifytype == 'propose') {
            $userfrom = $this->instance->generateuser;
            $userto = $recipients;
            $subjectline = get_string('requestapprovalsubject', 'cado');
            $fullmessage = '<p><strong>' . get_string('course') . ": " . '</strong>'. $this->course->fullname . "</p><br>" .
            '<p><strong>' . get_string('cado', 'cado') . ": " . '</strong>'. $this->instance->name . "</p><br>" .
            get_string('requestapproval', 'cado', $this->course->shortname);
            self::notify($userfrom, $userto, $fullmessage, $subjectline, $url);
        }
        redirect($url);
    }

    /**
     * Send a CADO related notification
     *
     * @param int $userfrom
     * @param int $userto
     * @param string $fullmessage
     * @param string $subjectline
     * @param string $url
     */
    private function notify($userfrom, $userto, $fullmessage, $subjectline, $url) {
        $fullplainmessage = strip_tags($fullmessage, '<p><br>');
        $fullplainmessage = str_ireplace ( "</p>", "\r\n\r\n", $fullplainmessage);
        $fullplainmessage = str_ireplace ( "<p>", "", $fullplainmessage);
        $fullplainmessage = str_ireplace ( "<br>", "\r\n\r\n", $fullplainmessage);

        $message = new \core\message\message();
        $message->component = 'mod_cado';
        $message->name = 'cadoworkflow';
        $message->userfrom = $userfrom;
        $message->userto = $userto;
        $message->subject = $subjectline;
        $message->fullmessage = $fullplainmessage;
        $message->fullmessageformat = FORMAT_PLAIN;
        $message->fullmessagehtml = $fullmessage;
        $message->smallmessage = $subjectline;
        $message->courseid = $this->course->id;

        $message->notification = '1';
        $message->contexturl = $url;
        $message->contexturlname = $this->instance->name;
        $message->set_additional_content('email', $this->instance->generatedpage);
        // For the above see lib/classes/message/message.php.

        message_send($message);

    }
    // For version 2, allow option to send out notification of approval update to all course users.
}
