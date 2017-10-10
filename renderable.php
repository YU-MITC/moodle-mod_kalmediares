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
 * This file contains the definition for the renderable classes for the resource
 *
 * @package    mod_kalmediares
 * @copyright  (C) 2016-2017 Yamaguchi University <info-cc@ml.cc.yamaguchi-u.ac.jp>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');

defined('MOODLE_INTERNAL') || die();

require_login();

/**
 * Renderable course index summary.
 * @package    mod_kalmediares
 * @copyright  (C) 2016-2017 Yamaguchi University <info-cc@ml.cc.yamaguchi-u.ac.jp>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class kalmediares_course_index_summary implements renderable {
    /** @var array resources A list of course module info and submission counts or statuses */
    public $resourcess = array();
    /** @var boolean usesections Does this course format support sections? */
    public $usesections = false;
    /** @var string courseformat The current course format name */
    public $courseformatname = '';

    /**
     * constructor
     *
     * @param bool $usesections - True if this course format uses sections
     * @param string $courseformatname - The id of this course format
     */
    public function __construct($usesections, $courseformatname) {
        $this->usesections = $usesections;
        $this->courseformatname = $courseformatname;
    }

    /**
     * Add a row of data to display on the course index page
     *
     * @param int $cmid - The course module id for generating a link
     * @param string $cmname - The course module name for generating a link
     * @param string $sectionname - The name of the course section (only if $usesections is true)
     */
    public function add_resource_info($cmid, $cmname, $sectionname) {
        $this->resources[] = array(
            'cmid' => $cmid,
            'cmname' => $cmname,
            'sectionname' => $sectionname,
        );
    }
}
