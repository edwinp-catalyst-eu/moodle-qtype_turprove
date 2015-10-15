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
 * Defines the editing form for the multiple choice question type.
 *
 * @package    qtype
 * @subpackage turprove
 */

defined('MOODLE_INTERNAL') || die();

class qtype_turprove_edit_form extends question_edit_form {

    /**
     *
     * @param type $mform
     */
    protected function definition_inner($mform) {

        // Remove 'Default mark'
        $mform->removeElement('defaultmark');

        // 'Autoplay' checkbox
        $mform->addElement('advcheckbox', 'autoplay',
                get_string('autoplay', 'qtype_turprove'), null, null, array(0, 1));
        $mform->setDefault('autoplay', 1); // TODO: Use/set constant

        // 'One or multiple answers?' select menu
        $menu = array(
            get_string('answersingleno', 'qtype_turprove'),
            get_string('answersingleyes', 'qtype_turprove'),
        );

        $mform->addElement('hidden', 'single', 0);
        $mform->setType('single', PARAM_INT);

        // 'Image to display' filemanager
        $mform->addElement('filemanager', 'questionimage', 'Image to display', null,
            array('maxfiles' => 1)); // TODO: Use lang string

        // 'Choose soundfile for question' filemanager
        $mform->addElement('filemanager', 'questionsound', 'Choose soundfile for question', null,
            array('maxfiles' => 1, 'accepted_types' => array('mp3'))); // TODO: Use lang string

        // 'Difficulty' select menu
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

        $mform->addElement('hidden', 'shuffleanswers', 1);
        $mform->setType('shuffleanswers', PARAM_INT);

        $this->add_per_answer_fields(
            $mform,
            get_string('choiceno', 'qtype_turprove', '{no}'),
            '0.25', //question_bank::fraction_options_full(),
            max(4, QUESTION_NUMANS_START),
            4
        );

        $this->add_combined_feedback_fields(true);
    }

