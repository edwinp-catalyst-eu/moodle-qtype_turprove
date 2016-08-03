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

    protected function get_answerfeedbacksound(question_answer $ans, $contextid, $slot, $usageid) {

        $fs = get_file_storage();
        $files = $fs->get_area_files(1, 'question', 'feedbacksound', $ans->id);
        if ($file = end($files)) {
            $filename = $file->get_filename();
            if ($filename != '.') {
                return moodle_url::make_file_url('/pluginfile.php',
                        "/1/question/feedbacksound/$usageid/$slot/$ans->id/$filename");
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

    protected function get_questions_total($cmid) {
        global $DB;

        $sql = "SELECT COUNT(qs.slot)
                  FROM {course_modules} cm
                  JOIN {quiz_slots} qs ON qs.quizid = cm.instance
                 WHERE cm.id = ?";
        $params = array($cmid);

        return $DB->count_records_sql($sql, $params);
    }

    /**
     * Whether a choice should be considered right, wrong or partially right.
     * @param question_answer $ans representing one of the choices.
     * @return fload 1.0, 0.0 or something in between, respectively.
     */
    protected abstract function is_right(question_answer $ans);

	public function array_in_string($str, array $arr) {
		foreach($arr as $arr_value) { //start looping the array
			if (strpos($str,$arr_value) !== false) {
				return true;
			}//if $arr_value is found in $str return true
		}
		return false; //else return false
	}
	
	
    /**
     * 
     * @param question_attempt $qa
     * @param question_display_options $options
     * @return type
     */
	 
    public function formulation_and_controls(question_attempt $qa, question_display_options $options) {
        global $CFG, $DB, $USER;
		$selection = 1; // Normal layout (default)
		$tableName = 'quiz_choose_layout';
		$dbman = $DB->get_manager(); // loads ddl manager and xmldb classes
		$attemptid = $DB->get_field('quiz_attempts', 'id', array('uniqueid' => $qa->get_usage_id()));
		$pageid = (int) $qa->get_slot() - 1;

		if ($dbman->table_exists($tableName)) { 
			if ($DB->record_exists($tableName, array('userid' => $USER->id))) {
				$sel = $DB->get_record($tableName, array('userid' => $USER->id));
				$selection = $sel->layout;
			}
		}

		$questioninfo = new stdClass();
        $questioninfo->questionnumber = $qa->get_slot();
        $questioninfo->questionstotal = $this->get_questions_total($options->context->instanceid);
        $question = $qa->get_question();
        $questiontext = $question->format_questiontext($qa);
		
		$html  = html_writer::empty_tag('selection', array('id' => 'selection', 'data-layout' => $selection));
		if ($selection == 2 && !$options->feedback) {
	        $html .= html_writer::div(get_string('questionxofy', 'qtype_turprove', $questioninfo), 'fullscreen-quizinfo');
		}
        $html .= html_writer::start_div('', array('id' => 'turprove_wrapper'));
		if ($selection == 2 && !$options->feedback) {
			$html .= html_writer::start_div('', array('id' => 'turprove_leftcolumn', 'style' => 'display:none;'));
		} else if ($selection == 3 && !$options->feedback) { 
			$html .= html_writer::start_div('', array('id' => 'turprove_leftcolumnNoText'));
		} else {
			$html .= html_writer::start_div('', array('id' => 'turprove_leftcolumn'));
		}
       

        $html .= html_writer::div(get_string('questionxofy', 'qtype_turprove', $questioninfo), 'turprove_leftcolumn_quiz_info');
        $html .= html_writer::start_div('', array('id' => 'turprove_question'));
        $questionsoundurl = $this->get_questionsound($question->id,
                $question->contextid, $qa->get_slot(), $qa->get_usage_id());
        $audiosource = html_writer::tag('source', '',
                array('type' => 'audio/mpeg', 'src' => $questionsoundurl));
        $audiosource .= get_string('yourbrowserdoesnotsupporttheaudiotag', 'qtype_turprove');
        $html .= html_writer::tag('audio', $audiosource, array('id' => 'audiodiv'));
		

		if ($selection != 3 || $options->feedback === 1) {
			$turprovequestionaudiodiv = html_writer::div('', 'audioplay', array('data-src' => $questionsoundurl));
			$html .= html_writer::div($turprovequestionaudiodiv, 'turprove_leftblock');
			$turprovequestiontextspan = html_writer::span($questiontext);
			$html .= html_writer::div($turprovequestiontextspan, 'turprove_contentblock');
		} else if ($selection == 3) {
			$turprovequestionaudiodiv = html_writer::div('', 'audioplay hide', array('data-src' => $questionsoundurl));	
			$html .= html_writer::div($turprovequestionaudiodiv, 'turprove_leftblock');
		}
		
		
        $html .= html_writer::end_div(); // #turprove_question
        $html .= html_writer::start_div('clearfix', array('id' => 'turprove_yn'));
        $html .= html_writer::div('', 'turprove_leftblock');
        $html .= html_writer::div('', 'turprove_contentblock');
        $yesnotext = get_string('yes') . ' / ' . get_string('no');
        $turproveyntextspan = html_writer::span($yesnotext);
		
		if ($selection == 3 && !$options->feedback) { 
		    $html .= html_writer::div($turproveyntextspan, 'turprove_rightblockNoText');
		} else {
			$html .= html_writer::div($turproveyntextspan, 'turprove_rightblock');
		}
		
        $html .= html_writer::end_div(); // #turprove_yn
        $response = $question->get_response($qa);
        $useranswers = $this->get_turprove_answers($question->get_order($qa), $response);
        $ordinal = 1;
		
		
        foreach ($question->get_order($qa) as $value => $ansid) {
            $ans = $question->answers[$ansid];
            $correct = null;
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

            if ($options->feedback) {			
                // Whether or not this question has been completed, we are requesting feedback
                $responsesummary = $qa->get_response_summary();
                $responsearray = explode('; ', $responsesummary);
                $thisanswer = $ans->answer;
                $thisanswerisyes = $ans->tur_answer_truefalse;
                $thisanswerstringmatch = str_replace(' ', '', $thisanswer);
                $thisanswerstringmatch = str_replace("\t", '', $thisanswerstringmatch);
                $responsearraystringmatch = array();
                foreach ($responsearray as $stringmatch) {
                    $stringmatch = str_replace(' ', '', $stringmatch);
                    $stringmatch = str_replace("\t", '', $stringmatch);
                    $responsearraystringmatch[] = $stringmatch;
                }

                if ($this->array_in_string($thisanswerstringmatch, $responsearraystringmatch)) {
                    // The correct answer has been selected
                    $correct = true;
                    if ($thisanswerisyes) {
                        $turproveanswerinputfields[0]['checked'] = 'checked'; // Set the 'Yes' radio button to checked
                    } else {
                        $turproveanswerinputfields[1]['checked'] = 'checked'; // Set the 'No' radio button to checked
                    }
                } else {
                    // The incorrect answer has been selected
                    $correct = false;
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
			
			$answerdivclass = 'turprove_answer';
			if ($selection == 3 && !$options->feedback) {
				$answerdivclass = 'turprove_answerNoText';
			}
            
            if ($options->feedback) {
                if ($correct) {
                    $answerdivclass .= ' correct';
                } else {
                    $answerdivclass .= ' incorrect';
                }
            }
            $html .= html_writer::start_div($answerdivclass);
			
			// Show only for normal layout and in feedback mode
			if ($selection != 3 || $options->feedback) {
				$turproveansweraudiodiv = html_writer::div('', 'audioplay',array('data-src' => $this->get_answersound($ans, $question->contextid, $qa->get_slot(), $qa->get_usage_id())));
				$html .= html_writer::div($turproveansweraudiodiv, 'turprove_leftblock');
			} else if ($selection == 3 && !$options->feedback) {
				$turproveansweraudiodiv = html_writer::div('', 'audioplay noIcon',array('data-src' => $this->get_answersound($ans, $question->contextid, $qa->get_slot(), $qa->get_usage_id())));
				$html .= html_writer::div($turproveansweraudiodiv, 'turprove_leftblock');
			}
				
            $ordinalspan = html_writer::span($ordinal . '. ');
            $ordinal++;
			
			if ($selection == 3 && !$options->feedback) {
				$turproveanswertextlabel = html_writer::label($question->make_html_inline($ordinalspan), $this->get_input_id($qa, $value));
			} else {
				$turproveanswertextlabel = html_writer::label($question->make_html_inline($ordinalspan.$question->format_text($ans->answer,$ans->answerformat, $qa, 'question', 'answer', $ansid)), $this->get_input_id($qa, $value));
			}

			if ($selection == 3 && !$options->feedback)
			{
				$html .= html_writer::start_div('turprove_answer_wrapperNoText clearfix');
			} else {
				$html .= html_writer::start_div('turprove_answer_wrapper clearfix');
			}
   
			if ($selection == 3 && !$options->feedback) {
				$html .= html_writer::div($turproveanswertextlabel, 'turprove_contentblockNoText');
			} else {
				$html .= html_writer::div($turproveanswertextlabel, 'turprove_contentblock');
			}
			
            $turproveanswerfields = '';
            foreach ($turproveanswerinputfields as $turproveanswerinputfield) {
                $turproveanswerfields .= html_writer::empty_tag('input', $turproveanswerinputfield);
            }
            if ($options->feedback) {
                $turproveanswerfields .= html_writer::div($this->feedback_image($correct), 'turprove_feedbackimage');
            }
			
			if ($selection == 3 && !$options->feedback){
				$html .= html_writer::div($turproveanswerfields, 'turprove_rightblockNoText');
			} else {
				$html .= html_writer::div($turproveanswerfields, 'turprove_rightblock');
			}
            $html .= html_writer::end_div(); // .turprove_answer_wrapper
            if ($options->feedback && trim($ans->feedback)) {
                $html .= html_writer::start_div('turprove_answer_feedback'); // start div.turprove_answer_feedback
                $tpanswerfeedbackaudio = '';
                $html .= html_writer::div($tpanswerfeedbackaudio, 'tp_feedbackaudio audioplay',
                    array('data-src' => $this->get_answerfeedbacksound($ans,
                            $question->contextid, $qa->get_slot(), $qa->get_usage_id())));
                $tpanswerfeedbacktext = trim($ans->feedback);
                $html .= html_writer::div($tpanswerfeedbacktext, 'turprove_feedbacktext');

                $html .= html_writer::end_div(); // end div.turprove_answer_feedback
            }
            $html .= html_writer::end_div(); // .turprove_answer
        }
        $html .= html_writer::end_div(); // #turprove_leftcolumn

        $turprovequestionimagesrc = $this->get_questionimage($question->id, $question->contextid, $qa->get_slot(), $qa->get_usage_id());
        $turprovequestionimage = html_writer::img($turprovequestionimagesrc, $questiontext, array('class' => 'questionimage'));
        $turprovequestionimagelink = html_writer::link($turprovequestionimagesrc, $turprovequestionimage, array('data-lightbox' => 'imagelink', 'data-title' => $questiontext));
        $turprovequestionimagediv = html_writer::div($turprovequestionimagelink);
		
		if ($selection == 2 && !$options->feedback) {
			$lightboxicon = '';
		} else {
			$lightboxicon = html_writer::img($CFG->wwwroot . '/question/type/turprove/pix/lightboxicon.jpg', $questiontext);
		}
        $lightboxlink = html_writer::link($turprovequestionimagesrc, $lightboxicon, array('data-lightbox' => 'iconlink', 'data-title' => $questiontext));
        $lighboxdiv = html_writer::div($lightboxlink, 'qtype_turprove_lightboxdiv');
		
		if ($selection == 2 && !$options->feedback) {
			$html .= html_writer::div($turprovequestionimagediv . $lighboxdiv, '', array('id' => 'turprove_rightcolumn', 'class' => 'fullscreen-rightcolumn'));
		} else if ($selection == 3 && !$options->feedback) {
			$html .= html_writer::div($turprovequestionimagediv . $lighboxdiv, '', array('id' => 'turprove_rightcolumn', 'class' => 'turprove_rightcolumnNoText'));
		} else {
			$html .= html_writer::div($turprovequestionimagediv . $lighboxdiv, '', array('id' => 'turprove_rightcolumn'));
		}
		
        $html .= html_writer::end_div(); // #turprove_wrapper

        // Menu button
        if ($options->readonly) {
            $modulecontext = $options->context;
            $coursecontext = $modulecontext->get_course_context();
            $menuurl = $coursecontext->get_url();
        } else {
            $menuurl = new moodle_url($CFG->wwwroot . '/mod/quiz/summary.php', array('attempt' => $attemptid));
        }
		
		if ($selection == 2 && !$options->feedback) {
			$html .= html_writer::start_div('', array('class' => 'fullsize'));
			$link = html_writer::link($menuurl, get_string('menu', 'qtype_turprove'), array('id' => 'tf_menubutton', 'class' => 'tf_button singlebutton fullscreen-quizinfo'));
			$html .= html_writer::div($link, '');
	        $html .= html_writer::end_div();
		} else {
			$link = html_writer::link($menuurl, get_string('menu', 'qtype_turprove'), array('id' => 'tf_menubutton', 'class' => 'tf_button'));
		
			$html .= html_writer::div($link, 'singlebutton turforlag');
		}
		if ($selection == 3) {
			$html .= html_writer::start_div('tf_prevnextquestion', array('class' => 'tf_prevnextquestionNoText'));	
		} else {
			$html .= html_writer::start_div('tf_prevnextquestion');
		}
			
        // Previous button
        if ($pageid) {
            if ($options->readonly) {
                $previousurl = new moodle_url($CFG->wwwroot . '/mod/quiz/review.php', array('attempt' => $attemptid, 'page' => $pageid - 1));
                $link = html_writer::link($previousurl, get_string('back', 'qtype_turprove'),array('id' => 'tf_previousbutton', 'class' => 'tf_button submit'));
                $html .= html_writer::div($link, 'singlebutton');
            } else {
                $previousurl = new moodle_url($CFG->wwwroot . '/mod/quiz/attempt.php', array('attempt' => $attemptid, 'page' => $pageid - 1));
				
				if ($selection == 2  || $selection == 3 && !$options->feedback) {				
					$link = html_writer::link($previousurl, get_string('back', 'qtype_turprove'), array('id' => 'tf_previousbutton',  'class' => 'tf_button submit hide'));
				} else {
					$link = html_writer::link($previousurl, get_string('back', 'qtype_turprove'), array('id' => 'tf_previousbutton',  'class' => 'tf_button submit'));
				}
								
                $html .= html_writer::div($link, 'singlebutton');
            }
        }

        // Next button
        if ($options->readonly) {
            if ($pageid + 1 != $this->get_questions_total($options->context->instanceid)) {
                $nexturl = new moodle_url($CFG->wwwroot . '/mod/quiz/review.php',
                        array('attempt' => $attemptid, 'page' => $pageid + 1));
						
						if ($selection == 2 && !$options->feedback) {	
							
							$link = html_writer::link($nexturl, get_string('forward', 'qtype_turprove'), array('id' => 'tf_nextbutton', 'class' => 'tf_button hide'));
						} else {
							$link = html_writer::link($nexturl, get_string('forward', 'qtype_turprove'), array('id' => 'tf_nextbutton', 'class' => 'tf_button submit'));
							
						}
                $html .= html_writer::div($link, 'singlebutton');
            }
        } else {			
			if ($selection == 2 || $selection == 3 && !$options->feedback) {
				$html .=  html_writer::empty_tag('input', array('type' => 'submit', 'id' => 'btnNext', 'class' => 'hide', 'name' => 'next'));
			} else {
				$html .=  html_writer::empty_tag('input', array('type' => 'submit', 'id' => 'btnNext', 'value' => get_string('forward', 'qtype_turprove'), 'name' => 'next'));
			}
        }

        $html .= html_writer::end_div(); // tf_prevnextquestion		
		
        if ($qa->get_state() == question_state::$invalid) {
            $html .= html_writer::div($question->get_validation_error($qa->get_last_qt_data()), 'validationerror');
        }
		
		$endMessageHead = '';
		$endMessageText = '';
		$imageUrl = '';
		
		if ($options->feedback && $pageid == 0) {
			
			$config = get_config('qtype_turprove');
			if ($config->show) {
				
				$attempt = $DB->get_record('quiz_attempts', array('id' => $attemptid));
				if ($attempt->userid != $USER->id){
					return;
				}
				
				$attemptResult = ($attempt->sumgrades / $questioninfo->questionstotal) * 100;
				
				if ($attemptResult >= 80) {
					$endMessageHead = get_string("passedHead", "qtype_turprove");
					$endMessageText = get_string("passedText", "qtype_turprove", $attemptResult);
					$imageUrl = $CFG->wwwroot . '/question/type/turprove/images/passed.png';
					$html .= '<div hidden id="isDone"></div>';	
				} else if ($attemptResult > 0 && $attemptResult < 80) {
					$endMessageHead = get_string("failedHead", "qtype_turprove");
					$endMessageText = get_string("failedText", "qtype_turprove", $attemptResult);
					$imageUrl = $CFG->wwwroot . '/question/type/turprove/images/failed.png';
					$html .= '<div hidden id="isDone"></div>';	
				}
			}
		}

        $this->page->requires->js_init_call(
            'M.qtype_turprove.init',
            array(
                '#q' . $qa->get_slot(),
                $options->readonly,
                $question->autoplay,
				$endMessageHead,
				$endMessageText,
				$imageUrl
            ),
            false,
            array(
                'name'     => 'qtype_turprove',
                'fullpath' => '/question/type/turprove/js/module.js',
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

    public function head_code(question_attempt $qa) {
        global $CFG;

        $js = new moodle_url($CFG->wwwroot . '/question/type/turprove/lightbox/lightbox-plus-jquery.min.js');
		$jsAlert = new moodle_url($CFG->wwwroot . '/question/type/turprove/js/sweetalert.min.js');
		
        $this->page->requires->js($js);
		$this->page->requires->js($jsAlert);
		
        $stylesheet = new moodle_url($CFG->wwwroot . '/question/type/turprove/lightbox/lightbox.css');
		$stylesheetMain = new moodle_url($CFG->wwwroot . '/question/type/turprove/css/styles.css');
		$stylesheetAlert = new moodle_url($CFG->wwwroot . '/question/type/turprove/css/sweetalert.css');
		
        $this->page->requires->css($stylesheet);
		$this->page->requires->css($stylesheetMain);
		$this->page->requires->css($stylesheetAlert);
    }

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

        if ($ans->fraction > 0) {
            return 1;
        } else {
            return 0;
        }
    }

    protected function num_parts_correct(question_attempt $qa) {

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
