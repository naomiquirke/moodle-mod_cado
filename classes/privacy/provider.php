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
 * Privacy implementation for mod_cado.
 *
 * @package   mod_cado
 * @copyright 2020 Naomi Quirke
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_cado\privacy;

use context_module;
use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\userlist;
use core_privacy\local\request\transform;
use core_privacy\local\request\writer;


/**
 * Class for privacy implementation for mod_cado.
 *
 * @package   mod_cado
 * @copyright 2020 Naomi Quirke
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements {
    // This plugin stores data entered by user with role generator,
    // and comments made by user with role approver to user with CADO generator.
    \core_privacy\local\metadata\provider,
    \core_privacy\local\request\plugin\provider,
    \core_privacy\local\request\core_userlist_provider
 

    /**
     * Return meta data about this plugin.
     *
     * @param  collection $collection A list of information to add to.
     * @return collection Return the collection after adding to it.
     */
    public static function get_metadata(collection $collection) : collection {
        $data = [
            'approveuser' => 'privacy:metadata:approverpurpose',
            'name' => 'privacy:metadata:cadoname',
            'approvecomment' => 'privacy:metadata:commentpurpose',
            'generateuser' => 'privacy:metadata:generatorpurpose',
            'timeapproved'  => 'privacy:metadata:approvetimepurpose',
            'timemodified'  => 'privacy:metadata:modifiedtimepurpose',
        ];
        $collection->add_database_table('cado', $data, 'privacy:metadata:tablesummary');

        return $collection;
    }

    /**
     * Get the list of contexts that contain user information for the specified user.
     *
     * @param int $userid The user to search.
     * @return contextlist $contextlist The contextlist containing the list of contexts used in this plugin.
     */
    public static function get_contexts_for_userid(int $userid) : contextlist {
        $contextlist = new \core_privacy\local\request\contextlist();
        $sql = "SELECT c.id
                FROM {context} c
                  INNER JOIN {course_modules} cm ON cm.id = c.instanceid AND c.contextlevel = :contextlevel
                  INNER JOIN {modules} m ON m.id = cm.module AND m.name = :modname
                  INNER JOIN {cado} cad ON cad.id = cm.instance
                WHERE (cad.generateuser = :thisgenuser) OR (cad.approveuser = :thisappruser)
                ";
        $params = [
            'modname'      => 'cado',
            'contextlevel' => CONTEXT_MODULE,
            'thisgenuser'  => $userid,
            'thisappruser' => $userid,
        ];

        $contextlist->add_from_sql($sql, $params);

        return $contextlist;

    }

    /**
     * Get the list of users who have data within a context.
     *
     * @param   userlist    $userlist   The userlist containing the list of users who have data in this context/plugin combination.
     *
     */
    public static function get_users_in_context(userlist $userlist) {
        $context = $userlist->get_context();

        if (!is_a($context, \context_module::class)) {
            return;
        }

        $sql = "WITH part1 as ( " .
                "SELECT cm.instance
                FROM {context} c
                    JOIN {course_modules} cm ON cm.id = c.instanceid AND c.contextlevel = :contextlevel
                    JOIN {modules} m ON m.id = cm.module AND m.name = :modname
                WHERE c.id = :contextid
                )

                , part2 as (
                SELECT cad.approveuser userid
                FROM  part1
                    JOIN {cado} cad ON cad.id = part1.instance
                WHERE cad.approveuser is not null
                )

                , part3 as (
                SELECT cad.generateuser userid
                FROM  part1
                    JOIN {cado} cad ON cad.id = part1.instance
                )

                SELECT userid
                FROM  part2
                UNION
                SELECT userid
                FROM  part3";

        $params = [
            'contextid' => $context->id,
            'contextlevel' => CONTEXT_MODULE,
            'modname' => 'cado',
        ];

        $userlist->add_from_sql('userid', $sql, $params);
    }


    /**
     * Export all user data for the specified user, in the specified contexts.
     *
     * @param approved_contextlist $contextlist The approved contexts to export information for.
     */
    public static function export_user_data(approved_contextlist $contextlist) {
        // Approved_contextlist includes both the user record, and a list of contexts, which can be retrieved by either
        // processing it as an Iterator, or by calling get_contextids() or get_contexts() as required.
        // Data is exported using a \core_privacy\local\request\content_writer.
        global $DB;

        $user = $contextlist->get_user();
        $userid = $user->id;
        $cmids = array_reduce($contextlist->get_contexts(), function($carry, $context) {
            if ($context->contextlevel == CONTEXT_MODULE) {
                $carry[] = $context->instanceid;
            }
            return $carry;
        }, []);
        if (empty($cmids)) {
            return;
        }

        // Find the cado IDs.
        $cadoidstocmids = static::get_cado_ids_to_cmids_from_cmids($cmids);
        $cadoids = array_keys($cadoidstocmids);

        // Get the data.
        list($insql, $inparams) = $DB->get_in_or_equal($cadoids, SQL_PARAMS_NAMED);
        $sqlwhere = "(generateuser = :generateuser OR approveuser = :approveuser) AND id $insql";
        $params = array_merge($inparams, ['generateuser' => $userid, 'approveuser' => $userid]);
        $sql = "SELECT *
                FROM {cado}
                WHERE $sqlwhere";
        $recordset = $DB->get_records_sql($sql, $params);
        // Export the data.
        foreach ($recordset as $record) {
            $data = (object) [
                'name' => $record->name,
                'generateuser' => $record->generateuser,
                'approveuser' => isset($record->approveuser) ? $record->approveuser : get_string('privacy:nothing', 'cado'),
                'approvecomment' => isset($record->approvecomment) ?
                    get_string('privacy:nothing', 'cado') : $record->approvecomment,
                'timeapproved' =>
                    $record->timeapproved == 0 ? get_string('privacy:nothing', 'cado') : transform::datetime($record->timeapproved),
                'timemodified' => transform::datetime($record->timemodified)
            ];
            $context = context_module::instance($cadoidstocmids[$record->id]);
            writer::with_context($context)->export_data([], $data);
        }
    }

    /**
     * Delete all data for all users in the specified context.
     *
     * @param context $context The specific context to delete data for.
     */
    public static function delete_data_for_all_users_in_context(\context $context) {

        if ($context->contextlevel != CONTEXT_MODULE) {
            return;
        }
        $cadoid = static::get_cado_id_from_context($context);
        if (!$cadoid) {
            return;
        }
        static::delete_data(0, $cadoid);
    }

    /**
     * Delete all user data for the specified user, in the specified contexts.
     *
     * @param approved_contextlist $contextlist The approved contexts and user information to delete information for.
     */
    public static function delete_data_for_user(approved_contextlist $contextlist) {
        $userid = $contextlist->get_user()->id;
        $cmids = array_reduce($contextlist->get_contexts(), function($carry, $context) {
            if ($context->contextlevel == CONTEXT_MODULE) {
                $carry[] = $context->instanceid;
            }
            return $carry;
        }, []);
        if (empty($cmids)) {
            return;
        }

        // Find the cado IDs.
        $cadoidstocmids = static::get_cado_ids_to_cmids_from_cmids($cmids);
        $cadoids = array_keys($cadoidstocmids);
        if (empty($cadoids)) {
            return;
        }
        foreach ($cadoids as $cadoid) {
            static::delete_data($userid, $cadoid);
        }
    }

    /**
     * Delete multiple users within a single context.
     *
     * @param approved_userlist $userlist The approved context and user information to delete information for.
     */
    public static function delete_data_for_users(approved_userlist $userlist) {
        $context = $userlist->get_context();
        $cadoid = static::get_cado_id_from_context($context);
        $userids = $userlist->get_userids();

        if (empty($cadoid)) {
            return;
        }
        foreach ($userids as $userid) {
            static::delete_data($userid, $cadoid);
        }
    }

    /**
     * Return cado IDs mapped to their course module ID.
     *
     * @param array $cmids The course module IDs.
     * @return array In the form of [$cadoid => $cmid].
     */
    protected static function get_cado_ids_to_cmids_from_cmids(array $cmids) {
        global $DB;
        list($insql, $inparams) = $DB->get_in_or_equal($cmids, SQL_PARAMS_NAMED);
        $sql = "
            SELECT c.id, cm.id cmid
              FROM {cado} c
              JOIN {modules} m
                ON m.name = :cado
              JOIN {course_modules} cm
                ON cm.instance = c.id
               AND cm.module = m.id
             WHERE cm.id $insql";
        $params = array_merge($inparams, ['cado' => 'cado']);
        return $DB->get_records_sql_menu($sql, $params);
    }

    /**
     * Get a cado ID from its context.
     *
     * @param context_module $context The module context.
     * @return int either the cado ID or 0 if not found.
     */
    protected static function get_cado_id_from_context(context_module $context) {
        $cm = get_coursemodule_from_id('cado', $context->instanceid);
        return $cm ? (int) $cm->instance : 0;
    }

    /**
     * Delete data related to a userid and cado id
     *
     * @param  int $userid The user ID
     * @param  int $cadoid The cado ID
     */
    protected static function delete_data($userid, $cadoid) {
        global $DB;
        // CADO Reports are considered to be 'owned' by the institution, even if they were originally written by a specific
        // user. They are still exported in the list of a users data, but they are not removed.
        // The relevant user is instead anonymised, and any name removed from the autoinclusion in the approve-comment.

        // If $userid 0, then all records matching the cadoid are affected;
        // If $cadoid 0 then all matching useride affected.
        // There is no call where both are 0.
        $cadoparam = ['id' => $cadoid];  // If cadoid is zero then this is disregarded.
        $approveparam = $userid ? ['approveuser' => $userid] : [];
        $generateparam = $userid ? ['generateuser' => $userid] : [];
        $params = $cadoid ? array_merge($cadoparam, $approveparam) : $approveparam;
        $recordset = $DB->get_records('cado', $params, '', 'id, approveuser, approvecomment');
        foreach ($recordset as $record) {
            // String calc is done inside loop because may only have cadoid outside loop.
            $thisuser = fullname($DB->get_record('user', ['id' => $record->approveuser]));
            $record->approvecomment = str_replace($thisuser,
                get_string('useranonymous', 'cado'), $record->approvecomment);
            $record->approveuser = 0;
            $DB->update_record('cado', $record, true);
        }

        $params = $cadoid ? array_merge($cadoparam, $generateparam) : $generateparam;
        $DB->set_field('cado', 'generateuser', 0, $params);
    }

}
