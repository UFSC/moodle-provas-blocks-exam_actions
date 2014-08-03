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
        global $SESSION, $PAGE;

        if($this->content !== NULL) {
            return $this->content;
        }

        $this->content = new stdClass();
        $this->content->text = '';
        $this->content->footer = '';

        if(isset($SESSION->exam_access_key)) {
            $this->content->text = html_writer::tag('B', get_string('computer_released', 'block_exam_actions'), array('class'=>'computer_released'));
            return $this->content;
        }

        $links = array();
        if(is_a($PAGE->context, 'context_course')) {
            if($PAGE->context->instanceid == 1) {
                $links[] = html_writer::link(new moodle_url('/blocks/exam_actions/release_computer.php'), get_string('release_this_computer', 'block_exam_actions'));
            } else if(in_array('editor', $SESSION->exam_functions)) {
                $links[] = html_writer::link(new moodle_url('/blocks/exam_actions/export_exam.php', array('id'=>$PAGE->context->instanceid)),
                                             get_string('export_exam', 'block_exam_actions'));
            }
        } else if(is_a($PAGE->context, 'context_user')) {
            foreach($SESSION->exam_functions AS $f) {
                switch ($f) {
                    case 'editor':
                        $links[] = html_writer::link(new moodle_url('/blocks/exam_actions/remote_courses.php'), get_string('remote_courses', 'block_exam_actions'));
                        break;
                    case 'monitor':
                        $links[] = html_writer::link(new moodle_url('/blocks/exam_actions/monitor_exam.php'), get_string('monitor_exam', 'block_exam_actions'));
                        break;
                    case 'proctor':
                        $links[] = html_writer::link(new moodle_url('/blocks/exam_actions/generate_access_key.php'), get_string('generate_access_key', 'block_exam_actions'));
                        break;
                }
            }
        }

        if(!empty($links)) {
            $text = '';
            foreach($links AS $l) {
                $text .= html_writer::tag('LI', $l);
            }
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
