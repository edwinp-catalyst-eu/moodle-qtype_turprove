<?php

// $Id: questiontype.php,v 1.2 2007/09/11 09:35:04 thepurpleblob Exp $
///////////////////
/// MULTIDISTRACY ///
///////////////////
/// QUESTION TYPE CLASS //////////////////
///
/// This class contains some special features in order to make the
/// question type embeddable within a multianswer (cloze) question
///

class question_turmultiplechoice_qtype extends default_questiontype {

    var $soundcounter = 0;
    var $feedsoundcounter = 0;

    function name() {
        return 'turmultiplechoice';
    }

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

        if (file_exists($plugindir . '/shadowbox/shadowbox.js')) {
            require_js($baseurl . '/shadowbox/shadowbox.js');
        }

// YUI depencies: http://developer.yahoo.com/yui/articles/hosting/?animation&dragdrop&element&layout&reset&MIN
        require_js(array($CFG->wwwroot . '/question/type/turmultiplechoice/js/yui-combo.js'));
        $stylesheets[] = 'css/yui-combo.css';
        $stylesheets[] = 'css/display.css';
        $stylesheets[] = 'shadowbox/shadowbox.css';
        $stylesheets[] = 'css/hideElements.css';

        // BEGIN: audio.sj / http://kolber.github.com/audiojs/
        require_js($baseurl . '/audiojs/audio.min.js');
        require_js($baseurl . '/js/audiojs-local-120303.js');
        $stylesheets[] = 'css/audiojs-120303.css';
        // END: audio.js
        
