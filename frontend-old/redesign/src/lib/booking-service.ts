import api from "./api";
import type { TimeSlot, TicketType, Addon, PricingFee, Booking, BookingRequest, PrivateTourRequest, AvailableAddon } from "@/types/booking";

export async function getAvailability(date: string): Promise<TimeSlot[]> {
  const { data } = await api.get(`/availability?date=${date}`);
  return data.slots || data;
}

export async function getTicketTypes(): Promise<TicketType[]> {
  const { data } = await api.get("/ticket-types");
  return data;
}

export async function getAddons(): Promise<Addon[]> {
  const { data } = await api.get("/addons");
  return Array.isArray(data) ? data : [];
}

export async function getPricing(): Promise<{ adult: number; child: number; photo_upgrade?: number; fees: PricingFee[] }> {
  const { data } = await api.get("/pricing");
  return data;
}

export async function createBooking(booking: BookingRequest): Promise<{
  booking: Booking;
  payment: { client_secret: string; stripe_intent_id: string } | null;
  fees: { name: string; type: string; amount_cents: number; display: string }[];
}> {
  const { data } = await api.post("/bookings", booking);
  return data;
}

export async function confirmPayment(bookingRef: string, paymentIntentId: string): Promise<void> {
  await api.post("/bookings/confirm-payment", {
    booking_ref: bookingRef,
    payment_intent_id: paymentIntentId,
  });
}

export async function lookupBooking(ref: string, email: string): Promise<Booking> {
  const { data } = await api.get(`/bookings/lookup?ref=${ref}&email=${encodeURIComponent(email)}`);
  return data;
}

// ─── Private Tour APIs ───

export async function getPrivateTourAddons(): Promise<AvailableAddon[]> {
  const { data } = await api.get("/private-tour/addons");
  return Array.isArray(data) ? data : [];
}

export async function submitPrivateTourRequest(payload: {
  contact_first_name: string;
  contact_last_name: string;
  contact_email: string;
  contact_phone: string;
  adult_count: number;
  child_count: number;
  infant_count: number;
  has_occasion: boolean;
  occasion_details: string | null;
  preferredDates: { date: string; time_preference: "morning" | "afternoon" }[];
  guests: { first_name: string; last_name: string; email: string; phone: string; is_primary?: boolean }[];
  addon_ids?: string[];
}): Promise<{ request: PrivateTourRequest }> {
  const { data } = await api.post("/private-tour", payload);
  return data;
}
