# ERP Monorepo

This repository provides the starter layout for a multi-tenant ERP SaaS:

- `backend/` - Laravel 11 API
- `frontend/` - Vue 3 + TypeScript + Vite
- `mobile/` - Flutter 3.x
- `docs/` - architecture notes and product docs

## Prerequisites

- Docker and Docker Compose
- `make`

## Start the stack

1. Start all services:

   ```bash
   make up
   ```

2. Open the main services:

   - Backend API: `http://localhost:8000`
   - Frontend dev server: `http://localhost:3000`
   - MailHog UI: `http://localhost:8025`
   - MinIO console: `http://localhost:9001`
   - Elasticsearch: `http://localhost:9200`

3. Stop the stack:

   ```bash
   make down
   ```

## Useful commands

- `make backend` opens a shell in the backend container.
- `make logs` streams logs from all services.

## Data volumes

The stack uses named Docker volumes for PostgreSQL, Redis, MinIO, and Elasticsearch data so local state survives container restarts.
