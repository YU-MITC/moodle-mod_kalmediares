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
 * Kaltura media assignment grade preferences form
 *
 * @package   mod_kalmediaassign
 * @copyright (C) 2016-2025 Yamaguchi University <gh-cc@mlex.cc.yamaguchi-u.ac.jp>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
require_once(dirname(dirname(dirname(__FILE__))) . '/course/moodleform_mod.php');
require_once(dirname(__FILE__) . '/locallib.php');
require_once($CFG->libdir.'/formslib.php');

if (!defined('MOODLE_INTERNAL')) {
    // It must be included from a Moodle page.
    die('Direct access to this script is forbidden.');
}

global $PAGE, $COURSE;

$PAGE->set_url('/mod/kalmediaassign/grade_preferences_form.php');

require_login();

/**
 * Grade preferencees class of mod_kalmediassign
 * @package   mod_kalmediaassign
 * @copyright (C) 2016-2025 Yamaguchi University <gh-cc@mlex.cc.yamaguchi-u.ac.jp>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class kalmediaassign_gradepreferences_form extends moodleform {

    /**
     * This function outputs a grade submission form.
     */
    public function definition() {
        global $COURSE, $USER;

        $mform =& $this->_form;

        $mform->addElement('hidden', 'cmid', $this->_customdata['cmid']);
        $mform->setType('cmid', PARAM_INT);

        $mform->addElement('header', 'kal_media_subm_hdr', get_string('optionalsettings', 'kalmediaassign'));

        $context = context_module::instance($this->_customdata['cmid']);

        $groupopt = array();
        $groups = array();

        // If the user doesn't have access to all group print the groups they have access to.
        if (!has_capability('moodle/site:accessallgroups', $context)) {

            // Determine the groups mode.
            switch($this->_customdata['groupmode']) {
                case NOGROUPS:
                    // No groups, do nothing.
                    break;
                case SEPARATEGROUPS:
                    $groups = groups_get_all_groups($COURSE->id, $USER->id);
                    break;
                case VISIBLEGROUPS:
                    $groups = groups_get_all_groups($COURSE->id, $USER->id);
                    break;
            }

            $groupopt[0] = get_string('all', 'mod_kalmediaassign');

            foreach ($groups as $groupobj) {
                $groupopt[$groupobj->id] = $groupobj->name;
            }

        } else {
            $groups = groups_get_all_groups($COURSE->id);

            $groupopt[0] = get_string('all', 'mod_kalmediaassign');

            foreach ($groups as $groupobj) {
                $groupopt[$groupobj->id] = $groupobj->name;
            }

        }

        $mform->addElement('select', 'group_filter', get_string('group_filter', 'mod_kalmediaassign'), $groupopt);

        $filters = array(KALASSIGN_FILTER_ALL => get_string('all', 'kalmediaassign'),
                                KALASSIGN_FILTER_REQ_GRADING => get_string('reqgrading', 'kalmediaassign'),
                                KALASSIGN_FILTER_SUBMITTED => get_string('submitted', 'kalmediaassign'),
                                KALASSIGN_FILTER_NOTSUBMITTEDYET => get_string('notsubmittedyet', 'kalmediaassign'));

        $mform->addElement('select', 'filter', get_string('show'), $filters);
        $mform->addHelpButton('filter', 'show', 'kalmediaassign');

        $mform->addElement('text', 'perpage', get_string('pagesize', 'kalmediaassign'), array('size' => 3, 'maxlength' => 3));
        $mform->setType('perpage', PARAM_INT);
        $mform->addHelpButton('perpage', 'pagesize', 'kalmediaassign');

        $mform->addElement('checkbox', 'quickgrade', get_string('quickgrade', 'kalmediaassign'));
        $mform->setDefault('quickgrade', '');
        $mform->addHelpButton('quickgrade', 'quickgrade', 'kalmediaassign');

        $savepref = get_string('savepref', 'kalmediaassign');

        $mform->addElement('submit', 'savepref', $savepref);

    }

    /**
     * This function validates submissons.
     * @param array $data - form data.
     * @param array $files - form data.
     * @return $string - error messages (if no error occurs, return null).
     */
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        if (0 == (int) $data['perpage']) {
            $errors['perpage'] = get_string('invalidperpage', 'kalmediaassign');
        }

        return $errors;
    }
}
