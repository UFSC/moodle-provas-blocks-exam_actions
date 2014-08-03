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
 * @subpackage provas_generate_key
 * @copyright  2012 - Antonio Carlos Mariani, Luis Henrique Mulinari
 */

require('../../config.php');
require_once('locallib.php');

require_login();

if(!isset($SESSION->exam_courses)) {
    print_error('no_remote_courses', 'block_exam_actions');
}

if(optional_param('confirmadd', 0, PARAM_INT) && confirm_sesskey()) {
    $identifier = urldecode(required_param('identifier', PARAM_TEXT));
    $shortname = urldecode(required_param('shortname', PARAM_TEXT));
    foreach($SESSION->exam_courses[$identifier] AS $c) {
        if($c->shortname == $shortname) {
            if(!isset($c->local_course)) {
                $course = exam_add_course($identifier, $c);
                $c->local_course = $course;
                exam_enrol_user($USER->id, $course->id, 'editingteacher');
                exam_add_students($identifier, $shortname);
                redirect(new moodle_url('/course/view.php', array('id'=>$course->id)));
            }
        }
    }
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

if (optional_param('add', 0, PARAM_BOOL)) {
    $identifier = urldecode(required_param('identifier', PARAM_TEXT));
    $shortname = urldecode(required_param('shortname', PARAM_TEXT));

    echo $OUTPUT->heading(get_string('enablecourse', 'block_exam_actions', format_string($shortname)));
    $yesurl = new moodle_url('/blocks/exam_actions/remote_courses.php',
                    array('identifier'=>urlencode($identifier), 'shortname'=>urlencode($shortname) ,'confirmadd'=>1,'sesskey'=>sesskey()));
    $message = get_string('confirmenablecourse', 'block_exam_actions', $shortname);
    echo $OUTPUT->confirm($message, $yesurl, $baseurl);
    echo $OUTPUT->footer();
    exit;
}

echo $OUTPUT->heading(get_string('remote_courses', 'block_exam_actions'));
echo $OUTPUT->box_start('generalbox boxaligncenter boxwidthwide');
echo get_string('remote_courses_msg', 'block_exam_actions');
echo $OUTPUT->box_end();

if(empty($SESSION->exam_courses)) {
    echo html_writer::tag('h2', get_string('no_remote_courses', 'block_exam_actions'));
} else {
    $moodles = \local_exam_authorization\authorization::get_moodle();
    echo html_writer::start_tag('UL');
    foreach($SESSION->exam_courses AS $identifier=>$courses) {
        echo html_writer::start_tag('LI');
        echo html_writer::tag('STRONG', $moodles[$identifier]->description);
        echo exam_build_html_category_tree($identifier);
        echo html_writer::end_tag('LI');
    }
    echo html_writer::end_tag('UL');
}

echo $OUTPUT->footer();
