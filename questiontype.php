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
 * The questiontype class for the turprove question type.
 *
 * @package    qtype
 * @subpackage turprove
 * @copyright  1999 onwards Martin Dougiamas  {@link http://moodle.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/questionlib.php');

/**
 * The turprove question type.
 *
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qtype_turprove extends question_type {

    /**
     * 
     * @global type $PAGE
     */
    public function find_standard_scripts() {
        global $PAGE;

        $PAGE->requires->jquery();

        parent::find_standard_scripts();
    }

    /**
     * 
     * @global type $DB
     * @param type $question
     */
    public function get_question_options($question) {
        global $DB;

        $question->options = $DB->get_record('qtype_turprove_options',
                array('questionid' => $question->id), '*', MUST_EXIST);

        parent::get_question_options($question);
    }

    /**
     * This function writes to the question_answers table
     * 
     * 
     * @global type $DB
     * @param type $question
     * @return \stdClass
     */
    public function save_question_options($question) {
        global $DB;

        $numAnswers = 0;

        $context = $question->context;
        $result = new stdClass();

        $oldanswers = $DB->get_records('question_answers',
                array('question' => $question->id), 'id ASC');

        // Following hack to check at least two answers exist
        $answercount = 0;
        foreach ($question->answer as $key => $answer) {
            if ($answer != '') {
                $answercount++;
            }
        }
        if ($answercount < 2) { // Check there are at lest 2 answers for turprove.
            $result->notice = get_string('notenoughanswers', 'qtype_turprove', '2');
            return $result;
        }

        /* DMD */
        foreach ($question->answer as $key => $answer) {
            $trimmedanswer = trim($answer['text']);
            if (!empty($trimmedanswer)) {
                $numAnswers++;
            }
        }

        // Insert all the new answers.
        $totalfraction = 0;
        $maxfraction = -1;
        foreach ($question->answer as $key => $answerdata) {
            if (trim($answerdata['text']) == '') {
                continue;
            }

            // Update an existing answer if possible.
            $answer = array_shift($oldanswers);

            if (!$answer) {
                $answer = new stdClass();
                $answer->question = $question->id;
                $answer->answer = '';
                $answer->feedback = '';
                $answer->id = $DB->insert_record('question_answers', $answer);
            }

            // Doing an import.
            $answer->answer = $this->import_or_save_files($answerdata,
                    $context, 'question', 'answer', $answer->id);

            $answer->answerformat = $answerdata['format'];

            // Save answer 'answersound'
            file_save_draft_area_files($question->answersound[$key], 1,
                    'question', 'answersound', $answer->id, $this->fileoptions);

            $answer->fraction =  $this->tur_setcustomfraction($numAnswers);

            $answer->tur_answer_truefalse = $question->tur_answer_truefalse[$key];

            $answer->feedback = $this->import_or_save_files($question->feedback[$key],
                    $context, 'question', 'answerfeedback', $answer->id);

            $answer->feedbackformat = $question->feedback[$key]['format'];

            // Save answer 'feedbacksound'
            file_save_draft_area_files($question->feedbacksound[$key], 1,
                    'question', 'feedbacksound', $answer->id, $this->fileoptions);

            $DB->update_record('question_answers', $answer);

            if ($question->fraction[$key] > 0) {
                $totalfraction += $question->fraction[$key];
            }
            if ($question->fraction[$key] > $maxfraction) {
                $maxfraction = $question->fraction[$key];
            }
        }

        // Delete any left over old answer records.
        $fs = get_file_storage();
        foreach ($oldanswers as $oldanswer) {
            $fs->delete_area_files($context->id, 'question', 'answerfeedback', $oldanswer->id);
            $DB->delete_records('question_answers', array('id' => $oldanswer->id));
        }

        $options = $DB->get_record('qtype_turprove_options', array('questionid' => $question->id));

        if (!$options) {
            $options = new stdClass();
            $options->questionid = $question->id;
            $options->correctfeedback = '';
            $options->partiallycorrectfeedback = '';
            $options->incorrectfeedback = '';
            $options->id = $DB->insert_record('qtype_turprove_options', $options);
        }

        $options->single = $question->single;
        $options->autoplay = $question->autoplay;
        $options->qdifficulty = $question->qdifficulty;

        if (isset($question->layout)) {
            $options->layout = $question->layout;
        }

        $options->shuffleanswers = $question->shuffleanswers;

        $options = $this->save_combined_feedback_helper($options, $question, $context, true);

        $DB->update_record('qtype_turprove_options', $options);

        $this->save_hints($question, true);

        // Save question 'questionimage'
        file_save_draft_area_files($question->questionimage, 1,
                'question', 'questionimage', $question->id, $this->fileoptions);

        // Save question 'questionsound'
        file_save_draft_area_files($question->questionsound, 1,
                'question', 'questionsound', $question->id, $this->fileoptions);
    }

    /*
    public function save_question($question, $form) {

        return parent::save_question($question, $form);
    }
    */

    protected function make_question_instance($questiondata) {

        question_bank::load_question_definition_classes($this->name());
        return new qtype_turprove_multi_question();
    }

    protected function make_hint($hint) {
        return question_hint_with_parts::load_from_record($hint);
    }

    protected function initialise_question_instance(question_definition $question, $questiondata) {

        parent::initialise_question_instance($question, $questiondata);

        $question->shuffleanswers = $questiondata->options->shuffleanswers;
        $question->qdifficulty = $questiondata->options->qdifficulty;
        $question->autoplay = $questiondata->options->autoplay;

        $questiondata->options->correctfeedbackformat = 1;
        $questiondata->options->partiallycorrectfeedbackformat = 1;
        $questiondata->options->incorrectfeedbackformat = 1;
        $questiondata->options->shownumcorrect = 1;

        if (!empty($questiondata->options->layout)) {
            $question->layout = $questiondata->options->layout;
        } else {
            $question->layout = qtype_turprove_multi_question::LAYOUT_VERTICAL;
        }

        $this->initialise_combined_feedback($question, $questiondata, true);
        $this->initialise_question_answers($question, $questiondata, false);
    }

    /**
     * Initialise question_definition::answers field.
     * @param question_definition $question the question_definition we are creating.
     * @param object $questiondata the question data loaded from the database.
     * @param bool $forceplaintextanswers most qtypes assume that answers are
     *      FORMAT_PLAIN, and dont use the answerformat DB column (it contains
     *      the default 0 = FORMAT_MOODLE). Therefore, by default this method
     *      ingores answerformat. Pass false here to use answerformat. For example
     *      multichoice does this.
     */
    protected function initialise_question_answers(question_definition $question,
            $questiondata, $forceplaintextanswers = true) {

        $question->answers = array();
        if (empty($questiondata->options->answers)) {
            return;
        }
        foreach ($questiondata->options->answers as $a) {
            $question->answers[$a->id] = $this->make_answer($a);
            if (!$forceplaintextanswers) {
                $question->answers[$a->id]->answerformat = $a->answerformat;
            }
            $question->answers[$a->id]->tur_answer_truefalse = $a->tur_answer_truefalse;
        }
    }

    public function delete_question($questionid, $contextid) {
        global $DB;

        $DB->delete_records('qtype_turprove_options', array('questionid' => $questionid));
        parent::delete_question($questionid, $contextid);
    }

    public function get_random_guess_score($questiondata) {
        if (!$questiondata->options->single) {
            // Pretty much impossible to compute for _multi questions. Don't try.
            return null;
        }

        // Single choice questions - average choice fraction.
        $totalfraction = 0;
        foreach ($questiondata->options->answers as $answer) {
            $totalfraction += $answer->fraction;
        }
        return $totalfraction / count($questiondata->options->answers);
    }

    public function get_possible_responses($questiondata) {
        if ($questiondata->options->single) {
            $responses = array();

            foreach ($questiondata->options->answers as $aid => $answer) {
                $responses[$aid] = new question_possible_response(
                        question_utils::to_plain_text($answer->answer, $answer->answerformat),
                        $answer->fraction);
            }

            $responses[null] = question_possible_response::no_response();
            return array($questiondata->id => $responses);
        } else {
            $parts = array();

            foreach ($questiondata->options->answers as $aid => $answer) {
                $parts[$aid] = array($aid => new question_possible_response(
                        question_utils::to_plain_text($answer->answer, $answer->answerformat),
                        $answer->fraction));
            }

            return $parts;
        }
    }

    public function move_files($questionid, $oldcontextid, $newcontextid) {
        parent::move_files($questionid, $oldcontextid, $newcontextid);
        $this->move_files_in_answers($questionid, $oldcontextid, $newcontextid, true);
        $this->move_files_in_combined_feedback($questionid, $oldcontextid, $newcontextid);
        $this->move_files_in_hints($questionid, $oldcontextid, $newcontextid);
    }

    protected function delete_files($questionid, $contextid) {
        parent::delete_files($questionid, $contextid);
        $this->delete_files_in_answers($questionid, $contextid, true);
        $this->delete_files_in_combined_feedback($questionid, $contextid);
        $this->delete_files_in_hints($questionid, $contextid);
    }

    function tur_setcustomfraction($numAnswers) {
        $turfraction = 0;
        switch ($numAnswers) {
            case 1:
                $turfraction = 1;
                break;
            case 2:
                $turfraction = 0.5;
                break;
            case 3:
                $turfraction = 0.33333;
                break;
            case 4:
                $turfraction = 0.25;
                break;
            case 5:
                $turfraction = 0.20;
                break;
            case 10:
                $turfraction = 0.1;
                break;
            default:
                print($numAnswers . '-> Illegal number!');
        }

        return $turfraction;
    }
}
