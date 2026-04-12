#!/bin/bash
cd /root/.openclaw/workspace/DarkOaktyl

# Add all new files
git add -A

# Commit in order
# Feature 1: Push Notifications
git commit -m "feat: push-notifications

- Add PushNotificationService for VAPID-based browser push
- Add PushSubscriptionController with CRUD endpoints
- Add ServerEventListener for automated notifications
- Add service worker (sw.js) for handling push events
- Add frontend component PushNotifications.tsx
- Add migration for push_subscriptions table

Features:
- VAPID authentication for secure push
- Event-based subscription preferences
- Real-time notifications for server events
- Auto-cleanup of stale subscriptions"

# Feature 2: Auto-Scaling
git commit -m "feat: auto-scaling

- Add AutoScalingService with threshold-based scaling
- Add AutoScalingController for API endpoints
- Add AutoScalingEvaluationJob for periodic checks
- Add frontend component AutoScalingPanel.tsx
- Add migrations for auto_scaling_rules and auto_scaling_history

Features:
- CPU/Memory/Disk threshold monitoring
- Configurable scale up/down steps
- Cooldown periods to prevent flapping
- Complete scaling history tracking
- Manual evaluation trigger"

# Feature 3: Organizations
git commit -m "feat: organizations

- Add OrganizationService for team management
- Add OrganizationController with full CRUD
- Add frontend component OrganizationList.tsx
- Add migrations for organizations, members, invitations

Features:
- Create and manage organizations
- Role-based access (owner/admin/member)
- Email invitations with token-based acceptance
- Split cost billing support
- Organization-owned servers"

# Feature 4: Usage-Based Billing
git commit -m "feat: usage-based-billing

- Add UsageBillingService for hourly billing
- Add BillingController for API endpoints
- Add BillingProcessingJob for automated billing
- Add frontend component CreditBalance.tsx
- Add migrations for credit_balances, transactions, billing_records

Features:
- Hourly resource-based billing
- Credit balance management
- Low balance warnings
- Usage summaries and reports
- Automated billing via scheduled jobs"

# Feature 5: One-Click Apps
git commit -m "feat: one-click-apps

- Add ServerTemplateService for template deployment
- Add ServerTemplateController for API endpoints
- Add frontend component TemplateGallery.tsx

Features:
- Pre-configured server templates
- Category-based organization
- One-click deployment
- Featured templates
- Search and filter functionality"

# Feature 6: Multi-Region
git commit -m "feat: multi-region

- Add RegionService for region management
- Add RegionController for API endpoints

Features:
- Multiple geographic regions
- Region-based node assignment
- Latency estimation
- Auto-selection based on capacity
- Region statistics and monitoring"

echo "All features committed successfully!"
