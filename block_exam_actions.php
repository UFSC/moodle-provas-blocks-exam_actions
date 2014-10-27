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
//
// Este bloco é parte do Moodle Provas - http://tutoriais.moodle.ufsc.br/provas/
// Este projeto é financiado pela
// UAB - Universidade Aberta do Brasil (http://www.uab.capes.gov.br/)
// e é distribuído sob os termos da "GNU General Public License",
// como publicada pela "Free Software Foundation".

/**
 * This file contains the Exam Actions block.
 *
 * @package    block_exam_actions
 * @author     Antonio Carlos Mariani
 * @copyright  2010 onwards Universidade Federal de Santa Catarina (http://www.ufsc.br)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

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
        global $SESSION, $PAGE, $USER;

        if($this->content !== NULL) {
            return $this->content;
        }

        $this->content = new stdClass();
        $this->content->text = '';
        $this->content->footer = '';

        if(is_siteadmin($USER->id) || isguestuser($USER->id)) {
            return $this->content;
        }

        if(isset($SESSION->exam_access_key)) {
            $this->content->text = html_writer::tag('B', get_string('computer_released', 'block_exam_actions'), array('class'=>'computer_released'));
            return $this->content;
        }

        if(!isset($SESSION->exam_user_functions)) {
            $SESSION->exam_user_functions = array();
        }

        $links = array();
        if(is_a($PAGE->context, 'context_course')) {
            if($PAGE->course->id == 1) {
                $links[1] = html_writer::link(new moodle_url('/blocks/exam_actions/release_computer.php'), get_string('release_this_computer', 'block_exam_actions'));
            } else {
                if(empty($SESSION->exam_user_functions) || in_array('student', $SESSION->exam_user_functions)) {
                    return $this->content;
                }
                if (has_capability('moodle/backup:backupactivity', $PAGE->context)) {
                    $links[5] = html_writer::link(new moodle_url('/blocks/exam_actions/export_exam.php', array('courseid'=>$PAGE->context->instanceid)),
                                                 get_string('export_exam', 'block_exam_actions'));
                }
                if (has_capability('moodle/course:managegroups', $PAGE->context)) {
                    $links[4] = html_writer::link(new moodle_url('/blocks/exam_actions/sync_groups.php', array('courseid'=>$PAGE->context->instanceid)),
                                                 get_string('sync_groups', 'block_exam_actions'));
                }
                $conduct = false;
                if ((has_capability('block/exam_actions:conduct_exam', $PAGE->context) && $PAGE->course->visible)) {
                    $links[1] = html_writer::link(new moodle_url('/blocks/exam_actions/generate_access_key.php', array('courseid'=>$PAGE->context->instanceid)),
                                                 get_string('generate_access_key', 'block_exam_actions'));
                    $conduct = true;
                }
                if ((has_capability('block/exam_actions:monitor_exam', $PAGE->context) && $PAGE->course->visible)) {
                    $links[2] = html_writer::link(new moodle_url('/blocks/exam_actions/monitor_exam.php', array('courseid'=>$PAGE->context->instanceid)),
                                                 get_string('monitor_exam', 'block_exam_actions'));
                }
                if ($conduct || in_array('editor', $SESSION->exam_user_functions)) {
                    $links[3] = html_writer::link(new moodle_url('/blocks/exam_actions/load_students.php', array('courseid'=>$PAGE->context->instanceid)),
                                                 get_string('load_students', 'block_exam_actions'));
                }
            }
        } else if(is_a($PAGE->context, 'context_user')) {
            if(in_array('editor', $SESSION->exam_user_functions)) {
                $links[2] = html_writer::link(new moodle_url('/blocks/exam_actions/remote_courses.php'), get_string('new_course', 'block_exam_actions'));
            }
            if(in_array('proctor', $SESSION->exam_user_functions)) {
                $links[1] = html_writer::link(new moodle_url('/blocks/exam_actions/generate_access_key.php'), get_string('generate_access_key', 'block_exam_actions'));
            }
            if(!empty($SESSION->exam_user_functions) && ! in_array('student', $SESSION->exam_user_functions)) {
                $links[6] = html_writer::link(new moodle_url('/blocks/exam_actions/review_permissions.php'), get_string('review_permissions', 'block_exam_actions'));
            }
        }

        if(!empty($links)) {
            ksort($links);
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
