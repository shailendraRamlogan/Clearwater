"use client";

import { useState } from "react";
import { format } from "date-fns";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import { Button } from "@/components/ui/button";
import { Download, FileText } from "lucide-react";
import { getDailyReport, getSchedulePdfUrl } from "@/lib/booking-service";
import { formatCurrency } from "@/lib/utils";
import type { DailyReport } from "@/types/booking";
import { toast } from "sonner";

export default function AdminReports() {
  const [date, setDate] = useState(format(new Date(), "yyyy-MM-dd"));
  const [report, setReport] = useState<DailyReport | null>(null);
  const [loadingReport, setLoadingReport] = useState(false);
  const [loadingPdf, setLoadingPdf] = useState(false);
  const [error, setError] = useState("");

  const handleGenerate = async () => {
    setLoadingReport(true);
    setError("");
    try {
      const r = await getDailyReport(date);
      setReport(r);
    } catch (e: unknown) {
      setError((e as Error).message || "Failed to generate report");
    } finally {
      setLoadingReport(false);
    }
  };

  const handlePdf = async () => {
    setLoadingPdf(true);
    try {
      const url = await getSchedulePdfUrl(date);
      if (url && url !== "#") {
        const a = document.createElement("a");
        a.href = url;
        a.download = `schedule-${date}.pdf`;
        a.click();
        URL.revokeObjectURL(url);
      } else {
        toast.error("PDF export not available");
      }
    } catch (e: unknown) {
      toast.error((e as Error).message || "Failed to export PDF");
    } finally {
      setLoadingPdf(false);
    }
  };

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
            <Button variant="outline" disabled={loadingReport} onClick={handleGenerate}>
              <FileText className="mr-2 h-4 w-4" />
              {loadingReport ? "Loading..." : "Generate Report"}
            </Button>
            <Button disabled={loadingPdf} onClick={handlePdf}>
              <Download className="mr-2 h-4 w-4" />
              {loadingPdf ? "Exporting..." : "Export PDF"}
            </Button>
          </div>
        </CardContent>
      </Card>

      {error && (
        <div className="bg-red-50 border border-red-200 text-red-700 rounded-lg px-4 py-3 text-sm mb-6">
          {error}
        </div>
      )}

      <div className="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
        <Card>
          <CardHeader>
            <CardTitle className="text-lg">Summary</CardTitle>
          </CardHeader>
          <CardContent>
            <div className="space-y-3">
              <div className="flex justify-between">
                <span className="text-ocean-500">Total Bookings</span>
                <span className="font-semibold">{report?.total_bookings ?? "—"}</span>
              </div>
              <div className="flex justify-between">
                <span className="text-ocean-500">Total Adults</span>
                <span className="font-semibold">{report?.total_adults ?? "—"}</span>
              </div>
              <div className="flex justify-between">
                <span className="text-ocean-500">Total Children</span>
                <span className="font-semibold">{report?.total_children ?? "—"}</span>
              </div>
              <div className="flex justify-between border-t pt-3">
                <span className="text-ocean-500">Total Revenue</span>
                <span className="font-bold text-lg">
                  {report ? formatCurrency(report.total_revenue) : "—"}
                </span>
              </div>
            </div>
          </CardContent>
        </Card>
        <Card>
          <CardHeader>
            <CardTitle className="text-lg">Schedule Overview</CardTitle>
          </CardHeader>
          <CardContent>
            {report && report.bookings.length > 0 ? (
              <div className="space-y-2">
                {report.bookings.map((b) => (
                  <div key={b.id} className="flex justify-between text-sm p-2 bg-ocean-50 rounded">
                    <span className="font-medium">{b.id}</span>
                    <span>{b.guest.first_name} {b.guest.last_name}</span>
                    <span className="font-semibold">{formatCurrency(b.total_price)}</span>
                  </div>
                ))}
              </div>
            ) : (
              <div className="text-center py-6 text-ocean-400">
                <p className="text-sm">
                  {report ? "No bookings for this date." : "Generate a report to see schedule data."}
                </p>
              </div>
            )}
          </CardContent>
        </Card>
      </div>
    </div>
  );
}
