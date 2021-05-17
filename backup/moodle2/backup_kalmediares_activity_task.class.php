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
 * Backup activity script.
 * @package    mod_kalmediares
 * @subpackage backup-moodle2
 * @copyright  (C) 2010 onwards Eloy Lafuente (stronk7) {@link http://stronk7.com}
 * @copyright  (C) 2016-2021 Yamaguchi University <gh-cc@mlex.cc.yamaguchi-u.ac.jp>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

// Because it exists (must).
require_once(dirname(__FILE__) . '/backup_kalmediares_stepslib.php');
// Because it exists (must).
require_once(dirname(__FILE__) . '/backup_kalmediares_settingslib.php');

/**
 * kalmediares backup task.
 * @package    mod_kalmediares
 * @subpackage backup-moodle2
 * @copyright  (C) 2010 onwards Eloy Lafuente (stronk7) {@link http://stronk7.com}
 * @copyright  (C) 2016-2021 Yamaguchi University <gh-cc@mlex.cc.yamaguchi-u.ac.jp>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class backup_kalmediares_activity_task extends backup_activity_task {

    /**
     * Define (add) particular settings this activity can have.
     */
    protected function define_my_settings() {
        // No particular settings for this activity.
    }

    /**
     * Define (add) particular steps this activity can have.
     */
    protected function define_my_steps() {
        // Choice only has one structure step.
        $this->add_step(new backup_kalmediares_activity_structure_step('kalmediares_structure', 'kalmediares.xml'));
    }

    /**
     * Code the transformations to perform in the activity in
     * order to get transportable (encoded) links.
     * @param string $content - link URL of content.
     * @return string - Encoded URL of content.
     */
    public static function encode_content_links($content) {
        global $CFG;

        $base = preg_quote($CFG->wwwroot, "/");

        // Link to the list of kalmediaress.
        $search = "/(". $base . "\/mod\/kalmediares\/index.php\?id\=)([0-9]+)/";
        $content = preg_replace($search, '$@KALMEDIARESINDEX*$2@$', $content);

        // Link to kalmediares view by moduleid.
        $search = "/(" . $base . "\/mod\/kalmediares\/view.php\?id\=)([0-9]+)/";
        $content = preg_replace($search, '$@KALMEDIARESVIEWBYID*$2@$', $content);

        return $content;
    }
}
