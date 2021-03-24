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
 * Language strings for CADO.
 *
 * @package    mod_cado
 * @copyright  2020 Naomi Quirke
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['activityoptions'] = 'Activities to include';
$string['activityoptions_desc'] = 'Activities to include in CADO, if present in course';
$string['alloweditcomment'] = 'Edit approval history';
$string['approvalcomparisondifferent'] = 'Approval statement is different.';
$string['approvelink'] = 'Approve?';
$string['approveagree'] = 'Approve CADO';
$string['approvecomment'] = 'Approval comment:';
$string['approvecommentreviewed'] = 'Approval comment reviewed by {$a->user} on {$a->date}.';
$string['approvehistoryedited'] = 'Approval history edited by {$a->user} on {$a->date}.';
$string['approvedon'] = 'This CADO was approved by {$a->approver} on ';
$string['approvereport'] = 'Approve this CADO';
$string['approvesubjectline'] = 'CADO approved: ';
$string['assign'] = 'Assignments';
$string['assignexpectcompleted'] = 'Date to be completed by';
$string['cadobiblio'] = 'Additional comments displayed after assessments and after site-wide comment (if it is set)';
$string['cadointro'] = 'Initial comments displayed immediately after CADO heading.';
$string['cado'] = 'CADO';
$string['cadocomment'] = 'Additional comments displayed before assessments';
$string['cadotitle'] = 'Course Assessment and Delivery Outline';  // Used as header of CADO report.
$string['cado:addinstance'] = 'Add a new CADO resource to a course page';
$string['cado:approve'] = 'Approve or unapprove a CADO';
$string['cado:compare'] = 'Carry out a comparison between previously generated CADOs';
$string['cado:generate'] = 'Generate a new CADO';
$string['cado:view'] = 'View an approved CADO';
$string['cadoname'] = 'Name for this CADO report';
$string['cadooptions'] = 'CADO features to include';
$string['cadooptions_desc'] = 'CADO-specific options to include in the CADO report.
If course schedule is included, and the course uses a weekly format, dates will be included as a column in the schedule,
 otherwise the schedule will be presented only by topic. Any activities included in the general section will
 appear under a "{$a}" heading';
