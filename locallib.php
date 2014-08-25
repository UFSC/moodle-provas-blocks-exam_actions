<?php

// Este bloco é parte do Moodle Provas - http://tutoriais.moodle.ufsc.br/provas/
//
// O Moodle Provas pode ser utilizado livremente por instituições integradas à
// UAB - Universidade Aberta do Brasil (http://www.uab.capes.gov.br/), assim como ser
// modificado para adequação à estrutura destas instituições
// sobre os termos da "GNU General Public License" como publicada pela
// "Free Software Foundation".

// You should have received a copy of the GNU General Public License
// along with this plugin.  If not, see <http://www.gnu.org/licenses/>.

/**
 * This file contains the support functions for Exam Actions block.
 *
 * @package    block_exam_actions
 * @copyright  2012 onwards Antonio Carlos Mariani (https://moodle.ufsc.br)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once($CFG->libdir . '/filelib.php');
require_once($CFG->libdir . '/coursecatlib.php');
require_once($CFG->dirroot . '/course/lib.php');
require_once($CFG->dirroot . '/user/lib.php');
require_once($CFG->dirroot . '/user/profile/lib.php');

function exam_add_course($identifier, $remote_course) {
    global $DB, $SESSION;

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
        $remote_categories = exam_get_remote_categories($identifier, array($remote_course->categoryid));
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

function exam_enrol_students($identifier, $shortname, $course) {
    global $DB, $CFG;

    if (!enrol_is_enabled('manual')) {
        print_error('Enrol manual plugin is not enabled');
    }
    if (!$enrol = enrol_get_plugin('manual')) {
        print_error('Enrol manual plugin is not enabled');
    }
    if ($instances = $DB->get_records('enrol', array('enrol'=>'manual', 'courseid'=>$course->id), 'sortorder,id ASC')) {
        $enrol_instance = reset($instances);
    } else {
        $enrol_instance = $enrol->add_default_instance($course);
    }
    $roleid = $DB->get_field('role', 'id', array('shortname'=>'student'), MUST_EXIST);

    $userfields = array('username', 'idnumber', 'firstname', 'lastname', 'email', 'auth', 'password');
    $userfields_str = 'id, ' . implode(', ', $userfields);
    $customfields = $DB->get_records_menu('user_info_field', null, 'shortname', 'shortname, id');

    $students = exam_get_students($identifier, $shortname, $userfields, array_keys($customfields));
    $sql = "SELECT ue.userid, MAX(ue.status) AS status
              FROM {enrol} e
              JOIN {user_enrolments} ue ON (ue.enrolid = e.id)
             WHERE e.enrol = 'manual'
               AND e.courseid = :courseid
          GROUP BY ue.userid";
    $already_enrolled = $DB->get_records_sql($sql, array('courseid'=>$course->id));

    $auth_enabled = get_enabled_auth_plugins();

    foreach($students AS $student) {
        if($user = $DB->get_record('user', array('username'=> $student->username), $userfields_str)) {
            $update = false;
            $update_password = false;
            foreach($userfields AS $field) {
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
            $student->id = $user->id;
        } else {
            if(!in_array($student->auth, $auth_enabled)) {
                $student->auth = 'manual';
            }
            $student->confirmed      = 1;
            $student->mnethostid     = $CFG->mnet_localhost_id;
            $student->lang           = $CFG->lang;
            $student->id = user_create_user($student, true);
        }

        if(!empty($customfields)) {
            $save = false;
            foreach($customfields AS $f=>$fid) {
                if(isset($student->$f)) {
                    $field = 'profile_field_' . $f;
                    $student->$field = $student->$f;
                    $save = true;
                }
            }
            if($save) {
                profile_save_data($student);
            }
        }

        if (isset($already_enrolled[$student->id])) {
            $student->enrol = $already_enrolled[$student->id]->status;
        } else {
            $enrol->enrol_user($enrol_instance, $student->id, $roleid, 0, 0, ENROL_USER_SUSPENDED);
            $student->enrol = ENROL_USER_SUSPENDED;
        }
    }

    return $students;
}

function exam_build_html_category_tree($identifier, $courses) {
    $tree = exam_build_category_tree($identifier, $courses);
    $html_cat = exam_recursive_build_html_category_tree($identifier, $tree);
    if(count($html_cat) > 0) {
        return html_writer::tag('UL', implode("\n", $html_cat));
    } else {
        return '';
    }
}

function exam_recursive_build_html_category_tree($identifier, &$tree) {
    global $SESSION;

    $html_cat = array();

    foreach($tree AS $catid=>$cat) {
        $html_courses = array();
        if(!empty($cat['courses'])) {
            foreach($cat['courses'] AS $c) {
                $local_shortname = "{$identifier}_{$c->shortname}";
                if(isset($SESSION->exam_functions['editor'][$local_shortname])) {
                    $url = new moodle_url('/course/view.php?id=13', array('id'=>$SESSION->exam_functions['editor'][$local_shortname]));
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

function exam_build_category_tree($identifier, $rcourses) {
    global $SESSION;

    $courses = array();
    foreach($rcourses AS $c) {
        if(in_array('editor', $c->functions)) {
            if(!isset($courses[$c->categoryid])) {
                $courses[$c->categoryid] = array();
            }
            $courses[$c->categoryid][] = $c;
        }
    }

    $remote_categories = exam_get_remote_categories($identifier, array_keys($courses));
    if(!isset($remote_categories[$identifier])) {
        return array();
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

function exam_get_students($identifier, $course_shortname, $userfields=array(), $customfields=array()) {
    if(empty($userfields)) {
        $userfields = array('username');
    }

    $function = 'local_exam_remote_get_students';
    $params = array('shortname'=>$course_shortname);
    $params['userfields'] = array_merge($userfields, $customfields);

    $students = array();
    foreach(\local_exam_authorization\authorization::call_remote_function($identifier, $function, $params) as $st) {
        $student = new stdClass();
        $student->id = $st->id;
        foreach($st->userfields AS $obj) {
            $field = $obj->field;
            $student->$field = $obj->value;
        }
        $students[$student->id] = $student;
    }
    return $students;
}

function exam_get_remote_categories($identifier='', $categoryids=null) {
    global $SESSION;

    $function = 'local_exam_remote_get_categories';

    $moodles = empty($identifier) ? array(\local_exam_authorization\authorization::get_moodle($identifier)) :
                        \local_exam_authorization\authorization::get_moodles();

    $remote_categories = array();
    foreach($moodles AS $m) {
        if(is_null($categoryids)) {
            $rcatids = array();
            foreach($SESSION->exam_courses[$m->identifier] AS $c) {
                $rcatids[$c->categoryid] = true;
            }
            $params = array('categoryids'=>array_keys($rcatids));
        } else {
            $params = array('categoryids'=>$categoryids);
        }

        $rcats = \local_exam_authorization\authorization::call_remote_function($m->identifier, $function, $params);
        $remote_categories[$m->identifier] = array();
        foreach($rcats AS $rcat) {
            $remote_categories[$m->identifier][$rcat->id] = $rcat;
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

function exam_courses_menu($function, $capability) {
    global $USER, $SESSION, $DB;

    $courses_menu = array();
    \local_exam_authorization\authorization::calculate_functions($USER->username);
    foreach($SESSION->exam_courses AS $identifier=>$courses) {
        foreach($courses AS $shortname=>$course) {
            $local_shortname = "{$identifier}_{$shortname}";
            if(isset($SESSION->exam_functions[$function][$local_shortname])) {
                $courseid = $SESSION->exam_functions[$function][$local_shortname];
                if($c = $DB->get_record('course', array('id'=>$courseid, 'visible'=>1), 'id, fullname')) {
                    $context = context_course::instance($courseid);
                    if(has_capability($capability, $context)) {
                        $courses_menu[$c->id] = $c->fullname;
                    }
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
