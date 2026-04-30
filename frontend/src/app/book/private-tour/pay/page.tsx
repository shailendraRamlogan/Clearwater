"use client";

import { useEffect, useState, useRef, Suspense } from "react";
import { useRouter, useSearchParams } from "next/navigation";
import { loadStripe } from "@stripe/stripe-js";
import { CardElement, useStripe, useElements, Elements } from "@stripe/react-stripe-js";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { lookupPrivateTour, initiatePrivateTourPayment, confirmPrivateTourPayment } from "@/lib/private-tour-service";
import { formatCurrency } from "@/lib/utils";
import type { PrivateTourRequest } from "@/types/booking";
import {
  CreditCard,
  CheckCircle,
  AlertCircle,
  Loader2,
  Sparkles,
} from "lucide-react";
import { toast } from "sonner";

const stripePromise = loadStripe(process.env.NEXT_PUBLIC_STRIPE_PUBLISHABLE_KEY || "");

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
  const submittedRef = useRef(false);

  useEffect(() => {
    if (!ref) {
      toast.error("No booking reference provided.");
      return;
    }
    lookupPrivateTour(ref)
      .then(({ request: req }) => {
        if (req.status !== "confirmed") {
          toast.error("This request is not ready for payment.");
          return;
        }
        setRequest(req);
        // Initiate payment
        return initiatePrivateTourPayment(req.booking_ref);
      })
      .then((result) => {
        if (result) {
          setClientSecret(result.client_secret);
        }
      })
      .catch((err) => {
        toast.error(err.message || "Could not load request.");
      })
      .finally(() => setLoading(false));
  }, [ref]);

  // Wait for stripe intent id from confirmCardPayment
  // Actually, we need to get the intent id from the confirm call
  // Let me restructure this properly:

  const handlePayFixed = async () => {
    if (!stripe || !elements || !clientSecret || !request || submittedRef.current) return;
    submittedRef.current = true;
    setProcessing(true);
    setStripeError("");

    try {
      const { error: stripeErr, paymentIntent } = await stripe.confirmCardPayment(clientSecret, {
        payment_method: { card: elements.getElement(CardElement)! },
      });

      if (stripeErr) {
        setStripeError(stripeErr.message || "Payment failed.");
        submittedRef.current = false;
        return;
      }

      await confirmPrivateTourPayment(request.booking_ref, paymentIntent.id);

      setPaymentDone(true);
      toast.success("Payment successful! Check your email for confirmation.");
    } catch (err: unknown) {
      const error = err as Error;
      setStripeError(error.message || "Payment failed.");
      submittedRef.current = false;
    } finally {
      setProcessing(false);
    }
  };

  if (loading) {
    return (
      <div className="section-container py-20 text-center">
        <Loader2 className="h-8 w-8 text-ocean-500 animate-spin mx-auto mb-4" />
        <p className="text-ocean-500">Loading your booking...</p>
      </div>
    );
  }

  if (paymentDone) {
    return (
      <div className="section-container py-8 sm:py-20">
        <div className="max-w-lg mx-auto text-center">
          <div className="inline-flex items-center justify-center w-16 h-16 bg-green-100 rounded-full mb-6">
            <CheckCircle className="h-8 w-8 text-green-500" />
          </div>
          <h1 className="text-3xl font-bold mb-4 text-ocean-900">
            Payment Successful!
          </h1>
          <p className="text-ocean-500 mb-8">
            Your private tour is booked! A confirmation email has been sent.
          </p>
          <Button onClick={() => router.push("/")}>
            Back to Home
          </Button>
        </div>
      </div>
    );
  }

  if (!request) {
    return (
      <div className="section-container py-20 text-center">
        <AlertCircle className="h-8 w-8 text-red-400 mx-auto mb-4" />
        <p className="text-ocean-500">Could not load your booking request.</p>
      </div>
    );
  }

  return (
    <div className="section-container py-8 sm:py-20">
      <div className="max-w-lg mx-auto">
        <div className="text-center mb-8">
          <div className="inline-flex items-center gap-2 bg-ocean-100 text-ocean-700 px-4 py-1.5 rounded-full text-sm font-medium mb-4">
            <Sparkles className="h-4 w-4" />
            Private Tour Payment
          </div>
          <h1 className="text-3xl font-bold text-ocean-900 mb-2">
            Complete Your Booking
          </h1>
          <p className="text-ocean-500">
            Reference: <span className="font-mono font-bold">{request.booking_ref}</span>
          </p>
        </div>

        <Card>
          <CardHeader>
            <CardTitle>Payment Details</CardTitle>
          </CardHeader>
          <CardContent className="space-y-6">
            {/* Summary */}
            <div className="bg-ocean-50 rounded-lg p-4 space-y-2 text-sm">
              {request.confirmed_tour_date && (
                <div className="flex justify-between">
                  <span className="text-ocean-400">Tour Date</span>
                  <span className="font-medium">
                    {new Date(request.confirmed_tour_date + "T12:00:00").toLocaleDateString("en-US", {
                      weekday: "long",
                      year: "numeric",
                      month: "long",
                      day: "numeric",
                    })}
                  </span>
                </div>
              )}
              <div className="flex justify-between">
                <span className="text-ocean-400">Party</span>
                <span className="font-medium">
                  {request.adult_count} adult{request.adult_count !== 1 ? "s" : ""}
                  {request.child_count > 0 && `, ${request.child_count} child${request.child_count !== 1 ? "ren" : ""}`}
                </span>
              </div>
              <div className="flex justify-between">
                <span className="text-ocean-400">Tour Price</span>
                <span>{formatCurrency((request.total_price_cents ?? 0) / 100)}</span>
              </div>
              {(request.fees_cents ?? 0) > 0 && (
                <div className="flex justify-between">
                  <span className="text-ocean-400">Fees</span>
                  <span>{formatCurrency((request.fees_cents ?? 0) / 100)}</span>
                </div>
              )}
              <div className="border-t border-ocean-200 pt-2 flex justify-between font-bold text-base">
                <span>Total</span>
                <span className="text-ocean-700">
                  {formatCurrency(((request.total_price_cents ?? 0) + (request.fees_cents ?? 0)) / 100)}
                </span>
              </div>
            </div>

            {/* Card input */}
            <div className="space-y-2">
              <label className="text-sm font-medium">Card Details</label>
              <div className="border border-ocean-200 rounded-lg p-3">
                <CardElement
                  options={{
                    style: {
                      base: {
                        fontSize: "16px",
                        color: "#1e3a5f",
                        "::placeholder": { color: "#94a3b8" },
                      },
                    },
                  }}
                />
              </div>
            </div>

            {stripeError && (
              <div className="flex items-center gap-2 text-red-500 text-sm">
                <AlertCircle className="h-4 w-4 shrink-0" />
                {stripeError}
              </div>
            )}

            <Button
              variant="cta"
              className="w-full"
              size="lg"
              disabled={!stripe || processing}
              onClick={handlePayFixed}
            >
              {processing ? (
                <>
                  <Loader2 className="h-4 w-4 mr-2 animate-spin" />
                  Processing...
                </>
              ) : (
                <>
                  <CreditCard className="h-4 w-4 mr-2" />
                  Pay {formatCurrency(((request.total_price_cents ?? 0) + (request.fees_cents ?? 0)) / 100)}
                </>
              )}
            </Button>
          </CardContent>
        </Card>
      </div>
    </div>
  );
}

function PaymentFormWrapper() {
  return (
    <Elements stripe={stripePromise}>
      <PaymentFormInner />
    </Elements>
  );
}

export default function PrivateTourPaymentPage() {
  return (
    <Suspense fallback={
      <div className="section-container py-20 text-center">
        <Loader2 className="h-8 w-8 text-ocean-500 animate-spin mx-auto mb-4" />
        <p className="text-ocean-500">Loading...</p>
      </div>
    }>
      <PaymentFormWrapper />
    </Suspense>
  );
}
