<?php

require_once(dirname(__FILE__) . '/helpers.php');

class qtype_turprove extends question_type {

    var $soundcounter = 0;
    var $feedsoundcounter = 0;

    function has_html_answers() {
        return true;
    }

    var $already_done = false;

    function get_html_head_contributions(&$question, &$state) {
        global $CFG;
        if ($this->already_done) {
            return array();
        }
        $this->already_done = true;
        $plugindir = $this->plugin_dir();
        $baseurl = $this->plugin_baseurl();
        $stylesheets = array();

        if (file_exists($plugindir . '/styles.css')) {
            $stylesheets[] = 'styles.css';
        }
        if (file_exists($plugindir . '/styles.php')) {
            $stylesheets[] = 'styles.php';
        }

        if (file_exists($plugindir . '/script.js')) {
            require_js($baseurl . '/script.js');
        }
        if (file_exists($plugindir . '/script.php')) {
            require_js($baseurl . '/script.php');
        }
        
        // BEGIN: audio.js / http://kolber.github.com/audiojs/
        require_js($baseurl . '/audiojs/audio.min.js');
        require_js($baseurl . '/js/audiojs-local-120303.js');
        $stylesheets[] = 'css/audiojs-120303.css';
        // END: audio.js
        
        // BEGIN: shadowbox-js.com
        require_js($baseurl . '/shadowbox/shadowbox.js');
        $stylesheets[] = 'shadowbox/shadowbox.css';
        // END: shadowbox-js.com
        
        // BEGIN: custom javascripts
        require_js($baseurl . '/js/prove.js');
        // END: custom javascripts
        
        
        // add stylesheets reflecting the choosen quiz layout
        if (!$this->isreview()) {
            if ($this->user_setting_text_in_quiz() == 0) {
                // normal
                $stylesheets[] = 'css/hideElements.css';
                $stylesheets[] = 'css/prove-med-tekst-111206.css';
            } elseif ($this->user_setting_text_in_quiz() == 1) {
                // normal + no text
                $stylesheets[] = 'css/hideElements_text.css';
                $stylesheets[] = 'css/prove-uden-tekst-120301.css';
            } elseif ($this->user_setting_text_in_quiz() == 2) {
                // fullscreen
                $stylesheets[] = 'css/prove_fullscreen-111206.css';
                $stylesheets[] = 'css/hideElements_fullscreen.css';
            }
        } else {
            $stylesheets[] = 'css/prove-med-tekst-111206.css';
            $stylesheets[] = 'css/hideElements.css';
        }

        $contributions = array();
        foreach ($stylesheets as $stylesheet) {
            $contributions[] = '<link rel="stylesheet" type="text/css" href="' . $baseurl . '/' . $stylesheet . '" />';
        }
        return $contributions;
    }

    /**
     * Returns an array with the stuats for the questions in the current quiz
     *
     * @global <type> $CFG
     * @param <type> $cmoptions
     * @return <type>
     */
    function create_quiz_results_overview($cmoptions) {
        global $CFG;
        $attempt = optional_param('attempt', 0, PARAM_INT);
        $questionsarr = explode(',', $cmoptions->questions);

        $i = 0;
        foreach ($questionsarr as $questionid) {
            if ($questionid > 0) {
                $resultsarr[$i][1] = 0;
                $sql = 'attempt=' . $attempt . ' AND event=6 AND question=' . $questionid;
                if ($questionstatus = get_record_select('question_states', $sql, 'raw_grade')) {
                    if ($questionstatus->raw_grade > 0.95) {
                        $resultsarr[$i][1] = 1;
                    }
                }
                $resultsarr[$i][2] = $CFG->wwwroot . '/mod/quiz/review.php?attempt=' . $attempt . '&page=' . $i;
                $i++;
            }
        }
        return $resultsarr;
    }

    function extra_question_fields() {
        return array('question_turprove', 'questionsound', 'layout', 'answers', 'single', 'shuffleanswers', 'autoplay', 'correctfeedback', 'partiallycorrectfeedback', 'incorrectfeedback', 'qdifficulty');
    }

    function extra_answer_fields() {
        return array('question_answers_turprove', 'answersound', 'feedbacksound', 'tur_answer_truefalse');
    }

    function save_session_and_responses(&$question, &$state) {
        // Bundle the answer order and the responses into the legacy answer
        // field.
        // The serialized format for multiple choice quetsions
        // is (optionally) a comma separated list of answer ids
        // followed by a colon, followed by another comma separated
        // list of answer ids, which are the radio/checkboxes that were
        // ticked.
        // E.g. 1,3,2,4:2,4 means that the answers were shown in the order
        // 1, 3, 2 and then 4 and the answers 2 and 4 were checked.

        $tempVal = $state->responses;
        $tempAnswers = array();
        $tempOrder = $state->options->order;
        $tempOrder = array_flip($tempOrder);

        /*
          foreach($tempOrder as $key=>$value) {
          if (!array_key_exists($key, $tempVal)) {
          $state->responses[$key] = '';
          }
          }
         */
        ksort($state->responses);

        //      t3lib_div::debug($state->options->order, '$state->options->order');
        //      t3lib_div::debug($state->responses, '$state->responses');
        $responses = implode(',', $state->options->order) . ':';
        $responses .= implode(',', $state->responses);
        //  t3lib_div::debug($responses, '$responses2');
        // Set the legacy answer field
        if (!set_field('question_states', 'answer', $responses, 'id', $state->id)) {
            return false;
        }
        return true;
    }

