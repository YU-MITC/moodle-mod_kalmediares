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
 * @package    mod_kalmediaassign
 * @subpackage backup-moodle2
 * @copyright  (C) 2010 onwards Eloy Lafuente (stronk7) {@link http://stronk7.com}
 * @copyright  (C) 2016-2025 Yamaguchi University <gh-cc@mlex.cc.yamaguchi-u.ac.jp>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Define all the backup steps that will be used by the backup_kalmediaassign_activity_task.
 */

/**
 * Define the complete kalmediaassign structure for backup, with file and id annotations.
 * @package    mod_kalmediaassign
 * @subpackage backup-moodle2
 * @copyright  (C) 2010 onwards Eloy Lafuente (stronk7) {@link http://stronk7.com}
 * @copyright  (C) 2016-2025 Yamaguchi University <gh-cc@mlex.cc.yamaguchi-u.ac.jp>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class backup_kalmediaassign_activity_structure_step extends backup_activity_structure_step {

    /**
     * Define (add) particular settings this activity can have.
     * @return object - define structure.
     */
    protected function define_structure() {

        // To know if we are including userinfo.
        $userinfo = $this->get_setting_value('userinfo');

        // Define each element separated.
        $kalmediaassign = new backup_nested_element('kalmediaassign', array('id'), array(
            'course', 'name', 'intro', 'introformat', 'timeavailable', 'timedue', 'alwaysshowdescription',
            'preventlate', 'resubmit', 'emailteachers', 'grade', 'timecreated', 'timemodified'));

        $issues = new backup_nested_element('submissions');

        $issue = new backup_nested_element('submission', array('id'), array(
            'userid', 'entry_id', 'grade', 'submissioncomment', 'format',
            'teacher', 'mailed', 'timemarked', 'timecreated', 'timemodified'));

        // Build the tree.
        $kalmediaassign->add_child($issues);
        $issues->add_child($issue);

        // Define sources.
        $kalmediaassign->set_source_table('kalmediaassign', array('id' => backup::VAR_ACTIVITYID));

        // All the rest of elements only happen if we are including user info.
        if ($userinfo) {
            $issue->set_source_table('kalmediaassign_submission', array('mediaassignid' => backup::VAR_PARENTID));
        }

        // Annotate the user id's where required.
        $issue->annotate_ids('user', 'userid');

        // Annotate the file areas in use.
        $kalmediaassign->annotate_files('mod_kalmediaassign', 'intro', null);

        // Return the root element, wrapped into standard activity structure.
        return $this->prepare_activity_structure($kalmediaassign);
    }
}
