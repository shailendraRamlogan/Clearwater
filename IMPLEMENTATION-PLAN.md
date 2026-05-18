# Clearwater — Remove Additional Guest Requirement

**Date:** 2026-05-05
**Status:** Planning (backup next)
**Goal:** Remove the requirement for additional guest details in the booking flow. Primary guest data stays required. Party size is tracked via ticket quantities, not individual guest records.

---

## Phase 1: Backup
- Full backup of `/root/.openclaw/workspace/Clearwater/` to `/root/.openclaw/workspace/Clearwater-backup-2026-05-05.tar.gz`

## Phase 2: Backend Changes (Laravel)

### 2A. Remove Incomplete Bookings System
- [ ] Delete `app/Filament/Pages/IncompleteBookings.php`
- [ ] Delete `app/Filament/Widgets/IncompleteBookingsWidget.php`
- [ ] Delete `resources/views/filament/pages/incomplete-bookings.blade.php` (if exists)
- [ ] Remove "Guest Management" navigation group (or reorganize remaining items)
- [ ] Remove `scopeIncomplete()` from `Booking` model
- [ ] Remove `getCompleteGuestsCountAttribute()` from `Booking` model
- [ ] Remove `guests_count` accessor if it's only used for this comparison

### 2B. Booking Model Updates
- [ ] `isComplete()` — stop comparing guest count to ticket count. A booking is complete once it has a primary guest and is paid.
- [ ] Remove any auto-confirmation logic that depends on guest completion

### 2C. GuestEditor / ManageGuests (Keep as Optional Admin Tool)
- [ ] Remove auto-confirmation trigger from `GuestEditor.php` save method
- [ ] Remove `sendGuestsCompletedEmail()` call flow
- [ ] Keep `ManageGuests.php` page but move navigation to under Bookings group (or hide)
- [ ] Hide `BookingGuestResource` from Filament navigation

### 2D. ConfirmationRequired Widget
- [ ] Remove "Review Guests" action from `ConfirmationRequiredWidget.php`
- [ ] Keep the duplicate-flagging functionality if still needed

### 2E. Passenger Manifest (Option B)
- [ ] Update `PassengerManifest.php` table: show primary guest + "Party of X" (ticket count) instead of per-guest rows
- [ ] Update `ManifestExportController.php` CSV: primary guest + party size column
- [ ] Update `resources/views/pdf/passenger-manifest.blade.php`: same format
- [ ] Keep filtering (date/boat/time-slot) as-is

### 2F. Booking Resource (Admin Panel)
- [ ] Remove "Guests" column (`complete_guests_count` badge) from bookings list table
- [ ] Replace `guests_expected` + `guests_collected` in edit form with single "Party Size" = `items->sum('quantity')`
- [ ] Keep the invoice modal but simplify guest section (already works with 1 guest)

### 2G. Dashboard Widgets
- [ ] `StatsOverview.php`: Replace "Total Guests" (`BookingGuest::count()`) with "Total Tickets Sold" (`BookingItem::sum('quantity')`)
- [ ] `RecentBookingsTable.php`: No change needed (already uses `primaryGuest`)

### 2H. Email Service
- [ ] Remove conditional template switching (guests complete vs incomplete)
- [ ] Always use the receipt template that includes ticket download link
- [ ] Remove "📋 Guest Information Required" notice section from receipt HTML
- [ ] Remove or simplify `sendGuestsCompletedEmail()` method
- [ ] Remove the "guest info complete" follow-up email template usage

### 2I. PDF Tickets
- [ ] Rework ticket generation to create tickets per ticket type/quantity, not per guest record
- [ ] Update `pdf/ticket.blade.php` to handle ticket-based generation
- [ ] Update `TicketService` or relevant controller

## Phase 3: Frontend Changes (Next.js)

### 3A. Booking Store (`booking-store.ts`)
- [ ] `missingGuestCount()` — stop auto-creating guest slots based on ticket quantity
- [ ] `emptyGuest()` — can keep but won't be used for additional guests
- [ ] Ensure `createBooking` only sends primary guest in the guests array

### 3B. Booking Wizard (`book/page.tsx`)
- [ ] Step 4 (Guest Details): Keep primary guest form, remove additional guest form sections
- [ ] Remove any UI that shows "X of Y guests completed"
- [ ] Keep the step navigable (or collapse into an earlier step if too thin)

### 3C. Booking Service (`booking-service.ts`)
- [ ] Stop sending `store.guests.slice(1)` as additional guests
- [ ] Only send `store.guests[0]` as primary guest

### 3D. Booking Types (`booking.ts`)
- [ ] Review `BookingGuest` type — keep as-is (it's the DB shape), just no longer create multiple records

### 3E. Confirmation Page (`book/confirmation/page.tsx`)
- [ ] Remove ticket download gate that checks `complete_guests_count`
- [ ] Always show ticket download button
- [ ] Remove any "guest info required" messaging

### 3F. Private Tours
- [ ] No changes needed — already only collects contact info + party size counts

## Phase 4: Verification
- [ ] Test booking flow end-to-end (frontend)
- [ ] Test admin panel loads without errors
- [ ] Test manifest export (PDF + CSV)
- [ ] Test email templates render correctly
- [ ] Test ticket PDF generation
- [ ] Verify no broken navigation links
- [ ] Verify database migration not needed (no schema changes)
