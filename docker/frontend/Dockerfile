FROM node:22-bookworm-slim

WORKDIR /app

COPY frontend/package.json frontend/package-lock.json frontend/.npmrc ./
RUN npm ci \
    && sha256sum package-lock.json | cut -d ' ' -f 1 > node_modules/.docker-lock-hash

COPY frontend/ ./
COPY docker/frontend/entrypoint.sh /usr/local/bin/frontend-entrypoint

EXPOSE 5173

ENTRYPOINT ["sh", "/usr/local/bin/frontend-entrypoint"]
CMD ["npm", "run", "dev", "--", "--host", "0.0.0.0"]
