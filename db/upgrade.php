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
 * @copyright (C) 2016-2023 Yamaguchi University <gh-cc@mlex.cc.yamaguchi-u.ac.jp>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Execute newmodule upgrade from the given old version
 *
 * @param int $oldversion - version number of old plugin.
 * @return bool - this function always return true.
 */
function xmldb_kalmediares_upgrade($oldversion) {
    global $DB;

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

    if ($oldversion < 2018051400) {
        $table = new xmldb_table('kalmediares');
        $field = new xmldb_field('publish_access_log');
        if (!$dbman->field_exists($table, $field)) {
            $field->set_attributes(XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0', 'internal');
            $field->setDefault('0');
            $dbman->add_field($table, $field);
        }
        $field = new xmldb_field('exclusion_time');
        if (!$dbman->field_exists($table, $field)) {
            $field->set_attributes(XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0', 'publish_access_log');
            $field->setDefault('0');
            $dbman->add_field($table, $field);
        }

        // Plugin kalmediares savepoint reached.
        upgrade_mod_savepoint(true, 2018051400, 'kalmediares');
    }

    if ($oldversion < 2019051400) {
        $table = new xmldb_table('kalmediares_log');
        if (!$dbman->table_exists($table)) {
            $field1 = new xmldb_field('id');
            $field1->set_attributes(XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null, null);

            $field2 = new xmldb_field('instanceid');
            $field2->set_attributes(XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, 0, 'id');
            $field2->setDefault('0');

            $field3 = new xmldb_field('userid');
            $field3->set_attributes(XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, 0, 'instanceid');
            $field3->setDefault('0');

            $field4 = new xmldb_field('plays');
            $field4->set_attributes(XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, 0, 'userid');
            $field4->setDefault('0');

            $field5 = new xmldb_field('views');
            $field5->set_attributes(XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, 0, 'plays');
            $field5->setDefault('0');

            $field6 = new xmldb_field('first');
            $field6->set_attributes(XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, 0, 'views');
            $field6->setDefault('0');

            $field7 = new xmldb_field('last');
            $field7->set_attributes(XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, 0, 'first');
            $field7->setDefault('0');

            $key = new xmldb_key('primary');
            $key->set_attributes(XMLDB_KEY_PRIMARY, array('id'), null, null);

            $table->addField($field1);
            $table->addField($field2);
            $table->addField($field3);
            $table->addField($field4);
            $table->addField($field5);
            $table->addField($field6);
            $table->addField($field7);
            $table->addKey($key);

            $dbman->create_table($table);
        }

        // Plugin kalmediares savepoint reached.
        upgrade_mod_savepoint(true, 2019051400, 'kalmediares');
    }

    return true;
}
