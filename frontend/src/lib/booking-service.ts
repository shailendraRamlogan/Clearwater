import type { TimeSlot, Booking, DailyReport } from "@/types/booking";
import api from "@/lib/api";

// Static mock time slots matching the Clear Boat schedule
const MOCK_SLOTS: TimeSlot[] = [
  {
    id: "1",
    start_time: "08:30",
    end_time: "11:00",
    boat_id: "1",
    boat_name: "SS Clear Seas",
    remaining_capacity: 4,
    max_capacity: 10,
    is_blocked: false,
  },
  {
    id: "2",
    start_time: "10:45",
    end_time: "13:15",
    boat_id: "1",
    boat_name: "SS Clear Seas",
    remaining_capacity: 7,
    max_capacity: 10,
    is_blocked: false,
  },
  {
    id: "3",
    start_time: "12:15",
    end_time: "14:45",
    boat_id: "2",
    boat_name: "Skys",
    remaining_capacity: 10,
    max_capacity: 10,
    is_blocked: false,
  },
  {
    id: "4",
    start_time: "13:15",
    end_time: "15:45",
    boat_id: "2",
    boat_name: "Skys",
    remaining_capacity: 2,
    max_capacity: 10,
    is_blocked: false,
  },
];

// Static mock bookings for admin dashboard
const MOCK_BOOKINGS: Booking[] = [
  {
    id: "BK-A1B2C3",
    tour_date: new Date().toISOString().split("T")[0],
    time_slot: MOCK_SLOTS[0],
    guest: { first_name: "James", last_name: "Smith", email: "james@example.com", phone: "+1 242 555-0101" },
    items: [
      { ticket_type: "adult", quantity: 2, unit_price: 200 },
      { ticket_type: "child", quantity: 1, unit_price: 150 },
    ],
    package_upgrade: true,
    special_occasion: true,
    special_comment: "Anniversary celebration! 🎉",
    total_price: 725,
    status: "confirmed",
    created_at: new Date(Date.now() - 3600000).toISOString(),
  },
  {
    id: "BK-D4E5F6",
    tour_date: new Date().toISOString().split("T")[0],
    time_slot: MOCK_SLOTS[1],
    guest: { first_name: "Maria", last_name: "Gonzalez", email: "maria@example.com", phone: "+1 242 555-0202" },
    items: [
      { ticket_type: "adult", quantity: 3, unit_price: 200 },
      { ticket_type: "child", quantity: 2, unit_price: 150 },
    ],
    package_upgrade: false,
    special_occasion: false,
    special_comment: "",
    total_price: 900,
    status: "confirmed",
    created_at: new Date(Date.now() - 7200000).toISOString(),
  },
  {
    id: "BK-G7H8I9",
    tour_date: new Date(Date.now() + 86400000).toISOString().split("T")[0],
    time_slot: MOCK_SLOTS[2],
    guest: { first_name: "David", last_name: "Chen", email: "david@example.com", phone: "+1 242 555-0303" },
    items: [
      { ticket_type: "adult", quantity: 1, unit_price: 200 },
    ],
    package_upgrade: true,
    special_occasion: false,
    special_comment: "",
    total_price: 275,
    status: "pending",
    created_at: new Date(Date.now() - 1800000).toISOString(),
  },
  {
    id: "BK-J0K1L2",
    tour_date: new Date(Date.now() + 86400000).toISOString().split("T")[0],
    time_slot: MOCK_SLOTS[0],
    guest: { first_name: "Sarah", last_name: "Johnson", email: "sarah@example.com", phone: "+1 242 555-0404" },
    items: [
      { ticket_type: "adult", quantity: 2, unit_price: 200 },
      { ticket_type: "child", quantity: 3, unit_price: 150 },
    ],
    package_upgrade: true,
    special_occasion: true,
    special_comment: "Daughter's 10th birthday! Can we arrange a small cake?",
    total_price: 1175,
    status: "confirmed",
    created_at: new Date(Date.now() - 600000).toISOString(),
  },
];

const USE_MOCK = !process.env.NEXT_PUBLIC_API_URL;

function delay(ms: number) {
  return new Promise((resolve) => setTimeout(resolve, ms));
}

export async function getAvailability(date: string): Promise<TimeSlot[]> {
  if (USE_MOCK) {
    await delay(400);
    const checkDate = new Date(date);
    // Closed Sundays
    if (checkDate.getDay() === 0) return [];
    // Use static capacities (not random)
    return MOCK_SLOTS;
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
  guests: { first_name: string; last_name: string; email: string; phone: string }[];
}): Promise<Booking> {
  if (USE_MOCK) {
    await delay(1200);
    const slot = MOCK_SLOTS.find((s) => s.id === payload.time_slot_id) || MOCK_SLOTS[0];
    return {
      id: "BK-" + Math.random().toString(36).substring(2, 8).toUpperCase(),
      tour_date: payload.tour_date,
      time_slot: slot,
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
    await delay(300);
    if (date) {
      return MOCK_BOOKINGS.filter((b) => b.tour_date === date);
    }
    return MOCK_BOOKINGS;
  }
  const { data } = await api.get("/bookings", { params: { date } });
  return data.bookings;
}

export async function getDailyReport(date: string): Promise<DailyReport> {
  if (USE_MOCK) {
    await delay(300);
    const dayBookings = MOCK_BOOKINGS.filter((b) => b.tour_date === date);
    return {
      date,
      total_bookings: dayBookings.length,
      total_adults: dayBookings.reduce((sum, b) => sum + b.items.filter((i) => i.ticket_type === "adult").reduce((s, i) => s + i.quantity, 0), 0),
      total_children: dayBookings.reduce((sum, b) => sum + b.items.filter((i) => i.ticket_type === "child").reduce((s, i) => s + i.quantity, 0), 0),
      total_revenue: dayBookings.reduce((sum, b) => sum + b.total_price, 0),
      bookings: dayBookings,
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
