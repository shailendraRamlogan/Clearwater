"use client";

import Link from "next/link";
import { usePathname } from "next/navigation";
import { useState, useEffect } from "react";
import { Menu, X, Phone } from "lucide-react";
import { cn } from "@/lib/utils";

const NAV_LINKS = [
  { label: "Home", href: "/" },
  { label: "About", href: "/about" },
  { label: "Gallery", href: "/gallery" },
  { label: "FAQ", href: "/faq" },
  { label: "Contact", href: "/contact" },
];

export function Navbar() {
  const [isOpen, setIsOpen] = useState(false);
  const [scrolled, setScrolled] = useState(false);
  const pathname = usePathname();
  const isHome = pathname === "/";

  useEffect(() => {
    const onScroll = () => setScrolled(window.scrollY > 20);
    window.addEventListener("scroll", onScroll, { passive: true });
    return () => window.removeEventListener("scroll", onScroll);
  }, []);

  // Close mobile menu on route change
  useEffect(() => {
    setIsOpen(false);
  }, [pathname]);

  // On inner pages (not home), always use solid bg unless explicitly transparent is needed
  const useTransparent = isHome && !scrolled && !isOpen;
  const textClass = useTransparent
    ? "text-white/90 hover:text-white hover:bg-white/10"
    : "text-brand-800 hover:text-brand-700 hover:bg-brand-50";
  const logoClass = useTransparent
    ? "brightness-0 invert"
    : "";
  const phoneClass = useTransparent
    ? "text-white/80 hover:text-white"
    : "text-brand-700";

  return (
    <>
      {/* Desktop Navbar */}
      <nav
        className={cn(
          "fixed top-0 left-0 right-0 z-50 hidden lg:block transition-all duration-300",
          useTransparent
            ? "bg-transparent"
            : "bg-white/95 backdrop-blur-md shadow-sm border-b border-brand-100"
        )}
      >
        <div className="max-w-7xl mx-auto px-8">
          <div className="flex items-center justify-between h-20">
            <Link href="/" className="flex-shrink-0">
              <img
                src="https://clearboatbahamas.com/wp-content/uploads/2024/06/CB-png-2.png"
                alt="Clear Boat Bahamas"
                className={cn("h-12 w-auto transition-all duration-300", logoClass)}
              />
            </Link>

            <div className="flex items-center gap-1">
              {NAV_LINKS.map((link) => (
                <Link
                  key={link.label}
                  href={link.href}
                  className={cn(
                    "text-sm font-medium px-4 py-2 rounded-lg transition-colors",
                    textClass
                  )}
                >
                  {link.label}
                </Link>
              ))}
            </div>

            <div className="flex items-center gap-4">
              <a
                href="tel:+1242XXXYYYY"
                className={cn(
                  "flex items-center gap-2 text-sm font-medium transition-colors",
                  phoneClass
                )}
              >
                <Phone className="w-4 h-4" />
                <span className="hidden xl:inline">Call Us</span>
              </a>
              <Link
                href="/?book=true"
                className={cn(
                  "inline-flex items-center justify-center h-11 px-6 text-sm font-semibold rounded-xl transition-all duration-200",
                  useTransparent
                    ? "bg-white text-brand-800 hover:bg-white/90"
                    : "bg-brand-700 text-white hover:bg-brand-800"
                )}
              >
                Book Now
              </Link>
            </div>
          </div>
        </div>
      </nav>

      {/* Mobile Navbar */}
      <nav
        className={cn(
          "fixed top-0 left-0 right-0 z-50 lg:hidden transition-all duration-300",
          useTransparent
            ? "bg-transparent"
            : "bg-white/95 backdrop-blur-md shadow-sm"
        )}
      >
        <div className="flex items-center justify-between h-16 px-5">
          <Link href="/" className="flex-shrink-0">
            <img
              src="https://clearboatbahamas.com/wp-content/uploads/2024/06/CB-png-2.png"
              alt="Clear Boat Bahamas"
              className={cn("h-9 w-auto transition-all duration-300", logoClass)}
            />
          </Link>
          <div className="flex items-center gap-3">
            <Link
              href="/?book=true"
              className="btn-primary-sm text-xs h-9 px-4"
            >
              Book Now
            </Link>
            <button
              onClick={() => setIsOpen(!isOpen)}
              className={cn(
                "p-2 rounded-lg transition-colors",
                useTransparent
                  ? "text-white hover:bg-white/10"
                  : "text-brand-800 hover:bg-brand-50"
              )}
              aria-label="Toggle menu"
            >
              {isOpen ? <X className="w-5 h-5" /> : <Menu className="w-5 h-5" />}
            </button>
          </div>
        </div>

        {/* Mobile menu */}
        <div
          className={cn(
            "lg:hidden overflow-hidden transition-all duration-300 bg-white",
            isOpen ? "max-h-80 border-t border-brand-100" : "max-h-0"
          )}
        >
          <div className="px-5 py-4 space-y-1">
            {NAV_LINKS.map((link) => (
              <Link
                key={link.label}
                href={link.href}
                className="block text-sm font-medium text-brand-800 hover:text-brand-700 hover:bg-brand-50 px-3 py-2.5 rounded-lg transition-colors"
              >
                {link.label}
              </Link>
            ))}
            <div className="pt-2 border-t border-brand-100 mt-2">
              <Link
                href="/?book=true"
                className="btn-primary w-full text-center h-12 text-sm rounded-xl"
              >
                Book Your Tour
              </Link>
            </div>
          </div>
        </div>
      </nav>
    </>
  );
}
