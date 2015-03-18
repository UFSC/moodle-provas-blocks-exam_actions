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
 * This file contains the Sync Groups page.
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

require_capability('moodle/course:managegroups', $context);
require_login($course);

$baseurl = new moodle_url('/blocks/exam_actions/sync_groups', array('courseid'=>$courseid));
$returnurl = new moodle_url('/course/view.php', array('id'=>$courseid));

if (optional_param('cancel', false, PARAM_TEXT)) {
    redirect($returnurl);
    exit;
}

$site = get_site();

$PAGE->set_course($course);
$PAGE->set_url($baseurl);
$PAGE->set_context($context);
$PAGE->set_heading($site->fullname);
$PAGE->set_pagelayout('course');
$PAGE->set_title(get_string('sync_groups', 'block_exam_actions'));
$PAGE->navbar->add(get_string('sync_groups_title', 'block_exam_actions'));

$synchronize = optional_param('synchronize', false, PARAM_TEXT);
$groupingids = optional_param_array('groupingids', array(), PARAM_INT);
$groupids = optional_param_array('groupids', array(), PARAM_INT);

$map_group = optional_param('map_group', false, PARAM_TEXT);
$local_groupid_to_map = optional_param('local_groupid_to_map', 0, PARAM_INT);
$remote_groupid_to_map = optional_param('remote_groupid_to_map', 0, PARAM_INT);

list($identifier, $shortname) = explode('_', $course->shortname, 2);

$params = array('key'=>'shortname', 'value'=>$shortname);
$remote_courseid = \local_exam_authorization\authorization::call_remote_function($identifier, 'local_exam_remote_get_courseid', $params);
if ($remote_courseid <= 0) {
    print_error('no_remote_course_found', 'block_exam_actions');
}

list($remote_groupings, $remote_groups, $remote_groupings_groups) =
    sync_groupings_groups_and_members($identifier, $shortname, $course, $remote_courseid, $synchronize, $groupingids, $groupids);

$message = false;
$message_type = 'exam_message';
if ($synchronize) {
    $message = 'synced_groups_msg';
} else if ($map_group) {
    $local_group = groups_get_group($local_groupid_to_map);
    if ($local_group && isset($remote_groups[$remote_groupid_to_map])) {
        $local_group->name = $remote_groups[$remote_groupid_to_map]->name;
        groups_update_group($local_group);
        list($remote_groupings, $remote_groups, $remote_groupings_groups) =
            sync_groupings_groups_and_members($identifier, $shortname, $course, $remote_courseid, $synchronize, $groupingids, $groupids);
        $message = 'mapped_group_msg';
    } else {
        $message = 'not_mapped_group_msg';
        $message_type = 'exam_error';
    }
}

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('sync_groups_title', 'block_exam_actions') . $OUTPUT->help_icon('sync_groups', 'block_exam_actions'), 3);

if ($message) {
    echo $OUTPUT->box_start('generalbox boxaligncenter boxwidthwide');
    echo $OUTPUT->heading(get_string($message, 'block_exam_actions'), 5, $message_type);
    echo $OUTPUT->box_end();
}

echo html_writer::start_tag('form', array('method'=>'post'));

echo $OUTPUT->box_start('generalbox boxalignleft boxwidthwide exam_box exam_list');

echo html_writer::empty_tag('input', array('type'=>'hidden', 'name'=>'courseid', 'value'=>$courseid));

$has_group = false;
$grouped = array();

if (!empty($remote_groupings)) {
    echo html_writer::tag('B', get_string('groupings', 'block_exam_actions'));
    echo html_writer::start_tag('ul');
    foreach ($remote_groupings_groups As $gr) {
        echo html_writer::start_tag('li');
        $checked = $remote_groupings[$gr->id]->localid ? true : false;
        $params = $checked ? array('disabled'=>'disabled') : null;
        echo html_writer::checkbox('groupingids[]', $gr->id, $checked, $gr->name, $params);
        echo html_writer::start_tag('ul');
        if(isset($gr->groups)) {
            foreach ($gr->groups as $g) {
                $checked = $remote_groups[$g->id]->localid ? true : false;
                $params = $checked ? array('disabled'=>'disabled') : null;
                $checkbox = html_writer::checkbox('groupids[]', $g->id, $checked, $g->name, $params);
                echo html_writer::tag('li', $checkbox);
                $grouped[$g->id] = true;
                $has_group = true;
            }
        }
        echo html_writer::end_tag('ul');
        echo html_writer::end_tag('li');
    }
    echo html_writer::end_tag('ul');
}

if (count($remote_groups) > count($grouped)) {
    echo html_writer::tag('B', get_string('groups', 'block_exam_actions'));
    echo html_writer::start_tag('ul');
    foreach ($remote_groups AS $gid=>$g) {
        if (!isset($grouped[$gid])) {
            $checked = $remote_groups[$g->id]->localid ? true : false;
            $params = $checked ? array('disabled'=>'disabled') : null;
            $checkbox = html_writer::checkbox('groupids[]', $g->id, $checked, $g->name, $params);
            echo html_writer::tag('li', $checkbox);
            $has_group = true;
        }
    }
    echo html_writer::end_tag('ul');
}

if ($has_group) {
    $sync_button = html_writer::empty_tag('input', array('type'=>'submit', 'name'=>'synchronize', 'value'=>get_string('sync_groups_button', 'block_exam_actions')));
} else {
    $sync_button = '';
    echo $OUTPUT->heading(get_string('no_groups_to_sync', 'block_exam_actions'));
}
echo html_writer::tag('div', $sync_button, array('class' => 'buttons'));

echo $OUTPUT->box_end();

// ----------------------------------------------------------------------------------------

$not_mapped_groups = groups_get_all_groups($course->id);
foreach ($remote_groups AS $r_group) {
    if ($r_group->localid) {
        unset($not_mapped_groups[$r_group->localid]);
    }
}

if (!empty($not_mapped_groups)) {
    echo $OUTPUT->box_start('generalbox boxalignleft boxwidthwide exam_box exam_list');

    echo html_writer::tag('B', get_string('not_mapped_groups', 'block_exam_actions'));
    echo html_writer::start_tag('ul');

    foreach ($not_mapped_groups AS $gid=>$g) {
        $radio = html_writer::tag('input', $g->name, array('type' => 'radio', 'name' => 'local_groupid_to_map', 'value' => $gid));
        echo html_writer::tag('li', $radio);
    }
    echo html_writer::end_tag('ul');

    $remote_groups_not_synced = array();
    foreach ($remote_groups AS $gid=>$g) {
        if (!$remote_groups[$g->id]->localid) {
            $remote_groups_not_synced[$gid] = $g->name;
        }
    }

    if (!empty($remote_groups_not_synced)) {
        $map_group = html_writer::empty_tag('input', array('type'=>'submit', 'name'=>'map_group', 'value'=>get_string('map_group_button', 'block_exam_actions')));
        $select_group = html_writer::select($remote_groups_not_synced, 'remote_groupid_to_map', false, array(''=>'choosedots'));
        echo html_writer::tag('div', $map_group . $select_group, array('class' => 'buttons'));
    }

    echo $OUTPUT->box_end();
}

echo html_writer::end_tag('form');

echo $OUTPUT->single_button($returnurl, get_string('back'));
echo $OUTPUT->footer();
