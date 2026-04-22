"use client";

import { useState, useCallback, useEffect } from "react";
import { X, ChevronLeft, ChevronRight } from "lucide-react";

interface GalleryImage {
  src: string;
  alt: string;
  span?: "tall" | "wide" | "normal";
}

const images: GalleryImage[] = [
  { src: "https://images.unsplash.com/photo-1544551763-46a013bb70d5?w=800&q=80", alt: "Crystal clear turquoise waters from above", span: "wide" },
  { src: "https://images.unsplash.com/photo-1569263979104-865ab7cd8d13?w=600&q=80", alt: "Luxury yacht cruising at sunset", span: "tall" },
  { src: "https://images.unsplash.com/photo-1548574505-5e239809ee19?w=600&q=80", alt: "White boat anchored in shallow water" },
  { src: "https://images.unsplash.com/photo-1506905925346-21bda4d32df4?w=600&q=80", alt: "Aerial view of tropical coastline" },
  { src: "https://images.unsplash.com/photo-1540202404-a2f29016b523?w=600&q=80", alt: "Catamaran sailing in blue waters", span: "tall" },
  { src: "https://images.unsplash.com/photo-1559599238-308793637427?w=800&q=80", alt: "Snorkeler exploring coral reef", span: "wide" },
  { src: "https://images.unsplash.com/photo-1586105251261-72a756497a11?w=600&q=80", alt: "Tropical island with white sand beach" },
  { src: "https://images.unsplash.com/photo-1559827260-dc66d52bef19?w=600&q=80", alt: "Underwater view of boat hull" },
  { src: "https://images.unsplash.com/photo-1507525428034-b723cf961d3e?w=600&q=80", alt: "Pristine beach with palm trees", span: "wide" },
  { src: "https://images.unsplash.com/photo-1476514525535-07fb3b4ae5f1?w=600&q=80", alt: "Kayaking in clear blue water" },
  { src: "https://images.unsplash.com/photo-1520483601560-389dff434fdf?w=600&q=80", alt: "Tourist boat approaching tropical island", span: "tall" },
  { src: "https://images.unsplash.com/photo-1534575673532-6b4ce5b72b95?w=600&q=80", alt: "Sunset over the ocean horizon" },
  { src: "https://images.unsplash.com/photo-1604882357326-5765a1a3b1e4?w=800&q=80", alt: "Group of friends on a boat trip", span: "wide" },
  { src: "https://images.unsplash.com/photo-1590523277543-a94d2e4eb00b?w=600&q=80", alt: "Underwater coral and tropical fish" },
  { src: "https://images.unsplash.com/photo-1559128010-7c1ad6e1b6a5?w=600&q=80", alt: "Speedboat cutting through waves" },
  { src: "https://images.unsplash.com/photo-1519046904884-53103b34b206?w=600&q=80", alt: "Beach chairs overlooking turquoise sea", span: "tall" },
];

export default function GalleryPage() {
  const [lightboxIndex, setLightboxIndex] = useState<number | null>(null);

  const openLightbox = (index: number) => setLightboxIndex(index);
  const closeLightbox = () => setLightboxIndex(null);

  const goNext = useCallback(() => {
    if (lightboxIndex === null) return;
    setLightboxIndex((lightboxIndex + 1) % images.length);
  }, [lightboxIndex]);

  const goPrev = useCallback(() => {
    if (lightboxIndex === null) return;
    setLightboxIndex((lightboxIndex - 1 + images.length) % images.length);
  }, [lightboxIndex]);

  useEffect(() => {
    if (lightboxIndex === null) return;
    const handleKey = (e: KeyboardEvent) => {
      if (e.key === "Escape") closeLightbox();
      if (e.key === "ArrowRight") goNext();
      if (e.key === "ArrowLeft") goPrev();
    };
    document.addEventListener("keydown", handleKey);
    document.body.style.overflow = "hidden";
    return () => {
      document.removeEventListener("keydown", handleKey);
      document.body.style.overflow = "";
    };
  }, [lightboxIndex, goNext, goPrev]);

  return (
    <div className="min-h-screen bg-white">
      {/* Header */}
      <section className="pt-24 pb-12 sm:pt-32 sm:pb-16 text-center">
        <h1 className="text-3xl sm:text-4xl font-bold text-ocean-900 mb-4">
          Gallery
        </h1>
        <p className="text-ocean-500 max-w-xl mx-auto">
          Moments captured on our tours. Click any photo to view the full slideshow.
        </p>
      </section>

      {/* Mosaic Grid */}
      <section className="px-4 sm:px-6 pb-16 sm:pb-24 max-w-7xl mx-auto">
        <div className="columns-1 sm:columns-2 lg:columns-3 gap-4 space-y-4">
          {images.map((img, i) => (
            <button
              key={i}
              onClick={() => openLightbox(i)}
              className="block w-full break-inside-avoid group focus:outline-none focus-visible:ring-2 focus-visible:ring-ocean-400 rounded-lg overflow-hidden"
            >
              <img
                src={img.src}
                alt={img.alt}
                loading="lazy"
                className={`w-full object-cover transition-opacity group-hover:opacity-90 ${
                  img.span === "tall" ? "h-[420px] sm:h-[480px]" : "h-[280px] sm:h-[320px]"
                }`}
              />
              <div className="opacity-0 group-hover:opacity-100 transition-opacity bg-ocean-950/60 text-white text-sm px-3 py-2 text-left">
                {img.alt}
              </div>
            </button>
          ))}
        </div>
      </section>

      {/* Lightbox */}
      {lightboxIndex !== null && (
        <div
          className="fixed inset-0 z-50 bg-ocean-950/95 flex items-center justify-center"
          onClick={closeLightbox}
        >
          {/* Close */}
          <button
            onClick={closeLightbox}
            className="absolute top-4 right-4 text-white/80 hover:text-white transition-colors z-10"
            aria-label="Close"
          >
            <X className="h-8 w-8" />
          </button>

          {/* Prev */}
          <button
            onClick={(e) => { e.stopPropagation(); goPrev(); }}
            className="absolute left-4 text-white/80 hover:text-white transition-colors z-10 p-2"
            aria-label="Previous"
          >
            <ChevronLeft className="h-10 w-10" />
          </button>

          {/* Image */}
          <div
            className="max-w-5xl max-h-[85vh] mx-4"
            onClick={(e) => e.stopPropagation()}
          >
            <img
              src={images[lightboxIndex].src.replace("w=600", "w=1400").replace("w=800", "w=1400")}
              alt={images[lightboxIndex].alt}
              className="max-h-[85vh] w-auto max-w-full object-contain rounded"
            />
            <p className="text-center text-white/70 text-sm mt-3">
              {lightboxIndex + 1} / {images.length} — {images[lightboxIndex].alt}
            </p>
          </div>

          {/* Next */}
          <button
            onClick={(e) => { e.stopPropagation(); goNext(); }}
            className="absolute right-4 text-white/80 hover:text-white transition-colors z-10 p-2"
            aria-label="Next"
          >
            <ChevronRight className="h-10 w-10" />
          </button>
        </div>
      )}
    </div>
  );
}
