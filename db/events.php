<?php

defined('MOODLE_INTERNAL') || die();

$observers = array(
    array(
        'eventname' => '\core\event\user_loggedin',
        'callback' => 'block_exam_actions_observer::user_loggedin',
    ),

);
