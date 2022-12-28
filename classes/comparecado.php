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

/**
 * Compares two CADO reports
 *
 * @package   mod_cado
 * @copyright 2020 Naomi Quirke
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_cado_comparecado {
    /** @var array report output to cado template */
    public $newjson;

    /**
     * Compares two CADO instances
     *
     * @param object $origincado is the origin cado instance
     * @param integer $otherid is cmid for other cado
     * @return object for rendering using cadocompare.mustache.
     */
    public function compare($origincado, $otherid) {
        global $DB;
        $result = [];
        $othercmod = $DB->get_record('course_modules', ['id' => $otherid])->instance;
        $othercado = $DB->get_record('cado', ['id' => $othercmod]);
        if (empty($othercado->generatedjson)) {
            // Then CADO must have been generated prior to version 3.0 upgrade, so needs to be translated to JSON.
            $newothercado = new mod_cado_translatecado($othercado);
            $othercado = $newothercado->translate();
        }
        $otherjson = json_decode($othercado->generatedjson, true);
        $originjson = json_decode($origincado->generatedjson, true);
        // Copy of origin JSON.  Will be adding normal DB fields and compare classes to it.
        $this->newjson = json_decode($origincado->generatedjson, true);
        $this->newjson['cadointro'] = $origincado->cadointro;
        $this->newjson['cadobiblio'] = $origincado->cadobiblio;
        $this->newjson['cadocomment'] = $origincado->cadocomment;

        $result["compareheaderorigin"] = get_string('compareheaderorigin', 'cado', $origincado->name);
        $result["compareheaderother"] = get_string('compareheaderother', 'cado', $othercado->name);
        $result["commentdiff"] = (strcasecmp($othercado->approvecomment, $origincado->approvecomment) != 0);

        $allmatched = true;
        // First, the DB items.  These must be brought from the DB record for compare.
        $allmatched = $this->applytopdiff($origincado->cadointro, $othercado->cadointro, 'di', 'cadointro'
            ) && $allmatched;
        // With the next two DB items, they may be present in DB, but may also not be displayed due to site settings at gen time.
        $items = ['comment', 'biblio'];
        foreach ($items as $item) {
            $descriptor = 'd' . substr($item, 0, 1);
            $fieldname = 'cado' . $item;
            $exists = $item . 'exists';
            $first = $originjson[$exists] ? $origincado->$fieldname : '';
            $second = $otherjson[$exists] ? $othercado->$fieldname : '';

            $allmatched = $this->applytopdiff($first, $second, $descriptor, $fieldname
                ) && $allmatched;
        }
        // Next the top level items.
        $topitems = [['groupingname', 'dg'], ['summary', 'ds'], ['sitecomment', 'dsc']];
        foreach ($topitems as $topitem) {
            $first = $originjson[$topitem[0]] ?? '';
            $second = $otherjson[$topitem[0]] ?? '';
            $allmatched = $this->applytopdiff($first, $second, $topitem[1], $topitem[0]) && $allmatched;
        }

        // New we need to do the modules.
        $mods = ['forum', 'quiz', 'assign'];
        $allmatched = $this->exists_check($mods, "d", $originjson, $otherjson, "compare_type")
            && $allmatched;

        // Make an overall statement.
        if ($allmatched) {
            $result["subheader"] = get_string('comparisonidentical', 'cado');
        } else {
            $result["subheader"] = get_string('comparisondifferent', 'cado');
        }
        $result["content"] = $this->newjson;
        return $result;
    }

    /**
     * To get whether sections exist between origin and other cado element, and if they do compare them.
     *
     * @param array $items elements to check.
     * @param string $prefix combined with element identifier used for moustache template to add the appropriate class.
     * @param array $ori is origin element.
     * @param array $oth is other element.
     * @param string $inner is the inner function to run when the elements both exist. Either: compare_type or...
     * @param string $section is the output section of result json.
     * @param string $index is the output index of result json.
     * @return boolean
     */
    private function exists_check($items, $prefix, $ori, $oth, $inner, $section = null, $index = null) {
        $itemmatch = true;
        if ($section === null) {
            $result = &$this->newjson;
        } else {
            $result = &$this->newjson[$section][$index];
        }
        foreach ($items as $itemtype) {
            $descriptor = $prefix . substr($itemtype, 0, 1);
            $exists = $itemtype . 'exists';
            $ori[$exists] = isset($ori[$exists]) ? $ori[$exists] : false;
            $oth[$exists] = isset($oth[$exists]) ? $oth[$exists] : false;
            // Re the existence of the itemtype, just apply same logic as applydiff function.
            if ((!$ori[$exists]) && (!$oth[$exists])) {
                continue;
            } else if (!$oth[$exists]) {
                $result[$descriptor] = "cado-othermissing";
                $itemmatch = false;
            } else if (!$ori[$exists]) {
                $result[$descriptor] = "cado-originmissing";
                $result[$exists] = 1;
                $result[$itemtype] = $oth[$itemtype];
                $itemmatch = false;
            } else {
                $params = [$itemtype, $ori, $oth, $section, $index];
                // Must do a matrix compare, because we don't want to just compare module ids because of backup restores.
                $temp = call_user_func_array([$this, $inner], [&$params]);
                $itemmatch = $temp && $itemmatch;
            }
        }
        return $itemmatch;
    }

    /**
     * To find the differences between origin and other at the first level of module.
     * Origin & other arrays will be changed in the process, as every time we find a match or identify a difference
     * from the origin, we will note in result, and add a note in the match arrays.
     *
     * @param array $params contains:
     *    string $type is the mod type.
     *    array $origin is the original cado report json
     *    array $other is the compared cado report json.
     * At the first level we don't use the final two parameters available in $params
     * @return boolean which says if everything matched.
     */
    private function compare_type(&$params) {
        list($type, $origin, $other) = $params;
        $matched = true;
        // Check all first for cmid associations, only then for name associations.
        // Above necessary because cmid is to be checked with highest priority for all.
        foreach (["cmodid", "name"] as $matchtype) {
            foreach ($origin[$type] as $orimodindex => &$orimod) {
                // Ignore origins that have already been matched.
                if (isset($orimod["done"])) {
                    continue;
                }
                foreach ($other[$type] as $othmodindex => &$othmod) {
                    // Ignore others that have already been matched.
                    if (isset($othmod["done"])) {
                        continue;
                    }
                    // Find association.
                    if ($orimod[$matchtype] === $othmod[$matchtype]) {
                        // Check for partial differences, no exact match search since doesn't add efficiency.
                        // Then add change record into the final json.
                        if ($matchtype == "cmodid") {
                            // Name difference, relevant only when matching cmodid.
                            $matched = $this->applydiff($orimod["name"], $othmod["name"], "dmn"
                                , "name", null, null, $this->newjson[$type][$orimodindex]) && $matched;
                        }
                        $matched = $this->applydiff($orimod["intro"], $othmod["intro"], "dmi"
                            , "intro", null, null, $this->newjson[$type][$orimodindex]) && $matched;
                        $matched = $this->exists_check(["dates", "completion", "extra"], "dms", $orimod, $othmod
                            , "compare_inner", $type, $orimodindex) && $matched;
                        $matched = $this->exists_check(["rubric"], "dm", $orimod, $othmod
                            , "compare_rubric", $type, $orimodindex) && $matched;

                        // Now make note of the two matching modules, so they don't get matched again (eg in case of duplicates).
                        $orimod["done"] = $matchtype . ' ' . $orimodindex;
                        $othmod["done"] = $matchtype . ' ' . $othmodindex;
                        // Now break the inner 'other' loop, because we don't want to trigger the not found code @ foreach end.
                        continue 2;
                    }
                }
            }
        }
        // Anything not marked done is missing from one of the records.
        return $this->findgaps($origin[$type], $other[$type], $this->newjson[$type], "dm")
            && $matched;
    }

    /**
     * To find the differences between origin and other for rubric and update classes on result.
     *
     * @param array $params contains:
     *    string $type is the section type.
     *    array $origininner is the original cado report json.
     *    array $otherinner is the compared cado report json.
     *    string $section is the output section of result json.
     *    string $index is the output index of result json.
     * @return boolean which says if everything matched.
     */
    private function compare_rubric(&$params) {
        list($type, $origininner, $otherinner, $section, $index) = $params;
        $matchedinner = true;
        $finalinner = &$this->newjson[$section][$index][$type];
        foreach ($origininner[$type] as $oricritkey => &$oricriterion) {
            foreach ($otherinner[$type] as &$othcriterion) {
                // Ignore others that have already been matched.
                if (isset($othcriterion["done"])) {
                    continue;
                }
                if ($oricriterion["critdesc"] === $othcriterion["critdesc"]) {
                    // Just do a straight comparison of all criterion levels, nothing fancy.
                    $orilevelpack = json_encode($oricriterion["levels"]);
                    $othlevelpack = json_encode($othcriterion["levels"]);
                    if ($orilevelpack !== $othlevelpack) {
                        $finalinner[$oricritkey]["dmrl"] = "cado-different";
                    }
                    // Now mark each side of match as done so can't be selected again.
                    $oricriterion["done"] = true;
                    $othcriterion["done"] = true;
                    continue 2;
                }
            }
        }
        // Anything not marked done is missing from one of the records.
        $temp = $this->findgaps($origininner[$type], $otherinner[$type], $finalinner, "dmrc");
        return $temp && $matchedinner;
    }

    /**
     * To find the differences between origin and other for dates, completion and tags and update classes on result.
     *
     * @param array $params contains:
     *    string $type is the section type.
     *    array $origininner is the original cado report json.
     *    array $otherinner is the compared cado report json.
     *    string $section is the output section of result json.
     *    string $index is the output index of result json.
     * @return boolean which says if everything matched.
     */
    private function compare_inner(&$params) {
        list($type, $origininner, $otherinner, $section, $index) = $params;
        $matchedinner = true;
        $finalinner = &$this->newjson[$section][$index][$type];
        foreach ($origininner[$type] as $orikey1 => &$orimod) {
            foreach ($otherinner[$type] as &$othmod) {
                // Ignore others that have already been matched.
                if (isset($othmod["done"])) {
                    continue;
                }
                // Find label association.
                if ($orimod["label"] === $othmod["label"]) {
                    $matchedinner = $this->applydiff($orimod["value"], $othmod["value"], "dmil"
                        , $orikey1, "value", $othmod, $finalinner) && $matchedinner;
                    $orimod["done"] = true;
                    $othmod["done"] = true;
                    // Now break the inner 'other' loop, because we don't want to trigger the not found code @ foreach end.
                    continue 2;
                }
            }
        }
        // Anything not marked done is missing from one of the records.
        return $this->findgaps($origininner[$type], $otherinner[$type], $finalinner, "dml")
            && $matchedinner;
    }

    /**
     * To update missing element differences between origin and other after the matching is done.
     * @param array $ori origin element.
     * @param array $oth other element.
     * @param array $diffresult resulting element.
     * @param string $difflabel label for style element.
     * @return boolean whether matched or not.
     */
    private function findgaps($ori, $oth, &$diffresult, $difflabel) {
        $matchedinner = true;
        // Anything not marked done is missing from one of the records.
        foreach ($ori as $key1 => &$element1) {
            if (isset($element1["done"])) {
                continue;
            }
            $diffresult[$key1][$difflabel] = "cado-othermissing";
            $matchedinner = false;
        }
        // Then find missing origins.
        foreach ($oth as &$element2) {
            if (isset($element2["done"])) {
                continue;
            }
            $element2[$difflabel] = "cado-originmissing";
            $diffresult[] = $element2;
            $matchedinner = false;
        }
        return $matchedinner;
    }

    /**
     * To get string differences between origin and other cado element.
     *
     * @param string $a is the string from the origin cado
     * @param string $b is the string from the other cado
     * @param string $diffdescriptor is the object name / moustache tag to add if required
     * @param string $childelement is the compared element
     * @param string $newelement is empty if we are not using indices, otherwise element index of $a.
     * @param array $otherelement is empty if not using indices, otherwise the entire record for the new element.
     * @param array $resultelement is the array at the parent element level to add to if required
     * @return boolean whether matched or not.
     */
    private function applydiff($a, $b, $diffdescriptor, $childelement, $newelement, $otherelement, &$resultelement) {
        $strippeda = trim(strip_tags($a));
        $strippedb = trim(strip_tags($b));
        if (empty($strippeda) && empty($strippedb)) {
            return true;
        } else if (empty($strippeda)) {
            if (!$newelement) {
                $resultelement[$childelement] = $b;
                $resultelement[$diffdescriptor] = "cado-originmissing";
            } else {
                $resultelement[$childelement][$newelement] = $otherelement;
                $resultelement[$childelement][$diffdescriptor] = "cado-originmissing";
            }
        } else if (empty($strippedb)) {
            if (!$newelement) {
                $resultelement[$diffdescriptor] = "cado-othermissing";
            } else {
                $resultelement[$childelement][$diffdescriptor] = "cado-othermissing";
            }
        } else if ($strippeda !== $strippedb) {
            if (!$newelement) {
                $resultelement[$diffdescriptor] = "cado-different";
                // Insert marker at point where difference occurs. This is only of significant use in paragraphs.
                $newa = $this->get_diff_pt($a, $b);
                $resultelement[$childelement] = $newa;
            } else {
                $resultelement[$childelement][$diffdescriptor] = "cado-different";
            }
        } else {
            return true;
        }
        return false;
    }

    /**
     * To get string differences between origin and other cado element.
     *
     * @param string $a is the string from the origin cado
     * @param string $b is the string from the other cado
     * @param string $diffdescriptor is the object name / moustache tag to add if required
     * @param string $childelement is the compared element
     * @return boolean whether matched or not.
     */
    private function applytopdiff($a, $b, $diffdescriptor, $childelement) {
        $strippeda = trim(strip_tags($a));
        $strippedb = trim(strip_tags($b));
        if (empty($strippeda) && empty($strippedb)) {
            return true;
        } else if (empty($strippeda)) {
            $this->newjson[$childelement] = $b;
            $this->newjson[$diffdescriptor] = "cado-originmissing";
        } else if (empty($strippedb)) {
            $this->newjson[$diffdescriptor] = "cado-othermissing";
        } else if ($strippeda !== $strippedb) {
            $this->newjson[$diffdescriptor] = "cado-different";
            // Insert marker at point where difference occurs. This is only of significant use in paragraphs.
            $newa = $this->get_diff_pt($a, $b);
            $this->newjson[$childelement] = $newa;
        } else {
            return true;
        }
        return false;
    }

    /**
     * To put a marker in at the difference point in two texts, $a and $b.
     *
     * @param string $a
     * @param string $b
     * @return string updated $a.
     */
    private function get_diff_pt($a, $b) {

        $sectiontext1 = preg_split('/<[^>]*>/', $a, -1, PREG_SPLIT_OFFSET_CAPTURE);
        $sectiontext2 = preg_split('/<[^>]*>/', $b, -1, PREG_SPLIT_OFFSET_CAPTURE);
        for ($i = 0; ($i < count($sectiontext1)) && ($i < count($sectiontext2)); $i++) {
            if ($sectiontext1[$i][0] == $sectiontext2[$i][0]) {
                continue;
            }
            $chars1 = preg_split('/ /', $sectiontext1[$i][0], -1, PREG_SPLIT_OFFSET_CAPTURE);
            $chars2 = preg_split('/ /', $sectiontext2[$i][0], -1, PREG_SPLIT_OFFSET_CAPTURE);
            for ($j = 0; ($j < count($chars1)) && ($j < count($chars2)); $j++) {
                if ($chars1[$j][0] == $chars2[$j][0]) {
                    continue;
                }
                break 2;
            }
            break;
        }
        $difference = $sectiontext1[$i][1] + $chars1[$j][1];
        return substr_replace($a, "\u{2198}", $difference, 0);
    }

}
