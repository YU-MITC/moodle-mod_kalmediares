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
 * Kaltura media resource renderer class
 *
 * @package    mod
 * @subpackage kalmediares
 * @copyright  (C) 2016-2017 Yamaguchi University <info-cc@ml.cc.yamaguchi-u.ac.jp>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
require_once(dirname(dirname(dirname(__FILE__))) . '/local/yukaltura/locallib.php');
require_once(dirname(dirname(dirname(__FILE__))) . '/local/yukaltura/API/KalturaClient.php');
require_once(dirname(dirname(dirname(__FILE__))) . '/local/yukaltura/kaltura_entries.class.php');
require_once(dirname(dirname(dirname(__FILE__))) . '/mod/kalmediares/renderable.php');

if (!defined('MOODLE_INTERNAL')) {
    // It must be included from a Moodle page.
    die('Direct access to this script is forbidden.');
}

class mod_kalmediares_renderer extends plugin_renderer_base {

    public function display_mod_info($title) {

        $output = '';
        $output .= html_writer::start_tag('b');
        $output .= html_writer::tag('div', $title);
        $output .= html_writer::end_tag('b');
        $output .= html_writer::empty_tag('br');

        return $output;
    }

    public function embed_media($kalmediares) {
        global $PAGE, $COURSE;

        $output = '';
        $entryobj = local_yukaltura_get_ready_entry_object($kalmediares->entry_id);

        if (!empty($entryobj)) {

            // Check if player selection is globally overridden.
            if (local_yukaltura_get_player_override()) {
                $newplayer = local_yukaltura_get_player_uiconf('player_resource');
                $kalmediares->uiconf_id = $newplayer;
            }

            $courseid = $COURSE->id;
            // Set the session.
            $session = local_yukaltura_generate_kaltura_session(array($entryobj->id));

            // Determine if the mobile theme is being used.
            $theme = core_useragent::get_device_type_theme();

            if (KalturaMediaType::IMAGE == $entryobj->mediaType) {
                $markup = local_yukaltura_create_image_markup($entryobj, $kalmediares->name, $theme,
                                                            KALTURA_IMAGE_DESKTOP_WIDTH, KALTURA_IMAGE_DESKTOP_HEIGHT);
            } else {
                $entryobj->width = $kalmediares->width;
                $entryobj->height = $kalmediares->height;

                if (0 == strcmp($theme, 'mymobile')) {
                    $markup = local_yukaltura_get_kwidget_code($entryobj, $kalmediares->uiconf_id, $courseid, $session);
                } else {
                    $markup = local_yukaltura_get_kdp_code($entryobj, $kalmediares->uiconf_id, $courseid, $session);
                }
            }

            $output .= html_writer::start_tag('center');
            $output .= html_writer::tag('div', $markup);
            $output .= html_writer::end_tag('center');
        } else {
            $output = get_string('media_converting', 'kalmediares');
        }

        return $output;
    }

    public function connection_failure() {
        return html_writer::tag('p', get_string('conn_failed_alt', 'local_yukaltura'));
    }

    public function create_access_link_markup($id) {
        global $COURSE, $USER;

        $output = '';
        $admin = false;
        $teacher = false;

        if (is_siteadmin()) {
            $admin = true;
        }

        $coursecontext = context_course::instance($COURSE->id);
        $roles = get_user_roles($coursecontext, $USER->id);
        foreach ($roles as $role) {
            if ($role->shortname == 'teacher' || $role->shortname == 'editingteacher') {
                $teacher = true;
            }
        }

        if ($admin == true || $teacher == true) {
            $output .= '<br>';
            $output .= '<p align=center>';
            $output .= '<a href="./access_logs.php?id='  . $id . '&sort=lastname&order=ASC">';
            $output .= get_string('view_access_logs', 'kalmediares');
            $output .= '</a>';
            $output .= '</p>';
        }

        return $output;
    }

    public function create_pagingbar_markup($page) {
        global $USER;

        $output = '';

        $attr   = array('border' => 0, 'width' => '100%');
        $output .= html_writer::start_tag('table', $attr);

        $output .= html_writer::start_tag('tr');

        $attr   = array('colspan' => 3, 'align' => 'center');
        $output .= html_writer::start_tag('td', $attr);

        $output .= $page;

        $output .= html_writer::end_tag('td');

        $output .= html_writer::end_tag('tr');

        $output .= html_writer::end_tag('table');

        return $output;
    }

