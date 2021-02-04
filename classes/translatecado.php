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
        error_log("\r\n" . time() . "****** result->schedule*****" . "\r\n" . print_r($result->schedule, true), 3, "d:\moodle_server\server\myroot\mylogs\myerrors.log");
        return json_encode($result,
            JSON_FORCE_OBJECT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK);
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
