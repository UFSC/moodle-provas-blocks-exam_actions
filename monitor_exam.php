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
$course = $DB->get_record('course', array('id'=>$courseid));
$context = context_course::instance($courseid);
if(!has_capability('block/exam_actions:monitor_exam', $context)) {
    print_error('no_monitor', 'block_exam_actions');
}

$baseurl = new moodle_url('/blocks/exam_actions/monitor_exam.php', array('courseid'=>$courseid));
$PAGE->set_course($course);
$PAGE->set_url($baseurl);
$PAGE->set_context($context);
$site = get_site();
$PAGE->set_heading($site->fullname);
$PAGE->set_pagelayout('course');
$PAGE->set_pagelayout('standard');
$PAGE->set_title(get_string('monitor_exam', 'block_exam_actions'));
$PAGE->navbar->add(get_string('monitor_exam', 'block_exam_actions'));

echo $OUTPUT->header();

$tab_items = array('generated_access_keys', 'used_access_keys');
$tabs = array();
foreach($tab_items AS $act) {
    $url = clone $baseurl;
    $url->param('action', $act);
    $tabs[$act] = new tabobject($act, $url, get_string($act, 'block_exam_actions'));
}

$action = optional_param('action', '' , PARAM_TEXT);
$action = isset($tabs[$action]) ? $action : reset($tab_items);

echo $OUTPUT->heading(get_string('monitor_exam', 'block_exam_actions'));
echo $OUTPUT->heading($course->fullname);
print_tabs(array($tabs), $action);

switch($action) {

case 'generated_access_keys':
    $sql = "SELECT ak.*, u.firstname, lastname
              FROM {exam_access_keys} ak
              JOIN {user} u ON (u.id = ak.userid)
             WHERE ak.courseid = :courseid
          ORDER BY ak.timecreated";
    $recs = $DB->get_records_sql($sql, array('courseid'=>$courseid));
    $data = array();
    foreach($recs AS $rec) {
        $data[] = array($rec->access_key,
                        userdate($rec->timecreated),
                        $rec->firstname.' '.$rec->lastname,
                        $rec->ip,
                        $rec->timeout . ' ' . ($rec->timeout == 1 ? get_string('minute') : get_string('minutes')),
                        $rec->verify_client_host == 1 ? get_string('yes') : get_string('no'),
                       );
    }

    $table = new html_table();
    $table->head  = array(get_string('access_key', 'block_exam_actions'),
                          get_string('createdon', 'block_exam_actions'),
                          get_string('createdby', 'block_exam_actions'),
                          get_string('real_ipaddress', 'block_exam_actions'),
                          get_string('access_key_timeout', 'block_exam_actions'),
                          get_string('verify_client_host', 'block_exam_actions'),
                         );
    $table->data = $data;
    break;

case 'used_access_keys':
    $order_options = array('used_by'=>'u.firstname, u.lastname, akl.time',
                           'used_time'=>'akl.time, u.firstname, u.lastname',
                           'access_key'=>'akl.access_key, akl.time',
                           'real_ipaddress'=>'akl.ip, akl.time');
    $order = optional_param('order', '' , PARAM_TEXT);
    $orderby = isset($order_options[$order]) ? $order_options[$order] : $order_options['used_by'];

    $sql = "SELECT akl.*, u.firstname, lastname
              FROM {exam_access_keys_log} akl
              JOIN {exam_access_keys} ak ON (ak.access_key = akl.access_key)
              JOIN {user} u ON (u.id = akl.userid)
             WHERE ak.courseid = :courseid
          ORDER BY {$orderby}";
    $recs = $DB->get_records_sql($sql, array('courseid'=>$courseid, 'contextlevel'=>CONTEXT_COURSE));
    $data = array();
    foreach($recs AS $rec) {
        $data[] = array($rec->firstname.' '.$rec->lastname,
                        userdate($rec->time),
                        $rec->access_key,
                        $rec->ip,
                        $rec->header_version,
                        $rec->header_ip,
                        $rec->header_network,
                        $rec->info);
    }

    $acturl = clone $baseurl;
    $acturl->param('action', $action);
    $head = array();
    foreach($order_options AS $cmp=>$ord) {
        $url = clone $acturl;
        $url->param('order', $cmp);
        $head[] = html_writer::link($url, get_string($cmp, 'block_exam_actions'));
    }
    $head[] = get_string('header_version', 'block_exam_actions');
    $head[] = get_string('header_ip', 'block_exam_actions');
    $head[] = get_string('header_network', 'block_exam_actions');
    $head[] = get_string('info');

    $table = new html_table();
    $table->head = $head;
    $table->data = $data;
    break;
}

if(isset($table)) {
    $boxwidth = isset($boxwidth) ? $boxwidth : 'boxwidthwide';
    echo $OUTPUT->box_start('generalbox boxaligncenter '.$boxwidth);
    echo html_writer::table($table);
    echo $OUTPUT->box_end();
}

echo $OUTPUT->footer();