        $contributions = array();
        foreach ($stylesheets as $stylesheet) {
            $contributions[] = '<link rel="stylesheet" type="text/css" href="' . $baseurl . '/' . $stylesheet . '" />';
        }
        return $contributions;
    }

    function get_question_options(&$question) {
// t3lib_div::debug($question);
// Get additional information from database
// and attach it to the question object
        if (!$question->options = get_record('question_turmultiplechoice', 'question', $question->id)) {
            notify('Error: Missing question options for turmultiplechoice question' . $question->id . '!');
            return false;
        }
        if (!$question->options->answers = get_records_select('question_answers', 'id IN (' . $question->options->answers . ')', 'id')) {
            notify('Error: Missing question answers for turmultiplechoice question' . $question->id . '!');
            return false;
        }

        return true;
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

    function save_question_options($question) {
        $numAnswers = 0;
        if (isset($question)) {
            
        }
        $result = new stdClass;
        if (!$oldanswers = get_records("question_answers", "question", $question->id, "id ASC")) {
            $oldanswers = array();
        }
// following hack to check at least two answers exist
        $answercount = 0;
        foreach ($question->answer as $key => $dataanswer) {
            if ($dataanswer != "") {
                $answercount++;
            }
        }
        $answercount += count($oldanswers);
        if ($answercount < 2) {
// check there are at lest 2 answers for multiple choice
            $result->notice = get_string("notenoughanswers", "qtype_turmultiplechoice", "2");
            return $result;
        }

        foreach ($question->answer as $key => $answer) {
            $trimmedanswer = trim($answer);
            if (!empty($trimmedanswer)) {
                $numAnswers++;
            }
        }

// Insert all the new answers
        $totalfraction = 0;
        $maxfraction = -1;
        $answers = array();

        foreach ($question->answer as $key => $dataanswer) {
            if ($dataanswer != "") {
                if ($answer = array_shift($oldanswers)) {
// Existing answer, so reuse it
                    $answer->answer = $dataanswer;
// $answer->answersound = $question->{'answersound[' . $key . ']'};
//$answer->feedbacksound = $question->{'feedbacksound[' . $key . ']'};
// answersound
                    $answersound = $question->{'answersound[' . $key . ']'};
                    if ($answersound == "") {
                        $answersound = $question->answersound[$key];
                    }
                    $answer->answersound = $answersound;

//feedback sound
                    $feedbacksound = $question->{'feedbacksound[' . $key . ']'};
                    if ($feedbacksound == "") {
                        $feedbacksound = $question->feedbacksound[$key];
                    }
                    $answer->feedbacksound = $feedbacksound;

                    $answer->fraction = $question->fraction[$key];
                    $answer->feedback = $question->feedback[$key];
                    if (!update_record("question_answers", $answer)) {
                        $result->error = "Could not update quiz answer! (id=$answer->id)";
                        return $result;
                    }
                } else {
                    unset($answer);
                    $answer->answer = $dataanswer;
                    $answer->question = $question->id;
                    $answer->fraction = $question->fraction[$key];
                    $answer->feedback = $question->feedback[$key];

//$answer->feedbacksound = $question->feedbacksound[$key];
//$answer->feedbacksound = $question->{'feedbacksound[' . $key . ']'};
//$answer->answersound = $question->answersound[$key];
//$answer->answersound = $question->{'answersound[' . $key . ']'};
// answersound
                    $answersound = $question->{'answersound[' . $key . ']'};
                    if ($answersound == "") {
                        $answersound = $question->answersound[$key];
                    }
                    $answer->answersound = $answersound;

//feedback sound
                    $feedbacksound = $question->{'feedbacksound[' . $key . ']'};
                    if ($feedbacksound == "") {
                        $feedbacksound = $question->feedbacksound[$key];
                    }
                    $answer->feedbacksound = $feedbacksound;


                    if (!$answer->id = insert_record("question_answers", $answer)) {
                        $result->error = "Could not insert quiz answer! ";
                        return $result;
                    }
                }

                $answers[] = $answer->id;
                if ($question->fraction[$key] > 0) {
// Sanity checks
                    $totalfraction += $question->fraction[$key];
                }
                if ($question->fraction[$key] > $maxfraction) {
                    $maxfraction = $question->fraction[$key];
                }
            }
        }

        $update = true;
        $options = get_record("question_turmultiplechoice", "question", $question->id);
        if (!$options) {
            $update = false;
            $options = new stdClass;
            $options->question = $question->id;
        }
        $options->questionsound = $question->questionsound;
        $options->answers = implode(",", $answers);
        $options->single = $question->single;
        $options->autoplay = $question->autoplay;
        $options->qdifficulty = $question->qdifficulty;
        $options->answernumbering = $question->answernumbering;
        $options->shuffleanswers = $question->shuffleanswers;
        $options->correctfeedback = trim($question->correctfeedback);
        $options->partiallycorrectfeedback = trim($question->partiallycorrectfeedback);
        $options->incorrectfeedback = trim($question->incorrectfeedback);

        if ($update) {
            if (!update_record("question_turmultiplechoice", $options)) {
                $result->error = "Could not update quiz turmultiplechoice options! (id=$options->id)";
                return $result;
            }
        } else {
            if (!insert_record("question_turmultiplechoice", $options)) {
                $result->error = "Could not insert quiz turmultiplechoice options!";
                return $result;
            }
        }

// delete old answer records
        if (!empty($oldanswers)) {
            foreach ($oldanswers as $oa) {
                delete_records('question_answers', 'id', $oa->id);
            }
        }

        if ($options->single) {
            if ($maxfraction != 1) {
                $maxfraction = $maxfraction * 100;
                $result->noticeyesno = get_string("fractionsnomax", "qtype_turmultiplechoice", $maxfraction);
                return $result;
            }
        } else {
            $totalfraction = round($totalfraction, 2);
            if ($totalfraction != 1) {
                $totalfraction = $totalfraction * 100;
                $result->noticeyesno = get_string("fractionsaddwrong", "qtype_turmultiplechoice", $totalfraction);
                return $result;
            }
        }
        /* MPL ******************************** */
        return true;
    }

    /**
     * Deletes question from the question-type specific tables
     *
     * @return boolean Success/Failure
     * @param object $question  The question being deleted
     */
    function delete_question($questionid) {
        delete_records("question_turmultiplechoice", "question", $questionid);
        return true;
    }

    function create_session_and_responses(&$question, &$state, $cmoptions, $attempt) {
// create an array of answerids ??? why so complicated ???
        $answerids = array_values(array_map(create_function('$val', 'return $val->id;'), $question->options->answers));
// Shuffle the answers if required
        if ($cmoptions->shuffleanswers and $question->options->shuffleanswers) {
            $answerids = swapshuffle($answerids);
        }
        $state->options->order = $answerids;
// Create empty responses
        if ($question->options->single) {
            $state->responses = array('' => '');
        } else {
            $state->responses = array();
        }
        return true;
    }

    function extra_question_fields() {
        return array('question_turmultiplechoice', 'questionsound, autoplay, qdifficulty');
    }

    function restore_session_and_responses(&$question, &$state) {
// The serialized format for multiple choice quetsions
// is an optional comma separated list of answer ids (the order of the
// answers) followed by a colon, followed by another comma separated
// list of answer ids, which are the radio/checkboxes that were
// ticked.
// E.g. 1,3,2,4:2,4 means that the answers were shown in the order
// 1, 3, 2 and then 4 and the answers 2 and 4 were checked.

        $pos = strpos($state->responses[''], ':');
        if (false === $pos) {
// No order of answers is given, so use the default
            $state->options->order = array_keys($question->options->answers);
        } else {
// Restore the order of the answers
            $state->options->order = explode(',', substr($state->responses[''], 0, $pos));
            $state->responses[''] = substr($state->responses[''], $pos + 1);
        }
// Restore the responses
// This is done in different ways if only a single answer is allowed or
// if multiple answers are allowed. For single answers the answer id is
// saved in $state->responses[''], whereas for the multiple answers case
// the $state->responses array is indexed by the answer ids and the
// values are also the answer ids (i.e. key = value).
        if (empty($state->responses[''])) {
// No previous responses
            if ($question->options->single) {
                $state->responses = array('' => '');
            } else {
                $state->responses = array();
            }
        } else {
            if ($question->options->single) {
                $state->responses = array('' => $state->responses['']);
            } else {
// Get array of answer ids
                $state->responses = explode(',', $state->responses['']);
// Create an array indexed by these answer ids
                $state->responses = array_flip($state->responses);
// Set the value of each element to be equal to the index
                array_walk($state->responses, create_function('&$a, $b', '$a = $b;'));
            }
        }
        return true;
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
                $sql = 'attempt=' . $attempt . ' AND event=6 AND question=' . $questionid;
                $questionstatus = get_record_select('question_states', $sql, 'raw_grade');

                if ($questionstatus->raw_grade > 0.95) {
                    $resultsarr[$i][1] = 1;
                } else {
                    $resultsarr[$i][1] = 0;
                }
                $resultsarr[$i][2] = $CFG->wwwroot . '/mod/quiz/review.php?attempt=' . $attempt . '&page=' . $i;
                $i++;
            }
        }
        return $resultsarr;
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
        $responses = implode(',', $state->options->order) . ':';
        $responses .= implode(',', $state->responses);

