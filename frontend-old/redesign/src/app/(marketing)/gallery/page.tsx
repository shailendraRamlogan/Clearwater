"use client";

import { useState, useCallback, useEffect } from "react";
import { X, ChevronLeft, ChevronRight } from "lucide-react";

interface GalleryImage {
  id: string;
  src: string;
  alt: string;
  cat: string;
}

const PHOTOS: GalleryImage[] = [
  { id: "1", src: "https://images.unsplash.com/photo-1544551763-46a013bb70d5?w=600&h=400&fit=crop", alt: "Crystal clear turquoise waters", cat: "Underwater" },
  { id: "2", src: "https://images.unsplash.com/photo-1569263979104-865ab7cd8d13?w=600&h=400&fit=crop", alt: "Sunset cruise", cat: "Sunset" },
  { id: "3", src: "https://images.unsplash.com/photo-1548574505-5e239809ee19?w=600&h=400&fit=crop", alt: "Boat in shallow water", cat: "On Deck" },
  { id: "4", src: "https://images.unsplash.com/photo-1506905925346-21bda4d32df4?w=600&h=400&fit=crop", alt: "Aerial coastline", cat: "On Deck" },
  { id: "5", src: "https://images.unsplash.com/photo-1540202404-a2f29016b523?w=600&h=400&fit=crop", alt: "Catamaran sailing", cat: "On Deck" },
  { id: "6", src: "https://images.unsplash.com/photo-1559599238-308793637427?w=600&h=400&fit=crop", alt: "Snorkeler on reef", cat: "Underwater" },
  { id: "7", src: "https://images.unsplash.com/photo-1586105251261-72a756497a11?w=600&h=400&fit=crop", alt: "Tropical island", cat: "On Deck" },
  { id: "8", src: "https://images.unsplash.com/photo-1559827260-dc66d52bef19?w=600&h=400&fit=crop", alt: "Underwater hull view", cat: "Underwater" },
  { id: "9", src: "https://images.unsplash.com/photo-1507525428034-b723cf961d3e?w=600&h=400&fit=crop", alt: "Pristine beach", cat: "On Deck" },
  { id: "10", src: "https://images.unsplash.com/photo-1476514525535-07fb3b4ae5f1?w=600&h=400&fit=crop", alt: "Kayaking", cat: "Wildlife" },
  { id: "11", src: "https://images.unsplash.com/photo-1544551763-77ef8d697d9e?w=600&h=400&fit=crop", alt: "Marine life", cat: "Wildlife" },
  { id: "12", src: "https://images.unsplash.com/photo-1596394516093-501ba68a0ba6?w=600&h=400&fit=crop", alt: "Tropical fish", cat: "Underwater" },
  { id: "13", src: "https://images.unsplash.com/photo-1590559899731-a382839e5549?w=600&h=400&fit=crop", alt: "Sunset on water", cat: "Sunset" },
  { id: "14", src: "https://images.unsplash.com/photo-1507525428034-b723cf961d3e?w=600&h=400&fit=crop", alt: "Beach", cat: "On Deck" },
];

const CATEGORIES = ["All", "On Deck", "Underwater", "Sunset", "Wildlife"];

