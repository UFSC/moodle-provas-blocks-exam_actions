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
 * This file contains the observers of Exam Actions block.
 *
 * @package    block_exam_actions
 * @author     Antonio Carlos Mariani
 * @copyright  2010 onwards Universidade Federal de Santa Catarina (http://www.ufsc.br)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

class block_exam_actions_observer {

    public static function user_loggedin(\core\event\user_loggedin $event) {
        global $CFG, $DB, $SESSION;

        if(is_siteadmin($event->userid) || isguestuser($event->userid)) {
            return true;
        }

        require_once($CFG->libdir . '/blocklib.php');
        require_once($CFG->dirroot . '/my/lib.php');
        self::add_block_to_user($event->userid, 'exam_actions');

        return true;
    }

    public static function user_loggedinas(\core\event\user_loggedinas $event) {
        global $CFG, $DB, $SESSION;

        if(is_siteadmin($event->relateduserid) || isguestuser($event->relateduserid)) {
            return true;
        }

        if(!$user = $DB->get_record('user', array('id'=>$event->relateduserid))) {
            return true;
        }

        \local_exam_authorization\authorization::review_permissions($user);
        if(isset($SESSION->exam_user_functions) && !in_array('student', $SESSION->exam_user_functions)) {
            require_once($CFG->libdir . '/blocklib.php');
            require_once($CFG->dirroot . '/my/lib.php');
            self::add_block_to_user($event->relateduserid, 'exam_actions');
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
