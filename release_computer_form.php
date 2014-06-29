<?php

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/lib/formslib.php');

class release_computer_form extends moodleform {

    public function definition() {

        $mform = $this->_form;

        $mform->addElement('text', 'access_key', get_string('access_key', 'block_exam_actions'), 'maxlength="8" size="10"');
        $mform->addRule('access_key', get_string('required'), 'required', null, 'client');
        $mform->setType('access_key', PARAM_TEXT);

        $buttonarray = array();
        $buttonarray[] = &$mform->createElement('submit', 'release_computer', get_string('release_computer', 'block_exam_actions'));
        $buttonarray[] = &$mform->createElement('cancel');
        $mform->addGroup($buttonarray, 'buttonar', '', array(' '), false);
    }

    public function validation($data, $files) {
        global $DB;

        $errors = parent::validation($data, $files);

        if ($access_key = $DB->get_record('exam_access_keys', array('access_key' => $data['access_key']))) {
            if ($access_key->timecreated + $access_key->timeout*60 < time()) {
                $errors['access_key'] = get_string('access_key_timedout', 'block_exam_actions');
            }
        } else {
            $errors['access_key'] = get_string('access_key_unknown', 'block_exam_actions');
        }

        return $errors;
    }

}
