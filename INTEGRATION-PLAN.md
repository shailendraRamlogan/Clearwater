# Clearwater Integration Plan — Final

**Date:** 2026-04-24
**Analysis:** 3 rounds of recursive review (R1 → R2 → R3)
**Status:** Ready for implementation

---

## Consolidated Obstacles

### Critical (Must Fix Before Integration)

| # | Issue | Source |
|---|---|---|
| C1 | **No capacity check on booking** — race condition allows overbooking | R1, R3 |
| C2 | **Fake card form** — frontend ignores Stripe `client_secret`, shows "Confirmed" before payment | R1, R2 |
| C3 | **No Stripe webhook** — payment success/failure never recorded, bookings stay `pending` forever | R1 |
| C4 | **Confirmation screen lost on refresh** — no lookup endpoint, booking ref is React state only | R2 |
| C5 | **Admin reports + schedule pages are stubs** — no API calls, buttons do nothing | R2 |
| C6 | **422 validation errors silently swallowed** — axios interceptor discards `errors` object | R2 |

### High (Should Fix)

| # | Issue | Source |
|---|---|---|
| H1 | **No admin auth on frontend** — `/admin/*` routes have zero protection | R2 |
| H2 | **Admin API calls have no auth header** — all admin endpoints return 401 | R1, R2 |
| H3 | **Missing `GET /api/schedules/blocked` endpoint** — can't load current blocked state | R2 |
| H4 | **No `unblockSchedule` in frontend** — backend exists but no caller | R2 |
| H5 | **No double-submit protection** — no idempotency key | R2 |
| H6 | **`is_confirmed`/`needs_confirmation` missing from BookingResource** | R1, R2 |
| H7 | **Confirmation email says "Confirmed" for pending bookings** | R3 |
| H8 | **No `config/cors.php`** — needs explicit origin allowlist for production | R1 |

### Medium (Fix Before Production Launch)

| # | Issue | Source |
|---|---|---|
| M1 | **Gallery images hardcoded** — Unsplash URLs can break, no admin management | R2 |
| M2 | **No `next/image` usage** — missing optimization | R2 |
| M3 | **`APP_DEBUG=true`** — stack traces exposed | R2 |
| M4 | **Email goes to `log` driver** — no real emails | R2 |
| M5 | **No queue worker running** — async emails never execute | R2 |
| M6 | **Pricing duplicated** — hardcoded in frontend store AND backend controller | R1 |
| M7 | **Guest count not validated against ticket count** | R3 |
| M8 | **No booking status state machine** — any transition allowed | R3 |
| M9 | **Export CSV button does nothing** | R2 |

### Confirmed Non-Issues ✅

| Item | Source | Why Not An Issue |
|---|---|---|
| CSRF on public booking | R3 | `statefulApi()` only activates for same-domain cookies; frontend uses JSON + Bearer tokens |
| Phone format | R3 | `+1242XXX` format works fine, `max:30` is generous |
| SSR hydration | R2 | All pages are `"use client"` — no SSR, no hydration mismatch |
| Package dependencies | R3 | No vulnerable or critically outdated deps |
| Session/cookie handling | R1 | API uses Bearer tokens, not sessions |
| Deployment architecture | R1 | Separate subdomains already work |

---

## Implementation Phases

### Phase 1 — Backend Foundation (no frontend changes)

**1A. Data Integrity**
- [ ] Add capacity check with `lockForUpdate()` in `BookingController@store()`
- [ ] Return 409 with clear message if over capacity
- [ ] Add `is_confirmed`, `needs_confirmation` to `BookingResource`
- [ ] Validate `guests.*` count matches `adult_count + child_count`
- [ ] Add booking status enum/cast to `Booking` model

**1B. Security**
- [ ] Remove hardcoded admin token fallback — require env var
- [ ] Create `config/cors.php` with explicit origins
- [ ] Add `throttle:60,1` to public API routes
- [ ] Set `APP_DEBUG=false` for production

**1C. Stripe Webhook**
- [ ] Create `POST /api/stripe/webhook` with signature verification
- [ ] Handle `payment_intent.succeeded` → `Booking.status = 'confirmed'`
- [ ] Handle `payment_intent.payment_failed` → `Booking.status = 'failed'`
- [ ] Add `STRIPE_WEBHOOK_SECRET` to env

