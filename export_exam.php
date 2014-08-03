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
require_once($CFG->dirroot . '/backup/util/includes/backup_includes.php');

$courseid = required_param('id', PARAM_INT);
$course = $DB->get_record('course', array('id'=>$courseid), 'id, shortname, fullname', MUST_EXIST);
$context = context_course::instance($courseid);
require_capability('moodle/backup:backupcourse', $context);

$baseurl = new moodle_url('/blocks/exam_actions/export_exam.php', array('id'=>$courseid));
$returnurl = new moodle_url('/course/view.php', array('id'=>$courseid));

$site = get_site();

$PAGE->set_url($baseurl);
$PAGE->set_context($context);
$PAGE->set_heading($site->fullname);
$PAGE->set_pagelayout('standard');
$PAGE->navbar->add(get_string('export_exam', 'block_exam_actions'));

echo $OUTPUT->header();
$activities = get_array_of_activities($courseid);

$export = optional_param('export', '', PARAM_TEXT);
if(empty($export)) {
    echo $OUTPUT->heading(get_string('export_exam', 'block_exam_actions'));
    echo $OUTPUT->heading(get_string('export_exam_desc', 'block_exam_actions'), 4);
    echo $OUTPUT->box_start('generalbox boxaligncenter boxwidthnormal');

    echo html_writer::start_tag('form', array('method'=>'post', 'action'=>$baseurl));
    echo html_writer::empty_tag('input', array('type'=>'hidden', 'name'=>'id', 'value'=>$courseid));
    foreach($activities AS $act) {
        $module = get_string('modulename', $act->mod);
        $name = "{$act->name} ({$module})";
        echo html_writer::tag('input', $name, array('type'=>'checkbox', 'name'=>"activities[{$act->cm}]", 'value'=>$name));
        echo html_writer::empty_tag('BR');
    }
    echo html_writer::empty_tag('input', array('type'=>'submit', 'name'=>'export', 'value'=>get_string('export', 'block_exam_actions')));
    echo '&nbsp;';
    echo html_writer::link($returnurl, get_string('cancel'));
    echo html_writer::end_tag('form');

    if(empty($activities)) {
        print_error('no_activities_to_export', 'block_exam_actions', $returnurl);
    }

    echo $OUTPUT->box_end();
} else {
    $export_activities = optional_param_array('activities', array(), PARAM_TEXT);
    if(empty($export_activities)) {
        print_error('no_selected_activities', 'block_exam_actions', $baseurl);
    }
    $data = array();
    $adminid = $DB->get_field('user', 'id', array('username'=>'admin'));
    list($identifier, $shortname) = explode('_', $course->shortname, 2);
    foreach($export_activities AS $cmid=>$act_name) {
        // executa backup com permissão de Admin em função de dados de usuários
        try {
            $bc = new backup_controller(backup::TYPE_1ACTIVITY, $cmid, backup::FORMAT_MOODLE, backup::INTERACTIVE_NO, backup::MODE_GENERAL, $adminid);
            $bc->execute_plan();
            $results = $bc->get_results();
            $backup_file = $results['backup_destination']; // May be empty if file already moved to target location.
            if(empty($backup_file)) {
                $data[] = array($act_name, get_string('empty_backup_file', 'block_exam_actions'));
            } else {
                $result = exam_export_activity($identifier, $shortname, $USER->username, $backup_file);
                $backup_file->delete();
                if(is_string($result)) {
                    $data[] = array($act_name, $result);
                } else {
                    $data[] = array($act_name, get_string('error') . ': '. var_export($result, true));
                }
            }
            $bc->destroy();
        } catch (Exception $e) {
            $data[] = array($act_name, $e->getMessage());
        }
    }

    echo $OUTPUT->heading(get_string('export_result', 'block_exam_actions'));
    echo html_writer::start_tag('DIV', array('align'=>'center'));
    $table = new html_table();
    $table->head  = array(get_string('activity'),
                          get_string('status'));

    $table->data = $data;
    echo html_writer::table($table);
    echo $OUTPUT->single_button($returnurl, get_string('back'));
    echo html_writer::end_tag('DIV');
}
echo $OUTPUT->footer();
