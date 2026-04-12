# DarkOaktyl TODO

## ✅ Done
- [x] Lock down billing routes when module disabled
- [x] jGuard anti-spam middleware
- [x] Frontend API cleanup (errorHandler + CRUD factory)
- [x] Phase 1 Security Fixes (Stripe webhook verification, API token encryption, Daemon rate limiting)
- [x] PHP 8.4 Upgrade
- [x] Database Migrations (discount codes, renewal dates, server_id in orders, user_state fixes)
- [x] Billing Refactor (transaction_id for Jexactyl compatibility)
- [x] Jexactyl Merge (81 commits integrated)
- [x] 2FA Enforcement Levels (NONE, ADMIN, ALL)
- [x] Alerts System
- [x] Webhooks System (model + controller + admin UI)
- [x] OAuth2 (Discord + Google login)
- [x] PHP 8.4 Deprecation Fixes (nullable parameters)

## 🔲 Open
- [ ] PHPStan Level 5 cleanup (Copilot running)
- [ ] Frontend tests (Jest/Vitest)
- [ ] Integration tests with database
- [ ] Docker deployment testing
- [ ] Documentation update (README, SETUP_GUIDE)
- [ ] ESLint/Prettier frontend cleanup
- [ ] CI/CD workflows for main branch

## 🚀 New Feature Ideas (aus deiner Liste vom 12.04.)

### ✅ Bereits vorhanden:
- [x] **#2: Resource-Monitoring Dashboard** - Echtzeit CPU/RAM/Disk Graphen (existiert: getMetrics API + OverviewContainer)
- [x] **#5: Scheduled Tasks** - Cron-Job Management im Panel (existiert: schedules/tasks DB-Tabellen)

### Priorisiert (von dir bestätigt):
- [ ] **#1: Push-Notifications** - Browser-Push bei Server-Events
- [ ] **#3: Auto-Scaling** - Server automatisch skalieren bei Auslastung
- [ ] **#4: Team/Org + Split-Kosten** - Server-Verwaltung für Teams + Kostenaufteilung
- [ ] **#6: API Key Scopes** - Feingranulare API-Berechtigungen
- [ ] **#7: One-Click Apps** - Vorgefertigte Server-Templates (Minecraft, Valheim, etc.)
- [ ] **#8: Usage-Based Billing** - Pay-per-hour statt monatlich
- [ ] **#9: Multi-Region** - Server in verschiedenen Rechenzentren

### 🆕 Neues Feature (von dir gerade genannt):
- [ ] **#11: Networking Daemon** - Neuer Daemon neben Wings für Firewall, VPN, SSL, Proxy
  - Firewall-Management (firewalld oder ufw wählbar)
  - VPN-Setup (WireGuard, OpenVPN, etc.)
  - SSL/Zertifikate automatisch
  - Proxy-Konfiguration (nginx, etc.)
  - Multi-Node Setup: Panel VPS + Proxy VPS + Nodes zuhause
  - Automatische Verbindung aller Komponenten

### Übersprungen:
- [ ] ~~**#10: PWA/Mobile App**~~ - (du: "nein")