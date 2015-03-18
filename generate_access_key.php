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
 * This file contains the Generate Access Key page.
 *
 * @package    block_exam_actions
 * @author     Antonio Carlos Mariani
 * @copyright  2010 onwards Universidade Federal de Santa Catarina (http://www.ufsc.br)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../config.php');
require_once(dirname(__FILE__).'/locallib.php');
require_once(dirname(__FILE__).'/generate_access_key_form.php');

if ($courseid = optional_param('courseid', 0, PARAM_INT)) {
    $course = $DB->get_record('course', array('id'=>$courseid, 'visible'=>1), '*', MUST_EXIST);
    $context = context_course::instance($courseid);
    $courses = array($course->id=>$course->fullname);
    $baseurl = new moodle_url('/blocks/exam_actions/generate_access_key.php', array('courseid'=>$courseid));
    $PAGE->set_course($course);
} else {
    $context = context_user::instance($USER->id);
    $courses = exam_courses_menu('proctor', 'block/exam_actions:conduct_exam');
    $baseurl = new moodle_url('/blocks/exam_actions/generate_access_key.php');
}

if (!$origin = optional_param('origin', false, PARAM_TEXT)) {
    $origin = $PAGE->course->id == 1 ? 'my' : 'course';
}
if ($origin == 'course') {
    $returnurl = new moodle_url('/course/view.php', array('id'=>$courseid));
} else {
    $returnurl = new moodle_url('/my');
}

$PAGE->set_url($baseurl);
$PAGE->set_context($context);
$site = get_site();
$PAGE->set_heading($site->fullname);
$PAGE->set_pagelayout('course');
$PAGE->set_title(get_string('generating_access_key', 'block_exam_actions'));
$PAGE->navbar->add(get_string('generating_access_key', 'block_exam_actions'));

$editform = new generate_access_key_form(null, array('data'=>$courses, 'origin'=>$origin));

if ($editform->is_cancelled()) {
    redirect($returnurl);
} else if ($data = $editform->get_data()) {
    $course = $DB->get_record('course', array('id'=>$data->courseid, 'visible'=>1), '*', MUST_EXIST);
    $context = context_course::instance($data->courseid);
    if (!has_capability('block/exam_actions:conduct_exam', $context)) {
        print_error('no_permission', 'block_exam_actions');
    }

    $sql = "SELECT MAX(timecreated) AS last_time
              FROM {exam_access_keys} ak
             WHERE ak.courseid = :courseid";
    if (!$last_time = $DB->get_field_sql($sql, array('courseid'=>$data->courseid))) {
        $last_time = 0;
    }

    $access_key = exam_generate_access_key($data->courseid, $USER->id, $data->access_key_timeout, $data->verify_client_host);
    if ($local_shortname = $DB->get_field('course', 'shortname', array('id'=>$data->courseid))) {
        list($identifier, $shortname) = explode('_', $local_shortname, 2);

        if (abs(time() - $last_time) / 60 > 60)  { // more than 60 minutes from the last key generation
            exam_enrol_students($identifier, $shortname, $course);
        }

        // generate and show access key
        echo $OUTPUT->header();
        echo $OUTPUT->heading(get_string('access_key', 'block_exam_actions'), 3);
        echo $OUTPUT->box_start('generalbox boxalignleft boxwidthnormal exam_box');

        $tdata = array();
        $tdata[] = array(get_string('course'), $courses[$data->courseid]);
        $tdata[] = array(get_string('access_key', 'block_exam_actions'), html_writer::tag('b', $access_key));

        $unity = $data->access_key_timeout == 1 ? get_string('minute') : get_string('minutes');
        $tdata[] = array(get_string('access_key_timeout', 'block_exam_actions'), $data->access_key_timeout . ' ' . $unity);

        $yesno = $data->verify_client_host ? get_string('yes') : get_string('no');
        $tdata[] = array(get_string('verify_client_host', 'block_exam_actions'), $yesno);

        $table = new html_table();
        $table->data = $tdata;
        echo html_writer::table($table);
        echo $OUTPUT->box_end();

        $button_new = $OUTPUT->single_button($baseurl, get_string('new_access_key', 'block_exam_actions'));
        $button_back = $OUTPUT->single_button($returnurl, get_string('back'));

        echo html_writer::tag('div', $button_new . $button_back);

        echo $OUTPUT->footer();
    } else {
        print_error('no_course', 'block_exam_actions', $data->courseid);
    }
} else {
    echo $OUTPUT->header();
    echo $OUTPUT->heading(get_string('generating_access_key', 'block_exam_actions') . $OUTPUT->help_icon('generating_access_key', 'block_exam_actions'), 3);
    echo "<br/>";

    if (empty($courses)) {
        echo $OUTPUT->heading(get_string('no_course_to_generate_key', 'block_exam_actions'), 4);
        echo $OUTPUT->single_button($returnurl, get_string('back'));
    } else {
        echo $OUTPUT->box_start('generalbox boxalignleft boxwidthwide exam_box');
        $editform->display();
        echo $OUTPUT->box_end();
    }
    echo $OUTPUT->footer();
}
