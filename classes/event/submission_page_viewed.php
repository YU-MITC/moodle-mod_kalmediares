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
 * The submission_page_viewed event.
 *
 * @package   mod_kalmediaassign
 * @copyright (C) 2016-2025 Yamaguchi University <gh-cc@mlex.cc.yamaguchi-u.ac.jp>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_kalmediaassign\event;

/**
 * Event class of YU Kaltura Media assign.
 *
 * @package   mod_kalmediaassign
 * @copyright (C) 2016-2025 Yamaguchi University <gh-cc@mlex.cc.yamaguchi-u.ac.jp>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class submission_page_viewed extends \core\event\base {
    /**
     * This function set default value.
     */
    protected function init() {
        // Select flags. c(reate), r(ead), u(pdate), d(elete).
        $this->data['crud'] = 'r';
        $this->data['edulevel'] = self::LEVEL_PARTICIPATING;
        $this->data['objecttable'] = 'kalmediaassign';
    }

    /**
     * This function return event name.
     * @return string - event name.
     */
    public static function get_name() {
        return get_string('event_submission_page_viewed', 'kalmediaassign');
    }

    /**
     * This function return description of submission.
     * @return string - description of event.
     */
    public function get_description() {
        return "The user with id $this->userid viewed the submission page of Kaltura media assign with "
        . "the course module id $this->contextinstanceid.";
    }

    /**
     * This function return object url.
     * @return string - URL of target submission.
     */
    public function get_url() {
        return new \moodle_url('/mod/kalmediaassign/grade_submissions.php', array('cmid' => $this->contextinstanceid));
    }

    /**
     * This function return object url.
     * @return array - log data.
     */
    public function get_legacy_logdata() {
        return array($this->courseid, 'kalmediaassign', 'view media submission page',
            $this->get_url(), $this->objectid, $this->contextinstanceid);
    }

    /**
     * Return objectid mapping.
     *
     * @return array - object mapping.
     */
    public static function get_objectid_mapping() {
        return array('db' => 'kalmediaassign', 'restore' => 'kalmediaassign');
    }
}
