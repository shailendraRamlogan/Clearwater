"use client";

import { useEffect, useState, useRef, Suspense } from "react";
import { useRouter, useSearchParams } from "next/navigation";
import { loadStripe } from "@stripe/stripe-js";
import {
  CardElement,
  useStripe,
  useElements,
  Elements,
} from "@stripe/react-stripe-js";
import {
  getPrivateTourByRef,
  initiatePrivateTourPayment,
  confirmPrivateTourPayment,
} from "@/lib/private-tour-service";
import { formatCurrency } from "@/lib/utils";
import type { PrivateTourRequest } from "@/types/booking";
import {
  CreditCard,
  CheckCircle,
  Loader2,
  Sparkles,
} from "lucide-react";
import Link from "next/link";

const stripePromise = loadStripe(
  process.env.NEXT_PUBLIC_STRIPE_PUBLISHABLE_KEY || ""
);

function PaymentFormInner() {
  const router = useRouter();
  const searchParams = useSearchParams();
  const ref = searchParams.get("ref");
  const stripe = useStripe();
  const elements = useElements();

  const [loading, setLoading] = useState(true);
  const [processing, setProcessing] = useState(false);
  const [request, setRequest] = useState<PrivateTourRequest | null>(null);
  const [clientSecret, setClientSecret] = useState<string | null>(null);
  const [stripeError, setStripeError] = useState("");
  const [paymentDone, setPaymentDone] = useState(false);
  const [bookingRef, setBookingRef] = useState<string | null>(null);
  const submittedRef = useRef(false);

  useEffect(() => {
    if (!ref) return;
    getPrivateTourByRef(ref)
      .then(({ request: req }) => {
        if (!["awaiting_payment", "confirmed"].includes(req.status)) {
          setStripeError("This request is not ready for payment.");
          return;
        }
        setRequest(req);
        return initiatePrivateTourPayment(req.booking_ref);
      })
      .then((result) => {
        if (result) setClientSecret(result.client_secret);
      })
      .catch(() => setStripeError("Could not load booking details."))
      .finally(() => setLoading(false));
  }, [ref]);

  const handlePay = async () => {
    if (!stripe || !elements || !clientSecret || !request || submittedRef.current)
      return;
    submittedRef.current = true;
    setProcessing(true);
    setStripeError("");

    try {
      const { error: stripeErr, paymentIntent } =
        await stripe.confirmCardPayment(clientSecret, {
          payment_method: { card: elements.getElement(CardElement)! },
        });

      if (stripeErr) {
        setStripeError(stripeErr.message || "Payment failed.");
        submittedRef.current = false;
        setProcessing(false);
        return;
      }

      if (paymentIntent && request.booking_ref) {
        const result = await confirmPrivateTourPayment(
          request.booking_ref,
          paymentIntent.id
        );
        if (result?.booking_ref) setBookingRef(result.booking_ref);
        setPaymentDone(true);
      }
    } catch {
      setStripeError("An unexpected error occurred.");
      submittedRef.current = false;
    }
    setProcessing(false);
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

  if (paymentDone) {
    return (
      <main className="min-h-screen pt-16 lg:pt-20 flex items-center bg-sand-50">
        <div className="section-container py-12">
          <div className="max-w-2xl w-[80%] mx-auto px-4 sm:w-full sm:mx-auto text-center">
            <div className="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
              <CheckCircle className="w-8 h-8 text-green-600" />
            </div>
            <h1 className="font-display text-2xl sm:text-3xl text-brand-900 mb-2">
              Payment Complete!
            </h1>
            <p className="text-brand-600 mb-6">
              Your private tour is confirmed. A confirmation email has been sent.
            </p>
            <div className="space-y-3">
              {bookingRef && (
                <a
                  href={`/api/tickets/pdf?ref=${encodeURIComponent(bookingRef)}`}
                  target="_blank"
                  className="w-full h-12 px-6 bg-brand-700 text-white rounded-xl text-sm font-semibold hover:bg-brand-800 transition-colors flex items-center justify-center gap-2"
                >
                  <CreditCard className="w-4 h-4" />
                  Download Ticket
                </a>
              )}
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
    );
  }

  return (
    <main className="min-h-screen pt-16 lg:pt-20 flex items-center bg-sand-50">
      <div className="section-container py-12">
        <div className="max-w-2xl w-[80%] mx-auto px-4 sm:w-full sm:mx-auto">
          <div className="text-center mb-8">
            <Sparkles className="w-10 h-10 text-brand-400 mx-auto mb-3" />
            <h1 className="font-display text-2xl sm:text-3xl text-brand-900 mb-1">
              Private Tour Payment
            </h1>
            <p className="text-sm text-brand-600">
              {request?.booking_ref || ""}
            </p>
          </div>

          <div className="bg-white rounded-2xl border border-brand-200 shadow-sm overflow-hidden w-full min-w-0 sm:min-w-[30rem]">
            {/* Booking details */}
            <div className="p-6 border-b border-brand-100">
              <div className="space-y-3 text-sm">
                {request?.confirmed_tour_date && (
                  <div className="flex justify-between">
                    <span className="text-brand-500">Date</span>
                    <span className="font-semibold text-brand-900">
                      {new Date(String(request.confirmed_tour_date)).toLocaleDateString("en-US", {
                        weekday: "short",
                        month: "short",
                        day: "numeric",
                        year: "numeric",
                      })}
                    </span>
                  </div>
                )}
                {request?.formatted_time && (
                  <div className="flex justify-between">
                    <span className="text-brand-500">Time</span>
                    <span className="font-semibold text-brand-900">
                      {request.formatted_time}
                    </span>
                  </div>
                )}
                <div className="flex justify-between">
                  <span className="text-brand-500">Guests</span>
                  <span className="font-semibold text-brand-900">
                    {request
                      ? `${request.adult_count} adult${request.adult_count !== 1 ? "s" : ""}${request.child_count ? `, ${request.child_count} child${request.child_count !== 1 ? "ren" : ""}` : ""}`
                      : "—"}
                  </span>
                </div>
                <div className="border-t border-brand-100 pt-3 flex justify-between">
                  <span className="text-brand-500">Tour Price</span>
                  <span className="font-semibold text-brand-900">
                    {request ? formatCurrency(request.total_price_cents / 100) : "—"}
                  </span>
                </div>
                <div className="flex justify-between">
                  <span className="text-brand-500">Fees</span>
                  <span className="text-brand-600">
                    {request ? formatCurrency((request.fees_cents ?? 0) / 100) : "—"}
                  </span>
                </div>
                <div className="border-t border-brand-100 pt-3 flex justify-between">
                  <span className="text-brand-900 font-bold">Total</span>
                  <span className="text-lg font-bold text-brand-900">
                    {request ? formatCurrency((request.grand_total ?? 0) / 100) : "—"}
                  </span>
                </div>
              </div>
            </div>

            {/* Card form */}
            <div className="p-6">
              <div className="flex items-center gap-2 mb-4">
                <CreditCard className="w-4 h-4 text-brand-400" />
                <label className="text-sm font-semibold text-brand-900">
                  Card Details
                </label>
              </div>
              <div className="border border-brand-200 rounded-lg p-3 bg-sand-50">
                <CardElement
                  options={{
                    style: {
                      base: {
                        fontSize: "14px",
                        color: "#0f172a",
                        "::placeholder": { color: "#94a3b8" },
                      },
                    },
                  }}
                />
              </div>
              {stripeError && (
                <p className="text-red-500 text-xs mt-3">{stripeError}</p>
              )}
              <button
                onClick={handlePay}
                disabled={processing || !stripe || !clientSecret}
                className="w-full h-12 mt-4 bg-brand-700 text-white rounded-xl text-sm font-semibold hover:bg-brand-800 transition-colors disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2"
              >
                {processing ? (
                  <Loader2 className="w-4 h-4 animate-spin" />
                ) : (
                  <>
                    <CreditCard className="w-4 h-4" />
                    Pay{" "}
                    {request
                      ? formatCurrency((request.grand_total ?? 0) / 100)
                      : "..."}
                  </>
                )}
              </button>
            </div>
          </div>

          <p className="text-center text-xs text-brand-500 mt-4">
            Payments are processed securely via Stripe.
          </p>
        </div>
      </div>
    </main>
  );
}

export default function PrivateTourPayPage() {
  return (
    <Suspense
      fallback={
        <main className="min-h-screen pt-16 lg:pt-20 flex items-center bg-sand-50">
          <div className="flex items-center justify-center py-20">
            <Loader2 className="w-6 h-6 text-brand-400 animate-spin" />
          </div>
        </main>
      }
    >
      <Elements stripe={stripePromise}>
        <PaymentFormInner />
      </Elements>
    </Suspense>
  );
}
