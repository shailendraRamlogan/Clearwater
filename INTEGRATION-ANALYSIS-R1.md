# Clearwater Integration Analysis (R1)

**Date:** 2026-04-24  
**Scope:** Frontend ↔ Backend integration planning  
**Backend:** Laravel 12 + Filament v3 + PostgreSQL  
**Frontend:** Next.js 14 + Zustand + Tailwind

---

## 1. API Contract Analysis

### 1.1 Backend Routes

#### `routes/api.php` (prefix: `/api`)

| Method | Path | Auth | Description |
|--------|------|------|-------------|
| GET | `/api/availability?date=YYYY-MM-DD` | None | List available time slots for a date |
| POST | `/api/bookings` | None | Create a new booking (returns Stripe client_secret) |
| GET | `/api/bookings?date=YYYY-MM-DD` | `auth.admin` (Bearer token) | List bookings, optionally filtered by date |
| GET | `/api/reports/daily?date=YYYY-MM-DD` | `auth.admin` | Daily summary report |
| POST | `/api/schedules/block` | `auth.admin` | Block a time slot / all slots for a date |
| POST | `/api/schedules/unblock` | `auth.admin` | Unblock a time slot / all slots for a date |
| GET | `/api/reports/schedule-pdf?date=YYYY-MM-DD` | `auth.admin` | Download schedule PDF |

#### `routes/web.php`

| Method | Path | Auth | Description |
|--------|------|------|-------------|
| GET | `/invoices/{booking}/pdf` | Filament session | Download invoice PDF |
| POST | `/downloadPassengerManifest` | Filament session | Download passenger manifest |

### 1.2 Request/Response Shapes

**GET `/api/availability`**
- Request: `?date=YYYY-MM-DD` (validated: required, date, Y-m-d format)
- Response: `{ "slots": TimeSlotResource[] }`

**TimeSlotResource:**
```json
{ "id": "uuid", "start_time": "HH:mm", "end_time": "HH:mm", "boat_id": "string", "boat_name": "string", "remaining_capacity": 0, "max_capacity": 10, "is_blocked": false }
```

**POST `/api/bookings`**
- Request:
```json
{
  "tour_date": "YYYY-MM-DD", "time_slot_id": "uuid", "adult_count": 1, "child_count": 0,
  "package_upgrade": false, "special_occasion": false, "special_comment": "",
  "guest": { "first_name": "", "last_name": "", "email": "", "phone": "" },
  "guests": [{ "first_name": "", "last_name": "", "email": "" }]
}
```
- Response (201):
```json
{
  "booking": BookingResource,
  "payment": { "client_secret": "pi_xxx_secret_xxx", "stripe_intent_id": "pi_xxx" } | null
}
```

**BookingResource:**
```json
{
  "id": "CBB-20260424-XXXX", "tour_date": "YYYY-MM-DD", "time_slot": TimeSlotResource,
  "guest": { "first_name": "", "last_name": "", "email": "", "phone": "" },
  "items": BookingItemResource[], "package_upgrade": false, "special_occasion": false,
  "special_comment": "", "total_price": 200.00, "status": "pending", "created_at": "ISO8601"
}
```

**BookingItemResource:** `{ "ticket_type": "adult|child", "quantity": 1, "unit_price": 200.00 }`

**Note:** Backend prices are stored in cents (`total_price_cents`), returned in dollars via resource. Frontend uses dollars throughout. ✅ Compatible.

### 1.3 Frontend API Calls vs Backend Endpoints

| Frontend Function | Calls | Backend Exists? | Status |
|---|---|---|---|
| `getAvailability(date)` | `GET /availability?date=` | ✅ Yes | **Compatible** |
| `createBooking(payload)` | `POST /bookings` | ✅ Yes | **MISMATCH** — see below |
| `getBookings(date?)` | `GET /bookings?date=` | ✅ Yes (auth required) | **Auth gap** |
| `getDailyReport(date)` | `GET /reports/daily?date=` | ✅ Yes (auth required) | **Auth gap** |
| `blockSchedule(payload)` | `POST /schedules/block` | ✅ Yes (auth required) | **Auth gap** |
| `getSchedulePdfUrl(date)` | `GET /reports/schedule-pdf?date=` | ✅ Yes (auth required) | **Auth gap** |

