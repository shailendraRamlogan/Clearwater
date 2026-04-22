---
name: Clear Boat Bahamas
description: Premium Bahamian transparent boat tour booking experience. Ocean tropical theme with warm sandy accents.

colors:
  primary: "#0ea5e9"
  primary-dark: "#0284c7"
  primary-light: "#38bdf8"
  secondary: "#0369a1"
  accent: "#facc15"
  text-primary: "#0c4a6e"
  text-secondary: "#0369a1"
  text-muted: "#0ea5e9"
  background: "#ffffff"
  background-subtle: "#f0f9ff"
  background-alt: "#fef9c3"
  border: "#e0f2fe"
  border-hover: "#bae6fd"
  success: "#22c55e"
  error: "#ef4444"
  hero-start: "#082f49"
  hero-mid: "#0c4a6e"
  hero-end: "#0369a1"
  overlay-dark: "#082f49"

typography:
  body:
    fontFamily: Nunito
    fontSize: 1rem
    fontWeight: 400
    lineHeight: 1.6
  heading:
    fontFamily: Nunito
    fontSize: 3rem
    fontWeight: 700
    lineHeight: 1.2
  subheading:
    fontFamily: Nunito
    fontSize: 1.25rem
    fontWeight: 600
  label:
    fontFamily: Nunito
    fontSize: 0.875rem
    fontWeight: 500
  caption:
    fontFamily: Nunito
    fontSize: 0.875rem
    fontWeight: 400
  logo:
    fontFamily: Playfair Display
    fontSize: 1.25rem
    fontWeight: 700
  small:
    fontFamily: Nunito
    fontSize: 0.75rem
    fontWeight: 500
  price:
    fontFamily: Nunito
    fontSize: 2.25rem
    fontWeight: 700

rounded:
  sm: 4px
  md: 8px
  lg: 8px
  xl: 12px
  full: 9999px

spacing:
  xs: 4px
  sm: 8px
  md: 12px
  lg: 16px
  xl: 24px
  2xl: 32px
  3xl: 48px
  4xl: 64px
  section-y: 80px
  section-y-sm: 48px

components:
  button-primary:
    backgroundColor: "#0369a1"
    textColor: "#ffffff"
    rounded: 8px
    padding: 12px 24px
  button-primary-hover:
    backgroundColor: "#075985"
    textColor: "#ffffff"
    rounded: 8px
  button-outline:
    backgroundColor: transparent
    textColor: "#0369a1"
    borderColor: "#e0f2fe"
    rounded: 8px
    padding: 12px 24px
  button-outline-hover:
    backgroundColor: "#f0f9ff"
    textColor: "#0369a1"
  card:
    backgroundColor: "#ffffff"
    borderColor: "#e0f2fe"
    rounded: 8px
    padding: 24px
  card-hover:
    borderColor: "#bae6fd"
  input:
    backgroundColor: "#ffffff"
    borderColor: "#bae6fd"
    rounded: 8px
    padding: 8px 12px
  nav-header:
    backgroundColor: "#f6f6f6a6"
  badge-count:
    backgroundColor: "#fef9c3"
    textColor: "#0c4a6e"
    rounded: 9999px
---

## Overview

Tropical ocean elegance meets practical booking UX. The design evokes the crystal-clear waters of the Bahamas — clean, warm, and inviting. Not a luxury brand; a premium experience brand. Think warm hospitality, not cold sophistication.

The palette is anchored in ocean blues with sandy gold accents. Everything should feel like looking through clear water — open, spacious, naturally beautiful.

## Colors

The palette is rooted in ocean blues with a single warm accent.

