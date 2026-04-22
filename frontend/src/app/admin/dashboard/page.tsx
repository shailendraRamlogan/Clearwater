"use client";

import Link from "next/link";
import { format } from "date-fns";
import { motion } from "framer-motion";
import {
  BarChart3,
  CalendarDays,
  Users,
  DollarSign,
  ChevronLeft,
  ChevronRight,
} from "lucide-react";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Button } from "@/components/ui/button";

export default function AdminDashboard() {
  // Mock stats for now
  const stats = [
    { label: "Today's Bookings", value: 12, icon: CalendarDays, color: "text-ocean-500" },
    { label: "Total Guests", value: 34, icon: Users, color: "text-ocean-500" },
    { label: "Revenue (Today)", value: "$6,850", icon: DollarSign, color: "text-green-500" },
    { label: "Occupancy Rate", value: "72%", icon: BarChart3, color: "text-sand-500" },
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
          <motion.div
            key={i}
            initial={{ opacity: 0, y: 20 }}
            animate={{ opacity: 1, y: 0 }}
            transition={{ delay: i * 0.1 }}
          >
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
          </motion.div>
        ))}
      </div>

      {/* Quick Actions */}
      <div className="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <Card className="hover:shadow-md transition-shadow">
          <CardContent className="pt-6">
            <h3 className="font-semibold mb-2">Upcoming Tours</h3>
            <p className="text-sm text-ocean-500 mb-4">
              {format(new Date(), "MMM d")} — 4 tours scheduled
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

      {/* Recent Bookings Placeholder */}
      <Card>
        <CardHeader>
          <div className="flex items-center justify-between">
            <CardTitle>Recent Bookings</CardTitle>
            <div className="flex gap-2">
              <Button variant="ghost" size="icon">
                <ChevronLeft className="h-4 w-4" />
              </Button>
              <Button variant="ghost" size="icon">
                <ChevronRight className="h-4 w-4" />
              </Button>
            </div>
          </div>
        </CardHeader>
        <CardContent>
          <div className="text-center py-8 text-ocean-400">
            <p>Bookings will appear here once the backend is connected.</p>
            <p className="text-sm mt-1">
              Connect to the API to see live data.
            </p>
          </div>
        </CardContent>
      </Card>
    </div>
  );
}
