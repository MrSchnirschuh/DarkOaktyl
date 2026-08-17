# DarkOaktyl Docker Setup Guide

Quick start for running DarkOaktyl with Docker Compose.

## Requirements

- [Docker](https://docs.docker.com/engine/install/)
- [Docker Compose](https://docs.docker.com/compose/install/)

## Setup

1. Copy the example environment file and adjust the variables:

   ```bash
   cp .env.example .env
   ```

2. Edit `.env`. The most important variables are listed below.
3. Start the containers:

   ```bash
   docker compose up -d
   ```

4. Create the first admin user:

   ```bash
   docker compose exec panel php artisan p:user:make
   ```

## Important Environment Variables

| Variable      | Description                                          |
| -------------- | ---------------------------------------------------- |
| `APP_URL`      | URL the panel is reachable at, including protocol  |
| `APP_TIMEZONE` | Panel timezone                                       |
| `DB_HOST`      | MySQL/MariaDB host                                   |
| `DB_PORT`      | MySQL/MariaDB port                                   |
| `DB_DATABASE`  | Database name                                        |
| `DB_USERNAME`  | Database user                                        |
| `DB_PASSWORD`  | Database password                                    |
| `REDIS_HOST`   | Redis host                                           |
| `REDIS_PORT`   | Redis port                                           |
| `REDIS_PASSWORD` | Redis password (if set)                           |
| `MAIL_DRIVER`  | Mail driver (`smtp`, `mailgun`, `postmark`, etc.)    |
| `MAIL_FROM`    | Sender email address                                 |
| `MAIL_HOST`    | Mail server host                                     |
| `MAIL_PORT`    | Mail server port                                     |
| `MAIL_USERNAME`| Mail username                                        |
| `MAIL_PASSWORD`| Mail password                                        |
| `LE_EMAIL`     | Let’s Encrypt email (required when `APP_URL` uses `https://`) |

For a full list of variables and an example compose file, see `.github/docker/README.md`.
