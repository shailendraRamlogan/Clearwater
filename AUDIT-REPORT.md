# Clearwater Backend Audit Report

**Date:** 2026-04-24  
**Stack:** Laravel 12 + Filament v3 + Stripe + Resend + DomPDF  
**Auditor:** Automated code review

---

## 1. Percentage Completion Estimate — **7/10** (~70%)

### Implemented ✅
| Feature | Status |
|---|---|
| Booking creation API | ✅ Full |
| Stripe payment intent creation | ✅ Basic (no webhook) |
| Availability check API | ✅ Full |
| Filament admin panel (boats, slots, bookings, guests, payments, email logs) | ✅ Full |
| Guest management (Livewire editor) | ✅ Full |
| Duplicate guest detection & confirmation workflow | ✅ Full |
| Incomplete bookings tracking | ✅ Full |
| Passenger manifest (page + CSV/PDF export) | ✅ Full |
| Daily report API + PDF schedule export | ✅ Full |
| Schedule block/unblock | ✅ Full |
| Booking invoice (modal + downloadable PDF) | ✅ Full |
| Email confirmation via Resend | ✅ Full |
| Dashboard widgets (stats, recent, incomplete, confirmation required) | ✅ Full |
| Time slot seeding + daily generation command | ✅ Full |
| Test data seeder (30+ bookings) | ✅ Full |

### Missing / Incomplete ❌
| Feature | Status |
|---|---|
| **Stripe webhook handler** | ❌ No webhook endpoint for payment success/failure updates |
| **Stripe webhook middleware verification** | ❌ |
| **Booking cancellation API** | ❌ No cancel endpoint |
| **Refund flow** | ❌ No refund handling |
| **Authentication (Sanctum)** | ❌ Installed but unused — no login/register API endpoints |
| **Rate limiting** | ❌ No throttle middleware on any API route |
| **Database migrations for core tables** | ❌ No migration for bookings, boats, time_slots, booking_guests, booking_items, payments, email_logs — likely created manually or via another tool |
| **Queue workers for emails** | ❌ Email sending is synchronous in booking flow (wrapped in try/catch) |
| **Tests** | ❌ Only default example tests exist |
| **API versioning** | ❌ No `/api/v1/` prefix |
| **Booking status webhook to frontend** | ❌ No mechanism for frontend to poll/check payment status |
| **Photo upgrade price mismatch** | ⚠️ Seeder uses $25/unit, BookingController uses $75/unit |

---

## 2. Best Practices Compliance — **6/10**

### Good ✅
- Proper use of Form Requests (`StoreBookingRequest`, `AvailabilityRequest`, etc.)
- API Resources for JSON transformation (`BookingResource`, `TimeSlotResource`, etc.)
- UUID primary keys with `booted()` auto-generation
- Filament v3 resource/page conventions followed well
- Service layer for email (`EmailService`)
- `DB::transaction()` in booking creation
- `$fillable` and `$casts` properly defined on models

### Issues ❌

| Issue | File:Line |
|---|---|
| **No Form Request for ManifestExportController** — uses inline `$request->validate()` | `ManifestExportController.php:14` |
| **No `authorize()` method** on any Form Request — all default to `true` | All `app/Http/Requests/*.php` |
| **No Policies** — no authorization logic for who can view/edit bookings, guests, etc. | Missing entirely |
| **No service layer for booking** — pricing, Stripe, guest creation all in controller | `BookingController.php:20-100` |
| **No middleware on public booking endpoint** — no rate limiting or bot protection | `routes/api.php:7-8` |
| **BoatResource exposes ID field** in create form (should be auto-generated) | `BoatResource.php:29-31` |
| **`EmailLogResource` allows create/edit** — email logs should be read-only | `EmailLogResource.php:49-51` |
| **No observer pattern** for booking status changes — status transitions have no guard logic | `Booking.php` |
| **`DatabaseSeeder` uses `WithoutModelEvents`** — suppresses model events during seeding, fine for seeders but no note about why | `DatabaseSeeder.php:12` |
| **`GenerateDailySlots` command doesn't use the `--date` option** meaningfully — it creates slots regardless of date, only uses it for info message | `GenerateDailySlots.php:23` |
| **No `HasFactory` trait** on any model except via User | All models except `User.php` |

---

## 3. Code Efficiency — **5/10**

### N+1 Query Issues 🔴

