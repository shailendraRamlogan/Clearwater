"use client";

import Link from "next/link";
import { useState } from "react";
import { Menu, X, Waves } from "lucide-react";
import { Button } from "@/components/ui/button";
import { cn } from "@/lib/utils";

const scrollTo = (id: string) => () =>
  document.getElementById(id)?.scrollIntoView({ behavior: "smooth" });

export function Navbar() {
  const [isOpen, setIsOpen] = useState(false);

  return (
    <nav className="sticky top-0 z-50 border-b border-transparent" style={{ background: '#f6f6f6a6' }}>
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div className="flex justify-between h-16 items-center">
          <Link href="/" className="flex items-center gap-2.5 group">
            <div className="bg-ocean-500 text-white p-2 rounded-lg transition-colors">
              <Waves className="h-5 w-5" />
            </div>
            <div className="flex flex-col leading-tight">
              <span className="font-display text-xl font-bold text-ocean-900">Clear Boat</span>
              <span className="text-[11px] font-medium text-ocean-400">Bahamas</span>
            </div>
          </Link>

          <div className="hidden md:flex items-center gap-1">
            {[
              { label: "About", id: "about" },
              { label: "Pricing", id: "pricing" },
              { label: "Gallery", id: "gallery" },
            ].map((item) => (
              <button
                key={item.id}
                onClick={scrollTo(item.id)}
                className="text-sm font-medium text-ocean-600 hover:text-ocean-900 hover:bg-ocean-50 px-4 py-2 rounded-lg transition-all"
              >
                {item.label}
              </button>
            ))}
            <div className="ml-4">
              <Link href="/book">
                <Button variant="cta">Book Now</Button>
              </Link>
            </div>
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
          <button onClick={() => { scrollTo("about")(); setIsOpen(false); }} className="block text-sm font-medium text-ocean-600">About</button>
          <button onClick={() => { scrollTo("pricing")(); setIsOpen(false); }} className="block text-sm font-medium text-ocean-600">Pricing</button>
          <button onClick={() => { scrollTo("gallery")(); setIsOpen(false); }} className="block text-sm font-medium text-ocean-600">Gallery</button>
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
            <div className="flex items-center gap-2.5">
              <div className="bg-ocean-500 text-white p-2 rounded-lg">
                <Waves className="h-5 w-5" />
              </div>
              <div className="flex flex-col leading-tight">
                <span className="font-display text-xl font-bold text-white">Clear Boat</span>
                <span className="text-[11px] font-medium text-ocean-300">Bahamas</span>
              </div>
            </div>
            <p className="text-sm text-ocean-300">
              Create lasting memories on our transparent boat tours. See the sea like never before.
            </p>
          </div>
          <div>
            <h4 className="font-semibold text-white mb-4">Quick Links</h4>
            <ul className="space-y-2 text-sm">
              <li><Link href="/book" className="hover:text-white transition-colors">Book a Tour</Link></li>
              <li><button onClick={scrollTo("about")} className="hover:text-white transition-colors">About Us</button></li>
              <li><button onClick={scrollTo("pricing")} className="hover:text-white transition-colors">Pricing</button></li>
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
