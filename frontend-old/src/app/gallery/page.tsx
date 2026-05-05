"use client";

import { useState, useCallback, useEffect } from "react";
import { X, ChevronLeft, ChevronRight } from "lucide-react";

interface GalleryImage {
  id: string;
  src: string;
  alt: string;
  sort_order: number;
}

// Each batch of 20 tiles perfectly into a 4-col × 7-row grid (28 cells)
// 1 feature (2×2) + 3 wide (2×1) + 2 tall (1×2) + 14 normal = 20 items, 28 cells
const GRID_PATTERN = [
  { col: "1/3", row: "1/3" },  // feature
  { col: "3/4", row: "1/2" },  // normal
  { col: "4/5", row: "1/2" },  // normal
  { col: "3/4", row: "2/4" },  // tall
  { col: "4/5", row: "2/3" },  // normal
  { col: "1/2", row: "3/4" },  // normal
  { col: "2/3", row: "3/4" },  // normal
  { col: "4/5", row: "3/4" },  // normal
  { col: "1/2", row: "4/6" },  // tall
  { col: "2/4", row: "4/5" },  // wide
  { col: "4/5", row: "4/5" },  // normal
  { col: "2/3", row: "5/6" },  // normal
  { col: "3/4", row: "5/6" },  // normal
  { col: "4/5", row: "5/6" },  // normal
  { col: "1/2", row: "6/7" },  // normal
  { col: "2/3", row: "6/7" },  // normal
  { col: "3/5", row: "6/7" },  // wide
  { col: "1/2", row: "7/8" },  // normal
  { col: "2/4", row: "7/8" },  // wide
  { col: "4/5", row: "7/8" },  // normal
];

const ROWS_PER_BATCH = 7;
const PER_PAGE = 20;

