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
 * YU Kaltura media assignment form.
 *
 * @package   mod_kalmediaassign
 * @copyright (C) 2016-2025 Yamaguchi University <gh-cc@mlex.cc.yamaguchi-u.ac.jp>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
require_once(dirname(dirname(dirname(__FILE__))) . '/course/moodleform_mod.php');

if (!defined('MOODLE_INTERNAL')) {
    // It must be included from a Moodle page.
    die('Direct access to this script is forbidden.');
}

header('Access-Control-Allow-Origin: *');
header('Cache-Control: no-cache');

require_login();

/**
 * class of YU Kaltura Media assignment grade/submission form.
 * @package   mod_kalmediaassign
 * @copyright (C) 2016-2025 Yamaguchi University <gh-cc@mlex.cc.yamaguchi-u.ac.jp>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_kalmediaassign_mod_form extends moodleform_mod {

    /**
     * This function outputs a submission information form.
     */
    public function definition() {
        global $CFG, $COURSE;

        $mform =& $this->_form;

        $mform->addElement('hidden', 'course', $COURSE->id);

        $mform->addElement('header', 'general', get_string('general', 'form'));

        $mform->addElement('text', 'name', get_string('name', 'kalmediaassign'), array('size' => '64'));

        if (!empty($CFG->formatstringstriptags)) {
            $mform->setType('name', PARAM_TEXT);
        } else {
            $mform->setType('name', PARAM_CLEANHTML);
        }
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');

        $this->standard_intro_elements(get_string('description', 'kalmediaassign'));

        $mform->addElement('date_time_selector', 'timeavailable',
                           get_string('availabledate', 'kalmediaassign'),
                           array('optional' => true));
        $mform->setDefault('timeavailable', time());
        $mform->addElement('date_time_selector', 'timedue',
                           get_string('duedate', 'kalmediaassign'),
                           array('optional' => true));
        $mform->setDefault('timedue', time() + 7 * 24 * 3600);

        $name = get_string('alwaysshowdescription', 'kalmediaassign');
        $mform->addElement('checkbox', 'alwaysshowdescription', $name);
        $mform->addHelpButton('alwaysshowdescription', 'alwaysshowdescription', 'kalmediaassign');
        $mform->disabledIf('alwaysshowdescription', 'timeavailable[enabled]', 'notchecked');

        $mform->addElement('header', 'submissionsettings', get_string('submissionsettings_hdr', 'kalmediaassign'));

        $ynoptions = array( 0 => get_string('no'), 1 => get_string('yes'));

        $mform->addElement('select', 'preventlate', get_string('preventlate', 'kalmediaassign'), $ynoptions);
        $mform->setDefault('preventlate', 0);

        $mform->addElement('select', 'resubmit', get_string('allowdeleting', 'kalmediaassign'), $ynoptions);
        $mform->addHelpButton('resubmit', 'allowdeleting', 'kalmediaassign');
        $mform->setDefault('resubmit', 0);

        $mform->addElement('select', 'emailteachers', get_string('emailteachers', 'kalmediaassign'), $ynoptions);
        $mform->addHelpButton('emailteachers', 'emailteachers', 'kalmediaassign');
        $mform->setDefault('emailteachers', 0);

        $this->standard_grading_coursemodule_elements();

        $this->standard_coursemodule_elements();

        $this->add_action_buttons();
    }
}
