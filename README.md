# DarkOaktyl (formerly Jexpanel, Pterodactyl)

[![Latest Release](https://img.shields.io/github/v/release/DarkOaktyl/DarkOaktyl?style=for-the-badge)](https://github.com/DarkOaktyl/DarkOaktyl/releases)
[![Stars](https://img.shields.io/github/stars/DarkOaktyl/DarkOaktyl?style=for-the-badge)](https://github.com/DarkOaktyl/DarkOaktyl/stargazers)
[![Forks](https://img.shields.io/github/forks/DarkOaktyl/DarkOaktyl?style=for-the-badge)](https://github.com/DarkOaktyl/DarkOaktyl/network)

**Game panel, billing, email, theming and auto-scaling — fast, secure and customizable.**

---

## Overview

DarkOaktyl is a modern, high-performance **game server management panel** forked from **Jexpanel** (itself based on **Pterodactyl Panel**), offering enhanced security, detailed customization, an email system for coupons and notifications, integrated billing (Stripe + PayPal), and optional **Auto-Scaling** for server resources.

## Tech Stack

- PHP 8.4
- Laravel
- React
- TypeScript
- Docker

## Useful Links

- Website & Documentation: [DarkOak.eu](https://DarkOak.eu)
- GitHub Repository: [DarkOaktyl/DarkOaktyl](https://github.com/DarkOaktyl/DarkOaktyl)
- Discord: [DarkOaktyl Discord](https://discord.gg/ecCVKteUBE)

## Getting Started

Docker Compose is the recommended deployment path. See [`SETUP_GUIDE.md`](./SETUP_GUIDE.md) for a quick start and [`.github/docker/README.md`](./.github/docker/README.md) for the full environment reference and compose example.

After the containers are up, create the first admin user:

```bash
docker compose exec panel php artisan p:user:make
```

## Development

Use the `Makefile` for local tasks:

```bash
make test-unit        # Unit-Tests mit SQLite (kein MySQL nötig)
make test-integration # Integration-Tests mit MySQL
make phpstan          # Statische Analyse
make cs               # Coding-Style-Check
```

## Contribution

Contributions are welcome! Please see [`CONTRIBUTING.md`](./CONTRIBUTING.md) and join discussions via the [DarkOaktyl Discord](https://discord.gg/ecCVKteUBE) or GitHub issues.
