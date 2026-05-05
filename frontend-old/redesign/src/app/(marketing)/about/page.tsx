"use client";

import Image from "next/image";

const STATS = [
  { num: "2,400+", label: "Guests welcomed" },
  { num: "18 yrs", label: "Captain experience" },
  { num: "4.9★", label: "Avg. tour rating" },
  { num: "100%", label: "Clear-bottom hull" },
];

const PILLARS = [
  {
    tag: "01 / The Boat",
    title: "A hull made of light.",
    body: "Our vessels are built around a single panoramic acrylic floor — engineered to resist Bahamian salt and sun, polished daily, and inspected before every departure.",
    img: "/about-the-boat.jpg",
  },
  {
    tag: "02 / The Route",
    title: "Where the reef meets the sky.",
    body: "We chart calm-water channels above Nassau's most photogenic reefs and shipwrecks. Spot rays, parrotfish, and the occasional turtle from a vantage point no other boat can offer.",
    img: "/about-the-route.jpg",
  },
  {
    tag: "03 / The Ritual",
    title: "Drinks, photos, repeat.",
    body: "A pro photographer rides every tour. Local rum, homemade lemonade, Bahamian beers — included. You don't lift a finger. You just look down, look up, and remember.",
    img: "/about-the-ritual.jpg",
  },
];

const TESTIMONIALS = [
  {
    quote: "We saw a hawksbill turtle five feet under the boat. My kids haven't stopped talking about it.",
    name: "Mira K.",
    city: "Toronto",
  },
  {
    quote: "The captain knew every reef by name. The rum punch didn't hurt either.",
    name: "James R.",
    city: "London",
  },
  {
    quote: "I have done a hundred boat tours. None felt like this. Photos came in same day, perfect.",
    name: "Aisha O.",
    city: "Miami",
  },
];

