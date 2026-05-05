"use client";

import { Suspense } from "react";
import ConfirmationContent from "./confirmation-content";

export default function ConfirmationPage() {
  return (
    <Suspense fallback={<div className="min-h-screen flex items-center justify-center"><p className="text-brand-400">Loading...</p></div>}>
      <ConfirmationContent />
    </Suspense>
  );
}
