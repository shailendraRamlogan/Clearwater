"use client";

import { useEffect, useState, useRef } from "react";
import { format } from "date-fns";
import {
  ChevronLeft,
  ChevronRight,
  Calendar,
  Clock,
  Users,
  CreditCard,
  Check,
  Minus,
  Plus,
  Sparkles,
  Camera,
  Wine,
  Fish,
  PartyPopper,
} from "lucide-react";
import { loadStripe } from "@stripe/stripe-js";
import { CardElement, Elements, useStripe, useElements } from "@stripe/react-stripe-js";
import { useBookingStore } from "@/stores/booking-store";
import {
  getAvailability,
  createBooking,
  getTicketTypes,
  getAddons,
  getPricing,
  confirmPayment,
} from "@/lib/booking-service";
import { formatCurrency, formatTime } from "@/lib/utils";
import { toast } from "sonner";
import { useRouter } from "next/navigation";
import { cn } from "@/lib/utils";

const stripePromise = loadStripe(
  process.env.NEXT_PUBLIC_STRIPE_PUBLISHABLE_KEY || ""
);

const FEATURE_ICONS: Record<string, React.ComponentType<{ className?: string }>> = {
  Camera, Citrus: Sparkles, Beer: Wine, Grape: Wine, Wine, Cookie: Sparkles, GlassWater: Fish, PartyPopper, Sparkles,
};

const BOOKING_STEPS = [
  { icon: Calendar, label: "Date" },
  { icon: Clock, label: "Time" },
  { icon: Users, label: "Tickets" },
  { icon: Users, label: "Details" },
  { icon: Sparkles, label: "Extras" },
  { icon: CreditCard, label: "Pay" },
];

