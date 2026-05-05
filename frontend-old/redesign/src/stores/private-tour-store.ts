import { create } from "zustand";
import type { AvailableAddon } from "@/types/booking";

interface PreferredDateEntry {
  id: string; // local uuid-ish
  date: string;
  time_preference: "morning" | "afternoon";
}

interface PrivateTourGuest {
  id: string;
  first_name: string;
  last_name: string;
  email: string;
  phone: string;
}

export interface PrivateTourState {
  currentStep: number;
  totalSteps: number;

  // Step 0: Party size
  adultCount: number;
  childCount: number;
  infantCount: number;

  // Step 1: Preferred dates
  preferredDates: PreferredDateEntry[];

  // Step 2: Occasion
  hasOccasion: boolean;
  occasionDetails: string;

  // Step 3: Add-ons
  addons: AvailableAddon[];
  selectedAddonIds: string[];

  // Step 4: Contact details
  contactFirstName: string;
  contactLastName: string;
  contactEmail: string;
  confirmEmail: string;
  contactPhone: string;
  guests: PrivateTourGuest[];

  // Submission
  submitting: boolean;
  submitted: boolean;
  submissionError: string;
  bookingRef: string;

  // Actions
  nextStep: () => void;
  prevStep: () => void;

  setAdultCount: (n: number) => void;
  setChildCount: (n: number) => void;
  setInfantCount: (n: number) => void;

  addPreferredDate: () => void;
  removePreferredDate: (id: string) => void;
  updatePreferredDate: (id: string, field: keyof PreferredDateEntry, value: string) => void;

  setHasOccasion: (v: boolean) => void;
  setOccasionDetails: (v: string) => void;

  setAddons: (addons: AvailableAddon[]) => void;
  toggleAddon: (id: string) => void;

  setContactFirstName: (v: string) => void;
  setContactLastName: (v: string) => void;
  setContactEmail: (v: string) => void;
  setConfirmEmail: (v: string) => void;
  setContactPhone: (v: string) => void;
  updateGuest: (id: string, field: keyof PrivateTourGuest, value: string) => void;

  setSubmitting: (v: boolean) => void;
  setSubmitted: (v: boolean) => void;
  setSubmissionError: (v: string) => void;
  setBookingRef: (v: string) => void;

  totalPartySize: () => number;
  reset: () => void;
}

let _idCounter = 0;
const localId = () => `local_${Date.now()}_${++_idCounter}`;

const STEPS = 5;

const emptyGuest = (): PrivateTourGuest => ({
  id: localId(),
  first_name: "",
  last_name: "",
  email: "",
  phone: "",
});

export const usePrivateTourStore = create<PrivateTourState>((set, get) => ({
  currentStep: 0,
  totalSteps: STEPS,

  adultCount: 1,
  childCount: 0,
  infantCount: 0,

  preferredDates: [{ id: localId(), date: "", time_preference: "morning" }],

  hasOccasion: false,
  occasionDetails: "",

  addons: [],
  selectedAddonIds: [],

  contactFirstName: "",
  contactLastName: "",
  contactEmail: "",
  confirmEmail: "",
  contactPhone: "",
  guests: [],

  submitting: false,
  submitted: false,
  submissionError: "",
  bookingRef: "",

  nextStep: () => set((s) => ({ currentStep: Math.min(s.totalSteps - 1, s.currentStep + 1) })),
  prevStep: () => set((s) => ({ currentStep: Math.max(0, s.currentStep - 1) })),

  setAdultCount: (n) => set({ adultCount: Math.max(1, n) }),
  setChildCount: (n) => set({ childCount: Math.max(0, n) }),
  setInfantCount: (n) => set({ infantCount: Math.max(0, n) }),

  addPreferredDate: () =>
    set((s) => ({
      preferredDates: [
        ...s.preferredDates,
        { id: localId(), date: "", time_preference: "morning" },
      ],
    })),

  removePreferredDate: (id) =>
    set((s) => ({
      preferredDates: s.preferredDates.filter((d) => d.id !== id),
    })),

  updatePreferredDate: (id, field, value) =>
    set((s) => ({
      preferredDates: s.preferredDates.map((d) =>
        d.id === id ? { ...d, [field]: value } : d
      ),
    })),

  setHasOccasion: (v) => set({ hasOccasion: v }),
  setOccasionDetails: (v) => set({ occasionDetails: v }),

  setAddons: (addons) => set({ addons }),
  toggleAddon: (id) =>
    set((s) => ({
      selectedAddonIds: s.selectedAddonIds.includes(id)
        ? s.selectedAddonIds.filter((x) => x !== id)
        : [...s.selectedAddonIds, id],
    })),

  setContactFirstName: (v) => set({ contactFirstName: v }),
  setContactLastName: (v) => set({ contactLastName: v }),
  setContactEmail: (v) => set({ contactEmail: v }),
  setConfirmEmail: (v) => set({ confirmEmail: v }),
  setContactPhone: (v) => set({ contactPhone: v }),
  updateGuest: (id, field, value) =>
    set((s) => ({
      guests: s.guests.map((g) =>
        g.id === id ? { ...g, [field]: value } : g
      ),
    })),

  setSubmitting: (v) => set({ submitting: v }),
  setSubmitted: (v) => set({ submitted: v }),
  setSubmissionError: (v) => set({ submissionError: v }),
  setBookingRef: (v) => set({ bookingRef: v }),

  totalPartySize: () => {
    const { adultCount, childCount, infantCount } = get();
    return adultCount + childCount + infantCount;
  },

  reset: () =>
    set({
      currentStep: 0,
      adultCount: 1,
      childCount: 0,
      infantCount: 0,
      preferredDates: [{ id: localId(), date: "", time_preference: "morning" }],
      hasOccasion: false,
      occasionDetails: "",
      selectedAddonIds: [],
      contactFirstName: "",
      contactLastName: "",
      contactEmail: "",
      confirmEmail: "",
      contactPhone: "",
      guests: [],
      submitting: false,
      submitted: false,
      submissionError: "",
      bookingRef: "",
    }),
}));