| Location | Issue |
|---|---|
| `BookingResource.php:12` | `primaryGuest` accessor falls back to `guests` relationship — if `primaryGuest` isn't loaded but `guests` is, it scans collection. Acceptable but fragile. |
| `PaymentResource` table | `booking.primaryGuest` column causes N+1 — no eager loading specified | `PaymentResource.php:37-41` |
| `BookingGuestResource` table | `booking.booking_ref` column — no eager loading | `BookingGuestResource.php:48` |
| `AvailabilityController.php:22-28` | **Major N+1**: Loads all bookings per slot individually, then iterates items. Should batch-query capacity. |
| `StatsOverview.php:22-28` | 5 separate count queries on dashboard load — should be cached (even for 60s) |
| `TimeSlot::remainingCapacity()` | Runs a query per call — called in availability endpoint for every slot |
| `IncompleteBookings` page | Raw subquery + `with` + `items->sum()` in column formatter — items already loaded but could be cleaner |
| `PassengerManifest` table | `booking.items_sum_quantity` column name suggests aggregation but actually does `$record->booking->items->sum('quantity')` — N+1 on every row |

### Redundant Code

| Location | Issue |
|---|---|
| `IncompleteBookings` page + `IncompleteBookingsWidget` | **Duplicated logic** — same query, same columns, same purpose. Widget is redundant. |
| `ConfirmationRequired` page + `ConfirmationRequiredWidget` | **Duplicated logic** — same query pattern |
| `BookingController::store()` | Hardcoded prices ($200, $150, $75) — should be in config or a pricing service |
| `TestDataSeeder` | Hardcoded prices that differ from BookingController ($25 vs $75 photo upgrade) |

### Missing Caching
- No caching on availability checks
- No caching on stats queries
- No caching on vessel/time-slot dropdown options

---

## 4. Bloat Detection — **7/10**

### Dead / Unused Code

| Item | File |
|---|---|
| `Tests\Feature\ExampleTest` and `Tests\Unit\ExampleTest` | Default Laravel scaffold — unused |
| `UserFactory` | Only user factory exists; no factories for other models |
| `BookingResource\Pages\CreateBooking` | Booking create page exists but most fields are disabled/read-only — creates bookings with no actual input |
| `PdfController` | Separate controller just for invoice download — could be a Filament action |
| `ReportController::schedulePdf` | Returns PDF as stream download but also `ManifestExportController` handles PDF export — overlapping concerns |
| `routes/console.php` inspire command | Default Laravel scaffold |

### Overly Complex

| Item | Issue |
|---|---|
| `PassengerManifest` page | Has both a Filament table AND a separate export via `ManifestExportController` with base64 encoding — the export logic should be a Filament action |
| `BookingGuestResource` form | Custom HTML toggle for `is_primary` instead of using Filament's built-in `Toggle` component |

---

## 5. Edge Cases — **4/10** ⚠️

### Race Conditions 🔴

| Issue | Severity |
|---|---|
| **No capacity check before booking** — `BookingController::store()` never verifies the time slot has enough remaining capacity. Two concurrent requests could overbook. | **Critical** |
| **No database-level lock** on capacity check — `AvailabilityController` shows remaining capacity but booking creation doesn't re-check | **Critical** |
| **Guest count vs ticket count mismatch** — booking accepts `adult_count` + `child_count` but additional guests array is optional and unvalidated against total count | **Medium** |

### Missing Validation

| Issue | File |
|---|---|
| No `after_or_equal` between `start_time` and `end_time` at DB level | `TimeSlot` |
| `guests.*` array not validated for max count matching `adult_count + child_count` | `StoreBookingRequest.php` |
| No unique email validation per booking (the duplicate detection is post-create) | `BookingGuest` model |
| `BlockScheduleRequest` `reason` field is validated as required but never stored or used | `BlockScheduleRequest.php` |
| No min/max validation on `tour_date` (max future date) | `StoreBookingRequest.php` |
| `BookingGuestResource` form allows changing `booking_id` after creation — could orphan guests | `BookingGuestResource.php:31` |

### Timezone Issues ⚠️

| Issue | Location |
|---|---|
| No explicit timezone handling — `now()` uses app timezone (UTC by default), but tour dates are stored as dates without timezone. Bahama time (EST/EDT) is not configured. | Entire app |
| `after_or_equal:today` in validation uses server time, not Bahamas time | `AvailabilityRequest.php:12`, `StoreBookingRequest.php:12` |

### Error Handling Gaps

