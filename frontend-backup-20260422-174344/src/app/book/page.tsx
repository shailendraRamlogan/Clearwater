"use client";

import { useEffect, useState, useRef } from "react";

import { format } from "date-fns";
import { motion, AnimatePresence } from "framer-motion";
import {
  ChevronLeft,
  ChevronRight,
  Calendar,
  Clock,
  Users,
  User,
  CreditCard,
  CheckCircle,
  Ship,
  Minus,
  Plus,
  PartyPopper,
} from "lucide-react";
import { Button } from "@/components/ui/button";
import "react-phone-input-2/lib/style.css";
import PhoneInput from "react-phone-input-2";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Switch } from "@/components/ui/switch";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { useBookingStore } from "@/stores/booking-store";
import { ModernCalendar } from "@/components/ui/calendar";
import { getAvailability, createBooking } from "@/lib/booking-service";
import { formatCurrency, formatTime } from "@/lib/utils";
import { toast } from "sonner";

const stepIcons = [
  Calendar,
  Clock,
  Users,
  User,
  CreditCard,
];

const stepLabels = [
  "Date",
  "Time",
  "Tickets",
  "Details",
  "Pay",
];

export default function BookingPage() {
  const store = useBookingStore();
  const [loading, setLoading] = useState(false);
  const [confirmEmail, setConfirmEmail] = useState("");
  const [errors, setErrors] = useState<Record<string, string>>({});
  const [showPayment, setShowPayment] = useState(false);
  const [cardData, setCardData] = useState({ number: "", expiry: "", cvc: "", name: "" });
  const [bookingId, setBookingId] = useState<string | null>(null);
  const [adultExpanded, setAdultExpanded] = useState(false);
  const [childExpanded, setChildExpanded] = useState(false);
  const adultDismissed = useRef(false);
  const childDismissed = useRef(false);
  const lastFetchedDate = useRef<string | null>(null);

  useEffect(() => {
    if (store.adultCount === 0) { setAdultExpanded(false); adultDismissed.current = false; }
  }, [store.adultCount]);

  useEffect(() => {
    if (store.childCount === 0) { setChildExpanded(false); childDismissed.current = false; }
  }, [store.childCount]);

  const fetchAvailability = async (date: Date) => {
    const dateStr = format(date, "yyyy-MM-dd");
    if (lastFetchedDate.current === dateStr) return;
    lastFetchedDate.current = dateStr;
    setLoading(true);
    try {
      const slots = await getAvailability(dateStr);
      store.setAvailableSlots(slots.filter((s) => !s.is_blocked && s.remaining_capacity > 0));
    } catch {
      toast.error("Could not load availability. Please try again.");
      store.setAvailableSlots([]);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    if (store.selectedDate && store.currentStep >= 2) {
      fetchAvailability(store.selectedDate);
    }
  }, [store.selectedDate, store.currentStep]);

  const handleDateSelect = (date: Date | undefined) => {
    store.setSelectedDate(date);
    store.setSelectedSlot(undefined);
  };

  const handleBooking = async () => {
    if (!store.selectedDate || !store.selectedSlot) return;
    if (
      !store.guest.first_name ||
      !store.guest.last_name ||
      !store.guest.email ||
      !store.guest.phone
    ) {
      toast.error("Please fill in all guest details.");
      return;
    }

    setLoading(true);
    try {
      const booking = await createBooking({
        tour_date: format(store.selectedDate, "yyyy-MM-dd"),
        time_slot_id: store.selectedSlot.id,
        adult_count: store.adultCount,
        child_count: store.childCount,
        package_upgrade: store.packageUpgrade,
        special_occasion: store.specialOccasion,
        special_comment: store.specialComment,
        guest: store.guest,
      });
      setBookingId(booking.id);
      toast.success("Booking confirmed! Check your email for details.");
    } catch {
      toast.error("Booking failed. Please try again.");
    } finally {
      setLoading(false);
    }
  };

  if (bookingId) {
    return (
      <div className="section-container py-8 sm:py-20">
        <motion.div
          initial={{ scale: 0.8, opacity: 0 }}
          animate={{ scale: 1, opacity: 1 }}
          className="max-w-lg mx-auto text-center"
        >
          <div className="inline-flex items-center justify-center w-20 h-20 bg-green-100 rounded-full mb-6">
            <CheckCircle className="h-10 w-10 text-green-500" />
          </div>
          <h1 className="font-display text-4xl font-bold mb-4">
            Booking Confirmed!
          </h1>
          <p className="text-ocean-500 mb-2">
            Your booking ID is{" "}
            <span className="font-mono font-bold text-ocean-700">
              {bookingId}
            </span>
          </p>
          <p className="text-ocean-500 mb-8">
            A confirmation email has been sent to{" "}
            <span className="font-medium">{store.guest.email}</span>
          </p>
          <div className="bg-ocean-50 rounded-xl p-6 mb-8 text-left">
            <h3 className="font-semibold mb-3">Booking Summary</h3>
            <div className="space-y-2 text-sm">
              <p>
                <span className="text-ocean-500">Date:</span>{" "}
                {format(store.selectedDate!, "EEEE, MMMM d, yyyy")}
              </p>
              <p>
                <span className="text-ocean-500">Time:</span>{" "}
                {formatTime(store.selectedSlot!.start_time)} —{" "}
                {store.selectedSlot!.boat_name}
              </p>
              <p>
                <span className="text-ocean-500">Guests:</span>{" "}
                {store.adultCount} adult{store.adultCount > 1 ? "s" : ""},{" "}
                {store.childCount} child{store.childCount !== 1 ? "ren" : ""}
              </p>
              <p>
                <span className="text-ocean-500">Total:</span>{" "}
                {formatCurrency(store.getTotal())}
              </p>
            </div>
          </div>
          <Button
            variant="outline"
            onClick={() => {
              store.reset();
              setBookingId(null);
            }}
          >
            Book Another Tour
          </Button>
        </motion.div>
      </div>
    );
  }

  return (
    <div className="relative overflow-hidden">
      {/* Ocean video background */}
      <div className="absolute inset-0 pointer-events-none" aria-hidden="true">
        <video
          className="absolute inset-0 w-full h-full object-cover"
          autoPlay
          muted
          loop
          playsInline
          poster=""
        >
          <source src="https://videos.pexels.com/video-files/3571264/3571264-uhd_2560_1440_30fps.mp4" type="video/mp4" />
        </video>
        {/* Warm overlay tint */}
        <div className="absolute inset-0 bg-amber-50/30" />
        {/* Center fade - video visible at 10% behind wizard */}
        <div className="absolute inset-0" style={{
          background: 'linear-gradient(to right, transparent 0%, rgba(255,255,255,0.3) 15%, rgba(255,255,255,0.7) 25%, rgba(255,255,255,0.9) 40%, rgba(255,255,255,0.9) 60%, rgba(255,255,255,0.7) 75%, rgba(255,255,255,0.3) 85%, transparent 100%)',
        }} />
      </div>

      <div className="section-container py-16 sm:py-24 relative z-10">
      {/* Progress */}
      <div className="mb-10">
        <div className="flex items-center justify-between max-w-2xl mx-auto mb-4">
          {stepIcons.map((Icon, i) => {
            const step = i + 1;
            const isActive = store.currentStep === step;
            const isComplete = store.currentStep > step;
            return (
              <div key={i} className="flex flex-col items-center">
                <div
                  className={`w-10 h-10 rounded-full flex items-center justify-center transition-all duration-300 ${
                    isComplete
                      ? "bg-ocean-500 text-white"
                      : isActive
                      ? "bg-ocean-500 text-white ring-4 ring-ocean-100"
                      : "bg-ocean-100 text-ocean-400"
                  }`}
                >
                  <Icon className="h-4 w-4" />
                </div>
                <span
                  className={`text-xs mt-2 font-medium hidden sm:block ${
                    isActive ? "text-ocean-700" : "text-ocean-400"
                  }`}
                >
                  {stepLabels[i]}
                </span>
              </div>
            );
          })}
        </div>
        <div className="max-w-2xl mx-auto h-1 bg-ocean-100 rounded-full overflow-hidden">
          <motion.div
            className="h-full bg-ocean-500 rounded-full"
            animate={{ width: `${((store.currentStep - 1) / 4) * 100}%` }}
          />
        </div>
      </div>

      {/* Steps */}
      <div className="max-w-2xl mx-auto">
        <AnimatePresence mode="wait">
          <motion.div
            key={store.currentStep}
            initial={{ opacity: 0, x: 20 }}
            animate={{ opacity: 1, x: 0 }}
            exit={{ opacity: 0, x: -20 }}
            transition={{ duration: 0.3 }}
          >
            {/* Step 1: Date */}
            {store.currentStep === 1 && (
              <Card>
                <CardHeader>
                  <CardTitle className="text-2xl">Select Your Date</CardTitle>
                </CardHeader>
                <CardContent>
                  <div className="flex justify-center">
                    <ModernCalendar
                      selected={store.selectedDate}
                      onSelect={(date) => handleDateSelect(date)}
                    />
                  </div>
                  <div className="mt-6 flex justify-end">
                    <Button
                      variant="cta"
                      disabled={!store.selectedDate}
                      onClick={() => store.nextStep()}
                    >
                      Choose Time
                      <ChevronRight className="ml-2 h-4 w-4" />
                    </Button>
                  </div>
                </CardContent>
              </Card>
            )}

            {/* Step 2: Time Slot */}
            {store.currentStep === 2 && (
              <Card>
                <CardHeader>
                  <CardTitle className="text-2xl">Choose a Time Slot</CardTitle>
                  <p className="text-ocean-500">
                    {format(store.selectedDate!, "EEEE, MMMM d, yyyy")}
                  </p>
                </CardHeader>
                <CardContent>
                  {loading ? (
                    <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                      {[1, 2, 3, 4].map((i) => (
                        <div
                          key={i}
                          className="h-28 bg-ocean-50 rounded-lg animate-pulse"
                        />
                      ))}
                    </div>
                  ) : !loading && store.availableSlots.length === 0 ? (
                    <div className="text-center py-12">
                      <p className="text-ocean-400">
                        No available time slots for this date.
                      </p>
                      <Button
                        variant="outline"
                        className="mt-4"
                        onClick={() => store.prevStep()}
                      >
                        Pick Another Date
                      </Button>
                    </div>
                  ) : (
                    <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                      {store.availableSlots.map((slot) => {
                        const isSelected =
                          store.selectedSlot?.id === slot.id;
                        const pct =
                          Math.round(
                            ((slot.max_capacity - slot.remaining_capacity) /
                              slot.max_capacity) *
                              100
                          );
                        return (
                          <button
                            key={slot.id}
                            onClick={() =>
                              store.setSelectedSlot(slot)
                            }
                            className={`relative p-4 rounded-xl border-2 text-left transition-all duration-200 hover:shadow-md ${
                              isSelected
                                ? "border-ocean-500 bg-ocean-50 shadow-md"
                                : "border-ocean-100 hover:border-ocean-300"
                            }`}
                          >
                            {isSelected && (
                              <div className="absolute top-2 right-2">
                                <CheckCircle className="h-5 w-5 text-ocean-500" />
                              </div>
                            )}
                            <div className="flex items-center gap-2 mb-2">
                              <Clock className="h-4 w-4 text-ocean-500" />
                              <span className="font-semibold text-lg">
                                {formatTime(slot.start_time)}
                              </span>
                            </div>
                            <div className="flex items-center gap-2 text-sm text-ocean-500">
                              <Ship className="h-4 w-4" />
                              <span>{slot.boat_name}</span>
                            </div>
                            <div className="mt-3">
                              <div className="flex justify-between text-xs mb-1">
                                <span>
                                  {slot.remaining_capacity} spots left
                                </span>
                                <span>{pct}% booked</span>
                              </div>
                              <div className="h-1.5 bg-ocean-100 rounded-full overflow-hidden">
                                <div
                                  className="h-full bg-ocean-500 rounded-full transition-all"
                                  style={{ width: `${pct}%` }}
                                />
                              </div>
                            </div>
                          </button>
                        );
                      })}
                    </div>
                  )}
                  <div className="mt-6 flex justify-between">
                    <Button variant="outline" onClick={() => store.prevStep()}>
                      <ChevronLeft className="mr-2 h-4 w-4" />
                      Back
                    </Button>
                    <Button
                      variant="cta"
                      disabled={!store.selectedSlot}
                      onClick={() => store.nextStep()}
                    >
                      Select Tickets
                      <ChevronRight className="ml-2 h-4 w-4" />
                    </Button>
                  </div>
                </CardContent>
              </Card>
            )}

            {/* Step 3: Tickets */}
            {store.currentStep === 3 && (
              <Card>
                <CardHeader>
                  <CardTitle className="text-xl sm:text-2xl">Select Tickets</CardTitle>
                  <p className="text-ocean-500">
                    {format(store.selectedDate!, "MMM d")} at{" "}
                    {formatTime(store.selectedSlot!.start_time)} —{" "}
                    {store.selectedSlot!.boat_name}
                  </p>
                </CardHeader>
                <CardContent className="space-y-6">
                  {/* Adult */}
                  <div className="rounded-xl overflow-hidden border border-ocean-100">
                    <button
                      onClick={() => { if (store.adultCount > 0) { const next = !adultExpanded; setAdultExpanded(next); if (!next) adultDismissed.current = true; else adultDismissed.current = false; } }}
                      className="w-full p-4 bg-ocean-50 hover:bg-ocean-100/70 transition-colors"
                    >
                      {/* Mobile: stacked centered */}
                      <div className="text-center sm:hidden space-y-3">
                        <p className="font-semibold flex items-center justify-center gap-2">🍺 Adult</p>
                        <p className="text-sm text-ocean-500">{formatCurrency(200)} per person</p>
                        <div className="flex items-center justify-center gap-6">
                          <Button variant="outline" size="icon" onClick={(e) => { e.stopPropagation(); store.setAdultCount(store.adultCount - 1); if (store.adultCount === 1) setAdultExpanded(false); }} disabled={store.adultCount <= 0}>
                            <Minus className="h-5 w-5" />
                          </Button>
                          <span className="text-2xl font-bold w-10 text-center">{store.adultCount}</span>
                          <Button variant="outline" size="icon" onClick={(e) => { e.stopPropagation(); store.setAdultCount(store.adultCount + 1); if (!adultDismissed.current) setAdultExpanded(true); }} disabled={store.adultCount + store.childCount >= 10}>
                            <Plus className="h-5 w-5" />
                          </Button>
                        </div>
                      </div>
                      {/* Desktop: inline */}
                      <div className="hidden sm:flex sm:items-center sm:justify-between">
                        <div>
                          <p className="font-semibold flex items-center gap-2">🍺 Adult</p>
                          <p className="text-sm text-ocean-500">{formatCurrency(200)} per person</p>
                        </div>
                        <div className="flex items-center gap-4">
                          {store.adultCount > 0 && <span className="text-xs text-ocean-400 font-medium">Details</span>}
                          <Button variant="outline" size="icon" onClick={(e) => { e.stopPropagation(); store.setAdultCount(store.adultCount - 1); if (store.adultCount === 1) setAdultExpanded(false); }} disabled={store.adultCount <= 0}>
                            <Minus className="h-4 w-4" />
                          </Button>
                          <span className="text-xl font-bold w-8 text-center">{store.adultCount}</span>
                          <Button variant="outline" size="icon" onClick={(e) => { e.stopPropagation(); store.setAdultCount(store.adultCount + 1); if (!adultDismissed.current) setAdultExpanded(true); }} disabled={store.adultCount + store.childCount >= 10}>
                            <Plus className="h-4 w-4" />
                          </Button>
                        </div>
                      </div>
                    </button>
                    {adultExpanded && store.adultCount > 0 && (
                      <motion.div initial={{ height: 0, opacity: 0 }} animate={{ height: "auto", opacity: 1 }} transition={{ duration: 0.2 }} className="border-t border-ocean-100">
                        <div className="p-4 bg-white space-y-2 text-sm text-ocean-600">
                          <p className="font-medium text-ocean-800">Includes (2.5 hour tour):</p>
                          <ul className="space-y-1.5">
                            <li className="flex items-start gap-2"><span>📸</span> Multiple action photos (snorkeling, boating, fun poses, sightseeing)</li>
                            <li className="flex items-start gap-2"><span>🍹</span> Homemade island lemonade — 2 of your choice</li>
                            <li className="flex items-start gap-2"><span>🍺</span> Bahamian beers (up to 3)</li>
                            <li className="flex items-start gap-2"><span>🍺</span> Bahamian Raddlers fruit-flavored beers (up to 3)</li>
                            <li className="flex items-start gap-2"><span>🥃</span> Caribbean rum tasting (banana, coconut, pineapple, peach)</li>
                            <li className="flex items-start gap-2"><span>🥤</span> Bottled water (x2)</li>
                            <li className="flex items-start gap-2"><span>🍿</span> Light snacks (variety of potato chips)</li>
                          </ul>
                        </div>
                      </motion.div>
                    )}
                  </div>

                  {/* Child */}
                  <div className="rounded-xl overflow-hidden border border-ocean-100">
                    <button
                      onClick={() => { if (store.childCount > 0) { const next = !childExpanded; setChildExpanded(next); if (!next) childDismissed.current = true; else childDismissed.current = false; } }}
                      className="w-full p-4 bg-sand-50 hover:bg-sand-100/70 transition-colors"
                    >
                      {/* Mobile: stacked centered */}
                      <div className="text-center sm:hidden space-y-3">
                        <p className="font-semibold flex items-center justify-center gap-2">🧒 Child</p>
                        <p className="text-sm text-ocean-500">{formatCurrency(150)} per child</p>
                        <div className="flex items-center justify-center gap-6">
                          <Button variant="outline" size="icon" onClick={(e) => { e.stopPropagation(); store.setChildCount(store.childCount - 1); if (store.childCount === 1) setChildExpanded(false); }} disabled={store.childCount <= 0}>
                            <Minus className="h-5 w-5" />
                          </Button>
                          <span className="text-2xl font-bold w-10 text-center">{store.childCount}</span>
                          <Button variant="outline" size="icon" onClick={(e) => { e.stopPropagation(); store.setChildCount(store.childCount + 1); if (!childDismissed.current) setChildExpanded(true); }} disabled={store.adultCount + store.childCount >= 10}>
                            <Plus className="h-5 w-5" />
                          </Button>
                        </div>
                      </div>
                      {/* Desktop: inline */}
                      <div className="hidden sm:flex sm:items-center sm:justify-between">
                        <div>
                          <p className="font-semibold flex items-center gap-2">🧒 Child</p>
                          <p className="text-sm text-ocean-500">{formatCurrency(150)} per child</p>
                        </div>
                        <div className="flex items-center gap-4">
                          {store.childCount > 0 && <span className="text-xs text-ocean-400 font-medium">Details</span>}
                          <Button variant="outline" size="icon" onClick={(e) => { e.stopPropagation(); store.setChildCount(store.childCount - 1); if (store.childCount === 1) setChildExpanded(false); }} disabled={store.childCount <= 0}>
                            <Minus className="h-4 w-4" />
                          </Button>
                          <span className="text-xl font-bold w-8 text-center">{store.childCount}</span>
                          <Button variant="outline" size="icon" onClick={(e) => { e.stopPropagation(); store.setChildCount(store.childCount + 1); if (!childDismissed.current) setChildExpanded(true); }} disabled={store.adultCount + store.childCount >= 10}>
                            <Plus className="h-4 w-4" />
                          </Button>
                        </div>
                      </div>
                    </button>
                    {childExpanded && store.childCount > 0 && (
                      <motion.div initial={{ height: 0, opacity: 0 }} animate={{ height: "auto", opacity: 1 }} transition={{ duration: 0.2 }} className="border-t border-ocean-100">
                        <div className="p-4 bg-white space-y-2 text-sm text-ocean-600">
                          <p className="font-medium text-ocean-800">Includes (2.5 hour tour):</p>
                          <ul className="space-y-1.5">
                            <li className="flex items-start gap-2"><span>📸</span> Multiple action photos included</li>
                            <li className="flex items-start gap-2"><span>🍹</span> Unleaded homemade island lemonade</li>
                            <li className="flex items-start gap-2"><span>🥤</span> Bottled water</li>
                            <li className="flex items-start gap-2"><span>🥂</span> Non-alcoholic sparkling beverages</li>
                            <li className="flex items-start gap-2"><span>🍿</span> Light snacks (variety of potato chips)</li>
                            <li className="flex items-start gap-2"><span>👨‍👩‍👧‍👦</span> Family-friendly activity</li>
                          </ul>
                        </div>
                      </motion.div>
                    )}
                  </div>

                  {/* Package Upgrade */}
                  <div className="flex items-center justify-between p-4 border border-ocean-100 rounded-xl">
                    <div>
                      <p className="font-semibold flex items-center gap-2">
                        <PartyPopper className="h-4 w-4 text-ocean-500" />
                        Photo Package Upgrade
                      </p>
                      <p className="text-sm text-ocean-500">
                        +{formatCurrency(75)} per person — All edited digital
                        photos + printed copies
                      </p>
                    </div>
                    <Switch
                      checked={store.packageUpgrade}
                      onCheckedChange={store.setPackageUpgrade}
                    />
                  </div>

                  {/* Special Occasion */}
                  <div className="flex items-center justify-between p-4 border border-ocean-100 rounded-xl">
                    <div>
                      <p className="font-semibold flex items-center gap-2">
                        🎉 Special Occasion
                      </p>
                      <p className="text-sm text-ocean-500">
                        Birthday, anniversary, proposal? Let us know!
                      </p>
                    </div>
                    <Switch
                      checked={store.specialOccasion}
                      onCheckedChange={store.setSpecialOccasion}
                    />
                  </div>

                  {store.specialOccasion && (
                    <motion.div
                      initial={{ opacity: 0, height: 0 }}
                      animate={{ opacity: 1, height: "auto" }}
                    >
                      <Label htmlFor="specialComment">
                        Tell us about the occasion
                      </Label>
                      <textarea
                        id="specialComment"
                        className="mt-1 flex w-full rounded-lg border border-ocean-200 bg-white px-4 py-2 text-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ocean-400 min-h-[80px]"
                        placeholder="e.g., It's Sarah's 30th birthday! Can we have a cake on the boat?"
                        value={store.specialComment}
                        onChange={(e) =>
                          store.setSpecialComment(e.target.value)
                        }
                      />
                    </motion.div>
                  )}

                  {/* Running Total */}
                  <div className="bg-ocean-900 text-white rounded-xl p-6">
                    <div className="flex justify-between items-center">
                      <span className="text-ocean-200">Total</span>
                      <span className="text-3xl font-bold">
                        {formatCurrency(store.getTotal())}
                      </span>
                    </div>
                  </div>

                  <div className="flex justify-between">
                    <Button
                      variant="outline"
                      onClick={() => store.prevStep()}
                    >
                      <ChevronLeft className="mr-2 h-4 w-4" />
                      Back
                    </Button>
                    <Button variant="cta" onClick={() => store.nextStep()} disabled={store.adultCount + store.childCount === 0}>
                      Guest Details
                      <ChevronRight className="ml-2 h-4 w-4" />
                    </Button>
                  </div>
                </CardContent>
              </Card>
            )}

            {/* Step 4: Guest Info */}
            {store.currentStep === 4 && (
              <Card>
                <CardHeader>
                  <CardTitle className="text-xl sm:text-2xl">Guest Details</CardTitle>
                </CardHeader>
                <CardContent className="space-y-4">
                  <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                      <Label htmlFor="firstName">First Name</Label>
                      <Input
                        id="firstName"
                        placeholder="John"
                        value={store.guest.first_name}
                        onChange={(e) => {
                          store.setGuest({ first_name: e.target.value });
                          setErrors(prev => ({ ...prev, first_name: "" }));
                        }}
                        className={errors.first_name ? "border-red-400 focus-visible:ring-red-400" : ""}
                      />
                      {errors.first_name && <p className="text-xs text-red-500 mt-1">{errors.first_name}</p>}
                    </div>
                    <div>
                      <Label htmlFor="lastName">Last Name</Label>
                      <Input
                        id="lastName"
                        placeholder="Doe"
                        value={store.guest.last_name}
                        onChange={(e) => {
                          store.setGuest({ last_name: e.target.value });
                          setErrors(prev => ({ ...prev, last_name: "" }));
                        }}
                        className={errors.last_name ? "border-red-400 focus-visible:ring-red-400" : ""}
                      />
                      {errors.last_name && <p className="text-xs text-red-500 mt-1">{errors.last_name}</p>}
                    </div>
                  </div>
                  <div>
                    <Label htmlFor="email">Email</Label>
                    <Input
                      id="email"
                      type="email"
                      placeholder="john@example.com"
                      value={store.guest.email}
                      onChange={(e) => {
                        store.setGuest({ email: e.target.value });
                        setErrors(prev => ({ ...prev, email: "" }));
                      }}
                    />
                  </div>
                  <div>
                    <Label htmlFor="confirmEmail">Confirm Email</Label>
                    <Input
                      id="confirmEmail"
                      type="email"
                      placeholder="john@example.com"
                      value={confirmEmail}
                      onChange={(e) => {
                        setConfirmEmail(e.target.value);
                        setErrors(prev => ({ ...prev, confirmEmail: "" }));
                      }}
                      className={errors.confirmEmail ? "border-red-400 focus-visible:ring-red-400" : ""}
                    />
                    {errors.confirmEmail && <p className="text-xs text-red-500 mt-1">{errors.confirmEmail}</p>}
                  </div>
                  <div>
                    <Label htmlFor="phone">Phone</Label>
                    <PhoneInput
                      country={'bs'}
                      value={store.guest.phone}
                      onChange={(phone) => store.setGuest({ phone })}
                      disableCountryCode={false}
                      disableDropdown={false}
                      enableAreaCodes={false}
                      countryCodeEditable={false}
                      inputClass="!w-full !border-ocean-200 !rounded-lg !py-2 !px-3 !text-sm !h-10 !bg-white focus:!ring-2 focus:!ring-ocean-400 focus:!outline-none !pl-14"
                      buttonClass="!rounded-l-lg !border-ocean-200 !bg-ocean-50"
                      dropdownClass="!rounded-lg"
                      containerClass="!mt-0"
                      inputProps={{ id: 'phone' }}
                    />
                    {errors.phone && <p className="text-xs text-red-500 mt-1">{errors.phone}</p>}
                  </div>

                  <div className="flex justify-between pt-4">
                    <Button
                      variant="outline"
                      onClick={() => store.prevStep()}
                    >
                      <ChevronLeft className="mr-2 h-4 w-4" />
                      Back
                    </Button>
                    <Button variant="cta" onClick={() => {
                      const g = store.guest;
                      const errs: Record<string, string> = {};
                      if (!g.first_name.trim()) errs.first_name = "Required";
                      if (!g.last_name.trim()) errs.last_name = "Required";
                      if (!g.email.trim()) errs.email = "Required";
                      if (!confirmEmail.trim()) errs.confirmEmail = "Required";
                      if (!g.phone.trim()) errs.phone = "Required";
                      if (Object.keys(errs).length) { setErrors(errs); return; }
                      if (g.email !== confirmEmail) { setErrors({ confirmEmail: "Emails do not match" }); return; }
                      const digits = g.phone.replace(/\D/g, "");
                      if (digits.length < 7) { setErrors({ phone: "At least 7 digits required" }); return; }
                      setErrors({});
                      store.nextStep();
                    }}>
                      Review & Pay
                      <ChevronRight className="ml-2 h-4 w-4" />
                    </Button>
                  </div>
                </CardContent>
              </Card>
            )}

            {/* Step 5: Review & Pay */}
            {store.currentStep === 5 && (
              <Card>
                <CardHeader>
                  <CardTitle className="text-xl sm:text-2xl">Review & Pay</CardTitle>
                </CardHeader>
                <CardContent className="space-y-6">
                  {/* Order Summary */}
                  <div className="bg-ocean-50 rounded-xl p-6 space-y-4">
                    <h3 className="font-semibold text-lg">Order Summary</h3>
                    <div className="space-y-2 text-sm">
                      <div className="flex justify-between">
                        <span className="text-ocean-500">Date</span>
                        <span className="font-medium">
                          {format(
                            store.selectedDate!,
                            "EEEE, MMMM d, yyyy"
                          )}
                        </span>
                      </div>
                      <div className="flex justify-between">
                        <span className="text-ocean-500">Time</span>
                        <span className="font-medium">
                          {formatTime(store.selectedSlot!.start_time)} —{" "}
                          {store.selectedSlot!.boat_name}
                        </span>
                      </div>
                      <div className="flex justify-between">
                        <span className="text-ocean-500">Adults</span>
                        <span>
                          {store.adultCount} × {formatCurrency(200)}
                        </span>
                      </div>
                      {store.childCount > 0 && (
                        <div className="flex justify-between">
                          <span className="text-ocean-500">Children</span>
                          <span>
                            {store.childCount} × {formatCurrency(150)}
                          </span>
                        </div>
                      )}
                      {store.packageUpgrade && (
                        <div className="flex justify-between">
                          <span className="text-ocean-500">
                            Photo Package
                          </span>
                          <span>
                            {store.adultCount + store.childCount} ×{" "}
                            {formatCurrency(75)}
                          </span>
                        </div>
                      )}
                    </div>
                    <div className="border-t border-ocean-200 pt-3 flex justify-between items-center">
                      <span className="font-semibold text-lg">Total</span>
                      <span className="text-2xl font-bold text-ocean-700">
                        {formatCurrency(store.getTotal())}
                      </span>
                    </div>
                  </div>

                  {/* Guest Summary */}
                  <div className="bg-ocean-50 rounded-xl p-6 space-y-2 text-sm">
                    <h3 className="font-semibold text-lg">Guest</h3>
                    <p>
                      {store.guest.first_name} {store.guest.last_name}
                    </p>
                    <p>{store.guest.email}</p>
                    <p>{store.guest.phone}</p>
                    {store.specialOccasion && (
                      <p className="text-ocean-600 italic">
                        🎉 Special occasion: {store.specialComment || "Not specified"}
                      </p>
                    )}
                  </div>

                  {/* Payment section */}
                  {showPayment && (
                    <motion.div initial={{ height: 0, opacity: 0 }} animate={{ height: "auto", opacity: 1 }} transition={{ duration: 0.3 }}>
                      <div className="border-t border-ocean-200 pt-6 space-y-4">
                        <h3 className="font-semibold text-lg">Card Details</h3>
                        <div>
                          <Label htmlFor="cardName">Name on Card</Label>
                          <Input id="cardName" placeholder="John Doe" value={cardData.name} onChange={(e) => setCardData({ ...cardData, name: e.target.value })} />
                        </div>
                        <div>
                          <Label htmlFor="cardNumber">Card Number</Label>
                          <Input id="cardNumber" placeholder="4242 4242 4242 4242" value={cardData.number} onChange={(e) => {
                            const v = e.target.value.replace(/\D/g, '').slice(0, 16);
                            const formatted = v.replace(/(.{4})/g, '$1 ').trim();
                            setCardData({ ...cardData, number: formatted });
                          }} />
                        </div>
                        <div className="grid grid-cols-2 gap-4">
                          <div>
                            <Label htmlFor="cardExpiry">Expiry</Label>
                            <Input id="cardExpiry" placeholder="MM/YY" value={cardData.expiry} onChange={(e) => {
                            let v = e.target.value.replace(/\D/g, '').slice(0, 4);
                            if (v.length >= 2) v = v.slice(0, 2) + '/' + v.slice(2);
                            setCardData({ ...cardData, expiry: v });
                          }} />
                          </div>
                          <div>
                            <Label htmlFor="cardCvc">CVC</Label>
                            <Input id="cardCvc" placeholder="123" value={cardData.cvc} onChange={(e) => setCardData({ ...cardData, cvc: e.target.value })} maxLength={4} />
                          </div>
                        </div>
                      </div>
                    </motion.div>
                  )}

                  <div className="flex justify-between">
                    <Button
                      variant="outline"
                      onClick={() => store.prevStep()}
                    >
                      <ChevronLeft className="mr-2 h-4 w-4" />
                      Back
                    </Button>
                    <Button
                      variant="cta"
                      size="lg"
                      disabled={loading}
                      onClick={showPayment ? handleBooking : () => setShowPayment(true)}
                    >
                      {loading
                        ? "Processing..."
                        : showPayment
                          ? "Confirm Payment"
                          : `Pay ${formatCurrency(store.getTotal())}`}
                    </Button>
                  </div>
                </CardContent>
              </Card>
            )}
          </motion.div>
        </AnimatePresence>
      </div>
      </div>
    </div>
  );
}
