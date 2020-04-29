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
 * access log migiration script from version 1.2.x to 1.3.x
 *
 * @package    mod_kalmediares
 * @copyright  (C) 2016-2020 Yamaguchi University <gh-cc@mlex.cc.yamaguchi-u.ac.jp>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('CLI_SCRIPT', true);
require(__DIR__ . '/../../../config.php');

require_once($CFG->libdir . '/clilib.php');

try {
    $mdata = $DB->get_records('modules', array('name' => 'kalmediares'));

    $mid = 0;

    foreach ($mdata as $row) {
            $mid = $row->id;
    }
    unset($mdata);

    $mdata = $DB->get_records('course_modules', array('module' => $mid));

    if (empty($mdata)) {
        echo "There is no media resources to migirate access logs." . PHP_EOL;
        exit;
    }

    $i = 0;
    $n = count($mdata);

    foreach ($mdata as $row1) {
        $i = $i + 1;
        $rid = $row1->instance;
        $cmid = $row1->id;
        $query = 'select a.userid as uid, ifnull(viewnum, 0) as views, ifnull(playnum, 0) as plays, ';
        $query .= 'least(firstview,ifnull(firstplay, firstview)) as first, ';
        $query .= 'greatest(ifnull(firstplay,firstview), ifnull(lastplay, lastview)) as last from ';
        $query .= '(select userid, count(userid) as viewnum, ';
        $query .= 'min(timecreated) as firstview, max(timecreated) as lastview ';
        $query .= 'from {logstore_standard_log} ';
        $query .= 'where component=\'mod_kalmediares\' and action=\'viewed\' and contextinstanceid=:cmid1 ';
        $query .= 'group by userid) a ';
        $query .= 'left join ';
        $query .= '(select userid,count(userid) as playnum, ';
        $query .= 'min(timecreated) as firstplay, max(timecreated) as lastplay ';
        $query .= 'from {logstore_standard_log} ';
        $query .= 'where component=\'mod_kalmediares\' and action=\'played\' and contextinstanceid=:cmid2 ';
        $query .= 'group by userid) b on a.userid=b.userid';

        $skip = true;

        $logdata = $DB->get_recordset_sql($query, array('cmid1' => $cmid, 'cmid2' => $cmid));
        foreach ($logdata as $row2) {
            if (! $kalmediareslog = $DB->get_record('kalmediares_log',
                                                    array('instanceid' => $rid, 'userid' => $row2->uid))) {
                $dataobject = array('instanceid' => $rid, 'userid' => $row2->uid, 'plays' => $row2->plays,
                                    'views' => $row2->views, 'first' => $row2->first, 'last' => $row2->last);
                $DB->insert_record('kalmediares_log', $dataobject, true, false);
                $skip = false;
            }
        }
        unset($logdata);

        echo "Log data migration of media resource ($i/$n) was ";
        if ($skip) {
            echo "skippped." . PHP_EOL;
        } else {
            echo "migrated." . PHP_EOL;
        }
    }
    unset($mdata);

    echo "Now, all migration processes have been finished." . PHP_EOL;
} catch (Exception $ex) {
    cli_error($ex->getErrorMessage());
}
