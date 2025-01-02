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
 * Kaltura media assignment single submission form
 *
 * @package   mod_kalmediaassign
 * @copyright (C) 2016-2025 Yamaguchi University <gh-cc@mlex.cc.yamaguchi-u.ac.jp>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
require_once(dirname(dirname(dirname(__FILE__))) . '/course/moodleform_mod.php');
require_once(dirname(dirname(dirname(__FILE__))) . '/local/yukaltura/locallib.php');

if (!defined('MOODLE_INTERNAL')) {
    // It must be included from a Moodle page.
    die('Direct access to this script is forbidden.');
}

header('Access-Control-Allow-Origin: *');
header('Cache-Control: no-cache');

require_login();

/**
 * Class for display single submission form.
 * @package   mod_kalmediaassign
 * @copyright (C) 2016-2025 Yamaguchi University <gh-cc@mlex.cc.yamaguchi-u.ac.jp>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class kalmediaassign_singlesubmission_form extends moodleform {

    /**
     * This function defines the forums elements that are to be displayed.
     */
    public function definition() {
        global $CFG;

        $mform =& $this->_form;

        $cm = $this->_customdata->cm;
        $userid = $this->_customdata->userid;

        $mform->addElement('hidden', 'cmid', $cm->id);
        $mform->setType('cmid', PARAM_INT);
        $mform->addelement('hidden', 'userid', $userid);
        $mform->setType('userid', PARAM_INT);
        $mform->addElement('hidden', 'tifirst', $this->_customdata->tifirst);
        $mform->setType('tifirst', PARAM_TEXT);
        $mform->addElement('hidden', 'tilast', $this->_customdata->tilast);
        $mform->setType('tilast', PARAM_TEXT);
        $mform->addElement('hidden', 'page', $this->_customdata->page);
        $mform->setType('page', PARAM_INT);

        /* Submission section */
        $mform->addElement('header', 'single_submission_1', get_string('submission', 'kalmediaassign'));

        $mform->addelement('static', 'submittinguser',
                           $this->_customdata->submissionuserpic,
                           $this->_customdata->submissionuserinfo);

        /* Media preview */
        $mform->addElement('header', 'single_submission_2', get_string('previewmedia', 'kalmediaassign'));

        $submission  = $this->_customdata->submission;
        $gradinginfo = $this->_customdata->grading_info;
        $entryobject = '';

        if (!empty($submission->entry_id)) {

            $kaltura = new yukaltura_connection();
            $connection = $kaltura->get_connection(false, true, KALTURA_SESSION_LENGTH);

            if ($connection) {
                $entryobject = local_yukaltura_get_ready_entry_object($this->_customdata->submission->entry_id);

                // Determine the type of media (See KALDEV-28).
                if (!local_yukaltura_media_type_valid($entryobject)) {
                    $entryobject = local_yukaltura_get_ready_entry_object($entryobject->id, false);
                }
            }

        }

        if (!empty($entryobject)) {

            // Set the session.
            $session = local_yukaltura_generate_kaltura_session(true, array($entryobject->id));

            // Determine if the mobile theme is being used.
            $theme = core_useragent::get_device_type_theme();

            // Get uiconfid for presentation.
            $uiconfid = local_yukaltura_get_player_uiconf('player');

            $markup = '';

            if (KalturaMediaType::IMAGE == $entryobject->mediaType) {
                $markup = local_yukaltura_create_image_markup($entryobject, $entryobject->name, $theme);
            } else {
                list($entryobject->width, $entryobject->height) = kalmediaassign_get_player_dimensions();
                $playertype = local_yukaltura_get_player_type($uiconfid, $connection);
                if ($playertype == KALTURA_TV_PLATFORM_STUDIO) {
                    $markup = local_yukaltura_get_dynamicembed_code($entryobject, $uiconfid, $connection, $session);
                } else {
                    $markup = local_yukaltura_get_kwidget_code($entryobject, $uiconfid, $session);
                }
            }

            $mform->addElement('static', 'description', get_string('submission', 'kalmediaassign'), $markup);

        } else if (empty($entryobject) && isset($submission->timemodified) && !empty($submission->timemodified)) {

            if ($connection) {
                // An empty entry object and a time modified timestamp means the media is still converting.
                $mform->addElement('static', 'description', get_string('submission', 'kalmediaassign'),
                                   get_string('media_converting', 'local_yukaltura'));
            } else {

                $mform->addElement('static', 'description', get_string('submission', 'kalmediaassign'),
                                   get_string('conn_failed_alt', 'local_yukaltura'));
            }
        } else {

            // An empty entry object and an empty time modified tamstamp mean the student hasn't submitted anything.
            $mform->addElement('static', 'description', get_string('submission', 'kalmediaassign'),
                               '');
        }

        // Grades section.
        $mform->addElement('header', 'single_submission_3', get_string('grades', 'kalmediaassign'));

        $attributes = array();

        if ($this->_customdata->gradingdisabled || $this->_customdata->gradingdisabled) {
            $attributes['disabled'] = 'disabled';
        }

        $grademenu = make_grades_menu($this->_customdata->cminstance->grade);
        $grademenu['-1'] = get_string('nograde');

        $mform->addElement('select', 'xgrade', get_string('gradenoun').':', $grademenu, $attributes);

        if (isset($submission->grade)) {
            // Fixme some bug when element called 'grade' makes it break.
            $mform->setDefault('xgrade', $this->_customdata->submission->grade );
        } else {
            // Fixme some bug when element called 'grade' makes it break.
            $mform->setDefault('xgrade', '-1' );
        }

        $mform->setType('xgrade', PARAM_INT);

        if (!empty($this->_customdata->enableoutcomes) && !empty($gradinginfo)) {

            foreach ($gradinginfo->outcomes as $n => $outcome) {

                $options = make_grades_menu(-$outcome->scaleid);

                if (array_key_exists($this->_customdata->userid, $outcome->grades) &&
                    $outcome->grades[$this->_customdata->userid]->locked) {

                    $options[0] = get_string('nooutcome', 'grades');
                    echo $options[$outcome->grades[$this->_customdata->userid]->grade];

                } else {

                    $options[''] = get_string('nooutcome', 'grades');
                    $attributes = array('id' => 'menuoutcome_'.$n );
                    $mform->addElement('select', 'outcome_'.$n.'['.$this->_customdata->userid.']',
                                       $outcome->name.':', $options, $attributes );
                    $mform->setType('outcome_'.$n.'['.$this->_customdata->userid.']', PARAM_INT);

                    if (array_key_exists($this->_customdata->userid, $outcome->grades)) {
                        $mform->setDefault('outcome_'.$n.'['.$this->_customdata->userid.']',
                                           $outcome->grades[$this->_customdata->userid]->grade );
                    }
                }
            }
        }

        if (has_capability('gradereport/grader:view', $this->_customdata->context) &&
            has_capability('moodle/grade:viewall', $this->_customdata->context)) {

            if (empty($gradinginfo) || !array_key_exists($this->_customdata->userid, $gradinginfo->items[0]->grades)) {

                $grade = ' - ';

            } else if (0 != strcmp('-', $gradinginfo->items[0]->grades[$this->_customdata->userid]->str_grade)) {

                $grade = '<a href="'.$CFG->wwwroot.'/grade/report/grader/index.php?id='. $this->_customdata->cm->course .'" >'.
                            $this->_customdata->grading_info->items[0]->grades[$this->_customdata->userid]->str_grade . '</a>';
            } else {

                $grade = $this->_customdata->grading_info->items[0]->grades[$this->_customdata->userid]->str_grade;
            }

        } else {

            $grade = $this->_customdata->grading_info->items[0]->grades[$this->_customdata->userid]->str_grade;

        }

        $mform->addElement('static', 'finalgrade', get_string('currentgrade', 'kalmediaassign').':', $grade);
        $mform->setType('finalgrade', PARAM_INT);

        // Feedback section.
        $mform->addElement('header', 'single_submission_4', get_string('feedback', 'kalmediaassign'));

        if (!empty($this->_customdata->gradingdisabled)) {

            if (array_key_exists($this->_customdata->userid, $gradinginfo->items[0]->grades)) {
                $mform->addElement('static', 'disabledfeedback', '&nbsp;',
                                   $gradinginfo->items[0]->grades[$this->_customdata->userid]->str_feedback );
            } else {
                $mform->addElement('static', 'disabledfeedback', '&nbsp;', '' );
            }

        } else {

            $mform->addElement('editor', 'submissioncomment_editor',
                               get_string('feedback', 'kalmediaassign').':', null, $this->get_editor_options());
            $mform->setType('submissioncomment_editor', PARAM_RAW); // To be cleaned before display.

        }

        // Marked section.
        $mform->addElement('header', 'single_submission_5', get_string('lastgrade', 'kalmediaassign'));

        $mform->addElement('static', 'markingteacher',
                           $this->_customdata->markingteacherpic,
                           $this->_customdata->markingteacherinfo);

        $this->add_action_buttons();
    }

    /**
     * This function defines the forums elements that are to be displayed.
     * @param object $data - submission object.
     */
    public function set_data($data) {

        if (!isset($data->submission->format)) {
            $data->textformat = FORMAT_HTML;
        } else {
            $data->textformat = $data->submission->format;
        }

        $data->editor_options = $this->get_editor_options();

        return parent::set_data($data);

    }

    /**
     * This function defines the forums elements that are to be displayed.
     * @return array - list of setting data of assignment.
     */
    protected function get_editor_options() {

        $editoroptions = array();
        $editoroptions['component'] = 'mod_kalmediaassign';
        $editoroptions['noclean'] = false;
        $editoroptions['maxfiles'] = 0;
        $editoroptions['context'] = $this->_customdata->context;

        return $editoroptions;
    }

}
