# FBControl - Politica de e-mail compartilhado por usuarios

## Classificacao

O FBControl permite tecnicamente que mais de um usuario operacional compartilhe o mesmo
endereco de e-mail. Essa pratica e classificada como **risco aceito temporariamente**, com
controles obrigatorios. Ela nao equivale a uma identidade individual forte.

A coluna `usuarios.email` permanece nao unica para preservar a operacao atual (ver
`sql/migration_v2_1_users_email_non_unique.sql`).

## Funcionamento atual

`UserModel::authenticateByEmailAndPassword()` busca todos os usuarios ativos com o e-mail:

- Se exatamente uma senha confere, a sessao recebe o `usuario_id` correspondente.
- Se mais de uma senha confere, o login e recusado como `auth_login_ambiguous`.
- Se nenhuma senha confere, o login e recusado como `auth_login_failed`.

O login bem-sucedido, turnos, acessos, reservas e exports ficam associados ao `usuario_id`.
Eventos anteriores a autenticacao, como falha ou ambiguidade, nao possuem usuario conhecido e
devem registrar `usuario_id = NULL`.

## Limite de rastreabilidade

O `usuario_id` demonstra qual credencial foi aceita, mas nao prova qual pessoa estava fisicamente
com o dispositivo. Senhas compartilhadas, anotadas ou conhecidas por terceiros permitem
atribuicao incorreta. Portanto, a trilha e util operacionalmente, mas nao oferece nao repudio.

`UserModel::emailPasswordExists()` impede cadastrar a mesma combinacao de e-mail e senha, mas
nao consegue impedir que uma pessoa revele sua senha a outra.

## Controles obrigatorios

1. Cada pessoa deve possuir cadastro e senha proprios.
2. Pessoas com o mesmo e-mail devem obrigatoriamente usar senhas diferentes.
3. Senhas nao podem ser compartilhadas, anotadas junto ao equipamento ou reutilizadas.
4. Usuarios desligados ou transferidos devem ser desativados imediatamente.
5. Perfis e vinculos de restaurante/operacao devem ser revisados periodicamente.
6. Ocorrencias `auth_login_ambiguous` devem gerar revisao das credenciais envolvidas.

## Evolucao recomendada

O caminho recomendado e separar identidade de contato:

- Criar um identificador individual e unico para login, como usuario, matricula ou codigo.
- Manter o e-mail funcional compartilhado apenas como contato operacional.

Essa evolucao nao bloqueia a operacao atual, mas permanece registrada como melhoria de seguranca
e governanca, especialmente antes de ampliar o numero de usuarios ou o uso fora da rede interna.
