"use client";

import { useEffect, useState } from "react";
import { useRouter } from "next/navigation";
import { format } from "date-fns";
import {
  ChevronLeft,
  ChevronRight,
  Users,
  Calendar,
  PartyPopper,
  User,
  Send,
  Plus,
  X,
  Minus,
  Sparkles,
  CheckCircle,
  Sun,
  CloudSun,
} from "lucide-react";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Textarea } from "@/components/ui/textarea";
import { Switch } from "@/components/ui/switch";
import { ModernCalendar } from "@/components/ui/calendar";
import { usePrivateTourStore } from "@/stores/private-tour-store";
import { createPrivateTourRequest } from "@/lib/private-tour-service";
import { toast } from "sonner";

const stepIcons = [Users, Calendar, PartyPopper, User];
const stepLabels = ["Party Size", "Preferred Dates", "Occasion", "Your Details"];

function PrivateTourPage() {
  const store = usePrivateTourStore();
  const router = useRouter();
  const [errors, setErrors] = useState<Record<string, string>>({});
  const [submittedRef, setSubmittedRef] = useState<string | null>(null);
  const [calendarDate, setCalendarDate] = useState<Date | undefined>(undefined);
  const [selectedTimePref, setSelectedTimePref] = useState<"morning" | "afternoon">("morning");

  // Cleanup store on unmount
  useEffect(() => {
    return () => usePrivateTourStore.getState().reset();
  }, []);

  const handleSubmit = async () => {
    setErrors({});
    if (!store.canSubmit()) {
      toast.error("Please fill in all required fields.");
      return;
    }

    store.setIsSubmitting(true);
    try {
      const result = await createPrivateTourRequest({
        contact_first_name: store.contactFirstName.trim(),
        contact_last_name: store.contactLastName.trim(),
        contact_email: store.contactEmail.trim(),
        contact_phone: store.contactPhone.trim(),
        adult_count: store.adultCount,
        child_count: store.childCount,
        infant_count: store.infantCount,
        has_occasion: store.hasOccasion,
        occasion_details: store.occasionDetails.trim(),
        preferred_dates: store.preferredDates.map((d) => ({
          date: d.date,
          time_preference: d.time_preference,
        })),
      });

      setSubmittedRef(result.booking_ref);
      store.setSubmittedRef(result.booking_ref);
    } catch (err: unknown) {
      const error = err as Error & { status?: number; errors?: Record<string, string[]> };
      if (error.errors) {
        const fieldErrors: Record<string, string> = {};
        for (const [field, msgs] of Object.entries(error.errors)) {
          fieldErrors[field] = Array.isArray(msgs) ? msgs[0] : String(msgs);
        }
        setErrors(fieldErrors);
        toast.error("Please fix the highlighted fields.");
      } else {
        toast.error(error.message || "Something went wrong. Please try again.");
      }
    } finally {
      store.setIsSubmitting(false);
    }
  };

  // Success state
  if (submittedRef) {
    return (
      <div className="section-container py-8 sm:py-20">
        <div className="max-w-lg mx-auto text-center">
          <div className="inline-flex items-center justify-center w-16 h-16 bg-green-100 rounded-full mb-6">
            <CheckCircle className="h-8 w-8 text-green-500" />
          </div>
          <h1 className="text-3xl font-bold mb-4 text-ocean-900">
            Request Submitted!
          </h1>
          <p className="text-ocean-500 mb-2">
            Your reference is{" "}
            <span className="font-mono font-bold text-ocean-700">
              {submittedRef}
            </span>
          </p>
          <p className="text-ocean-500 mb-8">
            We&apos;ll review your request and send you a quote within 24 hours.
            A confirmation email has been sent to{" "}
            <span className="font-medium">{store.contactEmail}</span>.
          </p>
          <div className="bg-ocean-50 rounded-lg p-6 mb-8 text-left">
            <h3 className="font-semibold mb-3 text-ocean-900">Request Summary</h3>
            <div className="space-y-2 text-sm text-ocean-600">
              <p>
                <span className="text-ocean-400">Party:</span>{" "}
                {store.adultCount} adult{store.adultCount !== 1 ? "s" : ""}
                {store.childCount > 0 && `, ${store.childCount} child${store.childCount !== 1 ? "ren" : ""}`}
                {store.infantCount > 0 && `, ${store.infantCount} infant${store.infantCount !== 1 ? "s" : ""}`}
              </p>
              {store.preferredDates.map((d, i) => (
                <p key={i}>
                  <span className="text-ocean-400">Date {i + 1}:</span>{" "}
                  {format(new Date(d.date + "T12:00:00"), "EEEE, MMMM d, yyyy")} — {d.time_preference}
                </p>
              ))}
              {store.hasOccasion && store.occasionDetails && (
                <p>
                  <span className="text-ocean-400">Occasion:</span> {store.occasionDetails}
                </p>
              )}
            </div>
          </div>
          <div className="flex gap-4 justify-center">
            <Button variant="outline" onClick={() => router.push("/book")}>
              Book a Regular Tour
            </Button>
            <Button
              variant="outline"
              onClick={() => {
                store.reset();
                setSubmittedRef(null);
              }}
            >
              Submit Another Request
            </Button>
          </div>
        </div>
      </div>
    );
  }

  return (
    <div className="relative overflow-hidden">
      {/* Background gradient */}
      <div className="absolute inset-0 bg-gradient-to-br from-ocean-50 via-white to-sand-50 pointer-events-none" />

      <div className="section-container py-16 sm:py-24 relative z-10">
        {/* Header */}
        <div className="text-center mb-10">
          <div className="inline-flex items-center gap-2 bg-ocean-100 text-ocean-700 px-4 py-1.5 rounded-full text-sm font-medium mb-4">
            <Sparkles className="h-4 w-4" />
            Private Tour
          </div>
          <h1 className="text-3xl sm:text-4xl font-bold text-ocean-900 mb-3">
            Book a Private Tour
          </h1>
          <p className="text-ocean-500 max-w-xl mx-auto">
            Have the whole boat to yourself! Tell us about your group and preferred dates,
            and we&apos;ll send you a custom quote.
          </p>
        </div>

        {/* Progress */}
        <div className="mb-10">
          <div className="flex items-center justify-between max-w-xl mx-auto mb-4">
            {stepIcons.map((Icon, i) => {
              const step = i + 1;
              const isActive = store.currentStep === step;
              const isComplete = store.currentStep > step;
              return (
                <div key={i} className="flex flex-col items-center">
                  <div
                    className={`w-10 h-10 rounded-full flex items-center justify-center transition-colors ${
                      isComplete
                        ? "bg-ocean-700 text-white"
                        : isActive
                        ? "bg-ocean-700 text-white ring-4 ring-ocean-200"
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
          <div className="max-w-xl mx-auto h-1 bg-ocean-100 rounded-full overflow-hidden">
            <div
              className="h-full bg-ocean-700 rounded-full transition-all duration-300"
              style={{ width: `${((store.currentStep - 1) / 3) * 100}%` }}
            />
          </div>
        </div>

        {/* Steps */}
        <div className="max-w-xl mx-auto">
          {/* Step 1: Party Size */}
          {store.currentStep === 1 && (
            <Card>
              <CardHeader>
                <CardTitle className="text-xl sm:text-2xl">How Many Guests?</CardTitle>
                <p className="text-ocean-500 text-sm">
                  Private tours accommodate up to 10 guests. Infants (≤2 years) are free and not counted.
                </p>
              </CardHeader>
              <CardContent className="space-y-6">
                {/* Adults */}
                <div className="flex items-center justify-between p-4 bg-ocean-50 rounded-lg">
                  <div>
                    <Label className="text-base font-medium">Adults</Label>
                    <p className="text-ocean-400 text-sm">Ages 18+</p>
                  </div>
                  <div className="flex items-center gap-3">
                    <button
                      onClick={() => store.setAdultCount(store.adultCount - 1)}
                      disabled={store.adultCount <= 0}
                      className="w-9 h-9 rounded-full border border-ocean-200 flex items-center justify-center hover:bg-ocean-100 disabled:opacity-40 transition-colors"
                    >
                      <Minus className="h-4 w-4" />
                    </button>
                    <span className="w-8 text-center text-lg font-bold text-ocean-900">
                      {store.adultCount}
                    </span>
                    <button
                      onClick={() => store.setAdultCount(store.adultCount + 1)}
                      disabled={store.totalPeople() >= 10}
                      className="w-9 h-9 rounded-full border border-ocean-200 flex items-center justify-center hover:bg-ocean-100 disabled:opacity-40 transition-colors"
                    >
                      <Plus className="h-4 w-4" />
                    </button>
                  </div>
                </div>

                {/* Children */}
                <div className="flex items-center justify-between p-4 bg-ocean-50 rounded-lg">
                  <div>
                    <Label className="text-base font-medium">Children</Label>
                    <p className="text-ocean-400 text-sm">Ages 3–17</p>
                  </div>
                  <div className="flex items-center gap-3">
                    <button
                      onClick={() => store.setChildCount(store.childCount - 1)}
                      disabled={store.childCount <= 0}
                      className="w-9 h-9 rounded-full border border-ocean-200 flex items-center justify-center hover:bg-ocean-100 disabled:opacity-40 transition-colors"
                    >
                      <Minus className="h-4 w-4" />
                    </button>
                    <span className="w-8 text-center text-lg font-bold text-ocean-900">
                      {store.childCount}
                    </span>
                    <button
                      onClick={() => store.setChildCount(store.childCount + 1)}
                      disabled={store.totalPeople() >= 10}
                      className="w-9 h-9 rounded-full border border-ocean-200 flex items-center justify-center hover:bg-ocean-100 disabled:opacity-40 transition-colors"
                    >
                      <Plus className="h-4 w-4" />
                    </button>
                  </div>
                </div>

                {/* Infants */}
                <div className="flex items-center justify-between p-4 bg-sand-50 rounded-lg">
                  <div>
                    <Label className="text-base font-medium">Infants</Label>
                    <p className="text-ocean-400 text-sm">≤2 years (Free)</p>
                  </div>
                  <div className="flex items-center gap-3">
                    <button
                      onClick={() => store.setInfantCount(store.infantCount - 1)}
                      disabled={store.infantCount <= 0}
                      className="w-9 h-9 rounded-full border border-sand-200 flex items-center justify-center hover:bg-sand-100 disabled:opacity-40 transition-colors"
                    >
                      <Minus className="h-4 w-4" />
                    </button>
                    <span className="w-8 text-center text-lg font-bold text-ocean-900">
                      {store.infantCount}
                    </span>
                    <button
                      onClick={() => store.setInfantCount(store.infantCount + 1)}
                      className="w-9 h-9 rounded-full border border-sand-200 flex items-center justify-center hover:bg-sand-100 transition-colors"
                    >
                      <Plus className="h-4 w-4" />
                    </button>
                  </div>
                </div>

                {/* Total */}
                <div className="text-center p-3 bg-ocean-700 text-white rounded-lg">
                  <span className="text-sm opacity-80">Total guests:</span>{" "}
                  <span className="text-xl font-bold">{store.totalPeople()}</span>
                  <span className="text-sm opacity-80"> / 10</span>
                </div>

                {errors.adult_count && (
                  <p className="text-sm text-red-500">{errors.adult_count}</p>
                )}

                <div className="flex justify-end">
                  <Button
                    variant="cta"
                    disabled={store.totalPeople() < 1}
                    onClick={() => store.nextStep()}
                  >
                    Choose Dates
                    <ChevronRight className="ml-2 h-4 w-4" />
                  </Button>
                </div>
              </CardContent>
            </Card>
          )}

          {/* Step 2: Preferred Dates */}
          {store.currentStep === 2 && (
            <Card>
              <CardHeader>
                <CardTitle className="text-xl sm:text-2xl">Preferred Dates</CardTitle>
                <p className="text-ocean-500 text-sm">
                  Pick up to 5 preferred dates. We&apos;ll do our best to accommodate you.
                </p>
              </CardHeader>
              <CardContent className="space-y-6">
                {/* Calendar */}
                <div className="flex justify-center">
                  <ModernCalendar
                    selected={calendarDate}
                    onSelect={(date) => setCalendarDate(date)}
                  />
                </div>

                {/* Time preference */}
                {calendarDate && (
                  <div className="flex gap-3 justify-center">
                    <button
                      onClick={() => setSelectedTimePref("morning")}
                      className={`flex items-center gap-2 px-5 py-3 rounded-lg border-2 transition-all ${
                        selectedTimePref === "morning"
                          ? "border-ocean-700 bg-ocean-50 text-ocean-700"
                          : "border-ocean-100 text-ocean-500 hover:border-ocean-200"
                      }`}
                    >
                      <Sun className="h-4 w-4" />
                      Morning
                    </button>
                    <button
                      onClick={() => setSelectedTimePref("afternoon")}
                      className={`flex items-center gap-2 px-5 py-3 rounded-lg border-2 transition-all ${
                        selectedTimePref === "afternoon"
                          ? "border-ocean-700 bg-ocean-50 text-ocean-700"
                          : "border-ocean-100 text-ocean-500 hover:border-ocean-200"
                      }`}
                    >
                      <CloudSun className="h-4 w-4" />
                      Afternoon
                    </button>
                  </div>
                )}

                {/* Add date button */}
                {calendarDate && (
                  <div className="flex justify-center">
                    <Button
                      variant="outline"
                      disabled={
                        store.preferredDates.length >= 5 ||
                        store.preferredDates.some(
                          (d) =>
                            d.date === format(calendarDate, "yyyy-MM-dd") &&
                            d.time_preference === selectedTimePref
                        )
                      }
                      onClick={() => {
                        store.addPreferredDate(
                          format(calendarDate, "yyyy-MM-dd"),
                          selectedTimePref
                        );
                        setCalendarDate(undefined);
                      }}
                    >
                      <Plus className="h-4 w-4 mr-2" />
                      Add {format(calendarDate, "MMM d")} — {selectedTimePref}
                    </Button>
                  </div>
                )}

                {/* Selected dates list */}
                {store.preferredDates.length > 0 && (
                  <div className="space-y-2">
                    <p className="text-sm font-medium text-ocean-700">
                      Your preferred dates ({store.preferredDates.length}/5):
                    </p>
                    {store.preferredDates.map((d, i) => (
                      <div
                        key={i}
                        className="flex items-center justify-between p-3 bg-ocean-50 rounded-lg"
                      >
                        <div className="flex items-center gap-2 text-sm">
                          <Calendar className="h-4 w-4 text-ocean-500" />
                          <span className="font-medium">
                            {format(new Date(d.date + "T12:00:00"), "EEEE, MMMM d, yyyy")}
                          </span>
                          <span className="text-ocean-400">—</span>
                          <span className="text-ocean-600 capitalize">{d.time_preference}</span>
                        </div>
                        <button
                          onClick={() => store.removePreferredDate(i)}
                          className="text-ocean-400 hover:text-red-500 transition-colors"
                        >
                          <X className="h-4 w-4" />
                        </button>
                      </div>
                    ))}
                  </div>
                )}

                {errors.preferred_dates && (
                  <p className="text-sm text-red-500">{errors.preferred_dates}</p>
                )}

                <div className="flex justify-between">
                  <Button variant="outline" onClick={() => store.prevStep()}>
                    <ChevronLeft className="mr-2 h-4 w-4" />
                    Back
                  </Button>
                  <Button
                    variant="cta"
                    disabled={store.preferredDates.length < 1}
                    onClick={() => store.nextStep()}
                  >
                    Continue
                    <ChevronRight className="ml-2 h-4 w-4" />
                  </Button>
                </div>
              </CardContent>
            </Card>
          )}

          {/* Step 3: Occasion */}
          {store.currentStep === 3 && (
            <Card>
              <CardHeader>
                <CardTitle className="text-xl sm:text-2xl">Special Occasion?</CardTitle>
                <p className="text-ocean-500 text-sm">
                  Celebrating something? Let us know so we can make it extra special.
                </p>
              </CardHeader>
              <CardContent className="space-y-6">
                <div className="flex items-center justify-between p-4 bg-ocean-50 rounded-lg">
                  <div className="flex items-center gap-3">
                    <PartyPopper className="h-5 w-5 text-ocean-500" />
                    <div>
                      <Label className="text-base font-medium">This is a special occasion</Label>
                      <p className="text-ocean-400 text-sm">Birthday, anniversary, proposal, etc.</p>
                    </div>
                  </div>
                  <Switch
                    checked={store.hasOccasion}
                    onCheckedChange={(checked) => store.setHasOccasion(checked)}
                  />
                </div>

                {store.hasOccasion && (
                  <div className="space-y-2">
                    <Label htmlFor="occasion-details">Tell us about it</Label>
                    <Textarea
                      id="occasion-details"
                      placeholder="e.g., We're celebrating my daughter's 10th birthday!..."
                      value={store.occasionDetails}
                      onChange={(e) => store.setOccasionDetails(e.target.value)}
                      rows={3}
                    />
                  </div>
                )}

                <div className="flex justify-between">
                  <Button variant="outline" onClick={() => store.prevStep()}>
                    <ChevronLeft className="mr-2 h-4 w-4" />
                    Back
                  </Button>
                  <Button variant="cta" onClick={() => store.nextStep()}>
                    Your Details
                    <ChevronRight className="ml-2 h-4 w-4" />
                  </Button>
                </div>
              </CardContent>
            </Card>
          )}

          {/* Step 4: Contact Details + Submit */}
          {store.currentStep === 4 && (
            <Card>
              <CardHeader>
                <CardTitle className="text-xl sm:text-2xl">Your Details</CardTitle>
                <p className="text-ocean-500 text-sm">
                  We&apos;ll use this to send you a quote and coordinate the tour.
                </p>
              </CardHeader>
              <CardContent className="space-y-5">
                <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                  <div className="space-y-2">
                    <Label htmlFor="first-name">First Name *</Label>
                    <Input
                      id="first-name"
                      placeholder="John"
                      value={store.contactFirstName}
                      onChange={(e) => store.setContactFirstName(e.target.value)}
                      className={errors.contact_first_name ? "border-red-400" : ""}
                    />
                    {errors.contact_first_name && (
                      <p className="text-xs text-red-500">{errors.contact_first_name}</p>
                    )}
                  </div>
                  <div className="space-y-2">
                    <Label htmlFor="last-name">Last Name *</Label>
                    <Input
                      id="last-name"
                      placeholder="Smith"
                      value={store.contactLastName}
                      onChange={(e) => store.setContactLastName(e.target.value)}
                      className={errors.contact_last_name ? "border-red-400" : ""}
                    />
                    {errors.contact_last_name && (
                      <p className="text-xs text-red-500">{errors.contact_last_name}</p>
                    )}
                  </div>
                </div>

                <div className="space-y-2">
                  <Label htmlFor="email">Email *</Label>
                  <Input
                    id="email"
                    type="email"
                    placeholder="john@example.com"
                    value={store.contactEmail}
                    onChange={(e) => store.setContactEmail(e.target.value)}
                    className={errors.contact_email ? "border-red-400" : ""}
                  />
                  {errors.contact_email && (
                    <p className="text-xs text-red-500">{errors.contact_email}</p>
                  )}
                </div>

                <div className="space-y-2">
                  <Label htmlFor="phone">Phone *</Label>
                  <Input
                    id="phone"
                    type="tel"
                    placeholder="+1 (242) 123-4567"
                    value={store.contactPhone}
                    onChange={(e) => store.setContactPhone(e.target.value)}
                    className={errors.contact_phone ? "border-red-400" : ""}
                  />
                  {errors.contact_phone && (
                    <p className="text-xs text-red-500">{errors.contact_phone}</p>
                  )}
                </div>

                {/* Summary */}
                <div className="bg-ocean-50 rounded-lg p-4 space-y-2 text-sm">
                  <h4 className="font-semibold text-ocean-900">Request Summary</h4>
                  <div className="grid grid-cols-2 gap-y-1 text-ocean-600">
                    <span className="text-ocean-400">Party:</span>
                    <span>
                      {store.adultCount} adult{store.adultCount !== 1 ? "s" : ""}
                      {store.childCount > 0 && `, ${store.childCount} child${store.childCount !== 1 ? "ren" : ""}`}
                      {store.infantCount > 0 && `, ${store.infantCount} infant${store.infantCount !== 1 ? "s" : ""}`}
                    </span>
                    <span className="text-ocean-400">Preferred dates:</span>
                    <span>
                      {store.preferredDates.map((d, i) => (
                        <span key={i}>
                          {format(new Date(d.date + "T12:00:00"), "MMM d")}
                          {i < store.preferredDates.length - 1 ? ", " : ""}
                        </span>
                      ))}
                    </span>
                    {store.hasOccasion && store.occasionDetails && (
                      <>
                        <span className="text-ocean-400">Occasion:</span>
                        <span>{store.occasionDetails}</span>
                      </>
                    )}
                  </div>
                </div>

                <div className="flex justify-between">
                  <Button variant="outline" onClick={() => store.prevStep()}>
                    <ChevronLeft className="mr-2 h-4 w-4" />
                    Back
                  </Button>
                  <Button
                    variant="cta"
                    disabled={store.isSubmitting}
                    onClick={handleSubmit}
                  >
                    {store.isSubmitting ? (
                      <>
                        <div className="h-4 w-4 border-2 border-white/30 border-t-white rounded-full animate-spin mr-2" />
                        Submitting...
                      </>
                    ) : (
                      <>
                        <Send className="mr-2 h-4 w-4" />
                        Submit Request
                      </>
                    )}
                  </Button>
                </div>
              </CardContent>
            </Card>
          )}
        </div>

        {/* Info box */}
        <div className="max-w-xl mx-auto mt-8">
          <div className="bg-amber-50 border border-amber-200 rounded-lg p-4 text-sm text-amber-800">
            <p className="font-medium mb-1">How it works:</p>
            <ol className="list-decimal list-inside space-y-1 text-amber-700">
              <li>Submit your private tour request with preferred dates</li>
              <li>We review and send you a custom quote within 24 hours</li>
              <li>Confirm your date, time, and pay securely online</li>
              <li>Enjoy your exclusive private tour! 🚤</li>
            </ol>
          </div>
        </div>
      </div>
    </div>
  );
}

export default PrivateTourPage;
