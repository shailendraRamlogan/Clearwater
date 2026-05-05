"use client";

import { useState, useEffect, useRef, Suspense } from "react";
import { useSearchParams } from "next/navigation";
import { format } from "date-fns";
import {
  ChevronRight,
  ChevronLeft,
  Menu,
  X,
  Camera,
  Wine,
  Users,
  Clock,
  Fish,
  Minus,
  Plus,
  Check,
  Calendar,
  CreditCard,
  Sparkles,
  Phone,
  PartyPopper,
  Trash2,
  Ship,
  Crown,
  Sun,
  Sunrise,
  Baby,
  Heart,
  Send,
  Loader2,
} from "lucide-react";
import { loadStripe } from "@stripe/stripe-js";
import { CardElement, Elements, useStripe, useElements } from "@stripe/react-stripe-js";
import Link from "next/link";
import { useBookingStore } from "@/stores/booking-store";
import { usePrivateTourStore, type PrivateTourState } from "@/stores/private-tour-store";
import {
  getAvailability,
  createBooking,
  getTicketTypes,
  getAddons,
  getPricing,
  confirmPayment,
} from "@/lib/booking-service";
import { getPrivateTourAddons, submitPrivateTourRequest } from "@/lib/private-tour-service";
import { formatCurrency, formatTime } from "@/lib/utils";
import { toast } from "sonner";
import { useRouter } from "next/navigation";
import { cn } from "@/lib/utils";

const stripePromise = loadStripe(
  process.env.NEXT_PUBLIC_STRIPE_PUBLISHABLE_KEY || ""
);

const NAV_LINKS = [
  { label: "About", href: "/about" },
  { label: "Gallery", href: "/gallery" },
  { label: "FAQ", href: "/faq" },
  { label: "Contact", href: "/contact" },
];

const PILLS = [
  { icon: Camera, label: "Pro Photos", color: "from-cyan-400/20 to-cyan-500/10", border: "border-cyan-400/30", text: "text-cyan-300" },
  { icon: Wine, label: "Island Drinks", color: "from-amber-400/20 to-amber-500/10", border: "border-amber-400/30", text: "text-amber-300" },
  { icon: Users, label: "All Ages", color: "from-emerald-400/20 to-emerald-500/10", border: "border-emerald-400/30", text: "text-emerald-300" },
  { icon: Fish, label: "Marine Life", color: "from-sky-400/20 to-sky-500/10", border: "border-sky-400/30", text: "text-sky-300" },
  { icon: Clock, label: "1h 45m", color: "from-rose-400/20 to-rose-500/10", border: "border-rose-400/30", text: "text-rose-300" },
];

const FEATURE_ICONS: Record<string, React.ComponentType<{ className?: string }>> = {
  Camera, Citrus: Wine, Beer: Wine, Grape: Wine, Wine, Cookie: Sparkles, GlassWater: Fish, PartyPopper, Sparkles,
};

/* ─── VIEW TYPES ─── */
type View = "landing" | "tour-select" | "shared-booking" | "private-tour";

/* ─── MAIN APP SHELL ─── */
function AppContent() {
  const params = useSearchParams();
  const [view, setView] = useState<View>(params.get("book") === "true" ? "tour-select" : "landing");
  const [mobileMenuOpen, setMobileMenuOpen] = useState(false);

  return (
    <div className="relative h-[100dvh] w-screen overflow-hidden bg-black">
      {/* Video Background */}
      <video
        autoPlay
        muted
        loop
        playsInline
        className="absolute inset-0 w-full h-full object-cover"
      >
        <source src="/hero-video.mp4" type="video/mp4" />
      </video>

      {/* Dark overlay */}
      <div className="absolute inset-0 bg-gradient-to-br from-black/50 via-black/40 to-brand-900/60" />

      {/* Subtle vignette */}
      <div className="absolute inset-0" style={{
        background: "radial-gradient(ellipse at center, transparent 50%, rgba(0,0,0,0.4) 100%)"
      }} />

      {/* ─── SINGLE NAV ─── */}
      <nav className="absolute top-0 left-0 right-0 z-30">
        <div className="flex items-center justify-between px-6 lg:px-10 h-16 lg:h-20">
          <Link href="/" className="flex-shrink-0">
            <img
              src="https://clearboatbahamas.com/wp-content/uploads/2024/06/CB-png-2.png"
              alt="Clear Boat Bahamas"
              className="h-8 lg:h-10 w-auto brightness-0 invert"
            />
          </Link>

          <div className="hidden lg:flex items-center gap-6">
            {NAV_LINKS.map((link) => (
              <Link
                key={link.label}
                href={link.href}
                className="text-sm font-medium text-white/70 hover:text-white transition-colors"
              >
                {link.label}
              </Link>
            ))}
          </div>

          <div className="flex items-center gap-4">
            <a href="tel:+12428128687" className="hidden lg:flex items-center gap-1.5 text-sm text-white/50 hover:text-white/80 transition-colors">
              <Phone className="w-3.5 h-3.5" />
              <span className="hidden xl:inline">Call</span>
            </a>
            <button
              onClick={() => setMobileMenuOpen(!mobileMenuOpen)}
              className="lg:hidden p-2 text-white/80 hover:text-white"
              aria-label="Toggle menu"
            >
              {mobileMenuOpen ? <X className="w-5 h-5" /> : <Menu className="w-5 h-5" />}
            </button>
          </div>
        </div>

        <div
          className={cn(
            "lg:hidden overflow-hidden transition-all duration-300 bg-black/80 backdrop-blur-lg",
            mobileMenuOpen ? "max-h-60 border-t border-white/10" : "max-h-0"
          )}
        >
          <div className="px-6 py-4 space-y-1">
            {NAV_LINKS.map((link) => (
              <Link
                key={link.label}
                href={link.href}
                onClick={() => setMobileMenuOpen(false)}
                className="block text-sm font-medium text-white/80 hover:text-white px-3 py-2.5 rounded-lg transition-colors"
              >
                {link.label}
              </Link>
            ))}
          </div>
        </div>
      </nav>

      {/* ─── VIEWS ─── */}
      <div className={cn(
        "absolute inset-0 z-10 transition-all duration-700 ease-[cubic-bezier(0.16,1,0.3,1)]",
        view === "landing" ? "opacity-100 translate-y-0" : "opacity-0 -translate-y-8 pointer-events-none"
      )}>
        <LandingView onBookNow={() => setView("tour-select")} />
      </div>

      <div className={cn(
        "absolute inset-0 z-10 transition-all duration-700 ease-[cubic-bezier(0.16,1,0.3,1)]",
        view === "tour-select" ? "opacity-100 translate-y-0 z-20" : "opacity-0 translate-y-8 pointer-events-none"
      )}>
        <TourSelectView
          onSelectShared={() => setView("shared-booking")}
          onSelectPrivate={() => setView("private-tour")}
          onBack={() => setView("landing")}
        />
      </div>

      <div className={cn(
        "absolute inset-0 z-10 transition-all duration-700 ease-[cubic-bezier(0.16,1,0.3,1)]",
        view === "shared-booking" ? "opacity-100 translate-y-0 z-20" : "opacity-0 translate-y-8 pointer-events-none"
      )}>
        <BookingView onBack={() => setView("tour-select")} />
      </div>

      <div className={cn(
        "absolute inset-0 z-10 transition-all duration-700 ease-[cubic-bezier(0.16,1,0.3,1)]",
        view === "private-tour" ? "opacity-100 translate-y-0 z-20" : "opacity-0 translate-y-8 pointer-events-none"
      )}>
        <PrivateTourView onBack={() => setView("tour-select")} />
      </div>
    </div>
  );
}

