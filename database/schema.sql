-- Clear Boat Bahamas — Booking Engine Schema
-- PostgreSQL 15+

BEGIN;

-- ─── Extensions ────────────────────────────────────────────────────────
CREATE EXTENSION IF NOT EXISTS "pgcrypto";  -- for gen_random_uuid()

-- ─── Enums ─────────────────────────────────────────────────────────────
CREATE TYPE booking_status  AS ENUM ('pending','confirmed','cancelled');
CREATE TYPE payment_status  AS ENUM ('pending','succeeded','failed','refunded');
CREATE TYPE ticket_type     AS ENUM ('adult','child');
CREATE TYPE occasion_type   AS ENUM ('birthday','anniversary','honeymoon','proposal','other');

-- ─── Users (admin staff) ──────────────────────────────────────────────
CREATE TABLE users (
    id          UUID        PRIMARY KEY DEFAULT gen_random_uuid(),
    email       TEXT        NOT NULL UNIQUE,
    name        TEXT        NOT NULL,
    password_hash TEXT      NOT NULL,
    role        TEXT        NOT NULL DEFAULT 'admin' CHECK (role IN ('admin','staff','readonly')),
    created_at  TIMESTAMPTZ NOT NULL DEFAULT now(),
    updated_at  TIMESTAMPTZ NOT NULL DEFAULT now()
);
COMMENT ON TABLE users IS 'Admin/staff accounts for the booking dashboard.';

-- ─── Boats ─────────────────────────────────────────────────────────────
CREATE TABLE boats (
    id          UUID        PRIMARY KEY DEFAULT gen_random_uuid(),
    name        TEXT        NOT NULL,
    slug        TEXT        NOT NULL UNIQUE,        -- e.g. 'crystal-clear'
    capacity    INT         NOT NULL CHECK (capacity > 0),
    description TEXT,
    is_active   BOOLEAN     NOT NULL DEFAULT true,
    created_at  TIMESTAMPTZ NOT NULL DEFAULT now()
);
COMMENT ON TABLE boats IS 'Tour boats available for booking.';

-- ─── Time Slots ────────────────────────────────────────────────────────
CREATE TABLE time_slots (
    id          UUID        PRIMARY KEY DEFAULT gen_random_uuid(),
    boat_id     UUID        NOT NULL REFERENCES boats(id) ON DELETE CASCADE,
    start_time  TIME        NOT NULL,
    end_time    TIME        NOT NULL,
    max_capacity INT        NOT NULL CHECK (max_capacity > 0),
    is_blocked  BOOLEAN     NOT NULL DEFAULT false,
    created_at  TIMESTAMPTZ NOT NULL DEFAULT now(),

    CONSTRAINT valid_time_range CHECK (end_time > start_time)
);
COMMENT ON TABLE time_slots IS 'Daily recurring time slots per boat. Remaining capacity is computed.';

CREATE INDEX idx_time_slots_boat ON time_slots(boat_id);

-- ─── Bookings ──────────────────────────────────────────────────────────
CREATE TABLE bookings (
    id                  UUID            PRIMARY KEY DEFAULT gen_random_uuid(),
    -- human-friendly reference shown in emails / QR codes
    booking_ref         TEXT            NOT NULL UNIQUE,           -- e.g. 'CBB-20260422-A3K7'
    tour_date           DATE            NOT NULL,
    time_slot_id        UUID            NOT NULL REFERENCES time_slots(id),
    status              booking_status  NOT NULL DEFAULT 'pending',
    photo_upgrade_count INT             NOT NULL DEFAULT 0 CHECK (photo_upgrade_count >= 0),
    special_occasion    occasion_type,
    special_comment     TEXT,
    total_price_cents   INT             NOT NULL CHECK (total_price_cents >= 0),
    created_at          TIMESTAMPTZ     NOT NULL DEFAULT now(),
    updated_at          TIMESTAMPTZ     NOT NULL DEFAULT now(),

    CONSTRAINT valid_booking_date CHECK (tour_date >= CURRENT_DATE)
);
COMMENT ON TABLE bookings IS 'Core booking record. Prices stored in cents to avoid floating-point issues.';