### 1.4 Key Mismatches

1. **Stripe Payment Flow Mismatch (CRITICAL):** Frontend has a fake card form (`cardData` state with number/expiry/cvc). Backend creates a Stripe PaymentIntent and returns `client_secret`. Frontend **ignores** the `payment` field in the response. It needs to use Stripe.js Elements with the `client_secret` instead of the fake card form.

2. **BookingResource missing fields:** Backend's `BookingResource` does not include `is_confirmed` or `needs_confirmation` fields that the frontend `Booking` type expects. The backend model has these columns but the resource doesn't expose them.

3. **Admin auth not implemented in frontend:** All admin endpoints require `auth.admin` (Bearer token matching `services.admin_token`). Frontend `api.ts` has no auth header for these calls. Admin pages call `getBookings`, `getDailyReport`, `blockSchedule`, `getSchedulePdfUrl` — all will return 401.

4. **Frontend `id` vs backend `booking_ref`:** Backend `BookingResource` maps `id` → `booking_ref` (e.g., `CBB-20260424-XXXX`). Frontend mock uses `BK-A1B2C3`. Format difference is fine but the frontend references `booking.id` in the confirmation screen, which will now show `CBB-*` format.

5. **`schedules/unblock` endpoint exists in backend but frontend has no `unblockSchedule` function.**

6. **Admin schedule page uses local `blockedDates` state** — doesn't call the block/unblock API at all.

7. **Admin reports page** — has a date picker and PDF download button but the PDF download function (`getSchedulePdfUrl`) returns a blob URL; the actual button click handler in `reports/page.tsx` needs verification that it wires correctly.

---

## 2. Data Flow Mapping

### 2.1 Booking Flow (End-to-End)

```
Frontend                          Backend                           Stripe
────────                          ───────                           ──────
Step 1: User picks date
Step 2: User picks slot  ──GET /availability──→  Query boats/slots, calc remaining
Step 3: User picks tickets       capacity from bookings table
Step 4: User fills guest info
Step 5: User reviews order
  |
  └── POST /bookings ──────────→  Create Booking (pending)
                                    Create BookingGuest(s)
                                    Create BookingItem(s)
                                    Create PaymentIntent ──→  Stripe returns intent + client_secret
                                    Send confirmation email
                               ←──  { booking, payment: { client_secret } }
  |
  ├── [MISSING] Use Stripe.js with client_secret to confirm payment
  |
  └── Show confirmation screen (currently shows immediately)
```

### 2.2 Mock vs Real Data

| Data | Source | Mock? |
|------|--------|-------|
| Time slots | `booking-service.ts` MOCK_SLOTS array | ✅ When `NEXT_PUBLIC_API_URL` not set |
| Bookings (admin) | `booking-service.ts` MOCK_BOOKINGS array | ✅ When `NEXT_PUBLIC_API_URL` not set |
| Daily report | Computed from MOCK_BOOKINGS | ✅ Mock |
| Pricing ($200/$150/$75) | Hardcoded in both store and backend | ⚠️ Duplicated — no single source of truth |
| Boat names | Hardcoded ("SS Clear Seas", "Skys") | ✅ Mock — backend has Boat model with real data |

The mock/real switch is controlled by `USE_MOCK = !process.env.NEXT_PUBLIC_API_URL`. Setting `NEXT_PUBLIC_API_URL` disables all mocks.

### 2.3 State Transformations (Zustand → Eloquent)

