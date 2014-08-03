<?php
$string['pluginname'] = 'Ações relativas o Moodle Provas';
$string['exam_actions:addinstance'] = 'Adiciona um novo bloco exam_actions';
$string['exam_actions:myaddinstance'] = 'Adiciona um novo bloco exam_actions à Página Inicial';

$string['title'] = 'Moodle Provas';

$string['remote_courses'] = 'Cursos remotos';
$string['remote_courses_msg'] = 'Abaixo está a relação de cursos das instalações remotos de Moodle ligadados ao Moodle Provas.
    Clique no nome do curso para ter acesso a ele (caso já esteja disponível) ou para disponibilizá-lo no Moodle Provas.';
$string['export_exam'] = 'Exportar prova';
$string['export_exam_desc'] = 'Selecione abaixo as atividades a serem exportadas para o Moodle de Origem';
$string['export'] = 'Exportar';
$string['export_result'] = 'Resultado da exportação';
$string['access_key'] = 'Chave de acesso';
$string['new_access_key'] = 'Nova chave de acesso';
$string['generate_access_key'] = 'Gerar chave de acesso';
$string['release_computer'] = 'Liberar computador';
$string['release_this_computer'] = 'Liberar computador para realizar prova';
$string['monitor_exam'] = 'Monitorar prova';
$string['computer_released'] = 'Computador liberado para realizar prova';

$string['no_remote_courses'] = 'Não foram localizados cursos remotos';
$string['enablecourse'] = 'Disponibiliando curso: \'{$a}\'';
$string['confirmenablecourse'] = 'Você realmente deseja disponibilizar o curso \'{$a}\' no Moodle Provas?';

$string['no_activities_to_export'] = 'Não há atividades a serem exportadas para o Moodle de Origem';
$string['no_selected_activities'] = 'Não foi selecionada nenhuma atividade a ser exportada para o Moodle de Origem';

$string['already_added'] = ' (já disponível)';
$string['no_proctor'] = 'Sem permissão para gerar chaves de acesso';
$string['generating_access_key'] = 'Gerando chave de acesso';
$string['generating_access_key_title'] = 'As chaves de acesso são necessárias para liberar os computadores que serão utilizados pelos estudantes para realizar provas.';

$string['course'] = 'Curso';
$string['course_help'] = 'Curso para o qual a chave de acesso é válida. Somente estudantes inscritos neste curso poderão ter acesso a ele utilizando essa chave.';
$string['select_course'] = '-- Selecione um curso';

$string['access_key_timeout'] = 'Validade da chave de acesso';
$string['access_key_timeout_help'] = 'Após este tempo a chave de acesso perde sua validade, ou seja, não poderá mais ser utilizada para liberar um computador para realizar prova.
    A autenticação do estudante também não será mais possível, mesmo que o computador já tenha sido liberado.';
$string['access_key_unknown'] = 'Chave de acesso desconhecida';
$string['access_key_timedout'] = 'Chave de acesso com validade expirada';

$string['verify_client_host'] = 'Restrigir uso à rede local';
$string['verify_client_host_help'] = 'Verificar se o computador onde será utilizada a chave de acesso está na mesma rede do computador onde a chave foi gerada.';

$string['empty_course'] = 'É necessário selecionar o curso para o qual a chave de acesso é válida';
$string['empty_backup_file'] = 'Falha na geração do backup da atividade: arquivo vazio';
$string['activity_exported'] = 'Atividade foi exportada';

$string['cd_needed'] = 'É necessário utilizar o CD (ou pendrive) de provas para que este computador possa ser liberado para realizar uma prova.';
$string['invalid_cd_version'] = 'A versão do CD (ou pendrive) de provas é inválida ou muito antiga.';
$string['out_of_student_ip_ranges'] = 'Este computador não pode ser utilizado para realizar uma prova pois seu número IP está fora da faixa de IPs permitidos.';
?>
