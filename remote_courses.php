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
 * This file contains the Load Students page.
 *
 * @package    block_exam_actions
 * @copyright  2012 onwards Antonio Carlos Mariani, Luis Henrique Mulinari (https://moodle.ufsc.br)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../config.php');
require_once(dirname(__FILE__).'/locallib.php');

require_login();

\local_exam_authorization\authorization::check_ip_range_editor();

if(optional_param('confirmadd', 0, PARAM_INT) && confirm_sesskey()) {
    $identifier = urldecode(required_param('identifier', PARAM_TEXT));
    $shortname = urldecode(required_param('shortname', PARAM_TEXT));
    if(isset($SESSION->exam_courses[$identifier][$shortname])) {
        $local_shortname = "{$identifier}_{$shortname}";
        if($id = $DB->get_field('course', 'id', array('shortname'=>$local_shortname))) {
            redirect(new moodle_url('/course/view.php', array('id'=>$id)));
        } else {
            $remote_course = $SESSION->exam_courses[$identifier][$shortname];
            if(in_array('editor', $remote_course->functions)) {
                $new_course = exam_add_course($identifier, $remote_course);
                $roleid = $DB->get_field('role', 'id', array('shortname'=>'editingteacher'), MUST_EXIST);
                enrol_try_internal_enrol($new_course->id, $USER->id, $roleid);
                exam_enrol_students($identifier, $shortname, $new_course);
                redirect(new moodle_url('/course/view.php', array('id'=>$new_course->id)));
            } else {
                print_error('no_editor', 'block_exam_actions');
            }
        }
    } else {
        print_error('no_remote_course', 'block_exam_actions');
    }
    exit;
}

$baseurl = new moodle_url('/blocks/exam_actions/remote_courses.php');
$context = context_system::instance();
$PAGE->set_url($baseurl);
$PAGE->set_context($context);
$site = get_site();
$PAGE->set_heading($site->fullname);
$PAGE->set_pagelayout('standard');

$PAGE->navbar->add(get_string('remote_courses', 'block_exam_actions'));
echo $OUTPUT->header();

if (optional_param('add', 0, PARAM_BOOL)) {
    $identifier = urldecode(required_param('identifier', PARAM_TEXT));
    $shortname = urldecode(required_param('shortname', PARAM_TEXT));

    echo $OUTPUT->heading(get_string('enablecourse', 'block_exam_actions', format_string($shortname)));
    $yesurl = new moodle_url('/blocks/exam_actions/remote_courses.php',
                    array('identifier'=>urlencode($identifier), 'shortname'=>urlencode($shortname) ,'confirmadd'=>1,'sesskey'=>sesskey()));
    $message = get_string('confirmenablecourse', 'block_exam_actions', $shortname);
    echo $OUTPUT->confirm($message, $yesurl, $baseurl);
    echo $OUTPUT->footer();
    exit;
}

echo $OUTPUT->heading(get_string('remote_courses', 'block_exam_actions'));
echo $OUTPUT->box_start('generalbox boxaligncenter boxwidthwide');
echo get_string('remote_courses_msg', 'block_exam_actions');
echo $OUTPUT->box_end();

$SESSION->exam_courses = \local_exam_authorization\authorization::get_remote_courses($USER->username);
\local_exam_authorization\authorization::calculate_functions($USER->username);

if(empty($SESSION->exam_courses)) {
    echo html_writer::tag('h2', get_string('no_remote_courses', 'block_exam_actions'));
} else {
    $moodles = \local_exam_authorization\authorization::get_moodles();
    echo html_writer::start_tag('UL');
    foreach($SESSION->exam_courses AS $identifier=>$cs) {
        echo html_writer::start_tag('LI');
        echo html_writer::tag('STRONG', $moodles[$identifier]->description);
        echo exam_build_html_category_tree($identifier, $cs);
        echo html_writer::end_tag('LI');
    }
    echo html_writer::end_tag('UL');
}

echo $OUTPUT->footer();