| Zustand Field | Backend Column | Notes |
|---|---|---|
| `selectedDate` | `tour_date` | Formatted as `YYYY-MM-DD` in `createBooking()` |
| `selectedSlot.id` | `time_slot_id` | Direct UUID pass-through ✅ |
| `adultCount` | `adult_count` (request) → `BookingItem(ticket_type='adult')` | Backend creates item record |
| `childCount` | `child_count` (request) → `BookingItem(ticket_type='child')` | Backend creates item record |
| `packageUpgrade` | `package_upgrade` (request) → `photo_upgrade_count` | Backend calculates count |
| `specialOccasion` | `special_occasion` (request) → stored as `'birthday'` or `null` | ⚠️ Backend hardcodes 'birthday' |
| `specialComment` | `special_comment` | Direct pass-through ✅ |
| `guests[0]` | `BookingGuest(is_primary=true)` | Direct pass-through ✅ |
| `guests[1+]` | `BookingGuest(is_primary=false)` | Only first_name/last_name/email sent; phone is dropped |
| `getTotal()` | `total_price_cents` | Backend recalculates independently (cents) |

---

## 3. Authentication Integration

### 3.1 Current State
- **Sanctum:** Installed (personal_access_tokens migration exists), but **unused**
- **Filament auth:** Uses Laravel's default session auth for admin panel
- **Admin API auth:** Custom `AdminTokenAuth` middleware — simple Bearer token comparison against `config('services.admin_token')`
- **Frontend:** No auth at all

### 3.2 Recommendation: Hybrid Approach

**Customer-facing (public booking):** No auth needed. The booking flow is anonymous — guest provides name/email/phone. No login required.

**Admin dashboard (frontend):** Use the existing `AdminTokenAuth` middleware. Add the admin token to the frontend API client for admin routes:

```typescript
// Option A: Static token in env
NEXT_PUBLIC_ADMIN_TOKEN=clearboat-admin-token-2026

// Option B: Simple login page that stores token in localStorage
```

**Filament admin panel:** Keep as-is (session auth). Separate from the Next.js admin dashboard.

### 3.3 Sanctum — When Needed
Sanctum SPA mode would be overkill right now. If customer accounts are added later (view past bookings, modify/cancel), then add Sanctum SPA cookies:
- Backend: `Sanctum::statefulApi()` already configured in `bootstrap/app.php`
- Frontend: `withCredentials: true` on axios, `SANCTUM_STATEFUL_DOMAINS` configured
- Same domain deployment required for cookie sharing

**Coexistence:** Filament uses session guard, Sanctum can use token guard simultaneously. No conflict.

---

## 4. State Synchronization

### 4.1 Zustand Store → Backend Mapping

| Zustand Store | Backend Entity | Sync Direction |
|---|---|---|
| `booking-store` | `Booking` + `BookingGuest` + `BookingItem` | Write-only (on submit) |
| `availableSlots` | `TimeSlot` (computed) | Read from API |
| No admin store | All models | Read from API (with auth) |

The frontend is essentially **stateless** relative to the backend — it collects data, sends it once, and shows a confirmation. No ongoing sync needed for the booking flow.

### 4.2 Backend State Changes

| Event | Current Handling | Recommendation |
|---|---|---|
| Payment confirmed (Stripe webhook) | No webhook endpoint exists | **Create** `POST /api/stripe/webhook` |
| Booking cancelled | No frontend mechanism | Admin-only via Filament |
| Booking confirmed | Backend sets `is_confirmed` | Not surfaced to customer |
| Slot becomes full | No notification | Re-fetch availability on date change (already done) |

### 4.3 Recommendation: Lightweight Approach

- **No WebSocket/SSE needed** — the booking flow is request-response
- **Stripe webhooks** are essential — need endpoint to update `Payment.status` and `Booking.status` when payment succeeds/fails
- **Polling not needed** — availability is fetched on-demand when user picks a date
- If real-time admin updates are needed later (e.g., "booking just came in"), add Laravel Reverb/Broadcasting with WebSocket client

