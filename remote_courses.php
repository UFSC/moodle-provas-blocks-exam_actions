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
 * This file contains the Load Students page.
 *
 * @package    block_exam_actions
 * @author     Antonio Carlos Mariani
 * @copyright  2010 onwards Universidade Federal de Santa Catarina (http://www.ufsc.br)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../config.php');
require_once(dirname(__FILE__).'/locallib.php');

require_login();

\local_exam_authorization\authorization::check_ip_range_editor();

$add = optional_param('add', 0, PARAM_BOOL);
$confirm = optional_param('confirmadd', 0, PARAM_BOOL);
if ($add || $confirm) {
    $identifier = urldecode(required_param('identifier', PARAM_TEXT));
    $shortname = urldecode(required_param('shortname', PARAM_TEXT));
    $local_shortname = "{$identifier}_{$shortname}";
    if ($id = $DB->get_field('course', 'id', array('shortname'=>$local_shortname))) {
        redirect(new moodle_url('/course/view.php', array('id'=>$id)));
        exit;
    }
}

if ($confirm && confirm_sesskey()) {
    if ($remote_course = \local_exam_authorization\authorization::get_remote_course($USER->username, $identifier, $shortname)) {
        if (in_array('editor', $remote_course->functions)) {
            $new_course = exam_add_course($identifier, $remote_course);
            \local_exam_authorization\authorization::review_permissions($USER);
            exam_enrol_students($identifier, $shortname, $new_course);
            redirect(new moodle_url('/course/view.php', array('id'=>$new_course->id)));
        } else {
            print_error('no_editor', 'block_exam_actions');
        }
    } else {
        print_error('no_remote_course', 'block_exam_actions');
    }
    exit;
}

$baseurl = new moodle_url('/blocks/exam_actions/remote_courses.php');
$context = context_system::instance();
$PAGE->set_url($baseurl);
$PAGE->set_context($context);
$site = get_site();
$PAGE->set_heading($site->fullname);
$PAGE->set_pagelayout('standard');

$PAGE->navbar->add(get_string('remote_courses', 'block_exam_actions'));
echo $OUTPUT->header();

if ($add) {
    echo $OUTPUT->heading(get_string('enablecourse', 'block_exam_actions', format_string($shortname)), 3);
    $yesurl = new moodle_url('/blocks/exam_actions/remote_courses.php',
                    array('identifier'=>urlencode($identifier), 'shortname'=>urlencode($shortname) ,'confirmadd'=>1,'sesskey'=>sesskey()));
    $message = get_string('confirmenablecourse', 'block_exam_actions', $shortname);
    echo $OUTPUT->confirm($message, $yesurl, $baseurl);
    echo $OUTPUT->footer();
    exit;
}

echo $OUTPUT->heading(get_string('remote_courses', 'block_exam_actions'), 3);
echo $OUTPUT->box_start('generalbox boxaligncenter boxwidthwide');
echo get_string('remote_courses_msg', 'block_exam_actions');

echo html_writer::start_tag('DIV', array('class'=>'remote_courses'));
echo exam_show_category_tree($USER->username);
echo html_writer::end_tag('DIV');

echo $OUTPUT->box_end();
echo $OUTPUT->footer();