function BookingForm() {
  const store = useBookingStore();
  const router = useRouter();
  const stripe = useStripe();
  const elements = useElements();

  const [loading, setLoading] = useState(false);
  const [slotsLoading, setSlotsLoading] = useState(false);
  const [stripeError, setStripeError] = useState("");
  const [showPayment, setShowPayment] = useState(false);
  const [submitted, setSubmitted] = useState(false);
  const [expandedTypes, setExpandedTypes] = useState<Record<string, boolean>>({});
  const [confirmEmail, setConfirmEmail] = useState("");
  const lastFetchedDate = useRef<string | null>(null);

  useEffect(() => {
    getTicketTypes()
      .then((types) => { if (types.length > 0) store.setTicketTypes(types); })
      .catch(() => {});
    getAddons()
      .then((addons) => { if (addons.length > 0) store.setAddons(addons); })
      .catch(() => {});
    getPricing()
      .then((p) => { if (p?.fees) store.setPricingFees(p.fees); })
      .catch(() => {});
    return () => { useBookingStore.getState().reset(); };
  }, []);

  useEffect(() => {
    if (store.selectedDate && store.currentStep >= 0) {
      const dateStr = format(store.selectedDate, "yyyy-MM-dd");
      if (lastFetchedDate.current === dateStr) return;
      lastFetchedDate.current = dateStr;
      setSlotsLoading(true);
      getAvailability(dateStr)
        .then((slots) => store.setAvailableSlots(slots.filter((s) => !s.is_blocked && s.remaining_capacity > 0)))
        .catch(() => store.setAvailableSlots([]))
        .finally(() => setSlotsLoading(false));
    }
  }, [store.selectedDate, store.currentStep]);

  useEffect(() => {
    for (const type of store.ticketTypes) {
      if ((store.ticketCounts[type.id] ?? 0) === 0) {
        setExpandedTypes((prev) => {
          if (!prev[type.id]) return prev;
          const next = { ...prev };
          delete next[type.id];
          return next;
        });
      }
    }
  }, [store.ticketCounts, store.ticketTypes]);

  const handleBooking = async () => {
    if (!store.selectedDate || !store.selectedSlot) return;
    const p = store.guests[0];
    if (!p.first_name || !p.last_name || !p.email || !p.phone) {
      toast.error("Please complete your details.");
      return;
    }
    if (confirmEmail !== p.email) {
      toast.error("Email addresses don't match.");
      return;
    }
    if (submitted) return;
    setSubmitted(true);
    setLoading(true);
    setStripeError("");

    try {
      const adultType = store.ticketTypes.find((t) => t.name.toLowerCase() === "adult");
      const childType = store.ticketTypes.find((t) => t.name.toLowerCase() === "child");
      const addonsPayload = Object.entries(store.selectedAddons)
        .filter(([, qty]) => qty > 0)
        .map(([addonId, qty]) => ({ addon_id: addonId, quantity: qty }));

      const result = await createBooking({
        tour_date: format(store.selectedDate, "yyyy-MM-dd"),
        time_slot_id: store.selectedSlot.id,
        adult_count: adultType ? (store.ticketCounts[adultType.id] ?? 0) : 0,
        child_count: childType ? (store.ticketCounts[childType.id] ?? 0) : 0,
        addons: addonsPayload,
        special_comment: store.specialComment || "",
        guest: store.guests[0],
        guests: [],
      });

      const bookingRef = result.booking?.booking_ref || result.booking?.booking_reference || result.booking?.id;
      const clientSecret = result.payment?.client_secret;

      if (!clientSecret) {
        toast.success("Booking created!");
        router.push(`/book/confirmation?ref=${bookingRef}&email=${encodeURIComponent(store.guests[0].email)}`);
        return;
      }

      if (!stripe || !elements) { setStripeError("Payment unavailable."); setSubmitted(false); return; }
      const cardElement = elements.getElement(CardElement);
      if (!cardElement) { setStripeError("Card element not found."); setSubmitted(false); return; }

      const { error: stripeErr } = await stripe.confirmCardPayment(clientSecret, { payment_method: { card: cardElement } });
      if (stripeErr) { setStripeError(stripeErr.message || "Payment failed."); setSubmitted(false); return; }

      if (result.payment?.stripe_intent_id && bookingRef) {
        try { await confirmPayment(bookingRef, result.payment.stripe_intent_id); } catch {}
      }

      toast.success("Booking confirmed!");
      router.push(`/book/confirmation?ref=${bookingRef}&email=${encodeURIComponent(store.guests[0].email)}`);
    } catch (err: unknown) {
      setSubmitted(false);
      const error = err as Error & { status?: number };
      if (error.status === 409) toast.error("This slot just filled up. Please select a different time.");
      else toast.error(error.message || "Booking failed.");
    } finally {
      setLoading(false);
    }
  };

  const fees = store.getFees();
  const grandTotal = store.getGrandTotal();
  const step = store.currentStep;

  const canProceed = () => {
    if (step === 0) return !!store.selectedDate;
    if (step === 1) return !!store.selectedSlot;
    if (step === 2) return store.totalGuests() > 0;
    if (step === 3) {
      const g = store.guests[0];
      return !!(g?.first_name && g?.last_name && g?.email && g?.phone && confirmEmail === g.email);
    }
    return true;
  };

  return (
    <div className="min-h-screen pt-16 lg:pt-20 pb-8 bg-sand-50">
      {/* Progress */}
      <div className="section-container py-6 lg:py-8">
        <div className="flex items-center justify-center gap-2 sm:gap-4 max-w-lg mx-auto">
          {BOOKING_STEPS.map((s, i) => {
            const isActive = step === i;
            const isComplete = step > i;
            return (
              <div key={i} className="flex items-center gap-2 sm:gap-4">
                <div className="flex flex-col items-center">
                  <div className={cn(
                    "w-10 h-10 sm:w-12 sm:h-12 rounded-full flex items-center justify-center transition-all duration-200",
                    isComplete ? "bg-brand-700 text-white" :
                    isActive ? "bg-brand-700 text-white ring-4 ring-brand-100" :
                    "bg-brand-100 text-brand-400"
                  )}>
                    {isComplete ? <Check className="w-5 h-5" /> : <s.icon className="w-5 h-5" />}
                  </div>
                  <span className={cn("text-[10px] sm:text-xs mt-1 font-medium", isActive ? "text-brand-700" : "text-brand-400")}>
                    {s.label}
                  </span>
                </div>
                {i < BOOKING_STEPS.length - 1 && (
                  <div className={cn("h-px flex-1 max-w-[40px] sm:max-w-[60px] mb-5", step > i ? "bg-brand-700" : "bg-brand-200")} />
                )}
              </div>
            );
          })}
        </div>
      </div>

      {/* Step Content */}
      <div className="section-container">
        <div className="max-w-2xl mx-auto">

          {/* Step 0: Date */}
          {step === 0 && (
            <div className="bg-white rounded-2xl border border-brand-100 p-6 sm:p-8">
              <h2 className="font-display text-xl sm:text-2xl text-brand-900 mb-6">Pick your date</h2>
              <input
                type="date"
                value={store.selectedDate ? format(store.selectedDate, "yyyy-MM-dd") : ""}
                onChange={(e) => {
                  const d = e.target.value ? new Date(e.target.value + "T12:00:00") : undefined;
                  store.setSelectedDate(d);
                }}
                min={format(new Date(), "yyyy-MM-dd")}
                className="w-full h-12 px-4 rounded-xl border border-brand-200 text-brand-900 focus:ring-2 focus:ring-brand-500 focus:border-brand-500 outline-none text-base"
              />
              <div className="flex justify-end mt-8">
                <button onClick={() => store.selectedDate && store.nextStep()} disabled={!store.selectedDate} className="btn-primary-lg gap-2 disabled:opacity-40 disabled:cursor-not-allowed">
                  Continue <ChevronRight className="w-5 h-5" />
                </button>
              </div>
            </div>
          )}

          {/* Step 1: Time */}
          {step === 1 && (
            <div className="bg-white rounded-2xl border border-brand-100 p-6 sm:p-8">
              <h2 className="font-display text-xl sm:text-2xl text-brand-900 mb-2">Available times</h2>
              <p className="text-sm text-brand-500 mb-6">{store.selectedDate && format(store.selectedDate, "EEEE, MMMM d, yyyy")}</p>
              {slotsLoading ? (
                <div className="flex items-center justify-center py-8 text-brand-400 text-sm">Loading times...</div>
              ) : store.availableSlots.length === 0 ? (
                <p className="text-brand-400 text-sm py-4">No available times for this date.</p>
              ) : (
                <div className="grid grid-cols-2 sm:grid-cols-3 gap-3">
                  {store.availableSlots.map((slot) => (
                    <button key={slot.id} onClick={() => store.setSelectedSlot(slot)}
                      className={cn(
                        "p-4 rounded-xl border-2 text-center transition-all duration-200",
                        store.selectedSlot?.id === slot.id ? "border-brand-700 bg-brand-50 text-brand-900" : "border-brand-100 hover:border-brand-300 text-brand-700"
                      )}>
                      <span className="text-base font-semibold">{formatTime(slot.start_time)}</span>
                      <span className="block text-xs text-brand-500 mt-1">{slot.boat_name}</span>
                      <span className="block text-xs text-brand-400 mt-0.5">{slot.remaining_capacity} spots left</span>
                    </button>
                  ))}
                </div>
              )}
              <div className="flex justify-between mt-8">
                <button onClick={() => store.prevStep()} className="btn-outline-sm gap-1"><ChevronLeft className="w-4 h-4" /> Back</button>
                <button onClick={() => store.selectedSlot && store.nextStep()} disabled={!store.selectedSlot} className="btn-primary-lg gap-2 disabled:opacity-40">
                  Continue <ChevronRight className="w-5 h-5" />
                </button>
              </div>
            </div>
          )}

          {/* Step 2: Tickets */}
          {step === 2 && (
            <div className="bg-white rounded-2xl border border-brand-100 p-6 sm:p-8">
              <h2 className="font-display text-xl sm:text-2xl text-brand-900 mb-6">Choose your tickets</h2>
              <div className="space-y-4">
                {store.ticketTypes.sort((a, b) => a.sort_order - b.sort_order).map((type) => {
                  const count = store.ticketCounts[type.id] ?? 0;
                  const expanded = expandedTypes[type.id];
                  const price = type.price_cents / 100;
                  return (
                    <div key={type.id} className="rounded-xl border border-brand-100 overflow-hidden">
                      <div className={cn("flex items-center justify-between p-4", count > 0 ? "bg-brand-50/50" : "")}>
                        <div className="flex-1 min-w-0 cursor-pointer" onClick={() => setExpandedTypes((p) => ({ ...p, [type.id]: !p[type.id] }))}>
                          <div className="flex items-center gap-2 mb-0.5">
                            <span className="font-semibold text-brand-900">{type.name}</span>
                            <span className="text-xs text-brand-400">{type.description}</span>
                          </div>
                          <span className="text-sm font-semibold text-brand-700">${price.toFixed(2)}</span>
                          {type.features && type.features.length > 0 && (
                            <span className="text-[10px] text-brand-400 ml-2">{expanded ? "▲ hide" : "▼ includes"}</span>
                          )}
                        </div>
                        <div className="flex items-center gap-3">
                          <button onClick={() => store.updateTicketCount(type.id, count - 1)} className="w-9 h-9 rounded-lg border border-brand-200 flex items-center justify-center text-brand-500 hover:bg-brand-50">
                            <Minus className="w-4 h-4" />
                          </button>
                          <span className="w-6 text-center text-lg font-semibold text-brand-900">{count}</span>
                          <button onClick={() => store.updateTicketCount(type.id, count + 1)} className="w-9 h-9 rounded-lg border border-brand-200 flex items-center justify-center text-brand-500 hover:bg-brand-50">
                            <Plus className="w-4 h-4" />
                          </button>
                        </div>
                      </div>
                      {expanded && type.features && (
                        <div className="px-4 pb-4 border-t border-brand-50">
                          <div className="space-y-1.5 pt-3">
                            {type.features.sort((a, b) => a.sort_order - b.sort_order).map((f, fi) => {
                              const FIcon = FEATURE_ICONS[f.icon] || Check;
                              return (
                                <div key={fi} className="flex items-center gap-2">
                                  <FIcon className="w-3.5 h-3.5 text-brand-400" />
                                  <span className="text-sm text-brand-600">{f.label}</span>
                                </div>
                              );
                            })}
                          </div>
                        </div>
                      )}
                    </div>
                  );
                })}
              </div>
              <p className="text-sm text-brand-400 mt-4 text-center">
                {store.totalGuests()} guest{store.totalGuests() !== 1 ? "s" : ""} selected
              </p>
              <div className="flex justify-between mt-8">
                <button onClick={() => store.prevStep()} className="btn-outline-sm gap-1"><ChevronLeft className="w-4 h-4" /> Back</button>
                <button onClick={() => store.totalGuests() > 0 && store.nextStep()} disabled={store.totalGuests() === 0} className="btn-primary-lg gap-2 disabled:opacity-40">
                  Continue <ChevronRight className="w-5 h-5" />
                </button>
              </div>
            </div>
          )}

          {/* Step 3: Guest Details */}
          {step === 3 && (
            <div className="bg-white rounded-2xl border border-brand-100 p-6 sm:p-8">
              <h2 className="font-display text-xl sm:text-2xl text-brand-900 mb-6">Your details</h2>
              <div className="space-y-3">
                <div>
                  <label className="block text-sm font-medium text-brand-700 mb-1">First name</label>
                  <input value={store.guests[0]?.first_name || ""} onChange={(e) => store.updateGuest(0, "first_name", e.target.value)}
                    className="w-full h-12 px-4 rounded-xl border border-brand-200 text-brand-900 focus:ring-2 focus:ring-brand-500 outline-none text-base" />
                </div>
                <div>
                  <label className="block text-sm font-medium text-brand-700 mb-1">Last name</label>
                  <input value={store.guests[0]?.last_name || ""} onChange={(e) => store.updateGuest(0, "last_name", e.target.value)}
                    className="w-full h-12 px-4 rounded-xl border border-brand-200 text-brand-900 focus:ring-2 focus:ring-brand-500 outline-none text-base" />
                </div>
                <div>
                  <label className="block text-sm font-medium text-brand-700 mb-1">Email</label>
                  <input type="email" value={store.guests[0]?.email || ""} onChange={(e) => store.updateGuest(0, "email", e.target.value)}
                    className="w-full h-12 px-4 rounded-xl border border-brand-200 text-brand-900 focus:ring-2 focus:ring-brand-500 outline-none text-base" />
                </div>
                <div>
                  <label className="block text-sm font-medium text-brand-700 mb-1">Confirm email</label>
                  <input type="email" value={confirmEmail} onChange={(e) => setConfirmEmail(e.target.value)}
                    className={cn(
                      "w-full h-12 px-4 rounded-xl border text-brand-900 focus:ring-2 outline-none text-base",
                      confirmEmail && confirmEmail !== store.guests[0]?.email ? "border-red-300 focus:ring-red-300" : "border-brand-200 focus:ring-brand-500"
                    )} />
                </div>
                <div>
                  <label className="block text-sm font-medium text-brand-700 mb-1">Phone</label>
                  <input type="tel" placeholder="+1 242 ..." value={store.guests[0]?.phone || ""} onChange={(e) => store.updateGuest(0, "phone", e.target.value)}
                    className="w-full h-12 px-4 rounded-xl border border-brand-200 text-brand-900 focus:ring-2 focus:ring-brand-500 outline-none text-base" />
                </div>

                                {/* Special comment */}
                <div className="pt-2">
                  <label className="block text-sm font-medium text-brand-700 mb-1">Special requests (optional)</label>
                  <textarea value={store.specialComment} onChange={(e) => store.setSpecialComment(e.target.value)} rows={2}
                    className="w-full px-4 py-2 rounded-xl border border-brand-200 text-brand-900 focus:ring-2 focus:ring-brand-500 outline-none text-sm resize-none" />
                </div>
              </div>
              <div className="flex justify-between mt-8">
                <button onClick={() => store.prevStep()} className="btn-outline-sm gap-1"><ChevronLeft className="w-4 h-4" /> Back</button>
                <button onClick={() => canProceed() && store.nextStep()} disabled={!canProceed()} className="btn-primary-lg gap-2 disabled:opacity-40">
                  Continue <ChevronRight className="w-5 h-5" />
                </button>
              </div>
            </div>
          )}

          {/* Step 4: Add-ons */}
          {step === 4 && (
            <div className="bg-white rounded-2xl border border-brand-100 p-6 sm:p-8">
              <h2 className="font-display text-xl sm:text-2xl text-brand-900 mb-2">Enhance your trip</h2>
              <p className="text-sm text-brand-500 mb-6">Optional add-ons for a better experience</p>
              {store.addons.length === 0 ? (
                <p className="text-brand-400 text-sm text-center py-8">No add-ons available at this time.</p>
              ) : (
                <div className="space-y-3">
                  {store.addons.map((addon) => {
                    const qty = store.selectedAddons[addon.id] ?? 0;
                    const name = addon.title || addon.name || "Add-on";
                    const price = addon.price_cents / 100;
                    return (
                      <div key={addon.id} className={cn(
                        "flex items-center justify-between p-4 rounded-xl border transition-all",
                        qty > 0 ? "border-brand-300 bg-brand-50/50" : "border-brand-100"
                      )}>
                        <div className="flex-1 min-w-0 mr-4">
                          <p className="font-semibold text-brand-900">{name}</p>
                          {addon.description && <p className="text-sm text-brand-500 mt-0.5">{addon.description}</p>}
                          <p className="text-sm font-semibold text-brand-700 mt-1">${price.toFixed(2)}</p>
                        </div>
                        <div className="flex items-center gap-3">
                          {qty > 0 && (
                            <button onClick={() => store.updateAddon(addon.id, qty - 1)} className="w-9 h-9 rounded-lg border border-brand-200 flex items-center justify-center text-brand-500 hover:bg-brand-50">
                              <Minus className="w-4 h-4" />
                            </button>
                          )}
                          {qty > 0 && <span className="w-5 text-center text-lg font-semibold text-brand-900">{qty}</span>}
                          <button onClick={() => store.updateAddon(addon.id, qty + 1)} disabled={qty >= (addon.max_quantity || 10)}
                            className="w-9 h-9 rounded-lg border border-brand-200 flex items-center justify-center text-brand-500 hover:bg-brand-50 disabled:opacity-30">
                            <Plus className="w-4 h-4" />
                          </button>
                        </div>
                      </div>
                    );
                  })}
                </div>
              )}
              <div className="flex justify-between mt-8">
                <button onClick={() => store.prevStep()} className="btn-outline-sm gap-1"><ChevronLeft className="w-4 h-4" /> Back</button>
                <button onClick={() => store.nextStep()} className="btn-primary-lg gap-2">
                  Continue <ChevronRight className="w-5 h-5" />
                </button>
              </div>
            </div>
          )}

          {/* Step 5: Review & Pay */}
          {step === 5 && (
            <div className="bg-white rounded-2xl border border-brand-100 p-6 sm:p-8">
              <h2 className="font-display text-xl sm:text-2xl text-brand-900 mb-6">Review & Pay</h2>
              <div className="space-y-2 text-sm">
                <div className="flex justify-between py-2 border-b border-brand-100">
                  <span className="text-brand-600">Date</span>
                  <span className="font-medium text-brand-900">{store.selectedDate && format(store.selectedDate, "EEEE, MMM d")}</span>
                </div>
                <div className="flex justify-between py-2 border-b border-brand-100">
                  <span className="text-brand-600">Time</span>
                  <span className="font-medium text-brand-900">{store.selectedSlot && formatTime(store.selectedSlot.start_time)} — {store.selectedSlot?.boat_name}</span>
                </div>
                <div className="flex justify-between py-2 border-b border-brand-100">
                  <span className="text-brand-600">Guests</span>
                  <span className="font-medium text-brand-900">{store.totalGuests()} guest{store.totalGuests() !== 1 ? "s" : ""}</span>
                </div>
                <div className="flex justify-between py-2 border-b border-brand-100">
                  <span className="text-brand-600">Name</span>
                  <span className="font-medium text-brand-900">{store.guests[0]?.first_name} {store.guests[0]?.last_name}</span>
                </div>
                <div className="flex justify-between py-2 border-b border-brand-100">
                  <span className="text-brand-600">Email</span>
                  <span className="font-medium text-brand-900">{store.guests[0]?.email}</span>
                </div>
              </div>

              {/* Line items */}
              <div className="mt-4 space-y-1">
                {store.ticketTypes.map((type) => {
                  const count = store.ticketCounts[type.id] ?? 0;
                  if (count === 0) return null;
                  return (
                    <div key={type.id} className="flex justify-between text-sm">
                      <span className="text-brand-500">{count}× {type.name}</span>
                      <span className="text-brand-700">${((count * type.price_cents) / 100).toFixed(2)}</span>
                    </div>
                  );
                })}
                {Object.entries(store.selectedAddons).filter(([, q]) => q > 0).map(([id, qty]) => (
                  <div key={id} className="flex justify-between text-sm">
                    <span className="text-brand-500">{qty}× {store.getAddonName(id)}</span>
                    <span className="text-brand-700">${((qty * (store.addons.find(a => a.id === id)?.price_cents || 0)) / 100).toFixed(2)}</span>
                  </div>
                ))}
              </div>

              {/* Fees */}
              {fees.length > 0 && (
                <div className="mt-3 space-y-1 border-t border-brand-100 pt-3">
                  {fees.map((fee, i) => (
                    <div key={i} className="flex justify-between text-sm">
                      <span className="text-brand-400">{fee.name}</span>
                      <span className="text-brand-600">${(fee.amount / 100).toFixed(2)}</span>
                    </div>
                  ))}
                </div>
              )}

              <div className="p-4 bg-brand-50 rounded-xl mt-4 mb-6">
                <div className="flex justify-between items-center">
                  <span className="text-sm font-medium text-brand-700">Total</span>
                  <span className="text-2xl font-bold text-brand-900">{formatCurrency(grandTotal / 100)}</span>
                </div>
              </div>

              {!showPayment ? (
                <button onClick={() => setShowPayment(true)} className="btn-primary-lg w-full gap-2">
                  Pay {formatCurrency(grandTotal / 100)}
                </button>
              ) : (
                <div className="space-y-4">
                  <h3 className="font-semibold text-brand-900">Card Details</h3>
                  <div className="rounded-xl border border-brand-200 p-4">
                    <CardElement options={{ style: { base: { fontSize: "16px", color: "#003038", "::placeholder": { color: "#94a3b8" } } } }} />
                  </div>
                  {stripeError && (
                    <div className="bg-red-50 border border-red-200 text-red-700 rounded-xl px-4 py-3 text-sm">{stripeError}</div>
                  )}
                  <button onClick={handleBooking} disabled={loading || submitted} className="btn-primary-lg w-full">
                    {loading ? "Processing..." : "Confirm Payment"}
                  </button>
                </div>
              )}

              <div className="flex justify-start mt-6">
                <button onClick={() => { store.prevStep(); setShowPayment(false); }} className="btn-outline-sm gap-1">
                  <ChevronLeft className="w-4 h-4" /> Back
                </button>
              </div>
            </div>
          )}
        </div>
      </div>
    </div>
  );
}

export default function BookPage() {
  return (
    <Elements stripe={stripePromise}>
      <BookingForm />
    </Elements>
  );
}
