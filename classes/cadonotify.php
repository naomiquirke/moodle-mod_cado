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
 * Functions for notifying users re CADO updates
 *
 * @package   mod_cado
 * @copyright 2020 Naomi Quirke
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Class of functions for notifying users re CADO updates
 *
 * @package   mod_cado
 * @copyright 2020 Naomi Quirke
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_cado_cadonotify extends mod_cado_cado {

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
            $this->notify($userfrom, $userto, $fullmessage, $subjectline, $url);
        }
        if ($notifytype == 'propose') {
            $userfrom = $this->instance->generateuser;
            $userto = $recipients;
            $subjectline = get_string('requestapprovalsubject', 'cado');
            $fullmessage = '<p><strong>' . get_string('course') . ": " . '</strong>'. $this->course->fullname . "</p><br>" .
            '<p><strong>' . get_string('cado', 'cado') . ": " . '</strong>'. $this->instance->name . "</p><br>" .
            get_string('requestapproval', 'cado', $this->course->shortname);
            $this->notify($userfrom, $userto, $fullmessage, $subjectline, $url);
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

        message_send($message);

    }
    // For version 2, allow option to send out notification of approval update to all course users.
}
