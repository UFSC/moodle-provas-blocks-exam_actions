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
require_once('generate_access_key_form.php');

if($SESSION->exam_role != 'teacher' && $SESSION->exam_role != 'monitor') {
    print_error('no_monitor', 'block_exam_actions');
}

exam_courses_menu();

$baseurl = new moodle_url('/blocks/exam_actions/generate_access_key.php');
$returnurl = new moodle_url('/my');

$context = context_system::instance();
$PAGE->set_url($baseurl);
$PAGE->set_context($context);
$site = get_site();
$PAGE->set_heading($site->fullname);
$PAGE->set_pagelayout('standard');

$PAGE->navbar->add(get_string('generating_access_key', 'block_exam_actions'));

$courses = exam_courses_menu();
$editform = new generate_access_key_form(null, array('data'=>$courses));

if ($editform->is_cancelled()) {
    redirect($returnurl);
} else if ($data = $editform->get_data()) {
    $access_key = exam_generate_access_key($data->courseid, $USER->id, $data->access_key_timeout, $data->verify_client_host);
    if(isset($data->release)) {
        $url = new moodle_url('/blocks/exam_actions/release_computer.php', array('access_key'=>$access_key));
        require_logout();
        redirect($url);
    } else {
        // generate and show access key
        echo $OUTPUT->header();
        echo $OUTPUT->box_start('generalbox boxaligncenter boxwidthnormal');
        echo $OUTPUT->heading(get_string('access_key', 'block_exam_actions'));

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

        $button_new = new single_button($baseurl, get_string('new_access_key', 'block_exam_actions'));
        $button_back = new single_button($returnurl, get_string('back'));
        echo html_writer::tag('div', $OUTPUT->render($button_new) . $OUTPUT->render($button_back), array('class' => 'buttons'));

        echo $OUTPUT->box_end();
        echo $OUTPUT->footer();
    }
} else {
    echo $OUTPUT->header();
    echo html_writer::tag('h2', get_string('generating_access_key', 'block_exam_actions'));
    echo "<br/>";

    $editform->display();
    echo $OUTPUT->footer();
}
