# local_missingstudents — Alunos Sumidos

Relatório local de retenção por curso. Lista alunos acompanhados pelo Moodle que ultrapassaram um limite de inatividade e mostra um dashboard com indicadores, distribuição de risco, faixas de ausência, ranking de criticidade, filtros e exportação CSV.

## Critério de aluno

O plugin usa `moodle/course:isincompletionreports`, a mesma capability que o Moodle usa para identificar usuários acompanhados em relatórios de conclusão. Por padrão, ela pertence ao papel de estudante.

## Critérios de inatividade

- **Acesso ao curso:** usa `{user_lastaccess}.timeaccess` para o curso atual.
- **Acesso ao Moodle:** usa `{user}.lastaccess` para identificar quem não entra em nenhuma área do Moodle.

O limite padrão é 10 dias e pode ser alterado em Administração do site > Plugins > Plugins locais > Alunos Sumidos.

## Instalação

Copie a pasta `missingstudents` para `local/missingstudents` e conclua a atualização do Moodle.
