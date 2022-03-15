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
 * Export Excel file of access status.
 *
 * You can have a rather longer description of the file as well,
 * if you like, and it can span multiple lines.
 *
 * @package    mod_kalmediares
 * @copyright  (C) 2016-2022 Yamaguchi University <gh-cc@mlex.cc.yamaguchi-u.ac.jp>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
require_once($CFG->libdir . '/excellib.class.php');
require_once(dirname(dirname(dirname(__FILE__))) . '/local/yukaltura/locallib.php');
require_once(dirname(dirname(dirname(__FILE__))) . '/mod/kalmediares/locallib.php');

defined('MOODLE_INTERNAL') || die();

global $PAGE, $SESSION, $CFG, $USER, $COURSE, $DB;

$PAGE->set_url('/mod/kalmediares/export_excel.php');

require_login();

$id = optional_param('id', 0, PARAM_INT);                // Course Module ID.
$sort = optional_param('sort', 'lastname', PARAM_TEXT);  // Sorting Key.
$order = optional_param('order', 'ASC', PARAM_TEXT);     // Sorting Order (ASC or DESC).

// Retrieve module instance.
if (empty($id)) {
    throw new moodle_exception('invalid_module', 'kalmediares', '', 'N/A');
    die();
}

if (! $cm = get_coursemodule_from_id('kalmediares', $id)) {
    throw new moodle_exception('invalid_module', 'kalmediares', '', $id);
    die();
}

if (! $course = $DB->get_record('course', array('id' => $cm->course))) {
    throw new moodle_exception('course_misconf');
    die();
}

$filename = "kalmediares_logs_" . $course->shortname . "_";
$filename .= date("YmdHi") . ".xlsx";

$workbook = new MoodleExcelWorkbook("-");

$workbook->send($filename);

$worksheet = array();
$worksheet[0] = $workbook->add_worksheet('');

$userdata = array();

if ($sort != 'lastname' and $sort != 'firstname' and $sort != 'last' and $sort != 'plays') {
    $sort = 'lastname';
}

if ($order != 'ASC' and $order != 'DESC') {
    $order = 'ASC';
}

// Retrieve module instance.
if (empty($id)) {
    throw new moodle_exception('invalid_module', 'kalmediares', '', 'N/A');
    die();
}

if (! $cm = get_coursemodule_from_id('kalmediares', $id)) {
    throw new moodle_exception('invalid_module', 'kalmediares', '', $id);
    die();
} else if (! $course = $DB->get_record('course', array('id' => $cm->course))) {
    throw new moodle_exception('course_misconf');
    die();
} else if (! $kalmediares = $DB->get_record('kalmediares', array('id' => $cm->instance))) {
    throw new moodle_exception('invalidid', 'kalmediares');
    die();
}

require_course_login($course->id, true, $cm);

$coursecontext = context_course::instance($COURSE->id);

require_capability('mod/kalmediares:viewlog', $coursecontext, $USER);

if (has_capability('mod/kalmediares:viewlog', $coursecontext) && !empty($kalmediares)) {
    if (!empty($kalmediares->entry_id)) {
        $roleid = 0;

        $roledata = $DB->get_records('role', array('shortname' => 'student'));

        foreach ($roledata as $row) {
            $roleid = $row->id;
        }

        $coursecontext = context_course::instance($COURSE->id);

        $mdata = $DB->get_records('course_modules', array('id' => $id));
        foreach ($mdata as $row) {
            $instanceid = $row->instance;
        }

        try {
            $activeids = mod_kalmediares_active_user_list();

            $query = 'select b.id, b.username, b.firstname, b.lastname, c.plays, c.views, c.first, c.last ';
            $query .= 'from (select u.id, u.username, u.picture, u.firstname, u.lastname, u.firstnamephonetic, ';
            $query .= 'u.lastnamephonetic, u.middlename, u.alternatename, u.imagealt, u.email ';
            $query .= 'from (select userid from {role_assignments} where contextid=:cid and roleid=:rid group by userid) a ';
            $query .= 'inner join {user} u on a.userid=u.id) b ';
            $query .= 'left join (select userid, plays, views, first, last from {kalmediares_log} ';
            $query .= 'where instanceid=:instanceid) c on b.id=c.userid ';
            $query .= 'order by ' . $sort . ' ' . $order;

            $userdata = $DB->get_recordset_sql($query,
                                               array(
                                                   'cid' => $coursecontext->id,
                                                   'rid' => $roleid,
                                                   'instanceid' => $kalmediares->id
                                               )
                                              );
        } catch (Exception $ex) {
            throw new moodle_exception('log_get_error', 'kalmediares', '', $ex->getMessage());
        }

        $worksheet[0]->write_string(0, 0, get_string('username', 'moodle'));
        $worksheet[0]->write_string(0, 1, get_string('lastname', 'moodle'));
        $worksheet[0]->write_string(0, 2, get_string('firstname', 'moodle'));
        $worksheet[0]->write_string(0, 3, get_string('plays', 'kalmediares'));
        $worksheet[0]->write_string(0, 4, get_string('views', 'kalmediares'));
        $worksheet[0]->write_string(0, 5, get_string('firstaccess', 'moodle'));
        $worksheet[0]->write_string(0, 6, get_string('lastaccess', 'moodle'));

        $rownum = 1;

        foreach ($userdata as $row) {
            $activeflag = false;
            for ($k = 0; $k < count($activeids); $k++) {
                if ($row->id == $activeids[$k]) {
                    $activeflag = true;
                    break;
                }
            }

            if ($activeflag === false) {
                continue;
            }

            $firstaccess = '-';
            if ($row->first != null and $row->first > 0) {
                $firstaccess = date("Y-m-d H:i:s", $row->first);
            }

            $lastaccess = '-';
            if ($row->last != null and $row->last > 0) {
                $lastaccess = date("Y-m-d H:i:s", $row->last);
            }

            if ($row->plays == null) {
                $row->plays = 0;
            }

            if ($row->views == null) {
                $row->views = 0;
            }

            $worksheet[0]->write_string($rownum, 0, $row->username);
            $worksheet[0]->write_string($rownum, 1, $row->lastname);
            $worksheet[0]->write_string($rownum, 2, $row->firstname);
            $worksheet[0]->write_string($rownum, 3, $row->plays);
            $worksheet[0]->write_string($rownum, 4, $row->views);
            $worksheet[0]->write_string($rownum, 5, $firstaccess);
            $worksheet[0]->write_string($rownum, 6, $lastaccess);
            $rownum++;
        }

        $worksheet[0]->set_column(0, 0, 16);
        $worksheet[0]->set_column(1, 2, 20);
        $worksheet[0]->set_column(3, 4, 16);
        $worksheet[0]->set_column(5, 6, 20);

    }

}

$workbook->close();
die();
