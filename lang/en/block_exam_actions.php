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
 * Language file for block "Exam Actions".
 *
 * @package    block_exam_actions
 * @author     Antonio Carlos Mariani
 * @copyright  2010 onwards Universidade Federal de Santa Catarina (http://www.ufsc.br)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'Ações relativas o Moodle Provas';
$string['exam_actions:addinstance'] = 'Adiciona um novo bloco exam_actions';
$string['exam_actions:myaddinstance'] = 'Adiciona um novo bloco exam_actions à Página Inicial';
$string['exam_actions:conduct_exam'] = 'Gerar chave de acesso';
$string['exam_actions:monitor_exam'] = 'Visualizar relatórios de acompanhamento';

$string['title'] = 'Moodle Provas';
$string['no_permission'] = 'Você não tem permissão para realizar esta operação.';
$string['no_remote_course_found'] = 'Não foi localizado curso equivamente no Moodle Remoto.';

$string['groupings'] = 'Agrupamentos e respectivos grupos:';
$string['groups'] = 'Grupos não pertencentes a agrupamentos:';


$string['review_permissions'] = 'Revisar minhas permissões';
$string['reviewed_permissions'] = 'Suas permissões foram revistas.';
$string['remote_courses'] = 'Cursos remotos';
$string['remote_courses_msg'] = 'Abaixo está a relação de cursos das instalações remotos de Moodle ligadados ao Moodle Provas.
    Clique no nome do curso para ter acesso a ele (caso já esteja disponível) ou para disponibilizá-lo no Moodle Provas.';
$string['export_exam'] = 'Exportar atividades';
$string['export_exam_title'] = 'Exportar atividades: \'{$a}\'';
$string['export_exam_desc'] = 'Selecione abaixo as atividades a serem exportadas para o Moodle de Origem:';
$string['export'] = 'Exportar';
$string['export_result'] = 'Resultado da exportação';
$string['access_key'] = 'Chave de acesso';
$string['new_access_key'] = 'Nova chave de acesso';
$string['generate_access_key'] = 'Gerar chave de acesso';
$string['generated_access_keys'] = 'Chaves de acesso geradas';
$string['used_access_keys'] = 'Uso das chaves de acesso';
$string['student_access'] = 'Acessos dos estudantes';
$string['release_computer'] = 'Liberar computador';
$string['release_this_computer'] = 'Liberar computador para realizar prova';
$string['monitor_exam'] = 'Monitorar prova';
$string['monitor_exam_title'] = 'Monitorar prova: \'{$a}\'';
$string['sync_students'] = 'Revisar inscrições';
$string['synced_students'] = 'Estudantes Inscritos: \'{$a}\'';
$string['sync_groups'] = 'Sincronizar grupos';
$string['sync_groups_title'] = 'Sincronizar grupos: \'{$a}\'';
$string['computer_released'] = 'Computador liberado para realizar prova: \'{$a}\'';
$string['new_course'] = 'Disponibilizar novo curso';

$string['no_remote_courses'] = 'Não foram localizados cursos remotos';
$string['no_remote_course'] = 'Não foi localizado curso remoto';
$string['no_course'] = 'Não foi localizado curso local: \'{$a}\'';
$string['enablecourse'] = 'Disponibilizando curso: \'{$a}\'';
$string['confirmenablecourse'] = 'Você realmente deseja disponibilizar o curso \'{$a}\' no Moodle Provas?';

$string['no_activities_to_export'] = 'Não há atividades a serem exportadas';
$string['no_selected_activities'] = 'Não foi selecionada nenhuma atividade a ser exportada para o Moodle de Origem';

$string['already_added'] = ' (já disponível)';
$string['no_proctor'] = 'Sem permissão para gerar chaves de acesso.';
$string['no_monitor'] = 'Sem permissão para monitorar provas.';
$string['no_editor'] = 'Sem permissão para editar ou disponibilizar este curso.';
$string['generating_access_key'] = 'Gerando chave de acesso';
$string['generating_access_key_title'] = 'As chaves de acesso são necessárias para liberar os computadores que serão utilizados pelos estudantes para realizar provas.';
$string['no_course_to_generate_key'] = 'Não há cursos disponíveis/visíveis para os quais você possa gerar chave de acesso';

$string['course'] = 'Curso';
$string['course_help'] = 'Curso para o qual a chave de acesso é válida. Somente estudantes inscritos neste curso poderão ter acesso a ele utilizando essa chave.';
$string['select_course'] = '-- Selecione um curso';

$string['access_key_timeout'] = 'Validade da chave de acesso';
$string['access_key_timeout_help'] = 'Após este tempo a chave de acesso perde sua validade, ou seja, não poderá mais ser utilizada para liberar um computador para realizar prova.
    A autenticação do estudante também não será mais possível, mesmo que o computador já tenha sido liberado.';
$string['access_key_unknown'] = 'Chave de acesso desconhecida';
$string['access_key_timedout'] = 'Chave de acesso com validade expirada';

$string['header_version'] = 'Versão do CD';
$string['header_ip'] = 'Endereço IP local';
$string['header_network'] = 'Endereço de Rede local';
$string['real_ipaddress'] = 'Endereço IP real';

$string['verify_client_host'] = 'Restrigir uso à rede local';
$string['verify_client_host_help'] = 'Verificar se o computador onde será utilizada a chave de acesso está na mesma rede do computador onde a chave foi gerada.';

$string['empty_course'] = 'É necessário selecionar o curso para o qual a chave de acesso é válida';
$string['empty_backup_file'] = 'Falha na geração do backup da atividade: arquivo vazio';
$string['activity_exported'] = 'Atividade foi exportada';

$string['cd_needed'] = 'É necessário utilizar o CD (ou pendrive) de provas para que este computador possa ser liberado para realizar uma prova.';
$string['invalid_cd_version'] = 'A versão do CD (ou pendrive) de provas é inválida ou muito antiga.';
$string['out_of_student_ip_ranges'] = 'Este computador não pode ser utilizado para realizar uma prova pois seu número IP está fora da faixa de IPs permitidos.';

$string['no_student_access_data'] = 'Não há dados de acessos de estudantes a serem apresentados';
$string['no_groups_to_sync'] = 'Não há grupos a sincronizar';

$string['access_keys'] = 'Chaves de acesso';
$string['used_by'] = 'Utilizada por';
$string['used_time'] = 'Utilizada em';
$string['createdon'] = 'Criada em';
$string['createdby'] = 'Criada por';

$string['kept'] = 'Mantido';
$string['enrolled'] = 'Inscrito';
$string['unenrolled'] = 'Desinscrito';

$string['no_function'] = 'Não foi identificada nenhuma função/papel com o qual você possa realizar ações sobre cursos neste ambiente.';
$string['warnings'] = 'Avisos';
?>