$string['chooseapprover'] = 'To which approver(s) do you wish to propose your CADO?';
$string['course'] = 'Enter course code';
$string['comparelink'] = 'Compare';
$string['compareheader'] = 'Comparison result';
$string['compareheaderorigin'] = 'Origin CADO: "{$a}"';
$string['compareheaderother'] = 'Other CADO: "{$a}"';
$string['comparisondifferent'] = 'Comparison key: pink highlights changes between origin and other, green highlights when only origin CADO has an activity, yellow highlights when only other CADO has an activity.';
$string['comparisonidentical'] = 'Identical CADOs.';
$string['comparemissing'] = 'MISSING: ';
$string['completion'] = 'Completion information';
$string['configdisallowstudentview'] = 'Adds a configuration option on course pages to allow students to view the CADO report on the course page';
$string['courseinstruction'] = 'Select CADO';
$string['coursenotchosen'] = 'Default: update filter settings';
$string['confirmpropose'] = 'Are you sure you want to propose this CADO to be approved?';
$string['datedue'] = 'Date due';
$string['duedates'] = 'Dates';
$string['datestartselector'] = 'Filter course by start date, date range start';
$string['dateendselector'] = 'Filter course by start date, date range end';
$string['description'] = 'Course overview';
$string['eventapprovecado'] = 'CADO approved';
$string['eventreportviewed'] = 'Approved CADO viewed';
$string['eventnotapprovecado'] = 'CADO not approved';
$string['eventproposecado'] = 'CADO proposed';
$string['filtercourses'] = 'Get courses';
$string['forgroup'] = 'for grouping:';
$string['forum'] = 'Discussion forums';
$string['forumexpectcompleted'] = 'Date to be completed by';
$string['forumposts'] = 'Participation: all posts, openers, replies';
$string['generateddraft'] = 'This draft CADO was generated by {$a->genuser} on ';
$string['generatedproposed'] = 'This draft CADO was proposed by {$a->genuser} to {$a->approver} on ';
$string['general'] = 'General';
$string['propose'] = 'Propose';
$string['grouping'] = 'Select grouping for CADO:';
$string['inchidden'] = 'Show hidden activities:';
$string['inchidden_desc'] = 'Whether hidden activities should appear in the CADO.';
$string['messageprovider:cadoshare'] = 'CADO share';
$string['messageprovider:cadoworkflow'] = 'CADO workflow notification';
$string['modulename'] = 'CADO report';
$string['modulename_help'] = 'The CADO module enables a teacher to automatically create a course overview "CADO" document, and compare this against other CADOs, either generated within the same course or in different courses.
Then any with cado/approve rights may subsequently approve the CADO for student or guest view from the module settings menu.
A CADO generation takes into account global and local settings to determine the modules included in the report. Activity information used for the CADO include description, dates, completion information, and selected tags.
If a grouping setting is included, only modules that are available to that grouping will be included.
If a grouping setting is not included, only modules that are available to all will be included in the report.
Upon approval, any specific CADO settings, including grouping, are frozen.
Multiple CADO resources, created with different CADO settings, may be established in a course.
CADOs may be presented in a page ready for printing upon selection of the print view option in the module settings menu.';
$string['minutes'] = '(minutes)';
$string['modulenameplural'] = 'CADO reports';
$string['nameinstruction'] = 'Filter course name with name part';
$string['notapprovesubjectline'] = 'CADO not approved: ';
$string['notapproved'] = 'It has had the following comments: ';
$string['notapplicable'] = 'Not set';
$string['notavailable'] = 'Sorry, this CADO is not yet available.';
$string['notgenerated'] = 'CADO is not yet generated.';
$string['nouserfiles'] = 'No user files listed.';
$string['pluginadministration'] = 'Plugin administration'; // Required by Moodle.
$string['pluginname'] = 'CADO: Course outline and approval';
$string['prevapprovecomment'] = 'Previous approval comment(s):';
$string['printview'] = 'Print layout view';
$string['privacy:metadata:approverpurpose'] = 'ID of user who approved or did not approve the CADO report most recently.';
$string['privacy:metadata:cadoname'] = 'CADO name.';
$string['privacy:metadata:commentpurpose'] = 'Comments made or reposted by approve-user about the CADO.';
$string['privacy:metadata:generatorpurpose'] = 'ID of user who generated the CADO report most recently from module settings, or who last sent the CADO for approval.';
$string['privacy:metadata:tablesummary'] = 'Stores CADO approve-user, and either generate-user or user who sent the CADO for approval, used in CADO workflow management.';
$string['privacy:metadata:approvetimepurpose'] = 'Time that approve-comments were made or reposted by approve-user.';
$string['privacy:metadata:modifiedtimepurpose'] = 'Time that record was modified.';
$string['privacy:nothing'] = 'Empty.';
$string['quiz'] = 'Quizzes';
$string['quizexpectcompleted'] = 'Date to be completed by';
$string['rubric'] = 'Grading rubric';
$string['requestapprovalsubject'] = 'CADO now awaiting approval.';
$string['requestapproval'] = 'A CADO has been generated for course "{$a}" and is now awaiting approval.';
$string['restore'] = 'This CADO workflow has been restored from backup or is a duplicate.';
$string['schedule'] = 'Course schedule';
$string['schedulesum'] = 'TOTALS';
$string['section'] = 'Section';
$string['semester'] = 'Semester';
$string['settings'] = 'CADO settings';
$string['showlogo'] = 'Include logo';
$string['showlogo_desc'] = 'Include site logo (from admin settings) on CADO report display.';
$string['sitecomment'] = 'Site-wide comment';
$string['sitecomment_desc'] = 'A comment to be included in every CADO report generated, if enabled.';
$string['storegeneratedhtml'] = 'Store HTML version.';
$string['storegenerated_desc'] = 'If enabled, an HTML version of the CADO report is stored in the database. Generated CADO may then be viewed in external reports.';
$string['summary'] = 'Course description';
$string['sumschedule'] = 'Schedule totals';
$string['sumschedule_desc'] = 'Add up numeric tags as a sum at the bottom of the schedule (if schedule and tags are enabled and present).';
$string['tags'] = 'Activity tags';
$string['taginclude'] = 'Tags to include';
$string['taginclude_desc'] = 'Activities may have further information assigned to them for display in the report by assigning tags.
A tag prefix is selected to be used as a heading, with the remainder of the tag being used for the information content.
Enter here any tag prefixes to be used, separated by commas.
When an activity is assigned a tag, have the tag composed of the selected prefix, then a double-colon separator, and then the information content.
For example, if the tag "Hours" was entered here, and an activity was tagged "Hours::5", then Hours would be included as a header and 5 as the content.';
$string['tagschedule'] = 'Tags as columns in course schedule';
$string['tagschedule_desc'] = 'Include the first three tags specified as additional columns in the course schedule, if schedule is present.';
$string['task'] = 'Task';
$string['titleforlegend'] = 'Select CADO report parameters:';
$string['topic'] = 'Topic';
$string['update'] = 'Update: ';
$string['useranonymous'] = 'Anonymous';
$string['week'] = 'Semester week';
$string['weekdate'] = 'Start date of week';
