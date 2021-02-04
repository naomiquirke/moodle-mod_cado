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
 * Functions to translate CADO record from HTML version to JSON version.
 *
 * @package   mod_cado
 * @copyright 2020 Naomi Quirke
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

/**
 * Changes CADO record from HTML version to JSON version.
 *
 * @package   mod_cado
 * @copyright 2020 Naomi Quirke
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_cado_translatecado extends mod_cado_cado{

    /**
     * Translates a CADO instance.
     */
    public function translate() {
        global $DB;
        $result = new stdClass;

        // These can eventually just be put in the data just before output.
        $result->cadobiblio = $this->instance->cadobiblio;
        $result->cadocomment = $this->instance->cadocomment;
        $result->cadointro = $this->instance->cadointro;
        $result->groupingname = $this->groupingid ? $DB->get_record('groupings', array('id' => $this->groupingid), 'name')->name : null;

        // Maybe add to data just before output.
        $result->fullname = $this->course->fullname;

        $origingenerated = $this->instance->generatedpage;
        $dom = new DOMDocument();
        $dom->loadHTML($origingenerated);

        $result->summary = $this->innerxml($dom->getElementById("cado-coursesummary")->childNodes->item(3));
        $result->sitecomment = $this->innerxml($dom->getElementById("cado-sitecomment")->childNodes->item(1));

        // SCHEDULE **********************************************************************************************.
        $header = $dom->getElementsByTagName("thead");
        $result->scheduleexists = $header->length; // This is zero if no thead.
        if ($result->scheduleexists) {
            $this->get_schedule_head($header, $result);
            $this->get_schedule_body($dom->getElementsByTagName("tbody")->item(0)->childNodes, $result);
        }
        // QUIZ **********************************************************************************************.
        $this->get_modtype($result, $dom, 'quiz');
        /*
        $quizouter = $dom->getElementById("cado-quiz");
        $rows = $quizouter->childNodes;
        $result->quizexists = $rows->length - 1; // This is zero if no thead.
        if ($result->quizexists) {
            // Number useful nodes is 5 per module, with whitespace, first is title.
            $result->quiz = [];
            for ($i = 3; $i <= $result->quizexists;) { // $i increments in the function.
                list($i, $result->quiz[]) = $this->get_items($rows, 0, $i);
            }
        }
*/
        // ASSIGN **********************************************************************************************.
        $this->get_modtype($result, $dom, 'assign');

        //error_log("\r\n" . time() . "****** result *****" . "\r\n" . print_r($result, true), 3, "d:\moodle_server\server\myroot\mylogs\myerrors.log");
        return json_encode($result,
            JSON_FORCE_OBJECT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK);
    }

    /**
     * Gets module items.
     *
     * @param stdClass $result
     * @param DOMDocument $doc
     * @param String $type is the mod type
     *
     */
    private function get_modtype(&$result, $doc, $type) {
        $modsouter = $doc->getElementById("cado-" . $type);
        $rows = $modsouter->childNodes;
        $existsname = $type . "exists";
        $result->$existsname = $rows->length - 1; // This is zero if no thead.
        if ($result->$existsname) {
            $result->$type = [];
            for ($i = 3; $i <= $result->$existsname;) { // $i increments in the function.
                error_log("\r\n" . time() . "****** i *****" . "\r\n" . print_r($i, true), 3, "d:\moodle_server\server\myroot\mylogs\myerrors.log");
                if (is_object($rows->item($i)) && is_object($rows->item($i)->attributes->item(0))) {
                    list($i, $returned) = $this->get_items($rows, 0, $i);
                    $result->{$type}[] = $returned;
                } else {
                    $i += 2;
                }
            }
        }
    }

    /**
     * Gets module items.
     *
     * @param DOMNodeList $item
     * @param Boolean $rubric
     * @param Int $num
     *
     */
    private function get_items($items, $rubric, $num) {
        $thismod = new stdClass;
        $nameid = $items->item($num)->attributes->item(0)->nodeValue;
        $thismod->cmodid = substr($nameid, strpos($nameid, '_') + 1);
        $thismod->name = $items->item($num)->childNodes->item(0)->nodeValue;
        $thismod->link = $items->item($num)->childNodes->item(1)->attributes->item(1)->nodeValue;
        $num += 2;
        // Dates.
        $thismod->dates = [];
        $rows = $items->item($num)->childNodes;
        for ($i = 3; $i <= ($rows->length - 1); $i += 2) {
            $thismod->dates[] = (object) [
                'label' => $rows->item($i)->childNodes->item(1)->nodeValue,
                'value' => $rows->item($i)->childNodes->item(3)->nodeValue,
            ];
        }
        $num += 2;
//        error_log("\r\n" . time() . "****** date *****" . "\r\n" . print_r($thismod->dates, true), 3, "d:\moodle_server\server\myroot\mylogs\myerrors.log");
        // Completion.
        if (is_object($items->item($num)) &&
            (strpos($items->item($num)->attributes->item(0)->nodeValue, 'completion') !== false)) {
            $thismod->completion = [];
            $rows = $items->item($num)->childNodes;
            for ($i = 2; $i <= ($rows->length - 1); $i += 2) { // $i starts at 2, not 3 because no whitespace before heading node.
                $thismod->completion[] = (object) [
                    'label' => $rows->item($i)->childNodes->item(1)->nodeValue,
                    'value' => $rows->item($i)->childNodes->item(3)->nodeValue,
                ];
            }
            $num += 2;
//            error_log("\r\n" . time() . "****** comp *****" . "\r\n" . print_r($thismod->completion, true), 3, "d:\moodle_server\server\myroot\mylogs\myerrors.log");
        }
        // Extra: Tags.
        $thismod->extra = [];
        $rows = $items->item($num)->childNodes;
        for ($i = 1; $i <= ($rows->length - 1); $i += 2) { // $i starts at 1, there is no heading.
            $thismod->extra[] = (object) [
                'tagheading' => $rows->item($i)->childNodes->item(1)->childNodes->item(0)->nodeValue,
                'tagcontent' => $rows->item($i)->childNodes->item(3)->childNodes->item(0)->nodeValue,
            ];
        }
//        error_log("\r\n" . time() . "****** extra *****" . "\r\n" . print_r($thismod->extra, true), 3, "d:\moodle_server\server\myroot\mylogs\myerrors.log");
        // Intro.
        $num += 2;
        $thismod->intro = $this->innerxml($items->item($num)->childNodes->item(1));
//        error_log("\r\n" . time() . "****** intro *****" . "\r\n" . print_r($thismod->intro, true), 3, "d:\moodle_server\server\myroot\mylogs\myerrors.log");

        // Rubric.
        $num += 2;
        if (is_object($items->item($num)) && ($items->item($num)->tagName == 'h4')) {
            $num += 2;
            $thismod->rubric = [];
            $rows = $items->item($num)->childNodes->item(1)->childNodes->item(1)->childNodes;
//            error_log("\r\n" . time() . "****** rubric rows *****" . "\r\n" . print_r($rows, true), 3, "d:\moodle_server\server\myroot\mylogs\myerrors.log");
            $currentlevel = new stdClass;
            for ($i = 1; $i <= ($rows->length - 1); $i += 2) {
                $currentlevel = new stdClass;
                $currentlevel->levels = [];
                $cols = $rows->item($i)->childNodes;
                $currentlevel->critdesc = $cols->item(1)->textContent;
                for ($j = 3; $j <= ($cols->length - 1); $j += 2) {
                    $points = $cols->item($j)->childNodes->item(2)->textContent;
                    $currentlevel->levels[] = (object) [
                        'levelsdesc' => $cols->item($j)->childNodes->item(0)->textContent,
                        'points' => substr($points, 1, strlen($points) - 2)
                    ];
                }
                $thismod->rubric[] = $currentlevel;
            }
            $num += 2;
        }
        return [$num, $thismod];
    }

    /**
     * Gets schedule body, taken out just to simplify reading.
     *
     * @param DOMNodeList $schedbody
     * @param stdClass $result
     *
     */
    private function get_schedule_body($schedbody, &$result) {
        $counter = 0;
        $itemadd = $result->weekly ? 2 : 0;
        $result->schedule = [];
        foreach ($schedbody as $row) {
            $counter ++; // White space issue in getting nodes.
            if ($counter % 2 == 1) {
                continue;
            }
            $schedrow = new stdClass();
            $schedrow->tasks = [];
            $schedrow->section = $row->childNodes->item(1)->nodeValue;
            $schedrow->startdate = $result->weekly ? $row->childNodes->item(3)->nodeValue : null;
            $topicclass = $row->childNodes->item(3 + $itemadd)->attributes->item(0)->nodeValue;
            if (!strpos($topicclass, 'total')) { // Doesn't appear, or appears at end of class list.
                $schedrow->sum = 0;
            } else {
                $schedrow->sum = 1;
            }
            $schedrow->name = $row->childNodes->item(3 + $itemadd)->nodeValue; // Topic.
            $subtablerows = $row->childNodes->item(5 + $itemadd)->childNodes->item(1)->childNodes;
            if ($subtablerows->length <= 1) { // No tasks.
                $result->schedule[] = $schedrow;
                continue;
            }
            foreach ($subtablerows as $subtablerow) {
                if (!$subtablerow->childNodes) { // Skip the whitespace.
                    continue;
                }
                $schedrow->tasks[] = (object) [
                    'name' => $subtablerow->childNodes->item(1)->nodeValue, // Task.
                    'date' => $subtablerow->childNodes->item(3)->nodeValue, // Datedue.
                    'tag0' => $result->tagsinsched > 0 ? $subtablerow->childNodes->item(5)->nodeValue : null, // Tag 0.
                    'tag1' => $result->tagsinsched > 1 ? $subtablerow->childNodes->item(7)->nodeValue : null, // Tag 1.
                    'tag2' => $result->tagsinsched > 2 ? $subtablerow->childNodes->item(9)->nodeValue : null // Tag 2.
                ];
            }
            $result->schedule[] = $schedrow;
        }
    }

    /**
     * Gets schedule header, taken out just to simplify reading.
     *
     * @param DOMNodeList $header
     * @param stdClass $result
     *
     */
    private function get_schedule_head($header, &$result) {
        $row = $header->item(0)->childNodes->item(1);
        $result->schedheads['section'] = $row->childNodes->item(1)->nodeValue;
        // Class of 2nd col.
        $col2class = $row->childNodes->item(3)->attributes->item(0)->nodeValue;
        $result->weekly = $col2class == "cado-tc2";
        if ($result->weekly) {
            $result->schedheads['startdate'] = $row->childNodes->item(3)->nodeValue;
            $itemadd = 2;
        } else {
            $result->schedheads['startdate'] = null;
            $itemadd = 0;
        };
        $result->schedheads['name'] = $row->childNodes->item(3 + $itemadd)->nodeValue; // Topic.
        $subtablerow = $row->childNodes->item(5 + $itemadd)->childNodes->item(1)->childNodes->item(1);
        $result->schedheads['task'] = $subtablerow->childNodes->item(1)->nodeValue; // Task.
        $result->schedheads['date'] = $subtablerow->childNodes->item(3)->nodeValue; // Datedue.
        $result->tagsinsched = ($subtablerow->childNodes->length - 3) / 2;
        $result->headtag0 = $result->tagsinsched > 0 ? $subtablerow->childNodes->item(5)->nodeValue : null; // Tag 0.
        $result->headtag1 = $result->tagsinsched > 1 ? $subtablerow->childNodes->item(7)->nodeValue : null; // Tag 1.
        $result->headtag2 = $result->tagsinsched > 2 ? $subtablerow->childNodes->item(9)->nodeValue : null; // Tag 2.
    }

    /**
     * Gets innerXML
     *
     * @param DOMNode $node is the dom node
     *
     */
    private function innerxml($node) {
        $doc  = $node->ownerDocument;
        $frag = $doc->createDocumentFragment();
        foreach ($node->childNodes as $child) {
             $frag->appendChild($child->cloneNode(true));
        }
        return $doc->saveXML($frag);
    }
}
