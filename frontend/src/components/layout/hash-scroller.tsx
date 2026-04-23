"use client";

import { useEffect } from "react";
import { usePathname } from "next/navigation";

export function HashScroller() {
  const pathname = usePathname();

  useEffect(() => {
    const hash = window.location.hash.slice(1);
    if (hash && pathname === "/") {
      // Small delay to ensure DOM is ready
      const timer = setTimeout(() => {
        const el = document.getElementById(hash);
        if (el) el.scrollIntoView({ behavior: "smooth" });
      }, 100);
      return () => clearTimeout(timer);
    }
  }, [pathname]);

  return null;
}
