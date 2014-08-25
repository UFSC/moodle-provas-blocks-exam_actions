<?php

// Este bloco é parte do Moodle Provas - http://tutoriais.moodle.ufsc.br/provas/
//
// O Moodle Provas pode ser utilizado livremente por instituições integradas à
// UAB - Universidade Aberta do Brasil (http://www.uab.capes.gov.br/), assim como ser
// modificado para adequação à estrutura destas instituições
// sobre os termos da "GNU General Public License" como publicada pela
// "Free Software Foundation".

// copyright 2012 Universidade Federal de Santa Catarina (http://moodle.ufsc.br)
// license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later

/**
 * Observers
 *
 * @package    block_exam_actions
 * @copyright  2014 onwards Antonio Carlos Mariani (https://moodle.ufsc.br)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$observers = array(
    array(
        'eventname' => '\core\event\user_loggedin',
        'callback' => 'block_exam_actions_observer::user_loggedin',
    ),

);
