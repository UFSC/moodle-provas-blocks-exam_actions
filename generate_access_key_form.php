<?php

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/lib/formslib.php');

class generate_access_key_form extends moodleform {

    public function definition() {

        $mform = $this->_form;

        $courses = $this->_customdata['data'];
        if(count($courses) > 1) {
            array_unshift ($courses, get_string('select_course', 'block_exam_actions'));
        }

        $mform->addElement('select', 'courseid', get_string('course', 'block_exam_actions'), $courses);
        $mform->addHelpButton('courseid', 'course', 'block_exam_actions');

        $options = array('1' => '1 ' . get_string('minute'),
                         '3' => '3 ' . get_string('minutes'),
                         '5' => '5 ' . get_string('minutes'),
                         '15' => '15 ' . get_string('minutes'));
        $mform->addElement('select', 'access_key_timeout', get_string('access_key_timeout', 'block_exam_actions'), $options);
        $mform->addHelpButton('access_key_timeout', 'access_key_timeout', 'block_exam_actions');
        $mform->setDefault('access_key_timeout', 5);

        $mform->addElement('selectyesno', 'verify_client_host', get_string('verify_client_host', 'block_exam_actions'));
        $mform->addHelpButton('verify_client_host', 'verify_client_host', 'block_exam_actions');
        $mform->setType('verify_client_host', PARAM_INT);
        $mform->setDefault('verify_client_host', 1);

        $buttons = array();
        $buttons[] = &$mform->createElement('submit', 'generate', get_string('generate_access_key', 'block_exam_actions'));
        $buttons[] = &$mform->createElement('submit', 'release', get_string('release_computer', 'block_exam_actions'));
        $buttons[] = &$mform->createElement('cancel');
        $mform->addGroup($buttons, 'buttons', '', array(' '), false);
        $mform->closeHeaderBefore('buttons');

    }

    public function validation($data, $files) {
        global $DB;

        $errors = parent::validation($data, $files);

        if(empty($data['courseid'])) {
            $errors['courseid'] = get_string('empty_course', 'block_exam_actions');
        }

        return $errors;
    }

}

