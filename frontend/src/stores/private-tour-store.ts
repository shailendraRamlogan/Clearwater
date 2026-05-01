import { create } from "zustand";
import type { PrivateTourPreferredDate } from "@/types/booking";

interface PrivateTourState {
  // Step 1: Party size
  adultCount: number;
  setAdultCount: (count: number) => void;
  childCount: number;
  setChildCount: (count: number) => void;
  infantCount: number;
  setInfantCount: (count: number) => void;

  // Step 2: Preferred dates (up to 5)
  preferredDates: PrivateTourPreferredDate[];
  addPreferredDate: (date: string, time: "morning" | "afternoon") => void;
  removePreferredDate: (index: number) => void;
  clearPreferredDates: () => void;

  // Step 3: Occasion
  hasOccasion: boolean;
  setHasOccasion: (val: boolean) => void;
  occasionDetails: string;
  setOccasionDetails: (text: string) => void;

  // Addons
  selectedAddonIds: string[];
  toggleAddon: (id: string) => void;

  // Step 4: Contact info
  contactFirstName: string;
  setContactFirstName: (val: string) => void;
  contactLastName: string;
  setContactLastName: (val: string) => void;
  contactEmail: string;
  setContactEmail: (val: string) => void;
  contactPhone: string;
  setContactPhone: (val: string) => void;

  // Navigation
  currentStep: number;
  setCurrentStep: (step: number) => void;
  nextStep: () => void;
  prevStep: () => void;

  // Submission state
  isSubmitting: boolean;
  setIsSubmitting: (val: boolean) => void;
  submittedRef: string | null;
  setSubmittedRef: (ref: string | null) => void;

  // Computed
  totalPeople: () => number;
  canSubmit: () => boolean;

  // Reset
  reset: () => void;
}

const initialState = {
  adultCount: 1,
  childCount: 0,
  infantCount: 0,
  preferredDates: [] as PrivateTourPreferredDate[],
  hasOccasion: false,
  occasionDetails: "",
  selectedAddonIds: [] as string[],
  contactFirstName: "",
  contactLastName: "",
  contactEmail: "",
  contactPhone: "",
  currentStep: 1,
  isSubmitting: false,
  submittedRef: null,
};

export const usePrivateTourStore = create<PrivateTourState>((set, get) => ({
  ...initialState,

  setAdultCount: (count) => {
    const people = count + get().childCount;
    if (people > 10) return;
    set({ adultCount: Math.max(0, count) });
  },
  setChildCount: (count) => {
    const people = get().adultCount + count;
    if (people > 10) return;
    set({ childCount: Math.max(0, count) });
  },
  setInfantCount: (count) => set({ infantCount: Math.max(0, count) }),

  addPreferredDate: (date, time) => {
    const dates = get().preferredDates;
    if (dates.length >= 5) return;
    // Prevent exact duplicates
    if (dates.some((d) => d.date === date && d.time_preference === time)) return;
    set({
      preferredDates: [
        ...dates,
        { date, time_preference: time, sort_order: dates.length },
      ],
    });
  },
  removePreferredDate: (index) =>
    set((state) => ({
      preferredDates: state.preferredDates
        .filter((_, i) => i !== index)
        .map((d, i) => ({ ...d, sort_order: i })),
    })),
  clearPreferredDates: () => set({ preferredDates: [] }),

  setHasOccasion: (val) => set({ hasOccasion: val, occasionDetails: val ? get().occasionDetails : "" }),
  setOccasionDetails: (text) => set({ occasionDetails: text }),

  toggleAddon: (id) =>
    set((state) => ({
      selectedAddonIds: state.selectedAddonIds.includes(id)
        ? state.selectedAddonIds.filter((a) => a !== id)
        : [...state.selectedAddonIds, id],
    })),

  setContactFirstName: (val) => set({ contactFirstName: val }),
  setContactLastName: (val) => set({ contactLastName: val }),
  setContactEmail: (val) => set({ contactEmail: val }),
  setContactPhone: (val) => set({ contactPhone: val }),

  setCurrentStep: (step) => set({ currentStep: step }),
  nextStep: () => set((state) => ({ currentStep: Math.min(4, state.currentStep + 1) })),
  prevStep: () => set((state) => ({ currentStep: Math.max(1, state.currentStep - 1) })),

  setIsSubmitting: (val) => set({ isSubmitting: val }),
  setSubmittedRef: (ref) => set({ submittedRef: ref }),

  totalPeople: () => get().adultCount + get().childCount,

  canSubmit: () => {
    const s = get();
    return (
      s.totalPeople() >= 1 &&
      s.totalPeople() <= 10 &&
      s.preferredDates.length >= 1 &&
      s.contactFirstName.trim() !== "" &&
      s.contactLastName.trim() !== "" &&
      s.contactEmail.trim() !== "" &&
      s.contactPhone.trim() !== ""
    );
  },

  reset: () => set(initialState),
}));
