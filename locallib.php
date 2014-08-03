<?php

require_once($CFG->libdir . '/filelib.php');
require_once($CFG->libdir . '/coursecatlib.php');
require_once($CFG->dirroot . '/course/lib.php');
require_once($CFG->dirroot . '/user/lib.php');

function exam_add_course($identifier, $remote_course) {
    global $DB;

    $moodle = \local_exam_authorization\authorization::get_moodle($identifier);
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

function exam_add_students($identifier, $shortname) {
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
            $update_password = false;
            foreach($user_fields AS $field) {
                if( $user->$field != $student->$field) {
                    $user->$field = $student->$field;
                    $update = true;
                }
            }
            if($user->auth == $student->auth) {
                if($user->password != $student->password) {
                    $user->password = $student->password;
                    $update_password = true;
                }
            } else {
                if(in_array($student->auth, $auth_enabled)) {
                    $user->auth     = $student->auth;
                    $user->password = $student->password;
                    $update_password = true;
                }
            }

            if($update || $update_password) {
                user_update_user($user, $update_password);
            }
        } else {
            if(!in_array($student->auth, $auth_enabled)) {
                $student->auth = 'manual';
            }
            $student->confirmed      = 1;
            $student->mnethostid     = $CFG->mnet_localhost_id;
            $student->lang           = $CFG->lang;
            user_create_user($student, true);
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
    foreach($SESSION->exam_courses[$identifier] AS $c) {
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

    return \local_exam_authorization\authorization::call_remote_function($identifier, $function, $params);
}

function exam_get_remote_categories($identifier=null) {
    global $SESSION;

    $function = 'local_exam_remote_get_categories';

    if($identifier == null) {
        $moodles = \local_exam_authorization\authorization::get_moodle();
    } else {
        $moodles = array($identifier=>\local_exam_authorization\authorization::get_moodle($identifier));
    }

    $remote_categories = array();
    foreach($moodles AS $ident=>$m) {

        $rcatids = array();
        foreach($SESSION->exam_courses[$ident] AS $c) {
            $rcatids[$c->categoryid] = true;
        }

        $params = array('categoryids'=>array_keys($rcatids));

        $rcats = \local_exam_authorization\authorization::call_remote_function($ident, $function, $params);
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
    foreach($SESSION->exam_courses AS $identifier=>$courses) {
        foreach($courses AS $c) {
            if(in_array('proctor', $c->functions)) {
                $shortname = "{$identifier}_{$c->shortname}";
                if($lc = $DB->get_record('course', array('shortname'=>$shortname, 'visible'=>1), 'id, fullname')) {
                    $courses_menu[$lc->id] = $lc->fullname;
                }
            }
        }
    }
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
    // $info =  $curl->get_info();
    // var_dump($return); exit;
        /*
        if($info['http_code'] == 200) { // OK
            $resp = json_decode($return);
            if(is_object($resp)) {
                if(isset($resp->message)) {
                    return get_string('error', 'block_activities_remote_copy', $resp->message);
                } else {
                    return get_string('error_object', 'block_activities_remote_copy', var_export($resp, true));
                }
            } else {
                if($resp == 'OK') {
                    return $resp;
                } else {
                    return get_string('error_return', 'block_activities_remote_copy', $resp);
                }
            }
        } else if($info['http_code'] == 404) { // Not found
            return get_string('error_url', 'block_activities_remote_copy', $urlbase);
        } else {
            return get_string('error_httpcode', 'block_activities_remote_copy', $info['http_code']);
        }
    } catch (Exception $e) {
        return get_string('error_exception', 'block_activities_remote_copy', $e->getMessage());
    }
    */
}
