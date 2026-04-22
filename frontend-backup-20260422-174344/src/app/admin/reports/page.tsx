"use client";

import { useState } from "react";
import { format } from "date-fns";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import { Button } from "@/components/ui/button";
import { Download, FileText } from "lucide-react";

export default function AdminReports() {
  const [date, setDate] = useState(format(new Date(), "yyyy-MM-dd"));

  return (
    <div className="section-container py-8">
      <div className="mb-8">
        <h1 className="text-3xl font-bold">Daily Reports</h1>
        <p className="text-ocean-500 mt-1">
          View daily summaries and export schedule PDFs
        </p>
      </div>

      <Card className="mb-6">
        <CardContent className="pt-6">
          <div className="flex flex-col sm:flex-row gap-4 items-end">
            <div className="flex-1">
              <label className="text-sm font-medium text-ocean-700 mb-1 block">
                Report Date
              </label>
              <Input
                type="date"
                value={date}
                onChange={(e) => setDate(e.target.value)}
              />
            </div>
            <Button variant="outline">
              <FileText className="mr-2 h-4 w-4" />
              Generate Report
            </Button>
            <Button>
              <Download className="mr-2 h-4 w-4" />
              Export PDF
            </Button>
          </div>
        </CardContent>
      </Card>

      <div className="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
        <Card>
          <CardHeader>
            <CardTitle className="text-lg">Summary</CardTitle>
          </CardHeader>
          <CardContent>
            <div className="space-y-3">
              <div className="flex justify-between">
                <span className="text-ocean-500">Total Bookings</span>
                <span className="font-semibold">—</span>
              </div>
              <div className="flex justify-between">
                <span className="text-ocean-500">Total Adults</span>
                <span className="font-semibold">—</span>
              </div>
              <div className="flex justify-between">
                <span className="text-ocean-500">Total Children</span>
                <span className="font-semibold">—</span>
              </div>
              <div className="flex justify-between border-t pt-3">
                <span className="text-ocean-500">Total Revenue</span>
                <span className="font-bold text-lg">—</span>
              </div>
            </div>
          </CardContent>
        </Card>
        <Card>
          <CardHeader>
            <CardTitle className="text-lg">Schedule Overview</CardTitle>
          </CardHeader>
          <CardContent>
            <div className="text-center py-6 text-ocean-400">
              <p className="text-sm">
                Schedule data will appear here once the backend is connected.
              </p>
            </div>
          </CardContent>
        </Card>
      </div>
    </div>
  );
}
