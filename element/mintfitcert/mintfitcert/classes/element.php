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
 * This file contains the customcert element mintfitcert's core interaction API.
 *
 * @package    customcertelement_mintfitcert
 * @copyright  2013 Mark Nelson <markn@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace customcertelement_mintfitcert;

defined('MOODLE_INTERNAL') || die();

/**
 * The customcert element mintfitcert's core interaction API.
 *
 * @package    customcertelement_mintfitcert
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
        // Get the user profile fields.
        global $DB, $COURSE;
        // Get the user profile fields.
        $fields = array();

        //$courseid = \mod_customcert\element_helper::get_courseid($this->get_id());
        $courseid = \mod_customcert\element_helper::get_courseid($this->get_id());

        //select id,name from mintmdl_quiz where course = 7 ;
        $currsql = "select id,name from mintmdl_quiz where course = '".$COURSE->id."'";
        $quizzesincourse = $DB->get_records_sql($currsql);

  
        foreach( $quizzesincourse as $currquiz){
           $fields[$currquiz->id] = $currquiz->name;
        }

        $userfields = array(
            'firstname' => get_user_field_name('firstname'),
            'lastname' => get_user_field_name('lastname'),
            'email' => get_user_field_name('email'),
            'city' => get_user_field_name('city'),
            'country' => get_user_field_name('country'),
            'url' => get_user_field_name('url'),
            'icq' => get_user_field_name('icq'),
            'skype' => get_user_field_name('skype'),
            'aim' => get_user_field_name('aim'),
            'yahoo' => get_user_field_name('yahoo'),
            'msn' => get_user_field_name('msn'),
            'idnumber' => get_user_field_name('idnumber'),
            'institution' => get_user_field_name('institution'),
            'department' => get_user_field_name('department'),
            'phone1' => get_user_field_name('phone1'),
            'phone2' => get_user_field_name('phone2'),
            'address' => get_user_field_name('address')
        );
        // Get the user custom fields.
        $arrcustomfields = \availability_profile\condition::get_custom_profile_fields();
        $customfields = array();
        foreach ($arrcustomfields as $key => $customfield) {
            $customfields[$customfield->id] = $customfield->name;
        }
        // Combine the two.
        //$fields = $userfields + $customfields;
        \core_collator::asort($fields);

        // Create the select box where the user field is selected.
        $mform->addElement('select', 'mintfitcert', get_string('mintfitcert', 'customcertelement_mintfitcert'), $fields);
        $mform->setType('mintfitcert', PARAM_ALPHANUM);
        $mform->addHelpButton('mintfitcert', 'mintfitcert', 'customcertelement_mintfitcert');

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
        return $data->mintfitcert;
    }

    /**
     * Handles rendering the element on the pdf.
     *
     * @param \pdf $pdf the pdf object
     * @param bool $preview true if it is a preview, false otherwise
     * @param \stdClass $user the user we are rendering this for
     */
    public function render($pdf, $preview, $user) {

        global $USER;
        $val = $this->get_quiz_result($USER, true);
        $out = $this->make_html($val);
        \mod_customcert\element_helper::render_content($pdf, $this,$out);
    }

    /**
     * Render the element in html.
     *
     * This function is used to render the element when we are using the
     * drag and drop interface to position it.
     */
    public function render_html() {
        global $USER;

        $val = $this->get_quiz_result($USER, true);
        $out = $this->make_html($val);
        return \mod_customcert\element_helper::render_html_content($this, $out );

    }

    /**
     * Create certificate text.
     *
     * This function is used to create an HTML mark up to be displayed
     * on the cert
     */
    public function make_html($scoring) {
        $out = "<table ><tr><td style=\"height:820px;\">12&nbsp;</td></tr></table>";
        $out .= "<table><tr><td>&nbsp;</td></tr></table>";
        $out .= "<table><tr><td>&nbsp;</td></tr></table>";
        $out .= "<table  style=\"font-family: Arial, Helvetica, sans-serif;width:600px;background-color: #f5f5f5;text-align: center;\">";
        $out .= "<tr><td>Ottilie  Otterstedt</td></tr>";
        $out .= "<tr><td style=\"font-size: 60%;\">(ottilie.otterstedt@mail.de)</td></tr>";  
        $out .= "<tr><td>hat den MINTFIT Mathetest (Grundwissen I+II)</td></tr>";     
        $out .= "<tr><td>mit sehr guten (".$scoring."%) Leistungen absolviert.</td></tr>";                  
        $out .= "</table>";
        $out .= "<table><tr><td>&nbsp;</td></tr></table>";
        $out .= "<table style=\"font-family: Arial, Helvetica, sans-serif;width:600px;background-color: #f5f5f5;text-align: center;\"><tr><td>";
        $out .= "<table><tr><td>MINTFIT Hamburg</td></tr></table>";   
        $out .= "</td><td>";       
        $out .= "<table><tr><td>MINTFIT Hamburg</td></tr></table>";  
        $out .= "</td></tr></table>";



        return $out;

    }


    /**
     * Sets the data on the form when editing an element.
     *
     * @param \MoodleQuickForm $mform the edit_form instance
     */
    public function definition_after_data($mform) {
        if (!empty($this->get_data())) {
            $element = $mform->getElement('mintfitcert');
            $element->setValue($this->get_data());
        }
        parent::definition_after_data($mform);
    }

    /**
     * Helper function that returns the text.
     *
     * @param \stdClass $user the user we are rendering this for
     * @param bool $preview Is this a preview?
     * @return string
     */
    protected function get_user_field_value(\stdClass $user, bool $preview) : string {
        global $CFG, $DB;

        // The user field to display.
        $field = $this->get_data();
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
    protected function get_quiz_result(\stdClass $user, bool $preview) : string {
        global $CFG, $DB;

        $count = 0;
        $term = "select maxmark from mintmdl_quiz_slots where quizid = ".$this->get_data();
        //var_dump($term);
        $quizmax = $DB->get_records_sql("select sum(maxmark) as maxres from mintmdl_quiz_slots where quizid = ".$this->get_data());
 
        foreach( $quizmax as $currmax){
            
            $count = floatval($currmax->maxres);
        }


        $value =  0;     
  
        $quizresult = $DB->get_records_sql("select  q.sumgrades as sumgrade from mintmdl_quiz_attempts q join (select min(qa.timemodified) as mintime from mintmdl_quiz_attempts qa where qa.quiz = ".$this->get_data()." and qa.userid = ".$user->id." ) r on r.mintime = q.timemodified  where q.quiz = ".$this->get_data()." and q.userid =  ".$user->id );
        
    
        foreach( $quizresult as $currquiz){
            
            $value = floatval($currquiz->sumgrade);
        }
        
        $quizres =  number_format(($value  / $count) * 100, 2, '.', '');
        $context = \mod_customcert\element_helper::get_context($this->get_id());
        return format_string($quizres, true, ['context' => $context]);
    }

}
