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
 * Language file of YU Kaltura media assignment.
 *
 * @package   mod_kalmediaassign
 * @copyright (C) 2016-2025 Yamaguchi University <gh-cc@mlex.cc.yamaguchi-u.ac.jp>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['pluginname'] = 'Kaltura Media Assignment';

$string['modulenameplural'] = 'Kaltura Media Assignments';
$string['modulename'] = 'Kaltura Media Assignment';
$string['modulename_help'] = 'The Kaltura Media Assignment enables a teacher to create assignments that require students to upload and submit Kaltura videos. Teachers can grade student submissions and provide feedback.';
$string['gradingsummary'] = 'Grading summary';
$string['submissionstatus'] = 'Submission status';
$string['gradingstatus'] = 'Grading status';
$string['name'] = 'Name';
$string['description'] = 'Description';
$string['availabledate'] = 'Available from';
$string['duedate'] = 'Due Date';
$string['alwaysshowdescription'] = 'Always show description';
$string['alwaysshowdescription_help'] = 'If disabled, the Assignment Description above will only become visible to students at the "Allow submissions from" date.';
$string['submissionsettings_hdr'] = 'Submission settings';
$string['preventlate'] = 'Prevent late submissions';
$string['allowdeleting'] = 'Allow resubmitting';
$string['allowdeleting_help'] = 'If enabled, students may replace submitted media. Whether it is possible to submit after the due date is controlled by the \'Prevent late submissions\' setting';
$string['emailteachers'] = 'Email alerts to teachers';
$string['emailteachers_help'] = 'If enabled, teachers receive email notification whenever students add or update an assignment submission. Only teachers who are able to grade the particular assignment are notified. So, for example, if the course uses separate groups, teachers restricted to particular groups won\'t receive notification about students in other groups.';
$string['invalidid'] = 'Invalid ID';
$string['pluginadministration'] = 'Kaltura Media Assignment';
$string['add_media'] = 'Add media';
$string['submit_media'] = 'Submit media';
$string['replace_media'] = 'Replace media';
$string['previewmedia'] = 'Preview';
$string['gradesubmission'] = 'Grade submissions';
$string['numberofmembers'] = 'Number of members';
$string['numberofsubmissions'] = 'Number of submissions';
$string['numberofrequiregrading'] = 'Number of require grading';
$string['assignmentexpired'] = 'Submission cancelled.  The assignment due date has passed';
$string['assignmentsubmitted'] = 'Success, your assignment has been submitted';
$string['emptyentryid'] = 'Media assignment was not submitted correctly.  Please try to resubmit.';
$string['deleteallsubmissions'] = 'Delete all media submissions';
$string['fullname'] = 'Name';
$string['grade'] = 'Grade';
$string['gradedby'] = 'Graded by';
$string['gradedon'] = 'Graded on';
$string['feedbackcomment'] = 'Feedback comment';
$string['currentgrade'] = 'Current grade';
$string['submissioncomment'] = 'Comment';
$string['timemodified'] = 'Last modified (Submission)';
$string['grademodified'] = 'Last modified (Grade)';
$string['finalgrade'] = 'Final grade';
$string['status'] = 'Status';
$string['optionalsettings'] = 'Optional settings';
$string['savepref'] = 'Save preferences';
$string['all'] = 'All';
$string['reqgrading'] = 'Require grading';
$string['submitted'] = 'Submitted';
$string['notsubmittedyet'] = 'Not submitted yet';
$string['pagesize'] = 'Submissions shown per page';
$string['pagesize_help'] = 'Set the number of assignment to display per page';
$string['show'] = 'Show';
$string['show_help'] = "If filter is set to 'All' then all student submissions will be displayed; even if the student didn't submit anything.  If set to 'Require grading' only submissions that has not been graded or submissions that were updated by the student after it was graded will be shown.  If set to 'Submitted' only students who submitted a media assignment.";
$string['quickgrade'] = 'Allow quick grade';
$string['quickgrade_help'] = 'If enabled, multiple assignments can be graded on one page. Add grades and comments then click the "Save all my feedback" button to save all changes for that page.';
$string['invalidperpage'] = 'Enter a number greater than zero';
$string['savefeedback'] = 'Save grade and feedback';
$string['submission'] = 'Submission';
$string['grades'] = 'Grades';
$string['feedback'] = 'Feedback';
$string['singlesubmissionheader'] = 'Grade submission';
$string['singlegrade'] = 'Add help text';
$string['singlegrade_help'] = 'Add help text';
$string['late'] = '{$a} late';
$string['early'] = '{$a} early';
$string['lastgrade'] = 'Last grade';
$string['savedchanges'] = 'Changed Saved';
$string['save'] = 'Save Changes';
$string['cancel'] = 'Close';
$string['checkconversionstatus'] = 'Check media conversion status';
$string['media_converting'] = 'The media is still converting.  Please check the status of the media at a later time.';
$string['emailteachermail'] = '{$a->username} has updated their assignment submission
for \'{$a->assignment}\' at {$a->timeupdated}

