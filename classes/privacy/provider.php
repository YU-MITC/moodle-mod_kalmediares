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
 * Privacy Subsystem implementation for mod_kalmediaassign.
 *
 * @package   mod_kalmediaassign
 * @copyright (C) 2016-2025 Yamaguchi University <gh-cc@mlex.cc.yamaguchi-u.ac.jp>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_kalmediaassign\privacy;

defined('MOODLE_INTERNAL') || die();

interface kalmediaassign_interface extends
    \core_privacy\local\metadata\provider,
    \core_privacy\local\request\core_userlist_provider,
    \core_privacy\local\request\plugin\provider {
};

use context;
use context_hepler;
use comtext_module;
use stdClass;
use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\helper;
use core_privacy\local\request\transform;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;

/**
 * Privacy Subsystem for mod_kalmediaassign implementing provider.
 *
 * @package   mod_kalmediaassign
 * @copyright (C) 2016-2023 Yamaguchi University <gh-cc@mlex.cc.yamaguchi-u.ac.jp>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements kalmediaassign_interface {

    // To provide php 5.6 (33_STABLE) and up support.
    use \core_privacy\local\legacy_polyfill;

    /**
     * This function returns meta data about this system.
     * @param collection $items - collection object for metadata.
     * @return collection - modified collection object.
     */
    public static function get_metadata($items): collection {
        // Add items to collection.
        $items->add_database_table('kalmediaassign_submission', [
            'mediaassignid' => 'privacy:metadata:kalmediaassign_submission:mediaassignid',
            'userid' => 'privacy:metadata:kalmediaassign_submission:userid',
            'grade' => 'privacy:metadata:kalmediaassign_submission:grade',
            'submissioncomment' => 'privacy:metadata:kalmediaassign_submission:submissioncomment',
            'teacher' => 'privacy:metadata:kalmediaassign_submission:teacher',
            'timemarked' => 'privacy:metadata:kalmediaassign_submission:timemarked',
            'timecreated' => 'privacy:metadata:kalmediaassign_submission:timecreated',
            'timemodified' => 'privacy:metadata:kalmediaassign_submission:timemodified'],
            'privacy:metadata:kalmediaassign_submission');

        return $items;
    }

    /**
     * This function gets the list of contexts that contain user information for the specified user.
     * @param int $userid - The user to search.
     * @return contextlist $contextlist - The list of contexts used in this plugin.
     */
    public static function get_contexts_for_userid($userid): contextlist {
        $sql = "select c.id from {context} c
           inner join {course_modules} cm on cm.id = c.instanceid and c.contextlevel = :contextlevel
           inner join {modules} m on m.id = cm.module and m.name = :modname
           inner join {kalmediaassign} k on k.id = cm.instance
           left join {kalmediaassign_submission} s on s.mediaassignid = k.id
           where s.userid = :submissionuserid";

        $params = array('modname' => 'kalmediaassign',
                        'contextlevel' => CONTEXT_MODULE,
                        'submissionuserid' => $userid);

        $contextlist = new \core_privacy\local\request\contextlist();
        $contextlist->add_from_sql($sql, $params);

        return $contextlist;
    }

    /**
     * Get the list of users within a specific context.
     * @param userlist $userlist - The user list containing the list of users who have data in this context/plugin combination.
     */
    public static function get_users_in_context($userlist) {
        $context = $userlist->get_context();

        if (!is_a($context, \context_module::class)) {
            return;
        }

        $params = ['instanceid' => $context->instanceid,
                   'moudlename' => 'kalmediaassign'];

        $sql = "select s.userid from {course_modules} cm
		join {modules} m on m.id = cm.module and m.name = :modulename
                join {kalmediaassign_submission} s on s.mediaassignid = cm.instance
                where cm.id = :instanceid";
        $userlist->add_from_sql('userid', $sql, $params);
    }

    /**
     * Export all user data for the specified user, in the specified contexts.
     * @param approved_contextlist $contextlist - The approved contexts to export information for.
     */
    public static function export_user_data($contextlist) {
        global $DB;

        $user = $contextlist->get_user();
        foreach ($contextlist->get_contexts() as $context) {
            $cm = $DB->get_record('course_modules', ['id' => $context->instanceid]);
            $instance = $DB->get_record('kalmediaassign', ['id' => $cm->instance]);
            $data = array();
            $params = array('mediaassignid' => $context->instanceid,
                            'userid' => $user->id);
            $submission = $DB->get_records('kalmediaassign_submission', $params);

            $params = array('id' => $context->instanceid);
            $assign = $DB->get_record('kalmediaassign', $params);

            if (!empty($submission) && !empty($assign)) {
                $submissiondata = (object) [
                    'name' => format_string($assign->name, true),
                    'grade' => $submission->grade,
                    'submssioncomment' => format_string($submission->submissioncomment, true),
                    'timecreated' => transform::datetime($submission->timecreated),
                    'timemodified' => transform::datetime($submission->timemodified),
                    'timemarked' => transform::datetime($submission->timemarked)];
                $data[$submission->id] = $submissiondata;
                $instance->export_data(null, $data);
            }
        }
    }

    /**
     * Delete all data for all users in the specified context.
     * @param context $context - The specific context to delete data for.
     */
    public static function delete_data_for_all_users_in_context($context) {
        global $DB;

        // Check that this is a context_module.
        if (!$context instanceof \context_module) {
            return;
        }

        // Get the course module.
        if (!$cm = get_coursemodule_from_id('kalmediaassign', $context->instanceid)) {
            return;
        }

        $assignid = $cm->instance;

        $DB->delete_records('kalemdiaassign_submission', ['mediaassignid' => $assignid]);
    }

    /**
     * Delete all user data for the specified user, in the specified contexts.
     * @param approved_contextlist $contextlist -The approved contexts and user information to delete information for.
     */
    public static function delete_data_for_user($contextlist) {
        global $DB;

        $user = $contextlist->get_user();
        $userid = $user->id;
        foreach ($contextlist as $context) {
            // Get the course module.
            $cm = $DB->get_record('course_modules', ['id' => $context->instanceid]);
            $DB->delete_records('kalmediaassign_submission',
                                ['mediaassignid' => $cm->instance,
                                 'userid' => $userid]);
        }
    }

    /**
     * Delete multiple users within a single context.
     * @param approved_userlist $userlist - The approved context and user information to delete information for.
     */
    public static function delete_data_for_users($userlist) {
        global $DB;

        $context = $userlist->get_context();
        $cm = $DB->get_record('course_modules', ['id' => $context->instanceid]);
        $assign = $DB->get_record('kalmediaassign', ['id' => $cm->instance]);

        list($userinsql, $userinparams) = $DB->get_in_or_equal($userlist->get_userids(), SQL_PARAMS_NAMED);
        $params = array_merge(['assignid' => $assign->id], $userinparams);
        $sql = "mediaassignid = :assignid and userid {$userinsql}";

        $DB->delete_records_select('kalmediaassign_submission', $sql, $params);
    }
}