CREATE INDEX idx_bookings_date_slot ON bookings(tour_date, time_slot_id);
CREATE INDEX idx_bookings_status    ON bookings(status);
CREATE INDEX idx_bookings_ref       ON bookings(booking_ref);
CREATE INDEX idx_bookings_guest_email ON bookings(time_slot_id);  -- used via join with booking_guests

-- ─── Booking Guests ───────────────────────────────────────────────────
CREATE TABLE booking_guests (
    id          UUID        PRIMARY KEY DEFAULT gen_random_uuid(),
    booking_id  UUID        NOT NULL REFERENCES bookings(id) ON DELETE CASCADE,
    first_name  TEXT        NOT NULL,
    last_name   TEXT        NOT NULL,
    email       TEXT        NOT NULL,
    phone       TEXT,          -- only required for primary guest
    is_primary  BOOLEAN     NOT NULL DEFAULT false,
    created_at  TIMESTAMPTZ NOT NULL DEFAULT now(),

    CONSTRAINT one_primary_per_booking CHECK (
        -- enforced via trigger; this is a documentation placeholder
        true
    )
);
COMMENT ON TABLE booking_guests IS 'Guest details per booking. Primary guest provides phone number.';

CREATE INDEX idx_booking_guests_booking ON booking_guests(booking_id);
CREATE INDEX idx_booking_guests_email   ON booking_guests(email);

-- ─── Booking Items (tickets) ──────────────────────────────────────────
CREATE TABLE booking_items (
    id          UUID        PRIMARY KEY DEFAULT gen_random_uuid(),
    booking_id  UUID        NOT NULL REFERENCES bookings(id) ON DELETE CASCADE,
    ticket_type ticket_type NOT NULL,
    quantity    INT         NOT NULL CHECK (quantity > 0),
    unit_price_cents INT    NOT NULL CHECK (unit_price_cents >= 0),
    created_at  TIMESTAMPTZ NOT NULL DEFAULT now()
);
COMMENT ON TABLE booking_items IS 'Line items: adult/child ticket quantities and their locked-in price.';

CREATE INDEX idx_booking_items_booking ON booking_items(booking_id);

-- ─── Payments (Stripe) ────────────────────────────────────────────────
CREATE TABLE payments (
    id              UUID            PRIMARY KEY DEFAULT gen_random_uuid(),
    booking_id      UUID            NOT NULL REFERENCES bookings(id) ON DELETE CASCADE,
    stripe_intent_id TEXT           NOT NULL UNIQUE,              -- Stripe PaymentIntent ID
    amount_cents    INT             NOT NULL CHECK (amount_cents >= 0),
    status          payment_status  NOT NULL DEFAULT 'pending',
    metadata        JSONB           DEFAULT '{}',
    created_at      TIMESTAMPTZ     NOT NULL DEFAULT now(),
    updated_at      TIMESTAMPTZ     NOT NULL DEFAULT now()
);
COMMENT ON TABLE payments IS 'Stripe payment records linked to bookings.';

CREATE INDEX idx_payments_booking ON payments(booking_id);
CREATE INDEX idx_payments_stripe  ON payments(stripe_intent_id);

-- ─── Email Logs ────────────────────────────────────────────────────────
CREATE TABLE email_logs (
    id          UUID        PRIMARY KEY DEFAULT gen_random_uuid(),
    booking_id  UUID        REFERENCES bookings(id) ON DELETE SET NULL,
    recipient   TEXT        NOT NULL,
    subject     TEXT        NOT NULL,
    template    TEXT        NOT NULL,           -- e.g. 'booking_confirmation','cancellation'
    resend_id   TEXT,                           -- Resend API message ID
    status      TEXT        NOT NULL DEFAULT 'sent' CHECK (status IN ('sent','failed','bounced')),
    sent_at     TIMESTAMPTZ NOT NULL DEFAULT now()
);
COMMENT ON TABLE email_logs IS 'Outbound email audit trail (Resend).';

