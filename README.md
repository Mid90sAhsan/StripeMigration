# 🔄 Stripe Subscription Migration Script

This PHP script migrates subscriptions from an **old Stripe account** to a **new Stripe account**, including key metadata and billing logic.

It safely re-creates subscriptions on the new account and cancels the original ones — without triggering refunds or additional charges.

---

## 🚀 Features

- Migrates subscriptions with status:
  - `trialing` (retains original trial end date)
  - `active` (creates new sub with trial until next renewal)
  - `past_due` (adds 1-day trial to give breathing room)
- Transfers subscriptions to new Stripe using **customer email matching**
- Copies the **price ID** from the original subscription
- Attaches `old_subscription_id` in **metadata**
- Cancels the original subscription on the old account
- Skips any subscriptions with unsupported statuses

---

## ⚙️ Requirements

- PHP 7.4+
- Composer
- Stripe PHP SDK

Install dependencies:

```bash
composer install