It is available here:

    {$a->url}';
$string['emailteachermailhtml'] = '{$a->username} has updated their assignment submission
for <i>\'{$a->assignment}\'  at {$a->timeupdated}</i><br /><br />
It is <a href="{$a->url}">available on the web site</a>.';
$string['messageprovider:kalmediaassign_updates'] = 'Kaltura Media assignment notifications';
$string['media_preview_header'] = 'Submission preview';
$string['kalmediaassign:gradesubmission'] = 'Grade media submissions';
$string['kalmediaassign:addinstance'] = 'Add a Kaltura Media Assignment';
$string['kalmediaassign:submit'] = 'Submit media';
$string['grade_media_not_cache'] = 'This media may still be in the process of converting...';
$string['noenrolledstudents'] = 'No students are enrolled in the course';
$string['group_filter'] = 'Group Filter';
$string['scr_loading'] = 'Loading...';
$string['reviewmedia'] = 'Review submission';
$string['kalmediaassign:screenrecorder'] = 'Screen recorder';
$string['cannotdisplaythumbnail'] = 'Unable to display thumbnail';
$string['noassignments'] = 'No Kaltura media assignments found in the course';
$string['table_failed'] = 'Submission table cannot be displayed due to an error.';
$string['submitted'] = 'Submitted';
$string['nosubmission'] = 'No submission';
$string['nosubmissions'] = 'No submissions';
$string['status_nosubmission'] = 'No submission';
$string['status_submitted'] = 'Submitted for grading';
$string['status_marked'] = 'Graded';
$string['status_nomarked'] = 'Not graded';
$string['status_timemodified'] = 'Last modified';
$string['submissionnotopened'] = 'Not opened yet';
$string['submissionexpired'] = 'Assignment is expired';
$string['latesubmission'] = 'Lated';
$string['submissionclosed'] = 'Closed';
$string['remainingtime'] = 'Remaining time';
$string['not_insert'] = 'Failed to insert submission data.';
$string['not_update'] = 'Failed to update submission data.';
$string['event_submission_page_viewed'] = 'Media submission page viewed';
$string['event_submission_detail_viewed'] = 'Media submission detail viewed';
$string['event_grades_updated'] = 'Grades of Media submission updated';
$string['event_media_submitted'] = 'Media submitted';
$string['reset_userdata'] = 'All data';
$string['outlinegrade'] = 'Grade: {$a}';

// Privacy strings.
$string['privacy:metadata:kalmediaassign_submission'] = 'Information about submission and grading to media assignment.';
$string['privacy:metadata:kalmediaassign_submission:mediaassignid'] = 'Media assignment ID which is linked to Media assignment module.';
$string['privacy:metadata:kalmediaassign_submission:userid'] = 'The ID of the user who submit a media';
$string['privacy:metadata:kalmediaassign_submission:grade'] = 'Score to the media submission.';
$string['privacy:metadata:kalmediaassign_submission:submissioncomment'] = 'Comment from teacher to student.';
$string['privacy:metadata:kalmediaassign_submission:teacher'] = 'The Id of teacher who grade the submitted media.';
$string['privacy:metadata:kalmediaassign_submission:timemarked'] = 'Time when the media was graded by teacher.';
$string['privacy:metadata:kalmediaassign_submission:timecreated'] = 'Time when the media was submitted.';
$string['privacy:metadata:kalmediaassign_submission:timemodified'] = 'Time when the media was modified.';
