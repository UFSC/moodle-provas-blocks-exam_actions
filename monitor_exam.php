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
 * This file contains the Monitor Exam page.
 *
 * @package    block_exam_actions
 * @author     Antonio Carlos Mariani
 * @copyright  2010 onwards Universidade Federal de Santa Catarina (http://www.ufsc.br)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../config.php');
require_once(dirname(__FILE__).'/locallib.php');

$courseid = required_param('courseid', PARAM_INT);
$course = $DB->get_record('course', array('id'=>$courseid), '*', MUST_EXIST);
$context = context_course::instance($courseid);

require_capability('block/exam_actions:monitor_exam', $context);
require_login($course);

$baseurl = new moodle_url('/blocks/exam_actions/monitor_exam.php', array('courseid'=>$courseid));
$returnurl = new moodle_url('/course/view.php', array('id'=>$courseid));

$site = get_site();

$PAGE->set_course($course);
$PAGE->set_url($baseurl);
$PAGE->set_context($context);
$PAGE->set_heading($site->fullname);
$PAGE->set_pagelayout('course');
$PAGE->set_title(get_string('monitor_exam', 'block_exam_actions'));
$PAGE->navbar->add(get_string('monitor_exam_title', 'block_exam_actions'));

echo $OUTPUT->header();

$tab_items = array('generated_access_keys', 'used_access_keys');
$tabs = array();
foreach ($tab_items AS $act) {
    $url = clone $baseurl;
    $url->param('action', $act);
    $tabs[$act] = new tabobject($act, $url, get_string($act, 'block_exam_actions'));
}

$action = optional_param('action', '' , PARAM_TEXT);
$action = isset($tabs[$action]) ? $action : reset($tab_items);

echo $OUTPUT->heading(get_string('monitor_exam_title', 'block_exam_actions'));
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
    foreach ($recs AS $rec) {
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
    foreach ($recs AS $rec) {
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
    foreach ($order_options AS $cmp=>$ord) {
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

if (isset($table)) {
    echo html_writer::start_tag('DIV', array('class'=>'exam_box'));
    echo html_writer::table($table);
    echo html_writer::end_tag('DIV');
}

echo $OUTPUT->single_button($returnurl, get_string('back'));
echo $OUTPUT->footer();
