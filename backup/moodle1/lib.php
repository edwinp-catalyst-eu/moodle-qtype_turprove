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
 * @package    qtype
 * @subpackage turprove
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Multichoice question type conversion handler
 */
class moodle1_qtype_turprove_handler extends moodle1_qtype_handler {

    /**
     * @return array
     */
    public function get_question_subpaths() {
        return array(
            'ANSWERS/ANSWER',
            'TURPROVE',
        );
    }

    /**
     * Appends the turprove specific information to the question
     */
    public function process_question(array $data, array $raw) {

        // convert and write the answers first
        if (isset($data['answers'])) {
            $this->write_answers($data['answers'], $this->pluginname);
        }

        // convert and write the turprove
        if (!isset($data['turprove'])) {
            // This should never happen, but it can do if the 1.9 site contained
            // corrupt data/
            $data['turprove'] = array(array(
                'single'                         => 1,
                'shuffleanswers'                 => 1,
                'correctfeedback'                => '',
                'correctfeedbackformat'          => FORMAT_HTML,
                'partiallycorrectfeedback'       => '',
                'partiallycorrectfeedbackformat' => FORMAT_HTML,
                'incorrectfeedback'              => '',
                'incorrectfeedbackformat'        => FORMAT_HTML,
                'qdifficulty'                    => '0',
            ));
        }
        $this->write_turprove($data['turprove'], $data['oldquestiontextformat']);
    }

    /**
     * Converts the turprove info and writes it into the question.xml
     *
     * @param array $turproves the grouped structure
     * @param int $oldquestiontextformat - {@see moodle1_question_bank_handler::process_question()}
     */
    protected function write_turprove(array $turproves, $oldquestiontextformat) {
        global $CFG;

        // the grouped array is supposed to have just one element - let us use foreach anyway
        // just to be sure we do not loose anything
        foreach ($turproves as $turprove) {
            // append an artificial 'id' attribute (is not included in moodle.xml)
            $turprove['id'] = $this->converter->get_nextid();

            // replay the upgrade step 2009021801
            $turprove['correctfeedbackformat']               = 0;
            $turprove['partiallycorrectfeedbackformat']      = 0;
            $turprove['incorrectfeedbackformat']             = 0;

            if ($CFG->texteditors !== 'textarea' and $oldquestiontextformat == FORMAT_MOODLE) {
                $turprove['correctfeedback']                 = text_to_html($turprove['correctfeedback'], false, false, true);
                $turprove['correctfeedbackformat']           = FORMAT_HTML;
                $turprove['partiallycorrectfeedback']        = text_to_html($turprove['partiallycorrectfeedback'], false, false, true);
                $turprove['partiallycorrectfeedbackformat']  = FORMAT_HTML;
                $turprove['incorrectfeedback']               = text_to_html($turprove['incorrectfeedback'], false, false, true);
                $turprove['incorrectfeedbackformat']         = FORMAT_HTML;
            } else {
                $turprove['correctfeedbackformat']           = $oldquestiontextformat;
                $turprove['partiallycorrectfeedbackformat']  = $oldquestiontextformat;
                $turprove['incorrectfeedbackformat']         = $oldquestiontextformat;
            }

            $this->write_xml('turprove', $turprove, array('/turprove/id'));
        }
    }
}
