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
 * Multiple choice question renderer classes.
 *
 * @package    qtype
 * @subpackage turprove
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Base class for generating the bits of output common to turprove
 * single and multiple questions.
 *
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class qtype_turprove_renderer_base extends qtype_with_combined_feedback_renderer {

    protected abstract function get_input_type();

    protected abstract function get_input_name(question_attempt $qa, $value);

    protected abstract function get_input_value($value);

    protected abstract function get_input_id(question_attempt $qa, $value);

    protected function get_answersound(question_answer $ans, $contextid, $slot, $usageid) {

        $fs = get_file_storage();
        $files = $fs->get_area_files(1, 'question', 'answersound', $ans->id);
        if ($file = end($files)) {
            $filename = $file->get_filename();
            if ($filename != '.') {
                return moodle_url::make_file_url('/pluginfile.php',
                        "/1/question/answersound/$usageid/$slot/$ans->id/$filename");
            }
        }
    }

    protected function get_questionimage($questionid, $contextid, $slot, $usageid) {

        $fs = get_file_storage();
        $files = $fs->get_area_files(1, 'question', 'questionimage', $questionid);
        if ($file = end($files)) {
            $filename = $file->get_filename();
            if ($filename != '.') {
                return moodle_url::make_file_url('/pluginfile.php',
                        "/1/question/questionimage/$usageid/$slot/$questionid/$filename");
            }
        }
    }

    protected function get_questionsound($questionid, $contextid, $slot, $usageid) {

        $fs = get_file_storage();
        $files = $fs->get_area_files(1, 'question', 'questionsound', $questionid);
        if ($file = end($files)) {
            $filename = $file->get_filename();
            if ($filename != '.') {
                return moodle_url::make_file_url('/pluginfile.php',
                        "/1/question/questionsound/$usageid/$slot/$questionid/$filename");
            }
        }
    }

    /**
     * Whether a choice should be considered right, wrong or partially right.
     * @param question_answer $ans representing one of the choices.
     * @return fload 1.0, 0.0 or something in between, respectively.
     */
    protected abstract function is_right(question_answer $ans);

    /**
     * 
     * @param question_attempt $qa
     * @param question_display_options $options
     * @return type
     */
    public function formulation_and_controls(question_attempt $qa, question_display_options $options) {

        $question = $qa->get_question();
        $html = '';

        $html .= html_writer::start_div('', array('id' => 'turprove_wrapper'));
        $html .= html_writer::start_div('', array('id' => 'turprove_leftcolumn'));
        $html .= html_writer::start_div('', array('id' => 'turprove_question'));

        $questionsoundurl = $this->get_questionsound($question->id,
                $question->contextid, $qa->get_slot(), $qa->get_usage_id());
        $audiosource = html_writer::tag('source', '',
                array('type' => 'audio/mpeg', 'src' => $questionsoundurl));
        $audiosource .= 'Your browser does not support the audio tag.'; // TODO: Lang string

        $html .= html_writer::tag('audio', $audiosource, array('id' => 'audiodiv'));
        $turprovequestionaudiodiv = html_writer::div('', 'audioplay', array('data-src' => $questionsoundurl));
        $html .= html_writer::div($turprovequestionaudiodiv, 'turprove_leftblock');
        $turprovequestiontextspan = html_writer::span($question->format_questiontext($qa));
        $html .= html_writer::div($turprovequestiontextspan, 'turprove_contentblock');
        $html .= html_writer::end_div(); // #turprove_question
        $html .= html_writer::start_div('', array('id' => 'turprove_yn'));
        $html .= html_writer::div('', 'turprove_leftblock');
        $html .= html_writer::div('', 'turprove_contentblock');
        $turproveyntextspan = html_writer::span('JA / NEJ'); // TODO: Lang string
        $html .= html_writer::div($turproveyntextspan, 'turprove_rightblock');
        $html .= html_writer::end_div(); // #turprove_yn

        $response = $question->get_response($qa);
        $useranswers = $this->get_turprove_answers($question->get_order($qa), $response);

        $ordinal = 1;
        foreach ($question->get_order($qa) as $value => $ansid) {
            $ans = $question->answers[$ansid];

            $html .= html_writer::start_div('turprove_answer');
            $turproveansweraudiodiv = html_writer::div('', 'audioplay',
                    array('data-src' => $this->get_answersound($ans,
                            $question->contextid, $qa->get_slot(), $qa->get_usage_id())));
            $html .= html_writer::div($turproveansweraudiodiv, 'turprove_leftblock');
            $ordinalspan = html_writer::span($ordinal . '. ');
            $ordinal++;
            $turproveanswertextlabel = html_writer::label(
                    $question->make_html_inline($ordinalspan .
                        $question->format_text($ans->answer,
                                $ans->answerformat, $qa, 'question', 'answer', $ansid)
                    ),
                    $this->get_input_id($qa, $value)
                    );
            $html .= html_writer::div($turproveanswertextlabel, 'turprove_contentblock');
            $turproveanswerinputfields = array(
                array( // yes
                    'type' => $this->get_input_type(),
                    'id' => $this->get_input_id($qa, $value . '_yes'),
                    'value' => $this->get_turprove_input_value($ansid, 1),
                    'name' => $this->get_turprove_field_name($qa, 'choice' . $value)
                ),
                array( // no
                    'type' => $this->get_input_type(),
                    'id' => $this->get_input_id($qa, $value . '_no'),
                    'value' => $this->get_turprove_input_value($ansid, 0),
                    'name' => $this->get_turprove_field_name($qa, 'choice' . $value)
                )
            );

            if ($responsesummary = $qa->get_response_summary()) {
                // This question attempt has been completed
                $responsearray = explode('; ', $responsesummary);
                $thisanswer = $ans->answer;
                $thisanswerisyes = $ans->tur_answer_truefalse;
                if (in_array($thisanswer, $responsearray)) {
                    // The correct answer has been selected
                    if ($thisanswerisyes) {
                        $turproveanswerinputfields[0]['checked'] = 'checked'; // Set the 'Yes' radio button to checked
                    } else {
                        $turproveanswerinputfields[1]['checked'] = 'checked'; // Set the 'No' radio button to checked
                    }
                } else {
                    // The incorrect answer has been selected
                    if ($thisanswerisyes) {
                        $turproveanswerinputfields[1]['checked'] = 'checked'; // Set the 'No' radio button to checked
                    } else {
                        $turproveanswerinputfields[0]['checked'] = 'checked'; // Set the 'Yes' radio button to checked
                    }
                }
            } else if (array_key_exists('choice' . $value, $response)) {
                // This question attempt has not yet been completed
                if ($response['choice' . $value] == 1) { // If the response is correct
                    if ($useranswers[$ansid] == 1) { // If the correct answer is 'Yes'
                        $turproveanswerinputfields[0]['checked'] = 'checked'; // Set the 'Yes' radio button to checked
                    } else { // If the correct answer is 'No'
                        $turproveanswerinputfields[1]['checked'] = 'checked'; // Set the 'No' radio button to checked
                    }
                } else if ($response['choice' . $value] == 0) { // If the response is incorrect
                    if ($useranswers[$ansid] == 1) { // If the correct answer is 'Yes'
                        $turproveanswerinputfields[1]['checked'] = 'checked'; // Set the 'No' radio button to checked
                    } else { // If the correct answer is 'No'
                        $turproveanswerinputfields[0]['checked'] = 'checked'; // Set the 'Yes' radio button to checked
                    }
                }
            }

            if ($qa->get_state()->is_finished()) {
                $turproveanswerinputfields[0]['disabled'] = 'disabled'; // yes radio button
                $turproveanswerinputfields[1]['disabled'] = 'disabled'; // no radio button
            }

            $turproveanswerfields = '';
            foreach ($turproveanswerinputfields as $turproveanswerinputfield) {
                $turproveanswerfields .= html_writer::empty_tag('input', $turproveanswerinputfield);
            }
            $html .= html_writer::div($turproveanswerfields, 'turprove_rightblock');
            $html .= html_writer::end_div(); // .turprove_answer
        }

        $html .= html_writer::end_div(); // #turprove_leftcolumn
        $turprovequestionimage = html_writer::img(
                $this->get_questionimage($question->id, $question->contextid, $qa->get_slot(), $qa->get_usage_id()),
                '', array('class' => '', 'width' => '', 'height' => ''));
        $html .= html_writer::div($turprovequestionimage, '', array('id' => 'turprove_rightcolumn'));
        $html .= html_writer::end_div(); // #turprove_wrapper

        if ($qa->get_state() == question_state::$invalid) {
            $html .= html_writer::div($question->get_validation_error($qa->get_last_qt_data()), 'validationerror');
        }

        $this->page->requires->js_init_call(
            'M.qtype_turprove.init',
            array(
                '#q' . $qa->get_slot(),
                $options->readonly,
                $question->autoplay
            ),
            false,
            array(
                'name'     => 'qtype_turprove',
                'fullpath' => '/question/type/turprove/module.js',
                'requires' => array('base', 'node', 'event', 'overlay'),
            )
        );

        return $html;
    }
}

