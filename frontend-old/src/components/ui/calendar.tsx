"use client";

import {
  format,
  isBefore,
  startOfDay,
  isSameDay,
  startOfMonth,
  endOfMonth,
  eachDayOfInterval,
  startOfWeek,
  endOfWeek,
  addMonths,
  subMonths,
} from "date-fns";
import { ChevronLeft, ChevronRight } from "lucide-react";
import { cn } from "@/lib/utils";
import { useState } from "react";

interface CompactCalendarProps {
  selected?: Date;
  onSelect?: (date: Date) => void;
  disabled?: (date: Date) => boolean;
  className?: string;
}

const DAY_LABELS = ["Sun", "Mon", "Tue", "Wed", "Thu", "Fri", "Sat"];

export function ModernCalendar({
  selected,
  onSelect,
  disabled,
  className,
}: CompactCalendarProps) {
  const [monthStart, setMonthStart] = useState(() => startOfMonth(selected || new Date()));

  const today = startOfDay(new Date());
  const calendarStart = startOfWeek(monthStart);
  const calendarEnd = endOfWeek(endOfMonth(monthStart));
  const days = eachDayOfInterval({ start: calendarStart, end: calendarEnd });

  const isDisabled = (date: Date) => {
    if (disabled) return disabled(date);
    return isBefore(date, today);
  };

  const goBack = () => {
    const prev = subMonths(monthStart, 1);
    if (isBefore(prev, startOfMonth(today))) return;
    setMonthStart(prev);
  };

  const goForward = () => setMonthStart(addMonths(monthStart, 1));
  const goToday = () => setMonthStart(startOfMonth(today));

  const monthLabel = format(monthStart, "MMMM yyyy");

  return (
    <div className={cn("w-full", className)}>
      {/* Header */}
      <div className="flex items-center justify-between mb-4">
        <button
          onClick={goBack}
          className="p-1.5 rounded-lg hover:bg-ocean-50 transition-colors text-ocean-400 hover:text-ocean-600"
        >
          <ChevronLeft className="h-4 w-4" />
        </button>
        <button onClick={goToday} className="text-sm font-semibold text-ocean-700 hover:text-ocean-900 transition-colors">
          {monthLabel}
        </button>
        <button
          onClick={goForward}
          className="p-1.5 rounded-lg hover:bg-ocean-50 transition-colors text-ocean-400 hover:text-ocean-600"
        >
          <ChevronRight className="h-4 w-4" />
        </button>
      </div>

      {/* Day labels */}
      <div className="grid grid-cols-7 gap-1 mb-1">
        {DAY_LABELS.map((d) => (
          <div
            key={d}
            className="text-center text-[10px] font-medium text-ocean-400 uppercase py-1"
          >
            {d}
          </div>
        ))}
      </div>

      {/* Date grid */}
      <div className="grid grid-cols-7 gap-1">
        {days.map((day) => {
          const dis = isDisabled(day);
          const isSelected = selected && isSameDay(day, selected);
          const isToday = isSameDay(day, today);
          const inMonth = day.getMonth() === monthStart.getMonth();

          return (
            <button
              key={day.toISOString()}
              disabled={dis}
              onClick={() => onSelect?.(day)}
              className={cn(
                "flex flex-col items-center justify-center py-3 sm:py-2 rounded-xl text-xs transition-all duration-200",
                dis && "text-ocean-200 cursor-not-allowed",
                !dis && !isSelected && "text-ocean-600 hover:bg-ocean-50",
                !inMonth && !isSelected && "text-ocean-300",
                isSelected && "bg-ocean-700 text-white shadow-sm",
                isToday && !isSelected && "ring-2 ring-ocean-700 ring-offset-1 font-bold"
              )}
            >
              <span className={cn("text-sm font-medium leading-none", isSelected && "font-bold")}>
                {format(day, "d")}
              </span>
              {isToday && (
                <span className={cn("w-1 h-1 rounded-full mt-1", isSelected ? "bg-white" : "bg-ocean-400")} />
              )}
            </button>
          );
        })}
      </div>
    </div>
  );
}
