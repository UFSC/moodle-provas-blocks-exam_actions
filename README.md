blocks-exam_actions
===================

Bloco que apresenta um conjunto de ações relativas à realização de provas.

Moodle Provas
=============

O "Moodle Provas" é uma solução desenvolvida pela
Universidade Federal de Santa Catarina
com financiamenteo do programa Universidade Aberta do Brasil (UAB)
para a realização de provas seguras nos pólos utilizando
o Moodle através da internet.

Além deste plugin, mais dois plugins compõem o pacote do Moodle Provas:

* local-exam_remote: Plugin que cria os webservices necessários no Moodle de origem
* local-exam_authorization : Bloco que trata da autorização de usuários ao ambiente de provas

Foi desenvolvido também um "CD de Provas", derivado do Ubuntu, para
restringir o acesso aos recursos dos computadores utilizados
para realização da provas.

No endereço abaixo você pode acessar um tutorial sobre a
arquitetura do Moodle Provas:

    https://tutoriais.moodle.ufsc.br/provas/arquitetura/

Download
========

Este plugin está disponível no seguinte endereço:

    https://gitlab.setic.ufsc.br/moodle-ufsc/block-exam_actions

Os outros plugins podem ser encontrados em:

    https://gitlab.setic.ufsc.br/moodle-ufsc/local-exam_authorization
    https://gitlab.setic.ufsc.br/moodle-ufsc/local-exam_remote

O código e instruções para gravação do "CD de Provas" podem ser encontrados em:

    https://gitlab.setic.ufsc.br/provas-online/livecd-provas

Instalação
==========

Este plugin deve ser instalado no "Moodle de Provas".

Pós-instalação
==============

Após instalar o módulo, execute as seguinte ações:

* incluir instância do bloco 'exam_actions' na tela inicial do ambiente (Site home)
 * necessário para que esteja disponível a ação de "Liberar computador para realizar prova"
* incluir em config.php a linha:
 * $CFG->defaultblocks_override = ':exam_actions';
* para que seja automaticamente adicionado este bloco em cada curso Moodle que seja criado.
* necessário para que estejam disponíveis diversas ações em nível de curso

Licença
=======

Este código-fonte é distribuído sob licença GNU General Plublic License
Uma cópia desta licença está no arquivo COPYING.txt
Ela também pode ser vista em <http://www.gnu.org/licenses/>.