---

## 5. Obstacles & Risks

### 5.1 CORS
- **Status:** `HandleCors` middleware is prepended to the API middleware stack in `bootstrap/app.php` ✅
- **Config file:** `config/cors.php` **does not exist** — Laravel 12 uses default CORS settings
- **Risk:** Default Laravel CORS allows all origins in local dev but needs explicit configuration for production
- **Fix:** Create `config/cors.php` with `allowed_origins` including `https://water.ourea.tech` and the production domain

### 5.2 Session/Cookie Handling
- **Current:** Admin API uses Bearer token auth (not sessions) — no cookie issues
- **Risk:** If Sanctum SPA mode is added later, both apps must be on the same domain (or subdomain with shared TLD)
- **Filament:** Uses session cookies — must be on `clearwater-panel.ourea.tech` or subdomain of the same domain

### 5.3 Environment Variables
- **Current state:**
  - Frontend `.env`: Has `NEXT_PUBLIC_API_URL=http://localhost:8000/api` (needs production URL)
  - Backend `.env`: Has `APP_URL=https://clearwater-panel.ourea.tech`, Stripe keys empty
- **Risk:** Both apps need their own `.env`. No conflicts since they run in separate processes
- **Missing:** `STRIPE_SECRET_KEY`, `STRIPE_WEBHOOK_SECRET`, `RESEND_API_KEY` are empty in backend

### 5.4 Stripe Integration (CRITICAL)
- **Current backend:** Creates PaymentIntent server-side, returns `client_secret` ✅
- **Current frontend:** Has a **fake card form** that collects number/expiry/cvc but **never sends to Stripe** ❌
- **Missing from frontend:**
  - `@stripe/stripe-js` and `@stripe/react-stripe-js` packages
  - Stripe Elements integration using the `client_secret` from booking response
  - `NEXT_PUBLIC_STRIPE_PUBLISHABLE_KEY` env var (commented out in .env)
- **Missing from backend:**
  - Stripe webhook endpoint to handle `payment_intent.succeeded` / `payment_intent.payment_failed`
  - Webhook should update `Payment.status` → `succeeded` and `Booking.status` → `confirmed`

**Required changes:**
1. Frontend: Replace card form with Stripe Elements (CardElement or PaymentElement)
2. Frontend: After booking response, confirm payment with `stripe.confirmCardPayment(client_secret)`
3. Backend: Add `POST /api/stripe/webhook` with signature verification
4. Backend: Webhook handler updates payment + booking status

### 5.5 File Uploads
- No file uploads in current scope. Photos are taken by crew (not uploaded by customers).
- Future: If guest photo uploads are needed, use Laravel's file upload with `Storage`.

### 5.6 Rate Limiting
- Laravel's default API rate limit (60 req/min) applies
- **Risk:** Availability polling on every keystroke/date change could hit limits
- **Mitigation:** Frontend already deduplicates availability fetches via `lastFetchedDate` ref. Sufficient for current usage.

### 5.7 Timezone Handling
- **Backend:** PostgreSQL stores `tour_date` as date (no timezone issue for dates)
- **Frontend:** Uses browser's local timezone for date display via `date-fns`
- **Risk:** A user in one timezone booking for "today" vs server's "today" could mismatch
- **Mitigation:** Backend validates `after_or_equal:today` which uses server time. Consider sending timezone offset from frontend, or using the user's browser date.

### 5.8 Error Handling
- **Frontend:** Axios interceptor extracts `error.response?.data?.message` and rejects with `Error(message)`
- **Backend:** Laravel returns validation errors as `{ "message": "...", "errors": { "field": ["message"] } }` (422)
- **Gap:** Frontend toast shows generic "Booking failed" — doesn't surface specific field errors
- **Fix:** Parse `error.response.data.errors` and show field-level validation messages

---

## 6. Deployment Architecture

