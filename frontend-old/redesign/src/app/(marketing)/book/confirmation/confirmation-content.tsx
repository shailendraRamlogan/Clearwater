"use client";

import { useSearchParams } from "next/navigation";
import { Check, Calendar, Clock, Ship, Download, Users, Mail } from "lucide-react";
import Link from "next/link";

export default function ConfirmationContent() {
  const searchParams = useSearchParams();
  const ref = searchParams.get("ref");
  const email = searchParams.get("email");
  const date = searchParams.get("date");
  const time = searchParams.get("time");
  const adults = searchParams.get("adults");
  const children = searchParams.get("children");
  const total = searchParams.get("total");

  const formattedDate = date
    ? new Date(date + "T12:00:00").toLocaleDateString("en-US", {
        weekday: "long",
        year: "numeric",
        month: "long",
        day: "numeric",
      })
    : null;

  const handleDownloadTicket = () => {
    const ticketWindow = window.open("", "_blank");
    if (!ticketWindow) return;

    ticketWindow.document.write(`
<!DOCTYPE html>
<html>
<head>
  <title>Clear Boat Bahamas - Ticket</title>
  <style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: 'Segoe UI', system-ui, -apple-system, sans-serif; background: #f5f5f5; display: flex; align-items: center; justify-content: center; min-height: 100vh; padding: 24px; }
    .ticket { background: white; border-radius: 16px; overflow: hidden; max-width: 400px; width: 100%; box-shadow: 0 4px 24px rgba(0,0,0,0.1); }
    .ticket-header { background: #00636C; padding: 32px 24px; text-align: center; color: white; }
    .ticket-header h1 { font-size: 20px; margin-bottom: 4px; }
    .ticket-header .ref { font-size: 24px; font-family: monospace; font-weight: bold; letter-spacing: 2px; margin-top: 8px; }
    .ticket-header .sub { font-size: 12px; color: #99dfe3; margin-top: 2px; }
    .ticket-body { padding: 24px; }
    .ticket-row { display: flex; align-items: flex-start; gap: 12px; padding: 12px 0; border-bottom: 1px solid #f0f0f0; }
    .ticket-row:last-child { border-bottom: none; }
    .ticket-row .icon { width: 20px; height: 20px; color: #00ABB3; flex-shrink: 0; margin-top: 2px; }
    .ticket-row .label { font-size: 11px; color: #888; text-transform: uppercase; letter-spacing: 0.5px; }
    .ticket-row .value { font-size: 15px; font-weight: 600; color: #003038; margin-top: 2px; }
    .ticket-divider { margin: 0 24px; border-top: 2px dashed #e0e0e0; position: relative; }
    .ticket-footer { padding: 20px 24px; text-align: center; }
    .ticket-footer p { font-size: 13px; color: #666; }
    .ticket-footer .email { font-weight: 600; color: #003038; }
    .ticket-barcode { text-align: center; padding: 16px 24px; font-family: monospace; font-size: 10px; color: #999; letter-spacing: 1px; }
    @media print { body { background: white; padding: 0; } .ticket { box-shadow: none; } }
  </style>
</head>
<body>
  <div class="ticket">
    <div class="ticket-header">
      <h1>🌊 Clear Boat Bahamas</h1>
      <div class="sub">Transparent Boat Tour</div>
      <div class="ref">${ref || "CBB-000"}</div>
      <div class="sub">Booking Reference</div>
    </div>
    <div class="ticket-body">
      <div class="ticket-row">
        <div class="icon">📅</div>
        <div>
          <div class="label">Date</div>
          <div class="value">${formattedDate || "See confirmation email"}</div>
        </div>
      </div>
      <div class="ticket-row">
        <div class="icon">🕐</div>
        <div>
          <div class="label">Time</div>
          <div class="value">${time || "See confirmation email"}</div>
        </div>
      </div>
      <div class="ticket-row">
        <div class="icon">🚢</div>
        <div>
          <div class="label">Experience</div>
          <div class="value">2.5-Hour Transparent Boat Tour</div>
        </div>
      </div>
      <div class="ticket-row">
        <div class="icon">👥</div>
        <div>
          <div class="label">Guests</div>
          <div class="value">${[adults && `${adults} Adult${Number(adults) !== 1 ? "s" : ""}`, children && `${children} Child${Number(children) !== 1 ? "ren" : ""}`].filter(Boolean).join(", ") || "See confirmation email"}</div>
        </div>
      </div>
      ${total ? `<div class="ticket-row">
        <div class="icon">💳</div>
        <div>
          <div class="label">Total Paid</div>
          <div class="value">BSD $${(Number(total) / 100).toFixed(2)}</div>
        </div>
      </div>` : ""}
    </div>
    <div class="ticket-divider"></div>
    <div class="ticket-barcode">||| ${ref || "CBB-000"} |||</div>
    <div class="ticket-footer">
      <p>Confirmation sent to<br><span class="email">${email || "your email"}</span></p>
    </div>
  </div>
  <script>window.onload = function() { window.print(); }</script>
</body>
</html>`);
    ticketWindow.document.close();
  };

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
                    <p className="text-sm font-semibold text-brand-900">{time || "See confirmation email"}</p>
                  </div>
                </div>
                <div className="flex items-center gap-3">
                  <Ship className="w-5 h-5 text-brand-400 flex-shrink-0" />
                  <div>
                    <p className="text-xs text-brand-500">Experience</p>
                    <p className="text-sm font-semibold text-brand-900">2.5-Hour Transparent Boat Tour</p>
                  </div>
                </div>
                {(adults || children) && (
                  <div className="flex items-center gap-3">
                    <Users className="w-5 h-5 text-brand-400 flex-shrink-0" />
                    <div>
                      <p className="text-xs text-brand-500">Guests</p>
                      <p className="text-sm font-semibold text-brand-900">
                        {[
                          adults && `${adults} Adult${Number(adults) !== 1 ? "s" : ""}`,
                          children && `${children} Child${Number(children) !== 1 ? "ren" : ""}`,
                        ].filter(Boolean).join(", ")}
                      </p>
                    </div>
                  </div>
                )}
                {total && (
                  <div className="flex items-center gap-3">
                    <div className="w-5 h-5 flex-shrink-0 text-center text-brand-400 text-sm font-bold">$</div>
                    <div>
                      <p className="text-xs text-brand-500">Total Paid</p>
                      <p className="text-sm font-semibold text-brand-900">BSD ${(Number(total) / 100).toFixed(2)}</p>
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
                <p className="text-sm font-semibold text-brand-900">{email || "your email"}</p>
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