CREATE INDEX idx_email_logs_booking ON email_logs(booking_id);
CREATE INDEX idx_email_logs_date    ON email_logs(sent_at);

-- ─── Trigger: one primary guest per booking ────────────────────────────
CREATE OR REPLACE FUNCTION enforce_one_primary_guest()
RETURNS TRIGGER AS $$
BEGIN
    IF NEW.is_primary THEN
        IF EXISTS (
            SELECT 1 FROM booking_guests
            WHERE booking_id = NEW.booking_id
              AND is_primary = true
              AND id IS DISTINCT FROM NEW.id
        ) THEN
            RAISE EXCEPTION 'booking already has a primary guest';
        END IF;
    END IF;
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER trg_one_primary_guest
    BEFORE INSERT OR UPDATE OF is_primary ON booking_guests
    FOR EACH ROW EXECUTE FUNCTION enforce_one_primary_guest();

-- ─── Trigger: auto-update updated_at ───────────────────────────────────
CREATE OR REPLACE FUNCTION update_timestamp()
RETURNS TRIGGER AS $$
BEGIN
    NEW.updated_at = now();
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER trg_users_updated     BEFORE UPDATE ON users     FOR EACH ROW EXECUTE FUNCTION update_timestamp();
CREATE TRIGGER trg_bookings_updated  BEFORE UPDATE ON bookings  FOR EACH ROW EXECUTE FUNCTION update_timestamp();
CREATE TRIGGER trg_payments_updated  BEFORE UPDATE ON payments  FOR EACH ROW EXECUTE FUNCTION update_timestamp();

-- ─── Views ─────────────────────────────────────────────────────────────

-- Available slots for a given date (admin / booking page)
CREATE OR REPLACE VIEW v_available_slots AS
SELECT
    ts.id             AS time_slot_id,
    ts.boat_id,
    b.name            AS boat_name,
    b.slug            AS boat_slug,
    ts.start_time,
    ts.end_time,
    ts.max_capacity,
    ts.is_blocked,
    COALESCE(
        ts.max_capacity
        - SUM(bi.quantity)
        - SUM(bk.photo_upgrade_count),   -- photo upgrades also consume capacity
        ts.max_capacity
    )                AS remaining_capacity
FROM time_slots ts
JOIN boats b ON b.id = ts.boat_id
LEFT JOIN bookings bk ON bk.time_slot_id = ts.id AND bk.tour_date = CURRENT_DATE AND bk.status != 'cancelled'
LEFT JOIN booking_items bi ON bi.booking_id = bk.id
WHERE ts.is_blocked = false
  AND b.is_active = true
GROUP BY ts.id, b.id;

COMMENT ON VIEW v_available_slots IS 'Remaining capacity per slot for today. Query with parameter for other dates.';

-- Daily revenue report (admin dashboard)
CREATE OR REPLACE VIEW v_daily_report AS
SELECT
    bk.tour_date,
    COUNT(*)                                    AS total_bookings,
    COUNT(*) FILTER (WHERE bk.status = 'confirmed') AS confirmed_bookings,
    SUM(bk.total_price_cents) / 100.0           AS revenue,
    SUM(bk.photo_upgrade_count)                 AS photo_upgrades,
    SUM(bi.quantity) FILTER (WHERE bi.ticket_type = 'adult') AS adult_tickets,
    SUM(bi.quantity) FILTER (WHERE bi.ticket_type = 'child') AS child_tickets
FROM bookings bk
LEFT JOIN booking_items bi ON bi.booking_id = bk.id
GROUP BY bk.tour_date
ORDER BY bk.tour_date DESC;

COMMENT ON VIEW v_daily_report IS 'Daily booking and revenue summary for admin dashboard.';

-- ─── Helper: generate booking_ref ─────────────────────────────────────
CREATE OR REPLACE FUNCTION generate_booking_ref()
RETURNS TEXT AS $$
SELECT 'CBB-' || TO_CHAR(NOW(), 'YYYYMMDD') || '-' || UPPER(SUBSTRING(ENCODE(gen_random_bytes(3), 'hex'), 1, 4));
$$ LANGUAGE sql;

COMMIT;
