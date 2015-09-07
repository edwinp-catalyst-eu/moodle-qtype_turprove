<?php

/**
 * Defines the editing form for the turmultiplechoice question type.
 *
 * @package questions
 */
// local define for initial number of answers
define('LOCAL_NUMANS_START', 4);
/*
 * multiple choice editing form definition.
 */
class question_edit_turmultiplechoice_form extends question_edit_form {

    /**
     * Add question-type specific form fields.
     *
     * @param object $mform the form being built.
     */
    /*
     * 2369:     function view_array($array_in)
     * 2397:     function print_array($array_in)
     * 2412:     function debug($var="",$brOrHeader=0)
     */
    function definition_inner(&$mform) {
        global $COURSE, $CFG, $QTYPES;

        /*  autoplayfeedbacksound MPL */
        $mform->addElement('advcheckbox', 'autoplay', get_string('autoplay', 'qtype_turmultiplechoice'), null, null, array(0, 1));
        $menu = array(get_string('answersingleno', 'qtype_turmultiplechoice'), get_string('answersingleyes', 'qtype_turmultiplechoice'));
        $mform->addElement('select', 'single', get_string('answerhowmany', 'qtype_turmultiplechoice'), $menu);
        $mform->setDefault('single', 0);

        // standardkarakter for sp�rgsm�l - skjules
        $mform->removeElement('defaultgrade'); // fjerne 'standardkarakter for sp�rgsm�l' som tidligere er blevet tilf�jet
        $mform->addElement('hidden', 'defaultgrade', get_string('defaultgrade', 'quiz'), array('size' => 3)); // og s� tilf�jer vi det igen, men denne gang som skjult felt.
        $mform->setType('defaultgrade', PARAM_INT);
        $mform->setDefault('defaultgrade', 1);

        // Strafkvotient skjules og s�ttes til v�rdien 0
        $mform->removeElement('penalty');
        $mform->addElement('hidden', 'penalty', get_string('penaltyfactor', 'quiz'), array('size' => 3));
        $mform->setType('penalty', PARAM_NUMBER);
        $mform->addRule('penalty', null, 'required', null, 'client');
        $mform->setDefault('penalty', 0);

        //Generel feedback fjernes. Hvis den �nskes tilbage fjernes denne kode blot
        $mform->removeElement('generalfeedback');
        $mform->removeElement('image');
        if (isset($CFG->turimage)) {
            $mform->addElement('choosecoursefile', 'image', get_string('imagedisplay', 'quiz'), array('courseid' => $CFG->turimage, 'height' => 500, 'width' => 960, 'options' => 'none'));
        } else {
            $mform->addElement('choosecoursefile', 'image', get_string('imagedisplay', 'quiz'), array('height' => 500, 'width' => 960, 'options' => 'none'));
        }
        $mform->addElement('hidden', 'shuffleanswers', 1);
        $mform->setDefault('answernumbering', 'none');
        $numberingoptions = $QTYPES[$this->qtype()]->get_numbering_styles();
        $menu = array();
        foreach ($numberingoptions as $numberingoption) {
            $menu[$numberingoption] = get_string('answernumbering' . $numberingoption, 'qtype_multichoice');
        }
        $mform->addElement('hidden', 'answernumbering', get_string('answernumbering', 'qtype_multichoice'), $menu);
        $mform->setDefault('answernumbering', 'none');

        // If TUR soundcourse is set in config.php
        if (isset($CFG->tursound)) {
            $mform->addElement('choosecoursefile', 'questionsound', get_string('questionsound', 'qtype_turmultiplechoice'), array('courseid' => $CFG->tursound, 'height' => 500, 'width' => 960, 'options' => 'none'));
        } else {
            $mform->addElement('choosecoursefile', 'questionsound', get_string('questionsound', 'qtype_turmultiplechoice'), array('height' => 500, 'width' => 960, 'options' => 'none'));
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
        $repeated[] = & $mform->createElement('header', 'choicehdr', get_string('choiceno', 'qtype_turmultiplechoice', '{no}'));
        $repeated[] = & $mform->createElement('htmleditor', 'answer', get_string('answer', 'quiz'));
        if (isset($CFG->tursound)) {
            $repeated[] = & $mform->createElement('choosecoursefile', 'answersound', get_string('answersound', 'qtype_turmultiplechoice'), array('courseid' => $CFG->tursound, 'height' => 500, 'width' => 960, 'options' => 'none'));
        } else {
            $repeated[] = & $mform->createElement('choosecoursefile', 'answersound', get_string('answersound', 'qtype_turmultiplechoice'), array('height' => 500, 'width' => 960, 'options' => 'none'));
        }
        $turgradeoptions = array();
        $turgradeoptions['1'] = '100%';
        $turgradeoptions['0.75'] = '75%';
        $turgradeoptions['0.66666'] = '66.666%';
        $turgradeoptions['0.5'] = '50%';
        $turgradeoptions['0.33333'] = '33.333%';
        $turgradeoptions['0.25'] = '25%';
        $turgradeoptions[0] = 'Ingen';

        $repeated[] = & $mform->createElement('select', 'fraction', get_string('grade'), $turgradeoptions);
        $repeated[] = & $mform->createElement('htmleditor', 'feedback', get_string('feedback', 'quiz'));
        if (isset($CFG->tursound)) {
            $repeated[] = & $mform->createElement('choosecoursefile', 'feedbacksound', get_string('feedbacksound', 'qtype_turmultiplechoice'), array('courseid' => $CFG->tursound, 'height' => 500, 'width' => 960, 'options' => 'none'));
        } else {
            $repeated[] = & $mform->createElement('choosecoursefile', 'feedbacksound', get_string('feedbacksound', 'qtype_turmultiplechoice'), array('height' => 500, 'width' => 960, 'options' => 'none'));
        }
        if (isset($this->question->options)) {
            $countanswers = count($this->question->options->answers);
        } else {
            $countanswers = 0;
        }
        $repeatsatstart = LOCAL_NUMANS_START; //(LOCAL_NUMANS_START > ($countanswers + QUESTION_NUMANS_ADD)) ? LOCAL_NUMANS_START : ($countanswers + QUESTION_NUMANS_ADD);
        $repeatedoptions = array();
        $repeatedoptions['fraction']['default'] = -1;
        $mform->setType('answer', PARAM_RAW);
        $mform->setType('questionsound', PARAM_RAW);
        $mform->setType('autoplay', PARAM_INT);
        $mform->setType('answersound', PARAM_RAW);
        $mform->setType('feedbacksound', PARAM_RAW);

        $this->repeat_elements($repeated, $repeatsatstart, $repeatedoptions, 'noanswers', 'addanswers', QUESTION_NUMANS_ADD, get_string('addmorechoiceblanks', 'qtype_turmultiplechoice'));
        $mform->removeElement('addanswers');
        /* PTK udkommenteret for at fjerne */
        $mform->addElement('header', 'overallfeedbackhdr', get_string('overallfeedback', 'qtype_turmultiplechoice'));
        $mform->addElement('htmleditor', 'correctfeedback', get_string('correctfeedback', 'qtype_turmultiplechoice'));
        $mform->setType('correctfeedback', PARAM_RAW);
        $mform->addElement('htmleditor', 'partiallycorrectfeedback', get_string('partiallycorrectfeedback', 'qtype_turmultiplechoice'));
        $mform->setType('partiallycorrectfeedback', PARAM_RAW);
        $mform->addElement('htmleditor', 'incorrectfeedback', get_string('incorrectfeedback', 'qtype_turmultiplechoice'));
        $mform->setType('incorrectfeedback', PARAM_RAW);
    }

    function set_data($question) {
        if (isset($question->options)) {
            $answers = $question->options->answers;
            if (count($answers)) {
                $key = 0;
                foreach ($answers as $answer) {
                    $default_values['answer[' . $key . ']'] = $answer->answer;
                    $default_values['answersound[' . $key . ']'] = $answer->answersound;
                    $default_values['fraction[' . $key . ']'] = $answer->fraction;
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
        return 'turmultiplechoice';
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

    function validation($data) {
        $errors = array();
        $answers = $data['answer'];
        $answercount = 0;
        $totalfraction = 0;
        $maxfraction = -1;

        $answersounds = array();
        $feedbacksounds = array();

        $answercount = 0;
        $tur_num_true = 0;
        foreach ($answers as $key => $answer) {
            //check no of choices
            $trimmedanswer = trim($answer);
            if (!empty($trimmedanswer)) {
                $answercount++;
            }
            if ($data['fraction'][$key] > 0) {
                $tur_num_true++;
            }
        }
        reset($answers);

        if ($tur_num_true > 0) {
            $tur_fraction = 1 / $tur_num_true;
        }

        foreach ($answers as $key => $answer) {
            //check no of choices
            $trimmedanswer = trim($answer);
            if (!empty($trimmedanswer)) {
                $answercount++;
            }
            //check grades
            if ($answer != '') {
                if ($data['fraction'][$key] > 0) {
                    $data['fraction'][$key] = $tur_fraction;
                    $totalfraction += $data['fraction'][$key]; // l�gger alle fraktioner sammen
                }
                if ($data['fraction'][$key] > $maxfraction) {
                    $maxfraction = $data['fraction'][$key];
                }
            }
            $answersounds[$key] = $data['answersound[' . $key . ']'];
            $feedbacksounds[$key] = $data['feedbacksound[' . $key . ']'];
        } //foreach
        /* Answersound must be mp3 */
        foreach ($answersounds as $key => $value) {
            if ($value != '' && !$this->file_extension($value)) {
                $errors['answersound[' . $key . ']'] = get_string('mp3only', 'qtype_turmultiplechoice', 2);
            }
        }

        /* Feedbacksound must be mp3 */
        foreach ($feedbacksounds as $key => $value) {
            if ($value != '' && !$this->file_extension($value)) {
                $errors['feedbacksound[' . $key . ']'] = get_string('mp3only', 'qtype_turmultiplechoice', 2);
            }
        }

        /* Questionsound must be mp3 */
        if ($data['questionsound'] <> '') {
            if (!$this->file_extension($data['questionsound'])) {
                $errors['questionsound'] = get_string('mp3only', 'qtype_turmultiplechoice', 2);
            }
        }
        if ($answercount == 0) {
            $errors['answer[0]'] = get_string('notenoughanswers', 'qtype_turmultiplechoice', 2);
            $errors['answer[1]'] = get_string('notenoughanswers', 'qtype_turmultiplechoice', 2);
        } elseif ($answercount == 1) {
            $errors['answer[1]'] = get_string('notenoughanswers', 'qtype_turmultiplechoice', 2);
        }
        /// Perform sanity checks on fractional grades
        if ($data['single']) {
            if ($maxfraction != 1) {
                $maxfraction = $maxfraction * 100;
            }
        } else {
            $totalfraction = round($totalfraction, 2);
            if ($totalfraction != 1) {
                $totalfraction = $totalfraction * 100;
            }
        }
        return $errors;
    }
}
?>