/* ─── LANDING VIEW ─── */
function LandingView({ onBookNow }: { onBookNow: () => void }) {
  const [visible, setVisible] = useState(false);

  useEffect(() => {
    const t = setTimeout(() => setVisible(true), 200);
    return () => clearTimeout(t);
  }, []);

  return (
    <div className="flex flex-col justify-end h-full pb-12 lg:pb-20 px-6 lg:px-10">
      <div className="max-w-7xl mx-auto w-full">
        {/* Desktop */}
        <div className="hidden lg:block">
          <div className={cn(
            "transition-all duration-1000 delay-200",
            visible ? "opacity-100 translate-y-0" : "opacity-0 translate-y-6"
          )}>
            <p className="text-gold-400 text-sm font-semibold tracking-[0.2em] uppercase mb-4">
              The Bahamas&apos; First 100% Clear Boat Tour
            </p>
          </div>

          <h1 className={cn(
            "font-display text-6xl xl:text-7xl text-white leading-[1.05] mb-5 max-w-2xl transition-all duration-1000 delay-300",
            visible ? "opacity-100 translate-y-0" : "opacity-0 translate-y-6"
          )}>
            Clear Boat<br />Bahamas
          </h1>

          <p className={cn(
            "text-lg text-white/60 leading-relaxed max-w-md mb-8 transition-all duration-1000 delay-[400ms]",
            visible ? "opacity-100 translate-y-0" : "opacity-0 translate-y-6"
          )}>
            Glide over crystal-clear waters in our transparent boat.
            Photos, drinks, and unforgettable memories included.
          </p>

          <div className={cn(
            "flex flex-wrap gap-2.5 mb-10 transition-all duration-1000 delay-500",
            visible ? "opacity-100 translate-y-0" : "opacity-0 translate-y-6"
          )}>
            {PILLS.map((pill) => (
              <span
                key={pill.label}
                className={cn(
                  "inline-flex items-center gap-2 px-4 py-2 rounded-full border backdrop-blur-sm bg-gradient-to-r text-sm font-medium",
                  pill.color, pill.border, pill.text
                )}
              >
                <pill.icon className="w-3.5 h-3.5" />
                {pill.label}
              </span>
            ))}
          </div>

          <div className={cn(
            "flex items-center gap-4 transition-all duration-1000 delay-[600ms]",
            visible ? "opacity-100 translate-y-0" : "opacity-0 translate-y-6"
          )}>
            <button
              onClick={onBookNow}
              className="group inline-flex items-center gap-3 h-14 px-8 bg-gold-400 text-brand-900 rounded-xl font-semibold text-base hover:bg-gold-500 transition-all duration-200 hover:gap-4"
            >
              Book Now
              <ChevronRight className="w-5 h-5 transition-transform group-hover:translate-x-0.5" />
            </button>
            <span className="text-sm text-white/40">From BSD $200/person</span>
          </div>
        </div>

        {/* Mobile */}
        <div className="lg:hidden">
          <h1 className={cn(
            "font-display text-4xl sm:text-5xl text-white leading-[1.1] mb-3 transition-all duration-700 delay-200",
            visible ? "opacity-100 translate-y-0" : "opacity-0 translate-y-4"
          )}>
            Clear Boat<br />Bahamas
          </h1>

          <p className={cn(
            "text-sm text-white/60 leading-relaxed mb-5 transition-all duration-700 delay-300",
            visible ? "opacity-100 translate-y-0" : "opacity-0 translate-y-4"
          )}>
            Glide over crystal-clear waters in our transparent boat. Photos, drinks, memories included.
          </p>

          <div className={cn(
            "flex gap-2 overflow-x-auto pb-3 pl-1 pr-6 mb-6 scrollbar-hide transition-all duration-700 delay-[400ms]",
            visible ? "opacity-100 translate-y-0" : "opacity-0 translate-y-4"
          )}>
            {PILLS.map((pill) => (
              <span
                key={pill.label}
                className={cn(
                  "inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full border backdrop-blur-sm bg-gradient-to-r text-xs font-medium whitespace-nowrap shrink-0",
                  pill.color, pill.border, pill.text
                )}
              >
                <pill.icon className="w-3 h-3" />
                {pill.label}
              </span>
            ))}
          </div>

          <button
            onClick={onBookNow}
            className={cn(
              "w-full h-14 bg-gold-400 text-brand-900 rounded-xl font-semibold text-base flex items-center justify-center gap-2 transition-all duration-700 delay-500 hover:bg-gold-500",
              visible ? "opacity-100 translate-y-0" : "opacity-0 translate-y-4"
            )}
          >
            Book Now
            <ChevronRight className="w-5 h-5" />
          </button>
        </div>
      </div>
    </div>
  );
}

/* ─── TOUR SELECT VIEW ─── */
function TourSelectView({
  onSelectShared,
  onSelectPrivate,
  onBack,
}: {
  onSelectShared: () => void;
  onSelectPrivate: () => void;
  onBack: () => void;
}) {
  return (
    <div className="flex items-center justify-center h-full px-4">
      <div className="w-full max-w-2xl">
        <div className="text-center mb-6">
          <h2 className="text-2xl sm:text-3xl font-display text-white mb-2">Choose Your Experience</h2>
          <p className="text-sm text-white/50">How would you like to ride?</p>
        </div>

        <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
          {/* Shared Tour Card */}
          <button
            onClick={onSelectShared}
            className="group relative text-left rounded-2xl border border-white/15 bg-black/40 backdrop-blur-xl p-6 sm:p-8 hover:border-gold-400/40 hover:bg-white/5 transition-all duration-300"
          >
            <div className="w-12 h-12 rounded-xl bg-brand-500/15 border border-brand-400/25 flex items-center justify-center mb-5">
              <Ship className="w-6 h-6 text-brand-400" />
            </div>
            <h3 className="text-lg font-semibold text-white mb-1.5">Shared Tour</h3>
            <p className="text-sm text-white/50 leading-relaxed mb-4">
              Join others on our transparent boat. Pick a date, time, and go.
            </p>
            <div className="flex items-center justify-between">
              <span className="text-xs text-gold-400 font-medium">From BSD $200/adult</span>
              <span className="w-8 h-8 rounded-lg bg-gold-400/10 flex items-center justify-center group-hover:bg-gold-400/20 transition-colors">
                <ChevronRight className="w-4 h-4 text-gold-400" />
              </span>
            </div>
          </button>

          {/* Private Tour Card */}
          <button
            onClick={onSelectPrivate}
            className="group relative text-left rounded-2xl border border-white/15 bg-black/40 backdrop-blur-xl p-6 sm:p-8 hover:border-gold-400/40 hover:bg-white/5 transition-all duration-300"
          >
            <div className="absolute top-4 right-4">
              <span className="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-gold-400/15 border border-gold-400/25 text-[10px] font-semibold text-gold-400 uppercase tracking-wider">
                <Sparkles className="w-3 h-3" /> Popular
              </span>
            </div>
            <div className="w-12 h-12 rounded-xl bg-gold-400/15 border border-gold-400/25 flex items-center justify-center mb-5">
              <Crown className="w-6 h-6 text-gold-400" />
            </div>
            <h3 className="text-lg font-semibold text-white mb-1.5">Private Tour</h3>
            <p className="text-sm text-white/50 leading-relaxed mb-4">
              Have the whole boat to yourself. Perfect for special occasions.
            </p>
            <div className="flex items-center justify-between">
              <span className="text-xs text-gold-400 font-medium">Up to 10 guests</span>
              <span className="w-8 h-8 rounded-lg bg-gold-400/10 flex items-center justify-center group-hover:bg-gold-400/20 transition-colors">
                <ChevronRight className="w-4 h-4 text-gold-400" />
              </span>
            </div>
          </button>
        </div>

        <div className="text-center mt-5">
          <button
            onClick={onBack}
            className="text-xs text-white/40 hover:text-white/60 transition-colors inline-flex items-center gap-1"
          >
            <ChevronLeft className="w-3 h-3" /> Back
          </button>
        </div>
      </div>
    </div>
  );
}

