# JexPanel Feature Comparison & Merge Analysis

## Executive Summary

**DarkOaktyl is already fully synchronized with JexPanel** — the latest JexPanel commit
(`3124260a1`, 2026-03-29) was merged into DarkOaktyl via commit `e13c1bcaf` on 2026-04-12.
Since that merge, JexPanel has produced **zero new commits** — the upstream is dormant.
Meanwhile, DarkOaktyl has added **15 substantial commits** of its own Phase 3 features.

**Conclusion: No merge action is needed. DarkOaktyl is ahead of JexPanel.**

---

## Timeline

| Date        | Event                                                |
|-------------|------------------------------------------------------|
| 2026-03-29  | JexPanel final commit (`3124260a1`)                  |
| 2026-04-12  | DarkOaktyl merges 81 JexPanel commits (`e13c1bcaf`) |
| 2026-04~05  | DarkOaktyl adds Phase 3 features (15 commits)       |
| 2026-05-29  | This analysis performed                              |

---

## JexPanel State (at `3124260a1`)

Total commits: 5,460
Key features present:
- Integrated Stripe billing system
- Tickets / support system
- AI assistant integration (admin AI settings)
- Alert system (global site alerts)
- Webhooks (Discord webhook events)
- Discount codes for billing
- Server presets (one-click create)
- Theme customization (color palette via admin panel)
- OAuth2 modules (Discord SSO, Google SSO with admin toggles)
- jGuard anti-spam middleware
- Onboarding system
- Links management (custom navbar links)
- Mode settings (light/dark/auto)
- Server groups
- SSH key management
- Docker support
- PHP 8.4 compatibility
- Admin role management

---

## What DarkOaktyl Has That JexPanel Does NOT (DarkOaktyl's Own Phase 3 Work)

These are features DarkOaktyl has added AFTER the JexPanel merge:

### 1. Push Notifications
   - Browser Push via Service Workers (`resources/scripts/sw-push.ts`)
   - Push subscription API endpoints
   - Notification settings UI (`resources/scripts/components/account/notifications/NotificationSettings.tsx`)
   - PushNotificationService backend

### 2. Auto-Scaling
   - AutoScalingController (client API)
   - AutoScalingPanel frontend component
   - AutoScalingSettings server settings UI
   - AutoScalingCheckJob, AutoScalingEvaluationJob (scheduled)
   - AutoScalingHistory, AutoScalingRule models
   - Server scaling based on CPU/Memory thresholds

### 3. Usage-Based Billing
   - Server hourly rates (`ServerHourlyRate` model)
   - Server runtime tracking (`ServerRuntimeTracking` model)
   - Credit balance system (`CreditBalance`, `CreditTransaction` models)
   - Resource pricing (`ResourcePrice` model + admin CRUD UI)
   - Billing quotes (`QuoteController`, `BillingPricingService`)
   - Billing terms (monthly/yearly pricing tiers)
   - BillingRecords for usage tracking
   - `UsageBillingService` backend service
   - Frontend: `CreditBalance.tsx`, `ResourcePriceContainer.tsx`, `BillingTermContainer.tsx`,
     `PricingConfigurationTable.tsx`

### 4. Organizations (Teams + Cost Splitting)
   - Organization model, OrganizationMember, OrganizationInvitation
   - OrganizationService, OrganizationInvitationService
   - Full REST API for organizations
   - `OrganizationList.tsx` frontend

### 5. Multi-Region Support
   - Region model and RegionService
   - Region selection during server creation (`RegionSelector.tsx`)
   - Region API routes (client + admin)

### 6. One-Click Apps / Server Templates
   - ServerTemplate and ServerTemplateCategory models
   - ServerTemplateService, TemplateDeployService
   - Template gallery frontend (`TemplateGallery.tsx`)
   - Template store (`TemplateStore.tsx`)
   - Template deployment workflow

### 7. Email System (Full)
   - EmailTemplate, EmailTheme, EmailTrigger models
   - Full admin panel UI: EmailRouter, TemplatesContainer, ThemesContainer, TriggersContainer
   - EmailDispatchService, EmailTemplateRenderer, EmailTriggerProcessor
   - EmailEventRegistry, EmailTriggerEventSubscriber
   - Scheduled email dispatching (`DispatchEmailTriggersCommand`)

### 8. Advanced Coupon System
   - Full coupon model with redemption tracking (`Coupon`, `CouponRedemption` models)
   - Admin coupon CRUD (`CouponContainer.tsx`, `CouponForm.tsx`, `CouponTable.tsx`)
   - Email-based coupon delivery

