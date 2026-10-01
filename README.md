# Work-base
A crud project

## Ambiente local com Docker

O Compose sobe o backend da branch `Rotas` em FrankenPHP e o MySQL. Requer Docker com Docker Compose.

```sh
cp docker/php/app.env.example docker/php/app.env
docker compose up --build
```

- API: <http://localhost:8080/api/health>
- MySQL no host: `127.0.0.1:3306` (banco/usuário/senha: `loja`)

A API usa o hostname `mysql:3306` na rede interna do Compose. A configuração da aplicação em `docker/php/app.env` é montada como `/app/.env`, no caminho e formato que o backend já lê. `api/database/schema.sql` e `api/database/seed.sql` são executados na criação inicial do volume do MySQL.

Para usar um cliente SQL no container:

```sh
docker compose exec mysql mysql -uloja -ploja loja
```

Ou enviar um arquivo SQL do host:

```sh
docker compose exec -T mysql mysql -uloja -ploja loja < caminho/arquivo.sql
```

Os dados ficam no volume `mysql_data`. Os scripts de inicialização só rodam na primeira criação; `docker compose down -v` apaga o volume e os dados. A API publica `8080` e o MySQL publica `3306` somente no host local. Para integrar o Vite depois, anexe o serviço à rede `app` e configure o proxy de desenvolvimento para `http://php`; o CORS já permite `http://localhost:5173`.
