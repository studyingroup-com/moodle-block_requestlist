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
 * Form to Join an already requested course or Add a new requested course.
 *                and also to remove a Request.
 *
 * @package    blocks
 * @subpackage  requestlist
 * @author      studyingroup.com
 * @copyright   2021 studyingroup.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');    ///  It must be included from a Moodle page
}

require_once($CFG->libdir.'/formslib.php');

/**
 * A form for a user to request a new course.
 */
class new_course_requesting_form extends moodleform {
    function definition() {
        global $CFG, $DB, $USER;

        $mform =& $this->_form;


        $mform->addElement('header','coursedetails', get_string('new_courserequestdetails', 'block_requestlist'));

        $mform->addElement('text', 'fullname', get_string('fullnamecourse', 'block_requestlist'), 'maxlength="254" size="50"');
        $mform->addHelpButton('fullname', 'fullnamecourse');
        $mform->addRule('fullname', get_string('missingfullname'), 'required', null, 'client');
        $mform->setType('fullname', PARAM_TEXT);

        $displaylist = core_course_category::make_categories_list('block/requestlist:use');
        $mform->addElement('autocomplete', 'category', get_string('coursecategory'), $displaylist);
        $mform->setDefault('category', $CFG->defaultrequestcategory);
        $mform->addRule('category', get_string('missingcategory', 'block_requestlist'), 'required', null, 'client');
        $mform->addHelpButton('category', 'coursecategory');

        $mform->addElement('text', 'summary_editor[text]', get_string('summary', 'block_requestlist'), 'maxlength="254" size="50"');
        $mform->setType('summary_editor[text]', PARAM_RAW);
        $mform->addHelpButton('summary_editor[text]', 'linkhelper', 'block_requestlist');

        //Hidden values but which are still required 
        //$mform->addElement('text', 'summary_editor[format]', '', '');
        $mform->addElement('text', 'summary_editor[format]', '', 'hidden');
        $mform->setType('summary_editor[format]', PARAM_INT);
        $mform->setDefault('summary_editor[format]', 1);

        //$mform->addElement('text', 'shortname', '', '');
        $mform->addElement('text', 'shortname', '', 'hidden');
        $mform->setDefault('shortname', '-');
        $mform->setType('shortname', PARAM_TEXT);

        //$mform->addElement('textarea', 'reason', '', '');
        $mform->addElement('textarea', 'reason', '', 'hidden');
        $mform->setType('reason', PARAM_TEXT);
        $mform->setDefault('reason', '-');

        $mform->addElement('text', 'requestform_type', '', 'hidden');
        $mform->setDefault('requestform_type', 'new');
        $mform->setType('requestform_type', PARAM_TEXT);

        $this->add_action_buttons(true, get_string('requestcourse'));
    }

    function validation($data, $files) {
        global $DB;

        $errors = parent::validation($data, $files);
        $foundcourses = null;
        $foundreqcourses = null;

        if (!empty($data['fullname'])) {
            $foundcourses = $DB->get_records('course', array('fullname'=>$data['fullname']));
            $foundreqcourses = $DB->get_records('course_request', array('fullname'=>$data['fullname']));
        }
        if (!empty($foundreqcourses)) {
            if (!empty($foundcourses)) {
                $foundcourses = array_merge($foundcourses, $foundreqcourses);
            } else {
                $foundcourses = $foundreqcourses;
            }
        }

        if (!empty($foundcourses)) {
            foreach ($foundcourses as $foundcourse) {
                if (!empty($foundcourse->requester)) {
                    $pending = 1;
                    $foundcoursenames[] = $foundcourse->fullname.' [*]';
                } else {
                    $foundcoursenames[] = $foundcourse->fullname;
                }
            }
            $foundcoursenamestring = implode(',', $foundcoursenames);

            $errors['fullname'] = get_string('fullnametaken', 'block_requestlist', $foundcoursenamestring);
            if (!empty($pending)) {
                $errors['fullnameshortname'] .= get_string('starpending');
            }
        }

        return $errors;
    }
}

/**
 * A form for a user to join his request to already requested course.
 */
