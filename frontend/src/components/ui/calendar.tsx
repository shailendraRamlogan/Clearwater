"use client";

import { format, isBefore, startOfDay, isSameDay } from "date-fns";
import { ChevronLeft, ChevronRight } from "lucide-react";
import { cn } from "@/lib/utils";
import { useState } from "react";

interface ModernCalendarProps {
  selected?: Date;
  onSelect?: (date: Date) => void;
  disabled?: (date: Date) => boolean;
  className?: string;
}

export function ModernCalendar({
  selected,
  onSelect,
  disabled,
  className,
}: ModernCalendarProps) {
  const [currentMonth, setCurrentMonth] = useState(selected || new Date());

  const year = currentMonth.getFullYear();
  const month = currentMonth.getMonth();
  const firstDay = new Date(year, month, 1).getDay();
  const daysInMonth = new Date(year, month + 1, 0).getDate();
  const today = startOfDay(new Date());

  const days: (Date | null)[] = [];
  for (let i = 0; i < firstDay; i++) days.push(null);
  for (let d = 1; d <= daysInMonth; d++) days.push(new Date(year, month, d));

  const prevMonth = () => setCurrentMonth(new Date(year, month - 1, 1));
  const nextMonth = () => setCurrentMonth(new Date(year, month + 1, 1));

  const isDisabled = (date: Date) => {
    if (disabled) return disabled(date);
    return isBefore(date, today);
  };

  return (
    <div className={cn("select-none", className)}>
      {/* Header */}
      <div className="flex items-center justify-between mb-6">
        <button
          onClick={prevMonth}
          className="p-2 rounded-xl hover:bg-ocean-50 transition-colors text-ocean-600"
        >
          <ChevronLeft className="h-5 w-5" />
        </button>
        <h3 className="text-lg font-semibold text-ocean-900">
          {format(currentMonth, "MMMM yyyy")}
        </h3>
        <button
          onClick={nextMonth}
          className="p-2 rounded-xl hover:bg-ocean-50 transition-colors text-ocean-600"
        >
          <ChevronRight className="h-5 w-5" />
        </button>
      </div>

      {/* Day labels */}
      <div className="grid grid-cols-7 gap-1 mb-2">
        {["Sun", "Mon", "Tue", "Wed", "Thu", "Fri", "Sat"].map((d) => (
          <div
            key={d}
            className="text-center text-xs font-medium text-ocean-400 py-2"
          >
            {d}
          </div>
        ))}
      </div>

      {/* Days */}
      <div className="grid grid-cols-7 gap-1">
        {days.map((day, i) => {
          if (!day) return <div key={`blank-${i}`} />;

          const disabled = isDisabled(day);
          const isSelected = selected && isSameDay(day, selected);
          const isToday = isSameDay(day, today);

          return (
            <button
              key={day.toISOString()}
              disabled={disabled}
              onClick={() => onSelect?.(day)}
              className={cn(
                "relative h-11 rounded-xl text-sm font-medium transition-all duration-200",
                disabled && "text-ocean-200 cursor-not-allowed",
                !disabled && !isSelected && "text-ocean-700 hover:bg-ocean-50",
                isSelected && "bg-ocean-500 text-white shadow-md shadow-ocean-500/25 hover:bg-ocean-600",
                isToday && !isSelected && "ring-2 ring-ocean-300 ring-offset-1"
              )}
            >
              {format(day, "d")}
            </button>
          );
        })}
      </div>
    </div>
  );
}
