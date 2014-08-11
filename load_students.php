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
require_once(dirname(__FILE__).'/locallib.php');

require_login();

$courseid = required_param('courseid', PARAM_INT);
$course = $DB->get_record('course', array('id'=>$courseid), '*', MUST_EXIST);
$context = context_course::instance($courseid);
if(!has_capability('block/exam_actions:conduct_exam', $context)) {
    print_error('no_proctor', 'block_exam_actions');
}

$baseurl = new moodle_url('/blocks/exam_actions/load_students.php', array('courseid'=>$courseid));
$PAGE->set_course($course);
$PAGE->set_url($baseurl);
$PAGE->set_context($context);
$site = get_site();
$PAGE->set_heading($site->fullname);
$PAGE->set_pagelayout('standard');
$PAGE->set_title(get_string('load_students', 'block_exam_actions'));
$PAGE->navbar->add(get_string('load_students', 'block_exam_actions'));

echo $OUTPUT->header();

list($identifier, $shortname) = explode('_', $course->shortname, 2);
$students = exam_enrol_students($identifier, $shortname, $course);
$customfields = $DB->get_records_menu('user_info_field', null, 'shortname', 'shortname, name');

$data = array();
$ind = 0;
foreach($students AS $st) {
    $ind++;
    $status  = $st->enrol == ENROL_USER_SUSPENDED ? get_string('participationsuspended', 'enrol') : get_string('participationactive', 'enrol');
    $line = array($ind, $st->username, $st->firstname, $st->lastname, $st->auth, $status);
    foreach($customfields AS $f=>$name) {
        $field = 'profile_field_' . $f;
        $line[] = isset($st->$field) ? $st->$field : '';
    }
    $data[] = $line;
}

$head = array('',
              get_string('username'),
              get_string('firstname'),
              get_string('lastname'),
              get_string('type_auth', 'plugin'),
              get_string('status'),
             );
foreach($customfields AS $f=>$name) {
    $head[] = $name;
}

$table = new html_table();
$table->head = $head;
$table->data = $data;

echo $OUTPUT->heading(get_string('loaded_students', 'block_exam_actions'));
echo $OUTPUT->heading($course->fullname);

echo $OUTPUT->box_start('generalbox boxaligncenter boxwidthwide');
echo html_writer::table($table);
echo $OUTPUT->box_end();

echo $OUTPUT->footer();