    function get_correct_responses(&$question, &$state) {
        //print('get_correct_responses' . '<br />');
        if ($question->options->single) {
            foreach ($question->options->answers as $answer) {
                if (((int) $answer->fraction) === 1) {
                    return array('' => $answer->id);
                }
            }
            return null;
        } else {
            $responses = array();
            foreach ($question->options->answers as $answer) {
                if (((float) $answer->fraction) > 0.0) {
                    $responses[$answer->id] = (string) $answer->id;
                }
            }
            return empty($responses) ? null : $responses;
        }
    }

    function prove_get_correct_responses(&$question, &$state) {
        // print('prove_get_correct_responses' . '<br />');
        if ($question->options->single) {
            foreach ($question->options->answers as $answer) {
                if (((int) $answer->fraction) === 1) {
                    return array('' => $answer->id);
                }
            }
            return null;
        } else {
            $responses = array();
            foreach ($question->options->answers as $answer) {
                $responses[$answer->id] = (string) $answer->id;
            }
            return empty($responses) ? null : $responses;
        }
    }

    /* MPL */

    function isreview() {
        $attempt = optional_param('attempt', 0, PARAM_INT);
        if ($attempt) {
            return true;
        } else {
            return false;
        }
    }

    function question_numbering() {
        global $COURSE;
        // Course Module ID, or
        $id = optional_param('id', 0, PARAM_INT);
        // quiz ID
        $q = optional_param('q', 0, PARAM_INT);
        // quiz ID
        $page = optional_param('page', 0, PARAM_INT);
        $attempt = optional_param('attempt', 0, PARAM_INT);
        $showall = optional_param('showall', 0);
        if ($id) {
            if (!$cm = get_coursemodule_from_id('quiz', $id)) {
                //  error("There is no coursemodule with id $id");
            }
            if (!$course = get_record("course", "id", $cm->course)) {
                //  error("Course is misconfigured");
            }
            if (!$quiz = get_record("quiz", "id", $cm->instance)) {
                //  error("The quiz with id $cm->instance corresponding to this coursemodule $id is missing");
            }
        } else {
            if ($q) {
                if (!$quiz = get_record("quiz", "id", $q)) {
                    //  error("There is no quiz with id $q");
                }
                if (!$course = get_record("course", "id", $quiz->course)) {
                    //  error("The course with id $quiz->course that the quiz with id $q belongs to is missing");
                }
                if (!$cm = get_coursemodule_from_instance("quiz", $quiz->id, $course->id)) {
                    //  error("The course module for the quiz with id $q is missing");
                }
            } else {
                if (!$quizID = get_record("quiz_attempts", "id", $attempt)) {
                    //error("There is no quiz with id $attempt");
                }
                if (!$quiz = get_record("quiz", "id", $quizID->quiz)) {
                    //  error("There is no quiz with id $q");
                }
                if (!$course = get_record("course", "id", $quiz->course)) {
                    //  error("The course with id $quiz->course that the quiz with id $q belongs to is missing");
                }
                if (!$cm = get_coursemodule_from_instance("quiz", $quiz->id, $course->id)) {
                    //  error("The course module for the quiz with id $q is missing");
                }
            }
        }
        /* if (!$showall) { */
        $Qs = array($quiz->questions);
        $QsP = $Qs[0];
        $QsT = t3lib_div::trimExplode(',', $QsP, 1);
        // remove zeroes
        $QsT = t3lib_div::removeArrayEntryByValue($QsT, '0');
        //  t3lib_div::debug(array_values($QsT));
        $noQ = count($QsT);
        $noP = $page + 1;

        return array_values($QsT);
    }

    function returnQnumber($qid) {
        $qnumbers = array();
        $qnumbers = $this->question_numbering();
        $key = array_search($qid, $qnumbers);
        return(isset($qid)) ? (array_search($qid, $qnumbers) + 1) . get_string('questionnumbering', 'qtype_turprove') . count($qnumbers) : false;
    }

    function return_question_image($question) {
        global $CFG;
        $img = '';
        $coursefilesdir = $CFG->turimage;
        if ($question->image) {
            if (substr(strtolower($question->image), 0, 7) == 'http://') {
                $img .= $question->image;
            } elseif ($CFG->turquizimageurl) {
                // override the file.php scheme, and load direct from the webserver
                $img .= "$CFG->turquizimageurl/$question->image";
            } elseif ($CFG->slasharguments) {
                // Use this method if possible for better caching
                $img .= "$CFG->wwwroot/file.php/$coursefilesdir/$question->image";
            } else {
                $img .= "$CFG->wwwroot/file.php?file=/$coursefilesdir/$question->image";
            }
        }
        return $img;
    }

    function stripValue($string) {
        $a = split("_", $string);
        return $a[1];
    }

