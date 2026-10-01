# local_missingstudents — Alunos Sumidos

Relatório de retenção por curso que identifica estudantes com períodos relevantes de inatividade e organiza essas
informações em um dashboard para acompanhamento.

## Critério de aluno

O plugin usa a capability `moodle/course:isincompletionreports`, a mesma utilizada pelo Moodle para identificar usuários
acompanhados nos relatórios de conclusão. Assim, o relatório trabalha com o público que efetivamente deve ser monitorado
no curso.

## Critérios de inatividade

- **Acesso ao curso:** usa `{user_lastaccess}.timeaccess` para medir há quanto tempo o estudante não acessa o curso atual.
- **Acesso ao Moodle:** usa `{user}.lastaccess` para identificar quem não entra em nenhuma área do ambiente.

O limite de dias pode ser configurado em **Administração do site → Plugins → Plugins locais → Alunos Sumidos**.

## Dashboard

O relatório apresenta indicadores, distribuição de risco, faixas de ausência, ranking de criticidade e filtros para
facilitar a busca pelos casos que merecem acompanhamento. Os dados também podem ser exportados em CSV para contato,
cruzamento com outros sistemas ou rotinas de retenção.
