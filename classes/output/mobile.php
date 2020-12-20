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
 * Contains the mobile output class for the attendance
 *
 * @package   mod_attendance
 * @copyright 2018 Dan Marsden
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_kalmediares\output;

require_once(dirname(dirname(dirname(dirname(dirname(__FILE__))))) . '/config.php');
require_once(dirname(dirname(dirname(dirname(dirname(__FILE__)) ))). '/local/yukaltura/locallib.php');

defined('MOODLE_INTERNAL') || die();
/**
 * Mobile output class for the attendance.
 *
 * @copyright 2018 Dan Marsden
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mobile
{
    /* Returns the initial page when viewing the activity for the mobile app.
     *
     * @param  array $args Arguments from tool_mobile_get_content WS
     * @return array HTML, javascript and other data
     */
    public static function mobile_view_activity($args)
    {
        global $OUTPUT, $DB;

        $cmid = $args['cmid'];
        if (! $cm = get_coursemodule_from_id('kalmediares', $cmid)) {
            print_error('invalid_coursemodule', 'kalmediares');
        }

        if (! $course = $DB->get_record('course', array('id' => $cm->course))) {
            print_error('course_misconf');
        }

        if (! $kalmediares = $DB->get_record('kalmediares', array('id' => $cm->instance))) {
            print_error('invalid_id', 'kalmediares');
        }

        // Try connection.
        $kaltura = new \yukaltura_connection();
        $connection = $kaltura->get_connection(false, true, KALTURA_SESSION_LENGTH);
        $data = [];
        if ($connection) {
            $media = $connection->media->get($kalmediares->entry_id);
            $data = [];
            if ($media !== null) {

                if (local_yukaltura_get_player_override() &&
                    $kalmediares->uiconf_id != local_yukaltura_get_player_uiconf('player_resource')) {
                    $newplayer = local_yukaltura_get_player_uiconf('player_resource');
                    $kalmediares->uiconf_id = $newplayer;
                }

                $userId = $args['userid'];
                $student = false;
                $teacher = false;

                $coursecontext = \context_course::instance($args['courseid']);
                $roles = get_user_roles($coursecontext, $userId);
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
                            array('instanceid' => $cm->instance, 'userid' => $userId));
                        $now = time();
                        if (empty($kalmediareslog)) {
                            $objectdata = array('instanceid' => $cm->instance, 'userid' => $userId, 'plays' => 0, 'views' => 1,
                                'first' => $now, 'last' => $now);
                            $DB->insert_record('kalmediares_log', $objectdata);
                        } else {
                            $kalmediareslog->last = $now;
                            $kalmediareslog->views = $kalmediareslog->views + 1;
                            $DB->update_record('kalmediares_log', $kalmediareslog, false);
                        }
                    } catch (Exception $ex) {

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
                    $data['entryobj'] = $entryobj;
                    $data['uiconf'] = $uiconf;
                } else {
                    $data['error'] = true;
                    $data['message'] = 'mod_kalmediares.media_converting';
                }
            }else{
                $data['error'] = true;
                $data['message'] = 'mod_kalmediares.no_media';
                $data['data'] = $kalmediares->entry_id;
            }
        } else{
            $data['error'] = true;
            $data['message'] = 'local_yukaltura.conn_failed_alt';
        }


        return [
            'templates' => [
                [
                    'id' => 'main',
                    'html' => $OUTPUT->render_from_template('mod_kalmediares/mobile_view_page', $data),
                ],
            ],
            'javascript' => '',
            'otherdata' => ''
        ];
    }
}