**1D. Missing Endpoints**
- [ ] `GET /api/bookings/lookup?email=x&ref=y` — public, rate-limited
- [ ] `GET /api/schedules/blocked?month=YYYY-MM` — list blocked dates
- [ ] `GET /api/pricing` — return current prices (single source of truth)

**1E. Email Fix**
- [ ] Change email subject/body from "Booking Confirmed" to "Booking Received"
- [ ] Switch from `log` driver to Resend in production

### Phase 2 — Frontend Integration

**2A. Stripe Integration**
- [ ] Install `@stripe/stripe-js` and `@stripe/react-stripe-js`
- [ ] Replace fake card form with Stripe Elements (CardElement or PaymentElement)
- [ ] After booking response, call `stripe.confirmCardPayment(client_secret)`
- [ ] Show "Payment Processing..." state
- [ ] Handle payment success → show confirmation
- [ ] Handle payment failure → show error, offer retry

**2B. Booking Confirmation**
- [ ] Create `/book/confirmation?ref=CBB-XXX` route
- [ ] On successful booking, redirect to confirmation page (not in-page state)
- [ ] Confirmation page fetches booking from `GET /api/bookings/lookup`
- [ ] Handle page refresh gracefully

**2C. Error Handling**
- [ ] Update axios interceptor to pass through `errors` object from 422 responses
- [ ] Show field-level validation errors on booking form
- [ ] Add specific Stripe error messages (card declined, insufficient funds, etc.)
- [ ] Add network error handling with retry prompt

**2D. Admin Auth**
- [ ] Add simple admin login page (token-based)
- [ ] Store token in localStorage
- [ ] Add token to axios headers for admin API calls
- [ ] Add route guard for `/admin/*` pages
- [ ] Add `NEXT_PUBLIC_ADMIN_TOKEN` to env (or login flow)

**2E. Admin Pages Wiring**
- [ ] Wire dashboard to `getDailyReport()` with loading state
- [ ] Wire bookings page — add pagination, remove stub Export CSV
- [ ] Wire reports page — connect "Generate Report" to `getDailyReport()`, connect PDF download
- [ ] Wire schedule page — load blocked dates from API, call block/unblock on toggle
- [ ] Add `unblockSchedule()` to `booking-service.ts`

### Phase 3 — Polish

- [ ] Centralize pricing — fetch from `GET /api/pricing` instead of hardcoding
- [ ] Gallery: move images to config or backend endpoint
- [ ] Add `next/image` with `remotePatterns` for Unsplash
- [ ] Video background fallback
- [ ] Double-submit protection (idempotency key or frontend debounce)
- [ ] Phone format validation (regex on backend)
- [ ] Start queue worker (`php artisan queue:work`) for async emails
- [ ] Production build (`next build` + `next start`)

---

## Deployment Checklist

### Backend
- [ ] `APP_ENV=production`, `APP_DEBUG=false`
- [ ] `STRIPE_SECRET_KEY` set
- [ ] `STRIPE_WEBHOOK_SECRET` set
- [ ] `RESEND_API_KEY` set
- [ ] `ADMIN_TOKEN` set (remove hardcoded fallback)
- [ ] `SESSION_DOMAIN=.ourea.tech`
- [ ] `QUEUE_CONNECTION=database` + queue worker running
- [ ] `config/cors.php` created with allowed origins
- [ ] Migrations run, seeders run

### Frontend
- [ ] `NEXT_PUBLIC_API_URL=https://clearwater-panel.ourea.tech/api`
- [ ] `NEXT_PUBLIC_STRIPE_PUBLISHABLE_KEY` set
- [ ] `NEXT_PUBLIC_APP_URL=https://water.ourea.tech`
- [ ] `next build` succeeds
- [ ] `next start` replaces `next dev`

### Nginx
- [ ] `water.ourea.tech` → Next.js port 3000 (SSL)
- [ ] `clearwater-panel.ourea.tech` → Laravel API (SSL)
- [ ] Both have valid SSL certs

---

## Backup Points
- **Git:** `ab164e1` (pre-integration baseline)
- **File backup:** `/root/backups/clearwater-20260424-040157.tar.gz` (210MB)
