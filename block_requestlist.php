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
 * Defines the class for the Request List block
 *
 * @package    blocks
 * @subpackage  requestlist
 * @author      studyingroup.com
 * @copyright   2021 studyingroup.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Class definition for the Request List Block
 *
 * @uses block_base
 */
class block_requestlist extends block_base 
{

    private $globalconf;
    
    public function init() 
    {
        $this->content_type = BLOCK_TYPE_TEXT;
        $this->globalconf = get_config('block_requestlist');
        if (isset($this->globalconf->title) && !empty($this->globalconf->title)) 
        {
            $this->title = $this->globalconf->title;
        } else {
            $this->title = get_string('requestlist', 'block_requestlist');
        }
    }

    //stop it showing up on any add block lists
    public function applicable_formats() 
    {
        return (array(  
            'all' => false, 
            'site' => true, 
            'my' => true, 
            'course-index' => true
        ));
    }

    public function has_config() 
    {
        return true;
    }

    /**
     * Displays the form for searching courses, and the results if a search as been submitted
     *
     * @access public
     * @return
     */
    public function get_content() 
    {
        global $CFG, $DB;
        if ($this->content !== null) 
        {
            return $this->content;
        }
        
        $this->content = new stdClass(); //$this.content is what HTML is displayed
        $context_block = context_block::instance($this->instance->id); //use for the capabilities
        $search = optional_param('requestlistsearch', '', PARAM_TEXT);
        $requestlistsubmit = optional_param('requestlistsubmit', false, PARAM_TEXT);
        
        //for the admin, link to Pending request
        if (core_course_category::can_approve_course_requests()) {
            global $OUTPUT;
            $url = new moodle_url('/course/pending.php');
            $button = $OUTPUT->single_button($url, get_string('coursespending')); 
            $this->content->text .= $button;
        }

        // the SQL search of the requests:
        $requests = self::_get_request(); 
        if (!empty($requests)) 
        {
            /*
                $cells = html_writer::tag('th', 'Number of Users who are searching other people to study this');
                $cells .= html_writer::tag('th', 'Name of the course');
                $cells .= html_writer::tag('th', 'Preferred origin of the course');
                $cells .= html_writer::tag('th', 'Category');
                $rows = html_writer::tag('tr', $cells);
                */
            $previous_category = '';
            $rows = '';
            foreach ($requests as $request) 
            {
                $current_category = $request->category_name;
                if ($previous_category != $current_category)
                {
                    $rows .= html_writer::tag('th', $request->category_name, array('colspan'=>'4'));
                }
                $previous_category = $current_category;
                
                $requestnb_html = html_writer::tag('span', $request->request_nb, 
                    array('class'=>'font-weight-bold'));
                $cells = html_writer::tag('td', $requestnb_html. 
                    get_string('users_are_searching_other_people_to_study', 'block_requestlist'));
                $fullname_html = html_writer::tag('span', $request->fullname, 
                    array('class'=>'font-weight-bold'));
                $cells .= html_writer::tag('td', $fullname_html);
                $summary_content_truncated =  strlen($request->summary) > 40 ? substr($request->summary,0,10)."..." : $request->summary;
                $summary_html = html_writer::tag('a', $summary_content_truncated, 
                    array('href'=>$request->summary));
                $cells .= html_writer::tag('td', ' (link :'.$summary_html.')');
                $url = new moodle_url('/blocks/requestlist/course_requesting.php', 
                                            array('requestform_type'=>'old',
                                                'fullname'=>$request->fullname,
                                                'categoryid'=>$request->category_id,
                                                'requestid'=>$request->requestid,
                                                'category_name'=>$request->category_name,
                                                'summary'=>$request->summary));

                //Display the "I want it to study also only if not requested before by the User
                $requester_list = explode(', ', $request->requester_list);
                global $OUTPUT;
                global $USER;
                if (!in_array($USER->id, $requester_list))
                {
                    $button = $OUTPUT->single_button($url, get_string('I_want_to_study_it_also', 'block_requestlist')); 
                } else {
                    $button1 = $OUTPUT->single_button(null, get_string('You_already_join_them', 'block_requestlist'), 
                        $method="post", array('disabled'=>"disabled")); 
                    $url = new moodle_url('/blocks/requestlist/course_requesting.php', 
                                                array('requestform_type'=>'del',
                                                    'fullname'=>$request->fullname,
                                                    'requestid'=>$request->requestid,
                                                    'category_name'=>$request->category_name,
                                                    'summary'=>$request->summary));
                    $button2 = $OUTPUT->action_icon($url, 
                                                new pix_icon('i/trash', 
                                                get_string('Delete_your_course_request', 'block_requestlist'))); 
                    global $OUTPUT;
                    $button_cell = html_writer::tag('td', $button1);
                    $button_cell .= html_writer::tag('td', $button2);
                    $button_row = html_writer::tag('tr', $button_cell);
                    $button = html_writer::tag('table', $button_row);
                }
                $cells .= html_writer::tag('td', $button);
                $rows .= html_writer::tag('tr', $cells);

            }
            $table = html_writer::tag('table', $rows, array('id'=>'requested_courses'));
            $this->content->text .= $table;
        }
        $url = new moodle_url('/blocks/requestlist/course_requesting.php', array('requestform_type'=>'new'));
        global $OUTPUT;
        $this->content->text .= $OUTPUT->single_button($url,
                        get_string('Not_found_the_subject_you_d_like_to_study_Request_it', 'block_requestlist')); 
        $this->content->footer='';
        return $this->content; //send the HTML to be displayed
    }

    /**
     *  helper function for get_request_content(): SQL search of requests 
     */
    public static function _get_request() 
    {
        global $DB;
        $sql = "SELECT row_number() OVER (ORDER BY request.fullname) n, count(*) AS request_nb, 
                         GROUP_CONCAT(request.requester SEPARATOR ', ') AS requester_list,
                        request.id AS requestid, 
                        request.fullname,  
                        request.summary, 
                        category.id AS category_id,
                        category.name AS category_name
        FROM {course_request} AS request 
        INNER JOIN {course_categories} AS category ON request.category = category.id
        GROUP BY request.fullname
        ORDER BY category.name, request.fullname;";
        // Other databases than MySQL don't know GROUP_CONCAT...
        $dbman = get_class($DB->get_manager()->generator);
        if (strpos($dbman, 'mysql') !== 0)
        {
            $sql = "SELECT row_number() OVER (ORDER BY request.fullname) n, count(*) AS request_nb, 
                            STRING_AGG(request.requester, ', ') AS requester_list,
                            request.id AS requestid, 
                            request.fullname,  
                            request.summary, 
                            category.id AS category_id,
                            category.name AS category_name
            FROM {course_request} AS request 
            INNER JOIN {course_categories} AS category ON request.category = category.id
            GROUP BY request.fullname
            ORDER BY category.name, request.fullname;";
        }
        $requests = $DB->get_records_sql($sql);
    return $requests;
    }
}

