import type { Metadata } from "next";
import { DM_Sans, DM_Serif_Display } from "next/font/google";
import "./globals.css";

const dmSans = DM_Sans({
  subsets: ["latin"],
  variable: "--font-dm-sans",
  display: "swap",
});

const dmSerif = DM_Serif_Display({
  subsets: ["latin"],
  weight: "400",
  variable: "--font-display",
  display: "swap",
});

export const metadata: Metadata = {
  title: "Clear Boat Bahamas | Transparent Boat Tours in Nassau",
  description:
    "Swim, snorkel, and sail through crystal-clear Bahamian waters aboard our transparent boats. Professional photos, island drinks, and unforgettable memories included.",
};

export default function RootLayout({
  children,
}: {
  children: React.ReactNode;
}) {
  return (
    <html lang="en" className={`${dmSans.variable} ${dmSerif.variable}`}>
      <body className="font-sans antialiased bg-white text-brand-900">
        {children}
      </body>
    </html>
  );
}