### 9. Theme Designer System
   - Theme palette CRUD API (`updatePalette.ts`, `getPalette.ts`, `deleteColor.ts`)
   - `ThemeDesigner.tsx` interactive theme editor
   - Logo settings UI
   - Email preview in theme editor
   - Presets system for themes
   - CSS variable-based theming (`ThemeVars.tsx`)
   - `AppearanceSync.tsx` for user-preference sync
   - Dark/light/system mode with automatic activation

### 10. Security Hardening
   - SecurityHeaders middleware
   - ApiRateLimit middleware
   - CheckApiKeyScope middleware (fine-grained API key scopes)
   - Stripe webhook signature verification
   - Improved ServerGroupController validation

### 11. Ticket System Extensions
   - TicketFilters, TicketList, TicketStats components
   - Ticket history tracking
   - Extended ticket admin panel

### 12. API Key Scopes
   - `ApiKeyScope` model
   - `UpdateApiKeyScopesRequest` validation
   - `ApiKeyScopes.tsx` frontend component
   - Fine-grained API permission management

### 13. Vite Bundle Optimization
   - Code splitting
   - Gzip/Brotli compression
   - Bundle analysis setup

### 14. Additional Admin Panel Pages
   - PayPal setup guide
   - Stripe setup guide refactored
   - SetupLink component for generic payment links

---

## What JexPanel Has That DarkOaktyl Does NOT

This list is **extremely short** because DarkOaktyl has everything JexPanel has:

1. **`resources/scripts/state/everest.ts`** — JexPanel's state store. DarkOaktyl uses
   `resources/scripts/state/darkoak.ts` instead. They are structurally identical except
   DarkOak's version has an additional `emails` section for email defaults.

2. **`resources/scripts/assets/images/pterodactyl.svg`** — JexPanel's logo SVG.
   DarkOaktyl uses its own `DarkOaktyl.svg`.

3. **`app/Exceptions/PterodactylException.php`** — JexPanel's base exception class.
   DarkOaktyl uses `DarkOaktylException.php`.

4. **`app/Extensions/League/Fractal/Serializers/PterodactylSerializer.php`** — JexPanel's
   serializer. DarkOaktyl uses `DarkOaktylSerializer.php`.

5. **`app/Http/ViewComposers/EverestComposer.php`** — JexPanel's view composer.
   DarkOaktyl uses `DarkOakComposer.php`.

6. **`config/everest.php`** — JexPanel's main config. DarkOaktyl uses `config/darkoak.php`.

All of these are **identity/rebranding differences only**, not missing features.

---

## Shared Files Analysis

~259 frontend files and ~615 backend files differ between the repos, but this is
**expected divergence** — DarkOaktyl has made many modifications on top of the
shared base. The differences fall into categories:

- DarkOaktyl brand naming (DarkOaktyl/everest/darkoak replacements)
- DarkOaktyl added features (see above)
- JexPanel's original code that DarkOaktyl still shares

---

## Merge Strategy Assessment

### Approach 1: Direct Git Merge — NOT POSSIBLE
- DarkOaktyl's `e13c1bcaf` already consumed JexPanel's latest HEAD
- No new JexPanel commits exist to merge
- Any future upstream commits would need careful conflict resolution

### Approach 2: Cherry-Pick Individual Features — NOT NEEDED
- DarkOaktyl already has all JexPanel features (plus many more)
- Nothing to cherry-pick

### Approach 3: Manual Feature Implementation — COMPLETE
- The `feat/jexpanel-sync` branch was created but is essentially empty
- All "missing" items are identity differences, not functional gaps

---

## Recommendations

1. **No merge action needed.** Close the `feat/jexpanel-sync` branch without merging.
2. **Monitor JexPanel upstream** for any future commits.
3. **If JexPanel resumes development**, evaluate new commits individually.
4. **Continue DarkOaktyl development** — DarkOaktyl is now the leading fork.

---

## Verification Checklist

- [x] JexPanel repo cloned to /home/pandi/jexpanel
- [x] Both repos compared at the frontend (resources/scripts/)
- [x] Both repos compared at the backend (app/)
- [x] Both repos compared at the config level
- [x] Both repos compared at the database/migration level
- [x] Commit history analyzed
- [x] DarkOaktyl features documented
- [x] JexPanel-only features documented (5 identity files only)
- [x] Branch `feat/jexpanel-sync` created

---

*Analysis performed: 2026-05-29*
*JexPanel version: commit 3124260a1 (2026-03-29)*
*DarkOaktyl version: commit 90b7277ae (2026-05-29)*
