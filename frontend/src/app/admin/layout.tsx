"use client";

import { usePathname } from "next/navigation";
import { AdminGuard } from "@/components/admin-guard";

export default function AdminLayout({ children }: { children: React.ReactNode }) {
  const pathname = usePathname();

  // Don't guard the login page
  if (pathname === "/admin/login") {
    return <>{children}</>;
  }

  return <AdminGuard>{children}</AdminGuard>;
}
