"use client";

import { useEffect } from "react";
import { useRouter } from "next/navigation";
import { isAdmin } from "@/lib/admin-auth";

export function AdminGuard({ children }: { children: React.ReactNode }) {
  const router = useRouter();

  useEffect(() => {
    if (!isAdmin()) {
      router.replace("/admin/login");
    }
  }, [router]);

  if (!isAdmin()) {
    return (
      <div className="section-container py-20 text-center">
        <p className="text-ocean-400">Redirecting to login…</p>
      </div>
    );
  }

  return <>{children}</>;
}
