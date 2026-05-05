"use client";

import { useState } from "react";
import { Mail, Phone, MapPin, Clock, Send, CheckCircle } from "lucide-react";

const TOPICS = [
  "General inquiry",
  "Private charter",
  "Press / Media",
  "Lost & found",
];

const INFO = [
  { icon: Mail, label: "Email", value: "bookings@clearboatbahamas.com", href: "mailto:bookings@clearboatbahamas.com" },
  { icon: Phone, label: "Phone", value: "1-242-812-TOUR (8687)", href: "tel:+12428128687" },
  { icon: MapPin, label: "Location", value: "Shop 9, Elizabeth on Bay Plaza, Nassau, NP", href: null },
  { icon: Clock, label: "Hours", value: "Mon–Sun · 9 AM – 5 PM EST", href: null },
];

/* eslint-disable react/no-unescaped-entities */
export default function ContactPage() {
  const [topic, setTopic] = useState(TOPICS[0]);
  const [name, setName] = useState("");
  const [email, setEmail] = useState("");
  const [message, setMessage] = useState("");
  const [sent, setSent] = useState(false);

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    // In production, this would POST to the backend
    setSent(true);
  };

  return (
    <div className="bg-sand-50 pt-16 lg:pt-20">
      {/* ─── HEADER ─── */}
      <section className="px-6 lg:px-20 pt-10 lg:pt-16 pb-10 lg:pb-14">
        <div className="max-w-7xl mx-auto">
          <div className="flex items-center gap-3 mb-4">
            <span className="w-6 h-px bg-brand-500" />
            <span className="text-[11px] font-semibold tracking-[0.28em] text-brand-600 uppercase">
              Contact
            </span>
            <span className="w-6 h-px bg-brand-500" />
          </div>
          <h1 className="font-display text-4xl sm:text-5xl lg:text-6xl text-brand-900 tracking-tight leading-[0.95]">
            Drop us a line.
          </h1>
        </div>
      </section>

      {/* ─── FORM + INFO ─── */}
      <section className="px-6 lg:px-20 pb-16 lg:pb-20">
        <div className="max-w-7xl mx-auto grid lg:grid-cols-[1.2fr_1fr] gap-10 lg:gap-16">
          {/* Left: Form */}
          <div>
            {!sent ? (
              <form onSubmit={handleSubmit} className="space-y-6">
                {/* Topic chips */}
                <div>
                  <label className="block text-sm font-medium text-brand-900 mb-3">Topic</label>
                  <div className="flex flex-wrap gap-2">
                    {TOPICS.map((t) => (
                      <button
                        key={t}
                        type="button"
                        onClick={() => setTopic(t)}
                        className={`px-4 py-2 rounded-full text-sm font-medium transition-all ${
                          topic === t
                            ? "bg-brand-900 text-sand-50"
                            : "bg-white text-brand-900 border border-brand-900/10 hover:border-brand-900/25"
                        }`}
                      >
                        {t}
                      </button>
                    ))}
                  </div>
                </div>

                <div className="grid sm:grid-cols-2 gap-4">
                  <div>
                    <label className="block text-sm font-medium text-brand-900 mb-1.5">Name</label>
                    <input
                      value={name}
                      onChange={(e) => setName(e.target.value)}
                      required
                      className="w-full h-12 px-4 rounded-xl border border-brand-900/10 bg-white text-brand-900 focus:ring-2 focus:ring-brand-500 focus:border-brand-500 outline-none text-sm"
                    />
                  </div>
                  <div>
                    <label className="block text-sm font-medium text-brand-900 mb-1.5">Email</label>
                    <input
                      type="email"
                      value={email}
                      onChange={(e) => setEmail(e.target.value)}
                      required
                      className="w-full h-12 px-4 rounded-xl border border-brand-900/10 bg-white text-brand-900 focus:ring-2 focus:ring-brand-500 focus:border-brand-500 outline-none text-sm"
                    />
                  </div>
                </div>

                <div>
                  <label className="block text-sm font-medium text-brand-900 mb-1.5">Message</label>
                  <textarea
                    value={message}
                    onChange={(e) => setMessage(e.target.value)}
                    required
                    rows={5}
                    className="w-full px-4 py-3 rounded-xl border border-brand-900/10 bg-white text-brand-900 focus:ring-2 focus:ring-brand-500 focus:border-brand-500 outline-none text-sm resize-none"
                  />
                </div>

                <button
                  type="submit"
                  className="inline-flex items-center gap-2 bg-gold-400 text-brand-900 px-7 py-3 rounded-xl text-sm font-semibold hover:bg-gold-500 transition-colors"
                >
                  <Send className="w-4 h-4" />
                  Send Message
                </button>
              </form>
            ) : (
              <div className="flex flex-col items-center justify-center py-16 text-center">
                <CheckCircle className="w-12 h-12 text-brand-500 mb-4" />
                <h3 className="font-display text-2xl text-brand-900 mb-2">Message sent!</h3>
                <p className="text-sm text-brand-900/60 max-w-sm">
                  We&apos;ll get back to you within 24 hours. Check your inbox for a confirmation.
                </p>
                <button
                  onClick={() => {
                    setSent(false);
                    setName("");
                    setEmail("");
                    setMessage("");
                  }}
                  className="mt-6 text-sm text-brand-500 font-medium hover:text-brand-700 transition-colors"
                >
                  Send another message
                </button>
              </div>
            )}
          </div>

          {/* Right: Contact Info */}
          <div className="space-y-5">
            {INFO.map((item) => (
              <div
                key={item.label}
                className="bg-white rounded-xl border border-brand-900/5 p-5 flex items-start gap-4"
              >
                <div className="w-10 h-10 rounded-lg bg-brand-50 flex items-center justify-center shrink-0">
                  <item.icon className="w-5 h-5 text-brand-600" />
                </div>
                <div>
                  <p className="text-sm font-semibold text-brand-900">{item.label}</p>
                  {item.href ? (
                    <a
                      href={item.href}
                      className="text-sm text-brand-600 hover:text-brand-800 transition-colors"
                    >
                      {item.value}
                    </a>
                  ) : (
                    <p className="text-sm text-brand-900/70">{item.value}</p>
                  )}
                </div>
              </div>
            ))}

            {/* Social */}
            <div className="bg-brand-950 rounded-xl p-6 text-white">
              <p className="text-xs font-semibold tracking-[0.18em] text-white/60 uppercase mb-4">
                Follow Us
              </p>
              <div className="flex gap-6 text-sm font-medium">
                <span className="text-white/80 hover:text-gold-400 cursor-pointer transition-colors">
                  Instagram
                </span>
                <span className="text-white/80 hover:text-gold-400 cursor-pointer transition-colors">
                  X
                </span>
                <span className="text-white/80 hover:text-gold-400 cursor-pointer transition-colors">
                  TikTok
                </span>
              </div>
            </div>
          </div>
        </div>
      </section>

      {/* ─── MAP ─── */}
      <section className="w-full h-64 lg:h-80 bg-brand-200 relative">
        <iframe
          src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3588.5!2d-77.35!3d25.08!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x0%3A0x0!2sElizabeth+on+Bay+Plaza%2C+Nassau!5e0!3m2!1sen!2sbs!4v1"
          width="100%"
          height="100%"
          style={{ border: 0 }}
          allowFullScreen
          loading="lazy"
          referrerPolicy="no-referrer-when-downgrade"
          className="absolute inset-0"
        />
      </section>
    </div>
  );
}