class notnew_course_requesting_form extends moodleform {
    function definition() {
        global $CFG, $DB, $USER;

        $mform =& $this->_form;

        $mycustomdata = $this->_customdata[0];

        $mform->addElement('header','coursedetails', get_string('notnew_courserequestdetails', 'block_requestlist'));

        $mform->addElement('text', 'fullname', get_string('fullnamecourse', 'block_requestlist'), 'readonly maxlength="254" size="50"');
        $mform->setType('fullname', PARAM_TEXT);
        $mform->setDefault('fullname', $mycustomdata['fullname']);

        $mform->addElement('text', 'categoryname', get_string('coursecategory'), 'readonly maxlength="254" size="50"');
        $mform->setType('categoryname', PARAM_TEXT);
        $mform->setDefault('categoryname', $mycustomdata['categoryname']);

        $mform->addElement('text', 'summary_editor[text]', get_string('summary', 'block_requestlist'), 'readonly maxlength="254" size="50"');
        $mform->setType('summary_editor[text]', PARAM_RAW);
        $mform->setDefault('summary_editor[text]', $mycustomdata['summary']);

        //Hidden values but which are still required 
        $mform->addElement('text', 'requestform_type', '', 'hidden');
        $mform->setDefault('requestform_type', 'old');
        $mform->setType('requestform_type', PARAM_TEXT);

        $mform->addElement('text', 'category', '', 'hidden');
        $mform->setType('category', PARAM_INT);
        $mform->setDefault('category', $mycustomdata['categoryid']);

        $mform->addElement('text', 'summary_editor[format]', '', 'hidden');
        $mform->setType('summary_editor[format]', PARAM_INT);
        $mform->setDefault('summary_editor[format]', 1);

        $mform->addElement('text', 'shortname', '', 'hidden');
        $mform->setDefault('shortname', '-');
        $mform->setType('shortname', PARAM_TEXT);

        $mform->addElement('textarea', 'reason', '', 'hidden');
        $mform->setType('reason', PARAM_TEXT);
        $mform->setDefault('reason', '-');

        //$this->add_action_buttons(true, get_string('requestcourse'));

        $buttonarray=array();
        $buttonarray[] = &$mform->createElement('submit', 'submitbutton', 
            get_string('I_want_to_study_it_also', 'block_requestlist'));
        $buttonarray[] = &$mform->createElement('cancel', 'cancel', get_string('cancel', 'block_requestlist'));
        $mform->registerNoSubmitButton('newsuggest');
        $mform->setType('newsuggest', PARAM_NOTAGS);
        $buttonarray[] =& $mform->createElement('submit', 'newsuggest', 
            get_string('Not_the_subject_you_want_Suggest_a_new_one', 'block_requestlist'));
        $mform->addGroup($buttonarray, 'buttonar', '', array(' '), false);
    }
}
/**
 * A form for an administrator to reject a course request.
 */
class reject_request_form extends moodleform {
    function definition() {
        $mform =& $this->_form;

        $mform->addElement('hidden', 'reject', 0);
        $mform->setType('reject', PARAM_INT);

        $mform->addElement('header','coursedetails', get_string('coursereasonforrejecting'));

        $mform->addElement('textarea', 'rejectnotice', get_string('coursereasonforrejectingemail'), array('rows'=>'15', 'cols'=>'50'));
        $mform->addRule('rejectnotice', get_string('missingreqreason'), 'required', null, 'client');
        $mform->setType('rejectnotice', PARAM_TEXT);

        $this->add_action_buttons(true, get_string('reject'));
    }
}

/**
 * A form for a user to remove his request.
 */
class del_course_requesting_form extends moodleform {
    function definition() {
        global $CFG, $DB, $USER;

        $mform =& $this->_form;

        $mycustomdata = $this->_customdata[0];

        $mform->addElement('header','coursedetails', get_string('del_courserequestdetails', 'block_requestlist'));

        $mform->addElement('text', 'fullname', get_string('fullnamecourse', 'block_requestlist'), 'readonly maxlength="254" size="50"');
        $mform->setType('fullname', PARAM_TEXT);
        $mform->setDefault('fullname', $mycustomdata['fullname']);

        $mform->addElement('text', 'categoryname', get_string('coursecategory'), 'readonly maxlength="254" size="50"');
        $mform->setType('categoryname', PARAM_TEXT);
        $mform->setDefault('categoryname', $mycustomdata['categoryname']);

        $mform->addElement('text', 'summary', get_string('summary', 'block_requestlist'), 'readonly maxlength="254" size="50"');
        $mform->setType('summary', PARAM_RAW);
        $mform->setDefault('summary', $mycustomdata['summary']);

        //Hidden values but which are still required 
        $mform->addElement('text', 'requestform_type', '', 'hidden');
        $mform->setDefault('requestform_type', 'del');
        $mform->setType('requestform_type', PARAM_TEXT);

        $mform->addElement('text', 'requestid', '', 'hidden');
        $mform->setType('requestid', PARAM_INT);
        $mform->setDefault('requestid', $mycustomdata['requestid']);

        $this->add_action_buttons(true, get_string('Yes_remove_my_request', 'block_requestlist'));

    }
}