/* ─── BOOKING VIEW (Shared Tour) ─── 6-step: Date → Time → Tickets → Details → Add-ons → Pay ─── */
const BOOKING_STEPS = [
  { icon: Calendar, label: "Date" },
  { icon: Clock, label: "Time" },
  { icon: Users, label: "Tickets" },
  { icon: Users, label: "Details" },
  { icon: Sparkles, label: "Extras" },
  { icon: CreditCard, label: "Pay" },
];

function BookingView({ onBack }: { onBack: () => void }) {
  const store = useBookingStore();
  const router = useRouter();
  const stripe = useStripe();
  const elements = useElements();

  const [slotsLoading, setSlotsLoading] = useState(false);
  const [loading, setLoading] = useState(false);
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
        const cParams = new URLSearchParams({
          ref: bookingRef,
          email: store.guests[0].email,
          date: format(store.selectedDate, "yyyy-MM-dd"),
          time: store.selectedSlot ? formatTime(store.selectedSlot.start_time) : "",
          adults: String(Object.entries(store.ticketCounts).reduce((sum, [id, c]) => { const t = store.ticketTypes.find(t => t.id === id); return sum + (t && t.name?.toLowerCase().includes('adult') ? c : 0); }, 0) || ""),
          children: String(Object.entries(store.ticketCounts).reduce((sum, [id, c]) => { const t = store.ticketTypes.find(t => t.id === id); return sum + (t && (t.name?.toLowerCase().includes('child') || t.name?.toLowerCase().includes('kid')) ? c : 0); }, 0) || ""),
          total: String(store.getGrandTotal()),
        });
        router.push(`/book/confirmation?${cParams.toString()}`);
        return;
      }

      if (!stripe || !elements) {
        setStripeError("Payment system unavailable. Please refresh.");
        setSubmitted(false);
        return;
      }
      const cardElement = elements.getElement(CardElement);
      if (!cardElement) {
        setStripeError("Card element not found.");
        setSubmitted(false);
        return;
      }

      const { error: stripeErr } = await stripe.confirmCardPayment(clientSecret, {
        payment_method: { card: cardElement },
      });
      if (stripeErr) {
        setStripeError(stripeErr.message || "Payment failed.");
        setSubmitted(false);
        return;
      }

      if (result.payment?.stripe_intent_id && bookingRef) {
        try {
          await confirmPayment(bookingRef, result.payment.stripe_intent_id);
        } catch { /* non-blocking */ }
      }

      toast.success("Booking confirmed!");
      const cParams = new URLSearchParams({
        ref: bookingRef,
        email: store.guests[0].email,
        date: format(store.selectedDate, "yyyy-MM-dd"),
        time: store.selectedSlot ? formatTime(store.selectedSlot.start_time) : "",
        adults: String(Object.entries(store.ticketCounts).reduce((sum, [id, c]) => { const t = store.ticketTypes.find(t => t.id === id); return sum + (t && t.name?.toLowerCase().includes('adult') ? c : 0); }, 0) || ""),
        children: String(Object.entries(store.ticketCounts).reduce((sum, [id, c]) => { const t = store.ticketTypes.find(t => t.id === id); return sum + (t && (t.name?.toLowerCase().includes('child') || t.name?.toLowerCase().includes('kid')) ? c : 0); }, 0) || ""),
        total: String(store.getGrandTotal()),
      });
      router.push(`/book/confirmation?${cParams.toString()}`);
    } catch (err: unknown) {
      setSubmitted(false);
      const error = err as Error & { status?: number };
      if (error.status === 409) {
        toast.error("This slot just filled up. Please select a different time.");
      } else {
        toast.error(error.message || "Booking failed. Please try again.");
      }
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
    <div className="flex items-center justify-center h-full px-4">
      <div className="w-full max-w-md">
        <div className="bg-black/40 backdrop-blur-xl border border-white/10 rounded-2xl overflow-hidden shadow-2xl">
          {/* Step indicator */}
          <div className="px-5 pt-5 pb-3">
            <div className="flex items-center justify-between">
              {BOOKING_STEPS.map((s, i) => (
                <div key={i} className="flex flex-col items-center gap-1">
                  <div className={cn(
                    "w-8 h-8 rounded-full flex items-center justify-center transition-all duration-300",
                    i < step ? "bg-gold-400/20 border border-gold-400/40" :
                    i === step ? "bg-gold-400 text-brand-900" :
                    "bg-white/5 border border-white/10"
                  )}>
                    {i < step ? (
                      <Check className="w-3.5 h-3.5 text-gold-400" />
                    ) : (
                      <s.icon className="w-3.5 h-3.5" />
                    )}
                  </div>
                  <span className={cn(
                    "text-[9px] font-medium transition-colors",
                    i === step ? "text-gold-400" : "text-white/30"
                  )}>{s.label}</span>
                </div>
              ))}
            </div>
          </div>

          {/* Content */}
          <div className="px-5 py-4 max-h-[55vh] overflow-y-auto scrollbar-hide">
            {step === 0 && (
              <div className="space-y-4">
                <h2 className="text-lg font-semibold text-white">Pick your date</h2>
                <input
                  type="date"
                  value={store.selectedDate ? format(store.selectedDate, "yyyy-MM-dd") : ""}
                  onChange={(e) => {
                    const d = e.target.value ? new Date(e.target.value + "T12:00:00") : undefined;
                    store.setSelectedDate(d);
                  }}
                  min={format(new Date(), "yyyy-MM-dd")}
                  className="w-full h-12 px-4 rounded-xl bg-white/10 border border-white/15 text-white placeholder-white/30 focus:ring-2 focus:ring-gold-400/50 focus:border-gold-400/50 outline-none text-sm"
                />
              </div>
            )}

            {step === 1 && (
              <div className="space-y-3">
                <h2 className="text-lg font-semibold text-white">
                  {store.selectedDate && format(store.selectedDate, "EEE, MMM d")}
                </h2>
                {slotsLoading ? (
                  <div className="flex items-center justify-center py-8 text-white/40 text-sm">Loading times...</div>
                ) : store.availableSlots.length === 0 ? (
                  <p className="text-white/40 text-sm py-4">No available times for this date.</p>
                ) : (
                  <div className="grid grid-cols-2 gap-2">
                    {store.availableSlots.map((slot) => (
                      <button
                        key={slot.id}
                        onClick={() => store.setSelectedSlot(slot)}
                        className={cn(
                          "p-3 rounded-xl border text-center transition-all duration-200",
                          store.selectedSlot?.id === slot.id
                            ? "border-gold-400 bg-gold-400/10 text-white"
                            : "border-white/10 bg-white/5 text-white/70 hover:border-white/20"
                        )}
                      >
                        <span className="text-sm font-semibold">{formatTime(slot.start_time)}</span>
                        <span className="block text-[10px] text-white/40 mt-0.5">{slot.boat_name}</span>
                        <span className="block text-[10px] text-white/30 mt-0.5">{slot.remaining_capacity} spots</span>
                      </button>
                    ))}
                  </div>
                )}
              </div>
            )}

            {step === 2 && (
              <div className="space-y-3">
                <h2 className="text-lg font-semibold text-white">Choose tickets</h2>
                {store.ticketTypes.sort((a, b) => a.sort_order - b.sort_order).map((type) => {
                  const count = store.ticketCounts[type.id] ?? 0;
                  const expanded = expandedTypes[type.id];
                  const price = type.price_cents / 100;
                  const IconComponent = FEATURE_ICONS[type.features?.[0]?.icon || ""] || Sparkles;

                  return (
                    <div key={type.id} className="rounded-xl border border-white/10 overflow-hidden">
                      <div className={cn(
                        "flex items-center justify-between p-3 transition-colors cursor-pointer",
                        count > 0 ? "bg-gold-400/5" : "bg-white/5"
                      )} onClick={() => setExpandedTypes((prev) => ({ ...prev, [type.id]: !prev[type.id] }))}>
                        <div className="flex-1 min-w-0">
                          <div className="flex items-center gap-2">
                            <span className="text-sm font-semibold text-white">{type.name}</span>
                            <span className="text-[10px] text-white/40">{type.description}</span>
                          </div>
                          <span className="text-xs text-gold-400 font-medium">${price.toFixed(2)}</span>
                        </div>
                        <div className="flex items-center gap-2">
                          <button
                            onClick={(e) => { e.stopPropagation(); store.updateTicketCount(type.id, count - 1); }}
                            className="w-7 h-7 rounded-lg bg-white/10 flex items-center justify-center text-white/60 hover:bg-white/15"
                          >
                            <Minus className="w-3 h-3" />
                          </button>
                          <span className="w-6 text-center text-sm font-semibold text-white">{count}</span>
                          <button
                            onClick={(e) => { e.stopPropagation(); store.updateTicketCount(type.id, count + 1); }}
                            className="w-7 h-7 rounded-lg bg-white/10 flex items-center justify-center text-white/60 hover:bg-white/15"
                          >
                            <Plus className="w-3 h-3" />
                          </button>
                        </div>
                      </div>
                      {expanded && type.features && type.features.length > 0 && (
                        <div className="px-3 pb-3 pt-1 border-t border-white/5">
                          <div className="space-y-1.5">
                            {type.features.sort((a, b) => a.sort_order - b.sort_order).map((f, fi) => {
                              const FIcon = FEATURE_ICONS[f.icon] || Check;
                              return (
                                <div key={fi} className="flex items-center gap-2 text-white/50">
                                  <FIcon className="w-3 h-3 text-gold-400/70" />
                                  <span className="text-xs">{f.label}</span>
                                </div>
                              );
                            })}
                          </div>
                        </div>
                      )}
                    </div>
                  );
                })}
                <p className="text-xs text-white/30 text-center pt-1">
                  {store.totalGuests()} guest{store.totalGuests() !== 1 ? "s" : ""} selected
                </p>
              </div>
            )}

            {step === 3 && (
              <div className="space-y-3">
                <h2 className="text-lg font-semibold text-white">Your details</h2>
                <div className="space-y-2">
                  <div className="space-y-2">
                    <input
                      placeholder="First name"
                      value={store.guests[0]?.first_name || ""}
                      onChange={(e) => store.updateGuest(0, "first_name", e.target.value)}
                      className="w-full h-10 px-3 rounded-lg bg-white/10 border border-white/10 text-white text-sm placeholder-white/25 focus:ring-1 focus:ring-gold-400/50 outline-none"
                    />
                    <input
                      placeholder="Last name"
                      value={store.guests[0]?.last_name || ""}
                      onChange={(e) => store.updateGuest(0, "last_name", e.target.value)}
                      className="w-full h-10 px-3 rounded-lg bg-white/10 border border-white/10 text-white text-sm placeholder-white/25 focus:ring-1 focus:ring-gold-400/50 outline-none"
                    />
                  </div>
                  <input
                    type="email"
                    placeholder="Email"
                    value={store.guests[0]?.email || ""}
                    onChange={(e) => store.updateGuest(0, "email", e.target.value)}
                    className="w-full h-10 px-3 rounded-lg bg-white/10 border border-white/10 text-white text-sm placeholder-white/25 focus:ring-1 focus:ring-gold-400/50 outline-none"
                  />
                  <input
                    type="email"
                    placeholder="Confirm email"
                    value={confirmEmail}
                    onChange={(e) => setConfirmEmail(e.target.value)}
                    className={cn(
                      "w-full h-10 px-3 rounded-lg bg-white/10 border text-white text-sm placeholder-white/25 focus:ring-1 focus:ring-gold-400/50 outline-none",
                      confirmEmail && confirmEmail !== store.guests[0]?.email
                        ? "border-red-400/50" : "border-white/10"
                    )}
                  />
                  {confirmEmail && confirmEmail !== store.guests[0]?.email && (
                    <p className="text-xs text-red-400 flex items-center gap-1">
                      <X className="w-3 h-3" /> Emails don&apos;t match
                    </p>
                  )}
                  <input
                    type="tel"
                    placeholder="Phone (+1 242 ...)"
                    value={store.guests[0]?.phone || ""}
                    onChange={(e) => store.updateGuest(0, "phone", e.target.value)}
                    className="w-full h-10 px-3 rounded-lg bg-white/10 border border-white/10 text-white text-sm placeholder-white/25 focus:ring-1 focus:ring-gold-400/50 outline-none"
                  />
                </div>

                <textarea
                  placeholder="Special requests? (optional)"
                  value={store.specialComment}
                  onChange={(e) => store.setSpecialComment(e.target.value)}
                  rows={2}
                  className="w-full px-3 py-2 rounded-lg bg-white/10 border border-white/10 text-white text-sm placeholder-white/25 focus:ring-1 focus:ring-gold-400/50 outline-none resize-none"
                />
              </div>
            )}

            {step === 4 && (
              <div className="space-y-3">
                <h2 className="text-lg font-semibold text-white">Enhance your trip</h2>
                {store.addons.length === 0 ? (
                  <p className="text-white/30 text-sm text-center py-6">No add-ons available at this time.</p>
                ) : (
                  store.addons.map((addon) => {
                    const qty = store.selectedAddons[addon.id] ?? 0;
                    const name = addon.title || addon.name || "Add-on";
                    const price = addon.price_cents / 100;
                    const maxQty = addon.max_quantity || 10;

                    return (
                      <div key={addon.id} className={cn(
                        "flex items-center justify-between p-3 rounded-xl border transition-all",
                        qty > 0 ? "border-gold-400/30 bg-gold-400/5" : "border-white/10 bg-white/5"
                      )}>
                        <div className="flex-1 min-w-0 mr-3">
                          <p className="text-sm font-medium text-white">{name}</p>
                          {addon.description && (
                            <p className="text-xs text-white/40 mt-0.5 line-clamp-2">{addon.description}</p>
                          )}
                          <p className="text-xs text-gold-400 font-medium mt-0.5">${price.toFixed(2)}</p>
                        </div>
                        <div className="flex items-center gap-2">
                          {qty > 0 && (
                            <button
                              onClick={() => store.updateAddon(addon.id, qty - 1)}
                              className="w-7 h-7 rounded-lg bg-white/10 flex items-center justify-center text-white/60 hover:bg-white/15"
                            >
                              <Minus className="w-3 h-3" />
                            </button>
                          )}
                          {qty > 0 && (
                            <span className="w-5 text-center text-sm font-semibold text-white">{qty}</span>
                          )}
                          <button
                            onClick={() => store.updateAddon(addon.id, qty + 1)}
                            disabled={qty >= maxQty}
                            className="w-7 h-7 rounded-lg bg-white/10 flex items-center justify-center text-white/60 hover:bg-white/15 disabled:opacity-30"
                          >
                            <Plus className="w-3 h-3" />
                          </button>
                        </div>
                      </div>
                    );
                  })
                )}
                <button
                  onClick={() => store.nextStep()}
                  className="w-full text-xs text-white/30 hover:text-white/50 py-2"
                >
                  Skip →
                </button>
              </div>
            )}

            {step === 5 && (
              <div className="space-y-3">
                <h2 className="text-lg font-semibold text-white">Review & Pay</h2>
                <div className="space-y-1.5 text-sm">
                  <div className="flex justify-between"><span className="text-white/50">Date</span><span className="text-white">{store.selectedDate && format(store.selectedDate, "EEE, MMM d")}</span></div>
                  <div className="flex justify-between"><span className="text-white/50">Time</span><span className="text-white">{store.selectedSlot && formatTime(store.selectedSlot.start_time)} — {store.selectedSlot?.boat_name}</span></div>
                  <div className="flex justify-between"><span className="text-white/50">Guests</span><span className="text-white">{store.totalGuests()}</span></div>
                  <div className="flex justify-between"><span className="text-white/50">Name</span><span className="text-white">{store.guests[0]?.first_name} {store.guests[0]?.last_name}</span></div>
                  <div className="flex justify-between"><span className="text-white/50">Email</span><span className="text-white truncate ml-3">{store.guests[0]?.email}</span></div>
                </div>
                <div className="space-y-1 pt-1">
                  {store.ticketTypes.map((type) => {
                    const count = store.ticketCounts[type.id] ?? 0;
                    if (count === 0) return null;
                    return (
                      <div key={type.id} className="flex justify-between text-xs">
                        <span className="text-white/40">{count}× {type.name}</span>
                        <span className="text-white/60">${((count * type.price_cents) / 100).toFixed(2)}</span>
                      </div>
                    );
                  })}
                  {Object.entries(store.selectedAddons).filter(([, q]) => q > 0).map(([id, qty]) => (
                    <div key={id} className="flex justify-between text-xs">
                      <span className="text-white/40">{qty}× {store.getAddonName(id)}</span>
                      <span className="text-white/60">
                        ${((qty * (store.addons.find(a => a.id === id)?.price_cents || 0)) / 100).toFixed(2)}
                      </span>
                    </div>
                  ))}
                </div>
                {fees.map((fee, i) => (
                  <div key={i} className="flex justify-between text-xs">
                    <span className="text-white/40">{fee.name}</span>
                    <span className="text-white/60">${(fee.amount / 100).toFixed(2)}</span>
                  </div>
                ))}
                <div className="p-3 rounded-xl bg-white/5 border border-white/10 flex justify-between items-center">
                  <span className="text-xs text-white/50">Total</span>
                  <span className="text-xl font-bold text-white">{formatCurrency(grandTotal / 100)}</span>
                </div>
                {!showPayment ? (
                  <button onClick={() => setShowPayment(true)} className="w-full h-11 bg-gold-400 text-brand-900 rounded-xl font-semibold text-sm hover:bg-gold-500 transition-colors">
                    Pay {formatCurrency(grandTotal / 100)}
                  </button>
                ) : (
                  <div className="space-y-3">
                    <div className="rounded-xl border border-white/15 p-3 bg-white/5">
                      <CardElement options={{ style: { base: { fontSize: "14px", color: "#fff", "::placeholder": { color: "rgba(255,255,255,0.3)" } } } }} />
                    </div>
                    {stripeError && <p className="text-red-400 text-xs">{stripeError}</p>}
                    <button onClick={handleBooking} disabled={loading || submitted} className="w-full h-11 bg-gold-400 text-brand-900 rounded-xl font-semibold text-sm hover:bg-gold-500 transition-colors disabled:opacity-50">
                      {loading ? "Processing..." : "Confirm Payment"}
                    </button>
                  </div>
                )}
              </div>
            )}
          </div>

          {/* Footer nav */}
          <div className="flex items-center justify-between px-5 py-3 border-t border-white/10">
            <button
              onClick={() => {
                if (step === 0) { onBack(); }
                else { store.prevStep(); setShowPayment(false); }
              }}
              className="text-xs text-white/50 hover:text-white/80 transition-colors flex items-center gap-1"
            >
              <ChevronLeft className="w-3 h-3" />
              {step === 0 ? "Back" : "Back"}
            </button>
            {step < 5 && (
              <button
                onClick={() => { if (canProceed()) store.nextStep(); }}
                disabled={!canProceed()}
                className="h-9 px-5 bg-gold-400 text-brand-900 rounded-lg text-xs font-semibold hover:bg-gold-500 transition-colors disabled:opacity-30 flex items-center gap-1"
              >
                Continue <ChevronRight className="w-3.5 h-3.5" />
              </button>
            )}
          </div>
        </div>
      </div>
    </div>
  );
}

