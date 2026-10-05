# Work-base

A crud project

## Ambiente local com Docker

O Compose sobe o frontend compilado e servido por Nginx, o backend da branch `Rotas` em FrankenPHP e o MySQL. Node/npm/Vite são usados somente na etapa de build da imagem do frontend e não ficam na imagem final. Requer Docker com Docker Compose.

```sh
cp docker/php/app.env.example docker/php/app.env
cp api/.env.example api/.env
docker compose up -d --build
```

- API: <http://localhost:8080/api/health>
- Frontend: <http://localhost:8081>
- MySQL no host: `127.0.0.1:3306` (banco/usuário/senha: `loja`)

Usuário administrador de desenvolvimento: `admin@livraria.com` / `senha123`. Essas credenciais são públicas neste projeto de exemplo e não devem ser usadas em produção.

Os containers compartilham a rede `app`; a API conecta ao banco pelo hostname `mysql:3306`. O Nginx serve o frontend em `http://localhost:8081` e encaminha `/api` e `/uploads` para o PHP pela rede interna. A configuração da aplicação em `docker/php/app.env` é montada como `/app/.env`, no caminho e formato que o backend já lê. `api/database/schema.sql` e `api/database/seed.sql` são executados na criação inicial do volume do MySQL.

Para usar um cliente SQL no container:

```sh
docker compose exec mysql mysql -uloja -ploja loja
```

Ou enviar um arquivo SQL do host:

```sh
docker compose exec -T mysql mysql --default-character-set=utf8mb4 -uloja -ploja loja < caminho/arquivo.sql
```

Os dados ficam no volume `mysql_data`. Os scripts SQL só rodam na primeira criação do banco; `docker compose down -v` apaga os volumes e os dados. A API publica `8080`, o Nginx `8081` e o MySQL `3306`, somente no host local. A API também permanece acessível diretamente em `http://localhost:8080` para diagnóstico.

Para bancos existentes, execute uma vez `api/database/migrations/001_utf8mb4.sql` para garantir UTF-8 completo e corrigir registros antigos gravados com dupla codificação.
