"use client";

import React, { useEffect, useState } from "react";
import { useSearchParams } from "next/navigation";
import { loadStripe } from "@stripe/stripe-js";
import {
  CardElement,
  Elements,
  useStripe,
  useElements,
} from "@stripe/react-stripe-js";
import { Button } from "@/components/ui/button";
import { Card, CardContent } from "@/components/ui/card";
import {
  getPrivateTourByRef,
  initiatePrivateTourPayment,
  confirmPrivateTourPayment,
} from "@/lib/private-tour-service";
import { formatCurrency } from "@/lib/utils";
import { Sparkles, CheckCircle, AlertCircle, Loader2 } from "lucide-react";
import Link from "next/link";
import { toast } from "sonner";
import type { PrivateTourRequest } from "@/types/booking";

const stripePromise = loadStripe(
  process.env.NEXT_PUBLIC_STRIPE_PUBLISHABLE_KEY || ""
);

function PaymentForm({ tour }: { tour: PrivateTourRequest }) {
  const stripe = useStripe();
  const elements = useElements();
  const [processing, setProcessing] = useState(false);
  const [success, setSuccess] = useState(false);
  const [bookingRef, setBookingRef] = useState<string | null>(null);
  const [error, setError] = useState<string | null>(null);

  async function handleSubmit(e: React.FormEvent) {
    e.preventDefault();
    if (!stripe || !elements) return;

    setProcessing(true);
    setError(null);

    try {
      // Initiate payment
      const { client_secret } = await initiatePrivateTourPayment(
        tour.booking_ref
      );

      // Confirm card payment
      const { error: stripeError, paymentIntent } =
        await stripe.confirmCardPayment(client_secret, {
          payment_method: {
            card: elements.getElement(CardElement)!,
            billing_details: {
              email: tour.contact_email,
              name: `${tour.contact_first_name} ${tour.contact_last_name}`,
            },
          },
        });

      if (stripeError) {
        setError(stripeError.message || "Payment failed.");
        return;
      }

      if (paymentIntent?.status === "succeeded") {
        // Confirm payment on backend
        const result = await confirmPrivateTourPayment(
          tour.booking_ref,
          paymentIntent.id
        );
        setBookingRef(result.booking_ref);
        setSuccess(true);
        toast.success("Payment successful! Your tour is booked.");
      }
    } catch (err: unknown) {
      const error = err as { response?: { data?: { message?: string } } };
      const msg =
        error.response?.data?.message || "Payment failed. Please try again.";
      setError(msg);
      toast.error(msg);
    } finally {
      setProcessing(false);
    }
  }

  if (success) {
    return (
      <div className="min-h-screen bg-gradient-to-b from-ocean-50 to-white flex items-center justify-center px-4 -mt-16">
        <Card className="max-w-lg w-full border-green-200 shadow-lg">
          <CardContent className="pt-10 pb-10 text-center">
            <div className="inline-flex items-center justify-center w-16 h-16 bg-green-100 rounded-full mb-6">
              <CheckCircle className="h-8 w-8 text-green-600" />
            </div>
            <h1 className="text-2xl font-bold text-ocean-900 mb-3">
              Payment Successful! 🎉
            </h1>
            <p className="text-ocean-500 mb-6">
              Your private tour is confirmed! A confirmation email with all the
              details has been sent to <strong>{tour.contact_email}</strong>.
            </p>
            {bookingRef && (
              <div className="bg-ocean-50 rounded-lg p-4 mb-6">
                <p className="text-sm text-ocean-500 mb-1">Booking Reference</p>
                <p className="text-xl font-bold text-ocean-700 font-mono">
                  {bookingRef}
                </p>
              </div>
            )}
            <Link href="/">
              <Button variant="cta">Back to Home</Button>
            </Link>
          </CardContent>
        </Card>
      </div>
    );
  }

  const grandTotal =
    (tour.total_price_cents ?? 0) + (tour.fees_cents ?? 0);

  return (
    <div className="min-h-screen bg-gradient-to-b from-ocean-50 to-white -mt-16">
      <div className="section-container py-8 sm:py-16">
        <div className="text-center mb-8">
          <h1 className="text-3xl font-bold text-ocean-900 mb-3">
            Complete Your Private Tour Payment
          </h1>
          <p className="text-ocean-500">
            Reference: <strong>{tour.booking_ref}</strong>
          </p>
        </div>

        <Card className="max-w-lg mx-auto border-ocean-100 shadow-sm">
          <CardContent className="pt-8 pb-8">
            {/* Order Summary */}
            <div className="mb-6">
              <h2 className="text-lg font-semibold text-ocean-900 mb-3">
                Order Summary
              </h2>
              <div className="bg-ocean-50 rounded-lg p-4 space-y-2 text-sm">
                <div className="flex justify-between">
                  <span className="text-ocean-500">Tour Date</span>
                  <span className="font-medium text-ocean-900">
                    {tour.confirmed_tour_date
                      ? new Date(
                          tour.confirmed_tour_date + "T12:00:00"
                        ).toLocaleDateString("en-US", {
                          weekday: "long",
                          month: "long",
                          day: "numeric",
                          year: "numeric",
                        })
                      : "TBD"}
                  </span>
                </div>
                <div className="flex justify-between">
                  <span className="text-ocean-500">Guests</span>
                  <span className="font-medium text-ocean-900">
                    {tour.adult_count} adult
                    {tour.adult_count !== 1 ? "s" : ""},{" "}
                    {tour.child_count} child
                    {tour.child_count !== 1 ? "ren" : ""}
                  </span>
                </div>
                <div className="border-t border-ocean-200 pt-2 flex justify-between">
                  <span className="text-ocean-500">Tour Price</span>
                  <span className="font-medium">
                    {formatCurrency(tour.total_price_cents / 100)}
                  </span>
                </div>
                {tour.fees_cents > 0 && (
                  <div className="flex justify-between">
                    <span className="text-ocean-500">Fees</span>
                    <span className="font-medium">
                      {formatCurrency(tour.fees_cents / 100)}
                    </span>
                  </div>
                )}
                <div className="border-t border-ocean-200 pt-2 flex justify-between">
                  <span className="text-lg font-bold text-ocean-900">
                    Total
                  </span>
                  <span className="text-lg font-bold text-ocean-900">
                    {formatCurrency(grandTotal / 100)}
                  </span>
                </div>
              </div>
            </div>

            {/* Payment Form */}
            <form onSubmit={handleSubmit} className="space-y-6">
              <div>
                <label className="block text-sm font-medium text-ocean-700 mb-2">
                  Card Details
                </label>
                <div className="border border-ocean-200 rounded-lg p-3 bg-white">
                  <CardElement
                    options={{
                      style: {
                        base: {
                          fontSize: "16px",
                          color: "#111827",
                          "::placeholder": { color: "#9ca3af" },
                        },
                      },
                    }}
                  />
                </div>
              </div>

              {error && (
                <div className="bg-red-50 border border-red-200 rounded-lg p-3 text-sm text-red-600 flex items-center gap-2">
                  <AlertCircle className="h-4 w-4 shrink-0" />
                  {error}
                </div>
              )}

              <Button
                type="submit"
                variant="cta"
                className="w-full"
                size="lg"
                disabled={processing || !stripe}
              >
                {processing ? (
                  <span className="flex items-center gap-2">
                    <Loader2 className="h-4 w-4 animate-spin" />
                    Processing...
                  </span>
                ) : (
                  <>
                    <Sparkles className="h-4 w-4 mr-2" />
                    Pay {formatCurrency(grandTotal / 100)}
                  </>
                )}
              </Button>
            </form>

            <p className="text-xs text-ocean-400 text-center mt-4">
              Your payment is secure and encrypted. We never store your card
              details.
            </p>
          </CardContent>
        </Card>
      </div>
    </div>
  );
}

