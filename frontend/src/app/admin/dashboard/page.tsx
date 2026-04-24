"use client";

import Link from "next/link";
import { format } from "date-fns";
// motion removed
import {
  BarChart3,
  CalendarDays,
  Users,
  DollarSign,
  ChevronRight,
  Clock,
  Ship,
} from "lucide-react";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { formatCurrency, formatTime } from "@/lib/utils";
import { getDailyReport } from "@/lib/booking-service";
import { useEffect, useState } from "react";
import type { DailyReport } from "@/types/booking";

export default function AdminDashboard() {
  const [report, setReport] = useState<DailyReport | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState("");

  useEffect(() => {
    getDailyReport(format(new Date(), "yyyy-MM-dd"))
      .then(setReport)
      .catch((e) => setError(e.message))
      .finally(() => setLoading(false));
  }, []);

  if (loading) {
    return (
      <div className="section-container py-8">
        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
          {[1, 2, 3, 4].map((i) => (
            <div key={i} className="h-32 bg-ocean-50 rounded-xl animate-pulse" />
          ))}
        </div>
      </div>
    );
  }

  if (error) {
    return (
      <div className="section-container py-8 text-center">
        <p className="text-red-500">Failed to load dashboard: {error}</p>
      </div>
    );
  }

  const stats = [
    {
      label: "Today's Bookings",
      value: report?.total_bookings ?? 0,
      icon: CalendarDays,
      color: "text-ocean-500",
    },
    {
      label: "Total Guests",
      value: (report?.total_adults ?? 0) + (report?.total_children ?? 0),
      icon: Users,
      color: "text-ocean-500",
    },
    {
      label: "Revenue (Today)",
      value: formatCurrency(report?.total_revenue ?? 0),
      icon: DollarSign,
      color: "text-green-500",
    },
    {
      label: "Adults / Children",
      value: `${report?.total_adults ?? 0} / ${report?.total_children ?? 0}`,
      icon: BarChart3,
      color: "text-sand-500",
    },
  ];

  return (
    <div className="section-container py-8">
      <div className="flex items-center justify-between mb-8">
        <div>
          <h1 className="text-3xl font-bold">Admin Dashboard</h1>
          <p className="text-ocean-500 mt-1">
            {format(new Date(), "EEEE, MMMM d, yyyy")}
          </p>
        </div>
        <Link href="/">
          <Button variant="outline">View Site</Button>
        </Link>
      </div>

      {/* Stats */}
      <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        {stats.map((stat, i) => (
          <div key={i}>
            <Card>
              <CardContent className="pt-6">
                <div className="flex items-center justify-between">
                  <div>
                    <p className="text-sm text-ocean-500">{stat.label}</p>
                    <p className="text-3xl font-bold mt-1">{stat.value}</p>
                  </div>
                  <div className="p-3 bg-ocean-50 rounded-xl">
                    <stat.icon className={`h-6 w-6 ${stat.color}`} />
                  </div>
                </div>
              </CardContent>
            </Card>
          </div>
        ))}
      </div>

      {/* Quick Actions */}
      <div className="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <Card className="hover:shadow-md transition-shadow">
          <CardContent className="pt-6">
            <h3 className="font-semibold mb-2">Upcoming Tours</h3>
            <p className="text-sm text-ocean-500 mb-4">
              {report?.total_bookings ?? 0} tours scheduled today
            </p>
            <Link href="/admin/bookings">
              <Button variant="outline" size="sm">
                View All Bookings
              </Button>
            </Link>
          </CardContent>
        </Card>
        <Card className="hover:shadow-md transition-shadow">
          <CardContent className="pt-6">
            <h3 className="font-semibold mb-2">Schedule Management</h3>
            <p className="text-sm text-ocean-500 mb-4">
              Block dates or time slots
            </p>
            <Link href="/admin/schedule">
              <Button variant="outline" size="sm">
                Manage Schedule
              </Button>
            </Link>
          </CardContent>
        </Card>
        <Card className="hover:shadow-md transition-shadow">
          <CardContent className="pt-6">
            <h3 className="font-semibold mb-2">Daily Reports</h3>
            <p className="text-sm text-ocean-500 mb-4">
              View summaries and export PDFs
            </p>
            <Link href="/admin/reports">
              <Button variant="outline" size="sm">
                View Reports
              </Button>
            </Link>
          </CardContent>
        </Card>
      </div>

      {/* Recent Bookings */}
      <Card>
        <CardHeader>
          <div className="flex items-center justify-between">
            <CardTitle>Recent Bookings</CardTitle>
            <Link href="/admin/bookings">
              <Button variant="ghost" size="sm">
                View All <ChevronRight className="ml-1 h-4 w-4" />
              </Button>
            </Link>
          </div>
        </CardHeader>
        <CardContent>
          {report && report.bookings.length > 0 ? (
            <div className="space-y-4">
              {report.bookings.map((booking) => (
                <div
                  key={booking.id}
                  className="flex items-center justify-between p-4 bg-ocean-50 rounded-xl"
                >
                  <div className="flex-1">
                    <div className="flex items-center gap-3 mb-1">
                      <span className="font-mono text-sm font-bold text-ocean-600">
                        {booking.id}
                      </span>
                      <span
                        className={`text-xs px-2 py-0.5 rounded-full font-medium ${
                          booking.status === "confirmed"
                            ? "bg-green-100 text-green-700"
                            : "bg-yellow-100 text-yellow-700"
                        }`}
                      >
                        {booking.status}
                      </span>
                    </div>
                    <p className="text-sm text-ocean-700">
                      {booking.guest.first_name} {booking.guest.last_name}
                    </p>
                    <div className="flex items-center gap-4 mt-1 text-xs text-ocean-400">
                      <span className="flex items-center gap-1">
                        <Clock className="h-3 w-3" />
                        {formatTime(booking.time_slot.start_time)}
                      </span>
                      <span className="flex items-center gap-1">
                        <Ship className="h-3 w-3" />
                        {booking.time_slot.boat_name}
                      </span>
                      <span className="flex items-center gap-1">
                        <Users className="h-3 w-3" />
                        {booking.items.reduce((s, i) => s + i.quantity, 0)} guests
                      </span>
                    </div>
                  </div>
                  <div className="text-right">
                    <p className="font-bold text-lg">
                      {formatCurrency(booking.total_price)}
                    </p>
                    {booking.special_occasion && (
                      <p className="text-xs text-ocean-400">🎉 Special</p>
                    )}
                  </div>
                </div>
              ))}
            </div>
          ) : (
            <div className="text-center py-8 text-ocean-400">
              <p>No bookings for today.</p>
            </div>
          )}
        </CardContent>
      </Card>
    </div>
  );
}
