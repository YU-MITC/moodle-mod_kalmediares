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
 * @package    mod_kalmediares
 * @copyright  (C) 2016-2021 Yamaguchi University <gh-cc@mlex.cc.yamaguchi-u.ac.jp>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;

/**
 * This function returns playback and page view count.
 * @param int $userid - user ID.
 * @param int $mid - module ID.
 * @param int $exclution_time - exclution time duration for stastics calculate.
 */
function mod_kalmediares_get_user_playsviews($userid, $mid, $exclusion_time) {
    global $COURSE, $DB;

    $stamp = time() - 3600 * $exclusion_time;

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
