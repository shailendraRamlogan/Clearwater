# Clear Boat Bahamas — Database Schema

PostgreSQL schema for the tour boat booking engine.

## Overview

| Table | Purpose |
|---|---|
| `users` | Admin/staff dashboard accounts |
| `boats` | Tour boats with capacity and status |
| `time_slots` | Daily recurring time slots per boat |
| `bookings` | Core booking records |
| `booking_guests` | Guest details (primary + additional) |
| `booking_items` | Ticket line items (adult/child × quantity) |
| `payments` | Stripe PaymentIntent records |
| `email_logs` | Outbound email audit trail (Resend) |

## Key Design Decisions

- **Prices in cents** (`total_price_cents`, `unit_price_cents`, `amount_cents`) — avoids floating-point rounding.
- **UUIDs** for all primary and foreign keys — safe for public URLs and QR codes.
- **`booking_ref`** — human-readable reference (e.g. `CBB-20260422-A3K7`) for emails and QR codes. Generated via `generate_booking_ref()`.
- **Computed `remaining_capacity`** — available through `v_available_slots` view rather than a denormalized column, preventing stale data.
- **Photo upgrade capacity** — `photo_upgrade_count` on bookings counts toward capacity usage in the available slots view.

## Enums

| Enum | Values |
|---|---|
| `booking_status` | `pending`, `confirmed`, `cancelled` |
| `payment_status` | `pending`, `succeeded`, `failed`, `refunded` |
| `ticket_type` | `adult`, `child` |
| `occasion_type` | `birthday`, `anniversary`, `honeymoon`, `proposal`, `other` |

## Pricing (application-level, not stored in DB)

| Item | Price |
|---|---|
| Adult ticket | $200 (20,000 cents) |
| Child ticket | $150 (15,000 cents) |
| Photo package upgrade | $75/person (7,500 cents) |

## Views

- **`v_available_slots`** — Remaining capacity per slot for a given date (defaults to today).
- **`v_daily_report`** — Daily booking counts, revenue, and ticket breakdowns for the admin dashboard.

## Triggers

| Trigger | Purpose |
|---|---|
| `trg_one_primary_guest` | Ensures only one primary guest per booking |
| `trg_*_updated` | Auto-updates `updated_at` on `users`, `bookings`, `payments` |

## Indexes

All foreign keys indexed. Additional indexes on:
- `bookings(tour_date, time_slot_id)` — booking lookup by date
- `bookings(status)` — filter by status
- `bookings(booking_ref)` — reference lookup for QR codes
- `booking_guests(email)` — guest lookup
- `payments(stripe_intent_id)` — Stripe webhook correlation
- `email_logs(sent_at)` — log browsing

## Quick Start

```bash
psql -U postgres -f schema.sql
```
