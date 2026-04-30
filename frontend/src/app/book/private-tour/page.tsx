"use client";

import { useState } from "react";
import {
  User,
  Users,
  Calendar,
  Heart,
  ClipboardCheck,
  ChevronLeft,
  ChevronRight,
  Sparkles,
  Minus,
  Plus,
  X,
  Sun,
  CloudSun,
} from "lucide-react";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Card, CardContent } from "@/components/ui/card";
import { Textarea } from "@/components/ui/textarea";
import "react-phone-input-2/lib/style.css";
import PhoneInput from "react-phone-input-2";
import { usePrivateTourStore } from "@/stores/private-tour-store";
import { createPrivateTourRequest } from "@/lib/private-tour-service";
// formatDate not needed here — dates formatted inline
import { toast } from "sonner";
import Link from "next/link";

const stepIcons = [User, Users, Calendar, Heart, ClipboardCheck];
const stepLabels = ["Contact", "Guests", "Dates", "Occasion", "Review"];

function PrivateTourForm() {
  const store = usePrivateTourStore();
  const [errors, setErrors] = useState<Record<string, string>>({});
  const [selectedDate, setSelectedDate] = useState<string>("");

  const totalPayingGuests = store.adultCount + store.childCount;

  function validateStep(step: number): boolean {
    const e: Record<string, string> = {};
    setErrors({});

    if (step === 0) {
      if (!store.firstName.trim()) e.firstName = "First name is required";
      if (!store.lastName.trim()) e.lastName = "Last name is required";
      if (!store.email.trim() || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(store.email))
        e.email = "Valid email is required";
      if (!store.phone.trim() || store.phone.length < 6)
        e.phone = "Valid phone number is required";
    } else if (step === 1) {
      if (totalPayingGuests === 0)
        e.guests = "At least 1 adult or child is required";
      if (totalPayingGuests > 10)
        e.guests = "Maximum 10 guests (adults + children)";
    } else if (step === 2) {
      if (store.preferredDates.length === 0)
        e.dates = "Please select at least 1 preferred date";
      if (store.preferredDates.length > 5)
        e.dates = "Maximum 5 preferred dates";
    } else if (step === 3) {
      if (store.hasOccasion && !store.occasionDetails.trim())
        e.occasion = "Please describe the occasion";
    }

    if (Object.keys(e).length > 0) {
      setErrors(e);
      return false;
    }
    return true;
  }

  function nextStep() {
    if (validateStep(store.currentStep)) {
      store.setStep(store.currentStep + 1);
    }
  }

  function prevStep() {
    store.setStep(store.currentStep - 1);
  }

  function handleAddDate() {
    if (!selectedDate) return;
    if (store.preferredDates.some((d) => d.date === selectedDate)) {
      toast.error("Date already selected");
      return;
    }
    if (store.preferredDates.length >= 5) {
      toast.error("Maximum 5 dates");
      return;
    }
    store.addPreferredDate(selectedDate, "morning");
    setSelectedDate("");
  }

  async function handleSubmit() {
    if (!validateStep(4)) return;
    store.setSubmitting(true);
    store.setError(null);

    try {
      const result = await createPrivateTourRequest({
        contact_first_name: store.firstName,
        contact_last_name: store.lastName,
        contact_email: store.email,
        contact_phone: store.phone,
        adult_count: store.adultCount,
        child_count: store.childCount,
        infant_count: store.infantCount,
        has_occasion: store.hasOccasion,
        occasion_details: store.hasOccasion
          ? store.occasionDetails
          : undefined,
        preferred_dates: store.preferredDates.map((d) => ({
          date: d.date,
          time_preference: d.time_preference,
        })),
      });
      store.setSubmittedRef(result.booking_ref);
      toast.success("Request submitted! We'll be in touch soon.");
    } catch (err: unknown) {
      const error = err as { response?: { data?: { message?: string; errors?: Record<string, string> } } };
      const msg =
        error.response?.data?.message ||
        Object.values(error.response?.data?.errors || {})[0] ||
        "Something went wrong. Please try again.";
      store.setError(msg);
      toast.error(msg);
    } finally {
      store.setSubmitting(false);
    }
  }

  // Confirmation screen
  if (store.submittedRef) {
    return (
      <div className="min-h-screen bg-gradient-to-b from-ocean-50 to-white flex items-center justify-center px-4 -mt-16">
        <Card className="max-w-lg w-full border-ocean-100 shadow-lg">
          <CardContent className="pt-10 pb-10 text-center">
            <div className="inline-flex items-center justify-center w-16 h-16 bg-green-100 rounded-full mb-6">
              <Sparkles className="h-8 w-8 text-green-600" />
            </div>
            <h1 className="text-2xl font-bold text-ocean-900 mb-3">
              Request Submitted!
            </h1>
            <p className="text-ocean-500 mb-6 leading-relaxed">
              Thank you for your interest in a private tour! We&apos;ll review
              your request and get back to you within{" "}
              <strong>24–48 hours</strong> with a personalized quote.
            </p>
            <div className="bg-ocean-50 rounded-lg p-4 mb-6">
              <p className="text-sm text-ocean-500 mb-1">Your Reference</p>
              <p className="text-xl font-bold text-ocean-700 font-mono">
                {store.submittedRef}
              </p>
            </div>
            <p className="text-sm text-ocean-400 mb-6">
              A confirmation email has been sent to{" "}
              <strong>{store.email}</strong>
            </p>
            <div className="flex flex-col sm:flex-row gap-3 justify-center">
              <Link href="/">
                <Button variant="outline" className="w-full sm:w-auto">
                  Back to Home
                </Button>
              </Link>
              <Button
                variant="cta"
                className="w-full sm:w-auto"
                onClick={() => store.reset()}
              >
                Submit Another Request
              </Button>
            </div>
          </CardContent>
        </Card>
      </div>
    );
  }

  return (
    <div className="min-h-screen bg-gradient-to-b from-ocean-50 to-white -mt-16">
      <div className="section-container py-8 sm:py-16">
        {/* Header */}
        <div className="text-center mb-10">
          <div className="inline-flex items-center gap-2 bg-ocean-100 text-ocean-700 px-4 py-2 rounded-full text-sm font-medium mb-4">
            <Sparkles className="h-4 w-4" />
            Private Tour
          </div>
          <h1 className="text-3xl sm:text-4xl font-bold text-ocean-900 mb-3">
            Book a Private Tour
          </h1>
          <p className="text-ocean-500 max-w-xl mx-auto">
            Get the whole boat to yourself! Perfect for celebrations, family
            reunions, or a special day on the water. Up to 10 guests.
          </p>
        </div>

        {/* Step Indicator */}
        <div className="flex items-center justify-center gap-1 sm:gap-2 mb-10 max-w-2xl mx-auto">
          {stepIcons.map((Icon, idx) => (
            <div key={idx} className="flex items-center">
              <div className="flex flex-col items-center">
                <div
                  className={`w-10 h-10 sm:w-12 sm:h-12 rounded-full flex items-center justify-center transition-colors ${
                    i < store.currentStep
                      ? "bg-ocean-700 text-white"
                      : i === store.currentStep
                        ? "bg-ocean-700 text-white ring-4 ring-ocean-100"
                        : "bg-ocean-100 text-ocean-400"
                  }`}
                >
                  {i < store.currentStep ? (
                    <svg
                      className="w-5 h-5"
                      fill="none"
                      viewBox="0 0 24 24"
                      stroke="currentColor"
                    >
                      <path
                        strokeLinecap="round"
                        strokeLinejoin="round"
                        strokeWidth={2}
                        d="M5 13l4 4L19 7"
                      />
                    </svg>
                  ) : (
                    <Icon className="w-5 h-5" />
                  )}
                </div>
                <span
                  className={`text-xs mt-1.5 font-medium hidden sm:block ${
                    i <= store.currentStep
                      ? "text-ocean-700"
                      : "text-ocean-300"
                  }`}
                >
                  {stepLabels[i]}
                </span>
              </div>
              {idx < stepIcons.length - 1 && (
                <div
                  className={`w-8 sm:w-16 h-0.5 mx-1 rounded transition-colors ${
                    idx < store.currentStep ? "bg-ocean-700" : "bg-ocean-200"
                  }`}
                />
              )}
            </div>
          ))}
        </div>

        {/* Form Card */}
        <Card className="max-w-2xl mx-auto border-ocean-100 shadow-sm">
          <CardContent className="pt-8 pb-8">
            {/* Step 0: Contact Info */}
            {store.currentStep === 0 && (
              <div className="space-y-6">
                <h2 className="text-xl font-semibold text-ocean-900 mb-1">
                  Contact Information
                </h2>
                <p className="text-ocean-400 text-sm mb-4">
                  We&apos;ll use this to send you your quote.
                </p>
                <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                  <div className="space-y-2">
                    <Label htmlFor="firstName">First Name</Label>
                    <Input
                      id="firstName"
                      placeholder="John"
                      value={store.firstName}
                      onChange={(e) =>
                        store.setContact(
                          e.target.value,
                          store.lastName,
                          store.email,
                          store.phone
                        )
                      }
                    />
                    {errors.firstName && (
                      <p className="text-sm text-red-500">{errors.firstName}</p>
                    )}
                  </div>
                  <div className="space-y-2">
                    <Label htmlFor="lastName">Last Name</Label>
                    <Input
                      id="lastName"
                      placeholder="Doe"
                      value={store.lastName}
                      onChange={(e) =>
                        store.setContact(
                          store.firstName,
                          e.target.value,
                          store.email,
                          store.phone
                        )
                      }
                    />
                    {errors.lastName && (
                      <p className="text-sm text-red-500">{errors.lastName}</p>
                    )}
                  </div>
                  <div className="space-y-2">
                    <Label htmlFor="email">Email</Label>
                    <Input
                      id="email"
                      type="email"
                      placeholder="john@example.com"
                      value={store.email}
                      onChange={(e) =>
                        store.setContact(
                          store.firstName,
                          store.lastName,
                          e.target.value,
                          store.phone
                        )
                      }
                    />
                    {errors.email && (
                      <p className="text-sm text-red-500">{errors.email}</p>
                    )}
                  </div>
                  <div className="space-y-2">
                    <Label htmlFor="phone">Phone</Label>
                    <PhoneInput
                      country={"bs"}
                      value={store.phone}
                      onChange={(phone) =>
                        store.setContact(
                          store.firstName,
                          store.lastName,
                          store.email,
                          phone
                        )
                      }
                      inputStyle={{
                        width: "100%",
                        height: "42px",
                        fontSize: "14px",
                        borderColor: errors.phone ? "#ef4444" : undefined,
                      }}
                      containerStyle={{ marginTop: "0" }}
                    />
                    {errors.phone && (
                      <p className="text-sm text-red-500">{errors.phone}</p>
                    )}
                  </div>
                </div>
              </div>
            )}

            {/* Step 1: Guest Counts */}
            {store.currentStep === 1 && (
              <div className="space-y-6">
                <h2 className="text-xl font-semibold text-ocean-900 mb-1">
                  Guest Count
                </h2>
                <p className="text-ocean-400 text-sm mb-4">
                  Private tours accommodate up to 10 paying guests (adults +
                  children).
                </p>

                {errors.guests && (
                  <div className="bg-red-50 border border-red-200 rounded-lg p-3 text-sm text-red-600">
                    {errors.guests}
                  </div>
                )}

                <div className="space-y-4">
                  {/* Adults */}
                  <div className="flex items-center justify-between bg-white border border-ocean-100 rounded-lg p-4">
                    <div>
                      <p className="font-medium text-ocean-900">Adults</p>
                      <p className="text-sm text-ocean-400">Age 18+</p>
                    </div>
                    <div className="flex items-center gap-3">
                      <Button
                        variant="outline"
                        size="icon"
                        className="h-9 w-9"
                        onClick={() =>
                          store.setGuestCounts(
                            Math.max(0, store.adultCount - 1),
                            store.childCount,
                            store.infantCount
                          )
                        }
                      >
                        <Minus className="h-4 w-4" />
                      </Button>
                      <span className="w-8 text-center text-lg font-semibold text-ocean-900">
                        {store.adultCount}
                      </span>
                      <Button
                        variant="outline"
                        size="icon"
                        className="h-9 w-9"
                        disabled={totalPayingGuests >= 10}
                        onClick={() =>
                          store.setGuestCounts(
                            store.adultCount + 1,
                            store.childCount,
                            store.infantCount
                          )
                        }
                      >
                        <Plus className="h-4 w-4" />
                      </Button>
                    </div>
                  </div>

                  {/* Children */}
                  <div className="flex items-center justify-between bg-white border border-ocean-100 rounded-lg p-4">
                    <div>
                      <p className="font-medium text-ocean-900">Children</p>
                      <p className="text-sm text-ocean-400">Age 3–17</p>
                    </div>
                    <div className="flex items-center gap-3">
                      <Button
                        variant="outline"
                        size="icon"
                        className="h-9 w-9"
                        onClick={() =>
                          store.setGuestCounts(
                            store.adultCount,
                            Math.max(0, store.childCount - 1),
                            store.infantCount
                          )
                        }
                      >
                        <Minus className="h-4 w-4" />
                      </Button>
                      <span className="w-8 text-center text-lg font-semibold text-ocean-900">
                        {store.childCount}
                      </span>
                      <Button
                        variant="outline"
                        size="icon"
                        className="h-9 w-9"
                        disabled={totalPayingGuests >= 10}
                        onClick={() =>
                          store.setGuestCounts(
                            store.adultCount,
                            store.childCount + 1,
                            store.infantCount
                          )
                        }
                      >
                        <Plus className="h-4 w-4" />
                      </Button>
                    </div>
                  </div>

                  {/* Infants */}
                  <div className="flex items-center justify-between bg-white border border-ocean-100 rounded-lg p-4">
                    <div>
                      <p className="font-medium text-ocean-900">Infants</p>
                      <p className="text-sm text-ocean-400">
                        Age 2 and under — free!
                      </p>
                    </div>
                    <div className="flex items-center gap-3">
                      <Button
                        variant="outline"
                        size="icon"
                        className="h-9 w-9"
                        onClick={() =>
                          store.setGuestCounts(
                            store.adultCount,
                            store.childCount,
                            Math.max(0, store.infantCount - 1)
                          )
                        }
                      >
                        <Minus className="h-4 w-4" />
                      </Button>
                      <span className="w-8 text-center text-lg font-semibold text-ocean-900">
                        {store.infantCount}
                      </span>
                      <Button
                        variant="outline"
                        size="icon"
                        className="h-9 w-9"
                        onClick={() =>
                          store.setGuestCounts(
                            store.adultCount,
                            store.childCount,
                            store.infantCount + 1
                          )
                        }
                      >
                        <Plus className="h-4 w-4" />
                      </Button>
                    </div>
                  </div>
                </div>

                <div className="bg-ocean-50 rounded-lg p-4 text-sm text-ocean-600 text-center">
                  Total paying guests:{" "}
                  <strong className="text-ocean-900">
                    {totalPayingGuests} / 10
                  </strong>
                  {store.infantCount > 0 && (
                    <span className="text-ocean-400">
                      {" "}
                      (+ {store.infantCount} infant
                      {store.infantCount > 1 ? "s" : ""} free)
                    </span>
                  )}
                </div>
              </div>
            )}

            {/* Step 2: Preferred Dates */}
            {store.currentStep === 2 && (
              <div className="space-y-6">
                <h2 className="text-xl font-semibold text-ocean-900 mb-1">
                  Preferred Dates
                </h2>
                <p className="text-ocean-400 text-sm mb-4">
                  Select up to 5 dates that work for you. We&apos;ll confirm one
                  based on availability.
                </p>

                {errors.dates && (
                  <div className="bg-red-50 border border-red-200 rounded-lg p-3 text-sm text-red-600">
                    {errors.dates}
                  </div>
                )}

                {/* Date Picker */}
                <div className="flex flex-col sm:flex-row gap-3">
                  <Input
                    type="date"
                    value={selectedDate}
                    onChange={(e) => setSelectedDate(e.target.value)}
                    min={new Date().toISOString().split("T")[0]}
                    className="flex-1"
                  />
                  <Button
                    variant="cta"
                    onClick={handleAddDate}
                    disabled={
                      !selectedDate || store.preferredDates.length >= 5
                    }
                  >
                    <Plus className="h-4 w-4 mr-2" />
                    Add Date
                  </Button>
                </div>

                {/* Selected Dates as Chips */}
                {store.preferredDates.length > 0 && (
                  <div className="space-y-3">
                    <p className="text-sm font-medium text-ocean-700">
                      {store.preferredDates.length} of 5 dates selected
                    </p>
                    <div className="flex flex-wrap gap-2">
                      {store.preferredDates.map((d) => {
                        const dateObj = new Date(d.date + "T12:00:00");
                        const formatted = dateObj.toLocaleDateString("en-US", {
                          weekday: "short",
                          month: "short",
                          day: "numeric",
                        });
                        const isMorning = d.time_preference === "morning";

                        return (
                          <div
                            key={d.date}
                            className="flex items-center gap-1 bg-ocean-50 border border-ocean-200 rounded-full pl-1 pr-2 py-1"
                          >
                            <button
                              onClick={() =>
                                store.updateTimePreference(
                                  d.date,
                                  isMorning ? "afternoon" : "morning"
                                )
                              }
                              className="flex items-center gap-1.5 px-2 py-1 rounded-full text-xs font-medium transition-colors cursor-pointer"
                              title="Click to toggle morning/afternoon"
                            >
                              {isMorning ? (
                                <>
                                  <Sun className="h-3.5 w-3.5 text-amber-500" />
                                  <span className="text-amber-700">AM</span>
                                </>
                              ) : (
                                <>
                                  <CloudSun className="h-3.5 w-3.5 text-orange-500" />
                                  <span className="text-orange-700">PM</span>
                                </>
                              )}
                            </button>
                            <span className="text-sm text-ocean-800 font-medium">
                              {formatted}
                            </span>
                            <button
                              onClick={() => store.removePreferredDate(d.date)}
                              className="text-ocean-400 hover:text-red-500 transition-colors ml-1"
                            >
                              <X className="h-3.5 w-3.5" />
                            </button>
                          </div>
                        );
                      })}
                    </div>
                    <p className="text-xs text-ocean-400">
                      💡 Click the sun/moon icon to toggle between morning and
                      afternoon preference
                    </p>
                  </div>
                )}
              </div>
            )}

            {/* Step 3: Occasion */}
            {store.currentStep === 3 && (
              <div className="space-y-6">
                <h2 className="text-xl font-semibold text-ocean-900 mb-1">
                  Special Occasion
                </h2>
                <p className="text-ocean-400 text-sm mb-4">
                  Let us know if this is for a special occasion so we can make
                  it extra memorable!
                </p>

                <div className="flex items-center gap-3 bg-white border border-ocean-100 rounded-lg p-4">
                  <button
                    onClick={() =>
                      store.setOccasion(!store.hasOccasion, "")
                    }
                    className={`relative w-12 h-6 rounded-full transition-colors ${
                      store.hasOccasion ? "bg-ocean-700" : "bg-ocean-200"
                    }`}
                  >
                    <span
                      className={`absolute top-0.5 left-0.5 w-5 h-5 bg-white rounded-full shadow transition-transform ${
                        store.hasOccasion ? "translate-x-6" : ""
                      }`}
                    />
                  </button>
                  <span className="font-medium text-ocean-900">
                    This is for a special occasion
                  </span>
                </div>

                {store.hasOccasion && (
                  <div className="space-y-2">
                    <Label htmlFor="occasionDetails">
                      Tell us about it!
                    </Label>
                    <Textarea
                      id="occasionDetails"
                      placeholder="e.g., Birthday celebration for Sarah, 10th anniversary, etc."
                      value={store.occasionDetails}
                      onChange={(e) =>
                        store.setOccasion(true, e.target.value)
                      }
                      rows={3}
                      maxLength={500}
                    />
                    {errors.occasion && (
                      <p className="text-sm text-red-500">
                        {errors.occasion}
                      </p>
                    )}
                    <p className="text-xs text-ocean-400 text-right">
                      {store.occasionDetails.length}/500
                    </p>
                  </div>
                )}
              </div>
            )}

            {/* Step 4: Review */}
            {store.currentStep === 4 && (
              <div className="space-y-6">
                <h2 className="text-xl font-semibold text-ocean-900 mb-1">
                  Review & Submit
                </h2>
                <p className="text-ocean-400 text-sm mb-4">
                  Please review your request before submitting. Our team will
                  send you a personalized quote within 24–48 hours.
                </p>

                <div className="space-y-4">
                  {/* Contact */}
                  <div className="bg-ocean-50 rounded-lg p-4">
                    <h3 className="text-sm font-semibold text-ocean-700 mb-2 flex items-center gap-2">
                      <User className="h-4 w-4" /> Contact Information
                    </h3>
                    <div className="grid grid-cols-2 gap-2 text-sm">
                      <div>
                        <span className="text-ocean-400">Name</span>
                        <p className="font-medium text-ocean-900">
                          {store.firstName} {store.lastName}
                        </p>
                      </div>
                      <div>
                        <span className="text-ocean-400">Email</span>
                        <p className="font-medium text-ocean-900">
                          {store.email}
                        </p>
                      </div>
                      <div className="col-span-2">
                        <span className="text-ocean-400">Phone</span>
                        <p className="font-medium text-ocean-900">
                          {store.phone}
                        </p>
                      </div>
                    </div>
                  </div>

                  {/* Guests */}
                  <div className="bg-ocean-50 rounded-lg p-4">
                    <h3 className="text-sm font-semibold text-ocean-700 mb-2 flex items-center gap-2">
                      <Users className="h-4 w-4" /> Guests
                    </h3>
                    <div className="flex gap-4 text-sm">
                      <span>
                        <strong>{store.adultCount}</strong> Adult
                        {store.adultCount !== 1 ? "s" : ""}
                      </span>
                      <span>
                        <strong>{store.childCount}</strong> Child
                        {store.childCount !== 1 ? "ren" : ""}
                      </span>
                      {store.infantCount > 0 && (
                        <span>
                          <strong>{store.infantCount}</strong> Infant
                          {store.infantCount !== 1 ? "s" : ""} (free)
                        </span>
                      )}
                    </div>
                  </div>

                  {/* Dates */}
                  <div className="bg-ocean-50 rounded-lg p-4">
                    <h3 className="text-sm font-semibold text-ocean-700 mb-2 flex items-center gap-2">
                      <Calendar className="h-4 w-4" /> Preferred Dates
                    </h3>
                    <div className="flex flex-wrap gap-2">
                      {store.preferredDates.map((d) => {
                        const dateObj = new Date(d.date + "T12:00:00");
                        const formatted = dateObj.toLocaleDateString("en-US", {
                          weekday: "short",
                          month: "short",
                          day: "numeric",
                        });
                        return (
                          <span
                            key={d.date}
                            className="inline-flex items-center gap-1.5 bg-white border border-ocean-200 rounded-full px-3 py-1 text-sm"
                          >
                            {d.time_preference === "morning" ? (
                              <Sun className="h-3.5 w-3.5 text-amber-500" />
                            ) : (
                              <CloudSun className="h-3.5 w-3.5 text-orange-500" />
                            )}
                            {formatted}
                          </span>
                        );
                      })}
                    </div>
                  </div>

                  {/* Occasion */}
                  {store.hasOccasion && (
                    <div className="bg-ocean-50 rounded-lg p-4">
                      <h3 className="text-sm font-semibold text-ocean-700 mb-2 flex items-center gap-2">
                        <Heart className="h-4 w-4" /> Special Occasion
                      </h3>
                      <p className="text-sm text-ocean-900">
                        {store.occasionDetails}
                      </p>
                    </div>
                  )}
                </div>

                <div className="bg-amber-50 border border-amber-200 rounded-lg p-4 text-sm text-amber-700">
                  <strong>Pricing:</strong> A flat rate will be determined by
                  our team and sent to you as a personalized quote. You&apos;ll
                  be able to pay securely online once you approve the quote.
                </div>

                {store.error && (
                  <div className="bg-red-50 border border-red-200 rounded-lg p-3 text-sm text-red-600">
                    {store.error}
                  </div>
                )}
              </div>
            )}

            {/* Navigation Buttons */}
            <div className="flex items-center justify-between mt-8 pt-6 border-t border-ocean-100">
              {store.currentStep > 0 ? (
                <Button variant="outline" onClick={prevStep}>
                  <ChevronLeft className="h-4 w-4 mr-1" />
                  Back
                </Button>
              ) : (
                <div />
              )}

              {store.currentStep < 4 ? (
                <Button variant="cta" onClick={nextStep}>
                  Continue
                  <ChevronRight className="h-4 w-4 ml-1" />
                </Button>
              ) : (
                <Button
                  variant="cta"
                  onClick={handleSubmit}
                  disabled={store.isSubmitting}
                >
                  {store.isSubmitting ? (
                    <span className="flex items-center gap-2">
                      <svg
                        className="animate-spin h-4 w-4"
                        viewBox="0 0 24 24"
                      >
                        <circle
                          className="opacity-25"
                          cx="12"
                          cy="12"
                          r="10"
                          stroke="currentColor"
                          strokeWidth="4"
                          fill="none"
                        />
                        <path
                          className="opacity-75"
                          fill="currentColor"
                          d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"
                        />
                      </svg>
                      Submitting...
                    </span>
                  ) : (
                    <>
                      <Sparkles className="h-4 w-4 mr-1" />
                      Submit Request
                    </>
                  )}
                </Button>
              )}
            </div>
          </CardContent>
        </Card>
      </div>
    </div>
  );
}

export default function PrivateTourPage() {
  return <PrivateTourForm />;
}
