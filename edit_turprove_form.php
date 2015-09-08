<?php

require_once(dirname(__FILE__) . '/helpers.php');

/**
 * Defines the editing form for the turprove question type.
 *
 * @package questions
 */
define('LOCAL_NUMANS_START', 4);

class qtype_turprove_edit_form extends question_edit_form {

    function qtype() {
        return 'turprove';
    }
    
    function definition_inner($mform) {
        global $COURSE, $CFG, $QTYPES;

        $mform->addElement('advcheckbox', 'autoplay', get_string('autoplay', 'qtype_turprove'), null, null, array(0, 1));
        $mform->addElement('hidden', 'defaultgrade', get_string('defaultgrade', 'quiz'), array('size' => 3)); // og s� tilf�jer vi det igen, men denne gang som skjult felt.
        $mform->setType('defaultgrade', PARAM_INT);
        $mform->setDefault('defaultgrade', 1);
        $mform->addElement('hidden', 'penalty', get_string('penaltyfactor', 'question'), array('size' => 3));
        $mform->setType('penalty', PARAM_NUMBER);
        $mform->addRule('penalty', null, 'required', null, 'client');
        $mform->setDefault('penalty', 0);

        if (isset($CFG->turimage)) {
            $mform->addElement('filepicker', 'image', get_string('imagedisplay', 'quiz'), array('courseid' => $CFG->turimage, 'height' => 500, 'width' => 960, 'options' => 'none'));
        } else {
            $mform->addElement('filepicker', 'image', get_string('imagedisplay', 'quiz'), array('height' => 500, 'width' => 960, 'options' => 'none'));
        }

        $menu = array(get_string('answersingleno', 'qtype_turprove'), get_string('answersingleyes', 'qtype_turprove'));
        $mform->addElement('hidden', 'single', 0);
        $mform->setDefault('single', 0);
        $mform->setType('single', PARAM_INT);
        $mform->addElement('hidden', 'shuffleanswers', 1);
        $mform->setType('shuffleanswers', PARAM_BOOL);
        $mform->addElement('hidden', 'answernumbering',
                get_string('answernumbering', 'qtype_turprove'),
                qtype_turprove::get_numbering_styles());
        $mform->setDefault('answernumbering', 'none');
        $mform->setType('answernumbering', PARAM_RAW);

        if (isset($CFG->tursound)) {
            $mform->addElement('filepicker', 'questionsound', get_string('questionsound', 'qtype_turprove'), array('courseid' => $CFG->tursound, 'height' => 500, 'width' => 960, 'options' => 'none'), array('accepted_types' => '*.mp3'));
        } else {
            $mform->addElement('filepicker', 'questionsound', get_string('questionsound', 'qtype_turprove'), null, array('accepted_types' => '*.mp3'));
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
        
        $this->add_per_answer_fields($mform, get_string('choiceno', 'qtype_turprove', '{no}'),
                $gradeoptions, max(LOCAL_NUMANS_START, QUESTION_NUMANS_START));
        
        $mform->setType('questionsound', PARAM_RAW);
        $mform->setType('autoplay', PARAM_INT);
        $mform->setDefault('autoplay', 1);
    }
	
    function get_per_answer_fields($mform, $label, $gradeoptions, &$repeatedoptions, &$answersoption) {
        $repeated = array();
        $repeated[] = $mform->createElement('editor', 'answer', $label, array('rows' => 1), $this->editoroptions);
        $repeated[] = $mform->createElement('select', 'tur_answer_truefalse', get_string('correctanswer', 'qtype_turprove'), array(0 => 'Nej', 1 => 'Ja'));
        $repeated[] = $mform->createElement('editor', 'feedback', get_string('feedback', 'question'), array('rows' => 1), $this->editoroptions);
        $repeated[] = $mform->createElement('hidden', 'fraction', '0.25');

        if (isset($CFG->tursound)) {
            $repeated[] = $mform->createElement('filepicker', 'answersound', get_string('answersound', 'qtype_turprove'), array('test' => 43, 'courseid' => $CFG->tursound, 'height' => 500, 'width' => 960, 'options' => 'none'), array('accepted_types' => '*.mp3'));
        } else {
            $repeated[] = $mform->createElement('filepicker', 'answersound', get_string('answersound', 'qtype_turprove'), null, array('accepted_types' => '*.mp3'));
        }

        if (isset($CFG->tursound)) {
            $repeated[] = $mform->createElement('filepicker', 'feedbacksound', get_string('feedbacksound', 'qtype_turprove'), array('courseid' => $CFG->tursound, 'height' => 500, 'width' => 960, 'options' => 'none'), array('accepted_types' => '*.mp3'));
        } else {
            $repeated[] = $mform->createElement('filepicker', 'feedbacksound', get_string('feedbacksound', 'qtype_turprove'), null, array('accepted_types' => '*.mp3'));
        }
        
        $repeatedoptions['fraction']['default'] = 0.25;
        $answersoption = 'answers';
        
        $mform->setType('answersound', PARAM_RAW);
        $mform->setType('feedbacksound', PARAM_RAW);
        $mform->setType('fraction', PARAM_FLOAT);
        
        return $repeated;
    }

    function set_data($question) {
//    	print "set_data question: ";var_dump($question);
		
        if (isset($question->options) && is_array($question->options->answers)) {
            $answers = $question->options->answers;
            $default_values = array();
            $key = 0;
			
            foreach ($answers as $answer) {
//    			print "set_data answer: ";var_dump($answer);
                $default_values['answer[\''.$key.'\']'] = $answer->answer;
                $default_values['answer['.$key.']']['text'] = $answer->answer;
                $default_values['fraction['.$key.']'] = $answer->fraction;
                $default_values['feedback['.$key.']']['text'] = $answer->feedback;
                $default_values['answersound['.$key.']'] = $answer->answersound;
                $default_values['feedbacksound['.$key.']'] = $answer->feedbacksound;
                $default_values['tur_answer_truefalse['.$key.']'] = $answer->tur_answer_truefalse;
                $key++;
            }
			
            $default_values['single'] = $question->options->single;
            $default_values['shuffleanswers'] = $question->options->shuffleanswers;
            $default_values['autoplay'] = $question->options->autoplay;
            $default_values['qdifficulty'] = $question->options->qdifficulty;
            $default_values['correctfeedback'] = $question->options->correctfeedback;
            $default_values['partiallycorrectfeedback'] = $question->options->partiallycorrectfeedback;
            $default_values['incorrectfeedback'] = $question->options->incorrectfeedback;
            $default_values['questionsound'] = $question->options->questionsound;

            $question = (object)((array)$question + $default_values);
			
//    		print "set_data default_values: ";var_dump($default_values);
//    		print "set_data question: ";var_dump($question);
        }
		 
        parent::set_data($question);
    }

    function validation($data, $files) {
    	//var_dump($data);
		//var_dump($files);
		
        $errors = parent::validation($data, $files);
        $answers = $data['answer'];
        $answercount = 0;

        $totalfraction = 0;
        $maxfraction = -1;
		
        $tur_num_true = 0;

        foreach ($answers as $key => $answer) {
            $trimmedanswer = trim($answer['text']);
            if (!empty($trimmedanswer)) {
                $answercount++;
            }
        }
		
        foreach ($answers as $key => $answer) {
            $trimmedanswer = trim($answer['text']);
            if (!empty($trimmedanswer)) {
                if ($data['fraction'][$key] > 0) {
                    $data['fraction'][$key] = tur_getcustomfraction($answercount);
                    $totalfraction += $data['fraction'][$key]; // l�gger alle fraktioner sammen
                }
                if ($data['fraction'][$key] > $maxfraction) {
                    $maxfraction = $data['fraction'][$key];
                }
            }
        }
		
		//var_dump($data['fraction']);
		//var_dump($totalfraction);
        //var_dump($maxfraction);
        
        if ($answercount == 0) {
            $errors['answer[0]'] = get_string('notenoughanswers', 'qtype_turprove', 2);
            $errors['answer[1]'] = get_string('notenoughanswers', 'qtype_turprove', 2);
        } elseif ($answercount == 1) {
            $errors['answer[1]'] = get_string('notenoughanswers', 'qtype_turprove', 2);
        }
		
         // Perform sanity checks on fractional grades.
        if ($data['single']) {
            if ($maxfraction != 1) {
                $errors['fraction[0]'] = get_string('errfractionsnomax', 'qtype_turprove',
                        $maxfraction * 100);
            }
        } else {
            $totalfraction = round($totalfraction, 2);
            if ($totalfraction != 1) {
                $errors['fraction[0]'] = get_string('errfractionsaddwrong', 'qtype_turprove',
                        $totalfraction * 100);
            }
        }
		
		//var_dump($errors);
        return $errors;
    }
}
?>