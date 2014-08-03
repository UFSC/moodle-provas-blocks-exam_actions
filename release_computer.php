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
require_once(dirname(__FILE__).'/release_computer_form.php');

$baseurl = new moodle_url('/blocks/exam_actions/release_computer.php');
$returnurl = new moodle_url('/');

if(!\local_exam_authorization\authorization::check_ip_header(false) || !\local_exam_authorization\authorization::check_network_header(false)) {
    print_error('cd_needed', 'block_exam_actions', $returnurl);
}
if(!\local_exam_authorization\authorization::check_version_header(false)) {
    print_error('invalid_cd_version', 'block_exam_actions', $returnurl);
}
if(!\local_exam_authorization\authorization::check_ip_range_student(false)) {
    print_error('out_of_student_ip_ranges', 'block_exam_actions', $returnurl);
}

if($key = optional_param('key', '', PARAM_TEXT)) {
    if (!$access_key = $DB->get_record('exam_access_keys', array('access_key' => $key))) {
        print_error('access_key_unknown', 'block_exam_actions');
    }
    if ($access_key->timecreated + $access_key->timeout*60 < time()) {
        print_error('access_key_timedout', 'block_exam_actions');
    }
} else {
    $site = get_site();
    $context = context_system::instance();

    $PAGE->set_url($baseurl);
    $PAGE->set_context($context);
    $PAGE->set_heading($site->fullname);
    $PAGE->set_pagelayout('standard');
    $PAGE->navbar->add(get_string('release_computer', 'block_exam_actions'));

    $editform = new release_computer_form();
    if ($editform->is_cancelled()) {
        redirect($returnurl);
    } else if ($data = $editform->get_data()) {
        $key = $data->access_key;
    } else {
        echo $OUTPUT->header();
        echo $OUTPUT->heading(get_string('release_this_computer', 'block_exam_actions'));
        echo $OUTPUT->box_start('generalbox boxaligncenter boxwidthnormal');
        $editform->display();
        echo $OUTPUT->box_end();
        echo $OUTPUT->footer();
        exit;
    }
}

$SESSION->exam_access_key = $key;
redirect($returnurl);
