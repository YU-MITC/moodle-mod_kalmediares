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
 * This file keeps track of upgrades to the chat module.
 *
 * Sometimes, changes between versions involve alterations to database structures
 * and other major things that may break installations.
 * The upgrade function in this file will attempt to perform all the necessary actions
 * to upgrade your older installation to the current version.
 * If there's something it cannot do itself, it will tell you what you need to do.
 * The commands in here will all be database-neutral,
 * using the methods of database_manager class.
 * Please do not forget to use upgrade_set_timeout()
 * before any action that may take longer time to finish.
 *
 * @package   mod_kalmediaassign
 * @copyright (C) 2010 onwards  Aparup Banerjee  http://moodle.com
 * @copyright (C) 2016-2025 Yamaguchi University <gh-cc@mlex.cc.yamaguchi-u.ac.jp>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Upgrade Kaltura Media assign object.
 *
 * @param int $oldversion - version number of old version plugin.
 * @return bool - this function always return "true".
 */
function xmldb_kalmediaassign_upgrade($oldversion) {
    global $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2011091301) {

        // Changing type of field intro on table kalmediaassign to text.
        $table = new xmldb_table('kalmediaassign');
        $field = new xmldb_field('intro', XMLDB_TYPE_TEXT, 'small', null, null, null, null, 'name');

        // Launch change of type for field intro.
        $dbman->change_field_type($table, $field);

        // Records of kalmediaassign savepoint reached.
        upgrade_mod_savepoint(true, 2011091301, 'kalmediaassign');
    }

    if ($oldversion < 2019022000) {
        $table = new xmldb_table('kalmediaassign');
        $field = new xmldb_field('alwaysshowdescription');
        if (!$dbman->field_exists($table, $field)) {
             $field->set_attributes(XMLDB_TYPE_INTEGER, '2', null, XMLDB_NOTNULL, null, '0', 'timedue');
             $field->setDefault('0');
             $dbman->add_field($table, $field);
        }

        // Plugin kalmediares savepoint reached.
        upgrade_mod_savepoint(true, 2019022000, 'kalmediaassign');
    }

    return true;
}

