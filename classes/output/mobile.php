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
 * Contains the mobile output class for mod_kalmediares.
 *
 * @package   mod_kalmediares
 * @copyright (C) 2016-2023 Yamaguchi University <gh-cc@mlex.cc.yamaguchi-u.ac.jp>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_kalmediares\output;

use context_module;

defined('MOODLE_INTERNAL') || die();
require_once(dirname(dirname(dirname(dirname(dirname(__FILE__))))) . '/config.php');
require_login();

/**
 * Mobile output class for the mod_kalmediares.
 *
 * @package   mod_kalmediares
 * @copyright (C) 2016-2023 Yamaguchi University <gh-cc@mlex.cc.yamaguchi-u.ac.jp>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mobile {
    /**
     *  This function returns the initial page when viewing the activity for the mobile app.
     *
     * @param array $args - Arguments from tool_mobile_get_content WS.
     * @return array - HTML, javascript and other data.
     */
    public static function mobile_view_activity($args) {
        global $CFG, $OUTPUT, $DB, $PAGE;

        $renderer = $PAGE->get_renderer('mod_kalmediares');

        require_once($CFG->dirroot . '/local/yukaltura/locallib.php');
        require_once($CFG->dirroot . '/mod/kalmediares/locallib.php');
        require_once($CFG->libdir . '/completionlib.php');

        $cmid = $args['cmid'];

        if (! $cm = get_coursemodule_from_id('kalmediares', $cmid)) {
            throw new \moodle_exception('invalid_module', 'kalmediares', '', $cmid);
        }

        if (! $course = $DB->get_record('course', array('id' => $cm->course))) {
            throw new \moodle_exception('course_misconf');
        }

        if (! $kalmediares = $DB->get_record('kalmediares', array('id' => $cm->instance))) {
            throw new \moodle_exception('invalid_id', 'kalmediares', '', $cm->instance);
        }

        $PAGE->requires->jquery();

        $url = $CFG->wwwroot . '/mod/kalmediares/trigger.php';
        $PAGE->requires->js_call_amd('mod_kalmediares/playtrigger', 'init', array($url, $cmid));

        $html = '';
        $json = array();
        $data = [];

        $data['cmid'] = $cmid;

        $clientipaddress = local_yukaltura_get_client_ipaddress(true);

        if ($kalmediares->internal == 1 && !local_yukaltura_check_internal($clientipaddress)) {
            $data['error'] = true;
            $data['message'] = 'mod_kalmediares.invalid_ipaddress';
        } else {
            // Try connection.
            $kaltura = new \yukaltura_connection();
            $connection = $kaltura->get_connection(false, true, KALTURA_SESSION_LENGTH);
            if ($connection) {
                $media = $connection->media->get($kalmediares->entry_id);
                if ($media !== null) {
                    if (local_yukaltura_get_player_override() &&
                        $kalmediares->uiconf_id != local_yukaltura_get_player_uiconf('player_resource')) {
                        $newplayer = local_yukaltura_get_player_uiconf('player_resource');
                        $kalmediares->uiconf_id = $newplayer;
                    }

                    $userid = $args['userid'];
                    $student = false;
                    $teacher = false;

                    $coursecontext = \context_course::instance($args['courseid']);
                    $roles = get_user_roles($coursecontext, $userid);
                    foreach ($roles as $role) {
                        if ($role->shortname == 'student' || $role->shortname == 'guest') {
                            $student = true;
                        }
                        if ($role->shortname == 'teacher' || $role->shortname == 'editingteacher') {
                            $teacher = true;
                        }
                    }

                    if ($student == true) {
                        $event = \mod_kalmediares\event\media_resource_viewed::create(array(
                            'objectid' => $kalmediares->id,
                            'context' => \context_module::instance($cmid)
                        ));
                        $event->trigger();

                        try {
                            $kalmediareslog = $DB->get_record('kalmediares_log',
                                array('instanceid' => $cm->instance, 'userid' => $userid));
                            $now = time();
                            $data['playerid'] = 'kaltura_player_' . $now;
                            $data['now'] = $now;
                            if (empty($kalmediareslog)) {
                                $objectdata = array('instanceid' => $cm->instance, 'userid' => $userid,
                                    'plays' => 0, 'views' => 1, 'first' => $now, 'last' => $now);
                                $DB->insert_record('kalmediares_log', $objectdata);
                            } else {
                                $kalmediareslog->last = $now;
                                $kalmediareslog->views = $kalmediareslog->views + 1;
                                $DB->update_record('kalmediares_log', $kalmediareslog, false);
                            }

                            if ( $kalmediares->publish_access_log == 1) {
                                list($plays, $views) = \mod_kalmediares_get_user_playsviews($userid, $cmid,
                                                                                           $kalmediares->exclusion_time);
                                $studentlog = array();
                                $studentlog['plays'] = get_string('your_plays', 'kalmediares', $plays);
                                $studentlog['views'] = get_string('your_views', 'kalmediares', $views);
                                if ($kalmediares->exclusion_time > 0) {
                                    $studentlog['exclusion'] = get_string('delay_stats_desc',
                                                                          'kalmediares',
                                                                         $kalmediares->exclusion_time);
                                }
                                $json['studentlog'] = $studentlog;
                            }
                        } catch (Exception $ex) {
                            $data['error'] = true;
                            $data['message'] = $ex->getMessage();
                        }
                    }

                    $entryobj = local_yukaltura_get_ready_entry_object($kalmediares->entry_id);
                    if (!empty($entryobj)) {
                        // For completion trackings.
                        if ((\KalturaMediaType::VIDEO != $entryobj->mediaType &&
                            \KalturaMediaType::AUDIO != $entryobj->mediaType) ||
                            $teacher == true && $student == false) {
                            $completion = new \completion_info($course);
                            $completion->set_module_viewed($cm);
                        }

                        $uiconfid = $kalmediares->uiconf_id;

                        if (empty($uiconfid)) {
                            $uiconf = local_yukaltura_get_player_uiconf('player');
                        } else {
                            $uiconf = $uiconfid;
                        }

                        $data['host'] = local_yukaltura_get_host();
                        $data['partnerid'] = local_yukaltura_get_partner_id();
                        $data['uiconf'] = $uiconf;
                        $data['entryid'] = $kalmediares->entry_id;
                    } else {
                        $data['error'] = true;
                        $data['message'] = 'mod_kalmediares.media_converting';
                    }
                } else {
                    $data['error'] = true;
                    $data['message'] = 'mod_kalmediares.no_media';
                    $data['data'] = $kalmediares->entry_id;
                }
            } else { // Connetion failed.
                $data['error'] = true;
                $data['message'] = 'local_yukaltura.conn_failed_alt';
            }

            $json['kalmediares'] = $kalmediares;
        }

        // Determine if the mobile theme is being used.
        $theme = \core_useragent::get_device_type_theme();

        $data['theme']  = $theme;

        $data['width'] = KALTURA_IMAGE_MOBILE_WIDTH;
        $data['height'] = KALTURA_IMAGE_MOBILE_HEIGHT;

        $markup = '';

        if (\KalturaMediaType::IMAGE == $entryobj->mediaType) {
            $data['image'] = true;
            $data['image_alt'] .= $kalmediares->name;

            $markup = local_yukaltura_create_image_markup($entryobj, $kalmediares->name, $theme,
                                                          KALTURA_IMAGE_MOBILE_WIDTH, KALTURA_IMAGE_MOBILE_HEIGHT);
            preg_match('/src\s*=\s*"[^"]*"/', $markup, $matches);
            $imagesrc = $matches[0];
            $index = strpos($imagesrc, '"');
            $imagesrc = substr($imagesrc , $index + 1, strlen($imagesrc) - $index - 2);
            $data['image_src'] = $imagesrc;

            preg_match('/width\s*=\s*"[^"]*"/', $markup, $matches);
            $imagewidth = $matches[0];
            $index = strpos($imagewidth, '"');
            $imagewidth = substr($imagewidth , $index + 1, strlen($imagewidth) - $index - 2);
            $data['image_width'] = $imagewidth;

            preg_match('/height\s*=\s*"[^"]*"/', $markup, $matches);
            $imageheight = $matches[0];
            $index = strpos($imageheight, '"');
            $imageheight = substr($imageheight , $index + 1, strlen($imageheight) - $index - 2);
            $data['image_height'] = $imageheight;

        } else {
            $playertype = local_yukaltura_get_player_type($kalmediares->uiconf_id, $connection);

            if ($playertype == KALTURA_TV_PLATFORM_STUDIO) {
                $data['ovp'] = true;
            } else {
                $data['html5'] = true;
            }
        }

        $data['stats_warning'] = get_string('app_stats_warning', 'kalmediares');

        $json['data'] = $data;
        $json['js'] = true;

        return [
            'templates' => [
                [
                    'id' => 'main',
                    'html' => $OUTPUT->render_from_template('mod_kalmediares/mobile_view_page', $json),
                ],
            ],

            'javascript' => '',
            'otherdata' => ''
        ];
    }
}