    public function create_access_list_markup($kalmediares, $moduleid, $sort, $order, $page, $tablerows, $perpage, &$rows) {
        global $CFG, $COURSE, $USER, $OUTPUT, $DB;

        $url = new moodle_url('/mod/kalmediares/access_logs.php');

        $output = '';

        $rows = 0;

        if (!empty($kalmediares->entry_id)) {
            $total = 0;

            $roleid = 0;

            $roledata = $DB->get_records('role', array('shortname' => 'student'));

            foreach ($roledata as $row) {
                $roleid = $row->id;
            }

            $coursecontext = context_course::instance($COURSE->id);

           $query = 'select m.id, picture, m.firstname, m.lastname, m.firstnamephonetic, m.lastnamephonetic, m.middlename, m.alternatename, m.imagealt, m.email, n.plays, n.views, n.first, n.last from ((select distinct u.id, picture, u.firstname, u.lastname, u.firstnamephonetic, u.lastnamephonetic, u.middlename, u.alternatename, u.imagealt, u.email from {role_assignments} as a join {user} as u on u.id=a.userid and a.contextid=' . $coursecontext->id . ' and a.roleid=' . $roleid . ') as m left join (select v.userid, plays, views, least(firstview,ifnull(firstplay, firstview)) as first, greatest(ifnull(firstplay,firstview),ifnull(lastplay,lastview)) as last from ((select userid,count(timecreated) as views, min(timecreated) as firstview, max(timecreated) as lastview from {logstore_standard_log} where component=\'mod_kalmediares\' and action=\'viewed\' and contextinstanceid=' . $moduleid . ' group by userid) as v left join (select userid,count(timecreated) as plays, min(timecreated) as firstplay, max(timecreated) as lastplay from {logstore_standard_log} where component=\'mod_kalmediares\' and action=\'played\' and contextinstanceid=' . $moduleid . ' group by userid) as p on v.userid=p.userid)) as n on n.userid=m.id) order by ' . $sort . ' ' . $order;

            $studentlist = $DB->get_recordset_sql( $query );

            $totalplays = 0;
            $totalviews = 0;
            $recently = 0;

            $i = 0;
            $j = 0;

            if ($studentlist != null) {
                foreach ($studentlist as $student) {

                    if ($student->plays == null) {
                        $student->plays = 0;
                    }

                    if ($student->views == null) {
                        $student->views = 0;
                    }

                    $totalplays = $totalplays + $student->plays;
                    $totalviews = $totalviews + $student->views;

                    if ($student->last != null and $student->last > 0) {
                        $recently = $student->last;
                    }

                    if ($i >= $page * $perpage  and $i < ($page + 1) * $perpage) {

                        if ( $j % $tablerows == 0 ) {
                            $attr = array('class' => 'generaltable', 'border' => '0', 'cellpadding' => '10', 'cellspacing' => '0');
                            $output .= html_writer::start_tag('table', $attr);

                            $output .= html_writer::start_tag('thead');
                            $output .= html_writer::start_tag('tr');

                            $attr = array('class' => 'header c0');
                            $output .= html_writer::start_tag('th', $attr);
                            $output .= '#';
                            $output .= html_writer::end_tag('th');

                            $attr = array('class' => 'header c1');
                            $output .= html_writer::start_tag('th', $attr);
                            $output .= ' ';
                            $output .= html_writer::end_tag('th');

                            $attr = array('class' => 'header c2');
                            $output .= html_writer::start_tag('th', $attr);

                            $link = $url . '?id=' . $moduleid. '&sort=lastname&order=';
                            if ($sort == 'lastname') {
                                if ($order == 'ASC') {
                                    $link .= 'DESC';
                                } else {
                                    $link .= 'ASC';
                                }
                            } else {
                                $link .= 'ASC';
                            }

                            $output .= '<a href="' . $link . '">' . get_string('lastname', 'moodle') . '</a>';

                            $output .= ' / ';

                            $link = $url . '?id=' . $moduleid . '&sort=firstname&order=';
                            if ($sort == 'firstname') {
                                if ($order == 'ASC') {
                                    $link .= 'DESC';
                                } else {
                                    $link .= 'ASC';
                                }
                            } else {
                                $link .= 'ASC';
                            }

                            $output .= '<a href="' . $link . '">' . get_string('firstname', 'moodle') . '</a>';

                            $output .= html_writer::end_tag('th');

                            $attr = array('class' => 'header c3');

                            $output .= html_writer::start_tag('th', $attr);

                            $link = $url . '?id=' . $moduleid . '&sort=plays&order=';
                            if ($sort == 'plays') {
                                if ($order == 'ASC') {
                                    $link .= 'DESC';
                                } else {
                                    $link .= 'ASC';
                                }
                            } else {
                                $link .= 'ASC';
                            }

                            $output .= '<a href="' . $link . '">' . get_string('plays', 'kalmediares') . '</a>';

                            $output .= html_writer::end_tag('th');


                            $attr = array('class' => 'header c4');

                            $output .= html_writer::start_tag('th', $attr);

                            $link = $url . '?id=' . $moduleid . '&sort=views&order=';
                            if ($sort == 'views') {
                                if ($order == 'ASC') {
                                    $link .= 'DESC';
                                } else {
                                    $link .= 'ASC';
                                }
                            } else {
                                $link .= 'ASC';
                            }

                            $output .= '<a href="' . $link . '">' . get_string('views', 'kalmediares') . '</a>';

                            $output .= html_writer::end_tag('th');

                            $attr = array('class' => 'header c5', 'lastcol' => '');

                            $output .= html_writer::start_tag('th', $attr);

                            $link = $url . '?id=' . $moduleid . '&sort=last&order=';
                            if ($sort == 'last') {
                                if ($order == 'ASC') {
                                    $link .= 'DESC';
                                } else {
                                    $link .= 'ASC';
                                }
                            } else {
                                $link .= 'ASC';
                            }

                            $output .= '<a href="' . $link . '">' . get_string('lastaccess', 'moodle') . '</a>';

                            $output .= html_writer::end_tag('th');

                            $output .= html_writer::end_tag('tr');
                            $output .= html_writer::end_tag('thead');

                            $output .= html_writer::start_tag('tbody');
                        }

                        $output .= html_writer::start_tag('tr');

                        $attr = array('class' => 'cell c0', 'align' => 'left');
                        $output .= html_writer::start_tag('td', $attr);

                        $output .= $i + 1;

                        $output .= html_writer::end_tag('td');

                        $options = array('size' => 25,
                                     'courseid' => $COURSE->id,
                                     'link' => true,
                                     'popup' => false,
                                     'alttext' => true,
                                     'class' => 'userpicture',
                                     'visibletoscreenreaders' => true
                                    );

                        $attr = array('class' => 'cell c1');
                        $output .= html_writer::start_tag('td');
                        $output .= $OUTPUT->user_picture($student, $options);
                        $output .= html_writer::end_tag('td');

                        $attr = array('class' => 'cell c2');
                        $output .= html_writer::start_tag('td');

                        $link = $CFG->wwwroot . '/user/view.php?';
                        $link .= 'id=' . $student->id . '&course=' . $COURSE->id;

                        $output .= '<a href= "' . $link . '">' . $student->lastname . ' ' . $student->firstname . '</a>';
                        $output .= html_writer::end_tag('td');

                        $attr = array('class' => 'cell c3', 'align' => 'right');
                        $output .= html_writer::start_tag('td', $attr);
                        $output .= $student->plays;
                        $output .= html_writer::end_tag('td');

                        $attr = array('class' => 'cell c4', 'align' => 'right');
                        $output .= html_writer::start_tag('td', $attr);
                        $output .= $student->views;
                        $output .= html_writer::end_tag('td');

                        $attr = array('class' => 'cell c5', 'align' => 'center');
                        $output .= html_writer::start_tag('td', $attr);

                        $lastaccess = '-';
                        if ($student->last != null and $student->last > 0) {
                            $lastaccess = date("Y-m-d H:i:s", $student->last);
                        }

                        $output .= $lastaccess;
                        $output .= html_writer::end_tag('td');

                        $output .= html_writer::end_tag('tr');

                        $j = $j + 1;

                        if ($j % $tablerows == 0) {
                            $output .= html_writer::end_tag('tbody');
                            $output .= html_writer::end_tag('table');
                            $output .= html_writer::empty_tag('br');
                        }
                    }

                    $i = $i + 1;
                }

                unset($student);

                if ($j % $tablerows != 0) {
                    $output .= html_writer::end_tag('tbody');
                    $output .= html_writer::end_tag('table');
                    $output .= html_writer::empty_tag('br');
                }

                unset($student);
            }

            $stats = '';

            if ($i == 0) {
                $stats .= html_writer::start_tag('p');
                $stats .= get_string('no_student', 'kalmediares');
                $stats .= html_writer::end_tag('p');
            }

            if ($totalplays > 0 || $totalviews > 0) {
                $stats .= html_writer::start_tag('p');
                $stats .= get_string('lastaccess', 'moodle')  . ' : ' . date("Y-m-d H:i:s", $recently);
                $stats .= html_writer::end_tag('p');
                $stats .= html_writer::start_tag('p');
                $stats .= get_string('totalplays', 'kalmediares') . ' : ' . $totalplays .'';
                $stats .= html_writer::end_tag('p');
                $stats .= html_writer::start_tag('p');
                $stats .= get_string('totalviews', 'kalmediares') . ' : ' . $totalviews .'';
                $stats .= html_writer::end_tag('p');
            } else {
                $stats .= html_writer::start_tag('p');
                $stats .= get_string('not_viewed', 'kalmediares');
                $stats .= html_writer::end_tag('p');
            }

            $output = $stats . $output;

            $rows = $i;
        }

        return $output;
    }


