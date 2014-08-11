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
 * Version details
 *
 * @package    blocks
 * @subpackage exam_actions
 * @copyright  2014 - Antonio Carlos Mariani
 */

defined('MOODLE_INTERNAL') || die();

$plugin->version = 2014080501;
$plugin->requires  = 2013111800;
$plugin->component = 'block_exam_actions'; // Full name of the plugin (used for diagnostics)
$plugin->dependencies = array(
    'local_exam_authorization' => 2014080500
);
