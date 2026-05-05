# Mobile Responsiveness Audit Report — Clearwater Booking Flow

**Site:** clearwater.ourea.tech  
**Viewport:** iPhone 14 (390×844)  
**Date:** 2026-04-28  

---

## Summary

The booking flow is **mostly mobile-responsive** — no horizontal overflow, no broken layouts. However, there are several issues ranging from accessibility violations to UX friction on small screens. Below are all findings.

---

## 🔴 High Severity

### 1. Overlapping `<a>` + `<button>` Elements (Accessibility + HTML Violation)
**Pages:** Landing page (hero, pricing, CTA), Gallery  
**Description:** Multiple CTA buttons are nested as `<Link><Button>` or `<a><button>`, creating overlapping interactive elements. The audit detected 5 overlapping pairs on the landing page. This is an **HTML spec violation** — interactive elements must not be nested inside other interactive elements.

**Affected instances:**
- Hero: `Book Your Adventure` — `<Link href="/book">` wrapping `<Button>`
- Hero: `Learn More` — similar pattern  
- Pricing cards: `Book Adult Tour`, `Book Child Tour`
- CTA section: `Book Your Tour Now`
- Nav: `Book Now` link + button overlap

**Impact:** Screen readers may announce duplicate actions; click behavior can be unpredictable on some browsers; violates WCAG.

**Fix:** Replace `<Link href><Button>` with just `<Button asChild><Link href>...</Link></Button>` (Radix UI pattern), or use `onClick` with `router.push()` on the button directly and remove the wrapping link.

---

### 2. "Learn More" Button Has Zero Contrast
**Page:** Landing hero  
**Description:** The "Learn More" button has white text on `rgba(255,255,255,0.1)` background — **contrast ratio of 1.00:1**. The button text is essentially invisible.

**Fix:** Add a visible background or stronger border: `border-white/40 bg-white/15 text-white`. Current code uses `border-white/30 bg-white/10` which isn't enough.

---

### 3. Small Tap Targets Throughout (Accessibility)
**Pages:** All pages  
**Description:** Many interactive elements are well below the recommended 44×44px minimum touch target:

| Element | Size | Min Dim |
|---------|------|---------|
| Nav links (About, Pricing, Gallery) | ~41×20px | 20px |
| Calendar date buttons | ~41×38px | 38px |
| "Book Now" text links | ~358×19px | 19px |
| Footer "Book a Tour" link | ~82×16px | 16px |
| "About Us" footer button | ~63×20px | 20px |
| Month nav "April 2026" | ~81×20px | 20px |

**Fix:** Add `min-h-[44px] min-w-[44px]` or `py-3` padding to interactive elements. Calendar day buttons should be at least 44×44px. Nav links should have `py-3` instead of `py-2`.

---

## 🟡 Medium Severity

### 4. Hamburger Menu Content Clipped (`max-h-64`)
**Page:** All pages (mobile nav)  
**Description:** The mobile dropdown menu has `max-h-64` (256px) hardcoded. The audit detected the dropdown content div has `scrollHeight: 157, clientHeight: 0` — meaning it's currently fine but fragile. If more nav items are added, they'll be clipped with no scroll indicator.

**Fix:** Change `max-h-64` to `max-h-[calc(100vh-80px)]` or add `overflow-y-auto` to the dropdown.

---

### 5. Step Progress Indicators Lack Mobile Labels
**Page:** Booking page (all steps)  
**Description:** The 5-step progress bar (Date → Time → Tickets → Details → Pay) has step labels hidden on mobile via `hidden sm:block`. Users only see icons — no text context about what step they're on or which steps remain.

**Fix:** Show abbreviated labels on mobile (e.g., "1. Date", "2. Time") or add a `hidden sm:inline` pattern that shows short text below each icon. Alternatively, add a text summary like "Step 2 of 5 — Choose a Time Slot" above the card.

---

### 6. Guest Details Form — Phone Input Width
**Page:** Booking Step 4 (Guest Details)  
**Description:** The `react-phone-input-2` component with `!w-full` override should be fine, but the country dropdown may cause horizontal overflow on very narrow viewports (<375px) since it adds a country code button + input side by side.

**Fix:** Verify at 320px viewport. If overflow occurs, ensure `containerClass="!w-full !flex-wrap"` and the dropdown uses a modal/dropdown overlay rather than inline expansion.

---

### 7. Fee Label Text Can Be Long on Small Screens
**Page:** Booking Step 3 (Tickets) — Running Total  
**Description:** Fee labels like "Service Fee (3.5% + $2.00)" are displayed in a `flex justify-between` row. On very narrow screens, the label + amount could wrap awkwardly.

**Fix:** Add `text-xs sm:text-sm` to fee rows, or use `flex-wrap gap-1` to allow the amount to drop below the label.

---

### 8. Sticky Nav Background Semi-transparent
**Page:** All pages  
**Description:** The nav has `background: #f6f6f6a6` (66% opacity). When scrolling over the hero video or dark sections, text can become hard to read against the semi-transparent background.

**Fix:** Add a subtle backdrop blur: `backdrop-blur-sm` or increase opacity to `#f6f6f6cc`.

---

## 🟢 Low Severity

### 9. Booking Page Video Background
**Page:** Booking page  
**Description:** A full-screen video background plays behind the booking wizard with a heavy white overlay (`rgba(255,255,255,0.9)`). This wastes bandwidth on mobile (downloading a 4K video that's barely visible) and could cause performance issues on low-end devices.

**Fix:** Consider using a static image on mobile or conditionally loading the video only on WiFi/larger screens via a media query or JS check.

---

### 10. Footer Layout Single Column on Mobile (Acceptable)
**Page:** Landing page footer  
**Description:** Footer uses `grid-cols-1 md:grid-cols-3` — stacks vertically on mobile. This is correct behavior, no fix needed. Just noting for completeness.

---

## Screenshots

All screenshots saved in `Clearwater/frontend/mobile-audit/`:
- `01-landing-hero.png` through `18-book-fullpage.png` (PNG, 2x resolution)
- `sm-*.jpg` (compressed JPG copies)
- `review-*.jpg` (review copies)
- `audit-results.json` (raw automated audit data)

---

## Recommended Fix Order

1. **Fix overlapping `<a>` + `<button>` nesting** — accessibility blocker, HTML violation
2. **Fix "Learn More" contrast** — invisible button
3. **Increase tap target sizes** — especially nav links and calendar buttons
4. **Add mobile step labels** — UX improvement for booking flow
5. **Fix hamburger menu max-height** — future-proofing
6. **Add backdrop-blur to nav** — polish
7. **Optimize video for mobile** — performance (optional)
