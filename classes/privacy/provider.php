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
 * Privacy Subsystem implementation for mod_kalmediares.
 *
 * @package   mod_kalmediares
 * @copyright (C) 2016-2023 Yamaguchi University <gh-cc@mlex.cc.yamaguchi-u.ac.jp>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_kalmediares\privacy;

interface kalmediares_interface extends
    \core_privacy\local\metadata\provider,
    \core_privacy\local\request\core_userlist_provider,
    \core_privacy\local\request\plugin\provider {
};

use context;
use context_helper;
use context_module;
use stdClass;
use \core_privacy\local\metadata\collection;
use \core_privacy\local\request\approved_contextlist;
use \core_privacy\local\request\approved_userlist;
use \core_privacy\local\request\contextlist;
use \core_privacy\local\request\helper;
use \core_privacy\local\request\transform;
use \core_privacy\local\request\userlist;
use \core_privacy\local\request\writer;

/**
 * Privacy Subsystem for mod_kalmediares implementing provider.
 *
 * @package   mod_kalmediares
 * @copyright (C) 2016-2023 Yamaguchi University <gh-cc@mlex.cc.yamaguchi-u.ac.jp>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements kalmediares_interface {

    // To provide php 5.6 (33_STABLE) and up support.
    use \core_privacy\local\legacy_polyfill;

    /**
     * This function returns meta data about this system.
     * @param collection $items - collection object for metadata.
     * @return collection - modified collection object.
     */
    public static function get_metadata($items): collection {
        // Add items to collection.
        $items->add_database_table('kalmediares_log', [
            'userid' => 'privacy:metadata:kalmediares_log:userid',
            'instanceid' => 'privacy:metadata:kalmediares_log:instanceid',
            'plays' => 'privacy:metadata:kalmediares_log:plays',
            'views' => 'privacy:metadata:kalmediares_log:views',
            'first' => 'privacy:metadata:kalmediares_log:first',
            'last' => 'privacy:metadata:kalmediares_log:last'
            ], 'privacy:metadata:kalmediares_log');

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
           inner join {kalmediares} k on k.id = cm.instance
           left join {kalmediares_log} l on l.instanceid = k.id
           where l.userid = :loguserid";

        $params = array('modname' => 'kalmediares',
                        'contextlevel' => CONTEXT_MODULE,
                        'loguserid' => $userid);

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
                   'moudlename' => 'kalmediares'];

        $sql = "select l.userid from {course_modules} cm
		join {modules} m on m.id = cm.module and m.name = :modulename
		join {kalmediares_log} l on l.instanceid = cm.instance
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
            $instance = $DB->get_record('kalmediares', ['id' => $cm->instance]);
            $data = array();
            $params = array('instanceid' => $context->instanceid,
                            'userid' => $user->id);
            $log = $DB->get_record('kalmediares_log', $params);

            $params = array('id' => $context->instanceid);
            $resource = $DB->get_record('kalmediares', $params);

            if (!empty($log) && !empty($resource)) {
                $logdata = (object) [
                    'name' => format_string($resource->name, true),
                    'plays' => $log->plays,
                    'views' => $log->views,
                    'first' => transform::datetime($log->first),
                    'last' => transform::datetime($log->last)];
                $data[$log->id] = $logdata;

                $instance->export_data(null, $data);
            }
        }
    }

    /**
     * Delete all data for all users in the specified context.
     * @param context $context - The specific context to delete data for.
     */
    public static function delete_data_for_all_users_in_context(context $context) {
        global $DB;

        // Check that this is a context_module.
        if (!$context instanceof \context_module) {
            return;
        }

        // Get the course module.
        if (!$cm = get_coursemodule_from_id('kalmediares', $context->instanceid)) {
            return;
        }

        $resourceid = $cm->instance;

        $DB->delete_records('kalemdires_log', ['instanceid' => $resourceid]);
    }

    /**
     * Delete all user data for the specified user, in the specified contexts.
     * @param approved_contextlist $contextlist - The approved contexts and user information to delete information for.
     */
    public static function delete_data_for_user($contextlist) {
        global $DB;
        $user = $contextlist->get_user();
        $userid = $user->id;
        foreach ($contextlist as $context) {
            // Get the course module.
            $cm = $DB->get_record('course_modules', ['id' => $context->instanceid]);
            $DB->delete_records('kalmediares_log',
                                ['instanceid' => $cm->instance,
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
        $resource = $DB->get_record('kalmediares', ['id' => $cm->instance]);

        list($userinsql, $userinparams) = $DB->get_in_or_equal($userlist->get_userids(), SQL_PARAMS_NAMED);
        $params = array_merge(['resourceid' => $resource->id], $userinparams);
        $sql = "instanceid = :resourceid and userid {$userinsql}";

        $DB->delete_records_select('kalmediares_log', $sql, $params);
    }
}
