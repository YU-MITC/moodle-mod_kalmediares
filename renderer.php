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
 * @package    mod_kalmediares
 * @copyright  (C) 2016-2022 Yamaguchi University <gh-cc@mlex.cc.yamaguchi-u.ac.jp>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
require_once(dirname(dirname(dirname(__FILE__))) . '/local/yukaltura/locallib.php');
require_once(dirname(dirname(dirname(__FILE__))) . '/local/yukaltura/API/KalturaClient.php');
require_once(dirname(dirname(dirname(__FILE__))) . '/local/yukaltura/kaltura_entries.class.php');
require_once(dirname(dirname(dirname(__FILE__))) . '/mod/kalmediares/renderable.php');
require_once(dirname(dirname(dirname(__FILE__))) . '/mod/kalmediares/locallib.php');

defined('MOODLE_INTERNAL') || die();

require_login();

/**
 * Renderer class of YU Kaltura media resource.
 * @package    mod_kalmediares
 * @copyright  (C) 2016-2022 Yamaguchi University <gh-cc@mlex.cc.yamaguchi-u.ac.jp>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_kalmediares_renderer extends plugin_renderer_base {

    /**
     * This function return HTML markup to display module information(title).
     * @param string $title - title of module.
     * @return string - HTML markup to display module information.
     */
    public function display_mod_info($title) {

        $output = '';
        $output .= html_writer::start_tag('b');
        $output .= html_writer::tag('div', $title);
        $output .= html_writer::end_tag('b');
        $output .= html_writer::empty_tag('br');

        return $output;
    }

    /**
     * This function return HTML markup to embed media.
     * @param object $kalmediares - object of Kaltura Media resource.
     * @param object $connection - object of Kaltura connection.
     * @return string - HTML markup to embed media.
     */
    public function embed_media($kalmediares, $connection) {

        $output = '';
        $entryobj = local_yukaltura_get_ready_entry_object($kalmediares->entry_id);

        if (!empty($entryobj)) {

            // Check if player selection is globally overridden.
            if (local_yukaltura_get_player_override() &&
                $kalmediares->uiconf_id != local_yukaltura_get_player_uiconf('player_resource')) {
                $newplayer = local_yukaltura_get_player_uiconf('player_resource');
                $kalmediares->uiconf_id = $newplayer;
            }

            // Set the session.
            $session = local_yukaltura_generate_kaltura_session(true, array($entryobj->id));

            // Determine if the mobile theme is being used.
            $theme = core_useragent::get_device_type_theme();

            if (KalturaMediaType::IMAGE == $entryobj->mediaType) {
                $markup = local_yukaltura_create_image_markup($entryobj, $kalmediares->name, $theme,
                                                            KALTURA_IMAGE_DESKTOP_WIDTH, KALTURA_IMAGE_DESKTOP_HEIGHT);
            } else {
                $devicetype = core_useragent::get_device_type();
                if (get_config(KALTURA_PLUGIN_NAME, 'enable_player_resource_audio') == 1 &&
                    KalturaMediaType::AUDIO == $entryobj->mediaType && !core_useragent::is_moodle_app() &&
                    $devicetype != core_useragent::DEVICETYPE_MOBILE && $devicetype != core_useragent::DEVICETYPE_MOBILE) {
                    $uiconfobj = local_yukaltura_get_player_object(
                                                                   get_config(
                                                                              KALTURA_PLUGIN_NAME,
                                                                              'player_resource_audio'
                                                                             ),
                                                                   $connection
                                                                  );
                    if (!empty($uiconfobj)) {
                        $kalmediares->uiconf_id = get_config(KALTURA_PLUGIN_NAME, 'player_resource_audio');
                        $kalmediares->width = $uiconfobj->width;
                        $kalmediares->height = $uiconfobj->height;
                    }
                }
                $entryobj->width = $kalmediares->width;
                $entryobj->height = $kalmediares->height;

                $playertype = local_yukaltura_get_player_type($kalmediares->uiconf_id, $connection);

                if ($playertype == KALTURA_TV_PLATFORM_STUDIO) {
                    $markup = local_yukaltura_get_dynamicembed_code($entryobj, $kalmediares->uiconf_id, $connection, $session);
                } else {
                    if (false !== strpos($theme, 'mymobile')) {
                        $markup = local_yukaltura_get_kwidget_code($entryobj, $kalmediares->uiconf_id, $session);
                    } else {
                        $markup = local_yukaltura_get_dynamicembed_code($entryobj, $kalmediares->uiconf_id, $connection, $session);
                    }
                }
            }

            $output .= html_writer::start_tag('center');
            $output .= html_writer::tag('div', $markup);

            $accesscontrol = local_yukaltura_get_internal_access_control($connection);
            if ($entryobj != null && $accesscontrol != null && $entryobj->accessControlId == $accesscontrol->id) {
                $output .= html_writer::tag('div', get_string('internal', 'kalmediares'));
            }

            $output .= html_writer::end_tag('center');
        } else {
            $output = get_string('media_converting', 'kalmediares');
        }

        return $output;
    }

    /**
     * This function return HTML markup to display connection error message.
     * @return string - HTML markup to display connection error message.
     */
    public function connection_failure() {
        return html_writer::tag('p', get_string('conn_failed_alt', 'local_yukaltura'));
    }

    /**
     * This function return HTML markup to display link to access status page.
     * @param int $id - module id.
     * @return string - HTML markup to display link to access status page.
     */
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

    /**
     * This function return HTML markup to display play/view counts.
     * @param int $id - module id.
     * @param object $kalmediares - instance object of YU Kaltura Media Resource.
     * @return string - HTML markup to display link to access status page.
     */
    public function create_student_playsviews_markup($id, $kalmediares) {
        global $COURSE, $USER, $DB;

        $output = '';
        $student = false;

        $coursecontext = context_course::instance($COURSE->id);
        $roles = get_user_roles($coursecontext, $USER->id);
        foreach ($roles as $role) {
            if ($role->shortname == 'student') {
                $student = true;
            }
        }

        if ($student == true) {
            $stamp = time() - 3600 * $kalmediares->exclusion_time;

            $plays = 0;
            $views = 0;

            $sql = 'select count(*) as plays from {logstore_standard_log} ';
            $sql .= 'where component=\'mod_kalmediares\' and contextinstanceid = :mid and ';
            $sql .= 'action = \'played\' and userid = :uid and timecreated <= :stamp';
            $result = $DB->get_record_sql($sql, array('mid' => $id, 'uid' => $USER->id, 'stamp' => $stamp));

            if (!empty($result)) {
                $attr = array('align' => 'center');
                $output .= html_writer::start_tag('div', $attr);
                $plays = $result->plays;
                $output .= get_string('your_plays', 'kalmediares', $plays);
                $output .= html_writer::end_tag('div');
            }

            $sql = 'select count(*) as views from {logstore_standard_log} ';
            $sql .= 'where component=\'mod_kalmediares\' and contextinstanceid = :mid and ';
            $sql .= 'action = \'viewed\' and userid = :uid and timecreated <= :stamp';
            $result = $DB->get_record_sql($sql, array('mid' => $id, 'uid' => $USER->id, 'stamp' => $stamp));

            if (!empty($result)) {
                $attr = array('align' => 'center');
                $output .= html_writer::start_tag('div', $attr);
                $views = $result->views;
                $output .= get_string('your_views', 'kalmediares', $views);
                $output .= html_writer::end_tag('div');
            }

            if ($kalmediares->exclusion_time > 0) {
                $attr = array('align' => 'center');
                $output .= html_writer::start_tag('div', $attr);
                $output .= html_writer::start_tag('font', array('color' => 'red'));
                $output .= get_string('delay_stats_desc', 'mod_kalmediares', $kalmediares->exclusion_time);
                $output .= html_writer::end_tag('font');
                $output .= html_writer::end_tag('div');
            }

        }

        return $output;
    }

    /**
     * This function return HTML markup to display paging bar.
     * @param int $page - page number.
     * @return string - HTML markup to display paging bar.
     */
    public function create_pagingbar_markup($page) {

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

    /**
     * This function return HTML markup to display link to access status page.
     * @param object $kalmediares - object of Kaltura Media resource.
     * @param int $moduleid - moudle id.
     * @param string $sort - sorting option.
     * @param string $order - sorting order ("ASC" or "DESC").
     * @param int $page - page number.
     * @param int $tablerows - rows per table.
     * @param int $perpage - number of rows per page..
     * @param int $rows - number of rows.
     * @return string - HTML markup to display link to access status page.
     */
    public function create_access_list_markup($kalmediares, $moduleid, $sort, $order, $page, $tablerows, $perpage, &$rows) {
        global $CFG, $COURSE, $DB;

        $url = new moodle_url('/mod/kalmediares/access_logs.php');

        $output = '';

        $rows = 0;

        if (!empty($kalmediares->entry_id)) {

            $roleid = 0;

            $roledata = $DB->get_records('role', array('shortname' => 'student'));

            foreach ($roledata as $row) {
                $roleid = $row->id;
            }

            $coursecontext = context_course::instance($COURSE->id);

            $mdata = $DB->get_records('course_modules', array('id' => $moduleid));

            foreach ($mdata as $row) {
                $instanceid = $row->instance;
            }

            $activeids = mod_kalmediares_active_user_list();

            $query = 'select b.id, b.picture, b.firstname, b.lastname, b.firstnamephonetic, b.lastnamephonetic, ';
            $query .= 'b.middlename, b.alternatename, b.imagealt, b.email, c.plays, c.views, c.first, c.last ';
            $query .= 'from (select u.id, u.picture, u.firstname, u.lastname, u.firstnamephonetic, ';
            $query .= 'u.lastnamephonetic, u.middlename, u.alternatename, u.imagealt, u.email ';
            $query .= 'from (select userid from {role_assignments} where contextid=:cid and roleid=:rid group by userid) a ';
            $query .= 'inner join {user} u on a.userid=u.id) b ';
            $query .= 'left join (select userid, plays, views, first, last from {kalmediares_log} ';
            $query .= 'where instanceid=:instanceid) c on b.id=c.userid ';
            $query .= 'order by ' . $sort . ' ' . $order;

            $studentlist = $DB->get_recordset_sql($query,
                                                  array('cid' => $coursecontext->id,
                                                        'rid' => $roleid,
                                                        'instanceid' => $instanceid
                                                  )
                                                 );

            $totalplays = 0;
            $totalviews = 0;
            $recently = 0;

            $i = 0;
            $j = 0;

            if ($studentlist != null && !empty($activeids)) {
                foreach ($studentlist as $student) {
                    if ($student->plays == null) {
                        $student->plays = 0;
                    }

                    if ($student->views == null) {
                        $student->views = 0;
                    }

                    $totalplays = $totalplays + $student->plays;
                    $totalviews = $totalviews + $student->views;

                    if ($student->last != null and $student->last > 0 and $student->last > $recently) {
                        $recently = $student->last;
                    }

                    $activeflag = false;
                    for ($k = 0; $k < count($activeids); $k++) {
                        if ($student->id == $activeids[$k]) {
                            $activeflag = true;
                            break;
                        }
                    }

                    if ($activeflag === false) {
                        continue;
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

                            $attr = array('class' => 'header c3', 'align' => 'center');

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

                            $attr = array('class' => 'header c4', 'align' => 'center');

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
                        $output .= $this->output->user_picture($student, $options);
                        $output .= html_writer::end_tag('td');

                        $attr = array('class' => 'cell c2');
                        $output .= html_writer::start_tag('td');

                        $link = $CFG->wwwroot . '/user/view.php?';
                        $link .= 'id=' . $student->id . '&course=' . $COURSE->id;

                        $output .= '<a href= "' . $link . '">' . $student->lastname . ' ' . $student->firstname . '</a>';
                        $output .= html_writer::end_tag('td');

                        $attr = array('class' => 'cell c3', 'align' => 'center');
                        $output .= html_writer::start_tag('td', $attr);
                        $output .= $student->plays;
                        $output .= html_writer::end_tag('td');

                        $attr = array('class' => 'cell c4', 'align' => 'center');
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

    /**
     * This function return HTML markup to display access error message.
     * @param string $ipaddress - IP address of client.
     * @return string - HTML markup to display access error message.
     */
    public function create_access_error_markup($ipaddress = 'unknown') {
        $output = '';
        $output .= get_string('invalid_ipaddress', 'kalmediares');
        $output .= '(' . get_string('your_ipaddress', 'kalmediares') . ' : ' . $ipaddress . ')<br>';
        return $output;
    }

    /**
     * This function return HTML markup to display download button.
     * @param int $id - id of rsource module.
     * @param string $sort - sorting option.
     * @param string $order - sorting order.
     * @return string - HTML markup to display download button.
     */
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
     * @param object $course - The course odject.
     */
    public function display_kalmediaresources_table($course) {
        global $CFG;

        echo html_writer::start_tag('center');

        if (!get_coursemodules_in_course('kalmediares', $course->id)) {
            echo get_string('noresources', 'kalmediares');
            echo $this->output->continue_button($CFG->wwwroot.'/course/view.php?id='.$course->id);
        }

        $strsectionname  = get_string('sectionname', 'format_'.$course->format);
        $usesections = course_format_uses_sections($course->format);
        $modinfo = get_fast_modinfo($course);

        if ($usesections) {
            $sections = $modinfo->get_section_info_all();
        }
        $courseindexsummary = new kalmediares_course_index_summary($usesections, $strsectionname);

        $resourcecount = 0;

        if (!empty($modinfo) and !empty($modinfo->instances['kalmediares'])) {
            foreach ($modinfo->instances['kalmediares'] as $cm) {
                if (!$cm->uservisible) {
                    continue;
                }

                $resourcecount++;

                $sectionname = '';
                if ($usesections and $cm->sectionnum) {
                    $sectionname = get_section_name($course, $sections[$cm->sectionnum]);
                }

                $courseindexsummary->add_resource_info($cm->id, $cm->name, $sectionname);
            }
        }

        if ($resourcecount > 0) {
            $pagerenderer = $this->page->get_renderer('mod_kalmediares');
            echo $pagerenderer->render($courseindexsummary);
        }

        echo html_writer::end_tag('center');
    }

    /**
     * Render a course index summary.
     * @param kalmediares_course_index_summary $indexsummary - Structure for index summary.
     * @return string - HTML for assignments summary table.
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
