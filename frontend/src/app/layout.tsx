import type { Metadata } from "next";
import { Nunito, Playfair_Display } from "next/font/google";
import "./globals.css";
import { Navbar, Footer } from "@/components/layout/nav";
import { Toaster } from "sonner";

const nunito = Nunito({ subsets: ["latin"], variable: "--font-nunito" });
const playfair = Playfair_Display({ subsets: ["latin"], variable: "--font-display" });

export const metadata: Metadata = {
  title: "Clear Boat Bahamas | Transparent Boat Tours",
  description:
    "Create lasting memories on our transparent boat tours while we photograph your magical moments. Swim, snorkel, and navigate the crystal-clear waters of the Bahamas.",
};

export default function RootLayout({
  children,
}: {
  children: React.ReactNode;
}) {
  return (
    <html lang="en" className={`${nunito.variable} ${playfair.variable}`}>
      <body className="font-sans antialiased bg-white text-ocean-900">
        <Navbar />
        <main>{children}</main>
        <Footer />
        <Toaster position="top-right" richColors />
      </body>
    </html>
  );
}
