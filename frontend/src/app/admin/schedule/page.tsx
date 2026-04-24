"use client";

import { useState, useEffect } from "react";
import { format, startOfMonth, endOfMonth, eachDayOfInterval, isBefore, startOfDay, addDays } from "date-fns";
import { Lock } from "lucide-react";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { getBlockedDates, blockSchedule, unblockSchedule } from "@/lib/booking-service";
import { toast } from "sonner";

export default function AdminSchedule() {
  const [currentMonth, setCurrentMonth] = useState(new Date());
  const [blockedDates, setBlockedDates] = useState<Set<string>>(new Set());
  const [selectedDate, setSelectedDate] = useState<Date | null>(null);
  const [saving, setSaving] = useState(false);

  const monthStr = format(currentMonth, "yyyy-MM");

  useEffect(() => {
    getBlockedDates(monthStr)
      .then((blocks) => {
        setBlockedDates(new Set(blocks.map((b) => b.date)));
      })
      .catch(() => {});
  }, [monthStr]);

  const toggleBlock = async (date: Date) => {
    const dateStr = format(date, "yyyy-MM-dd");
    const blocked = blockedDates.has(dateStr);
    setSaving(true);
    try {
      if (blocked) {
        await unblockSchedule({ date: dateStr });
        setBlockedDates((prev) => {
          const next = new Set(prev);
          next.delete(dateStr);
          return next;
        });
        toast.success(`${format(date, "MMM d")} unblocked`);
      } else {
        await blockSchedule({ date: dateStr, reason: "Blocked by admin" });
        setBlockedDates((prev) => {
          const next = new Set(prev);
          next.add(dateStr);
          return next;
        });
        toast.success(`${format(date, "MMM d")} blocked`);
      }
    } catch (e: unknown) {
      toast.error((e as Error).message || "Failed to update schedule");
    } finally {
      setSaving(false);
    }
  };

  const days = eachDayOfInterval({
    start: startOfMonth(currentMonth),
    end: endOfMonth(currentMonth),
  });

  const startDay = startOfMonth(currentMonth).getDay();
  const blanks = Array.from({ length: startDay }, (_, i) => i);

  return (
    <div className="section-container py-8">
      <div className="mb-8">
        <h1 className="text-3xl font-bold">Schedule Management</h1>
        <p className="text-ocean-500 mt-1">
          Block or unblock dates and time slots
        </p>
      </div>

      <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {/* Calendar */}
        <Card className="lg:col-span-2">
          <CardHeader>
            <div className="flex items-center justify-between">
              <CardTitle>
                {format(currentMonth, "MMMM yyyy")}
              </CardTitle>
              <div className="flex gap-2">
                <Button
                  variant="outline"
                  size="sm"
                  onClick={() =>
                    setCurrentMonth(
                      new Date(currentMonth.getFullYear(), currentMonth.getMonth() - 1)
                    )
                  }
                >
                  ←
                </Button>
                <Button
                  variant="outline"
                  size="sm"
                  onClick={() => setCurrentMonth(new Date())}
                >
                  Today
                </Button>
                <Button
                  variant="outline"
                  size="sm"
                  onClick={() =>
                    setCurrentMonth(
                      new Date(currentMonth.getFullYear(), currentMonth.getMonth() + 1)
                    )
                  }
                >
                  →
                </Button>
              </div>
            </div>
          </CardHeader>
          <CardContent>
            <div className="grid grid-cols-7 gap-1">
              {["Sun", "Mon", "Tue", "Wed", "Thu", "Fri", "Sat"].map((d) => (
                <div key={d} className="text-center text-xs font-medium text-ocean-400 py-2">
                  {d}
                </div>
              ))}
              {blanks.map((i) => (
                <div key={`blank-${i}`} />
              ))}
              {days.map((day) => {
                const dateStr = format(day, "yyyy-MM-dd");
                const blocked = blockedDates.has(dateStr);
                const past = isBefore(day, startOfDay(addDays(new Date(), 1)));
                const selected = selectedDate && format(selectedDate, "yyyy-MM-dd") === dateStr;

                return (
                  <button
                    key={dateStr}
                    disabled={past || saving}
                    onClick={() => {
                      setSelectedDate(day);
                      if (!past) toggleBlock(day);
                    }}
                    className={`relative p-2 rounded-lg text-sm transition-all ${
                      past
                        ? "text-ocean-200 cursor-not-allowed"
                        : blocked
                        ? "bg-coral-500/10 text-coral-600 font-medium hover:bg-coral-500/20"
                        : selected
                        ? "bg-ocean-700 text-white"
                        : "hover:bg-ocean-50 text-ocean-700"
                    }`}
                  >
                    {format(day, "d")}
                    {blocked && (
                      <Lock className="absolute top-0.5 right-0.5 h-3 w-3 text-coral-400" />
                    )}
                  </button>
                );
              })}
            </div>
          </CardContent>
        </Card>

        {/* Selected Date Details */}
        <Card>
          <CardHeader>
            <CardTitle className="text-lg">Time Slots</CardTitle>
          </CardHeader>
          <CardContent>
            {selectedDate ? (
              <div className="space-y-3">
                <p className="text-sm text-ocean-500 mb-4">
                  {format(selectedDate, "EEEE, MMMM d")}
                </p>
                {["8:30 AM", "10:45 AM", "12:15 PM", "1:15 PM"].map((time) => (
                  <div
                    key={time}
                    className="flex items-center justify-between p-3 bg-ocean-50 rounded-lg"
                  >
                    <span className="font-medium text-sm">{time}</span>
                    <Button variant="ghost" size="sm" disabled>
                      <Lock className="h-3 w-3 mr-1" />
                      Block
                    </Button>
                  </div>
                ))}
              </div>
            ) : (
              <p className="text-sm text-ocean-400">
                Select a date to manage its time slots.
              </p>
            )}
          </CardContent>
        </Card>
      </div>
    </div>
  );
}
