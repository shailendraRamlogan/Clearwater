"use client";

import { useState, useEffect } from "react";
import { format } from "date-fns";
// Download icon removed - CSV export disabled
import { Card, CardContent } from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import { Button } from "@/components/ui/button";
import { formatCurrency, formatTime } from "@/lib/utils";
import { getBookings } from "@/lib/booking-service";
import type { Booking } from "@/types/booking";

export default function AdminBookings() {
  const [dateFilter, setDateFilter] = useState(format(new Date(), "yyyy-MM-dd"));
  const [bookings, setBookings] = useState<Booking[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState("");

  useEffect(() => {
    setLoading(true);
    setError("");
    getBookings(dateFilter)
      .then((data) => { setBookings(data); setLoading(false); })
      .catch((e) => { setError(e.message); setLoading(false); });
  }, [dateFilter]);

  return (
    <div className="section-container py-8">
      <div className="flex items-center justify-between mb-8">
        <div>
          <h1 className="text-3xl font-bold">Bookings</h1>
          <p className="text-ocean-500 mt-1">
            Manage and view all tour bookings
          </p>
        </div>
        <Button variant="outline" disabled>
        </Button>
      </div>

      <Card className="mb-6">
        <CardContent className="pt-6">
          <div className="flex flex-col sm:flex-row gap-4">
            <div className="flex-1">
              <Input
                type="date"
                value={dateFilter}
                onChange={(e) => setDateFilter(e.target.value)}
              />
            </div>
          </div>
        </CardContent>
      </Card>

      <Card>
        <CardContent className="pt-6">
          {loading ? (
            <div className="space-y-4">
              {[1, 2, 3].map((i) => (
                <div key={i} className="h-24 bg-ocean-50 rounded-xl animate-pulse" />
              ))}
            </div>
          ) : error ? (
            <div className="text-center py-12 text-red-500">
              <p>{error}</p>
            </div>
          ) : bookings.length > 0 ? (
            <div className="space-y-4">
              {bookings.map((booking) => (
                <div
                  key={booking.id}
                  className="p-4 border border-ocean-100 rounded-xl hover:shadow-sm transition-shadow"
                >
                  <div className="flex items-start justify-between mb-2">
                    <div>
                      <div className="flex items-center gap-3 mb-1">
                        <span className="font-mono text-sm font-bold text-ocean-600">
                          {booking.id}
                        </span>
                        <span
                          className={`text-xs px-2 py-0.5 rounded-full font-medium ${
                            booking.status === "confirmed"
                              ? "bg-green-100 text-green-700"
                              : "bg-yellow-100 text-yellow-700"
                          }`}
                        >
                          {booking.status}
                        </span>
                      </div>
                      <p className="font-medium text-ocean-800">
                        {booking.guest.first_name} {booking.guest.last_name}
                      </p>
                    </div>
                    <p className="font-bold text-lg text-ocean-700">
                      {formatCurrency(booking.total_price)}
                    </p>
                  </div>
                  <div className="flex flex-wrap gap-x-6 gap-y-1 text-sm text-ocean-500">
                    <span>📧 {booking.guest.email}</span>
                    <span>📞 {booking.guest.phone}</span>
                    <span>🕐 {formatTime(booking.time_slot.start_time)} — {booking.time_slot.boat_name}</span>
                    <span>
                      👥{" "}
                      {booking.items
                        .map((i) => `${i.quantity} ${i.ticket_type}${i.quantity > 1 ? "s" : ""}`)
                        .join(", ")}
                    </span>
                  </div>
                  {booking.addons && booking.addons.length > 0 && (
                    <p className="mt-2 text-sm text-ocean-500 italic bg-sand-50 rounded-lg px-3 py-2">
                      {booking.addons.map(a => a.title).join(", ")}
                    </p>
                  )}
                </div>
              ))}
            </div>
          ) : (
            <div className="text-center py-12 text-ocean-400">
              <p>
                No bookings found for{" "}
                {format(new Date(dateFilter), "MMMM d, yyyy")}.
              </p>
            </div>
          )}
        </CardContent>
      </Card>
    </div>
  );
}
