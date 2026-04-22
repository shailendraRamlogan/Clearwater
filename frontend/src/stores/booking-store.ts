import { create } from "zustand";
import type {
  TimeSlot,
  BookingGuest,
  BookingItem,
} from "@/types/booking";

interface BookingState {
  // Step 1: Date
  selectedDate: Date | undefined;
  setSelectedDate: (date: Date | undefined) => void;

  // Step 2: Time Slot
  selectedSlot: TimeSlot | undefined;
  setSelectedSlot: (slot: TimeSlot | undefined) => void;
  availableSlots: TimeSlot[];
  setAvailableSlots: (slots: TimeSlot[]) => void;

  // Step 3: Tickets
  adultCount: number;
  setAdultCount: (count: number) => void;
  childCount: number;
  setChildCount: (count: number) => void;
  packageUpgrade: boolean;
  setPackageUpgrade: (upgrade: boolean) => void;
  specialOccasion: boolean;
  setSpecialOccasion: (occasion: boolean) => void;
  specialComment: string;
  setSpecialComment: (comment: string) => void;

  // Step 4: Guests (array)
  guests: BookingGuest[];
  setGuestField: (index: number, field: keyof BookingGuest, value: string) => void;
  addGuest: () => void;
  removeGuest: (index: number) => void;

  // Navigation
  currentStep: number;
  setCurrentStep: (step: number) => void;
  nextStep: () => void;
  prevStep: () => void;

  // Computed
  getTotal: () => number;
  getItems: () => BookingItem[];
  totalGuests: () => number;

  // Reset
  reset: () => void;
}

const ADULT_PRICE = 200;
const CHILD_PRICE = 150;
const UPGRADE_PRICE = 75;

const emptyGuest = (): BookingGuest => ({
  first_name: "",
  last_name: "",
  email: "",
  phone: "",
});

const initialState = {
  selectedDate: undefined,
  selectedSlot: undefined,
  availableSlots: [],
  adultCount: 0,
  childCount: 0,
  packageUpgrade: false,
  specialOccasion: false,
  specialComment: "",
  guests: [emptyGuest()],
  currentStep: 1,
};

export const useBookingStore = create<BookingState>((set, get) => ({
  ...initialState,

  setSelectedDate: (date) => set({ selectedDate: date, selectedSlot: undefined }),
  setSelectedSlot: (slot) => set({ selectedSlot: slot }),
  setAvailableSlots: (slots) => set({ availableSlots: slots }),
  setAdultCount: (count) => {
    const newCount = Math.max(0, count);
    set((state) => {
      const total = newCount + state.childCount;
      // Ensure guest count matches ticket count
      let guests = [...state.guests];
      while (guests.length < total && total > 0) guests.push(emptyGuest());
      if (total === 0) guests = [emptyGuest()];
      else guests = guests.slice(0, total);
      return { adultCount: newCount, guests };
    });
  },
  setChildCount: (count) => {
    const newCount = Math.max(0, count);
    set((state) => {
      const total = state.adultCount + newCount;
      let guests = [...state.guests];
      while (guests.length < total && total > 0) guests.push(emptyGuest());
      if (total === 0) guests = [emptyGuest()];
      else guests = guests.slice(0, total);
      return { childCount: newCount, guests };
    });
  },
  setPackageUpgrade: (upgrade) => set({ packageUpgrade: upgrade }),
  setSpecialOccasion: (occasion) => set({ specialOccasion: occasion }),
  setSpecialComment: (comment) => set({ specialComment: comment }),
  setGuestField: (index, field, value) =>
    set((state) => {
      const guests = [...state.guests];
      guests[index] = { ...guests[index], [field]: value };
      return { guests };
    }),
  addGuest: () => set((state) => ({ guests: [...state.guests, emptyGuest()] })),
  removeGuest: (index) => set((state) => ({
    guests: state.guests.filter((_, i) => i !== index),
  })),

  setCurrentStep: (step) => set({ currentStep: step }),
  nextStep: () => set((state) => ({ currentStep: Math.min(5, state.currentStep + 1) })),
  prevStep: () => set((state) => ({ currentStep: Math.max(1, state.currentStep - 1) })),

  getTotal: () => {
    const state = get();
    const ticketTotal = state.adultCount * ADULT_PRICE + state.childCount * CHILD_PRICE;
    const upgradeTotal = state.packageUpgrade ? (state.adultCount + state.childCount) * UPGRADE_PRICE : 0;
    return ticketTotal + upgradeTotal;
  },

  getItems: () => {
    const state = get();
    const items: BookingItem[] = [];
    if (state.adultCount > 0) {
      items.push({ ticket_type: "adult", quantity: state.adultCount, unit_price: ADULT_PRICE });
    }
    if (state.childCount > 0) {
      items.push({ ticket_type: "child", quantity: state.childCount, unit_price: CHILD_PRICE });
    }
    if (state.packageUpgrade) {
      items.push({
        ticket_type: "adult" as const,
        quantity: state.adultCount + state.childCount,
        unit_price: UPGRADE_PRICE,
      });
    }
    return items;
  },

  totalGuests: () => get().adultCount + get().childCount,

  reset: () => set(initialState),
}));
