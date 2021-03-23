<?php
// This file is part of the customcert module for Moodle - http://moodle.org/
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
 * This file contains the customcert element text's core interaction API.
 *
 * @package    customcertelement_flextext
 * @copyright  2013 Mark Nelson <markn@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace customcertelement_flextext;

defined('MOODLE_INTERNAL') || die();

/**
 * The customcert element text's core interaction API.
 *
 * @package    customcertelement_flextext
 * @copyright  2013 Mark Nelson <markn@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class element extends \mod_customcert\element {

    /**
     * This function renders the form elements when adding a customcert element.
     *
     * @param \MoodleQuickForm $mform the edit_form instance
     */
    public function render_form_elements($mform) {
        global  $DB, $COURSE;


        $fields = array();

        $courseid = \mod_customcert\element_helper::get_courseid($this->get_id());

        //$currsql = "select id,name from mintmdl_quiz where course = '".$COURSE->id."'";
        $currsql = "select id,name from {quiz} where course = '".$COURSE->id."'";
        $quizzesincourse = $DB->get_records_sql($currsql);


        foreach( $quizzesincourse as $currquiz){
            $fields[$currquiz->id] = $currquiz->name;
        }


        \core_collator::asort($fields);

        // Create the select box where the quiz is selected.
        $mform->addElement('select', 'quizid', get_string('quiztocertificate', 'customcertelement_flextext'), $fields);
        $mform->setType('quizid', PARAM_ALPHANUM);
        $mform->addHelpButton('quizid', 'quizid', 'customcertelement_flextext');


        $mform->addElement('textarea', 'text', get_string('flextext', 'customcertelement_flextext'));
        $mform->setType('text', PARAM_RAW);
        $mform->addHelpButton('text', 'text', 'customcertelement_flextext');

        parent::render_form_elements($mform);
    }

    /**
     * This will handle how form data will be saved into the data column in the
     * customcert_elements table.
     *
     * @param \stdClass $data the form data
     * @return string the text
     */
    public function save_unique_data($data) {

        $arrtostore = array(
            'quizid' => $data->quizid,
            'text' => $data->text
        );

        // Encode these variables before saving into the DB.
        return json_encode($arrtostore);


        //return $data->mintfitcert;
        //return $data->text;
    }

    /**
     * Handles rendering the element on the pdf.
     *
     * @param \pdf $pdf the pdf object
     * @param bool $preview true if it is a preview, false otherwise
     * @param \stdClass $user the user we are rendering this for
     */
    public function render($pdf, $preview, $user) {
        \mod_customcert\element_helper::render_content($pdf, $this, $this->get_text());
    }

    /**
     * Render the element in html.
     *
     * This function is used to render the element when we are using the
     * drag and drop interface to position it.
     *
     * @return string the html
     */
    public function render_html() {
        return \mod_customcert\element_helper::render_html_content($this, $this->get_text());
    }

    /**
     * Sets the data on the form when editing an element.
     *
     * @param \MoodleQuickForm $mform the edit_form instance
     */
    public function definition_after_data($mform) {

        if (!empty($this->get_data())) {

            $flextextinfo = json_decode($this->get_data());

            $element = $mform->getElement('text');
            $element->setValue($flextextinfo->text);
            $element = $mform->getElement('quizid');
            $element->setValue($flextextinfo->quizid);

        }
        parent::definition_after_data($mform);
    }


    /**
     * Helper function that returns the fields of Moodle's usertable
     *
     * @return array
     */
    protected function get_user_fields() : array
    {
        global $CFG, $DB;
        //$currsql = "describe mintmdl_user";
        $currsql = "describe {user}";
        $usertablefields = $DB->get_records_sql($currsql);
        return $usertablefields;
    }

    /**
     * Helper function that returns the text.
     *
     * @return string
     */
    protected function get_text() : string {
        global $USER;

        // get certdata
        $certinfo = $this->get_cert_info();

        // get userfields
        $context = \mod_customcert\element_helper::get_context($this->get_id());

        $flextextinfo = json_decode($this->get_data());
        $output = $flextextinfo->text;
        $usertablefields = $this->get_user_fields();

        foreach($usertablefields as $curruser){
            $currval = \mod_customcert\element_helper::render_html_content($this, $this->get_user_field_value($USER, $curruser->field,true));
            $output = str_replace( "###".$curruser->field."###", strip_tags($currval), $output);
        }

        // get gradeinfo
        $quizdata = $this->get_quiz_result($USER);

        //$cmid = $this->get_coursemoduleid();
        //$gradeformat = 2;
        //$userid = $USER->id;
        //$grade =  \mod_customcert\element_helper::get_mod_grade_info($cmid, $gradeformat, $userid);
        $output = str_replace( "###quizresult###", $quizdata['quizresult'], $output);
        $qstring = "/###QUESTIONS\((.*?)\)###/";

        if (preg_match_all($qstring, $output, $matches)) {

            foreach($matches[0] as $match) {
                $cmatch =  str_replace( "###QUESTIONS(", "", $match);
                $cmatch =  str_replace( ")###", "", $cmatch);
                $qeval = "";
                $qeval = $this->get_group_result($USER, $cmatch);
                $output = str_replace( $match, $qeval , $output);
            }
        }
        $output = str_replace( "###certdate###", date('d.m.Y',$quizdata['timemodified']), $output);
        $output = str_replace( "###certcode###",  $certinfo->code, $output);

        return format_text( $output, FORMAT_HTML, ['context' => $context]);
    }

    protected function get_coursemoduleid(){
        global $CFG, $DB; $COURSE;

        $courseid = \mod_customcert\element_helper::get_courseid($this->get_id());


        //return $courseid."-".$this->get_data();


        $quizmax = $DB->get_records_sql("select id  from mintmdl_course_modules where course = ".$courseid." and instance = ".$this->get_data()) ;
        foreach( $quizmax as $currmax){

            $count = $currmax->id;
        }
        return $count;
    }

    /**
     * Helper function that returns the text.
     *
     * @param \stdClass $user the user we are rendering this for
     * @param bool $preview Is this a preview?
     * @return string
     */
    protected function get_user_field_value(\stdClass $user, string $currfield , bool $preview) : string {

        global $CFG, $DB;

        // The user field to display.
        $field = $currfield; // $this->get_data();
        // The value to display - we always want to show a value here so it can be repositioned.
        if ($preview) {
            $value = $field;
        } else {
            $value = '';
        }
        if (is_number($field)) { // Must be a custom user profile field.
            if ($field = $DB->get_record('user_info_field', array('id' => $field))) {
                // Found the field name, let's update the value to display.
                $value = $field->name;
                $file = $CFG->dirroot . '/user/profile/field/' . $field->datatype . '/field.class.php';
                if (file_exists($file)) {
                    require_once($CFG->dirroot . '/user/profile/lib.php');
                    require_once($file);
                    $class = "profile_field_{$field->datatype}";
                    $field = new $class($field->id, $user->id);
                    $value = $field->display_data();
                }
            }
        } else if (!empty($user->$field)) { // Field in the user table.
            $value = $user->$field;
        }

        $context = \mod_customcert\element_helper::get_context($this->get_id());
        return format_string($value, true, ['context' => $context]);
    }



    /**
     * Helper function that returns the result of a quiz.
     *
     * @param \stdClass $user the user we are rendering this for
     * @param bool $preview Is this a preview?
     * @return string
     */
    protected function get_quiz_result(\stdClass $user) : array {
        global $CFG, $DB;


        $flextextinfo = json_decode($this->get_data());


        //$sql = "select sum(maxmark) as maxres from mintmdl_quiz_slots where quizid = ".$flextextinfo->quizid;
        $sql = "select sum(maxmark) as maxres from {quiz_slots} where quizid = ".$flextextinfo->quizid;

        $quizmax = $DB->get_records_sql($sql);
        $count = 0;
        foreach( $quizmax as $currmax){
            $count = floatval($currmax->maxres);
        }

        //$sql = "select  q.sumgrades as sumgrade, q.* from mintmdl_quiz_attempts q join (select max(qa.timemodified) as mintime from mintmdl_quiz_attempts qa where qa.quiz = ".$flextextinfo->quizid." and qa.userid = ".$user->id." ) r on r.mintime = q.timemodified  where q.quiz = ".$flextextinfo->quizid." and q.userid =  ".$user->id ;
        $sql = "select  q.sumgrades as sumgrade, q.* from {quiz_attempts} q join (select max(qa.timemodified) as mintime from {quiz_attempts} qa where qa.quiz = ".$flextextinfo->quizid." and qa.userid = ".$user->id." ) r on r.mintime = q.timemodified  where q.quiz = ".$flextextinfo->quizid." and q.userid =  ".$user->id ;


        $value =  0;
        $quizquery = $DB->get_records_sql($sql);
        $quizresults = array();
        foreach($quizquery as $currquiz){
            $value = floatval($currquiz->sumgrade);
            $quizresults['timemodified'] = $currquiz->timemodified;
        }


        $quizres =  number_format(($value  / $count) * 100, 2, '.', '');
        $quizresults['quizresult'] = $quizres;

        return $quizresults;


    }
    /**
     * Helper function that returns the result of a quiz.
     *
     * @param \stdClass $user the user we are rendering this for
     * @param bool $preview Is this a preview?
     * @return string
     */
    protected function get_group_result(\stdClass $user, $questions): string
    {
        global $CFG, $DB;

        $flextextinfo = json_decode($this->get_data());
        //$sql = "select sum(fraction)  as result from  mintmdl_question_attempts inner join mintmdl_question_attempt_steps on mintmdl_question_attempts.id = mintmdl_question_attempt_steps.questionattemptid where slot in (".$questions.") and questionusageid in (select  uniqueid as sumgrade from mintmdl_quiz_attempts q join (select min(qa.timemodified) as mintime from mintmdl_quiz_attempts qa where qa.quiz = ".$flextextinfo->quizid." and qa.userid =  ".$user->id." ) r on r.mintime = q.timemodified  where q.quiz = ".$flextextinfo->quizid." and q.userid =   ".$user->id." )";
        $sql = "select sum(fraction)  as result from  {question_attempts} inner join {question_attempt_steps} on {question_attempts}.id = {question_attempt_steps}.questionattemptid where slot in (".$questions.") and questionusageid in (select  uniqueid as sumgrade from {quiz_attempts} q join (select min(qa.timemodified) as mintime from {quiz_attempts} qa where qa.quiz = ".$flextextinfo->quizid." and qa.userid =  ".$user->id." ) r on r.mintime = q.timemodified  where q.quiz = ".$flextextinfo->quizid." and q.userid =   ".$user->id." )";

        $quizres = $DB->get_records_sql($sql);
        $count = 0;
        foreach ($quizres as $currres) {
            $count = floatval($currres->result);
        }

        //$sql = "select sum(maxmark) as maxres from  mintmdl_question_attempts where slot in ( ".$questions.") and  questionusageid in (select  uniqueid as sumgrade from mintmdl_quiz_attempts q join (select min(qa.timemodified) as mintime from mintmdl_quiz_attempts qa where qa.quiz = ".$flextextinfo->quizid." and qa.userid = ".$user->id." ) r on r.mintime = q.timemodified  where q.quiz = ".$flextextinfo->quizid."  and q.userid = ".$user->id.")";

        $sql = "select sum(maxmark) as maxres from {question_attempts} where slot in ( ".$questions.") and  questionusageid in (select  uniqueid as sumgrade from {quiz_attempts} q join (select min(qa.timemodified) as mintime from {quiz_attempts} qa where qa.quiz = ".$flextextinfo->quizid." and qa.userid = ".$user->id." ) r on r.mintime = q.timemodified  where q.quiz = ".$flextextinfo->quizid."  and q.userid = ".$user->id.")";

        $quizmax = $DB->get_records_sql($sql);
        $value = 0;
        foreach ($quizmax as $currmax) {
            $value = floatval($currmax->maxres);
        }

        $quizeval = number_format(( $count / $value ) * 100, 2, '.', '');

        if ($count == 0 ){
            $quizeval = "00.00";
        }
        return $quizeval;

    }
    /**
     * Helper function that returns the text.
     *
     * @return object
     */
    protected function get_cert_info() : object
    {
        global $USER, $DB;

        // Get the page.
        $page = $DB->get_record('customcert_pages', array('id' => $this->get_pageid()), '*', MUST_EXIST);
        // Get the customcert this page belongs to.
        $customcert = $DB->get_record('customcert', array('templateid' => $page->templateid), '*', MUST_EXIST);
        // Now we can get the issue for this user.
        $issue = $DB->get_record('customcert_issues', array('userid' => $USER->id, 'customcertid' => $customcert->id), '*', IGNORE_MULTIPLE);

        return $issue;

    }
}


