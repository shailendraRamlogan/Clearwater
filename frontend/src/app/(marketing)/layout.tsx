import { Metadata } from "next";
import { Navbar } from "@/components/layout/navbar";
import { Footer } from "@/components/layout/footer";

export const metadata: Metadata = {
  title: {
    default: "Clear Boat Bahamas | Transparent Boat Tours in Nassau",
    template: "%s | Clear Boat Bahamas",
  },
  description:
    "Swim, snorkel, and sail through crystal-clear Bahamian waters aboard our transparent boats. Professional photos, island drinks, and unforgettable memories included.",
};

export default function MarketingLayout({
  children,
}: {
  children: React.ReactNode;
}) {
  return (
    <>
      <Navbar />
      {children}
      <Footer />
    </>
  );
}
