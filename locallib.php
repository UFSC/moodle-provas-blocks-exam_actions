<?php

require_once($CFG->libdir . '/filelib.php');
require_once($CFG->libdir . '/coursecatlib.php');
require_once($CFG->dirroot . '/course/lib.php');
require_once($CFG->dirroot . '/local/exam_authorization/classes/exam_authorization.php');

function exam_add_course($identifier, $remote_course) {
    global $DB;

    $moodle = \local\exam_authorization::get_moodle($identifier);
    if(!$parent = $DB->get_field('course_categories', 'id', array('idnumber'=> $identifier))) {
        $new_cat = new StdClass();
        $new_cat->name = $moodle->description;
        $new_cat->idnumber = $identifier;
        $new_cat->parent = 0;
        $new_cat = coursecat::create($new_cat);
        $parent = $new_cat->id;
    }

    if(!$catid = $DB->get_field('course_categories', 'id', array('idnumber'=> $identifier . '_' . $remote_course->categoryid))) {
        $catid = $parent;
        $remote_categories = exam_get_remote_categories($identifier);
        if(isset($remote_categories[$identifier][$remote_course->categoryid])) {
            foreach($remote_categories[$identifier][$remote_course->categoryid]->path AS $rcid) {
                if(!$catid = $DB->get_field('course_categories', 'id', array('idnumber'=> $identifier . '_' . $rcid))) {
                    $new_cat = new StdClass();
                    $new_cat->name = $remote_categories[$identifier][$rcid]->name;
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

function exam_enrol_user($userid, $courseid, $role_shortname) {
    global $DB;

    if($roleid = $DB->get_field('role', 'id', array('shortname'=>$role_shortname))) {
        enrol_try_internal_enrol($courseid, $userid, $roleid);
    } else {
        print_error('invalidrole');
    }
}

function exam_enrol_students($identifier, $shortname, $courseid) {
    global $DB, $CFG;

    if(!$roleid = $DB->get_field('role', 'id', array('shortname'=>'student'))) {
        print_error('invalidrole');
    }

    $students = exam_get_students($identifier, $shortname);

    $user_fields = array('idnumber', 'firstname', 'lastname', 'email');

    $auth_enabled = get_enabled_auth_plugins();
    foreach($students AS $student) {
        if($user = $DB->get_record('user', array('username'=> $student->username), 'id,auth,password,'.implode(',', $user_fields))) {
            $update = false;
            foreach($user_fields AS $field) {
                if( $user->$field != $student->$field) {
                    $user->$field = $student->$field;
                    $update = true;
                }
            }
            if($user->auth == $student->auth) {
                if($user->password != $student->password) {
                    $user->password = $student->password;
                    $update = true;
                }
            } else {
                if(in_array($student->auth, $auth_enabled)) {
                    $user->auth     = $student->auth;
                    $user->password = $student->password;
                    $update = true;
                }
            }

            if($update) {
                $user->timemodified = time();
                $DB->update_record('user', $user);
            }
        } else {
            if(!in_array($student->auth, $auth_enabled)) {
                $student->auth = 'manual';
            }
            $student->timecreated    = time();
            $student->timemodified   = time();
            $student->confirmed      = 1;
            $student->mnethostid     = $CFG->mnet_localhost_id;
            $student->lang           = $CFG->lang;
            $DB->insert_record('user', $student);
        }
    }
}

function exam_build_html_category_tree($identifier) {
    $tree = exam_build_category_tree($identifier);
    $html_cat = exam_recursive_build_html_category_tree($identifier, $tree);
    if(count($html_cat) > 0) {
        return html_writer::tag('UL', implode("\n", $html_cat));
    } else {
        return '';
    }
}

function exam_recursive_build_html_category_tree($identifier, &$tree) {
    $html_cat = array();

    foreach($tree AS $catid=>$cat) {
        $html_courses = array();
        if(!empty($cat['courses'])) {
            foreach($cat['courses'] AS $c) {
                if(isset($c->local_course)) {
                    $url = new moodle_url('/course/view.php?id=13', array('id'=>$c->local_course->id));
                    $already = get_string('already_added', 'block_exam_actions');
                } else {
                    $url = new moodle_url('/blocks/exam_actions/remote_courses.php', array('identifier'=>urlencode($identifier), 'shortname'=>urlencode($c->shortname), 'add'=>1));
                    $already = '';
                }
                $html_courses[] = html_writer::tag('LI', html_writer::link($url, $c->fullname) . $already);
            }
        }

        if(empty($cat['subcats'])) {
            $html_subcats = array();
        } else {
            $html_subcats = exam_recursive_build_html_category_tree($identifier, $cat['subcats']);
        }

        if(count($html_courses) + count($html_subcats) > 1) {
            $html = html_writer::tag('UL', implode("\n", $html_courses) . "\n" . implode("\n", $html_subcats));
            $html_cat[] = html_writer::tag('LI', $cat['name'] . $html);
        } else if(count($html_courses) > 0) {
            $html_cat[] = reset($html_courses);
        } else if(count($html_subcats) > 0) {
            $html_cat[] = reset($html_subcats);
        }
    }

    return $html_cat;
}

function exam_build_category_tree($identifier) {
    global $SESSION;

    $remote_categories = exam_get_remote_categories($identifier);
    if(!isset($remote_categories[$identifier])) {
        return array();
    }

    $courses = array();
    foreach($SESSION->exam_remote_courses[$identifier] AS $c) {
        if(!isset($courses[$c->categoryid])) {
            $courses[$c->categoryid] = array();
        }
        $courses[$c->categoryid][] = $c;
    }

    $tree = array();
    foreach($remote_categories[$identifier] AS $cat) {
        if(isset($courses[$cat->id])) {
            $cs = $courses[$cat->id];
        } else {
            $cs = array();
        }
        exam_recursive_build_category_tree($tree, $cat, 0, $cs);
    }

    return $tree;

}

function exam_recursive_build_category_tree(&$tree, $cat, $depth, &$courses) {
    $pathid = $cat->path[$depth];
    if($cat->id == $pathid) {
        $tree[$cat->id]['name'] = $cat->name;
        $tree[$cat->id]['subcats'] = array();
        $tree[$cat->id]['courses'] = $courses;
    } else {
        exam_recursive_build_category_tree($tree[$pathid]['subcats'], $cat, $depth+1, $courses);
    }
}

function exam_get_students($identifier, $course_shortname) {
    $function = 'local_exam_remote_get_students';
    $params = array('shortname'=>$course_shortname);

    return \local\exam_authorization::call_remote_function($identifier, $function, $params);
}

function exam_get_remote_categories($identifier=null) {
    global $SESSION;

    $function = 'local_exam_remote_get_categories';

    if($identifier == null) {
        $moodles = \local\exam_authorization::get_moodle();
    } else {
        $moodles = array($identifier=>\local\exam_authorization::get_moodle($identifier));
    }

    $remote_categories = array();
    foreach($moodles AS $ident=>$m) {

        $rcatids = array();
        foreach($SESSION->exam_remote_courses[$ident] AS $c) {
            $rcatids[$c->categoryid] = true;
        }

        $params = array('categoryids'=>array_keys($rcatids));

        $rcats = \local\exam_authorization::call_remote_function($ident, $function, $params);
        $remote_categories[$ident] = array();
        foreach($rcats AS $rcat) {
            $remote_categories[$ident][$rcat->id] = $rcat;
        }
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

function exam_courses_menu() {
    global $SESSION, $DB;

    $courses_menu = array();
    foreach($SESSION->exam_remote_courses AS $identifier=>$rcourses) {
        foreach($rcourses AS $rc) {
            if(isset($rc->local_course)) {
                if($DB->record_exists('course', array('id'=>$rc->local_course->id, 'visible'=>1))) {
                    $courses_menu[$rc->local_course->id] = $rc->fullname;
                }
            }
        }
    }
    return $courses_menu;
}

