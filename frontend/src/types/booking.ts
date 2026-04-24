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

export interface TourDate {
  date: string;
  slots: TimeSlot[];
}

export interface BookingGuest {
  first_name: string;
  last_name: string;
  email: string;
  phone: string;
}

export interface BookingItem {
  ticket_type: "adult" | "child";
  quantity: number;
  unit_price: number;
}

export interface Booking {
  id: string;
  tour_date: string;
  time_slot: TimeSlot;
  guest: BookingGuest;
  items: BookingItem[];
  package_upgrade: boolean;
  special_occasion: boolean;
  special_comment: string;
  total_price: number;
  fees_cents: number;
  fees_breakdown: { name: string; type: string; amount_cents: number; display: string }[];
  status: "pending" | "confirmed" | "cancelled";
  is_confirmed: boolean;
  needs_confirmation: boolean;
  created_at: string;
}

export interface DailyReport {
  date: string;
  total_bookings: number;
  total_adults: number;
  total_children: number;
  total_revenue: number;
  bookings: Booking[];
}

export interface ScheduleBlock {
  id: string;
  date: string;
  time_slot_id: string;
  reason: string;
}
