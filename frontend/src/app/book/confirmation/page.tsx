"use client";

import { Suspense, useEffect, useState } from "react";
import { useSearchParams, useRouter } from "next/navigation";
import { format } from "date-fns";
import { CheckCircle, ArrowLeft } from "lucide-react";
import { Button } from "@/components/ui/button";
import { Card, CardContent } from "@/components/ui/card";
import { formatCurrency, formatTime } from "@/lib/utils";
import api from "@/lib/api";
import { useBookingStore } from "@/stores/booking-store";

interface BookingLookup {
  id: string;
  tour_date: string;
  time_slot: { start_time: string; boat_name: string };
  guest: { first_name: string; last_name: string; email: string };
  items: { ticket_type: string; quantity: number; unit_price: number }[];
  total_price: number;
  status: string;
  package_upgrade: boolean;
  special_occasion: boolean;
  special_comment: string;
}

function BookingConfirmationContent() {
  const searchParams = useSearchParams();
  const router = useRouter();
  const ref = searchParams.get("ref");
  const [booking, setBooking] = useState<BookingLookup | null>(null);
  const [loading, setLoading] = useState(true);
  const [notFound, setNotFound] = useState(false);

  useEffect(() => {
    if (!ref) {
      setNotFound(true);
      setLoading(false);
      return;
    }
    api
      .get(`/bookings/lookup`, { params: { ref, email: searchParams.get('email') || '' } })
      .then((res) => {
        const b = res.data.booking;
        setBooking({
          id: b.id,
          tour_date: b.tour_date,
          time_slot: b.time_slot,
          guest: b.guest,
          items: b.items,
          total_price: b.total_price,
          status: b.status,
          package_upgrade: b.package_upgrade,
          special_occasion: b.special_occasion,
          special_comment: b.special_comment,
        });
      })
      .finally(() => setLoading(false));
  }, [ref]);

  if (loading) {
    return (
      <div className="section-container py-20 text-center">
        <div className="animate-pulse">
          <div className="h-16 w-16 bg-ocean-100 rounded-full mx-auto mb-6" />
          <div className="h-8 bg-ocean-100 rounded w-64 mx-auto mb-4" />
          <div className="h-4 bg-ocean-50 rounded w-48 mx-auto" />
        </div>
      </div>
    );
  }

  if (notFound) {
    return (
      <div className="section-container py-20 text-center">
        <h1 className="text-2xl font-bold mb-4">Booking Not Found</h1>
        <p className="text-ocean-500 mb-8">
          We couldn&apos;t find a booking with that reference.
        </p>
        <Button variant="cta" onClick={() => { useBookingStore.getState().reset(); router.push("/book"); }}>
          <ArrowLeft className="mr-2 h-4 w-4" />
          Book a Tour
        </Button>
      </div>
    );
  }

  if (!booking) return null;

  return (
    <div className="section-container py-8 sm:py-20">
      <div className="max-w-lg mx-auto text-center">
        <div className="inline-flex items-center justify-center w-16 h-16 bg-green-100 rounded-full mb-6">
          <CheckCircle className="h-8 w-8 text-green-500" />
        </div>
        <h1 className="text-3xl font-bold mb-4">Booking Confirmed!</h1>
        <p className="text-ocean-500 mb-2">
          Your booking reference is{" "}
          <span className="font-mono font-bold text-ocean-700">{booking.id}</span>
        </p>
        <p className="text-ocean-500 mb-8">
          A confirmation email has been sent to{" "}
          <span className="font-medium">{booking.guest.email}</span>
        </p>

        <Card className="mb-8 text-left">
          <CardContent className="pt-6 space-y-3 text-sm">
            <div className="flex justify-between">
              <span className="text-ocean-500">Date</span>
              <span className="font-medium">
                {format(new Date(booking.tour_date + "T00:00:00"), "EEEE, MMMM d, yyyy")}
              </span>
            </div>
            <div className="flex justify-between">
              <span className="text-ocean-500">Time</span>
              <span className="font-medium">
                {formatTime(booking.time_slot.start_time)} — {booking.time_slot.boat_name}
              </span>
            </div>
            <div className="flex justify-between">
              <span className="text-ocean-500">Guest</span>
              <span className="font-medium">
                {booking.guest.first_name} {booking.guest.last_name}
              </span>
            </div>
            <div className="flex justify-between">
              <span className="text-ocean-500">Guests</span>
              <span className="font-medium">
                {booking.items.reduce((s, i) => s + i.quantity, 0)}
              </span>
            </div>
            <div className="flex justify-between">
              <span className="text-ocean-500">Status</span>
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
            <div className="border-t pt-3 flex justify-between">
              <span className="font-semibold text-lg">Total</span>
              <span className="text-2xl font-bold text-ocean-700">
                {formatCurrency(booking.total_price)}
              </span>
            </div>
          </CardContent>
        </Card>

        <Button
          variant="outline"
          onClick={() => {
            useBookingStore.getState().reset();
            router.push("/book");
          }}
        >
          Book Another Tour
        </Button>
      </div>
    </div>
  );
}

export default function BookingConfirmationPage() {
  return (
    <Suspense>
      <BookingConfirmationContent />
    </Suspense>
  );
}
