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
 * Backup step script.
 * @package    mod_kalmediares
 * @subpackage backup-moodle2
 * @copyright  (C) 2010 onwards Eloy Lafuente (stronk7) {@link http://stronk7.com}
 * @copyright  (C) 2016-2019 Yamaguchi University <gh-cc@mlex.cc.yamaguchi-u.ac.jp>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Define all the backup steps that will be used by the backup_kalmediares_activity_task
 */

/**
 * Define the complete kalmediares structure for backup, with file and id annotations.
 * @package    mod_kalmediares
 * @subpackage backup-moodle2
 * @copyright  (C) 2010 onwards Eloy Lafuente (stronk7) {@link http://stronk7.com}
 * @copyright  (C) 2016-2018 Yamaguchi University <gh-cc@mlex.cc.yamaguchi-u.ac.jp>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class backup_kalmediares_activity_structure_step extends backup_activity_structure_step {

    /**
     * Define (add) particular settings this resource can have.
     * @return object - define structure.
     */
    protected function define_structure() {

        // To know if we are including userinfo.
        $userinfo = $this->get_setting_value('userinfo');

        // Define each element separated.
        $kalmediares = new backup_nested_element('kalmediares', array('id'), array(
            'name', 'intro', 'introformat', 'entry_id', 'media_title',
            'uiconf_id', 'widescreen', 'height', 'width', 'internal', 'publish_access_log',
            'exclusion_time', 'timemodified', 'timecreated'));

        $logs = new backup_nested_element('logs');

        $log = new backup_nested_element('log', array('id'), array(
            'instanceid', 'userid', 'plays', 'views', 'first', 'last'));

        // Build tree.
        $kalmediares->add_child($logs);
        $logs->add_child($log);

        // Define sources.
        $kalmediares->set_source_table('kalmediares', array('id' => backup::VAR_ACTIVITYID));

        // All the rest of elements only happen if we are including user info.
        if ($userinfo) {
            $log->set_source_table('kalmediares_log', array('instanceid' => backup::VAR_PARENTID));
        }

        // Annotate the user id's where required.
        $log->annotate_ids('user', 'userid');

        // Define file annotations.
        // This file area do not have an itemdid.
        $kalmediares->annotate_files('mod_kalmediares', 'intro', null);

        // Return the root element, wrapped into standard activity structure.
        return $this->prepare_activity_structure($kalmediares);
    }
}
