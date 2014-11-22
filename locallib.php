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
 * This file contains the support functions for Exam Actions block.
 *
 * @package    block_exam_actions
 * @author     Antonio Carlos Mariani
 * @copyright  2010 onwards Universidade Federal de Santa Catarina (http://www.ufsc.br)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once($CFG->libdir . '/filelib.php');
require_once($CFG->libdir . '/coursecatlib.php');
require_once($CFG->dirroot . '/course/lib.php');
require_once($CFG->dirroot . '/user/lib.php');
require_once($CFG->dirroot . '/user/profile/lib.php');

function exam_add_course($identifier, $remote_course) {
    global $DB;

    $moodle = \local_exam_authorization\authorization::get_moodle($identifier);
    if (!$parent = $DB->get_field('course_categories', 'id', array('idnumber' => $identifier))) {
        $new_cat = new StdClass();
        $new_cat->name = $moodle->description;
        $new_cat->idnumber = $identifier;
        $new_cat->parent = 0;
        $new_cat = coursecat::create($new_cat);
        $parent = $new_cat->id;
    }

    if (!$catid = $DB->get_field('course_categories', 'id', array('idnumber' => $identifier . '_' . $remote_course->categoryid))) {
        $catid = $parent;
        $remote_categories = exam_get_remote_categories($identifier, array($remote_course->categoryid));
        if (isset($remote_categories[$remote_course->categoryid])) {
            foreach ($remote_categories[$remote_course->categoryid]->path AS $rcid) {
                if (!$catid = $DB->get_field('course_categories', 'id', array('idnumber' => $identifier . '_' . $rcid))) {
                    $new_cat = new StdClass();
                    $new_cat->name = $remote_categories[$rcid]->name;
                    $new_cat->idnumber = $identifier . '_' . $rcid;
                    $new_cat->parent = $parent;
                    $new_cat = coursecat::create($new_cat);
                    $catid = $new_cat->id;
                }
                $parent = $catid;
            }
        }
    }

    try {
        $newcourse = new stdClass;
        $newcourse->shortname = $identifier . '_' . $remote_course->shortname;
        $newcourse->fullname  = $remote_course->fullname;
        $newcourse->category  = $catid;
        $newcourse->visible   = 0;
        $newcourse->enrollable   = 0;
        $newcourse->startdate    = time();
        return create_course($newcourse);
    } catch (Exception $e) {
        print_error($e->getMessage());
    }
}

function exam_add_or_update_user($student, $customfields=array()) {
    global $CFG, $DB;

    $userfields = array('username', 'idnumber', 'firstname', 'lastname', 'email');
    $userfields_str = 'id, password, auth, ' . implode(', ', $userfields);
    $auth_enabled = get_enabled_auth_plugins();

    $default_auth_plugin = \local_exam_authorization\authorization::get_config('auth_plugin');

    if ($user = $DB->get_record('user', array('username' => $student->username), $userfields_str)) {
        $update = false;
        foreach ($userfields AS $field) {
            if ( $user->$field != $student->$field) {
                $user->$field = $student->$field;
                $update = true;
            }
        }

        if (empty($default_auth_plugin)) {
            $auth = in_array($student->auth, $auth_enabled) ? $student->auth : 'manual';
        } else {
            $auth = $default_auth_plugin;
        }
        if ($user->auth != $auth) {
            $user->auth = $auth;
            $update = true;
        }

        $password = $user->password;
        $update_password = $user->auth == 'manual' && \local_exam_authorization\authorization::get_config('update_password');
        if ($update) {
            unset($user->password);
            user_update_user($user, false);
        }
    } else {
        $user = new stdClass();
        $user->confirmed   = 1;
        $user->mnethostid  = $CFG->mnet_localhost_id;
        $user->lang        = $CFG->lang;
        foreach ($userfields AS $field) {
            $user->$field = $student->$field;
        }
        $user->auth = in_array($student->auth, $auth_enabled) ? $student->auth : 'manual';
        $user->id = user_create_user($user, false);
        $password = '';
        $update_password = $user->auth == 'manual';
    }

    if ($update_password) {
        if ($password != $student->password && !empty($student->password)) {
            $password = $student->password;
            $DB->set_field('user', 'password', $password, array('id' => $user->id));
        }
        if (empty($password)) {
            $password = 'K5=#' . rand(1000000000, 9999999999);
            $DB->set_field('user', 'password', $password, array('id' => $user->id));
        }
    }

    if (!empty($customfields)) {
        $save = false;
        foreach ($customfields AS $f => $fid) {
            if (isset($student->$f)) {
                $field = 'profile_field_' . $f;
                $student->$field = $student->$f;
                $save = true;
            }
        }
        if ($save) {
            profile_save_data($student);
        }
    }

    return $user;
}

function exam_enrol_students($identifier, $shortname, $course) {
    global $DB, $CFG;

    if (!enrol_is_enabled('manual')) {
        print_error('Enrol manual plugin is not enabled');
    }
    if (!$enrol = enrol_get_plugin('manual')) {
        print_error('Enrol manual plugin is not enabled');
    }
    if ($instances = $DB->get_records('enrol', array('enrol' => 'manual', 'courseid' => $course->id), 'sortorder,id ASC')) {
        $enrol_instance = reset($instances);
    } else {
        $enrol_instance = $enrol->add_default_instance($course);
    }
    $roleid = $DB->get_field('role', 'id', array('shortname' => 'student'), MUST_EXIST);

    $userfields = array('username', 'idnumber', 'firstname', 'lastname', 'email', 'password', 'auth');
    $userfields_str = 'id, ' . implode(', ', $userfields);
    $customfields = $DB->get_records_menu('user_info_field', null, 'shortname', 'shortname, id');

    $students = exam_get_students($identifier, $shortname, $userfields, array_keys($customfields));
    $sql = "SELECT ue.userid, MAX(ue.status) AS status
              FROM {enrol} e
              JOIN {user_enrolments} ue ON (ue.enrolid = e.id)
              JOIN {context} ctx ON (ctx.instanceid = e.courseid AND ctx.contextlevel = :contextcourse)
              JOIN {role_assignments} ra ON (ra.contextid = ctx.id AND ra.userid = ue.userid AND ra.roleid = :roleid)
             WHERE e.enrol = 'manual'
               AND e.courseid = :courseid
          GROUP BY ue.userid";
    $already_enrolled = $DB->get_records_sql($sql, array('courseid' => $course->id, 'roleid' => $roleid, 'contextcourse' => CONTEXT_COURSE));
    
    $result_students = array();
    foreach ($students AS $student) {
        $student = exam_add_or_update_user($student, $customfields);

        if (isset($already_enrolled[$student->id])) {
            $student->enrol = $already_enrolled[$student->id]->status;
            $student->action = 'kept';
            unset($already_enrolled[$student->id]);
        } else {
            $enrol->enrol_user($enrol_instance, $student->id, $roleid, 0, 0, ENROL_USER_SUSPENDED);
            $student->enrol = ENROL_USER_SUSPENDED;
            $student->action = 'enrolled';
        }

        $result_students[$student->id] = $student;
    }

    foreach ($already_enrolled AS $userid => $st) {
        if ($user = $DB->get_record('user', array('id' => $userid), $userfields_str)) {
            $user->enrol = $st->status;
            $user->action = 'unenrolled';
            $result_students[$user->id] = $user;

            $sql = "SELECT ue.*
                      FROM {enrol} e
                      JOIN {user_enrolments} ue ON (ue.enrolid = e.id)
                      JOIN {context} ctx ON (ctx.instanceid = e.courseid AND ctx.contextlevel = :contextcourse)
                      JOIN {role_assignments} ra ON (ra.contextid = ctx.id AND ra.userid = ue.userid AND ra.roleid = :roleid)
                     WHERE e.enrol = 'manual'
                       AND e.courseid = :courseid";
            foreach ($DB->get_records_sql($sql, array('courseid' => $course->id, 'roleid' => $roleid, 'contextcourse' => CONTEXT_COURSE)) AS $instance) {
                $enrol->unenrol_user($enrol_instance, $userid);
            }
        }
    }

    return $result_students;
}

function exam_show_category_tree($username) {
    $moodles = \local_exam_authorization\authorization::get_moodles();
    $tree = exam_mount_category_tree($username);

    echo html_writer::empty_tag('OL', array('class' => 'tree'));
    foreach ($tree AS $identifier => $categories) {
        echo html_writer::start_tag('LI');
        echo html_writer::tag('SPAN', $moodles[$identifier]->description, array('class' => 'identifier'));
        echo html_writer::start_tag('OL');
        exam_show_categories($identifier, $categories);
        echo html_writer::end_tag('OL');
        echo html_writer::end_tag('LI');
    }
    echo "</OL>\n";
}

function exam_show_categories($identifier, $categories) {
    global $CFG, $OUTPUT;

    foreach ($categories AS $cat) {
        echo html_writer::start_tag('LI');
        $label = "{$identifier}_category_{$cat->id}";
        $folder_img = html_writer::empty_tag('img', array('src' =>  $OUTPUT->pix_url('f/folder'), 'class' => 'exam_pix'));
        echo html_writer::tag('LABEL', $folder_img . $cat->name, array('for' => $label));
        echo html_writer::empty_tag('INPUT', array('type' => 'checkbox', 'id' => $label));
        echo html_writer::start_tag('OL');
        if (!empty($cat->courses)) {
            foreach ($cat->courses AS $c) {
                $params = array('identifier' => urlencode($identifier), 'shortname' => urlencode($c->shortname), 'add' => 1);
                $url = new moodle_url('/blocks/exam_actions/remote_courses.php', $params);
                $img = html_writer::empty_tag('img', array('src' => $OUTPUT->pix_url('i/course')));
                $link = html_writer::link($url, $c->fullname);
                echo html_writer::tag('LI', $img . $link , array('class' => 'course'));
            }
        }
        if (!empty($cat->sub)) {
            exam_show_categories($identifier, $cat->sub);
        }
        echo html_writer::end_tag('OL');
        echo html_writer::end_tag('LI');
    }
}

function exam_mount_category_tree($username) {
    $remote_courses = \local_exam_authorization\authorization::get_remote_courses($username);
    $moodles = \local_exam_authorization\authorization::get_moodles();

    $tree = array();
    foreach ($moodles AS $identifier => $m) {
        if (!empty($remote_courses[$identifier])) {
            $rcourses = array();
            foreach ($remote_courses[$identifier] AS $c) {
                if (in_array('editor', $c->functions)) {
                    if (!isset($rcourses[$c->categoryid])) {
                        $rcourses[$c->categoryid] = array();
                    }
                    $rcourses[$c->categoryid][] = $c;
                }
                unset($c->functions);
            }
            $cats = exam_get_remote_categories($identifier, array_keys($rcourses));
            foreach ($cats AS $catid => $cat) {
                $cat->sub = array();
                $cat->courses = isset($rcourses[$catid]) ? $rcourses[$catid] : array();
            }
            foreach ($cats AS $catid => $cat) {
                $size = count($cat->path);
                if ($size > 1) {
                    $fatherid = $cat->path[$size-2];
                    $cats[$fatherid]->sub[$catid] = $cat;
                }
            }
            foreach (array_keys($cats) AS $catid) {
                $cat = $cats[$catid];
                if (count($cats[$catid]->path) > 1) {
                    unset($cats[$catid]);
                }
            }
            $tree[$identifier] = $cats;
        }
    }
    return $tree;
}


function exam_get_students($identifier, $course_shortname, $userfields=array(), $customfields=array()) {
    if (empty($userfields)) {
        $userfields = array('username');
    }

    $function = 'local_exam_remote_get_students';
    $params = array('shortname' => $course_shortname);
    $params['userfields'] = array_merge($userfields, $customfields);

    $students = array();
    foreach (\local_exam_authorization\authorization::call_remote_function($identifier, $function, $params) as $st) {
        $student = new stdClass();
        $student->remote_id = $st->id;
        foreach ($st->userfields AS $obj) {
            $field = $obj->field;
            $student->$field = $obj->value;
        }
        $students[$student->remote_id] = $student;
    }
    return $students;
}

function exam_get_remote_categories($identifier, $categoryids) {
    $function = 'local_exam_remote_get_categories';
    $params = array('categoryids' => $categoryids);
    $rcats = \local_exam_authorization\authorization::call_remote_function($identifier, $function, $params);
    $remote_categories = array();
    foreach ($rcats AS $rcat) {
        $remote_categories[$rcat->id] = $rcat;
    }
    return $remote_categories;
}

function exam_generate_access_key($courseid, $userid, $access_key_timeout=30, $verify_client_host=1) {
    global $DB;

    $chars = "abcdefghijkmnopqrstuvxwz23456789";

    $key_rec = true;
    while($key_rec) {
        $key = '';
        for($i=1; $i <=8; $i++) {
            $r = rand(0, strlen($chars)-1);
            $key .= substr($chars, $r, 1);
        }
        $key_rec = $DB->get_record('exam_access_keys', array('access_key' => $key));
    }

    $acc = new stdClass();
    $acc->access_key = $key;
    $acc->userid     = $userid;
    $acc->courseid   = $courseid;
    $acc->ip         = $_SERVER["REMOTE_ADDR"];
    $acc->timeout    = $access_key_timeout;
    $acc->verify_client_host = $verify_client_host;
    $acc->timecreated= time();
    $DB->insert_record('exam_access_keys', $acc);

    return $key;
}

function exam_courses_menu($function, $capability) {
    global $SESSION, $DB;

    $courses_menu = array();
    if (!isset($SESSION->exam_user_courses) || empty($SESSION->exam_user_courses)) {
        return $courses_menu;
    }

    foreach ($SESSION->exam_user_courses AS $courseid => $functions) {
        if (in_array($function, $functions)) {
            if ($course = $DB->get_record('course', array('id' => $courseid, 'visible' => 1), 'id, fullname')) {
                $context = context_course::instance($courseid);
                if (has_capability($capability, $context)) {
                    $courses_menu[$courseid] = $course->fullname;

                }
            }
        }
    }
    asort($courses_menu);
    return $courses_menu;
}

function exam_export_activity($identifier, $shortname, $username, $backup_file) {
    $function = 'local_exam_remote_restore_activity';
    $params = array(
        'username' => $username,
        'shortname' => $shortname,
        'backup_file' => $backup_file,
        );

    $return = \local_exam_authorization\authorization::call_remote_function($identifier, $function, $params);

    return $return;
}
