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
 * Local libraries of YU Kaltura Media resource.
 *
 * @package   mod_kalmediares
 * @copyright (C) 2016-2025 Yamaguchi University <gh-cc@mlex.cc.yamaguchi-u.ac.jp>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;

/**
 * This function returns playback and page view count.
 * @param int $userid - user ID.
 * @param int $mid - module ID.
 * @param int $exclusiontime - exclusion time duration for stastics calculate.
 * @return list - list of plays, and views.
 */
function mod_kalmediares_get_user_playsviews($userid, $mid, $exclusiontime) {
    global $DB;

    $stamp = time() - 3600 * $exclusiontime;

    $plays = 0;
    $views = 0;

    $sql = 'select count(*) as plays from {logstore_standard_log} ';
    $sql .= 'where component=\'mod_kalmediares\' and contextinstanceid = :mid and ';
    $sql .= 'action = \'played\' and userid = :uid and timecreated <= :stamp';
    $result = $DB->get_record_sql($sql, array('mid' => $mid, 'uid' => $userid, 'stamp' => $stamp));

    if (!empty($result)) {
        $plays = $result->plays;
    }

    $sql = 'select count(*) as views from {logstore_standard_log} ';
    $sql .= 'where component=\'mod_kalmediares\' and contextinstanceid = :mid and ';
    $sql .= 'action = \'viewed\' and userid = :uid and timecreated <= :stamp';
    $result = $DB->get_record_sql($sql, array('mid' => $mid, 'uid' => $userid, 'stamp' => $stamp));

    if (!empty($result)) {
        $views = $result->views;
    }

    return [$plays, $views];
}

/**
 * This function returns IDs of active users.
 * @return array - IDs of active users in course.
 */
function mod_kalmediares_active_user_list() {
    global $COURSE, $DB;

    $query = 'select id from {enrol} where courseid=:courseid and status=:statusid';

    $enrolitems = $DB->get_recordset_sql($query, array('courseid' => $COURSE->id, 'statusid' => 0));
    $enrolids = '';
    foreach ($enrolitems as $item) {
        if (strcmp($enrolids, '') != 0) {
            $enrolids .= ',';
        }
        $enrolids .= $item->id;
    }

    $query = 'select userid from {user_enrolments} where enrolid in (' . $enrolids  . ') ';
    $query .= 'and status=:statusid group by userid';
    $activelist = $DB->get_recordset_sql($query, array('statusid' => 0));
    $activeids = array();
    $i = 0;
    foreach ($activelist as $activeitem) {
        $activeids[$i] = $activeitem->userid;
        $i++;
    }

    return $activeids;
}

