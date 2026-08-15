# DarkOaktyl (formerly Jexpanel, Pterodactyl)

[![Latest Release](https://img.shields.io/github/v/release/DarkOaktyl/DarkOaktyl?style=for-the-badge)](https://github.com/DarkOaktyl/DarkOaktyl/releases)
[![Stars](https://img.shields.io/github/stars/DarkOaktyl/DarkOaktyl?style=for-the-badge)](https://github.com/DarkOaktyl/DarkOaktyl/stargazers)
[![Forks](https://img.shields.io/github/forks/DarkOaktyl/DarkOaktyl?style=for-the-badge)](https://github.com/DarkOaktyl/DarkOaktyl/network)

**Game panel, billing, email, theming and auto-scaling — fast, secure and customizable.**

---

## Overview

DarkOaktyl is a modern, high-performance **game server management panel** built on **Jexpanel** and **Pterodactyl Panel**, offering enhanced security, detailed customization, an email system for coupons and notifications, integrated billing (Stripe + PayPal), and optional **Auto-Scaling** for server resources.

## Features

- Advanced authentication and security setups, including 2FA enforcement
- Highly customizable themes with light, dark and system mode, plus automatic activation on a date
- Integrated billing system (Stripe + PayPal) with coupons and email integration
- Email support with timed sending
- **Auto-Scaling** — automatically scale server memory up or down based on CPU, memory and disk thresholds, with push notifications
- **JexpanelAI** — optional Gemini-powered assistant for server error debugging (configurable in admin settings)
- Clean, user-friendly administrative interface
- Built with PHP, Laravel, TypeScript, React and Docker
- Fully open-source and community-driven

## Useful Links

- Website & Documentation: [DarkOak.eu](https://DarkOak.eu)
- GitHub Repository: [DarkOaktyl/DarkOaktyl](https://github.com/DarkOaktyl/DarkOaktyl)
- Discord: [discord.com/jexpanel](https://discord.gg/qttGR4Z5Pk) (Jexactyl / Jexpanel) — do not ask for support
- Discord: [discord.com/just-calls](https://discord.gg/ecCVKteUBE) (DarkOaktyl) — ask here for support

## Getting Started

Docker Compose is the recommended deployment path. See `.github/docker/README.md` for environment variables and the compose example.

After the containers are up, create the first admin user:

```bash
docker compose exec panel php artisan p:user:make
```

## Contribution

Contributions are welcome! Please see `CONTRIBUTING.md` and join discussions via Discord or GitHub issues.
