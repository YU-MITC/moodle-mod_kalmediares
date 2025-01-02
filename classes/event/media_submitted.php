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
 * The media_submitted event.
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
class media_submitted extends \core\event\base {

    /**
     * This function set default value.
     */
    protected function init() {
        /*
         * Select flags. c(reate), r(ead), u(pdate), d(elete).
         */
        $this->data['crud'] = 'c';
        $this->data['edulevel'] = self::LEVEL_PARTICIPATING;
        $this->data['objecttable'] = 'kalmediaassign';
    }

    /**
     * This function return event name.
     * @return string - event name.
     */
    public static function get_name() {
        return get_string('event_media_submitted', 'kalmediaassign');
    }

    /**
     * This function return description of submission.
     * @return string - description of event.
     */
    public function get_description() {
        return "The user with id '{$this->userid}' submitted a media for submission with "
        . "the course module id '{$this->contextinstanceid}'.";
    }

    /**
     * This function return object url.
     * @return string - URL of target submission.
     */
    public function get_url() {
        return new \moodle_url('/mod/kalmediaassign/view.php', array('id' => $this->contextinstanceid));
    }

    /**
     * This function return object url.
     * @return array - log data.
     */
    public function get_legacy_logdata() {
        return array($this->courseid, 'kalmediaassign', 'media submitted',
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
