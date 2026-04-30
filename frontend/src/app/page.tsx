"use client";

import { useEffect, useState } from "react";
import Link from "next/link";
import {
  Camera,
  Wine,
  Waves,
  CheckCircle,
  Calendar,
  Clock,
  Users,
  ChevronRight,
  Sparkles,
} from "lucide-react";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { formatCurrency } from "@/lib/utils";
import { getTicketTypes } from "@/lib/booking-service";
import type { TicketType } from "@/types/booking";

export default function HomePage() {
  const [ticketTypes, setTicketTypes] = useState<TicketType[]>([]);
  const [mounted, setMounted] = useState(false);

  useEffect(() => {
    setMounted(true);
    getTicketTypes().then((types) => {
      if (types.length > 0) setTicketTypes(types);
    }).catch(() => {});
  }, []);

  return (
    <div>
      {/* Hero */}
      <section className="relative min-h-screen flex items-center bg-hero-gradient overflow-hidden -mt-16">
        <div className="absolute inset-0">
          <iframe
            className="pointer-events-none absolute top-1/2 left-1/2 h-[56.25vw] min-h-full w-[177.78vh] min-w-full"
            style={{ transform: 'translate(-50%, -50%)', border: 0 }}
            src="https://player.cloudinary.com/embed/?cloud_name=dcxl5ucot&public_id=Untitled_design_jlmdh6&autoplay=true&loop=true&controls=false&muted=true"
            title="Clear Boat Bahamas"
            allow="autoplay"
          />
          <div className="absolute inset-0 bg-ocean-950/50" />
        </div>
        <div className="absolute inset-0 bg-gradient-to-t from-ocean-950/80 to-transparent" />
        <div className="section-container relative z-10 py-20">
          <div className="max-w-3xl text-center sm:text-left mx-auto sm:mx-0">
            <h1 className="text-3xl sm:text-5xl lg:text-6xl font-bold text-white mb-6">
              See the Sea Like{" "}
              <span className="text-ocean-300">Never Before</span>
            </h1>
            <p className="text-base sm:text-lg text-ocean-100 max-w-2xl mb-8 leading-relaxed">
              Create lasting memories on our transparent boat tours while we
              photograph your magical moments. Swim, snorkel, and navigate the
              crystal-clear waters of the Bahamas.
            </p>
            <div className="flex flex-col sm:flex-row gap-4 sm:w-auto w-full mx-auto sm:mx-0">
              <Link href="/book">
                <Button variant="cta" size="xl" className="w-full sm:w-auto">
                  Book Your Adventure
                  <ChevronRight className="ml-2 h-5 w-5" />
                </Button>
              </Link>
              <Button
                variant="outline"
                size="xl"
                className="w-full sm:w-auto border-white/30 bg-white/10 text-white hover:bg-white/20 hover:text-white"
                onClick={() => document.getElementById("about")?.scrollIntoView({ behavior: "smooth" })}
              >
                Learn More
              </Button>
            </div>
          </div>
        </div>
      </section>

      {/* About */}
      <section id="about" className="py-12 sm:py-20 bg-white">
        <div className="section-container">
          <div className="text-center mb-12">
            <h2 className="text-3xl font-bold text-ocean-900 mb-4">
              The Clear Boat Experience
            </h2>
            <p className="text-ocean-500 max-w-2xl mx-auto">
              A 2.5-hour adventure through the stunning waters of Nassau aboard
              our one-of-a-kind transparent boats.
            </p>
          </div>
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
              <Card key={i} className="h-full border border-ocean-100 hover:border-ocean-200 transition-colors">
                <CardContent className="pt-8 pb-8 text-center">
                  <div className="inline-flex items-center justify-center w-14 h-14 bg-ocean-50 rounded-lg mb-6">
                    <item.icon className="h-7 w-7 text-ocean-500" />
                  </div>
                  <h3 className="text-lg font-semibold mb-3">{item.title}</h3>
                  <p className="text-ocean-500 leading-relaxed text-sm">
                    {item.desc}
                  </p>
                </CardContent>
              </Card>
            ))}
          </div>
        </div>
      </section>

      {/* Pricing */}
      <section id="pricing" className="py-12 sm:py-20 bg-ocean-50">
        <div className="section-container">
          <div className="text-center mb-12">
            <h2 className="text-3xl font-bold text-ocean-900 mb-4">
              Tour Packages
            </h2>
            <p className="text-ocean-500 max-w-2xl mx-auto">
              2.5 hours of unforgettable memories
            </p>
          </div>
          <div className="grid grid-cols-1 md:grid-cols-2 gap-8 max-w-4xl mx-auto">
            {!mounted || ticketTypes.length === 0 ? (
              <div className="col-span-2 text-center py-12 text-ocean-400">Loading tour packages…</div>
            ) : (
              ticketTypes.map((type) => {
                const isAdult = type.name.toLowerCase() === 'adult';
                const priceDollars = type.price_cents / 100;
                const accentClass = isAdult ? 'bg-ocean-700' : 'bg-sand-400';
                const checkColor = isAdult ? 'text-ocean-500' : 'text-sand-500';
                const label = isAdult ? '/ person' : '/ child';

                return (
                  <Card key={type.id} className="h-full border border-ocean-100 overflow-hidden">
                    <div className={`h-1 ${accentClass}`} />
                    <CardHeader className="text-center pb-2">
                      <CardTitle className="text-xl">{type.name} Tour</CardTitle>
                      <div className="mt-4">
                        <span className="text-4xl font-bold text-ocean-700">
                          {formatCurrency(priceDollars)}
                        </span>
                        <span className="text-ocean-400 ml-1">{label}</span>
                      </div>
                    </CardHeader>
                    <CardContent className="pt-4">
                      <ul className="space-y-3 mb-8">
                        {(type.features || [])
                          .sort((a, b) => a.sort_order - b.sort_order)
                          .map((feature, fi) => (
                            <li key={fi} className="flex items-start gap-3">
                              <CheckCircle className={`h-4 w-4 ${checkColor} shrink-0 mt-0.5`} />
                              <span className="text-sm text-ocean-600">{feature.label}</span>
                            </li>
                          ))}
                      </ul>
                      <Link href="/book" className="block">
                        <Button
                          variant={isAdult ? 'cta' : 'outline'}
                          className={isAdult ? 'w-full' : 'w-full border-ocean-300 text-ocean-700 hover:bg-ocean-50'}
                          size="lg"
                        >
                          Book {type.name} Tour
                        </Button>
                      </Link>
                    </CardContent>
                  </Card>
                );
              })
            )}
          </div>
        </div>
      </section>

      {/* How It Works */}
      <section className="py-12 sm:py-20 bg-white">
        <div className="section-container">
          <div className="text-center mb-12">
            <h2 className="text-3xl font-bold text-ocean-900 mb-4">
              How It Works
            </h2>
          </div>
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
              <div key={i} className="text-center">
                <div className="relative inline-flex items-center justify-center w-16 h-16 bg-ocean-700 text-white rounded-full mb-6">
                  <item.icon className="h-7 w-7" />
                  <span className="absolute -top-1 -right-1 w-7 h-7 bg-sand-400 text-ocean-900 rounded-full flex items-center justify-center text-xs font-bold">
                    {item.step}
                  </span>
                </div>
                <h3 className="text-lg font-semibold mb-2">{item.title}</h3>
                <p className="text-ocean-500 text-sm">{item.desc}</p>
              </div>
            ))}
          </div>
        </div>
      </section>

      {/* Private Tour CTA */}
      <section className="py-12 sm:py-16 bg-gradient-to-r from-ocean-50 to-white border-t border-ocean-100">
        <div className="section-container text-center">
          <div className="max-w-2xl mx-auto">
            <div className="inline-flex items-center gap-2 bg-ocean-100 text-ocean-700 px-4 py-2 rounded-full text-sm font-medium mb-4">
              <Sparkles className="h-4 w-4" />
              Private Experience
            </div>
            <h2 className="text-2xl sm:text-3xl font-bold text-ocean-900 mb-3">
              Want the Boat All to Yourself?
            </h2>
            <p className="text-ocean-500 mb-6 max-w-lg mx-auto">
              Book a private tour for up to 10 guests. Perfect for birthdays, anniversaries, corporate events, or just a special day on the water.
            </p>
            <Link href="/book/private-tour">
              <Button variant="cta" size="xl">
                Book a Private Tour
                <ChevronRight className="ml-2 h-5 w-5" />
              </Button>
            </Link>
          </div>
        </div>
      </section>

      {/* CTA */}
      <section className="py-12 sm:py-20 bg-hero-gradient relative overflow-hidden">
        <div className="absolute inset-0 bg-[url('https://images.unsplash.com/photo-1544551763-46a013bb70d5?w=1920&q=80')] bg-cover bg-center mix-blend-overlay opacity-20" />
        <div className="section-container relative z-10 text-center">
          <h2 className="text-3xl sm:text-4xl font-bold text-white mb-6">
            Ready for Your Adventure?
          </h2>
          <p className="text-ocean-200 max-w-2xl mx-auto mb-8">
            Spaces fill up fast. Book your transparent boat tour today and
            experience the Bahamas like never before.
          </p>
          <Link href="/book">
            <Button variant="cta" size="xl">
              Book Your Tour Now
              <ChevronRight className="ml-2 h-5 w-5" />
            </Button>
          </Link>
        </div>
      </section>
    </div>
  );
}
