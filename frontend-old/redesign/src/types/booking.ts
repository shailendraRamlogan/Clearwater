export interface TimeSlot {
  id: string;
  start_time: string;
  end_time: string;
  boat_id: string;
  boat_name: string;
  remaining_capacity: number;
  max_capacity: number;
  is_blocked: boolean;
}

export interface TicketType {
  id: string;
  name: string;
  description?: string;
  price_cents: number;
  sort_order: number;
  features: { icon: string; label: string; sort_order: number }[];
}

export interface Addon {
  id: string;
  title?: string;
  name?: string;
  description?: string;
  price_cents: number;
  price_dollars?: number;
  is_active?: boolean;
  sort_order: number;
  max_quantity?: number;
  icon_name?: string;
  image_url?: string;
}

export interface PricingFee {
  name: string;
  type: "flat" | "percent" | "both";
  value: number;
  flat_value?: number;
}

export interface BookingRequest {
  tour_date: string;
  time_slot_id: string;
  adult_count: number;
  child_count: number;
  addons: { addon_id: string; quantity: number }[];
  special_comment?: string;
  guest: {
    first_name: string;
    last_name: string;
    email: string;
    phone: string;
  };
  guests?: {
    first_name?: string;
    last_name?: string;
    email?: string;
    phone?: string;
  }[];
}

export interface Booking {
  id: string;
  booking_reference?: string;
  booking_ref?: string;
  status: string;
  tour_date: string;
  total_price: number;
  fees_cents?: number;
  grand_total?: number;
  client_secret?: string;
  stripe_intent_id?: string;
  is_confirmed?: boolean;
  needs_confirmation?: boolean;
}

// Private Tour Types
export interface PrivateTourPreferredDate {
  id?: string;
  date: string;
  time_preference: "morning" | "afternoon";
  sort_order?: number;
}

export interface PrivateTourGuest {
  id?: string;
  first_name: string;
  last_name: string;
  email: string;
  phone: string;
  is_primary?: boolean;
}

export interface PrivateTourAddon {
  id: string;
  addon_id: string;
  unit_price_cents: number | null;
  addon?: {
    id: string;
    title: string;
    description: string | null;
    icon_name: string | null;
  };
}

export interface AvailableAddon {
  id: string;
  title: string;
  description: string | null;
  icon_name: string | null;
}

export interface PrivateTourRequest {
  id: string;
  booking_ref: string;
  status: "requested" | "confirmed" | "rejected" | "awaiting_payment" | "completed";
  contact_first_name: string;
  contact_last_name: string;
  contact_email: string;
  contact_phone: string;
  adult_count: number;
  child_count: number;
  infant_count: number;
  has_occasion: boolean;
  occasion_details: string | null;
  admin_notes: string | null;
  confirmed_tour_date: string | null;
  confirmed_time_slot_id: string | null;
  total_price_cents: number;
  fees_cents: number;
  preferredDates: PrivateTourPreferredDate[];
  guests: PrivateTourGuest[];
  addons?: PrivateTourAddon[];
  created_at: string;
  updated_at: string;
}