export default function GalleryPage() {
  const [allImages, setAllImages] = useState<GalleryImage[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [visibleCount, setVisibleCount] = useState(PER_PAGE);
  const [lightboxIndex, setLightboxIndex] = useState<number | null>(null);

  useEffect(() => {
    fetch(`${process.env.NEXT_PUBLIC_API_URL}/gallery-photos`)
      .then((res) => {
        if (!res.ok) throw new Error("Failed to load gallery");
        return res.json();
      })
      .then((data) => setAllImages(data))
      .catch((err) => setError(err.message))
      .finally(() => setLoading(false));
  }, []);

  const visibleImages = allImages.slice(0, visibleCount);
  const hasMore = visibleCount < allImages.length;

  const openLightbox = (index: number) => setLightboxIndex(index);
  const closeLightbox = () => setLightboxIndex(null);

  const goNext = useCallback(() => {
    if (lightboxIndex === null) return;
    setLightboxIndex((lightboxIndex + 1) % visibleImages.length);
  }, [lightboxIndex, visibleImages.length]);

  const goPrev = useCallback(() => {
    if (lightboxIndex === null) return;
    setLightboxIndex((lightboxIndex - 1 + visibleImages.length) % visibleImages.length);
  }, [lightboxIndex, visibleImages.length]);

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

  const totalBatches = Math.ceil(visibleCount / PER_PAGE);
  const totalRows = totalBatches * ROWS_PER_BATCH;

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

      {/* Loading State */}
      {loading && (
        <section className="px-4 sm:px-6 pb-16 max-w-7xl mx-auto">
          <div className="grid grid-cols-2 sm:grid-cols-4 gap-4">
            {Array.from({ length: 8 }).map((_, i) => (
              <div
                key={i}
                className="aspect-[3/2] bg-ocean-100 animate-pulse rounded-lg"
              />
            ))}
          </div>
        </section>
      )}

      {/* Error State */}
      {error && !loading && (
        <section className="px-4 sm:px-6 pb-16 max-w-7xl mx-auto text-center">
          <p className="text-ocean-500">
            Unable to load gallery photos. Please try again later.
          </p>
        </section>
      )}

      {/* Empty State */}
      {!loading && !error && allImages.length === 0 && (
        <section className="px-4 sm:px-6 pb-16 max-w-7xl mx-auto text-center">
          <p className="text-ocean-400">
            No gallery photos yet. Check back soon!
          </p>
        </section>
      )}

      {/* Mosaic Grid */}
      {!loading && !error && allImages.length > 0 && (
        <section className="px-4 sm:px-6 pb-8 sm:pb-16 max-w-7xl mx-auto">
          {/* Desktop: 4-col mosaic */}
          <div
            className="hidden sm:grid gap-4"
            style={{
              gridTemplateColumns: "repeat(4, 1fr)",
              gridTemplateRows: `repeat(${totalRows}, 180px)`,
            }}
          >
            {visibleImages.map((img, i) => {
              const batchIndex = Math.floor(i / PER_PAGE);
              const itemInBatch = i % PER_PAGE;
              const pattern = GRID_PATTERN[itemInBatch];
              const rowOffset = batchIndex * ROWS_PER_BATCH;

              const colStart = parseInt(pattern.col.split("/")[0]);
              const colEnd = parseInt(pattern.col.split("/")[1]);
              const rowStart = parseInt(pattern.row.split("/")[0]) + rowOffset;
              const rowEnd = parseInt(pattern.row.split("/")[1]) + rowOffset;

              return (
                <button
                  key={img.id}
                  onClick={() => openLightbox(i)}
                  className="overflow-hidden rounded-lg focus:outline-none focus-visible:ring-2 focus-visible:ring-ocean-700"
                  style={{
                    gridColumn: `${colStart}/${colEnd}`,
                    gridRow: `${rowStart}/${rowEnd}`,
                  }}
                >
                  <img
                    src={img.src}
                    alt={img.alt}
                    loading={i < 8 ? "eager" : "lazy"}
                    className="w-full h-full object-cover transition-opacity hover:opacity-85"
                  />
                </button>
              );
            })}
          </div>

          {/* Mobile: 2-col simple grid */}
          <div className="grid grid-cols-2 gap-4 sm:hidden">
            {visibleImages.map((img, i) => (
              <button
                key={img.id}
                onClick={() => openLightbox(i)}
                className="aspect-[3/2] overflow-hidden rounded-lg focus:outline-none focus-visible:ring-2 focus-visible:ring-ocean-700"
              >
                <img
                  src={img.src}
                  alt={img.alt}
                  loading={i < 4 ? "eager" : "lazy"}
                  className="w-full h-full object-cover transition-opacity hover:opacity-85"
                />
              </button>
            ))}
          </div>
        </section>
      )}

      {/* Load More */}
      {hasMore && (
        <div className="flex justify-center pb-16 sm:pb-24">
          <button
            onClick={() => setVisibleCount((prev) => Math.min(prev + PER_PAGE, allImages.length))}
            className="px-8 py-3 border-2 border-ocean-200 text-ocean-700 font-medium rounded-lg hover:bg-ocean-50 hover:border-ocean-300 transition-colors"
          >
            Load More
          </button>
        </div>
      )}

      {/* Lightbox */}
      {lightboxIndex !== null && visibleImages[lightboxIndex] && (
        <div
          className="fixed inset-0 z-50 bg-ocean-950/95 flex items-center justify-center"
          onClick={closeLightbox}
        >
          <button
            onClick={closeLightbox}
            className="absolute top-4 right-4 text-white/80 hover:text-white transition-colors z-10"
            aria-label="Close"
          >
            <X className="h-8 w-8" />
          </button>

          <button
            onClick={(e) => { e.stopPropagation(); goPrev(); }}
            className="absolute left-4 text-white/80 hover:text-white transition-colors z-10 p-2"
            aria-label="Previous"
          >
            <ChevronLeft className="h-10 w-10" />
          </button>

          <div
            className="max-w-5xl max-h-[85vh] mx-4"
            onClick={(e) => e.stopPropagation()}
          >
            <img
              src={visibleImages[lightboxIndex].src.replace("w=600&h=400", "w=1400&h=933")}
              alt={visibleImages[lightboxIndex].alt}
              className="max-h-[85vh] w-auto max-w-full object-contain rounded"
            />
            <p className="text-center text-white/70 text-sm mt-3">
              {lightboxIndex + 1} / {visibleImages.length} — {visibleImages[lightboxIndex].alt}
            </p>
          </div>

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
