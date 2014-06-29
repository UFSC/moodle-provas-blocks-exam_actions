<?php

defined('MOODLE_INTERNAL') || die();

class block_exam_actions_observer {

    public static function user_loggedin(\core\event\user_loggedin $event) {
        global $CFG, $DB, $SESSION;

        if(is_siteadmin($event->userid) || isguestuser($event->userid)) {
            return true;
        }

        if(isset($SESSION->exam_role) && $SESSION->exam_role != 'student') {
            require_once($CFG->libdir . '/blocklib.php');
            require_once($CFG->dirroot . '/my/lib.php');

            self::add_block_to_user($event->userid, 'exam_actions');
        }

        return true;
    }

    private static function add_block_to_user($userid, $block_name) {
        $page = new moodle_page();
        if(!$page->blocks->is_known_block_type($block_name)) {
            return;
        }

        $context = context_user::instance($userid);
        $page->set_blocks_editing_capability('moodle/my:manageblocks');

        my_copy_page($userid);
        $currentpage = my_get_page($userid);

        $page->set_context($context);
        $page->set_url('/my/index.php');
        $page->set_pagelayout('mydashboard');
        $page->set_pagetype('my-index');
        $page->blocks->add_region('content');
        $page->set_subpage($currentpage->id);

        $page->blocks->load_blocks(true);
        $page->blocks->create_all_block_instances();

        if(!$page->blocks->is_block_present($block_name)) {
            $page->blocks->add_block_at_end_of_default_region($block_name);
        }
    }

}