| Issue | Location |
|---|---|
| Stripe failure is silently logged but booking is still created in `pending` state with no payment — no way for user to retry payment | `BookingController.php:80-83` |
| Email failure is silently logged — no retry mechanism | `BookingController.php:91-94` |
| `PdfController` has no auth middleware defined in the controller — relies on route middleware only | `PdfController.php` |
| No 404 handling for invalid booking UUIDs in manifest export | `ManifestExportController.php` |
| `ScheduleController::block()` without `time_slot_id` blocks ALL slots — potentially destructive with no safeguard | `ScheduleController.php:12-16` |

---

## 6. Security Audit — **5/10**

### Critical 🔴

| Issue | Location |
|---|---|
| **Hardcoded default admin token** — `config/services.php:18` has `'clearboat-admin-token-2026'` as fallback | `config/services.php:18` |
| **No CSRF on manifest export** — route explicitly disables CSRF (`withoutMiddleware(VerifyCsrfToken::class)`) | `routes/web.php:11-12` |
| **No rate limiting on any endpoint** — booking endpoint is fully public and unthrottled | `routes/api.php` |
| **No Stripe webhook verification** — no webhook endpoint exists at all | Missing |
| **No input sanitization on guest data** stored in DB and rendered in Blade (though Blade auto-escapes `{{ }}`) | `GuestEditor.php` |

### Medium 🟡

| Issue | Location |
|---|---|
| **Booking API `index()` returns all bookings** with no pagination — could be used to dump entire database | `BookingController.php:105-113` |
| **Manifest export returns base64-encoded data** including all guest PII (names, emails, phones) | `ManifestExportController.php` |
| **Admin token auth is static** — no rotation, no expiry, single shared token for all admins | `AdminTokenAuth.php` |
| **No Sanctum token auth** on API — only static bearer token for admin routes | `routes/api.php` |
| **`User` model has `$hidden = ['password']`** but no `Hidden` attribute for sensitive model data on API resources | `User.php:19` |
| **Booking controller logs Stripe errors** with full exception message — could leak API details to logs | `BookingController.php:83` |
| **EmailService builds HTML with user data** — guest names/booking refs injected into HTML without explicit escaping (though Resend handles this) | `EmailService.php:49-66` |
| **No file upload security** needed currently but no guards exist if added later | N/A |

### Low 🟢

| Issue | Location |
|---|---|
| Filament panel is properly secured with `Authenticate` middleware | `AdminPanelProvider.php:56` |
| CSRF is enabled by default for web routes (except the explicit exclusion) | Default Laravel |
| SQL injection is mitigated by Eloquent ORM usage throughout | All models |
| XSS is mitigated by Blade's `{{ }}` auto-escaping | All Blade views |

---

## Summary Scores

| Category | Score | Notes |
|---|---|---|
| **Completion** | 7/10 | Core booking flow solid; missing Stripe webhooks, auth, rate limiting |
| **Best Practices** | 6/10 | Good structure; missing policies, service layer, authorization |
| **Code Efficiency** | 5/10 | Multiple N+1 queries, duplicated widget/page logic, no caching |
| **Bloat Detection** | 7/10 | Relatively clean; some duplication and unused scaffolding |
| **Edge Cases** | 4/10 | Critical: no capacity validation on booking, race conditions, timezone issues |
| **Security** | 5/10 | Hardcoded token, no rate limiting, no CSRF on export, no webhook verification |

### **Overall: 5.7/10**

---

## Priority Fixes (Ordered)

1. **🔴 Add capacity check in `BookingController::store()`** — use `lockForUpdate()` in DB transaction
2. **🔴 Add Stripe webhook endpoint** — handle `payment_intent.succeeded`, `payment_intent.payment_failed`
3. **🔴 Add rate limiting** — `throttle:60,1` on public API endpoints
4. **🟡 Change default admin token** — remove hardcoded fallback, require env var
5. **🟡 Add pagination** to `BookingController::index()`
6. **🟡 Add CSRF protection** to manifest export (use session-based fetch or Filament action)
7. **🟡 Fix photo upgrade price mismatch** — align seeder ($25) with controller ($75) or use config
8. **🟡 Add `authorize()` to Form Requests** — at minimum, admin requests should check auth
9. **🟡 Configure Bahamas timezone** — `config/app.php` `timezone => 'America/Nassau'`
10. **🟢 Add caching** to stats dashboard and availability queries
11. **🟢 Deduplicate widgets/pages** — remove `IncompleteBookingsWidget` and `ConfirmationRequiredWidget` (or the pages)
12. **🟢 Make `EmailLogResource` read-only** — remove create/edit pages
13. **🟢 Add missing database migrations** for core tables
14. **🟢 Add proper test coverage** — at minimum booking creation, availability, and capacity limits