export default function AboutPage() {
  return (
    <div className="bg-sand-50 pt-16 lg:pt-20">
      {/* ─── MASTHEAD ─── */}
      <section className="px-6 lg:px-20 pt-8 lg:pt-12 pb-12 lg:pb-16">
        <div className="max-w-7xl mx-auto grid lg:grid-cols-[1.3fr_1fr] gap-10 lg:gap-16 items-end">
          <div>
            <p className="text-xs font-semibold tracking-[0.28em] text-brand-600 mb-4">
              VOL. 01 · NASSAU, BAHAMAS
            </p>
            <h1 className="font-display text-5xl sm:text-6xl lg:text-[6.5rem] leading-[0.92] tracking-tight text-brand-900">
              See clearly,
              <br />
              <span className="text-brand-600 not-italic">experience fully,</span>
              <br />
              remember <em>forever.</em>
            </h1>
          </div>
          <div className="flex flex-col gap-5 pb-2">
            <p className="font-display text-lg lg:text-xl text-brand-900 leading-relaxed italic">
              &ldquo;It started with a simple thought — what if the boat itself disappeared, and all
              you saw was the water beneath your feet?&rdquo;
            </p>
            <div className="flex items-center gap-3">
              <div className="w-11 h-11 rounded-full bg-gradient-to-br from-brand-500 to-brand-900" />
              <div>
                <p className="text-sm font-semibold text-brand-900">Captain Marcus Beneby</p>
                <p className="text-xs text-brand-900/60">Founder · Master Mariner, 18 yrs</p>
              </div>
            </div>
          </div>
        </div>
      </section>

      {/* ─── HERO IMAGE + STATS ─── */}
      <section className="relative h-64 sm:h-80 lg:h-[520px] overflow-hidden">
        <Image
          src="/about-stats-bg.jpg"
          alt="Guests enjoying the clear boat tour"
          fill
          className="object-cover"
          priority
        />
        <div className="absolute inset-0 bg-gradient-to-t from-brand-950/65 via-brand-950/20 to-brand-950/15" />
        <div className="absolute inset-x-6 lg:inset-x-20 bottom-6 lg:bottom-12">
          <div className="max-w-7xl mx-auto grid grid-cols-2 lg:grid-cols-4 gap-6 lg:gap-12">
            {STATS.map((s) => (
              <div key={s.label} className="border-t border-white/35 pt-3 lg:pt-4">
                <div className="font-display text-3xl lg:text-[2.8rem] font-semibold text-white leading-none tracking-tight">
                  {s.num}
                </div>
                <p className="text-xs lg:text-sm text-white/85 mt-1.5 tracking-wide">{s.label}</p>
              </div>
            ))}
          </div>
        </div>
      </section>

      {/* ─── MISSION ─── */}
      <section className="py-16 lg:py-24 px-6 lg:px-20 bg-sand-50">
        <div className="max-w-3xl mx-auto text-center">
          <div className="flex items-center justify-center gap-3 mb-5">
            <span className="w-6 h-px bg-brand-500" />
            <span className="text-[11px] font-semibold tracking-[0.28em] text-brand-600 uppercase">
              Our Mission
            </span>
            <span className="w-6 h-px bg-brand-500" />
          </div>
          <p className="font-display text-2xl sm:text-3xl lg:text-4xl text-brand-900 leading-tight tracking-tight">
            To deliver the most exhilarating{" "}
            <em className="text-brand-600">100% transparent</em> boat tours in New Providence —
            pairing the calm of Bahamian waters with the thrill of seeing straight through your
            hull.
          </p>
        </div>
      </section>

      {/* ─── THREE PILLARS (zig-zag) ─── */}
      <section className="px-6 lg:px-20 pb-20 bg-sand-50">
        <div className="max-w-7xl mx-auto space-y-0">
          {PILLARS.map((p, i) => (
            <div
              key={p.tag}
              className={`grid lg:grid-cols-2 gap-8 lg:gap-16 items-center py-8 lg:py-12 border-t border-brand-900/10 ${
                i % 2 === 1 ? "lg:[&>*:first-child]:order-2" : ""
              }`}
            >
              <div className="relative h-56 sm:h-72 lg:h-[360px] rounded-lg overflow-hidden">
                <Image src={p.img} alt={p.title} fill className="object-cover" />
              </div>
              <div>
                <p className="text-xs font-semibold tracking-[0.18em] text-gold-500 mb-2">
                  {p.tag}
                </p>
                <h3 className="font-display text-3xl lg:text-[2.6rem] font-semibold text-brand-900 leading-[1.05] tracking-tight mb-4">
                  {p.title}
                </h3>
                <p className="text-base text-brand-900/80 leading-relaxed max-w-md">{p.body}</p>
              </div>
            </div>
          ))}
        </div>
      </section>

      {/* ─── TESTIMONIALS ─── */}
      <section className="bg-brand-950 text-white py-16 lg:py-20 px-6 lg:px-20">
        <div className="max-w-7xl mx-auto grid sm:grid-cols-2 lg:grid-cols-3 gap-10 lg:gap-12">
          {TESTIMONIALS.map((t) => (
            <div key={t.name}>
              <div className="font-display text-5xl text-gold-400 leading-none mb-4">&ldquo;</div>
              <p className="font-display text-lg leading-relaxed italic">{t.quote}</p>
              <p className="mt-4 text-xs text-white/70 tracking-wider">
                {t.name.toUpperCase()} · {t.city.toUpperCase()}
              </p>
            </div>
          ))}
        </div>
      </section>

      {/* ─── CTA BAND ─── */}
      <section className="bg-gold-400 text-brand-900 py-10 lg:py-14 px-6 lg:px-20">
        <div className="max-w-7xl mx-auto flex flex-col sm:flex-row items-center justify-between gap-6">
          <h2 className="font-display text-2xl sm:text-3xl lg:text-4xl font-semibold italic tracking-tight text-center sm:text-left">
            Ready to see the Bahamas like never before?
          </h2>
          <a
            href="/"
            className="shrink-0 bg-brand-900 text-gold-400 px-7 py-3.5 rounded-full text-sm font-bold tracking-wider hover:bg-brand-950 transition-colors"
          >
            BOOK YOUR TOUR →
          </a>
        </div>
      </section>
    </div>
  );
}
