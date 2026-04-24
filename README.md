# Clearwater — Clear Boat Bahamas Tour Booking System

Full-stack tour booking system with Laravel 12 + Filament v3 admin panel (backend) and Next.js 14 (frontend).

## Architecture

```
clearwater.ourea.tech          → Next.js frontend (port 3000)
clearwater-panel.ourea.tech    → Laravel backend + Filament admin (port 8000)
                                → /api/* proxied from frontend domain
```

- **Backend:** Laravel 12, Filament v3, PostgreSQL, Stripe (payments)
- **Frontend:** Next.js 14 (App Router), Tailwind CSS, Zustand, Stripe Elements
- **Admin:** Filament v3 panel at `/admin` — bookings, passenger manifests, guest management, reports

## Prerequisites

- PHP 8.3+ with extensions: `pdo_pgsql`, `mbstring`, `xml`, `curl`, `bcmath`
- Node.js 22+
- PostgreSQL 15+
- Nginx
- PM2 (`npm install -g pm2`)
- Composer 2+

## Clone & Setup

```bash
git clone https://github.com/shailendraRamlogan/Clearwater.git
cd Clearwater
```

### 1. Backend (Laravel)

```bash
cd backend
composer install --no-interaction
cp .env.example .env
php artisan key:generate
```

Edit `.env`:

```env
APP_NAME="Clear Boat Bahamas"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://clearwater-panel.ourea.tech

DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=clearwater_db
DB_USERNAME=clearwater
DB_PASSWORD=<your-db-password>

# Create a PostgreSQL user and database:
# sudo -u postgres psql
# CREATE USER clearwater WITH PASSWORD '<your-db-password>';
# CREATE DATABASE clearwater_db OWNER clearwater;

ADMIN_TOKEN=<generate-a-secure-random-string>
# Used to authenticate admin API calls. Frontend stores this in localStorage.

STRIPE_SECRET_KEY=sk_test_...      # Stripe secret key (test or live)
STRIPE_WEBHOOK_SECRET=whsec_...   # Stripe webhook signing secret

# Email (optional — defaults to log driver)
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=587
MAIL_USERNAME=<username>
MAIL_PASSWORD=<password>
MAIL_FROM_ADDRESS="bookings@clearboatbahamas.com"
MAIL_FROM_NAME="Clear Boat Bahamas"

# CORS — set to your frontend domain(s)
CORS_ALLOWED_ORIGINS=https://clearwater.ourea.tech
```

Run migrations and seeders:

```bash
php artisan migrate --force
php artisan db:seed --class=DatabaseSeeder
```

Start the backend:

```bash
php artisan serve --host=127.0.0.1 --port=8000
# Or with PM2 for production:
pm2 start "php artisan serve --host=127.0.0.1 --port=8000" --name clearwater-backend
pm2 save
```

### 2. Frontend (Next.js)

```bash
cd frontend
npm install
```

Create `.env.local`:

```env
NEXT_PUBLIC_API_URL=https://clearwater-panel.ourea.tech/api
NEXT_PUBLIC_STRIPE_PUBLISHABLE_KEY=pk_test_...
```

Build and start:

```bash
npm run build
# Development:
npm run dev
# Production:
pm2 start "npm run start" --name clearwater-frontend
pm2 save
```

### 3. Nginx Configuration

**Frontend** (`/etc/nginx/sites-enabled/clearwater.ourea.tech`):

```nginx
server {
    server_name clearwater.ourea.tech;

    # API requests proxied to Laravel
    location /api/ {
        proxy_pass http://127.0.0.1:8000;
        proxy_http_version 1.1;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
    }

    # Frontend proxied to Next.js
    location / {
        proxy_pass http://127.0.0.1:3000;
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection 'upgrade';
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
        proxy_cache_bypass $http_upgrade;
    }

    listen 443 ssl;
    ssl_certificate /etc/letsencrypt/live/clearwater.ourea.tech/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/clearwater.ourea.tech/privkey.pem;
    include /etc/letsencrypt/options-ssl-nginx.conf;
    ssl_dhparam /etc/letsencrypt/ssl-dhparams.pem;
}
```

**Admin Panel** (`/etc/nginx/sites-enabled/clearwater-panel.ourea.tech`):

```nginx
server {
    server_name clearwater-panel.ourea.tech;

    location / {
        proxy_pass http://127.0.0.1:8000;
        proxy_http_version 1.1;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
    }

    listen 443 ssl;
    ssl_certificate /etc/letsencrypt/live/clearwater-panel.ourea.tech/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/clearwater-panel.ourea.tech/privkey.pem;
    include /etc/letsencrypt/options-ssl-nginx.conf;
    ssl_dhparam /etc/letsencrypt/ssl-dhparams.pem;
}
```

SSL via Certbot:

