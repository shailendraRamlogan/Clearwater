"use client";

import Link from "next/link";
import { useState } from "react";
import { Menu, X, Waves } from "lucide-react";
import { Button } from "@/components/ui/button";
import { cn } from "@/lib/utils";

export function Navbar() {
  const [isOpen, setIsOpen] = useState(false);

  return (
    <nav className="sticky top-0 z-50 bg-white/80 backdrop-blur-md border-b border-ocean-100">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div className="flex justify-between h-16 items-center">
          <Link href="/" className="flex items-center gap-2 group">
            <div className="bg-ocean-500 text-white p-2 rounded-lg group-hover:bg-ocean-600 transition-colors">
              <Waves className="h-5 w-5" />
            </div>
            <span className="font-display text-xl font-bold text-ocean-900">
              Clear Boat
            </span>
          </Link>

          <div className="hidden md:flex items-center gap-8">
            <Link
              href="/#about"
              className="text-sm font-medium text-ocean-600 hover:text-ocean-800 transition-colors"
            >
              About
            </Link>
            <Link
              href="/#pricing"
              className="text-sm font-medium text-ocean-600 hover:text-ocean-800 transition-colors"
            >
              Pricing
            </Link>
            <Link
              href="/#gallery"
              className="text-sm font-medium text-ocean-600 hover:text-ocean-800 transition-colors"
            >
              Gallery
            </Link>
            <Link href="/admin">
              <span className="text-xs text-ocean-400 hover:text-ocean-600 transition-colors">
                Admin
              </span>
            </Link>
            <Link href="/book">
              <Button variant="cta">Book Now</Button>
            </Link>
          </div>

          <button
            className="md:hidden p-2 text-ocean-600"
            onClick={() => setIsOpen(!isOpen)}
          >
            {isOpen ? <X className="h-6 w-6" /> : <Menu className="h-6 w-6" />}
          </button>
        </div>
      </div>

      <div
        className={cn(
          "md:hidden overflow-hidden transition-all duration-300",
          isOpen ? "max-h-64" : "max-h-0"
        )}
      >
        <div className="px-4 py-4 space-y-3 border-t border-ocean-100">
          <Link href="/#about" className="block text-sm font-medium text-ocean-600" onClick={() => setIsOpen(false)}>
            About
          </Link>
          <Link href="/#pricing" className="block text-sm font-medium text-ocean-600" onClick={() => setIsOpen(false)}>
            Pricing
          </Link>
          <Link href="/#gallery" className="block text-sm font-medium text-ocean-600" onClick={() => setIsOpen(false)}>
            Gallery
          </Link>
          <Link href="/book" onClick={() => setIsOpen(false)}>
            <Button variant="cta" className="w-full">Book Now</Button>
          </Link>
        </div>
      </div>
    </nav>
  );
}

export function Footer() {
  return (
    <footer className="bg-ocean-950 text-ocean-200">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <div className="grid grid-cols-1 md:grid-cols-3 gap-8">
          <div>
            <div className="flex items-center gap-2 mb-4">
              <div className="bg-ocean-500 text-white p-2 rounded-lg">
                <Waves className="h-5 w-5" />
              </div>
              <span className="font-display text-xl font-bold text-white">
                Clear Boat Bahamas
              </span>
            </div>
            <p className="text-sm text-ocean-300">
              Create lasting memories on our transparent boat tours. See the sea like never before.
            </p>
          </div>
          <div>
            <h4 className="font-semibold text-white mb-4">Quick Links</h4>
            <ul className="space-y-2 text-sm">
              <li><Link href="/book" className="hover:text-white transition-colors">Book a Tour</Link></li>
              <li><Link href="/#about" className="hover:text-white transition-colors">About Us</Link></li>
              <li><Link href="/#pricing" className="hover:text-white transition-colors">Pricing</Link></li>
            </ul>
          </div>
          <div>
            <h4 className="font-semibold text-white mb-4">Contact</h4>
            <ul className="space-y-2 text-sm">
              <li>Nassau, New Providence</li>
              <li>The Bahamas</li>
              <li className="pt-2">info@clearboatbahamas.com</li>
            </ul>
          </div>
        </div>
        <div className="border-t border-ocean-800 mt-8 pt-8 text-center text-sm text-ocean-400">
          © {new Date().getFullYear()} Clear Boat Bahamas. All rights reserved.
        </div>
      </div>
    </footer>
  );
}
