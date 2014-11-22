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
require_once($CFG->dirroot . '/group/lib.php');

require_login();

$courseid = required_param('courseid', PARAM_INT);
$course = $DB->get_record('course', array('id'=>$courseid), '*', MUST_EXIST);
$returnurl = new moodle_url('/course/view.php', array('id'=>$courseid));

if (optional_param('cancel', false, PARAM_TEXT)) {
    redirect($returnurl);
    exit;
}

$groupingids = optional_param_array('groupingids', array(), PARAM_INT);
$groupids = optional_param_array('groupids', array(), PARAM_INT);
$synchronize = optional_param('synchronize', false, PARAM_TEXT);

$context = context_course::instance($courseid);
if (!has_capability('moodle/course:managegroups', $context)) {
    print_error('no_permission', 'block_exam_actions');
}

$baseurl = new moodle_url('/blocks/exam_actions/sync_groups', array('courseid'=>$courseid));

$PAGE->set_course($course);
$PAGE->set_url($baseurl);
$PAGE->set_context($context);
$site = get_site();
$PAGE->set_heading($site->fullname);
$PAGE->set_pagelayout('course');
$PAGE->set_title(get_string('sync_groups', 'block_exam_actions'));
$PAGE->navbar->add(get_string('sync_groups', 'block_exam_actions'));

list($identifier, $shortname) = explode('_', $course->shortname, 2);

$params = array('key'=>'shortname', 'value'=>$shortname);
$remote_courseid = \local_exam_authorization\authorization::call_remote_function($identifier, 'local_exam_remote_get_courseid', $params);
if ($remote_courseid <= 0) {
    print_error('no_remote_course_found', 'block_exam_actions');
}

$gs = \local_exam_authorization\authorization::call_remote_function($identifier, 'core_group_get_course_groupings', array('courseid'=>$remote_courseid));
$groupings = array();
foreach ($gs AS $g) {
    $g->localid = groups_get_grouping_by_name($courseid, $g->name);
    if ($synchronize && !$g->localid && in_array($g->id, $groupingids)) {
        $grouping = new stdClass();
        $grouping->courseid = $courseid;
        $grouping->name     = $g->name;
        $g->localid = groups_create_grouping($grouping);
    }
    $groupings[$g->id] = $g;
}

$gs = \local_exam_authorization\authorization::call_remote_function($identifier, 'core_group_get_course_groups', array('courseid'=>$remote_courseid));
$groups = array();
foreach ($gs AS $g) {
    $g->localid = groups_get_group_by_name($courseid, $g->name);
    if ($synchronize && !$g->localid && in_array($g->id, $groupids)) {
        $group = new stdClass();
        $group->courseid = $courseid;
        $group->name     = $g->name;
        $g->localid = groups_create_group($group);
    }
    $groups[$g->id] = $g;
}
if (!empty($groupings)) {
    $params = array('returngroups'=>1, 'groupingids'=>array_keys($groupings));
    $groupings_groups = \local_exam_authorization\authorization::call_remote_function($identifier, 'core_group_get_groupings', $params);
    foreach ($groupings_groups AS $gg) {
        if ($groupings[$gg->id]->localid && in_array($gg->id, $groupingids)) {
            foreach ($gg->groups AS $g) {
                if ($groups[$g->id]->localid && in_array($g->id, $groupids)) {
                    groups_assign_grouping($groupings[$gg->id]->localid, $groups[$g->id]->localid);
                }
            }
        }
    }
}

if ($synchronize) {
    $students = exam_enrol_students($identifier, $shortname, $course);
    $gs = \local_exam_authorization\authorization::call_remote_function($identifier, 'core_group_get_group_members', array('groupids'=>array_keys($groups)));
    foreach ($gs AS $gms) {
        if ($groups[$gms->groupid]->localid) {
            $localid = $groups[$gms->groupid]->localid;
            $local_users = groups_get_members($localid, 'u.id');
            foreach ($gms->userids AS $uid) {
                if (isset($students[$uid])) {
                    if ($userid = $DB->get_field('user', 'id', array('username'=>$students[$uid]->username))) {
                        if (isset($local_users[$userid])) {
                            unset($local_users[$userid]);
                        } else {
                            groups_add_member($localid, $userid);
                        }
                    }
                }
            }
            foreach ($local_users AS $userid=>$gm) {
                groups_remove_member($localid, $userid);
            }
        }
    }
}

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('sync_groups_title', 'block_exam_actions', $course->fullname));

echo html_writer::start_tag('DIV', array('class'=>'exam_box exam_list'));

echo html_writer::start_tag('form', array('method'=>'post'));
echo html_writer::empty_tag('input', array('type'=>'hidden', 'name'=>'courseid', 'value'=>$courseid));

$has_group = false;
$grouped = array();

if (!empty($groupings)) {
    echo html_writer::tag('B', get_string('groupings', 'block_exam_actions'));
    echo html_writer::start_tag('ul');
    foreach ($groupings_groups As $gr) {
        echo html_writer::start_tag('li');
        $checked = $groupings[$gr->id]->localid ? true : false;
        $params = $checked ? array('disabled'=>'disabled') : null;
        echo html_writer::checkbox('groupingids[]', $gr->id, $checked, $gr->name, $params);
        echo html_writer::start_tag('ul');
        foreach ($gr->groups as $g) {
            $checked = $groups[$g->id]->localid ? true : false;
            $params = $checked ? array('disabled'=>'disabled') : null;
            $checkbox = html_writer::checkbox('groupids[]', $g->id, $checked, $g->name, $params);
            echo html_writer::tag('li', $checkbox);
            $grouped[$g->id] = true;
            $has_group = true;
        }
        echo html_writer::end_tag('ul');
        echo html_writer::end_tag('li');
    }
    echo html_writer::end_tag('ul');
}

if (count($groups) > count($grouped)) {
    echo html_writer::tag('B', get_string('groups', 'block_exam_actions'));
    echo html_writer::start_tag('ul');
    foreach ($groups AS $gid=>$g) {
        if (!isset($grouped[$gid])) {
            $checked = $groups[$g->id]->localid ? true : false;
            $params = $checked ? array('disabled'=>'disabled') : null;
            $checkbox = html_writer::checkbox('groupids[]', $g->id, $checked, $g->name, $params);
            echo html_writer::tag('li', $checkbox);
            $has_group = true;
        }
    }
    echo html_writer::end_tag('ul');
}

if ($has_group) {
    $sync_button = html_writer::empty_tag('input', array('type'=>'submit', 'name'=>'synchronize', 'value'=>get_string('sync_groups', 'block_exam_actions')));
} else {
    $sync_button = '';
    echo $OUTPUT->heading(get_string('no_groups_to_sync', 'block_exam_actions'));
}
$cancel_button = html_writer::empty_tag('input', array('type'=>'submit', 'name'=>'cancel', 'value'=>get_string('back')));
echo html_writer::tag('div', $sync_button . $cancel_button, array('class' => 'buttons'));

html_writer::end_tag('form');

echo html_writer::end_tag('DIV');
echo $OUTPUT->footer();
