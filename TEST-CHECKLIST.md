# Clearwater Integration — Test Checklist

**Date:** 2026-04-24
**Status:** Ready for testing

## Pre-Test Setup
- [ ] Verify backend is running: `curl https://clearwater-panel.ourea.tech/api/pricing`
- [ ] Verify frontend is running: `curl https://water.ourea.tech` (should load)
- [ ] Note: Admin token is now required from env var (no fallback)

---

## Phase 1 — Backend Tests

### 1. Pricing Endpoint
- [ ] `GET /api/pricing` returns `{ "adult": 200.00, "child": 150.00, "upgrade": 75.00 }`
- [ ] Status 200, no auth required

### 2. Capacity Check
- [ ] **Test date: 2026-04-28, slot 12:15-14:45** (9/10 booked, 1 remaining)
- [ ] Book 1 adult → should succeed (201)
- [ ] Book 1 more adult → should fail (409) "This time slot is full"
- [ ] Verify the 409 response doesn't create a booking in the database

### 3. Booking Lookup
- [ ] `GET /api/bookings/lookup?ref=CBB-20260424-7E08` (use a real ref from DB)
- [ ] Should return booking details with `is_confirmed` and `needs_confirmation` fields
- [ ] `GET /api/bookings/lookup?ref=FAKE-123` → should return 404

### 4. Blocked Schedules
- [ ] `GET /api/schedules/blocked?month=2026-04` with admin Bearer token → should return array
- [ ] Without auth token → should return 401

### 5. Rate Limiting
- [ ] Hit any public endpoint 60+ times quickly → should get 429 after limit

### 6. Filament Admin Panel
- [ ] `https://clearwater-panel.ourea.tech/admin` still loads and works
- [ ] Login still works

### 7. Guest Count Validation
- [ ] Send booking with 3 adults but only 1 guest in guests array → should fail 422

---

## Phase 2 — Frontend Tests

### 8. Booking Flow (Happy Path)
- [ ] Go to `https://water.ourea.tech/book`
- [ ] Select a date → availability loads from API
- [ ] Select a time slot
- [ ] Choose tickets (adults/children)
- [ ] Fill in guest info
- [ ] Review order
- [ ] Payment step: Stripe CardElement appears (not the old fake form)
- [ ] Enter test card: `4242 4242 4242 4242`, any future date, any CVC
- [ ] Click "Pay" → should see "Processing payment..."
- [ ] On success → redirects to `/book/confirmation?ref=CBB-XXXX`

### 9. Confirmation Page
- [ ] Confirmation page shows booking ref, date, time, boat, total
- [ ] Refresh the page → should still load (fetches from API, not Zustand)
- [ ] Visit `/book/confirmation?ref=FAKE` → shows "Booking not found"

### 10. Capacity Error
- [ ] Try booking on a full slot → should show "This slot just filled up" banner
- [ ] User should be able to go back and pick a different slot

### 11. Validation Errors
- [ ] Submit booking with invalid email → should show field-level error near email input
- [ ] Submit with missing required fields → should highlight the field with error

### 12. Admin Login
- [ ] Visit `/admin/dashboard` → should redirect to `/admin/login`
- [ ] Enter admin token → should redirect to dashboard
- [ ] Dashboard should show real data (or loading skeleton if no data)
- [ ] Refresh page → should stay logged in (localStorage)

### 13. Admin Dashboard
- [ ] Shows daily report with real numbers (or loading state)
- [ ] Error state if API fails

### 14. Admin Bookings
- [ ] Shows bookings list for selected date
- [ ] No broken "Export CSV" button

### 15. Admin Reports
- [ ] "Generate Report" button fetches real daily report
- [ ] Summary cards show real data (not dashes)
- [ ] "Export PDF" button downloads a PDF

### 16. Admin Schedule
- [ ] Page loads and shows current blocked dates from API
- [ ] Click a date/slot to block → calls API, shows success toast
- [ ] Click again to unblock → calls API, shows success toast
- [ ] Blocked state persists on page refresh

### 17. Double-Submit Protection
- [ ] Click "Pay" button rapidly → should only submit once
- [ ] Button stays disabled during processing

### 18. Network Error Handling
- [ ] Turn off backend → submit booking → should show "Unable to connect" message

---

## Known Issues to Monitor
- Stripe test mode: use `4242 4242 4242 4242` for success, `4000 0000 0000 0002` for decline
- Gallery page uses hardcoded Unsplash URLs (not part of this integration)
- Some existing bookings are overbooked (created before capacity check was added)
- `APP_DEBUG` should be set to `false` in production before going live
