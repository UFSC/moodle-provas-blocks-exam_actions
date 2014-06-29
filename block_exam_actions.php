<?php

// Este bloco é parte do Moodle Provas - http://tutoriais.moodle.ufsc.br/provas/
//
// O Moodle Provas pode ser utilizado livremente por instituições integradas à
// UAB - Universidade Aberta do Brasil (http://www.uab.capes.gov.br/), assim como ser
// modificado para adequação à estrutura destas instituições
// sobre os termos da "GNU General Public License" como publicada pela
// "Free Software Foundation".

// copyright 2012 Antonio Carlos Mariani (http://moodle.ufsc.br)
// license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later

/**
 * Version details
 *
 * @package    block
 * @subpackage provas_access_key
 * @copyright  2012 - Antonio Carlos Mariani
 */

class block_exam_actions extends block_base {
    /**
     * block initializations
     */
    public function init() {
        global $CFG;
        $this->title  = get_string('title', 'block_exam_actions');
    }

    /**
     * block contents
     *
     * @return object
     */
    public function get_content() {
        global $USER, $CFG, $OUTPUT, $SESSION, $PAGE;

        if($this->content !== NULL) {
            return $this->content;
        }

        $this->content = new stdClass();
        $this->content->text = '';
        $this->content->footer = '';

        if(!isset($SESSION->exam_role)) {
            if(isset($SESSION->exam_access_key)) {
                $this->content->text = html_writer::tag('B', get_string('computer_released', 'block_exam_actions'), array('class'=>'computer_released'));
            } else {
                $link = html_writer::link(new moodle_url('/blocks/exam_actions/release_computer.php'), get_string('release_this_computer', 'block_exam_actions'));
                $text = html_writer::tag('LI', $link);
                $this->content->text = html_writer::tag('UL', $text);
            }
            return $this->content;
        }

        if($SESSION->exam_role == 'student') {
            return $this->content;
        }

        $text = '';
        if($SESSION->exam_role == 'teacher') {
            if(is_a($PAGE->context, 'context_user')) {
                $link = html_writer::link(new moodle_url('/blocks/exam_actions/remote_courses.php'), get_string('remote_courses', 'block_exam_actions'));
                $text .= html_writer::tag('LI', $link);
            } else if(is_a($PAGE->context, 'context_course')) {
                $link = html_writer::link(new moodle_url('/blocks/exam_actions/export_exam.php'), get_string('export_exam', 'block_exam_actions'));
                $text .= html_writer::tag('LI', $link);
            }
        }
        if(is_a($PAGE->context, 'context_user')) {
            $link = html_writer::link(new moodle_url('/blocks/exam_actions/generate_access_key.php'), get_string('generate_access_key', 'block_exam_actions'));
            $text .= html_writer::tag('LI', $link);
        }

        if(!empty($text)) {
            $this->content->text = html_writer::tag('UL', $text);
        }

        return $this->content;
    }

    /**
     * allow the block to have a configuration page
     *
     * @return boolean
     */
    public function has_config() {
        return false;
    }

    /**
     * locations where block can be displayed
     *
     * @return array
     */
    public function applicable_formats() {
        return array('my'=>true, 'course-view' => true, 'site' => true);
    }
}
?>
