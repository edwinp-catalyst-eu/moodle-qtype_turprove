<?php

/**
 * Defines the editing form for the turprove question type.
 *
 * @package questions
 */
define('LOCAL_NUMANS_START', 4);

class question_edit_turprove_form extends question_edit_form {
    /*
     * 2369:     function view_array($array_in)
     * 2397:     function print_array($array_in)
     * 2412:     function debug($var="",$brOrHeader=0)
     */
    function definition_inner(&$mform) {
        global $COURSE, $CFG, $QTYPES;

        $mform->addElement('advcheckbox', 'autoplay', get_string('autoplay', 'qtype_turprove'), null, null, array(0, 1));
        $mform->removeElement('defaultgrade'); // fjerne 'standardkarakter for sp�rgsm�l' som tidligere er blevet tilf�jet
        $mform->addElement('hidden', 'defaultgrade', get_string('defaultgrade', 'quiz'), array('size' => 3)); // og s� tilf�jer vi det igen, men denne gang som skjult felt.
        $mform->setType('defaultgrade', PARAM_INT);
        $mform->setDefault('defaultgrade', 1);
        $mform->removeElement('penalty');
        $mform->addElement('hidden', 'penalty', get_string('penaltyfactor', 'quiz'), array('size' => 3));
        $mform->setType('penalty', PARAM_NUMBER);
        $mform->addRule('penalty', null, 'required', null, 'client');
        $mform->setDefault('penalty', 0);
        $mform->removeElement('generalfeedback');
        $mform->removeElement('image');

        if (isset($CFG->turimage)) {
            $mform->addElement('choosecoursefile', 'image', get_string('imagedisplay', 'quiz'), array('courseid' => $CFG->turimage, 'height' => 500, 'width' => 960, 'options' => 'none'));
        } else {
            $mform->addElement('choosecoursefile', 'image', get_string('imagedisplay', 'quiz'), array('height' => 500, 'width' => 960, 'options' => 'none'));
        }

        $menu = array(get_string('answersingleno', 'qtype_turprove'), get_string('answersingleyes', 'qtype_turprove'));
        $mform->addElement('hidden', 'single', 0);
        $mform->setDefault('single', 0);
        $mform->addElement('hidden', 'shuffleanswers', 1);
        $numberingoptions = $QTYPES[$this->qtype()]->get_numbering_styles();
        $mform->addElement('hidden', 'answernumbering', 'none');
        $mform->setDefault('answernumbering', 'none');

        if (isset($CFG->tursound)) {
            $mform->addElement('choosecoursefile', 'questionsound', get_string('questionsound', 'qtype_turprove'), array('courseid' => $CFG->tursound, 'height' => 500, 'width' => 960, 'options' => 'none'));
        } else {
            $mform->addElement('choosecoursefile', 'questionsound', get_string('questionsound', 'qtype_turprove'));
        }

        $question_difficulties = array();
        $question_difficulties[0] = get_string('q_easy1', 'qtype_turprove');
        $question_difficulties[1] = get_string('q_easy2', 'qtype_turprove');
        $question_difficulties[2] = get_string('q_easy3', 'qtype_turprove');
        $question_difficulties[3] = get_string('q_medium1', 'qtype_turprove');
        $question_difficulties[4] = get_string('q_medium2', 'qtype_turprove');
        $question_difficulties[5] = get_string('q_medium3', 'qtype_turprove');
        $question_difficulties[6] = get_string('q_hard1', 'qtype_turprove');
        $question_difficulties[7] = get_string('q_hard2', 'qtype_turprove');
        $question_difficulties[8] = get_string('q_hard3', 'qtype_turprove');
        $mform->addElement('select', 'qdifficulty', get_string('qdifficulty', 'qtype_turprove'), $question_difficulties);
        $creategrades = get_grade_options();
        $gradeoptions = $creategrades->gradeoptionsfull;
        $repeated = array();
        $repeated[] = & $mform->createElement('header', 'choicehdr', get_string('choiceno', 'qtype_turprove', '{no}'));
        $repeated[] = & $mform->createElement('htmleditor', 'answer', get_string('answer', 'quiz'));
        $repeated[] = & $mform->createElement('select', 'tur_answer_truefalse', get_string('correctanswer', 'qtype_turprove'), array(0 => 'Nej', 1 => 'Ja'));
        $repeated[] = & $mform->createElement('hidden', 'fraction', '0.25');
        $repeated[] = & $mform->createElement('htmleditor', 'feedback', get_string('feedback', 'quiz'));

        /* MPL */
        if (isset($CFG->tursound)) {
            $repeated[] = & $mform->createElement('choosecoursefile', 'answersound', get_string('answersound', 'qtype_turprove'), array('test' => 43, 'courseid' => $CFG->tursound, 'height' => 500, 'width' => 960, 'options' => 'none'));
        } else {
            $repeated[] = & $mform->createElement('choosecoursefile', 'answersound', get_string('answersound', 'qtype_turprove'));
        }
        /* MPL */

        if (isset($CFG->tursound)) {
            $repeated[] = & $mform->createElement('choosecoursefile', 'feedbacksound', get_string('feedbacksound', 'qtype_turprove'), array('courseid' => $CFG->tursound, 'height' => 500, 'width' => 960, 'options' => 'none'));
        } else {
            $repeated[] = & $mform->createElement('choosecoursefile', 'feedbacksound', get_string('feedbacksound', 'qtype_turprove'));
        }
        if (isset($this->question->options)) {
            $countanswers = count($this->question->options->answers);
        } else {
            $countanswers = 0;
        }
        $repeatsatstart = LOCAL_NUMANS_START; //(LOCAL_NUMANS_START > ($countanswers + QUESTION_NUMANS_ADD)) ? LOCAL_NUMANS_START : ($countanswers + QUESTION_NUMANS_ADD);
        $repeatedoptions = array();
        $mform->setType('questionsound', PARAM_RAW);
        $mform->setType('autoplay', PARAM_INT);
        $mform->setDefault('autoplay', 1);
        $mform->setType('answersound', PARAM_RAW);
        $mform->setType('feedbacksound', PARAM_RAW);
        $this->repeat_elements($repeated, $repeatsatstart, $repeatedoptions, 'noanswers', 'addanswers', QUESTION_NUMANS_ADD, get_string('addmorechoiceblanks', 'qtype_turprove'));
        $mform->removeElement('addanswers');
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
                print ($numAnswers . '-> Illegal number!');
        }
        return $turfraction;
    }

    function set_data($question) {
        //print('set_data' . '<br />');
        if (isset($question->options)) {
            $answers = $question->options->answers;
            if (count($answers)) {
                $key = 0;
                foreach ($answers as $answer) {
                    $default_values['answer[' . $key . ']'] = $answer->answer;
                    $default_values['answersound[' . $key . ']'] = $answer->answersound;
                    //  $default_values['fraction['.$key.']'] = $answer->fraction;
                    $default_values['fraction[' . $key . ']'] = $this->tur_setcustomfraction(count($answers));
                    $default_values['tur_answer_truefalse[' . $key . ']'] = $answer->tur_answer_truefalse;
                    $default_values['feedback[' . $key . ']'] = $answer->feedback;
                    $default_values['feedbacksound[' . $key . ']'] = $answer->feedbacksound;
                    $key++;
                }
            }
            $default_values['single'] = $question->options->single;
            $default_values['shuffleanswers'] = $question->options->shuffleanswers;
            $default_values['autoplay'] = $question->options->autoplay;
            $default_values['qdifficulty'] = $question->options->qdifficulty;
            $default_values['correctfeedback'] = $question->options->correctfeedback;
            $default_values['partiallycorrectfeedback'] = $question->options->partiallycorrectfeedback;
            $default_values['incorrectfeedback'] = $question->options->incorrectfeedback;
            $default_values['questionsound'] = $question->options->questionsound;
            $question = (object) ((array) $question + $default_values);
        }
        parent::set_data($question);
    }

    function qtype() {
        return 'turprove';
    }

    /* http://snippets.dzone.com/posts/show/2776 */
    function file_extension($filename) {
        $path_info = pathinfo($filename);
        $allowed = $path_info['extension'];
        if ($allowed == 'mp3') {
            return true;
        }
        return false;
    }

    function image_extension($filename) {
        $path_info = pathinfo($filename);
        $allowed = $path_info['extension'];
        if ($allowed == 'gif' || $allowed == 'jpg' || $allowed == 'jpeg' || $allowed == 'png') {
            return true;
        }
        return false;
    }

    function validation($data) {
        $errors = array();
        $answers = $data['answer'];
        $answercount = 0;
        $numAnswers = 0;
        $totalfraction = 0;
        $maxfraction = -1;
        $answersounds = array();
        $feedbacksounds = array();
        $answercount = 0;
        $tur_num_true = 0;

        foreach ($answers as $key => $answer) {
            $trimmedanswer = trim($answer);
            if (!empty($trimmedanswer)) {
                $numAnswers++;
            }
        }
        foreach ($answers as $key => $answer) {
            $trimmedanswer = trim($answer);
            if (!empty($trimmedanswer)) {
                $answercount++;
            }
            if ($answer != '') {
                if ($data['fraction'][$key] > 0) {
                    $data['fraction'][$key] = $this->tur_setcustomfraction($numAnswers);
                    $totalfraction += $data['fraction'][$key]; // l�gger alle fraktioner sammen
                }
                if ($data['fraction'][$key] > $maxfraction) {
                    $maxfraction = $data['fraction'][$key];
                }
            }
            $answersounds[$key] = $data['answersound[' . $key . ']'];
            $feedbacksounds[$key] = $data['feedbacksound[' . $key . ']'];
        }

        foreach ($answersounds as $key => $value) {
            if ($value != '' && !$this->file_extension($value)) {
                $errors['answersound[' . $key . ']'] = get_string('mp3only', 'qtype_turprove', 2);
            }
        }
        foreach ($feedbacksounds as $key => $value) {
            if ($value != '' && !$this->file_extension($value)) {
                $errors['feedbacksound[' . $key . ']'] = get_string('mp3only', 'qtype_turprove', 2);
            }
        }
        if ($data['questionsound'] <> '') {
            if (!$this->file_extension($data['questionsound'])) {
                $errors['questionsound'] = get_string('mp3only', 'qtype_turprove', 2);
            }
        }
        if (isset($data['image'])) {
            if ($data['image'] <> '') {
                if (!$this->image_extension($data['image'])) {
                    $errors['image'] = get_string('imageformat', 'qtype_turprove', 2);
                }
            }
        }
        if ($answercount == 0) {
            $errors['answer[0]'] = get_string('notenoughanswers', 'qtype_turprove', 2);
            $errors['answer[1]'] = get_string('notenoughanswers', 'qtype_turprove', 2);
        } elseif ($answercount == 1) {
            $errors['answer[1]'] = get_string('notenoughanswers', 'qtype_turprove', 2);
        }
        if ($data['single']) {
            if ($maxfraction != 1) {
                $maxfraction = $maxfraction * 100;
                $errors['fraction[0]'] = get_string('errfractionsnomax', 'qtype_turprove', $maxfraction);
            }
        } else {
            $totalfraction = round($totalfraction, 2);
            if ($totalfraction != 1) {
                $totalfraction = $totalfraction * 100;
                $errors['fraction[0]'] = get_string('errfractionsaddwrong', 'qtype_turprove', $totalfraction);
            }
        }
        return $errors;
    }
}
?>