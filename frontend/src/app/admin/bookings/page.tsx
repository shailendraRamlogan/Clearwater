"use client";

import { useState } from "react";
import { format } from "date-fns";
import { Download } from "lucide-react";
import { Card, CardContent } from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import { Button } from "@/components/ui/button";

export default function AdminBookings() {
  const [dateFilter, setDateFilter] = useState(format(new Date(), "yyyy-MM-dd"));

  return (
    <div className="section-container py-8">
      <div className="flex items-center justify-between mb-8">
        <div>
          <h1 className="text-3xl font-bold">Bookings</h1>
          <p className="text-ocean-500 mt-1">
            Manage and view all tour bookings
          </p>
        </div>
        <Button variant="outline">
          <Download className="mr-2 h-4 w-4" />
          Export CSV
        </Button>
      </div>

      <Card className="mb-6">
        <CardContent className="pt-6">
          <div className="flex flex-col sm:flex-row gap-4">
            <div className="flex-1">
              <Input
                type="date"
                value={dateFilter}
                onChange={(e) => setDateFilter(e.target.value)}
              />
            </div>
            <div className="flex-1">
              <Input
                type="text"
                placeholder="Search by name or booking ID..."
              />
            </div>
          </div>
        </CardContent>
      </Card>

      <Card>
        <CardContent className="pt-6">
          <div className="text-center py-12 text-ocean-400">
            <p>No bookings found for {format(new Date(dateFilter), "MMMM d, yyyy")}.</p>
            <p className="text-sm mt-1">
              Bookings will populate once the backend is connected.
            </p>
          </div>
        </CardContent>
      </Card>
    </div>
  );
}
