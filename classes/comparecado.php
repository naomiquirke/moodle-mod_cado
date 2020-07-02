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
 * Functions to compare two CADO reports
 *
 * @package   mod_cado
 * @copyright 2020 Naomi Quirke
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

/**
 * Compares two CADO reports
 *
 * @package   mod_cado
 * @copyright 2020 Naomi Quirke
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_cado_comparecado {

    /**
     * Compares two CADO instances
     *
     * @param object $origincado is the origin cado instance
     * @param integer $otherid is cmid for other cado
     * @return object for rendering using cadocompare.mustache.
     */
    public function compare($origincado, $otherid) {
        global $DB;
        $result = new stdClass;
        $othercmod = $DB->get_record('course_modules', ['id' => $otherid])->instance;
        $othercado = mod_cado_cado::getcadorecord($othercmod);
        $othergenerated = $othercado->generatedpage;
        $origingenerated = $origincado->generatedpage;
        $result->compareheaderorigin = get_string('compareheaderorigin', 'cado', $origincado->name);
        $result->compareheaderother = get_string('compareheaderother', 'cado', $othercado->name);
        $result->commentdiff = (strcasecmp($othercado->approvecomment, $origincado->approvecomment) != 0);

        // Check to see if not all identical.  Complete identicality will only occur in the same course,
        // otherwise the cmids will be different. Note we don't care about case, otherwise use strcmp.
        if (strcasecmp($origingenerated, $othergenerated) != 0) {
            list($allmatched, $updated) = $this->finddiff($origingenerated, $othergenerated);
        } else { // Matched and in the same course.
            $allmatched = true;
        }
        if ($allmatched) {
            $result->subheader = get_string('comparisonidentical', 'cado');
            $result->content = $origingenerated;
        } else {
            $result->subheader = get_string('comparisondifferent', 'cado');
            $result->content = $updated;
        }
        return $result;
    }

    /**
     * To find the differences between origin and othe
     *
     * @param string $origingenerated is the central part of origin cado
     * @param string $othergenerated is the central part of other cado
     * @return array [boolean $allmatched, string $updated the compared central part of cado with format updates]
     */
    private function finddiff($origingenerated, $othergenerated) {
        $allmatched = true; //Assume all matching until proven not.
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
            $a1 = $this->baseclean($origincontent);
            $a2 = $this->baseclean($othercontent);
            $result = strcasecmp( $a1 , $a2 );
            if ($result != 0) {
                if ( in_array($value, ['forum', 'quiz', 'assign'])) {
                    $origininnerarray = explode('id="cadoi-', $origincontent);
                    $ori1 = array_map('self::cleanline', $origininnerarray);
                    $otherinnerarray = explode('id="cadoi-', $othercontent);
                    $oth1 = array_map('self::cleanline', $otherinnerarray);

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
                                        $originarray[$splitcount] = $this->addalert($originarray[$splitcount], $idname, $class);
                                        $allmatched = false;
                                    } else if (strcmp($orival2 , $othval1[$orikey2]) != 0) {
                                        // Mark the descriptor as different.
                                        $class = ' class="cado-different"';
                                        $idname = $value . '-' . $orikey2 . '_' . $orikey1 . '"';
                                        $originarray[$splitcount] = $this->addalert($originarray[$splitcount], $idname, $class);
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
                        $originarray[$splitcount] = $this->addalert($originarray[$splitcount], $idname, $class);
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
        return [$allmatched, $updated];
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



}