export default function GalleryPage() {
  const [filter, setFilter] = useState("All");
  const [lightbox, setLightbox] = useState<GalleryImage | null>(null);
  const [lightboxIndex, setLightboxIndex] = useState<number>(0);

  const visible = filter === "All" ? PHOTOS : PHOTOS.filter((p) => p.cat === filter);

  const openLightbox = (photo: GalleryImage, index: number) => {
    setLightbox(photo);
    setLightboxIndex(visible.indexOf(photo));
  };

  const goNext = useCallback(() => {
    setLightboxIndex((prev) => (prev + 1) % visible.length);
    setLightbox(visible[(lightboxIndex + 1) % visible.length]);
  }, [visible, lightboxIndex]);

  const goPrev = useCallback(() => {
    setLightboxIndex((prev) => (prev - 1 + visible.length) % visible.length);
    setLightbox(visible[(lightboxIndex - 1 + visible.length) % visible.length]);
  }, [visible, lightboxIndex]);

  useEffect(() => {
    if (!lightbox) return;
    const handleKey = (e: KeyboardEvent) => {
      if (e.key === "Escape") setLightbox(null);
      if (e.key === "ArrowRight") goNext();
      if (e.key === "ArrowLeft") goPrev();
    };
    document.addEventListener("keydown", handleKey);
    document.body.style.overflow = "hidden";
    return () => {
      document.removeEventListener("keydown", handleKey);
      document.body.style.overflow = "";
    };
  }, [lightbox, goNext, goPrev]);

  return (
    <div className="bg-sand-50 pt-16 lg:pt-20">
      {/* ─── HEADER ─── */}
      <section className="px-6 lg:px-20 pt-10 lg:pt-16 pb-6 lg:pb-8">
        <div className="max-w-7xl mx-auto flex flex-col lg:flex-row lg:items-end lg:justify-between gap-6">
          <div>
            <div className="flex items-center gap-3 mb-3">
              <span className="w-6 h-px bg-brand-500" />
              <span className="text-[11px] font-semibold tracking-[0.28em] text-brand-600 uppercase">
                The Gallery
              </span>
              <span className="w-6 h-px bg-brand-500" />
            </div>
            <h1 className="font-display text-4xl sm:text-5xl lg:text-[5.5rem] leading-[0.95] tracking-tight text-brand-900">
              <em className="text-brand-600">Moments</em>
              <br />
              on the water.
            </h1>
          </div>
          <p className="text-sm text-brand-900/60 max-w-xs leading-relaxed lg:pb-3">
            Real photos from Clear Boat Bahamas tours. Click any image to view full
            size.
          </p>
        </div>
      </section>

      {/* ─── FILTER ─── */}
      <section className="px-6 lg:px-20 pb-8">
        <div className="max-w-7xl mx-auto flex flex-wrap items-center gap-2 border-b border-brand-900/10 pb-4">
          {CATEGORIES.map((c) => (
            <button
              key={c}
              onClick={() => setFilter(c)}
              className={`px-4 py-2 rounded-full text-sm font-medium transition-all ${
                filter === c
                  ? "bg-brand-900 text-sand-50"
                  : "bg-transparent text-brand-900 border border-brand-900/10 hover:border-brand-900/30"
              }`}
            >
              {c}
            </button>
          ))}
          <div className="flex-1" />
          <p className="text-xs text-brand-900/50">
            Showing <strong className="text-brand-900">{visible.length}</strong> of {PHOTOS.length}
          </p>
        </div>
      </section>

      {/* ─── GRID ─── */}
      <section className="px-6 lg:px-20 pb-20">
        <div className="max-w-7xl mx-auto grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-3 lg:gap-4 auto-rows-[180px] sm:auto-rows-[220px] lg:auto-rows-[260px]">
          {PHOTOS.map((photo, i) => {
            const isVisible = visible.includes(photo);
            return (
              <div
                key={photo.id}
                onClick={() => isVisible && openLightbox(photo, i)}
                className={`relative rounded-lg overflow-hidden group cursor-pointer transition-all duration-300 ${
                  isVisible ? "opacity-100" : "opacity-10 grayscale"
                }`}
              >
                <img
                  src={photo.src}
                  alt={photo.alt}
                  className="w-full h-full object-cover transition-transform duration-300 group-hover:scale-105"
                  loading={i < 8 ? "eager" : "lazy"}
                />
                <div className="absolute inset-0 bg-gradient-to-t from-brand-950/70 via-transparent to-transparent" />
                <div className="absolute inset-x-0 bottom-0 px-3 pb-3 flex items-end justify-between text-white text-[11px] tracking-wide">
                  <span>{photo.alt}</span>
                  <span className="text-white/60">· {photo.cat}</span>
                </div>
              </div>
            );
          })}
        </div>
      </section>

      {/* ─── LIGHTBOX ─── */}
      {lightbox && (
        <div
          className="fixed inset-0 z-50 bg-brand-950/92 flex items-center justify-center p-6 lg:p-10 cursor-zoom-out"
          onClick={() => setLightbox(null)}
        >
          <button
            onClick={() => setLightbox(null)}
            className="absolute top-5 right-5 text-white/70 hover:text-white"
          >
            <X className="w-6 h-6" />
          </button>

          <button
            onClick={(e) => {
              e.stopPropagation();
              goPrev();
            }}
            className="absolute left-5 text-white/70 hover:text-white p-2"
          >
            <ChevronLeft className="w-10 h-10" />
          </button>

          <div className="relative w-full max-w-4xl aspect-video rounded overflow-hidden">
            <img
              src={lightbox.src.replace("w=600&h=400", "w=1400&h=933")}
              alt={lightbox.alt}
              className="w-full h-full object-cover"
            />
          </div>

          <button
            onClick={(e) => {
              e.stopPropagation();
              goNext();
            }}
            className="absolute right-5 text-white/70 hover:text-white p-2"
          >
            <ChevronRight className="w-10 h-10" />
          </button>

          <p className="absolute bottom-6 text-white/80 text-sm">
            {lightbox.alt} · {lightbox.cat}
          </p>
        </div>
      )}
    </div>
  );
}
