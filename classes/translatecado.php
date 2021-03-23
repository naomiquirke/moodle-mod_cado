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
 * @copyright 2021 Naomi Quirke
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

/**
 * Creates generated JSON from HTML record. Used for CADOs created prior to version 3.0.
 *
 * @package   mod_cado
 * @copyright 2021 Naomi Quirke
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_cado_translatecado {
    /** @var object instance of cado */
    public $instance;
    /** @var int groupingid of cado */
    public $groupingid;

    /**
     * Creates a CADO image.
     *
     * @param object $instance CADO database entry
     * @param int $groupingid if known
     */
    public function __construct($instance, $groupingid = null) {
        $this->instance = $instance;
        if ($groupingid !== null) {
            $this->groupingid = $groupingid;
        }
    }

    /**
     * Translates a CADO instance.
     */
    public function translate() {
        global $DB;
        if ($this->groupingid === null) { // Then we are translating a CADO stored in DB, and don't yet have grouping info.
            /*
            It is easier to get grouping name through course module rather than off the html because the grouping name
            string is embedded with other text. Grouping is not something that is changed once a CADO is approved;
            because of access issues it rightfully should be reported as what is in the cm even if the name is now 'incorrect'
            on the CADO with respect to cm, access by particular groups is more important.
            */
            $cm = get_fast_modinfo($this->instance->course)->instances['cado'][$this->instance->id];
            $this->groupingid = $cm->groupingid;
        }
        $result = new stdClass;
        $result->groupingname = $this->groupingid ?
            $DB->get_record('groupings', ['id' => $this->groupingid], 'name')->name : null;

        $thispage = $this->instance->generatedpage;
        $dom = new DOMDocument();
        $dom->loadHTML($thispage);

        $result->fullname = $this->innerxml($dom->getElementById("cad-title")->childNodes->item(1));
        $result->summary = $this->innerxml($dom->getElementById("cado-coursesummary")->childNodes->item(3));
        $result->sitecomment = $this->innerxml($dom->getElementById("cado-sitecomment")->childNodes->item(1));

        // We use the DB version of intro, comment and biblio, rather than picking them up off the HTML.
        // But we need to know if comment and biblio are actually in the CADO.
        $result->biblioexists = $dom->getElementById("cado-biblio")->hasChildNodes();
        $result->commentexists = $dom->getElementById("cado-comment")->hasChildNodes();
        // SCHEDULE **********************************************************************************************.
        $header = $dom->getElementsByTagName("thead");
        $result->scheduleexists = $header->length; // This is zero if no thead.
        if ($result->scheduleexists) {
            $this->get_schedule_head($header, $result);
            $this->get_schedule_body($dom->getElementsByTagName("tbody")->item(0), $result);
        }
        $this->get_modtype($result, $dom, 'quiz');
        $this->get_modtype($result, $dom, 'assign');
        $this->get_modtype($result, $dom, 'forum');
        $this->instance->generatedjson = json_encode($result,
            JSON_FORCE_OBJECT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK);
        $this->instance->timemodified = time();
        $DB->update_record('cado', $this->instance);
        return $this->instance;
    }

    /**
     * Gets module types.
     *
     * @param object $result
     * @param DOMDocument $doc
     * @param string $type is the mod type
     *
     */
    private function get_modtype(&$result, $doc, $type) {
        $modsouter = $doc->getElementById("cado-" . $type);
        $rows = $modsouter->childNodes;
        $existsname = $type . "exists";
        $result->$existsname = $rows->length - 1; // This is zero if no thead.
        if ($result->$existsname) {
            $result->$type = [];
            $mod = $modsouter->getElementsByTagName("h2");
            for ($i = 1; $i < $mod->length; $i++) { // First h2 is a title.
                $returned = $this->getmoditems($mod->item($i), $doc, $type);
                $returned->module = $type;
                $result->{$type}[] = $returned;
            }
        }
    }

    /**
     * Gets module items for type.
     *
     * @param DOMElement $moditem header element of mod being itemized.
     * @param DOMDocument $docouter
     * @param string $type is the mod type
     * @return array
     */
    private function getmoditems($moditem, $docouter, $type) {
        $thismod = new stdClass;
        $nameid = $moditem->attributes->item(0)->nodeValue;
        $cmodid = substr($nameid, strpos($nameid, '_') + 1);
        $thismod->cmodid = $cmodid;
        $thismod->name = $moditem->childNodes->item(0)->nodeValue;
        $thismod->link = $moditem->childNodes->item(1)->attributes->item(1)->nodeValue;
        $thismod->datesexists = false;
        $thismod->completionexists = false;
        $thismod->extraexists = false;
        $thismod->introexists = false;
        $thismod->rubricexists = false;

        // Dates.
        $dates = $docouter->getElementById("cadoi-$type-dates_$cmodid");
        $thismod->dates = [];
        if (is_object($dates)) {
            $rows = $dates->getElementsByTagName("div");
            for ($i = 0; $i < $rows->length; $i += 3) {
                $thismod->dates[] = (object) [
                    'label' => $rows->item($i + 1)->nodeValue,
                    'value' => $rows->item($i + 2)->nodeValue,
                ];
                $thismod->datesexists = true;
            }
        }
        // Completion.
        $completions = $docouter->getElementById("cadoi-$type-completion_$cmodid");
        $thismod->completion = [];
        if (is_object($completions)) {
            $rows = $completions->getElementsByTagName("div");
            for ($i = 0; $i < $rows->length; $i += 3) {
                $thismod->completion[] = (object) [
                    'label' => $rows->item($i + 1)->nodeValue,
                    'value' => $rows->item($i + 2)->nodeValue,
                ];
                $thismod->completionexists = true;
            }
        }
        // Extra: Tags.
        $extra = $docouter->getElementById("cadoi-$type-extra_$cmodid");
        $thismod->extra = [];
        if (is_object($extra)) {
            $rows = $extra->getElementsByTagName("div");
            for ($i = 0; $i < $rows->length; $i += 3) {
                $thismod->extra[] = (object) [
                    'label' => $rows->item($i + 1)->getElementsByTagName('h4')->item(0)->nodeValue,
                    'value' => $rows->item($i + 2)->getElementsByTagName('h4')->item(0)->nodeValue,
                ];
                $thismod->extraexists = true;
            }
        }
        // Intro.
        $intro = $docouter->getElementById("cadoi-$type-intro_$cmodid");
        $thismod->intro = '';
        if (is_object($intro)) {
            // Intro is encased by two text nodes, so don't include first or last childnode.
            for ($i = 1; $i < $intro->childNodes->length - 1; $i++) {
                $thismod->intro .= $this->innerxml($intro->childNodes->item($i));
                $thismod->introexists = true;
            }
        }

        // Rubric.
        $rubric = $docouter->getElementById("cadoi-$type-rubric_$cmodid");
        $thismod->rubric = [];
        if (is_object($rubric)) {
            $thismod->rubricexists = true;
            $rows = $rubric->getElementsByTagName("tr");
            for ($i = 0; $i < $rows->length; $i++) {
                $currentlevel = new stdClass;
                $currentlevel->levels = [];
                $cols = $rows->item($i)->getElementsByTagName("td");
                $currentlevel->critdesc = $cols->item(0)->textContent;
                for ($j = 1; $j < $cols->length; $j++) {
                    $points = $cols->item($j)->childNodes->item(2)->textContent;
                    $currentlevel->levels[] = (object) [
                        'leveldesc' => $cols->item($j)->childNodes->item(0)->textContent,
                        'points' => substr($points, 1, strlen($points) - 2) // Get rid of brackets.
                    ];
                }
                $thismod->rubric[] = $currentlevel;
            }
        }
        return $thismod;
    }

    /**
     * Gets schedule body, taken out just to simplify reading.
     *
     * @param DOMNodeList $schedbody
     * @param stdClass $result
     * @return void
     */
    private function get_schedule_body($schedbody, &$result) {
        $itemadd = $result->weekly ? 1 : 0;
        $result->schedule = [];
        $rows = $schedbody->getElementsByTagName("tr");
        for ($i = 0; $i < $rows->length; $i++) {
            if ($rows->item($i)->parentNode !== $schedbody) {
                continue;
            }
            $schedrow = new stdClass();
            $schedrow->tasks = [];
            $cols = $rows->item($i)->getElementsByTagName("td");

            $schedrow->section = $cols->item(0)->nodeValue;
            $schedrow->startdate = $result->weekly ? $cols->item(1)->nodeValue : null;
            $topicclass = $cols->item(1 + $itemadd)->attributes->item(0)->nodeValue;
            if (!strpos($topicclass, 'total')) { // Doesn't appear, or appears at end of class list.
                $schedrow->sum = 0;
            } else {
                $schedrow->sum = 1;
            }
            $schedrow->name = $cols->item(1 + $itemadd)->nodeValue; // Topic.
            $subtablerows = $cols->item(2 + $itemadd)->getElementsByTagName("tr");
            if ($subtablerows->length < 1) { // No tasks.
                $result->schedule[] = $schedrow;
                continue;
            }
            foreach ($subtablerows as $subtablerow) {
                if (!$subtablerow->childNodes) { // Skip any whitespace.
                    continue;
                }
                $subtablecols = $subtablerow->getElementsByTagName("td");
                $schedrow->tasks[] = (object) [
                    'name' => $subtablecols->item(0)->nodeValue, // Task.
                    'date' => $subtablecols->item(1)->nodeValue, // Datedue.
                    'tag0' => $result->tagsinsched > 0 ? $subtablecols->item(2)->nodeValue : null, // Tag 0.
                    'tag1' => $result->tagsinsched > 1 ? $subtablecols->item(3)->nodeValue : null, // Tag 1.
                    'tag2' => $result->tagsinsched > 2 ? $subtablecols->item(4)->nodeValue : null // Tag 2.
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
     * @return void
     */
    private function get_schedule_head($header, &$result) {
        $row = $header->item(0)->getElementsByTagName("tr");
        $cols = $row->item(0)->getElementsByTagName("th");

        $result->schedheads['section'] = $cols->item(0)->nodeValue;
        // Class of 2nd col.
        $col2class = $cols->item(1)->attributes->item(0)->nodeValue;
        $result->weekly = $col2class == "cado-tc2";
        if ($result->weekly) {
            $result->schedheads['startdate'] = $cols->item(1)->nodeValue; // Week.
            $itemadd = 1;
        } else {
            $result->schedheads['startdate'] = null;
            $itemadd = 0;
        };
        $result->schedheads['name'] = $cols->item(1 + $itemadd)->nodeValue; // Topic.
        // $cols->item(2+ $itemadd) is the column containing the following columns.
        $result->schedheads['task'] = $cols->item(3 + $itemadd)->nodeValue; // Task.
        $result->schedheads['date'] = $cols->item(4 + $itemadd)->nodeValue; // Datedue.
        $result->tagsinsched = ($cols->length - 5 - $itemadd);
        if ($result->tagsinsched > 0) {
            $result->headtag0 = $cols->item(5 + $itemadd)->nodeValue;
            if ($result->tagsinsched > 1) {
                $result->headtag1 = $cols->item(6 + $itemadd)->nodeValue;
                if ($result->tagsinsched > 2) {
                    $result->headtag2 = $cols->item(7 + $itemadd)->nodeValue;
                }
            }
        }
    }

    /**
     * Gets innerXML
     *
     * @param DOMNode $node is the dom node
     * @return string
     */
    private function innerxml($node) {
        if ($node->hasChildNodes()) {
            $doc  = $node->ownerDocument;
            $frag = $doc->createDocumentFragment();
            foreach ($node->childNodes as $child) {
                $frag->appendChild($child->cloneNode(true));
            }
            return $doc->saveXML($frag);
        } else {
            return $node->nodeValue;
        }
    }
}
