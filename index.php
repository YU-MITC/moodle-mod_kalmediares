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
 * Displays information about all the resource modules in the requested course
 *
 * @package   mod_kalmediares
 * @copyright (C) 2016-2025 Yamaguchi University <gh-cc@mlex.cc.yamaguchi-u.ac.jp>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
require_once(dirname(dirname(dirname(__FILE__))) . '/local/yukaltura/locallib.php');
require_once(dirname(__FILE__) . '/renderable.php');

defined('MOODLE_INTERNAL') || die();

header('Access-Control-Allow-Origin: *');
header('Cache-Control: no-cache');

$id = required_param('id', PARAM_INT); // Course ID.

$course = $DB->get_record('course', array('id' => $id), '*', MUST_EXIST);

global $PAGE, $SESSION, $CFG;

$strplural = get_string("modulenameplural", "kalmediares");
$PAGE->set_url('/mod/kalmediares/index.php', array('id' => $id));
$PAGE->set_pagelayout('incourse');
$PAGE->navbar->add($strplural);
$PAGE->set_url('/mod/kalmediares/index.php');
$PAGE->set_title($strplural);
$PAGE->set_heading($course->fullname);
$PAGE->set_course($course);

require_login();

echo $OUTPUT->header();

$renderer = $PAGE->get_renderer('mod_kalmediares');
$renderer->display_kalmediaresources_table($course);

echo $OUTPUT->footer();