export default function PrivateTourPayPage() {
  return (
    <React.Suspense
      fallback={
        <div className="min-h-screen flex items-center justify-center -mt-16">
          <Loader2 className="h-8 w-8 animate-spin text-ocean-500" />
        </div>
      }
    >
      <PrivateTourPayContent />
    </React.Suspense>
  );
}

function PrivateTourPayContent() {
  const searchParams = useSearchParams();
  const ref = searchParams.get("ref");
  const [tour, setTour] = useState<PrivateTourRequest | null>(null);
  const [loading, setLoading] = useState(true);
  const [notFound, setNotFound] = useState(false);

  useEffect(() => {
    if (!ref) {
      setNotFound(true);
      setLoading(false);
      return;
    }
    getPrivateTourByRef(ref)
      .then((data) => {
        if (
          data.request.status !== "confirmed" &&
          data.request.status !== "awaiting_payment"
        ) {
          setNotFound(true);
          return;
        }
        setTour(data.request);
      })
      .catch(() => setNotFound(true))
      .finally(() => setLoading(false));
  }, [ref]);

  if (loading) {
    return (
      <div className="min-h-screen flex items-center justify-center -mt-16">
        <Loader2 className="h-8 w-8 animate-spin text-ocean-500" />
      </div>
    );
  }

  if (notFound || !tour) {
    return (
      <div className="min-h-screen flex items-center justify-center px-4 -mt-16">
        <Card className="max-w-md w-full">
          <CardContent className="pt-10 pb-10 text-center">
            <AlertCircle className="h-12 w-12 text-ocean-300 mx-auto mb-4" />
            <h1 className="text-xl font-bold text-ocean-900 mb-2">
              Payment Link Not Found
            </h1>
            <p className="text-ocean-500 mb-6">
              This payment link may have expired or already been used. Please
              contact us if you need help.
            </p>
            <Link href="/">
              <Button variant="cta">Back to Home</Button>
            </Link>
          </CardContent>
        </Card>
      </div>
    );
  }

  return (
    <Elements stripe={stripePromise}>
      <PaymentForm tour={tour} />
    </Elements>
  );
}
