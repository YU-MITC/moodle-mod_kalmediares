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
 * Prints access status for media resource.
 *
 * You can have a rather longer description of the file as well,
 * if you like, and it can span multiple lines.
 *
 * @package   mod_kalmediares
 * @copyright (C) 2016-2023 Yamaguchi University <gh-cc@mlex.cc.yamaguchi-u.ac.jp>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
require_once(dirname(dirname(dirname(__FILE__))) . '/local/yukaltura/locallib.php');

defined('MOODLE_INTERNAL') || die();

header('Access-Control-Allow-Origin: *');
header('Cache-Control: no-cache');

global $SESSION, $CFG, $USER, $COURSE, $DB;

$id = optional_param('id', 0, PARAM_INT);               // Course Module ID.
$page = optional_param('page', 0, PARAM_INT);           // Mymedia Page ID.
$sort = optional_param('sort', 'lastname', PARAM_TEXT); // Sorting Key.
$order = optional_param('order', 'ASC', PARAM_TEXT);    // Sorting Order (ASC or DESC).

if ($sort != 'lastname' && $sort != 'firstname' && $sort != 'last' && $sort != 'plays' && $sort != 'views') {
    $sort = 'lastname';
}

if ($order != 'ASC' && $order != 'DESC') {
    $order = 'ASC';
}

// Retrieve module instance.
if (empty($id)) {
    throw new moodle_exception('invalid_module', 'kalmediares', '', 'N/A');
    die();
} else if (! $cm = get_coursemodule_from_id('kalmediares', $id)) {
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

$PAGE->set_url('/mod/kalmediares/access_logs.php', array('id' => $id, 'sort' => $sort, 'order' => $order));
$PAGE->set_title(get_string('access_logs', 'kalmediares') . ':' . format_string($kalmediares->name));
$PAGE->set_heading($course->fullname);

$coursenode = $PAGE->navigation->find($id, navigation_node::TYPE_ACTIVITY);
$thingnode = $coursenode->add(get_string('access_logs', 'kalmediares'),
                              new moodle_url('/mod/kalmediares/access_logs.php' . '?id=' . $id));
$thingnode->make_active();

$tablerows = 50;
$perpage = 50;

echo $OUTPUT->header();

$coursecontext = context_course::instance($COURSE->id);

require_capability('mod/kalmediares:viewlog', $coursecontext, $USER);

if (has_capability('mod/kalmediares:viewlog', $coursecontext)) {
    echo '<h3>' . get_string('access_logs', 'kalmediares') . '</h3>';

    $renderer = $PAGE->get_renderer('mod_kalmediares');

    echo $renderer->create_export_excel_markup($id, $sort, $order);

    $output = $renderer->create_access_list_markup($kalmediares, $id, $sort, $order, $page, $tablerows, $perpage, $rows);

    $page = $OUTPUT->paging_bar($rows, $page, $perpage,
                                new moodle_url('/mod/kalmediares/access_logs.php',
                                array('id' => $id, 'sort' => $sort, 'order' => $order))
                               );

    echo $renderer->create_pagingbar_markup($page);
    echo $output;
    echo $renderer->create_pagingbar_markup($page);

} else {
    echo get_string('cannot_view', 'kalmediares') . '<br>';
}

echo $OUTPUT->footer();
