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
 * Page displaying the form. 2 types of forms: one to allow a user to Join an already requested course 
 *                                           or one to Create a course request.
 *
 * @package    blocks
 * @subpackage  requestlist
 * @author      studyingroup.com
 * @copyright   2021 studyingroup.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
//require_once('../../config.php');
require_once(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/course/lib.php');
require_once($CFG->dirroot . '/blocks/requestlist/course_requesting_form.php');

$PAGE->set_url('/blocks/requestlist/course_requesting.php');

// Where we came from. Used in a number of redirects.
$url = new moodle_url('/blocks/requestlist/course_requesting.php');
//POST data:
$return = optional_param('return', null, PARAM_ALPHANUMEXT);
$categoryid = optional_param('category', null, PARAM_INT);
$requestform_type = optional_param('requestform_type', 'new', PARAM_TEXT);
$category_name = optional_param('category_name', null, PARAM_TEXT);
$fullname = optional_param('fullname', null, PARAM_TEXT);
$summary = optional_param('summary', null, PARAM_TEXT);
$requestid = optional_param('requestid', null, PARAM_INT);
$cancel = optional_param('cancel', null, PARAM_TEXT);// BUG? the cancel button on "old request form" is not working

/*
if ($return === 'management') {
    $url->param('return', $return);
    $returnurl = new moodle_url('/course/management.php', array('categoryid' => $CFG->defaultrequestcategory));
} else {
    $returnurl = new moodle_url('/course/index.php');
}
*/
$returnurl = new moodle_url('/my/index.php');

$PAGE->set_url($url);

// Check permissions.
require_login(null, false);
if (isguestuser()) {
    print_error('guestsarenotallowed', '', $returnurl);
}
if (empty($CFG->enablecourserequests)) {
    print_error('courserequestdisabled', '', $returnurl);
}

if ($CFG->lockrequestcategory) {
    // Course request category is locked, user will always request in the default request category.
    $categoryid = null;
} else if (!$categoryid) {
    // Category selection is enabled but category is not specified.
    // Find a category where user has capability to request courses (preferably the default category).
    $list = core_course_category::make_categories_list('moodle/course:request');
    $categoryid = array_key_exists($CFG->defaultrequestcategory, $list) ? $CFG->defaultrequestcategory : key($list);
}


$context = context_coursecat::instance($categoryid ?: $CFG->defaultrequestcategory);
$PAGE->set_context($context);

// Set up the form.
if($requestform_type == 'new')
{
    $requestform = new new_course_requesting_form($url);
} else if ($requestform_type == 'old'){
    $data = array('categoryname' => $category_name, 
                    'categoryid' => $categoryid,
                    'fullname' => $fullname,
                    'summary' => $summary);
    $requestform = new notnew_course_requesting_form($url, array($data));
} else if ($requestform_type == 'del'){
    $data = array('categoryname' => $category_name, 
                    'fullname' => $fullname,
                    'requestid' => $requestid,
                    'summary' => $summary);
    $requestform = new del_course_requesting_form($url, array($data));
}
$strtitle = get_string('courserequest');
$PAGE->set_title($strtitle);
$PAGE->set_heading($strtitle);

// Standard form processing if statement.
$data = $requestform->get_data();
if ($requestform->is_cancelled() || $cancel == 'cancel'){ //BUG with the cancel button on 'old request form'???
    redirect($returnurl);

} else if ($requestform->no_submit_button_pressed()){ //the User has clicked on "Suggest a new course"
    $url = new moodle_url('/blocks/requestlist/course_requesting.php', array('requestform_type'=>'new'));
    redirect($url);

} else if ($data = $requestform->get_data()) {
    if($requestform_type == 'del') //check whether it's a delete form which has been completed
    {
        global $DB, $USER;
        $requestid = $data->requestid;
        $DB->delete_records_select('course_request', "id={$requestid}");
        $noticestring = get_string('deletingcourserequestsuccess', 'block_requestlist');
    } else {
        $request = course_request::create($data);
        // And redirect back to the course listing.
        $noticestring = get_string('courserequestsuccess');
    }
        notice($noticestring, $returnurl);

}

$PAGE->navbar->add($strtitle);
echo $OUTPUT->header();
echo $OUTPUT->heading($strtitle);
// Show the request form.
$requestform->display();
echo $OUTPUT->footer();