    public function create_access_error_markup($ipaddress = 'unknown') {
        $output = '';
        $output .= get_string('invalid_ipaddress', 'kalmediares');
        $output .= '(Your IP Address : ' . $ipaddress . ')<br>';
        return $output;
    }


    public function create_export_excel_markup($id, $sort, $order) {
        $output  = '';

        $attr = array('align' => 'right');
        $output .= html_writer::start_tag('div', $attr);

        $output .= $this->single_button(new moodle_url('/mod/kalmediares/export_excel.php',
                                                         array('id' => $id, 'sort' => $sort, 'order' => $order)),
                                          get_string('download', 'admin'));

        $output .= html_writer::end_tag('div');

        return $output;
    }

    /**
     * Displays the resources listing table.
     *
     * @param object $course The course odject.
     */
    public function display_kalmediaresources_table($course) {
        global $CFG, $DB, $PAGE, $OUTPUT, $USER;

        echo html_writer::start_tag('center');

        $strplural = get_string('modulenameplural', 'kalmediares');

        if (!$cms = get_coursemodules_in_course('kalmediares', $course->id)) {
            echo get_string('noresources', 'kalmediares');
            echo $OUTPUT->continue_button($CFG->wwwroot.'/course/view.php?id='.$course->id);
        }

        $strsectionname  = get_string('sectionname', 'format_'.$course->format);
        $usesections = course_format_uses_sections($course->format);
        $modinfo = get_fast_modinfo($course);

        if ($usesections) {
            $sections = $modinfo->get_section_info_all();
        }
        $courseindexsummary = new kalmediares_course_index_summary($usesections, $strsectionname);

        $timenow = time();
        $currentsection = '';
        $resourcecount = 0;

        if (!empty($modinfo) && !empty($modinfo->instances['kalmediares'])) {
            foreach ($modinfo->instances['kalmediares'] as $cm) {
                if (!$cm->uservisible) {
                    continue;
                }

                $resourcecount++;

                $sectionname = '';
                if ($usesections && $cm->sectionnum) {
                    $sectionname = get_section_name($course, $sections[$cm->sectionnum]);
                }

                $context = context_module::instance($cm->id);
                $courseindexsummary->add_resource_info($cm->id, $cm->name, $sectionname);
            }
        }

        if ($resourcecount > 0) {
            $pagerenderer = $PAGE->get_renderer('mod_kalmediares');
            echo $pagerenderer->render($courseindexsummary);
        }

        echo html_writer::end_tag('center');
    }

