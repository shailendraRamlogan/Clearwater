import { create } from "zustand";
import type { TimeSlot, TicketType, Addon, PricingFee } from "@/types/booking";

interface GuestInfo {
  first_name: string;
  last_name: string;
  email: string;
  phone: string;
}

interface BookingState {
  currentStep: number;
  selectedDate: Date | undefined;
  selectedSlot: TimeSlot | undefined;
  ticketTypes: TicketType[];
  ticketCounts: Record<string, number>;
  addons: Addon[];
  selectedAddons: Record<string, number>;
  pricingFees: PricingFee[];
  guests: GuestInfo[];
  availableSlots: TimeSlot[];
  specialComment: string;

  nextStep: () => void;
  prevStep: () => void;
  setSelectedDate: (date: Date | undefined) => void;
  setSelectedSlot: (slot: TimeSlot | undefined) => void;
  setTicketTypes: (types: TicketType[]) => void;
  updateTicketCount: (typeId: string, count: number) => void;
  setAddons: (addons: Addon[]) => void;
  updateAddon: (addonId: string, qty: number) => void;
  setPricingFees: (fees: PricingFee[]) => void;
  updateGuest: (index: number, field: keyof GuestInfo, value: string) => void;
  addGuest: () => void;
  removeGuest: (index: number) => void;
  setAvailableSlots: (slots: TimeSlot[]) => void;
  setSpecialComment: (comment: string) => void;

  totalGuests: () => number;
  missingGuestCount: () => number;
  getSubtotal: () => number;       // in cents
  getFees: () => { name: string; amount: number }[];  // in cents
  getGrandTotal: () => number;     // in cents
  getAddonName: (id: string) => string;
  reset: () => void;
}

const emptyGuest = (): GuestInfo => ({
  first_name: "",
  last_name: "",
  email: "",
  phone: "",
});

const defaultGuests: GuestInfo[] = [emptyGuest()];

export const useBookingStore = create<BookingState>((set, get) => ({
  currentStep: 0,
  selectedDate: undefined,
  selectedSlot: undefined,
  ticketTypes: [],
  ticketCounts: {},
  addons: [],
  selectedAddons: {},
  pricingFees: [],
  guests: defaultGuests,
  availableSlots: [],
  specialComment: "",

  nextStep: () => set((s) => ({ currentStep: s.currentStep + 1 })),
  prevStep: () => set((s) => ({ currentStep: Math.max(0, s.currentStep - 1) })),
  setSelectedDate: (date) => set({ selectedDate: date, selectedSlot: undefined }),
  setSelectedSlot: (slot) => set({ selectedSlot: slot }),
  setTicketTypes: (types) => set({ ticketTypes: types }),
  updateTicketCount: (typeId, count) =>
    set((s) => ({ ticketCounts: { ...s.ticketCounts, [typeId]: Math.max(0, count) } })),
  setAddons: (addons) => set({ addons }),
  updateAddon: (addonId, qty) =>
    set((s) => ({ selectedAddons: { ...s.selectedAddons, [addonId]: Math.max(0, qty) } })),
  setPricingFees: (fees) => set({ pricingFees: fees }),
  updateGuest: (index, field, value) =>
    set((s) => {
      const guests = [...s.guests];
      while (guests.length <= index) guests.push(emptyGuest());
      guests[index] = { ...guests[index], [field]: value };
      return { guests };
    }),
  addGuest: () => set((s) => ({ guests: [...s.guests, emptyGuest()] })),
  removeGuest: (index) => set((s) => ({
    guests: s.guests.filter((_, i) => i !== index),
  })),
  setAvailableSlots: (slots) => set({ availableSlots: slots }),
  setSpecialComment: (comment) => set({ specialComment: comment }),

  totalGuests: () => {
    const { ticketCounts } = get();
    return Object.values(ticketCounts).reduce((sum, c) => sum + c, 0);
  },

  missingGuestCount: () => {
    const total = get().totalGuests();
    const filled = get().guests.filter(
      (g) => g.first_name && g.last_name && g.email
    ).length;
    return Math.max(0, total - filled);
  },

  getSubtotal: () => {
    const { ticketTypes, ticketCounts, addons, selectedAddons } = get();
    let total = 0;
    for (const type of ticketTypes) {
      total += (ticketCounts[type.id] ?? 0) * type.price_cents;
    }
    for (const addon of addons) {
      total += (selectedAddons[addon.id] ?? 0) * addon.price_cents;
    }
    return total;
  },

  getFees: () => {
    const { pricingFees } = get();
    if (!pricingFees || pricingFees.length === 0) return [];
    const subtotal = get().getSubtotal();
    return pricingFees.map((fee) => {
      let amount = 0;
      if (fee.type === "both") {
        amount += Math.round(subtotal * (fee.value / 100));
        amount += Math.round((fee.flat_value ?? 0) * 100);
      } else if (fee.type === "percent") {
        amount += Math.round(subtotal * (fee.value / 100));
      } else {
        amount += Math.round((fee.flat_value ?? fee.value) * 100);
      }
      return { name: fee.name, amount };
    });
  },

  getGrandTotal: () => {
    const subtotal = get().getSubtotal();
    const fees = get().getFees();
    const feesTotal = fees.reduce((sum, f) => sum + f.amount, 0);
    return subtotal + feesTotal;
  },

  getAddonName: (id) => {
    const addon = get().addons.find((a) => a.id === id);
    return addon?.title || addon?.name || "Add-on";
  },

  reset: () =>
    set({
      currentStep: 0,
      selectedDate: undefined,
      selectedSlot: undefined,
      ticketCounts: {},
      selectedAddons: {},
      pricingFees: [],
      guests: defaultGuests,
      availableSlots: [],
      specialComment: "",
    }),
}));