- **Primary (#0ea5e9):** Sky blue — the main interactive color. Buttons, links, active states.
- **Primary Dark (#0284c7):** Deeper blue — hover states, emphasis.
- **Primary Light (#38bdf8):** Soft blue — highlights, secondary accents.
- **Secondary (#0369a1):** Deep ocean — headings, strong text.
- **Accent (#facc15):** Sandy gold — badges, step indicators, special highlights.
- **Text Primary (#0c4a6e):** Near-black ocean — body text, readable content.
- **Text Muted (#0ea5e9):** Light blue — captions, secondary info.
- **Background (#ffffff):** Pure white — main page backgrounds.
- **Background Subtle (#f0f9ff):** Pale blue — card backgrounds, section alternates.
- **Border (#e0f2fe):** Soft blue — subtle borders, dividers.

Use the full blue spectrum sparingly. Most of the UI should be white/neutral with blue as the intentional accent. Avoid blue-on-blue combinations that reduce contrast.

## Typography

Nunito is the primary typeface for everything. It's warm, rounded, and approachable — matching the Caribbean hospitality vibe.

Playfair Display is reserved exclusively for the "Clear Boat" logo mark in the navigation. It should never appear in headings, body text, or components.

- **Headings:** Nunito Bold (700), sizes scale from text-lg to text-4xl.
- **Body:** Nunito Regular (400), text-base with relaxed line-height (1.6).
- **Labels:** Nunito Medium (500), text-sm.
- **Captions:** Nunito Regular (400), text-sm, muted color.

Keep heading hierarchy strict. One h1 per page. Don't skip levels.

## Layout & Spacing

Content maxes out at 1280px (max-w-7xl). Sections alternate between white and subtle blue backgrounds.

- **Section padding:** py-16 sm:py-24 (vertical rhythm).
- **Card padding:** p-6 for content cards.
- **Component gaps:** gap-4 (16px) for grid layouts, space-y-4 for vertical stacks.
- **Form gaps:** space-y-4 between form fields.

On mobile, reduce padding and spacing. Keep content breathable but not wasteful.

## Elevation & Depth

Keep it flat. This is not a material design system.

- **Cards:** border only, no shadow by default. Use border-ocean-100.
- **Hover states:** border darkens to border-ocean-200. No shadow changes.
- **Active elements:** border-ocean-500 with subtle bg-ocean-50.
- **Modals/Lightbox:** bg-ocean-950/95 overlay with centered content.
- **The only shadows used are shadow-sm** — on the calendar selected date and switch thumb. Nothing heavier.

## Shapes

Consistent border radius scale. Most things are 8px (rounded-lg).

- **Buttons:** 8px
- **Cards:** 8px
- **Inputs:** 8px
- **Images in grid:** 8px
- **Icon containers:** 8px
- **Circles:** 9999px (progress steps, avatar, badge)

Never use rounded-2xl (16px) or larger on rectangular elements. Reserve rounded-full for intentional circles only.

## Components

Buttons should feel tactile but restrained. Flat backgrounds, no gradients, no shadows, no vertical movement on hover. Color change on hover is sufficient.

Cards use borders instead of shadows to create visual separation. This is intentional — it keeps the design feeling open and airy, like looking through water.

The booking wizard uses a single column max-w-2xl layout centered on the page. Steps progress horizontally via a thin progress bar. Each step is a single Card component.

The gallery uses a CSS Grid mosaic pattern. Every 20 images tile perfectly into a 4-column × 7-row grid with no jagged edges. Mobile falls back to a simple 2-column aspect-ratio grid.

The header uses a semi-transparent background (#f6f6f6a6) — do NOT use backdrop-blur as it does not work with iframe video backgrounds.

## Do's and Don'ts

**Do:**
- Use ocean-100 borders for card boundaries
- Use hover:border-ocean-200 for interactive feedback
- Use consistent gap-4 in grid layouts
- Keep mobile changes mobile-only (sm:hidden / hidden sm:flex)
- Use scrollIntoView instead of hash fragments for navigation
- Use transition-colors for hover effects

**Don't:**
- Use gradient backgrounds on buttons or interactive elements
- Use shadow-lg or shadow-xl anywhere
- Use translateY or transform on hover
- Use framer-motion or animation libraries
- Mix serif (Playfair) with sans-serif (Nunito) in content
- Use rounded-2xl or larger on rectangular elements
- Use uppercase text with tracking-wider (no eyebrow labels)
- Apply mobile-responsive changes globally (always scope to mobile breakpoints)
- Use backdrop-blur on the header