```bash
sudo certbot --nginx -d clearwater.ourea.tech -d clearwater-panel.ourea.tech
```

## API Endpoints

| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| GET | `/api/availability?date=YYYY-MM-DD` | None | Get available time slots for a date |
| GET | `/api/pricing` | None | Get current ticket prices |
| POST | `/api/bookings` | None | Create a booking |
| GET | `/api/bookings/lookup?ref=CBB-XXX&email=x` | None | Lookup a booking by reference |
| GET | `/api/bookings?date=YYYY-MM-DD` | Admin | List bookings |
| GET | `/api/reports/daily?date=YYYY-MM-DD` | Admin | Daily report |
| GET | `/api/reports/schedule-pdf?date=YYYY-MM-DD` | Admin | Schedule PDF |
| POST | `/api/schedules/block` | Admin | Block a time slot |
| POST | `/api/schedules/unblock` | Admin | Unblock a time slot |
| GET | `/api/schedules/blocked?month=YYYY-MM` | Admin | Get blocked slots |

Admin endpoints require `Authorization: Bearer <ADMIN_TOKEN>` header.

## Key Concepts

### Booking Flow
1. User selects date → frontend fetches available time slots from API
2. User selects tickets (adults/children), fills in primary guest info
3. Additional guest info is **optional** — partial data (e.g., first name only) is saved
4. Booking created → if all guests complete: `status = confirmed`; otherwise: `status = pending`
5. Stripe payment processed → confirmation page shows booking details

### Guest Completeness
- A guest is "complete" when they have first_name, last_name, AND email
- Primary guest (purchaser) is always required with full details
- Additional guests can be partial — admin completes them later
- Incomplete bookings appear in the **Incomplete Bookings** admin page
- When admin completes all guests, status auto-updates to `confirmed`

### Admin Panel
- **Bookings** — all confirmed bookings
- **Incomplete Bookings** — bookings needing guest data completion
- **Manage Guests** — edit guest info per booking
- **Passenger Manifest** — daily manifest with PDF export
- **Schedule** — block/unblock time slots
- **Reports** — daily summary and schedule PDF

## Important Notes

- **Filament + Tailwind:** Filament compiles its own Tailwind subset. Use inline `style=""` for custom colors — arbitrary utility classes like `bg-teal-600` won't work.
- **No `<style>` blocks in Blade:** Blade parses `@media` as a directive, silently corrupting views. Always use inline styles.
- **`wire:model.defer`** for filter inputs; `wire:change` only for dropdown refresh handlers.
- **Booking prices** are stored in cents (`config/pricing.php`). API returns dollars.
- **Rate limiting:** 60/min public, 30/min lookup, 120/min admin routes.

## Project Structure

```
Clearwater/
├── backend/
│   ├── app/
│   │   ├── Filament/          # Admin panel (pages, resources, widgets)
│   │   ├── Http/Controllers/Api/  # API endpoints
│   │   ├── Http/Resources/    # API resource transformers
│   │   ├── Livewire/          # GuestEditor component
│   │   ├── Models/            # Booking, BookingGuest, TimeSlot, Boat, etc.
│   │   └── Services/          # EmailService
│   ├── config/
│   │   ├── cors.php           # CORS origins
│   │   └── pricing.php        # Ticket prices (cents)
│   ├── database/
│   │   ├── migrations/
│   │   └── seeders/           # DatabaseSeeder, BoatSeeder, TestDataSeeder
│   ├── resources/views/
│   │   ├── filament/pages/    # Custom Filament page views
│   │   ├── livewire/          # Guest editor Blade view
│   │   └── pdf/               # Passenger manifest PDF template
│   └── routes/
│       ├── api.php            # API routes
│       └── web.php            # Filament routes
├── frontend/
│   ├── src/
│   │   ├── app/
│   │   │   ├── book/          # Booking wizard + confirmation page
│   │   │   └── admin/         # Admin dashboard, bookings, reports, schedule
│   │   ├── components/        # Reusable UI components
│   │   ├── lib/
│   │   │   ├── api.ts         # Axios instance with interceptors
│   │   │   ├── admin-auth.ts  # Admin token management
│   │   │   ├── booking-service.ts  # API call functions
│   │   │   └── utils.ts       # Formatting helpers
│   │   └── stores/
│   │       └── booking-store.ts  # Zustand state management
│   └── .env.local             # Frontend env vars
├── INTEGRATION-PLAN.md
├── TEST-CHECKLIST.md
└── AUDIT-REPORT.md
```

## Development

```bash
# Backend (dev mode with hot reload)
cd backend && php artisan serve

# Frontend (dev mode with hot reload)
cd frontend && npm run dev

# Run seeders (adds boats, time slots, test bookings)
cd backend && php artisan db:seed --class=DatabaseSeeder

# Clear all caches
cd backend && php artisan optimize:clear
```
