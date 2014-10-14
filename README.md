Moodle Provas
=============

Módulo de um conjunto de ações relativas à realização de provas

DOWNLOAD
========

O plugin está disponível no seguinte endereço:

    https://github.com/???


PÓS-INSTALAÇÃO
==============

Após instalar o módulo, execute as seguinte ações:

* incluir instância do bloco 'exam_actions' na tela inicial do ambiente (Site home)
** necessário para que esteja disponível a ação de "Liberar computador para realizar prova"
* incluir em config.php a linha:
** $CFG->defaultblocks_override = ':exam_actions';
* para que seja automaticamente adicionado este bloco em cada curso Moodle que seja criado.
* necessário para que estejam disponíveis diversas ações em nível de curso
