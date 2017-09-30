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
 * This file keeps track of upgrades to the newmodule module
 *
 * Sometimes, changes between versions involve alterations to database
 * structures and other major things that may break installations. The upgrade
 * function in this file will attempt to perform all the necessary actions to
 * upgrade your older installation to the current version. If there's something
 * it cannot do itself, it will tell you what you need to do.  The commands in
 * here will all be database-neutral, using the functions defined in DLL libraries.
 *
 * @package   mod_kalmediares
 * @copyright (C) 2016-2017 Yamaguchi University <info-cc@ml.cc.yamaguchi-u.ac.jp>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(dirname(dirname(dirname(__FILE__)))) . '/config.php');

if (!defined('MOODLE_INTERNAL')) {
    // It must be included from a Moodle page.
    die('Direct access to this script is forbidden.');
}

/**
 * Execute newmodule upgrade from the given old version
 *
 * @param int $oldversion - version number of old plugin.
 * @return bool - this function always return true.
 */
function xmldb_kalmediares_upgrade($oldversion) {
    global $CFG, $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2016041000) {

        // Changing type of field intro on table kalmediares to text.
        $table = new xmldb_table('kalmediares');
        $field = new xmldb_field('intro', XMLDB_TYPE_TEXT, 'small', null, null, null, null, 'name');

        // Launch change of type for field intro.
        $dbman->change_field_type($table, $field);

        // Plugin kalmediares savepoint reached.
        upgrade_mod_savepoint(true, 2016041000, 'kalmediares');
    }

    if ($oldversion < 2017051202) {
        $table = new xmldb_table('kalmediares');
        $field = new xmldb_field('internal');
        if (!$dbman->field_exists($table, $field)) {
             $field->set_attributes(XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0', 'width');
             $field->setDefault('0');
             $dbman->add_field($table, $field);
        }

        // Plugin kalmediares savepoint reached.
        upgrade_mod_savepoint(true, 2017051202, 'kalmediares');
    }

    return true;
}