/* ─── PRIVATE TOUR VIEW ─── 5-step: Party Size → Preferred Dates → Occasion → Add-ons → Your Details → Submit ─── */
const PRIVATE_STEPS = [
  { icon: Users, label: "Party" },
  { icon: Calendar, label: "Dates" },
  { icon: Heart, label: "Occasion" },
  { icon: Sparkles, label: "Extras" },
  { icon: Users, label: "Details" },
];

/* Helper: ensures guest slots match party size, no render-time side effects */
function PrivateTourView({ onBack }: { onBack: () => void }) {
  const store = usePrivateTourStore();
  const [submitted, setSubmitted] = useState(false);

  // Load private tour addons on mount
  useEffect(() => {
    getPrivateTourAddons()
      .then((addons) => { if (addons.length > 0) store.setAddons(addons); })
      .catch(() => {});
    return () => { usePrivateTourStore.getState().reset(); };
  }, []);

  const step = store.currentStep;

  const canProceed = () => {
    if (step === 0) return store.adultCount >= 1;
    if (step === 1) return store.preferredDates.some((d) => d.date !== "");
    return true;
  };

  const handleSubmit = async () => {
    if (submitted) return;
    if (!store.contactFirstName || !store.contactLastName || !store.contactEmail || !store.contactPhone) return;
    if (store.confirmEmail !== store.contactEmail) return;

    setSubmitted(true);
    store.setSubmitting(true);
    store.setSubmissionError("");

    try {
      const payload = {
        contact_first_name: store.contactFirstName,
        contact_last_name: store.contactLastName,
        contact_email: store.contactEmail,
        contact_phone: store.contactPhone,
        adult_count: store.adultCount,
        child_count: store.childCount,
        infant_count: store.infantCount,
        has_occasion: store.hasOccasion,
        occasion_details: store.hasOccasion ? store.occasionDetails : null,
        preferred_dates: store.preferredDates
          .filter((d) => d.date !== "")
          .map((d) => ({ date: d.date, time_preference: d.time_preference })),
        guests: store.guests
          .filter((g) => g.first_name || g.last_name)
          .map((g) => ({ ...g, is_primary: false })),
        addon_ids: store.selectedAddonIds.length > 0 ? store.selectedAddonIds : undefined,
      };

      const result = await submitPrivateTourRequest(payload);
      store.setBookingRef(result.request?.booking_ref || "");
      store.setSubmitted(true);
    } catch (err: unknown) {
      const error = err as Error;
      store.setSubmissionError(error.message || "Something went wrong. Please try again.");
    } finally {
      store.setSubmitting(false);
    }
  };

  // Success state
  if (store.submitted) {
    return (
      <div className="flex items-center justify-center h-full px-4">
        <div className="w-full max-w-md">
          <div className="bg-black/40 backdrop-blur-xl border border-white/10 rounded-2xl p-8 text-center">
            <div className="w-16 h-16 rounded-full bg-emerald-500/15 border border-emerald-400/30 flex items-center justify-center mx-auto mb-5">
              <Check className="w-8 h-8 text-emerald-400" />
            </div>
            <h2 className="text-xl font-semibold text-white mb-2">Request Submitted!</h2>
            <p className="text-sm text-white/50 leading-relaxed mb-5">
              We&apos;ll confirm your preferred date and send a quote within 24 hours.
            </p>

            {store.bookingRef && (
              <div className="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-white/5 border border-white/10 mb-5">
                <span className="text-xs text-white/40">Reference:</span>
                <span className="text-sm font-mono font-semibold text-gold-400">{store.bookingRef}</span>
              </div>
            )}

            {/* Booking details */}
            <div className="space-y-3 text-left bg-white/5 rounded-xl p-4 mb-5">
              {/* Preferred dates */}
              <div className="flex items-start gap-3">
                <Calendar className="w-4 h-4 text-gold-400 mt-0.5 flex-shrink-0" />
                <div>
                  <p className="text-xs text-white/40">Preferred Dates</p>
                  <div className="mt-0.5">
                    {store.preferredDates
                      .filter((d) => d.date)
                      .map((d, i) => (
                        <p key={i} className="text-sm font-medium text-white">
                          {new Date(d.date + "T12:00:00").toLocaleDateString("en-US", { weekday: "short", month: "short", day: "numeric" })}
                          <span className="text-white/40 ml-1">· {d.time_preference === "morning" ? "Morning" : "Afternoon"}</span>
                        </p>
                      ))}
                  </div>
                </div>
              </div>
              {/* Party size */}
              <div className="flex items-start gap-3">
                <Users className="w-4 h-4 text-gold-400 mt-0.5 flex-shrink-0" />
                <div>
                  <p className="text-xs text-white/40">Party Size</p>
                  <p className="text-sm font-medium text-white">
                    {[
                      store.adultCount > 0 && `${store.adultCount} Adult${store.adultCount !== 1 ? "s" : ""}`,
                      store.childCount > 0 && `${store.childCount} Child${store.childCount !== 1 ? "ren" : ""}`,
                      store.infantCount > 0 && `${store.infantCount} Infant${store.infantCount !== 1 ? "s" : ""}`,
                    ].filter(Boolean).join(", ")}
                  </p>
                </div>
              </div>
              {/* Occasion */}
              {store.hasOccasion && store.occasionDetails && (
                <div className="flex items-start gap-3">
                  <Sparkles className="w-4 h-4 text-gold-400 mt-0.5 flex-shrink-0" />
                  <div>
                    <p className="text-xs text-white/40">Special Occasion</p>
                    <p className="text-sm font-medium text-white">{store.occasionDetails}</p>
                  </div>
                </div>
              )}
            </div>

            <p className="text-xs text-white/30 mb-6">Confirmation sent to <span className="text-white/50">{store.contactEmail}</span></p>
            <button
              onClick={onBack}
              className="h-10 px-6 bg-gold-400 text-brand-900 rounded-xl text-sm font-semibold hover:bg-gold-500 transition-colors"
            >
              Done
            </button>
          </div>
        </div>
      </div>
    );
  }

  return (
    <div className="flex items-center justify-center h-full px-4">
      <div className="w-full max-w-md">
        <div className="bg-black/40 backdrop-blur-xl border border-white/10 rounded-2xl overflow-hidden shadow-2xl">
          {/* Header */}
          <div className="px-5 pt-4 pb-2 border-b border-white/5">
            <div className="flex items-center gap-2 mb-3">
              <Crown className="w-4 h-4 text-gold-400" />
              <span className="text-xs font-semibold text-gold-400 uppercase tracking-wider">Private Tour</span>
            </div>
            {/* Step indicator */}
            <div className="flex items-center justify-between">
              {PRIVATE_STEPS.map((s, i) => (
                <div key={i} className="flex flex-col items-center gap-1">
                  <div className={cn(
                    "w-8 h-8 rounded-full flex items-center justify-center transition-all duration-300",
                    i < step ? "bg-gold-400/20 border border-gold-400/40" :
                    i === step ? "bg-gold-400 text-brand-900" :
                    "bg-white/5 border border-white/10"
                  )}>
                    {i < step ? (
                      <Check className="w-3.5 h-3.5 text-gold-400" />
                    ) : (
                      <s.icon className="w-3.5 h-3.5" />
                    )}
                  </div>
                  <span className={cn(
                    "text-[9px] font-medium transition-colors",
                    i === step ? "text-gold-400" : "text-white/30"
                  )}>{s.label}</span>
                </div>
              ))}
            </div>
          </div>

          {/* Content */}
          <div className="px-5 py-4 max-h-[55vh] overflow-y-auto scrollbar-hide">

            {/* Step 0: Party Size */}
            {step === 0 && (
              <div className="space-y-5">
                <h2 className="text-lg font-semibold text-white">Party size</h2>

                {/* Adults */}
                <div className="flex items-center justify-between p-3 rounded-xl border border-white/10 bg-white/5">
                  <div className="flex items-center gap-3">
                    <div className="w-9 h-9 rounded-lg bg-brand-500/15 flex items-center justify-center">
                      <Users className="w-4 h-4 text-brand-400" />
                    </div>
                    <div>
                      <p className="text-sm font-medium text-white">Adults</p>
                      <p className="text-[10px] text-white/40">Age 13+</p>
                    </div>
                  </div>
                  <div className="flex items-center gap-2">
                    <button
                      onClick={() => store.setAdultCount(store.adultCount - 1)}
                      className="w-7 h-7 rounded-lg bg-white/10 flex items-center justify-center text-white/60 hover:bg-white/15"
                    >
                      <Minus className="w-3 h-3" />
                    </button>
                    <span className="w-6 text-center text-sm font-semibold text-white">{store.adultCount}</span>
                    <button
                      onClick={() => store.setAdultCount(store.adultCount + 1)}
                      disabled={store.totalPartySize() >= 10}
                      className="w-7 h-7 rounded-lg bg-white/10 flex items-center justify-center text-white/60 hover:bg-white/15 disabled:opacity-30"
                    >
                      <Plus className="w-3 h-3" />
                    </button>
                  </div>
                </div>

                {/* Children */}
                <div className="flex items-center justify-between p-3 rounded-xl border border-white/10 bg-white/5">
                  <div className="flex items-center gap-3">
                    <div className="w-9 h-9 rounded-lg bg-emerald-500/15 flex items-center justify-center">
                      <Users className="w-4 h-4 text-emerald-400" />
                    </div>
                    <div>
                      <p className="text-sm font-medium text-white">Children</p>
                      <p className="text-[10px] text-white/40">Age 3–12</p>
                    </div>
                  </div>
                  <div className="flex items-center gap-2">
                    <button
                      onClick={() => store.setChildCount(store.childCount - 1)}
                      className="w-7 h-7 rounded-lg bg-white/10 flex items-center justify-center text-white/60 hover:bg-white/15"
                    >
                      <Minus className="w-3 h-3" />
                    </button>
                    <span className="w-6 text-center text-sm font-semibold text-white">{store.childCount}</span>
                    <button
                      onClick={() => store.setChildCount(store.childCount + 1)}
                      disabled={store.totalPartySize() >= 10}
                      className="w-7 h-7 rounded-lg bg-white/10 flex items-center justify-center text-white/60 hover:bg-white/15 disabled:opacity-30"
                    >
                      <Plus className="w-3 h-3" />
                    </button>
                  </div>
                </div>

                {/* Infants */}
                <div className="flex items-center justify-between p-3 rounded-xl border border-white/10 bg-white/5">
                  <div className="flex items-center gap-3">
                    <div className="w-9 h-9 rounded-lg bg-sky-500/15 flex items-center justify-center">
                      <Baby className="w-4 h-4 text-sky-400" />
                    </div>
                    <div>
                      <p className="text-sm font-medium text-white">Infants</p>
                      <p className="text-[10px] text-white/40">Under 3 (free)</p>
                    </div>
                  </div>
                  <div className="flex items-center gap-2">
                    <button
                      onClick={() => store.setInfantCount(store.infantCount - 1)}
                      className="w-7 h-7 rounded-lg bg-white/10 flex items-center justify-center text-white/60 hover:bg-white/15"
                    >
                      <Minus className="w-3 h-3" />
                    </button>
                    <span className="w-6 text-center text-sm font-semibold text-white">{store.infantCount}</span>
                    <button
                      onClick={() => store.setInfantCount(store.infantCount + 1)}
                      disabled={store.totalPartySize() >= 10}
                      className="w-7 h-7 rounded-lg bg-white/10 flex items-center justify-center text-white/60 hover:bg-white/15 disabled:opacity-30"
                    >
                      <Plus className="w-3 h-3" />
                    </button>
                  </div>
                </div>

                <p className="text-xs text-white/30 text-center">
                  {store.totalPartySize()} guest{store.totalPartySize() !== 1 ? "s" : ""} · Max 10
                </p>
              </div>
            )}

            {/* Step 1: Preferred Dates */}
            {step === 1 && (
              <div className="space-y-4">
                <h2 className="text-lg font-semibold text-white">Preferred dates</h2>
                <p className="text-xs text-white/40">Pick 1–3 dates that work for you. We&apos;ll confirm availability.</p>

                {store.preferredDates.map((pd, idx) => (
                  <div key={pd.id} className="rounded-xl border border-white/10 bg-white/5 p-3 space-y-2">
                    <div className="flex items-center justify-between">
                      <span className="text-xs font-medium text-white/50">Option {idx + 1}</span>
                      {store.preferredDates.length > 1 && (
                        <button
                          onClick={() => store.removePreferredDate(pd.id)}
                          className="text-white/20 hover:text-red-400 transition-colors"
                        >
                          <Trash2 className="w-3 h-3" />
                        </button>
                      )}
                    </div>
                    <input
                      type="date"
                      value={pd.date}
                      onChange={(e) => store.updatePreferredDate(pd.id, "date", e.target.value)}
                      min={format(new Date(), "yyyy-MM-dd")}
                      className="w-full h-10 px-3 rounded-lg bg-white/10 border border-white/15 text-white text-sm focus:ring-1 focus:ring-gold-400/50 outline-none"
                    />
                    <div className="flex gap-2">
                      <button
                        onClick={() => store.updatePreferredDate(pd.id, "time_preference", "morning")}
                        className={cn(
                          "flex-1 h-9 rounded-lg text-xs font-medium transition-all flex items-center justify-center gap-1.5",
                          pd.time_preference === "morning"
                            ? "bg-gold-400/15 border border-gold-400/30 text-gold-400"
                            : "bg-white/5 border border-white/10 text-white/50 hover:border-white/20"
                        )}
                      >
                        <Sunrise className="w-3 h-3" /> Morning
                      </button>
                      <button
                        onClick={() => store.updatePreferredDate(pd.id, "time_preference", "afternoon")}
                        className={cn(
                          "flex-1 h-9 rounded-lg text-xs font-medium transition-all flex items-center justify-center gap-1.5",
                          pd.time_preference === "afternoon"
                            ? "bg-gold-400/15 border border-gold-400/30 text-gold-400"
                            : "bg-white/5 border border-white/10 text-white/50 hover:border-white/20"
                        )}
                      >
                        <Sun className="w-3 h-3" /> Afternoon
                      </button>
                    </div>
                  </div>
                ))}

                {store.preferredDates.length < 3 && (
                  <button
                    onClick={() => store.addPreferredDate()}
                    className="w-full h-9 rounded-lg border border-dashed border-white/15 text-white/40 text-xs hover:text-white/60 hover:border-white/25 flex items-center justify-center gap-1"
                  >
                    <Plus className="w-3 h-3" /> Add another date
                  </button>
                )}
              </div>
            )}

            {/* Step 2: Occasion */}
            {step === 2 && (
              <div className="space-y-4">
                <h2 className="text-lg font-semibold text-white">Special occasion?</h2>

                <div className="flex gap-2">
                  <button
                    onClick={() => store.setHasOccasion(true)}
                    className={cn(
                      "flex-1 h-11 rounded-xl text-sm font-medium transition-all",
                      store.hasOccasion
                        ? "bg-gold-400/15 border border-gold-400/30 text-gold-400"
                        : "bg-white/5 border border-white/10 text-white/50 hover:border-white/20"
                    )}
                  >
                    Yes 🎉
                  </button>
                  <button
                    onClick={() => store.setHasOccasion(false)}
                    className={cn(
                      "flex-1 h-11 rounded-xl text-sm font-medium transition-all",
                      !store.hasOccasion
                        ? "bg-gold-400/15 border border-gold-400/30 text-gold-400"
                        : "bg-white/5 border border-white/10 text-white/50 hover:border-white/20"
                    )}
                  >
                    Just a trip
                  </button>
                </div>

                {store.hasOccasion && (
                  <textarea
                    placeholder="Tell us about it — birthday, anniversary, proposal…"
                    value={store.occasionDetails}
                    onChange={(e) => store.setOccasionDetails(e.target.value)}
                    rows={3}
                    className="w-full px-3 py-2 rounded-lg bg-white/10 border border-white/10 text-white text-sm placeholder-white/25 focus:ring-1 focus:ring-gold-400/50 outline-none resize-none"
                  />
                )}
              </div>
            )}

            {/* Step 3: Add-ons */}
            {step === 3 && (
              <div className="space-y-3">
                <h2 className="text-lg font-semibold text-white">Enhance your trip</h2>
                {store.addons.length === 0 ? (
                  <p className="text-white/30 text-sm text-center py-6">No extras available at this time.</p>
                ) : (
                  store.addons.map((addon) => {
                    const selected = store.selectedAddonIds.includes(addon.id);
                    return (
                      <button
                        key={addon.id}
                        onClick={() => store.toggleAddon(addon.id)}
                        className={cn(
                          "w-full flex items-center gap-3 p-3 rounded-xl border text-left transition-all",
                          selected
                            ? "border-gold-400/30 bg-gold-400/5"
                            : "border-white/10 bg-white/5 hover:border-white/20"
                        )}
                      >
                        <div className={cn(
                          "w-7 h-7 rounded-lg flex items-center justify-center flex-shrink-0 transition-colors",
                          selected ? "bg-gold-400 text-brand-900" : "bg-white/10"
                        )}>
                          {selected ? <Check className="w-3.5 h-3.5" /> : <Plus className="w-3.5 h-3.5 text-white/40" />}
                        </div>
                        <div className="flex-1 min-w-0">
                          <p className="text-sm font-medium text-white">{addon.title}</p>
                          {addon.description && (
                            <p className="text-xs text-white/40 mt-0.5 line-clamp-2">{addon.description}</p>
                          )}
                        </div>
                      </button>
                    );
                  })
                )}
              </div>
            )}

            {/* Step 4: Contact Details */}
            {step === 4 && (
              <div className="space-y-3">
                <h2 className="text-lg font-semibold text-white">Your details</h2>

                <div className="space-y-2">
                  <div className="flex gap-2">
                    <input
                      placeholder="First name"
                      value={store.contactFirstName}
                      onChange={(e) => store.setContactFirstName(e.target.value)}
                      className="flex-1 h-10 px-3 rounded-lg bg-white/10 border border-white/10 text-white text-sm placeholder-white/25 focus:ring-1 focus:ring-gold-400/50 outline-none"
                    />
                    <input
                      placeholder="Last name"
                      value={store.contactLastName}
                      onChange={(e) => store.setContactLastName(e.target.value)}
                      className="flex-1 h-10 px-3 rounded-lg bg-white/10 border border-white/10 text-white text-sm placeholder-white/25 focus:ring-1 focus:ring-gold-400/50 outline-none"
                    />
                  </div>
                  <input
                    type="email"
                    placeholder="Email"
                    value={store.contactEmail}
                    onChange={(e) => store.setContactEmail(e.target.value)}
                    className="w-full h-10 px-3 rounded-lg bg-white/10 border border-white/10 text-white text-sm placeholder-white/25 focus:ring-1 focus:ring-gold-400/50 outline-none"
                  />
                  <input
                    type="email"
                    placeholder="Confirm email"
                    value={store.confirmEmail}
                    onChange={(e) => store.setConfirmEmail(e.target.value)}
                    className={cn(
                      "w-full h-10 px-3 rounded-lg bg-white/10 border text-white text-sm placeholder-white/25 focus:ring-1 focus:ring-gold-400/50 outline-none",
                      store.confirmEmail && store.confirmEmail !== store.contactEmail
                        ? "border-red-400/50" : "border-white/10"
                    )}
                  />
                  {store.confirmEmail && store.confirmEmail !== store.contactEmail && (
                    <p className="text-xs text-red-400 flex items-center gap-1">
                      <X className="w-3 h-3" /> Emails don&apos;t match
                    </p>
                  )}
                  <input
                    type="tel"
                    placeholder="Phone (+1 242 ...)"
                    value={store.contactPhone}
                    onChange={(e) => store.setContactPhone(e.target.value)}
                    className="w-full h-10 px-3 rounded-lg bg-white/10 border border-white/10 text-white text-sm placeholder-white/25 focus:ring-1 focus:ring-gold-400/50 outline-none"
                  />
                </div>

                                {/* Submission error */}
                {store.submissionError && (
                  <div className="p-3 rounded-lg bg-red-500/10 border border-red-400/20">
                    <p className="text-xs text-red-400">{store.submissionError}</p>
                  </div>
                )}
              </div>
            )}
          </div>

          {/* Footer nav */}
          <div className="flex items-center justify-between px-5 py-3 border-t border-white/10">
            <button
              onClick={() => {
                if (step === 0) onBack();
                else store.prevStep();
              }}
              className="text-xs text-white/50 hover:text-white/80 transition-colors flex items-center gap-1"
            >
              <ChevronLeft className="w-3 h-3" />
              Back
            </button>

            {step < 4 && (
              <button
                onClick={() => { if (canProceed()) store.nextStep(); }}
                disabled={!canProceed()}
                className="h-9 px-5 bg-gold-400 text-brand-900 rounded-lg text-xs font-semibold hover:bg-gold-500 transition-colors disabled:opacity-30 flex items-center gap-1"
              >
                Continue <ChevronRight className="w-3.5 h-3.5" />
              </button>
            )}

            {step === 4 && (
              <button
                onClick={handleSubmit}
                disabled={
                  submitted ||
                  store.submitting ||
                  !store.contactFirstName ||
                  !store.contactLastName ||
                  !store.contactEmail ||
                  !store.contactPhone ||
                  store.confirmEmail !== store.contactEmail
                }
                className="h-9 px-5 bg-gold-400 text-brand-900 rounded-lg text-xs font-semibold hover:bg-gold-500 transition-colors disabled:opacity-30 flex items-center gap-1.5"
              >
                {store.submitting ? (
                  <>
                    <Loader2 className="w-3.5 h-3.5 animate-spin" /> Submitting...
                  </>
                ) : (
                  <>
                    <Send className="w-3.5 h-3.5" /> Submit Request
                  </>
                )}
              </button>
            )}
          </div>
        </div>
      </div>
    </div>
  );
}

/* ─── EXPORTS ─── */
export default function HomePage() {
  return (
    <Suspense>
      <Elements stripe={stripePromise}>
        <AppContent />
      </Elements>
    </Suspense>
  );
}