// Set the legacy answer field
        if (!set_field('question_states', 'answer', $responses, 'id', $state->id)) {
            return false;
        }
        return true;
    }

    function get_correct_responses(&$question, &$state) {
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
        $Qs = array($quiz->questions);
        $QsP = $Qs[0];
        $QsT = t3lib_div::trimExplode(',', $QsP, 1);
        $QsT = t3lib_div::removeArrayEntryByValue($QsT, '0');
        $noQ = count($QsT);
        $noP = $page + 1;
        return array_values($QsT);
    }

    function returnQnumber(&$qid) {
        $qnumbers = array();
        $qnumbers = $this->question_numbering();
        $key = array_search($qid, $qnumbers);
//  t3lib_div::debug($key);
        return(isset($qid)) ? (array_search($qid, $qnumbers) + 1) . get_string('questionnumbering', 'qtype_turmultiplechoice') . count($qnumbers) : false;
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

    function print_question_formulation_and_controls(&$question, &$state, $cmoptions, $options) {
        global $CFG, $COURSE;

        $answers = &$question->options->answers;
        $correctanswers = $this->get_correct_responses($question, $state);
        $readonly = empty($options->readonly) ? '' : 'disabled="disabled"';
        $formatoptions = new stdClass;
        $formatoptions->noclean = true;
        $formatoptions->para = false;
// Print formulation
        $questiontext = format_text($question->questiontext, $question->questiontextformat, $formatoptions, $cmoptions->course);
        if (isset($CFG->turimage)) {
            $image = $this->return_question_image($question);
        } else {
            $image = get_question_image($question, $cmoptions->course);
        }

        /* If question contains sound, render flashobject with soundplayer */
        $questionspeak = '';
        if (!empty($question->options->questionsound)) {
            $questionspeak = $this->user_get_sound($question->options->questionsound, $question->category, $question->options->autoplay, 0, true);
        }

        $qnumbering = $this->returnQnumber($question->id);
        $zoom = $this->plugin_baseurl() . '/images/speaker.jpg';

        $thumb_w = '428';
        $thumb_h = '321';

        $answerprompt = ($question->options->single) ? get_string('singleanswer', 'quiz') : get_string('multipleanswers', 'quiz');

// Print each answer in a separate row
        $answercount = count($state->options->order);
        $count = 1;
        foreach ($state->options->order as $key => $aid) {
            $answer = &$answers[$aid];
            $checked = '';
            $chosen = false;

            if ($question->options->single) {
                $type = 'type="radio"';
//$type = ' type="checkbox" ';
                $name = "name=\"{$question->name_prefix}\"";
                if (isset($state->responses['']) and $aid == $state->responses['']) {
                    $checked = 'checked="checked"';
                    $chosen = true;
                }
            } else {
                $type = ' type="checkbox" ';
                $name = "name=\"{$question->name_prefix}{$aid}\"";
                if (isset($state->responses[$aid])) {
                    $checked = 'checked="checked"';
                    $chosen = true;
                }
            }
            $a = new stdClass;
            $a->id = $question->name_prefix . $aid;
            $a->class = '';
            $a->feedbackimg = '';
            $a->answersound = '';
            $a->feedbacksound = '';
// Print the control
            $a->control = "<input $readonly id=\"$a->id\" $name $checked $type value=\"$aid\" />";
            if ($options->correct_responses && $answer->fraction > 0) {
                $a->class = question_get_feedback_class(1);
            }

            $a->class = question_get_feedback_class($answer->fraction);

            if (($options->feedback && $chosen) || $options->correct_responses) {
                if ($type == ' type="checkbox" ') {
                    $a->feedbackimg = $this->question_get_feedback_image($answer->fraction > 0 ? 1 : 0, $chosen && $options->feedback);
                } else {
                    $a->feedbackimg = $this->question_get_feedback_image($answer->fraction, $chosen && $options->feedback);
                }
            }

// Print the answer text
            $a->text = format_text($answer->answer, FORMAT_MOODLE, $formatoptions, $cmoptions->course);
// Print feedback if feedback is on
            if (($options->feedback || $options->correct_responses) && ($checked || $options->readonly)) {
                $a->feedback = format_text($answer->feedback, FORMAT_MOODLE, $formatoptions, $cmoptions->course);
//question_get_feedback_class
            } else {
                $a->feedback = '';
            }

            if ((isset($answer->answersound) && ($answer->answersound))) {
                $a->answersound = $this->user_get_sound($answer->answersound, $question->category, 0, 0, true);
            }

            if (($options->feedback || $options->correct_responses) && ($checked || $options->readonly)) {
                if ((isset($answer->feedbacksound)) && ($answer->feedbacksound)) {
//t3lib_div::debug($answer->feedbacksound);
                    $a->feedbacksound = $this->user_get_sound($answer->feedbacksound, $question->category, 0, 1, true);
                } else {
                    $a->feedbacksound = '';
                }
            }

            $anss[] = clone($a);
        }


        $feedback = '';
        $lang_question = '';
        $isreview = false;
        if ($options->feedback) {
            $isreview = true;

//  The question has been submitted. Do not play sounds again!
            $questionspeak = '';
            if (!empty($question->options->questionsound)) {
//print("submitted");
                $questionspeak = $this->user_get_sound($question->options->questionsound, $question->category, 0, 0, true);
            }
            if ($state->raw_grade >= $question->maxgrade / 1.01) {
                $feedback = $question->options->correctfeedback;
            } elseif ($state->raw_grade > 0) {
                $feedback = $question->options->partiallycorrectfeedback;
            } else {
                $feedback = $question->options->incorrectfeedback;
            }
        }


// show a help text, on the status of the found answers
        $answerstatushelp = '';
        if (!$this->isreview()) {
            if ($state->grade > 0.95) {
                $answerstatushelp = '<span class="answerstatushelp greentext">' . get_string('status_allfound', 'qtype_turmultiplechoice') . '</span>';
            } elseif ($state->grade == 0) {
                $answerstatushelp = '<span class="answerstatushelp">' . get_string('status_nofound', 'qtype_turmultiplechoice') . '</span>';
            } else {
                $answerstatushelp = '<span class="answerstatushelp redtext">' . get_string('status_somefound', 'qtype_turmultiplechoice') . '</span>';
            }
        }

// create the results overview
        if ($this->isreview()) {
            $resultsoverview = $this->create_quiz_results_overview($cmoptions);
        } else {
            $resultsoverview = '';
        }

// output a javascript var that holds if the audio should autoplay
        if ($isreview) {
            echo '<script type="text/javascript">var soundAutoPlay = false; </script>';
            echo '<script type="text/javascript">var quizAutoProgress = false; </script>';
            echo '<script type="text/javascript">var quizShowAudioControls = true; </script>';
        } else {
            if ($question->options->autoplay) {
                echo '<script type="text/javascript">var soundAutoPlay = true; </script>';
                echo '<script type="text/javascript">var quizAutoProgress = false; </script>';
                echo '<script type="text/javascript">var quizShowAudioControls = true; </script>';
            } else {
                echo '<script type="text/javascript">var soundAutoPlay = false; </script>';
                echo '<script type="text/javascript">var quizAutoProgress = false; </script>';
                echo '<script type="text/javascript">var quizShowAudioControls = true; </script>';
            }
        }

// output weather the quiz is in adaptive mode
        if ($cmoptions->optionflags == 1) {
            echo '<script type="text/javascript">var quizIsAdaptive = true; </script>';
        } else {
            echo '<script type="text/javascript">var quizIsAdaptive = false; </script>';
        }

// output a help string if the quiz is in review mode
        if (!$isreview) {
            $helptext = get_string('helptext', 'qtype_turmultiplechoice');
        } else {
            $helptext = '';
        }
        include("$CFG->dirroot/question/type/turmultiplechoice/display.html");
    }

    function question_get_feedback_image($fraction, $selected = true) {
        global $CFG;
        if ($fraction >= 1.0) {
            if ($selected) {
                $feedbackimg = '<img src="' . $CFG->wwwroot . '/question/type/turmultiplechoice/images/ok.gif" ' . 'alt="' . get_string('correct', 'quiz') . '" class="icon" />';
            } else {
                $feedbackimg = '<img src="' . $CFG->wwwroot . '/question/type/turmultiplechoice/images/no.gif" ' . 'alt="' . get_string('correct', 'quiz') . '" class="icon" />';
            }
        } elseif ($fraction > 0.0 && $fraction < 1.0) {
            t3lib_div::debug($fraction, "fraction");
            if ($selected) {
                $feedbackimg = '<img src="' . $CFG->pixpath . '/i/tick_amber_big.gif" ' . 'alt="' . get_string('partiallycorrect', 'quiz') . '" class="icon" />';
            } else {
                $feedbackimg = '<img src="' . $CFG->pixpath . '/i/tick_amber_small.gif" ' . 'alt="' . get_string('partiallycorrect', 'quiz') . '" class="icon" />';
            }
        } else {
            if ($selected) {
                $feedbackimg = '<img src="' . $CFG->wwwroot . '/question/type/turmultiplechoice/images/no.gif"' . 'alt="' . get_string('incorrect', 'quiz') . '" class="icon" />';
            } else {
                $feedbackimg = '<img src="' . $CFG->wwwroot . '/question/type/turmultiplechoice/images/ok.gif" ' . 'alt="' . get_string('incorrect', 'quiz') . '" class="icon" />';
            }
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
     * @param <bool> $isvisible
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

        $soundlist[] = $sound;

        // build the html code
        $html = '';
        $soundfilepath = $CFG->dataroot . '/' . $coursefilesdir . '/' . $user_sound;
        if (file_exists($soundfilepath) && $user_sound != '') {
            $html = '<div class="audioplay" data-src="'. $sound .'" />';
        }

        return $html;
    }

    function grade_responses(&$question, &$state, $cmoptions) {
        $state->raw_grade = 0;
        if ($question->options->single) {
            $response = reset($state->responses);
            if ($response) {
                $state->raw_grade = $question->options->answers[$response]->fraction;
            }
        } else {
            foreach ($state->responses as $response) {
                if ($response) {
                    $state->raw_grade += $question->options->answers[$response]->fraction;
                }
            }
        }

// Make sure we don't assign negative or too high marks
        $state->raw_grade = min(max((float) $state->raw_grade, 0.0), 1.0) * $question->maxgrade;

// Apply the penalty for this attempt
        $state->penalty = $question->penalty * $question->maxgrade;

// mark the state as graded
        $state->event = ($state->event == QUESTION_EVENTCLOSE) ? QUESTION_EVENTCLOSEANDGRADE : QUESTION_EVENTGRADE;

        return true;
    }

// ULPGC ecastro
    function get_actual_response($question, $state) {
        $answers = $question->options->answers;
        $responses = array();
        if (!empty($state->responses)) {
            foreach ($state->responses as $aid => $rid) {
                if (!empty($answers[$rid])) {
                    $responses[] = $this->format_text($answers[$rid]->answer, $question->questiontextformat);
                }
            }
        } else {
            $responses[] = '';
        }
        return $responses;
    }

    function response_summary($question, $state, $length = 80) {
        return implode(',', $this->get_actual_response($question, $state));
    }

/// BACKUP FUNCTIONS ////////////////////////////

    /*
     * Backup the data in the question
     *
     * This is used in question/backuplib.php
     */


    function backup($bf, $preferences, $question, $level = 6) {
        $status = true;

        $multichoices = get_records("question_turmultiplechoice", "question", $question, "id");
//If there are multichoices
        if ($multichoices) {
//Iterate over each multichoice
            foreach ($multichoices as $multichoice) {
                $status = fwrite($bf, start_tag("turmultiplechoice", $level, true));
//Print multichoice contents
                fwrite($bf, full_tag("LAYOUT", $level + 1, false, $multichoice->layout));
                fwrite($bf, full_tag("ANSWERS", $level + 1, false, $multichoice->answers));
                fwrite($bf, full_tag("SINGLE", $level + 1, false, $multichoice->single));
                fwrite($bf, full_tag("SHUFFLEANSWERS", $level + 1, false, $multichoice->shuffleanswers));
                fwrite($bf, full_tag("CORRECTFEEDBACK", $level + 1, false, $multichoice->correctfeedback));
                fwrite($bf, full_tag("PARTIALLYCORRECTFEEDBACK", $level + 1, false, $multichoice->partiallycorrectfeedback));
                fwrite($bf, full_tag("INCORRECTFEEDBACK", $level + 1, false, $multichoice->incorrectfeedback));

                fwrite($bf, full_tag("QUESTIONSOUND", $level + 1, false, $multichoice->questionsound));
                fwrite($bf, full_tag("AUTOPLAY", $level + 1, false, $multichoice->autoplay));
                fwrite($bf, full_tag("QDIFFICULTY", $level + 1, false, $multichoice->qdifficulty));

                $status = fwrite($bf, end_tag("turmultiplechoice", $level, true));
            }

//Now print question_answers
//  $status = backup_answers();
//  $status = question_backup_answers_tur($bf,$preferences,$question);
            $answers = get_records("question_answers", "question", $question, "id");
//If there are answers
            if ($answers) {
                $status = $status && fwrite($bf, start_tag("ANSWERS", $level, true));
//Iterate over each answer
                foreach ($answers as $answer) {
                    $status = $status && fwrite($bf, start_tag("ANSWER", $level + 1, true));
//Print answer contents
                    fwrite($bf, full_tag("ID", $level + 2, false, $answer->id));
                    fwrite($bf, full_tag("ANSWER_TEXT", $level + 2, false, $answer->answer));
                    fwrite($bf, full_tag("FRACTION", $level + 2, false, $answer->fraction));
                    fwrite($bf, full_tag("FEEDBACK", $level + 2, false, $answer->feedback));

                    if ($answer->answersound == null) {
                        fwrite($bf, full_tag("ANSWERSOUND", $level + 2, false, ''));
                    } else {
                        fwrite($bf, full_tag("ANSWERSOUND", $level + 2, false, $answer->answersound));
                    }

                    if ($answer->feedbacksound == null) {
                        fwrite($bf, full_tag("FEEDBACKSOUND", $level + 2, false, ''));
                    } else {
                        fwrite($bf, full_tag("FEEDBACKSOUND", $level + 2, false, $answer->feedbacksound));
                    }

                    if ($answer->tur_answer_truefalse == null) {
                        fwrite($bf, full_tag("TUR_ANSWER_TRUEFALSE", $level + 2, false, ''));
                    } else {
                        fwrite($bf, full_tag("TUR_ANSWER_TRUEFALSE", $level + 2, false, $answer->tur_answer_truefalse));
                    }

                    $status = $status && fwrite($bf, end_tag("ANSWER", $level + 1, true));
                }
                $status = $status && fwrite($bf, end_tag("ANSWERS", $level, true));
            }
        }
        return $status;
    }

/// RESTORE FUNCTIONS /////////////////

    /*
     * Restores the data in the question
     *
     * This is used in question/restorelib.php
     */
    function restore($old_question_id, $new_question_id, $info, $restore) {
        $status = true;

// update the question _answers table with the additional information
        if ($new_question_id > 0) {
            $answers = $info['#']['ANSWERS']['0']['#']['ANSWER'];
            for ($i = 0; $i < sizeof($answers); $i++) {
                $ans_info = $answers[$i];

                $answer = new stdClass;
                $answer->question = $new_question_id;
                $answer->answer = backup_todb($ans_info['#']['ANSWER_TEXT']['0']['#']);
                $answer->fraction = backup_todb($ans_info['#']['FRACTION']['0']['#']);
                $answer->feedback = backup_todb($ans_info['#']['FEEDBACK']['0']['#']);
                $answer->answersound = backup_todb($ans_info['#']['ANSWERSOUND']['0']['#']);
                $answer->feedbacksound = backup_todb($ans_info['#']['FEEDBACKSOUND']['0']['#']);
                $answer->tur_answer_truefalse = backup_todb($ans_info['#']['TUR_ANSWER_TRUEFALSE']['0']['#']);

                $ansid = get_record('question_answers', 'question', $new_question_id, 'answer', $answer->answer, 'fraction', $answer->fraction, 'id');
                $answer->id = $ansid->id;

                $chckans = update_record(question_answers, $answer);
            }
        }


//Get the multichoices array
        $multichoices = $info['#']['TURMULTIPLECHOICE'];

//Iterate over multichoices
        for ($i = 0; $i < sizeof($multichoices); $i++) {
            $mul_info = $multichoices[$i];


//Now, build the question_multichoice record structure
            $multichoice = new stdClass;
            $multichoice->question = $new_question_id;
            $multichoice->layout = backup_todb($mul_info['#']['LAYOUT']['0']['#']);
            $multichoice->answers = backup_todb($mul_info['#']['ANSWERS']['0']['#']);
            $multichoice->single = backup_todb($mul_info['#']['SINGLE']['0']['#']);
            $multichoice->shuffleanswers = isset($mul_info['#']['SHUFFLEANSWERS']['0']['#']) ? backup_todb($mul_info['#']['SHUFFLEANSWERS']['0']['#']) : '';
            if (array_key_exists("CORRECTFEEDBACK", $mul_info['#'])) {
                $multichoice->correctfeedback = backup_todb($mul_info['#']['CORRECTFEEDBACK']['0']['#']);
            } else {
                $multichoice->correctfeedback = '';
            }
            if (array_key_exists("PARTIALLYCORRECTFEEDBACK", $mul_info['#'])) {
                $multichoice->partiallycorrectfeedback = backup_todb($mul_info['#']['PARTIALLYCORRECTFEEDBACK']['0']['#']);
            } else {
                $multichoice->partiallycorrectfeedback = '';
            }
            if (array_key_exists("INCORRECTFEEDBACK", $mul_info['#'])) {
                $multichoice->incorrectfeedback = backup_todb($mul_info['#']['INCORRECTFEEDBACK']['0']['#']);
            } else {
                $multichoice->incorrectfeedback = '';
            }

            if (array_key_exists("QUESTIONSOUND", $mul_info['#'])) {
                $multichoice->questionsound = backup_todb($mul_info['#']['QUESTIONSOUND']['0']['#']);
            } else {
                $multichoice->questionsound = '';
            }

            if (array_key_exists("AUTOPLAY", $mul_info['#'])) {
                $multichoice->autoplay = backup_todb($mul_info['#']['AUTOPLAY']['0']['#']);
            } else {
                $multichoice->autoplay = '';
            }

            if (array_key_exists("QDIFFICULTY", $mul_info['#'])) {
                $multichoice->qdifficulty = backup_todb($mul_info['#']['QDIFFICULTY']['0']['#']);
            } else {
                $multichoice->qdifficulty = '';
            }

//We have to recode the answers field (a list of answers id)
//Extracts answer id from sequence
            $answers_field = "";
            $in_first = true;
            $tok = strtok($multichoice->answers, ",");
            while ($tok) {
//Get the answer from backup_ids
                $answer = backup_getid($restore->backup_unique_code, "question_answers", $tok);
                if ($answer) {
                    if ($in_first) {
                        $answers_field .= $answer->new_id;
                        $in_first = false;
                    } else {
                        $answers_field .= "," . $answer->new_id;
                    }
                }
//check for next
                $tok = strtok(",");
            }
//We have the answers field recoded to its new ids
            $multichoice->answers = $answers_field;

//The structure is equal to the db, so insert the question_shortanswer
            $newid = insert_record("question_turmultiplechoice", $multichoice);

//Do some output
            if (($i + 1) % 50 == 0) {
                if (!defined('RESTORE_SILENTLY')) {
                    echo ".";
                    if (($i + 1) % 1000 == 0) {
                        echo "<br />";
                    }
                }
                backup_flush(300);
            }

            if (!$newid) {
                $status = false;
            }
        }

        return $status;
    }

    function restore_recode_answer($state, $restore) {
        $pos = strpos($state->answer, ':');
        $order = array();
        $responses = array();
        if (false === $pos) {
// No order of answers is given, so use the default
            if ($state->answer) {
                $responses = explode(',', $state->answer);
            }
        } else {
            $order = explode(',', substr($state->answer, 0, $pos));
            if ($responsestring = substr($state->answer, $pos + 1)) {
                $responses = explode(',', $responsestring);
            }
        }
        if ($order) {
            foreach ($order as $key => $oldansid) {
                $answer = backup_getid($restore->backup_unique_code, "question_answers", $oldansid);
                if ($answer) {
                    $order[$key] = $answer->new_id;
                } else {
                    echo 'Could not recode turmultiplechoice answer id ' . $oldansid . ' for state ' . $state->oldid . '<br />';
                }
            }
        }
        if ($responses) {
            foreach ($responses as $key => $oldansid) {
                $answer = backup_getid($restore->backup_unique_code, "question_answers", $oldansid);
                if ($answer) {
                    $responses[$key] = $answer->new_id;
                } else {
                    echo 'Could not recode turmultiplechoice response answer id ' . $oldansid . ' for state ' . $state->oldid . '<br />';
                }
            }
        }
        return implode(',', $order) . ':' . implode(',', $responses);
    }

    /**
     * Decode links in question type specific tables.
     * @return bool success or failure.
     */
    function decode_content_links_caller($questionids, $restore, &$i) {
        $status = true;

// Decode links in the question_turmultiplechoice table.
        if ($multichoices = get_records_list('question_turmultiplechoice', 'question', implode(',', $questionids), '', 'id, correctfeedback, partiallycorrectfeedback, incorrectfeedback')) {
            foreach ($multichoices as $multichoice) {
                $correctfeedback = restore_decode_content_links_worker($multichoice->correctfeedback, $restore);
                $partiallycorrectfeedback = restore_decode_content_links_worker($multichoice->partiallycorrectfeedback, $restore);
                $incorrectfeedback = restore_decode_content_links_worker($multichoice->incorrectfeedback, $restore);
                if ($correctfeedback != $multichoice->correctfeedback || $partiallycorrectfeedback != $multichoice->partiallycorrectfeedback || $incorrectfeedback != $multichoice->incorrectfeedback) {
                    $subquestion->correctfeedback = addslashes($correctfeedback);
                    $subquestion->partiallycorrectfeedback = addslashes($partiallycorrectfeedback);
                    $subquestion->incorrectfeedback = addslashes($incorrectfeedback);
                    if (!update_record('question_turmultiplechoice', $multichoice)) {
                        $status = false;
                    }
                }

// Do some output.
                if (++$i % 5 == 0 && !defined('RESTORE_SILENTLY')) {
                    echo ".";
                    if ($i % 100 == 0) {
                        echo "<br />";
                    }
                    backup_flush(300);
                }
            }
        }

        return $status;
    }

    function export_to_xml($question, $format, $extra = null) {
        $expout = '';
        $expout .= "    <questionsound>" . $question->options->questionsound . "</questionsound>\n";
        $expout .= "    <correctfeedback>" . $format->writetext($question->options->correctfeedback, 3) . "</correctfeedback>\n";
        $expout .= "    <partiallycorrectfeedback>" . $format->writetext($question->options->partiallycorrectfeedback, 3) . "</partiallycorrectfeedback>\n";
        $expout .= "    <incorrectfeedback>" . $format->writetext($question->options->incorrectfeedback, 3) . "</incorrectfeedback>\n";
        $expout .= "    <answernumbering>{$question->options->answernumbering}</answernumbering>\n";

// find t
        foreach ($question->options->answers as $answer) {
            $percent = $answer->fraction * 100;

// calculate the answer fraction
            if ($answer->fraction == 0) {
                $answer->tur_answer_truefalse = 0;
            } else {
                $answer->tur_answer_truefalse = 1;
            }

            $expout .= "      <answer fraction=\"$percent\">\n";
            $expout .= "      <answerstatus>" . $answer->tur_answer_truefalse . "</answerstatus>\n";
            $expout .= $format->writetext($answer->answer, 4, false);
            $expout .= "      <answersound>" . $answer->answersound . "</answersound>\n";
            $expout .= "      <feedback>\n";
            $expout .= $format->writetext($answer->feedback, 5, false);
            $expout .= "          <feedbacksound>" . $answer->feedbacksound . "</feedbacksound>\n";
            $expout .= "      </feedback>\n";
            $expout .= "    </answer>\n";
        }


        return $expout;
    }

    function import_from_xml($data, $question, $format, $extra = null) {
        $qtype = $data['@']['type'];
        if ($qtype != 'turmultiplechoice') {
            return false;
        }

// get common parts
        $qo = $format->import_headers($data);

// 'header' parts particular to multichoice
        $qo->qtype = 'turmultiplechoice';
        $qo->single = $format->getpath($data, array('#', 'single', 0, '#'), '', true);
        $qo->answernumbering = $format->getpath($data, array('#', 'answernumbering', 0, '#'), 'abc');
        $qo->correctfeedback = $format->getpath($data, array('#', 'correctfeedback', 0, '#', 'text', 0, '#'), '', true);
        $qo->partiallycorrectfeedback = $format->getpath($data, array('#', 'partiallycorrectfeedback', 0, '#', 'text', 0, '#'), '', true);
        $qo->incorrectfeedback = $format->getpath($data, array('#', 'incorrectfeedback', 0, '#', 'text', 0, '#'), '', true);

// Custom fields
        $autoplay = $format->getpath($data, array('#', 'autoplay', 0, '#'), '', true);
        $qo->autoplay = $autoplay;

        $qdifficulty = $format->getpath($data, array('#', 'qdifficulty', 0, '#'), '', true);
        $qo->qdifficulty = $qdifficulty;

        $questionsound = $format->getpath($data, array('#', 'questionsound', 0, '#'), '', true);
        $qo->questionsound = $questionsound;

// run through the answers
        $answers = $data['#']['answer'];
        $a_count = 0;
        $answerstatus = '';
        
        foreach ($answers as $answer) {
            $ans = $format->import_answer($answer);

            $feedbacksound = $format->getpath($answer, array('#', 'feedback', 0, '#', 'feedbacksound', 0, '#'), '', true);
            $answersound = $format->getpath($answer, array('#', 'answersound', 0, '#'), '', true);
            $answerstatus = $format->getpath($answer, array('#', 'answerstatus', 0, '#'), '', true);

            $qo->answer[$a_count] = $ans->answer;
            $qo->tur_answer_truefalse[$a_count] = $answerstatus;
            $qo->fraction[$a_count] = $ans->fraction;
            $qo->feedback[$a_count] = $ans->feedback;
            $qo->feedbacksound[$a_count] = $feedbacksound;
            $qo->answersound[$a_count] = $answersound;
            ++$a_count;
        }
        return $qo;
    }

    function get_numbering_styles() {
        return array('abc', 'ABCD', '123', 'none');
    }

}

//// END OF CLASS ////
//////////////////////////////////////////////////////////////////////////
//// INITIATION - Without this line the question type is not in use... ///
//////////////////////////////////////////////////////////////////////////
question_register_questiontype(new question_turmultiplechoice_qtype());
?>