### 6.1 Current Setup
- **Frontend:** `water.ourea.tech:3000` (Next.js dev server)
- **Backend admin panel:** `clearwater-panel.ourea.tech` (Filament, likely via php-fpm/nginx)
- **Backend API:** Presumably `clearwater-panel.ourea.tech/api` (same Laravel app)

### 6.2 Recommended Production Architecture

```
water.ourea.tech              → Next.js (frontend) :3000
clearwater-panel.ourea.tech   → Laravel (Filament + API) :80/443
```

Both on separate subdomains behind nginx reverse proxy.

### 6.3 Required Config Changes

**Frontend `.env` (production):**
```
NEXT_PUBLIC_API_URL=https://clearwater-panel.ourea.tech/api
NEXT_PUBLIC_STRIPE_PUBLISHABLE_KEY=pk_live_...
```

**Backend `config/cors.php`:**
```php
'allowed_origins' => ['https://water.ourea.tech'],
'allowed_origins_patterns' => [],
'supports_credentials' => false,
```

**Backend `.env` (production):**
```
APP_URL=https://clearwater-panel.ourea.tech
STRIPE_SECRET_KEY=sk_live_...
STRIPE_WEBHOOK_SECRET=whsec_...
SANCTUM_STATEFUL_DOMAINS=clearwater-panel.ourea.tech
SESSION_DOMAIN=.ourea.tech
```

**Nginx:**
- `water.ourea.tech`: Proxy to Next.js on port 3000
- `clearwater-panel.ourea.tech`: Proxy to php-fpm (already configured for Filament)
- Both need SSL (Let's Encrypt / Cloudflare)
- No special proxy headers needed since API uses Bearer token auth (not cookies)

### 6.4 SSL
- Both domains need valid SSL certificates
- If using Cloudflare: SSL is handled at the edge
- If using Let's Encrypt: `certbot` for both domains

---

## 7. Implementation Priority

### P0 — Must Do (Booking won't work without)
1. **Replace fake card form with Stripe Elements** — integrate `@stripe/stripe-js`, use `client_secret` from booking response
2. **Add Stripe webhook endpoint** — handle payment success/failure, update booking status
3. **Add admin auth to frontend API calls** — pass Bearer token for admin endpoints
4. **Create `config/cors.php`** — allow `water.ourea.tech` origin

### P1 — Should Do (Proper UX)
5. **Surface validation errors** — show field-level errors from 422 responses
6. **Add `is_confirmed`/`needs_confirmation` to BookingResource**
7. **Wire admin schedule page to block/unblock API**
8. **Wire admin reports page to real API calls**
9. **Set production env vars** (Stripe keys, API URLs)

### P2 — Nice to Have
10. **Booking lookup page** (by email + booking ref)
11. **Cancel/reschedule booking** (customer-facing)
12. **Real-time admin notifications** (Reverb/WebSocket)
13. **Timezone-aware date handling**
14. **Customer accounts** (Sanctum SPA mode)

---

## 8. Database Schema (Existing)

- `boats` — id (uuid), name, slug, capacity, is_active
- `time_slots` — id (uuid), boat_id, day, start_time, end_time, max_capacity, is_blocked, effective_from/until
- `bookings` — id (uuid), booking_ref, tour_date, time_slot_id, status, photo_upgrade_count, special_occasion, special_comment, total_price_cents, is_confirmed, needs_confirmation, timestamps
- `booking_guests` — id (uuid), booking_id, first_name, last_name, email, phone, is_primary
- `booking_items` — id (uuid), booking_id, ticket_type, quantity, unit_price_cents
- `payments` — id (uuid), booking_id, stripe_intent_id, amount_cents, status, metadata
- `email_logs` — id (uuid), booking_id, recipient, subject, template, resend_id, status, sent_at
- `users` — id (uuid), email, name, password, role (admin/staff/super_admin)
- `personal_access_tokens` — Sanctum (exists but unused)
