import { api } from "./api";
import type {
  PrivateTourRequest,
} from "@/types/booking";

interface CreatePrivateTourPayload {
  contact_first_name: string;
  contact_last_name: string;
  contact_email: string;
  contact_phone: string;
  adult_count: number;
  child_count: number;
  infant_count: number;
  has_occasion: boolean;
  occasion_details?: string;
  preferred_dates: { date: string; time_preference: "morning" | "afternoon" }[];
}

export async function createPrivateTourRequest(
  payload: CreatePrivateTourPayload
): Promise<{
  message: string;
  booking_ref: string;
  request: PrivateTourRequest;
}> {
  const { data } = await api.post("/private-tour-requests", payload);
  return data;
}

export async function getPrivateTourByRef(
  ref: string
): Promise<{ request: PrivateTourRequest }> {
  const { data } = await api.get("/private-tour-requests/lookup", {
    params: { ref },
  });
  return data;
}

export async function initiatePrivateTourPayment(
  id: string
): Promise<{
  client_secret: string;
  stripe_intent_id: string;
  amount: number;
}> {
  const { data } = await api.post(
    `/private-tour-requests/${id}/initiate-payment`
  );
  return data;
}

export async function confirmPrivateTourPayment(
  bookingRef: string,
  paymentIntentId: string
): Promise<{
  message: string;
  status: string;
  booking_ref: string;
}> {
  const { data } = await api.post("/private-tour-requests/confirm-payment", {
    booking_ref: bookingRef,
    payment_intent_id: paymentIntentId,
  });
  return data;
}
