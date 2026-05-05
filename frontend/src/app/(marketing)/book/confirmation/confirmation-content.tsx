"use client";

import { useEffect, useState } from "react";
import { useSearchParams } from "next/navigation";
import { Check, Calendar, Clock, Ship, Download, Users, Mail, Loader2 } from "lucide-react";
import Link from "next/link";

interface BookingData {
  tour_date: string;
  time_slot?: {
    start_time: string;
    end_time: string;
    boat?: { name: string };
  };
  items?: Array<{ ticket_type?: { name: string }; quantity: number }>;
  guest?: { first_name: string; last_name: string; email: string };
  grand_total?: number;
  subtotal?: number;
  fees_cents?: number;
}

export default function ConfirmationContent() {
  const searchParams = useSearchParams();
  const ref = searchParams.get("ref");
  const email = searchParams.get("email");
  const date = searchParams.get("date");
  const time = searchParams.get("time");
  const adults = searchParams.get("adults");
  const children = searchParams.get("children");
  const total = searchParams.get("total");

  const [booking, setBooking] = useState<BookingData | null>(null);
  const [loading, setLoading] = useState(!date && !!ref);

  useEffect(() => {
    if (!date && ref) {
      const params = new URLSearchParams({ ref });
      if (email) params.set("email", email);
      fetch(`/api/bookings/lookup?${params}`)
        .then((r) => r.json())
        .then((data) => {
          if (data?.tour_date) setBooking(data);
        })
        .catch(() => {})
        .finally(() => setLoading(false));
    }
  }, [ref, email, date]);

  const formattedDate = date
    ? new Date(date + "T12:00:00").toLocaleDateString("en-US", {
        weekday: "long",
        year: "numeric",
        month: "long",
        day: "numeric",
      })
    : booking?.tour_date
    ? new Date(booking.tour_date + "T12:00:00").toLocaleDateString("en-US", {
        weekday: "long",
        year: "numeric",
        month: "long",
        day: "numeric",
      })
    : null;

  const formatTime = (t: string) => {
    const [h, m] = t.split(":").map(Number);
    const ampm = h >= 12 ? "PM" : "AM";
    const hr = h % 12 || 12;
    return `${hr}:${String(m).padStart(2, "0")} ${ampm}`;
  };

  const displayTime = time
    || (booking?.time_slot?.start_time ? formatTime(booking.time_slot.start_time) : null);

  const displayGuests = adults || children
    ? [
        adults && `${adults} Adult${Number(adults) !== 1 ? "s" : ""}`,
        children && `${children} Child${Number(children) !== 1 ? "ren" : ""}`,
      ].filter(Boolean).join(", ")
    : null;

  const displayTotalCents = total
    ? Number(total)
    : booking?.grand_total
    ? Math.round(booking.grand_total * 100)
    : null;

  const handleDownloadTicket = () => {
    if (!ref) return;
    window.open(`/api/tickets/pdf?ref=${encodeURIComponent(ref)}`, "_blank");
  };

  if (loading) {
    return (
      <main className="min-h-screen pt-16 lg:pt-20 flex items-center bg-sand-50">
        <div className="flex items-center justify-center py-20">
          <Loader2 className="w-6 h-6 text-brand-400 animate-spin" />
        </div>
      </main>
    );
  }

  return (
    <>
      <main className="min-h-screen pt-16 lg:pt-20 flex items-center bg-sand-50">
        <div className="section-container py-12">
          <div className="max-w-md mx-auto">
            {/* Success icon */}
            <div className="text-center mb-8">
              <div className="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <Check className="w-8 h-8 text-green-600" />
              </div>
              <h1 className="font-display text-2xl sm:text-3xl text-brand-900 mb-2">Booking Confirmed!</h1>
              <p className="text-brand-600">Your transparent boat tour is booked.</p>
            </div>

            {/* Ticket card */}
            <div className="bg-white rounded-2xl border-2 border-brand-700 overflow-hidden shadow-lg">
              {/* Ticket header */}
              <div className="bg-brand-700 p-6 text-white text-center">
                <p className="text-sm font-medium text-brand-200 mb-1">Clear Boat Bahamas</p>
                <p className="text-xs text-brand-300">Booking Reference</p>
                <p className="text-2xl font-mono font-bold tracking-wider mt-1">{ref || "CBB-000"}</p>
              </div>

              {/* Ticket body */}
              <div className="p-6 space-y-4">
                <div className="flex items-center gap-3">
                  <Calendar className="w-5 h-5 text-brand-400 flex-shrink-0" />
                  <div>
                    <p className="text-xs text-brand-500">Date</p>
                    <p className="text-sm font-semibold text-brand-900">{formattedDate || "See confirmation email"}</p>
                  </div>
                </div>
                <div className="flex items-center gap-3">
                  <Clock className="w-5 h-5 text-brand-400 flex-shrink-0" />
                  <div>
                    <p className="text-xs text-brand-500">Time</p>
                    <p className="text-sm font-semibold text-brand-900">{displayTime || "See confirmation email"}</p>
                  </div>
                </div>
                <div className="flex items-center gap-3">
                  <Ship className="w-5 h-5 text-brand-400 flex-shrink-0" />
                  <div>
                    <p className="text-xs text-brand-500">Experience</p>
                    <p className="text-sm font-semibold text-brand-900">2.5-Hour Transparent Boat Tour</p>
                  </div>
                </div>
                {displayGuests && (
                  <div className="flex items-center gap-3">
                    <Users className="w-5 h-5 text-brand-400 flex-shrink-0" />
                    <div>
                      <p className="text-xs text-brand-500">Guests</p>
                      <p className="text-sm font-semibold text-brand-900">{displayGuests}</p>
                    </div>
                  </div>
                )}
                {displayTotalCents != null && (
                  <div className="flex items-center gap-3">
                    <div className="w-5 h-5 flex-shrink-0 text-center text-brand-400 text-sm font-bold">$</div>
                    <div>
                      <p className="text-xs text-brand-500">Total Paid</p>
                      <p className="text-sm font-semibold text-brand-900">BSD ${(displayTotalCents / 100).toFixed(2)}</p>
                    </div>
                  </div>
                )}
              </div>

              {/* Dashed separator */}
              <div className="relative px-6">
                <div className="border-t-2 border-dashed border-brand-200" />
                <div className="absolute -left-3 top-1/2 -translate-y-1/2 w-6 h-6 bg-sand-50 rounded-full" />
                <div className="absolute -right-3 top-1/2 -translate-y-1/2 w-6 h-6 bg-sand-50 rounded-full" />
              </div>

              {/* Ticket footer */}
              <div className="p-6 text-center">
                <div className="flex items-center justify-center gap-2 mb-1">
                  <Mail className="w-4 h-4 text-brand-400" />
                  <p className="text-sm text-brand-600">
                    Confirmation sent to
                  </p>
                </div>
                <p className="text-sm font-semibold text-brand-900">{email || booking?.guest?.email || "your email"}</p>
              </div>
            </div>

            {/* Actions */}
            <div className="mt-6 space-y-3">
              <button
                onClick={handleDownloadTicket}
                className="w-full h-12 px-6 bg-brand-700 text-white rounded-xl text-sm font-semibold hover:bg-brand-800 transition-colors flex items-center justify-center gap-2"
              >
                <Download className="w-4 h-4" />
                Download Ticket
              </button>
              <Link
                href="/"
                className="w-full h-12 px-6 border-2 border-brand-700 text-brand-700 rounded-xl text-sm font-semibold hover:bg-brand-50 transition-colors flex items-center justify-center"
              >
                Back to Home
              </Link>
            </div>
          </div>
        </div>
      </main>
    </>
  );
}
