import Link from "next/link";
import { Phone, Mail, MapPin } from "lucide-react";

const FOOTER_LINKS = {
  experience: [
    { label: "Home", href: "/" },
    { label: "About", href: "/about" },
    { label: "Gallery", href: "/gallery" },
    { label: "FAQ", href: "/faq" },
    { label: "Contact", href: "/contact" },
    { label: "Terms of Service", href: "/terms" },
  ],
};

export function Footer() {
  return (
    <footer className="bg-brand-900 text-white">
      <div className="max-w-7xl mx-auto px-5 sm:px-6 lg:px-8 pt-16 pb-8">
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-10 lg:gap-12 mb-12">
          {/* Brand */}
          <div className="lg:col-span-1">
            <img
              src="https://clearboatbahamas.com/wp-content/uploads/2024/06/CB-png-2.png"
              alt="Clear Boat Bahamas"
              className="h-10 w-auto brightness-0 invert mb-4"
            />
            <p className="text-brand-300 text-sm leading-relaxed max-w-xs mb-4">
              The Bahamas&apos; first 100% clear boat experience. Unforgettable transparent boat tours through the crystal-clear waters of Nassau.
            </p>
            {/* Social */}
            <div className="flex items-center gap-4">
              <a href="https://www.instagram.com/clearboatbahamas/" target="_blank" rel="noopener noreferrer" className="text-brand-400 hover:text-white transition-colors text-sm">
                Instagram
              </a>
              <a href="https://x.com/ClearBoatBah" target="_blank" rel="noopener noreferrer" className="text-brand-400 hover:text-white transition-colors text-sm">
                X
              </a>
              <a href="https://www.tiktok.com/@bahamasclearboat" target="_blank" rel="noopener noreferrer" className="text-brand-400 hover:text-white transition-colors text-sm">
                TikTok
              </a>
            </div>
          </div>

          {/* Quick Links */}
          <div>
            <h4 className="text-sm font-semibold uppercase tracking-wider text-brand-300 mb-4">
              Quick Links
            </h4>
            <ul className="space-y-3">
              {FOOTER_LINKS.experience.map((link) => (
                <li key={link.label}>
                  <Link
                    href={link.href}
                    className="text-sm text-brand-200 hover:text-white transition-colors"
                  >
                    {link.label}
                  </Link>
                </li>
              ))}
            </ul>
          </div>

          {/* Contact */}
          <div>
            <h4 className="text-sm font-semibold uppercase tracking-wider text-brand-300 mb-4">
              Get In Touch
            </h4>
            <ul className="space-y-3">
              <li className="flex items-start gap-2.5">
                <MapPin className="w-4 h-4 text-brand-400 mt-0.5 shrink-0" />
                <span className="text-sm text-brand-200">Shop 9, Elizabeth on Bay Plaza<br />Nassau, NP, Bahamas</span>
              </li>
              <li className="flex items-center gap-2.5">
                <Phone className="w-4 h-4 text-brand-400 shrink-0" />
                <a href="tel:+12428128687" className="text-sm text-brand-200 hover:text-white transition-colors">
                  1-242-812-TOUR (8687)
                </a>
              </li>
              <li className="flex items-center gap-2.5">
                <Mail className="w-4 h-4 text-brand-400 shrink-0" />
                <a href="mailto:bookings@clearboatbahamas.com" className="text-sm text-brand-200 hover:text-white transition-colors">
                  bookings@clearboatbahamas.com
                </a>
              </li>
            </ul>
          </div>

          {/* CTA */}
          <div>
            <h4 className="text-sm font-semibold uppercase tracking-wider text-brand-300 mb-4">
              Ready to go?
            </h4>
            <p className="text-sm text-brand-300 mb-4 leading-relaxed">
              Book your 100% transparent boat tour today. Spaces fill up fast.
            </p>
            <Link
              href="/?book=true"
              className="inline-flex items-center justify-center h-11 px-6 text-sm font-semibold bg-gold-400 text-brand-900 rounded-xl hover:bg-gold-500 transition-all duration-200"
            >
              Book Your Tour
            </Link>
          </div>
        </div>

        {/* Bottom bar */}
        <div className="border-t border-brand-800 pt-6 flex flex-col sm:flex-row items-center justify-between gap-4">
          <p className="text-xs text-brand-500">
            © {new Date().getFullYear()} Clear Boat Bahamas. All rights reserved.
          </p>
          <p className="text-xs text-brand-500">
            #CLEARBOATBAHAMAS
          </p>
        </div>
      </div>
    </footer>
  );
}