/**
 * Subclass for generating the bits of output specific to turprove
 * multi=select questions.
 *
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qtype_turprove_multi_renderer extends qtype_turprove_renderer_base {

    protected function get_input_type() {
        return 'radio';
    }

    protected function get_input_id(question_attempt $qa, $value) {

        return $this->get_input_name($qa, $value);
    }

    protected function get_input_value($value) {

        return 1;
    }

    protected function get_turprove_input_value($ansid, $value) {
        global $DB;

        $correctanswer = $DB->get_field('question_answers',
                'tur_answer_truefalse', array('id' => $ansid));

        if ($correctanswer == $value) {
            $inputvalue = 1;
        } else {
            $inputvalue = 0;
        }

        return $inputvalue;
    }

    protected function get_input_name(question_attempt $qa, $value) {
        return $qa->get_qt_field_name('choice' . $value);
    }

    protected function get_turprove_field_name(question_attempt $qa, $value) {

        return $qa->get_qt_field_name($value);
    }

    protected function is_right(question_answer $ans) {

        // TODO: Review: depends on the custom column

        if ($ans->fraction > 0) {
            return 1;
        } else {
            return 0;
        }
    }

    public function correct_response(question_attempt $qa) {

        $question = $qa->get_question();
        $html = html_writer::tag('p', 'The correct answers are:');
        $html .= html_writer::start_tag('ul');
        $ordinal = 1;
        foreach ($question->get_order($qa) as $value => $ansid) {
            $ans = $question->answers[$ansid];
            $answertext = strip_tags($ans->answer);
            $yesorno = ($ans->tur_answer_truefalse == 1) ? get_string('yes') : get_string('no');
            $ordinalspan = html_writer::span($ordinal . '. ');
            $ordinal++;
            $answertext = $question->make_html_inline($ordinalspan .
                    $question->format_text($answertext . ': ' . $yesorno,
                    $ans->answerformat, $qa, 'question', 'answer', $ansid));
            $html .= html_writer::tag('li', $answertext);
        }
        $html .= html_writer::end_tag('ul');

        return $html;
    }

    protected function num_parts_correct(question_attempt $qa) {

         // TODO: Review/rewrite -

        if ($qa->get_question()->get_num_selected_choices($qa->get_last_qt_data()) >
                $qa->get_question()->get_num_correct_choices()) {
            return get_string('toomanyselected', 'qtype_turprove');
        }

        return parent::num_parts_correct($qa);
    }

    protected function get_turprove_answers($answerids, $response) {
        global $DB;

        list($turprovesql, $params) = $DB->get_in_or_equal($answerids);
        $sql = "SELECT id, tur_answer_truefalse
                  FROM {question_answers}
                 WHERE id {$turprovesql}
              ORDER BY CASE id";
        for ($i = 0; $i < count($answerids); $i++) {
            $sql .= ' WHEN ' . $answerids[$i] . ' THEN ' . $i;
        }
        $sql .= ' END';

        return $DB->get_records_sql_menu($sql, $params);
    }
}
