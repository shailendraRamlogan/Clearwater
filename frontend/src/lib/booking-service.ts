import type { TimeSlot, Booking, DailyReport } from "@/types/booking";
import api from "@/lib/api";

// Mock data for development
const MOCK_SLOTS: TimeSlot[] = [
  { id: "1", start_time: "08:30", end_time: "11:00", boat_id: "1", boat_name: "SS Clear Seas", remaining_capacity: 8, max_capacity: 10, is_blocked: false },
  { id: "2", start_time: "10:45", end_time: "13:15", boat_id: "1", boat_name: "SS Clear Seas", remaining_capacity: 6, max_capacity: 10, is_blocked: false },
  { id: "3", start_time: "12:15", end_time: "14:45", boat_id: "2", boat_name: "Skys", remaining_capacity: 10, max_capacity: 10, is_blocked: false },
  { id: "4", start_time: "13:15", end_time: "15:45", boat_id: "2", boat_name: "Skys", remaining_capacity: 4, max_capacity: 10, is_blocked: false },
];

const USE_MOCK = !process.env.NEXT_PUBLIC_API_URL;

function delay(ms: number) {
  return new Promise((resolve) => setTimeout(resolve, ms));
}

export async function getAvailability(date: string): Promise<TimeSlot[]> {
  if (USE_MOCK) {
    await delay(600);
    // Simulate some blocked dates
    const checkDate = new Date(date);
    if (checkDate.getDay() === 0) return []; // Closed Sundays
    return MOCK_SLOTS.map((s) => ({
      ...s,
      remaining_capacity: Math.floor(Math.random() * s.max_capacity) + 1,
    }));
  }
  const { data } = await api.get("/availability", { params: { date } });
  return data.slots;
}

export async function createBooking(payload: {
  tour_date: string;
  time_slot_id: string;
  adult_count: number;
  child_count: number;
  package_upgrade: boolean;
  special_occasion: boolean;
  special_comment: string;
  guest: { first_name: string; last_name: string; email: string; phone: string };
}): Promise<Booking> {
  if (USE_MOCK) {
    await delay(1200);
    return {
      id: "BK-" + Math.random().toString(36).substring(2, 8).toUpperCase(),
      tour_date: payload.tour_date,
      time_slot: MOCK_SLOTS[0],
      guest: payload.guest,
      items: [
        { ticket_type: "adult", quantity: payload.adult_count, unit_price: 200 },
        { ticket_type: "child", quantity: payload.child_count, unit_price: 150 },
      ],
      package_upgrade: payload.package_upgrade,
      special_occasion: payload.special_occasion,
      special_comment: payload.special_comment,
      total_price:
        payload.adult_count * 200 +
        payload.child_count * 150 +
        (payload.package_upgrade ? (payload.adult_count + payload.child_count) * 75 : 0),
      status: "confirmed",
      created_at: new Date().toISOString(),
    };
  }
  const { data } = await api.post("/bookings", payload);
  return data.booking;
}

export async function getBookings(date?: string): Promise<Booking[]> {
  if (USE_MOCK) {
    await delay(500);
    return [];
  }
  const { data } = await api.get("/bookings", { params: { date } });
  return data.bookings;
}

export async function getDailyReport(date: string): Promise<DailyReport> {
  if (USE_MOCK) {
    await delay(500);
    return {
      date,
      total_bookings: 0,
      total_adults: 0,
      total_children: 0,
      total_revenue: 0,
      bookings: [],
    };
  }
  const { data } = await api.get("/reports/daily", { params: { date } });
  return data.report;
}

export async function blockSchedule(payload: {
  date: string;
  time_slot_id?: string;
  reason: string;
}): Promise<void> {
  if (USE_MOCK) {
    await delay(400);
    return;
  }
  await api.post("/schedules/block", payload);
}

export async function getSchedulePdfUrl(date: string): Promise<string> {
  if (USE_MOCK) {
    await delay(300);
    return "#";
  }
  const { data } = await api.get("/reports/schedule-pdf", {
    params: { date },
    responseType: "blob",
  });
  return URL.createObjectURL(data);
}