    /**
     * Render a course index summary.
     *
     * @param kalmediaassign_course_index_summary $indexsummary Structure for index summary.
     * @return string HTML for assignments summary table
     */
    public function render_kalmediares_course_index_summary(kalmediares_course_index_summary $indexsummary) {
        $strplural = get_string('modulenameplural', 'kalmediares');
        $strsectionname  = $indexsummary->courseformatname;

        $table = new html_table();
        if ($indexsummary->usesections) {
            $table->head  = array ($strsectionname, $strplural);
            $table->align = array ('left', 'left');
        } else {
            $table->head  = array ($strplural);
            $table->align = array ('left');
        }
        $table->data = array();

        $currentsection = '';
        foreach ($indexsummary->resources as $info) {
            $params = array('id' => $info['cmid']);
            $link = html_writer::link(new moodle_url('/mod/kalmediares/view.php', $params), $info['cmname']);

            $printsection = '';
            if ($indexsummary->usesections) {
                if ($info['sectionname'] !== $currentsection) {
                    if ($info['sectionname']) {
                        $printsection = $info['sectionname'];
                    }
                    if ($currentsection !== '') {
                        $table->data[] = 'hr';
                    }
                    $currentsection = $info['sectionname'];
                }
            }

            if ($indexsummary->usesections) {
                $row = array($printsection, $link);
            } else {
                $row = array($link);
            }
            $table->data[] = $row;
        }

        return html_writer::table($table);
    }
}
