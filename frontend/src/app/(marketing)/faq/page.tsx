"use client";

import { useState } from "react";
import { ChevronDown } from "lucide-react";

type FAQItem = { q: string; a: string };

const FAQ_SECTIONS: { cat: string; items: FAQItem[] }[] = [
  {
    cat: "Booking",
    items: [
      {
        q: "How far in advance should I book?",
        a: "We recommend booking 3–5 days ahead. During high season (Dec–Apr), 1–2 weeks is safer — sunset slots are especially popular. You can book up to 6 months in advance.",
      },
      {
        q: "Can I book for a private group or event?",
        a: "Absolutely. Private charters are available for birthdays, corporate outings, proposals, and more. Contact us directly for custom pricing and availability.",
      },
      {
        q: "What's the cancellation policy?",
        a: "Free cancellation up to 24 hours before departure. Cancellations within 24 hours receive a 50% refund or full reschedule credit. No-shows are non-refundable.",
      },
      {
        q: "Is there a deposit required?",
        a: "Full payment is taken at the time of booking. If you prefer to pay a deposit, contact us and we can arrange a custom booking.",
      },
    ],
  },
  {
    cat: "On the Day",
    items: [
      {
        q: "Where do I board the boat?",
        a: "Shop 9, Elizabeth on Bay Plaza, Nassau. We're right on the waterfront — look for the Clear Boat Bahamas signage. Arrive 15 minutes before departure.",
      },
      {
        q: "What should I bring?",
        a: "Sunscreen, a swimsuit, a towel, and your sense of adventure. We provide drinks, snacks, and professional photography. No shoes required on the boat — it's barefoot-only.",
      },
      {
        q: "What about bad weather?",
        a: "Safety first. If weather forces a cancellation, you can reschedule for any future date or receive a full refund — your choice.",
      },
      {
        q: "Is there parking nearby?",
        a: "Yes — street parking and several paid lots are within a 3-minute walk of Elizabeth on Bay. We'll send directions in your confirmation email.",
      },
    ],
  },
  {
    cat: "The Experience",
    items: [
      {
        q: "How long is the tour?",
        a: "Approximately 1 hour 45 minutes from dock to dock. That's plenty of time to cruise the reef, spot marine life, enjoy drinks, and take in the views.",
      },
      {
        q: "Is there an age restriction?",
        a: "No — it's family-friendly! Children 2 and under sail free. All ages enjoy the clear-bottom view. Life jackets are available for everyone.",
      },
      {
        q: "What will we see through the clear hull?",
        a: "Depending on the route and conditions: coral reefs, tropical fish, stingrays, sea turtles, and even shipwrecks. The water clarity around Nassau is exceptional.",
      },
      {
        q: "Are the photos really included?",
        a: "Yes — a professional photographer is on every tour. You'll receive high-resolution images within 24 hours via email. No extra charge, no upsell.",
      },
    ],
  },
];

/* eslint-disable react/no-unescaped-entities */
export default function FAQPage() {
  const [openIdx, setOpenIdx] = useState<string | null>(null);

  const toggle = (key: string) => {
    setOpenIdx((prev) => (prev === key ? null : key));
  };

  return (
    <div className="bg-sand-50 pt-16 lg:pt-20">
      {/* ─── HEADER ─── */}
      <section className="px-6 lg:px-20 pt-10 lg:pt-16 pb-10 lg:pb-14">
        <div className="max-w-7xl mx-auto">
          <div className="flex items-center gap-3 mb-4">
            <span className="w-6 h-px bg-brand-500" />
            <span className="text-[11px] font-semibold tracking-[0.28em] text-brand-600 uppercase">
              FAQ
            </span>
            <span className="w-6 h-px bg-brand-500" />
          </div>
          <h1 className="font-display text-4xl sm:text-5xl lg:text-6xl text-brand-900 tracking-tight leading-[0.95] max-w-2xl">
            Everything you wanted to ask before boarding.
          </h1>
        </div>
      </section>

      {/* ─── FAQ SECTIONS ─── */}
      <section className="px-6 lg:px-20 pb-20">
        <div className="max-w-7xl mx-auto space-y-12">
          {FAQ_SECTIONS.map((section) => (
            <div key={section.cat}>
              <h2 className="text-xs font-semibold tracking-[0.2em] text-gold-500 uppercase mb-5">
                {section.cat}
              </h2>
              <div className="space-y-0">
                {section.items.map((item) => {
                  const key = `${section.cat}-${item.q}`;
                  const isOpen = openIdx === key;
                  return (
                    <div key={item.q} className="border-b border-brand-900/10">
                      <button
                        onClick={() => toggle(key)}
                        className="w-full flex items-center justify-between py-5 text-left group"
                      >
                        <span className="text-base lg:text-lg font-medium text-brand-900 group-hover:text-brand-600 transition-colors pr-4">
                          {item.q}
                        </span>
                        <ChevronDown
                          className={`w-5 h-5 text-brand-900/40 shrink-0 transition-transform duration-200 ${
                            isOpen ? "rotate-180" : ""
                          }`}
                        />
                      </button>
                      <div
                        className={`overflow-hidden transition-all duration-300 ${
                          isOpen ? "max-h-40 pb-5" : "max-h-0"
                        }`}
                      >
                        <p className="text-sm lg:text-base text-brand-900/70 leading-relaxed max-w-2xl">
                          {item.a}
                        </p>
                      </div>
                    </div>
                  );
                })}
              </div>
            </div>
          ))}
        </div>
      </section>

      {/* ─── STILL NEED HELP? ─── */}
      <section className="bg-brand-950 py-14 lg:py-20 px-6 lg:px-20 text-center">
        <h2 className="font-display text-2xl lg:text-3xl text-white tracking-tight mb-4">
          Still have questions?
        </h2>
        <p className="text-sm text-white/60 mb-6">We'd love to hear from you.</p>
        <a
          href="/contact"
          className="inline-block bg-gold-400 text-brand-900 px-7 py-3 rounded-lg text-sm font-semibold hover:bg-gold-500 transition-colors"
        >
          Contact Us
        </a>
      </section>
    </div>
  );
}
