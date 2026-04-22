"use client";

import Link from "next/link";
import { motion } from "framer-motion";
import {
  Camera,
  Wine,
  Waves,
  CheckCircle,
  Calendar,
  Clock,
  Users,
  Star,
  ChevronRight,
} from "lucide-react";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { formatCurrency } from "@/lib/utils";

const fadeInUp = {
  initial: { opacity: 0, y: 30 },
  whileInView: { opacity: 1, y: 0 },
  viewport: { once: true },
  transition: { duration: 0.6 },
};

export default function HomePage() {
  return (
    <div>
      {/* Hero */}
      <section className="relative min-h-[90vh] flex items-center bg-hero-gradient overflow-hidden">
        <div className="absolute inset-0 bg-[url('https://images.unsplash.com/photo-1507525428034-b723cf961d3e?w=1920&q=80')] bg-cover bg-center mix-blend-overlay opacity-30" />
        <div className="absolute inset-0 bg-gradient-to-t from-ocean-950/80 to-transparent" />
        <div className="section-container relative z-10 py-20">
          <motion.div
            initial={{ opacity: 0, y: 40 }}
            animate={{ opacity: 1, y: 0 }}
            transition={{ duration: 0.8 }}
            className="max-w-3xl"
          >
            <div className="inline-flex items-center gap-2 bg-white/10 backdrop-blur-sm border border-white/20 rounded-full px-4 py-2 mb-6">
              <Star className="h-4 w-4 text-sand-400 fill-sand-400" />
              <span className="text-sm text-white/90">
                #1 Tour Experience in Nassau
              </span>
            </div>
            <h1 className="font-display text-5xl sm:text-6xl lg:text-7xl font-bold text-white leading-tight mb-6">
              See the Sea Like{" "}
              <span className="text-ocean-300">Never Before</span>
            </h1>
            <p className="text-lg sm:text-xl text-ocean-100 max-w-2xl mb-8 leading-relaxed">
              Create lasting memories on our transparent boat tours while we
              photograph your magical moments. Swim, snorkel, and navigate the
              crystal-clear waters of the Bahamas.
            </p>
            <div className="flex flex-col sm:flex-row gap-4">
              <Link href="/book">
                <Button variant="cta" size="xl">
                  Book Your Adventure
                  <ChevronRight className="ml-2 h-5 w-5" />
                </Button>
              </Link>
              <a href="#about">
                <Button
                  variant="outline"
                  size="xl"
                  className="border-white/30 bg-white/10 text-white hover:bg-white/20 hover:text-white"
                >
                  Learn More
                </Button>
              </a>
            </div>
          </motion.div>
        </div>
      </section>

      {/* About */}
      <section id="about" className="py-20 bg-white">
        <div className="section-container">
          <motion.div {...fadeInUp} className="text-center mb-16">
            <h2 className="font-display text-4xl font-bold text-ocean-900 mb-4">
              The Clear Boat Experience
            </h2>
            <p className="text-ocean-500 max-w-2xl mx-auto text-lg">
              A 2.5-hour adventure through the stunning waters of Nassau aboard
              our one-of-a-kind transparent boats.
            </p>
          </motion.div>

          <div className="grid grid-cols-1 md:grid-cols-3 gap-8">
            {[
              {
                icon: Waves,
                title: "Crystal Clear Waters",
                desc: "Navigate the pristine waters of the Bahamas in our see-through boats — it's like flying over the ocean.",
              },
              {
                icon: Camera,
                title: "Professional Photos",
                desc: "We capture your favorite moments — snorkeling, sightseeing, family fun — while you enjoy the adventure.",
              },
              {
                icon: Wine,
                title: "Island Beverages",
                desc: "Sip handmade island lemonade, Bahamian beers, and Caribbean rum tastings as you sail.",
              },
            ].map((item, i) => (
              <motion.div
                key={i}
                {...fadeInUp}
                transition={{ duration: 0.6, delay: i * 0.15 }}
              >
                <Card className="h-full border-0 shadow-lg hover:shadow-xl transition-shadow">
                  <CardContent className="pt-8 pb-8 text-center">
                    <div className="inline-flex items-center justify-center w-16 h-16 bg-ocean-50 rounded-2xl mb-6">
                      <item.icon className="h-8 w-8 text-ocean-500" />
                    </div>
                    <h3 className="text-xl font-semibold mb-3">{item.title}</h3>
                    <p className="text-ocean-500 leading-relaxed">
                      {item.desc}
                    </p>
                  </CardContent>
                </Card>
              </motion.div>
            ))}
          </div>
        </div>
      </section>

      {/* Pricing */}
      <section id="pricing" className="py-20 bg-ocean-50">
        <div className="section-container">
          <motion.div {...fadeInUp} className="text-center mb-16">
            <h2 className="font-display text-4xl font-bold text-ocean-900 mb-4">
              Tour Packages
            </h2>
            <p className="text-ocean-500 max-w-2xl mx-auto text-lg">
              2.5 hours of unforgettable memories
            </p>
          </motion.div>

          <div className="grid grid-cols-1 md:grid-cols-2 gap-8 max-w-4xl mx-auto">
            {/* Adult */}
            <motion.div {...fadeInUp}>
              <Card className="h-full border-0 shadow-xl relative overflow-hidden">
                <div className="absolute top-0 left-0 right-0 h-1 bg-ocean-500" />
                <CardHeader className="text-center pb-2">
                  <CardTitle className="text-2xl">Adult Tour</CardTitle>
                  <div className="mt-4">
                    <span className="text-5xl font-bold text-ocean-700">
                      {formatCurrency(200)}
                    </span>
                    <span className="text-ocean-400 ml-1">/ person</span>
                  </div>
                </CardHeader>
                <CardContent className="pt-4">
                  <ul className="space-y-3 mb-8">
                    {[
                      "Multiple action photos included",
                      "Homemade island lemonade (2 choices)",
                      "Bahamian beers (up to 3)",
                      "Fruit-flavored Raddlers (up to 3)",
                      "Caribbean rum tasting",
                      "Light snacks provided",
                    ].map((item, i) => (
                      <li key={i} className="flex items-start gap-3">
                        <CheckCircle className="h-5 w-5 text-ocean-500 shrink-0 mt-0.5" />
                        <span className="text-sm text-ocean-600">{item}</span>
                      </li>
                    ))}
                  </ul>
                  <Link href="/book" className="block">
                    <Button variant="cta" className="w-full" size="lg">
                      Book Adult Tour
                    </Button>
                  </Link>
                </CardContent>
              </Card>
            </motion.div>

            {/* Child */}
            <motion.div {...fadeInUp} transition={{ delay: 0.15 }}>
              <Card className="h-full border-0 shadow-xl relative overflow-hidden">
                <div className="absolute top-0 left-0 right-0 h-1 bg-sand-400" />
                <CardHeader className="text-center pb-2">
                  <CardTitle className="text-2xl">Child Tour</CardTitle>
                  <div className="mt-4">
                    <span className="text-5xl font-bold text-ocean-700">
                      {formatCurrency(150)}
                    </span>
                    <span className="text-ocean-400 ml-1">/ child</span>
                  </div>
                </CardHeader>
                <CardContent className="pt-4">
                  <ul className="space-y-3 mb-8">
                    {[
                      "Multiple action photos included",
                      "Unleaded lemonade",
                      "Bottled water",
                      "Non-alcoholic sparkling beverages",
                      "Light snacks provided",
                      "Family-friendly fun",
                    ].map((item, i) => (
                      <li key={i} className="flex items-start gap-3">
                        <CheckCircle className="h-5 w-5 text-sand-500 shrink-0 mt-0.5" />
                        <span className="text-sm text-ocean-600">{item}</span>
                      </li>
                    ))}
                  </ul>
                  <Link href="/book" className="block">
                    <Button
                      variant="outline"
                      className="w-full border-ocean-300 text-ocean-700 hover:bg-ocean-50"
                      size="lg"
                    >
                      Book Child Tour
                    </Button>
                  </Link>
                </CardContent>
              </Card>
            </motion.div>
          </div>
        </div>
      </section>

      {/* How It Works */}
      <section className="py-20 bg-white">
        <div className="section-container">
          <motion.div {...fadeInUp} className="text-center mb-16">
            <h2 className="font-display text-4xl font-bold text-ocean-900 mb-4">
              How It Works
            </h2>
          </motion.div>
          <div className="grid grid-cols-1 md:grid-cols-3 gap-12 max-w-4xl mx-auto">
            {[
              {
                icon: Calendar,
                step: "1",
                title: "Pick Your Date",
                desc: "Choose a date that works for you from our live availability calendar.",
              },
              {
                icon: Clock,
                step: "2",
                title: "Select a Time",
                desc: "Pick from multiple daily departures. Morning and afternoon slots available.",
              },
              {
                icon: Users,
                step: "3",
                title: "Enjoy the Ride",
                desc: "Show up, hop on, and let us take care of the rest. Photos included!",
              },
            ].map((item, i) => (
              <motion.div
                key={i}
                {...fadeInUp}
                transition={{ delay: i * 0.15 }}
                className="text-center"
              >
                <div className="relative inline-flex items-center justify-center w-20 h-20 bg-ocean-500 text-white rounded-full mb-6">
                  <item.icon className="h-8 w-8" />
                  <span className="absolute -top-2 -right-2 w-8 h-8 bg-sand-400 text-ocean-900 rounded-full flex items-center justify-center text-sm font-bold">
                    {item.step}
                  </span>
                </div>
                <h3 className="text-xl font-semibold mb-2">{item.title}</h3>
                <p className="text-ocean-500">{item.desc}</p>
              </motion.div>
            ))}
          </div>
        </div>
      </section>

      {/* CTA */}
      <section className="py-20 bg-hero-gradient relative overflow-hidden">
        <div className="absolute inset-0 bg-[url('https://images.unsplash.com/photo-1544551763-46a013bb70d5?w=1920&q=80')] bg-cover bg-center mix-blend-overlay opacity-20" />
        <div className="section-container relative z-10 text-center">
          <motion.div {...fadeInUp}>
            <h2 className="font-display text-4xl sm:text-5xl font-bold text-white mb-6">
              Ready for Your Adventure?
            </h2>
            <p className="text-ocean-200 text-lg max-w-2xl mx-auto mb-8">
              Spaces fill up fast. Book your transparent boat tour today and
              experience the Bahamas like never before.
            </p>
            <Link href="/book">
              <Button variant="cta" size="xl">
                Book Your Tour Now
                <ChevronRight className="ml-2 h-5 w-5" />
              </Button>
            </Link>
          </motion.div>
        </div>
      </section>
    </div>
  );
}
