# Work-base

A crud project

## Ambiente local com Docker

O Compose sobe o frontend Vite, o backend da branch `Rotas` em FrankenPHP e o MySQL. A instalação do frontend acontece dentro do container e é atualizada quando o `package-lock.json` mudar; não é necessário executar comandos npm no host. Requer Docker com Docker Compose.

```sh
cp docker/php/app.env.example docker/php/app.env
docker compose up --build
```

- API: <http://localhost:8080/api/health>
- Frontend: <http://localhost:5173>
- MySQL no host: `127.0.0.1:3306` (banco/usuário/senha: `loja`)

Os containers compartilham a rede `app`; a API conecta ao banco pelo hostname `mysql:3306`. O frontend usa a URL pública `http://localhost:8080/api`, acessada pelo navegador, e a API libera a origem `http://localhost:5173` via CORS. A configuração da aplicação em `docker/php/app.env` é montada como `/app/.env`, no caminho e formato que o backend já lê. `api/database/schema.sql` e `api/database/seed.sql` são executados na criação inicial do volume do MySQL.

Para usar um cliente SQL no container:

```sh
docker compose exec mysql mysql -uloja -ploja loja
```

Ou enviar um arquivo SQL do host:

```sh
docker compose exec -T mysql mysql --default-character-set=utf8mb4 -uloja -ploja loja < caminho/arquivo.sql
```

Os dados ficam no volume `mysql_data`; as dependências do frontend ficam em `frontend_node_modules`. Os scripts SQL só rodam na primeira criação do banco; `docker compose down -v` apaga os volumes e os dados. A API publica `8080`, o Vite `5173` e o MySQL `3306`, somente no host local. O Vite recebe `VITE_API_URL=http://localhost:8080/api` pelo Compose, e o CORS da API permite `http://localhost:5173`.

Para bancos existentes, execute uma vez `api/database/migrations/001_utf8mb4.sql` para garantir UTF-8 completo e corrigir registros antigos gravados com dupla codificação.
