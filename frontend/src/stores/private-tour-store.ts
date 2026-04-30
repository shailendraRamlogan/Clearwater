import { create } from "zustand";

interface PrivateTourStore {
  // Contact Info
  firstName: string;
  lastName: string;
  email: string;
  phone: string;

  // Guest Counts
  adultCount: number;
  childCount: number;
  infantCount: number;

  // Preferred Dates
  preferredDates: { date: string; time_preference: "morning" | "afternoon" }[];

  // Occasion
  hasOccasion: boolean;
  occasionDetails: string;

  // UI State
  currentStep: number;
  isSubmitting: boolean;
  submittedRef: string | null;
  error: string | null;

  // Actions
  setContact: (
    firstName: string,
    lastName: string,
    email: string,
    phone: string
  ) => void;
  setGuestCounts: (adults: number, children: number, infants: number) => void;
  addPreferredDate: (
    date: string,
    preference: "morning" | "afternoon"
  ) => boolean;
  removePreferredDate: (date: string) => void;
  updateTimePreference: (
    date: string,
    preference: "morning" | "afternoon"
  ) => void;
  setOccasion: (hasOccasion: boolean, details: string) => void;
  setStep: (step: number) => void;
  setSubmitting: (v: boolean) => void;
  setSubmittedRef: (ref: string | null) => void;
  setError: (err: string | null) => void;
  reset: () => void;
}

const initialState = {
  firstName: "",
  lastName: "",
  email: "",
  phone: "",
  adultCount: 0,
  childCount: 0,
  infantCount: 0,
  preferredDates: [] as { date: string; time_preference: "morning" | "afternoon" }[],
  hasOccasion: false,
  occasionDetails: "",
  currentStep: 0,
  isSubmitting: false,
  submittedRef: null,
  error: null,
};

export const usePrivateTourStore = create<PrivateTourStore>((set, get) => ({
  ...initialState,

  setContact: (firstName, lastName, email, phone) =>
    set({ firstName, lastName, email, phone }),

  setGuestCounts: (adults, children, infants) =>
    set({ adultCount: adults, childCount: children, infantCount: infants }),

  addPreferredDate: (date, preference) => {
    const { preferredDates } = get();
    if (preferredDates.length >= 5) return false;
    if (preferredDates.some((d) => d.date === date)) return false;
    set({
      preferredDates: [
        ...preferredDates,
        { date, time_preference: preference },
      ],
    });
    return true;
  },

  removePreferredDate: (date) =>
    set((state) => ({
      preferredDates: state.preferredDates.filter((d) => d.date !== date),
    })),

  updateTimePreference: (date, preference) =>
    set((state) => ({
      preferredDates: state.preferredDates.map((d) =>
        d.date === date ? { ...d, time_preference: preference } : d
      ),
    })),

  setOccasion: (hasOccasion, details) =>
    set({ hasOccasion, occasionDetails: details }),

  setStep: (step) => set({ currentStep: step, error: null }),

  setSubmitting: (v) => set({ isSubmitting: v }),

  setSubmittedRef: (ref) => set({ submittedRef: ref }),

  setError: (err) => set({ error: err }),

  reset: () => set(initialState),
}));
