import { create } from "zustand";
import type {
  TimeSlot,
  BookingGuest,
  BookingItem,
  TicketType,
} from "@/types/booking";
import { getTicketTypes } from "@/lib/booking-service";

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
  ticketTypes: TicketType[];
  setTicketTypes: (types: TicketType[]) => void;
  ticketCounts: Record<string, number>;
  setTicketCount: (typeId: string, count: number) => void;
  packageUpgrade: boolean;
  setPackageUpgrade: (upgrade: boolean) => void;
  specialOccasion: boolean;
  setSpecialOccasion: (occasion: boolean) => void;
  specialComment: string;
  setSpecialComment: (comment: string) => void;

  // Backward-compatible getters
  adultCount: number;
  setAdultCount: (count: number) => void;
  childCount: number;
  setChildCount: (count: number) => void;

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

  // Pricing fees (from API)
  pricingFees: { name: string; type: string; value: number; flat_value?: number }[];
  setPricingFees: (fees: { name: string; type: string; value: number }[]) => void;

  // Computed
  getTotal: () => number;
  getSubtotal: () => number;
  getFees: () => { name: string; type: string; value: number; flat_value?: number; amount: number }[];
  getGrandTotal: () => number;
  getItems: () => BookingItem[];
  totalGuests: () => number;
  missingGuestCount: () => number;
  getTicketPrice: (typeId: string) => number;

  // Init
  init: () => void;

  // Reset
  reset: () => void;
}

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
  pricingFees: [],
  ticketTypes: [] as TicketType[],
  ticketCounts: {} as Record<string, number>,
  packageUpgrade: false,
  specialOccasion: false,
  specialComment: "",
  guests: [emptyGuest()],
  currentStep: 1,
};

// Helper: find ticket type by name (case-insensitive)
function findTicketTypeByName(types: TicketType[], name: string): TicketType | undefined {
  return types.find((t) => t.name.toLowerCase() === name.toLowerCase());
}

export const useBookingStore = create<BookingState>((set, get) => ({
  ...initialState,

  setSelectedDate: (date) => set({ selectedDate: date, selectedSlot: undefined }),
  setSelectedSlot: (slot) => set({ selectedSlot: slot }),
  setAvailableSlots: (slots) => set({ availableSlots: slots }),
  setPricingFees: (fees) => set({ pricingFees: fees }),
  setTicketTypes: (types) => set({ ticketTypes: types }),
  setTicketCount: (typeId, count) =>
    set((state) => ({
      ticketCounts: { ...state.ticketCounts, [typeId]: Math.max(0, count) },
    })),
  setPackageUpgrade: (upgrade) => set({ packageUpgrade: upgrade }),
  setSpecialOccasion: (occasion) => set({ specialOccasion: occasion }),
  setSpecialComment: (comment) => set({ specialComment: comment }),

  // Backward-compatible getters/setters
  get adultCount() {
    return get().ticketCounts[findTicketTypeByName(get().ticketTypes, "Adult")?.id ?? ""] ?? 0;
  },
  setAdultCount: (count) => {
    const adultType = findTicketTypeByName(get().ticketTypes, "Adult");
    if (adultType) {
      get().setTicketCount(adultType.id, count);
    }
  },
  get childCount() {
    return get().ticketCounts[findTicketTypeByName(get().ticketTypes, "Child")?.id ?? ""] ?? 0;
  },
  setChildCount: (count) => {
    const childType = findTicketTypeByName(get().ticketTypes, "Child");
    if (childType) {
      get().setTicketCount(childType.id, count);
    }
  },

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

  init: () => {
    // Fetch ticket types on init
    getTicketTypes()
      .then((types) => {
        if (types.length > 0) {
          set({ ticketTypes: types });
        }
      })
      .catch(() => {});
  },

  getTicketPrice: (typeId) => {
    const type = get().ticketTypes.find((t) => t.id === typeId);
    return type ? type.price_cents / 100 : 0;
  },

  getTotal: () => {
    return get().getSubtotal();
  },

  getSubtotal: () => {
    const state = get();
    let ticketTotal = 0;
    for (const type of state.ticketTypes) {
      const count = state.ticketCounts[type.id] ?? 0;
      ticketTotal += count * (type.price_cents / 100);
    }
    const totalTickets = Object.values(state.ticketCounts).reduce((s, c) => s + c, 0);
    const upgradeTotal = state.packageUpgrade ? totalTickets * UPGRADE_PRICE : 0;
    return ticketTotal + upgradeTotal;
  },

  getFees: () => {
    const subtotal = get().getSubtotal();
    const fees = get().pricingFees;
    if (!fees || fees.length === 0) return [];
    return fees.map((f) => {
      let amount = 0;
      if (f.type === 'flat') {
        amount = f.flat_value ?? f.value;
      } else if (f.type === 'both') {
        amount = Math.round(subtotal * f.value / 100 * 100) / 100 + (f.flat_value ?? 0);
      } else {
        amount = Math.round(subtotal * f.value / 100 * 100) / 100;
      }
      return { name: f.name, type: f.type, value: f.value, flat_value: f.flat_value, amount };
    });
  },

  getGrandTotal: () => {
    const subtotal = get().getSubtotal();
    const fees = get().getFees();
    const feesTotal = fees.reduce((s, f) => s + f.amount, 0);
    return subtotal + feesTotal;
  },

  getItems: () => {
    const state = get();
    const items: BookingItem[] = [];
    for (const type of state.ticketTypes) {
      const count = state.ticketCounts[type.id] ?? 0;
      if (count > 0) {
        items.push({
          ticket_type: type.name.toLowerCase() as "adult" | "child",
          quantity: count,
          unit_price: type.price_cents / 100,
        });
      }
    }
    if (state.packageUpgrade) {
      const totalTickets = Object.values(state.ticketCounts).reduce((s, c) => s + c, 0);
      if (totalTickets > 0) {
        items.push({
          ticket_type: "adult" as const,
          quantity: totalTickets,
          unit_price: UPGRADE_PRICE,
        });
      }
    }
    return items;
  },

  totalGuests: () => {
    return Object.values(get().ticketCounts).reduce((s, c) => s + c, 0);
  },
  missingGuestCount: () => {
    const total = Object.values(get().ticketCounts).reduce((s, c) => s + c, 0);
    return Math.max(0, total - get().guests.length);
  },

  reset: () => set(initialState),
}));
