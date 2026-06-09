COMPOSE ?= docker-compose

.PHONY: up down backend logs

up:
	$(COMPOSE) up -d

down:
	$(COMPOSE) down

backend:
	$(COMPOSE) exec backend bash

logs:
	$(COMPOSE) logs -f
