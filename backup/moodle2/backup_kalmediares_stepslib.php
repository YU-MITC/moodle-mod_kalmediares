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
 * @package    moodlecore
 * @subpackage backup-moodle2
 * @copyright  (C) 2010 onwards Eloy Lafuente (stronk7) {@link http://stronk7.com}
 * @copyright  (C) 2016-2017 Yamaguchi University <info-cc@ml.cc.yamaguchi-u.ac.jp>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(dirname(dirname(dirname(dirname(__FILE__))))) . '/config.php');

if (!defined('MOODLE_INTERNAL')) {
    // It must be included from a Moodle page.
    die('Direct access to this script is forbidden.');
}

/**
 * Define all the backup steps that will be used by the backup_kalmediares_activity_task
 */

/**
 * Define the complete kalmediares structure for backup, with file and id annotations
 */
class backup_kalmediares_activity_structure_step extends backup_activity_structure_step {

    protected function define_structure() {

        // Define each element separated.
        $kalmediares = new backup_nested_element('kalmediares', array('id'), array(
            'name', 'intro', 'introformat', 'entry_id', 'media_title',
            'uiconf_id', 'widescreen', 'height', 'width', 'timemodified', 'timecreated'));

        // Define sources.
        $kalmediares->set_source_table('kalmediares', array('id' => backup::VAR_ACTIVITYID));

        // Return the root element, wrapped into standard activity structure.
        return $this->prepare_activity_structure($kalmediares);
    }
}