    /**
     *
     * @param type $numAnswers
     * @return real
     */
    function tur_setcustomfraction($numAnswers) {

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

    /**
     * Add a set of form fields, obtained from get_per_answer_fields, to the form,
     * one for each existing answer, with some blanks for some new ones.
     *
     * @param object $mform the form being built.
     * @param $label the label to use for each option.
     * @param $gradeoptions the possible grades for each answer.
     * @param $minoptions the minimum number of answer blanks to display.
     *      Default QUESTION_NUMANS_START.
     * @param $addoptions the number of answer blanks to add. Default QUESTION_NUMANS_ADD.
     */
    protected function add_per_answer_fields(&$mform, $label, $gradeoptions,
            $minoptions = QUESTION_NUMANS_START, $addoptions = QUESTION_NUMANS_ADD) {

        $answersoption = '';
        $repeatedoptions = array();
        $repeated = $this->get_per_answer_fields($mform, $label, $gradeoptions,
                $repeatedoptions, $answersoption);

        if (isset($this->question->options)) {
            $repeatsatstart = count($this->question->options->$answersoption);
        } else {
            $repeatsatstart = $minoptions;
        }

        $this->repeat_elements($repeated, $repeatsatstart, $repeatedoptions,
                'noanswers', 'addanswers', $addoptions,
                $this->get_more_choices_string(), true);
    }

    /**
     *
     * @param type $mform
     * @param type $label
     * @param type $gradeoptions
     * @param type $repeatedoptions
     * @param string $answersoption
     * @return type
     */
    protected function get_per_answer_fields($mform, $label, $gradeoptions,
            &$repeatedoptions, &$answersoption) {

        $filemanageroptions = $this->editoroptions;
        $filemanageroptions['maxfiles'] = 1;
        $filemanageroptions['accepted_types'] = array('mp3');
        $filemanageroptions['return_types'] = FILE_INTERNAL | FILE_EXTERNAL;

        $repeated = array();
        $repeated[] = $mform->createElement('header', 'choicehdr', $label);

        $repeated[] = $mform->createElement('editor', 'answer', $label,
            array('rows' => 1), $this->editoroptions);

        $truefalseoptions = array(
            0 => get_string('no'),
            1 => get_string('yes')
        );

        $repeated[] = $mform->createElement('select', 'tur_answer_truefalse',
                get_string('correctanswer', 'qtype_turprove'), $truefalseoptions);

        $repeated[] = $mform->createElement('hidden', 'fraction', '0.25');

        $repeated[] = $mform->createElement('editor', 'feedback',
                get_string('feedback', 'question'), array('rows' => 1), $this->editoroptions);

        $repeated[] = $mform->createElement('filemanager', 'answersound',
            'Choose soundfile for answer', null, $filemanageroptions); // TODO: use lang string

        $repeated[] = $mform->createElement('filemanager', 'feedbacksound',
            'Choose soundfile for answerfeedback', null, $filemanageroptions); // TODO: use lang string

        $repeatedoptions['answer']['type'] = PARAM_RAW;
        $repeatedoptions['fraction']['type'] = PARAM_RAW;
        //$repeatedoptions['tur_answer_truefalse']['default'] = 0;

        $answersoption = 'answers';

        return $repeated;
    }

    /**
     * Perform preprocessing needed on the data passed to {@link set_data()}
     * before it is used to initialise the form.
     *
     * @param object $question the data being passed to the form.
     * @return object $question the modified data.
     */
    protected function data_preprocessing($question) {
        $question = parent::data_preprocessing($question);
        $question = $this->data_preprocessing_answers($question, true);
        $question = $this->data_preprocessing_combined_feedback($question, true);
        $question = $this->data_preprocessing_hints($question, true, true);

        if (!empty($question->options)) {
            $question->single = $question->options->single;
            $question->shuffleanswers = $question->options->shuffleanswers;
            $question->qdifficulty = $question->options->qdifficulty;
        }

        if (isset($question->id)) {
            // Prepare the questionimage filemanager to display files in draft area.
            $draftitemid = file_get_submitted_draft_itemid('questionimage');
            file_prepare_draft_area($draftitemid, 1,
                    'question', 'questionimage', $question->id);
            $question->questionimage = $draftitemid;

            // Prepare the questionsound filemanager to display files in draft area.
            $draftitemid = file_get_submitted_draft_itemid('questionsound');
            file_prepare_draft_area($draftitemid, 1,
                    'question', 'questionsound', $question->id);
            $question->questionsound = $draftitemid;
        }

        return $question;
    }

    /**
     * Perform the necessary preprocessing for the fields added by
     * {@link add_per_answer_fields()}.
     *
     * @param object $question the data being passed to the form.
     * @return object $question the modified data.
     */
    protected function data_preprocessing_answers($question, $withanswerfiles = false) {

        parent::data_preprocessing_answers($question, $withanswerfiles);

        // Prepare filemanagers to display files in draft area.
        if (isset($question->options->answers) && $question->options->answers) {
            $key = 0;
            foreach ($question->options->answers as $answer) {

                // Prepare the answersound filemanager to display files in draft area.
                $draftitemid = file_get_submitted_draft_itemid('answersound['.$key.']');
                file_prepare_draft_area($draftitemid, 1,
                        'question', 'answersound', $answer->id);
                $question->answersound[$key] = $draftitemid;

                // Prepare the feedbacksound filemanager to display files in draft area.
                $draftitemid = file_get_submitted_draft_itemid('feedbacksound['.$key.']');
                file_prepare_draft_area($draftitemid, 1,
                        'question', 'feedbacksound', $answer->id);
                $question->feedbacksound[$key] = $draftitemid;

                $key++;
            }
        }

        return $question;
    }

    public function set_data($question) {

        if (isset($question->options)) {
            $answers = $question->options->answers;
            if (count($answers)) {
                $key = 0;
                foreach ($answers as $answer) {
                    $default_values['fraction[' . $key . ']'] = $this->tur_setcustomfraction(count($answers));
                    $default_values['tur_answer_truefalse[' . $key . ']'] = $answer->tur_answer_truefalse;
                    $key++;
                }
            }
            $question = (object) ((array) $question + $default_values);
        }

        parent::set_data($question);
    }

    /**
     *
     * @param type $data
     * @param type $files
     * @return type
     */
    public function validation($data, $files) {

        $errors = parent::validation($data, $files);

        $answers = $data['answer'];
        $answercount = 0;

        $totalfraction = 0;
        $maxfraction = -1;

        /*
         * Review/rewrite following as necessary
         *
        foreach ($answers as $key => $answer) {

            // Check no of choices.
            $trimmedanswer = trim($answer['text']);
            $fraction = (float) $data['fraction'][$key];
            if ($trimmedanswer === '' && empty($fraction)) {
                continue;
            }

            if ($trimmedanswer === '') {
                $errors['fraction['.$key.']'] = get_string('errgradesetanswerblank', 'qtype_turprove');
            }

             $answercount++;

            // Check grades.
            if ($data['fraction'][$key] > 0) {
                $totalfraction += $data['fraction'][$key];
            }
            if ($data['fraction'][$key] > $maxfraction) {
                $maxfraction = $data['fraction'][$key];
            }
        }

        if ($answercount == 0) {
            $errors['answer[0]'] = get_string('notenoughanswers', 'qtype_turprove', 2);
            $errors['answer[1]'] = get_string('notenoughanswers', 'qtype_turprove', 2);
        } else if ($answercount == 1) {
            $errors['answer[1]'] = get_string('notenoughanswers', 'qtype_turprove', 2);
        }

        // Perform sanity checks on fractional grades.
        if ($data['single']) {
            if ($maxfraction != 1) {
                $errors['fraction[0]'] = get_string('errfractionsnomax', 'qtype_turprove', $maxfraction * 100);
            }
        } else {
            // Remove 100% grade validation for form as these are multiple graded
            $totalfraction = round($totalfraction, 2);
            if ($totalfraction != 1) {
                $errors['fraction[0]'] = get_string('errfractionsaddwrong', 'qtype_turprove', $totalfraction * 100);
            }
        }
         *
         */

        return $errors;
    }

    public function qtype() {
        return 'turprove';
    }

    /**
     * Language string to use for 'Add {no} more {whatever we call answers}'.
     */
    protected function get_more_choices_string() {
        return get_string('addmorechoiceblanks', 'qtype_turmultiplechoice');
    }

    function repeat_elements($elementobjs, $repeats, $options, $repeathiddenname,
            $addfieldsname, $addfieldsno=5, $addstring=null, $addbuttoninside=false){

        $addstring = str_ireplace('{no}', $addfieldsno, $addstring);
        $repeats = optional_param($repeathiddenname, $repeats, PARAM_INT);
        $addfields = optional_param($addfieldsname, '', PARAM_TEXT);
        if (!empty($addfields)){
            $repeats += $addfieldsno;
        }
        $mform =& $this->_form;
        $mform->registerNoSubmitButton($addfieldsname);
        $mform->addElement('hidden', $repeathiddenname, $repeats);
        $mform->setType($repeathiddenname, PARAM_INT);
        //value not to be overridden by submitted value
        $mform->setConstants(array($repeathiddenname=>$repeats));
        $namecloned = array();
        for ($i = 0; $i < $repeats; $i++) {
            foreach ($elementobjs as $elementobj){
                $elementclone = fullclone($elementobj);
                $this->repeat_elements_fix_clone($i, $elementclone, $namecloned);

                if ($elementclone instanceof HTML_QuickForm_group && !$elementclone->_appendName) {
                    foreach ($elementclone->getElements() as $el) {
                        $this->repeat_elements_fix_clone($i, $el, $namecloned);
                    }
                    $elementclone->setLabel(str_replace('{no}', $i + 1, $elementclone->getLabel()));
                }

                $mform->addElement($elementclone);
            }
        }
        for ($i=0; $i<$repeats; $i++) {
            foreach ($options as $elementname => $elementoptions){
                $pos=strpos($elementname, '[');
                if ($pos!==FALSE){
                    $realelementname = substr($elementname, 0, $pos)."[$i]";
                    $realelementname .= substr($elementname, $pos);
                }else {
                    $realelementname = $elementname."[$i]";
                }
                foreach ($elementoptions as  $option => $params){
                    switch ($option){
                        case 'default' :
                            $mform->setDefault($realelementname, $params);
                            break;
                        case 'type' :
                            //Type should be set only once
                            if (!isset($mform->_types[$elementname])) {
                                $mform->setType($elementname, $params);
                            }
                            break;
                    }
                }
            }
        }

        return $repeats;
    }
}