    function stripAnswerid($string) {
        $a = split("_", $string);
        return $a[0];
    }

    // returns the display state for the quiz
    //   0=Display with text
    //   1=Display without text
    //   2=Display in fullscreen
    function user_setting_text_in_quiz() {
        global $USER, $COURSE;
        $selected_course = (($COURSE->id) > 1) ? ($COURSE->id) : -1;
        if ($selected_course && $selected_course != -1 && $USER->id) {
            $getcoursesetting = get_record('course_turmenu_settings', 'userid', $USER->id, 'courseid', $selected_course);
        }
        if (!empty($getcoursesetting)) {
            return $getcoursesetting->displaytype;
        } else {
            return 0;
        }
    }

    /* DKMD: New feedbackimage function. Ignore fraction, just check if answer is correct or not */

    function dkmd_question_get_feedback_image($answercorrect) {
        global $CFG;
        if ($answercorrect) {
            $feedbackimg = '<img src="' . $CFG->wwwroot . '/question/type/turprove/images/ok.gif" ' . 'alt="' . get_string('correct', 'quiz') . '" class="icon" />';
        } else {
            $feedbackimg = '<img src="' . $CFG->wwwroot . '/question/type/turprove/images/no.gif"' . 'alt="' . get_string('incorrect', 'quiz') . '" class="icon" />';
        }

        return $feedbackimg;
    }

    /**
     *
     * @global <type> $CFG
     * @param <type> $user_sound
     * @param <type> $user_question_category
     * @param <type> $autoplay
     * @param <type> $isfeedback
     * @param <type> $isvisible
     * @return <type> html code, that includes the sound on the page
     */
    function user_get_sound($user_sound, $user_question_category, $autoplay, $isfeedback, $isvisible) {
        global $CFG;

        // resolve path to the sound file
        $sound = '';
        if (!$category = get_record('question_categories', 'id', $user_question_category)) {
            error('invalid category id ' . $user_question_category);
        }
        if (isset($CFG->tursound)) {
            $coursefilesdir = $CFG->tursound;
        } else {
            $coursefilesdir = get_filesdir_from_context(get_context_instance_by_id($category->contextid));
        }
        if (substr(strtolower($user_sound), 0, 7) == 'http://') {
            $sound .= $user_sound;
        } elseif ($CFG->turquizaudiourl) {
            // override the file.php scheme, and load direct from the webserver
            $sound .= "$CFG->turquizaudiourl/$user_sound";
        } elseif ($CFG->slasharguments) {
            $sound .= "$CFG->wwwroot/file.php/$coursefilesdir/$user_sound";
        } else {
            $sound .= "$CFG->wwwroot/file.php?file=/$coursefilesdir/$user_sound";
        }

        // build the html code
        $html = '';
        $soundfilepath = $CFG->dataroot . '/' . $coursefilesdir . '/' . $user_sound;
        if (file_exists($soundfilepath) && $user_sound != '') {
            $html = '<div class="audioplay" data-src="'. $sound .'" />';
        }

        return $html;
    }

    // Calculate the grade for a response
    function grade_responses(&$question, &$state, $cmoptions) {
        $state->raw_grade = 0;
        if ($question->options->single) {
            $response = reset($state->responses);
            if ($response) {
                $state->raw_grade = $question->options->answers[$response]->fraction;
            }
        } else {
            $tmp = array_keys($question->options->answers);

            foreach ($tmp as $key => $value) {
                if (isset($state->responses[$value])) {
                    if ($state->responses[$value] == $value . '_2' && $question->options->answers[$value]->tur_answer_truefalse == '1') {
                        //  t3lib_div::debug($question->options->answers[$value]->fraction, 'HEST_1');
                        $state->raw_grade += $question->options->answers[$value]->fraction;
                    }
                    if ($state->responses[$value] == $value . '_3' && $question->options->answers[$value]->tur_answer_truefalse == '0') {
                        $state->raw_grade += $question->options->answers[$value]->fraction;
                    }
                } else {
                    // Brugeren har valgt at gå videre uden at angive et svar
                }
            }
        }

        $state->raw_grade = min(max((float) $state->raw_grade, 0.0), 1.0) * $question->maxgrade;
        // Question is only approved if all answers are correct!
        if ($state->raw_grade < (float) 0.95) {
            $state->raw_grade = 0;
        }

        $state->raw_grade = $state->raw_grade * $question->maxgrade;
        //  Apply the penalty for this attempt
        $state->penalty = $question->penalty * $question->maxgrade;
        // mark the state as graded
        $state->event = ($state->event == QUESTION_EVENTCLOSE) ? QUESTION_EVENTCLOSEANDGRADE : QUESTION_EVENTGRADE;

        return true;
    }

    function get_actual_response($question, $state) {
        $answers = $question->options->answers;
        $responses = array();

        return $responses;
    }

    function response_summary($question, $state, $length = 80) {
        //print('response_summary' . '<br />');
        return implode(',', $this->get_actual_response($question, $state));
    }

    public static function get_numbering_styles() {
        return array('abc', 'ABCD', '123', 'none');
    }

}

?>