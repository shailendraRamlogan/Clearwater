"use client";

import { useState, useCallback, useEffect } from "react";
import { X, ChevronLeft, ChevronRight } from "lucide-react";

interface GalleryImage {
  src: string;
  alt: string;
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

const allImages: GalleryImage[] = [
  { src: "https://images.unsplash.com/photo-1544551763-46a013bb70d5?w=600&h=400&fit=crop", alt: "Crystal clear turquoise waters from above" },
  { src: "https://images.unsplash.com/photo-1569263979104-865ab7cd8d13?w=600&h=400&fit=crop", alt: "Luxury yacht cruising at sunset" },
  { src: "https://images.unsplash.com/photo-1548574505-5e239809ee19?w=600&h=400&fit=crop", alt: "White boat anchored in shallow water" },
  { src: "https://images.unsplash.com/photo-1506905925346-21bda4d32df4?w=600&h=400&fit=crop", alt: "Aerial view of tropical coastline" },
  { src: "https://images.unsplash.com/photo-1540202404-a2f29016b523?w=600&h=400&fit=crop", alt: "Catamaran sailing in blue waters" },
  { src: "https://images.unsplash.com/photo-1559599238-308793637427?w=600&h=400&fit=crop", alt: "Snorkeler exploring coral reef" },
  { src: "https://images.unsplash.com/photo-1586105251261-72a756497a11?w=600&h=400&fit=crop", alt: "Tropical island with white sand beach" },
  { src: "https://images.unsplash.com/photo-1559827260-dc66d52bef19?w=600&h=400&fit=crop", alt: "Underwater view of boat hull" },
  { src: "https://images.unsplash.com/photo-1507525428034-b723cf961d3e?w=600&h=400&fit=crop", alt: "Pristine beach with turquoise water" },
  { src: "https://images.unsplash.com/photo-1476514525535-07fb3b4ae5f1?w=600&h=400&fit=crop", alt: "Kayaking in crystal clear water" },
  { src: "https://images.unsplash.com/photo-1520483601560-389dff434fdf?w=600&h=400&fit=crop", alt: "Tourist boat approaching tropical island" },
  { src: "https://images.unsplash.com/photo-1534575673532-6b4ce5b72b95?w=600&h=400&fit=crop", alt: "Dramatic sunset over the ocean" },
  { src: "https://images.unsplash.com/photo-1604882357326-5765a1a3b1e4?w=600&h=400&fit=crop", alt: "Group of friends on a boat trip" },
  { src: "https://images.unsplash.com/photo-1590523277543-a94d2e4eb00b?w=600&h=400&fit=crop", alt: "Underwater coral reef with tropical fish" },
  { src: "https://images.unsplash.com/photo-1559128010-7c1ad6e1b6a5?w=600&h=400&fit=crop", alt: "Speedboat cutting through ocean waves" },
  { src: "https://images.unsplash.com/photo-1519046904884-53103b34b206?w=600&h=400&fit=crop", alt: "Beach chairs overlooking turquoise sea" },
  { src: "https://images.unsplash.com/photo-1551698618-1dfe5d97d256?w=600&h=400&fit=crop", alt: "Tropical coral reef from above" },
  { src: "https://images.unsplash.com/photo-1546026423-cc4642628d2b?w=600&h=400&fit=crop", alt: "Aerial view of boat in shallow water" },
  { src: "https://images.unsplash.com/photo-1582967788606-a171c1080cb0?w=600&h=400&fit=crop", alt: "Dolphins swimming alongside a boat" },
  { src: "https://images.unsplash.com/photo-1502904550040-7534597429ae?w=600&h=400&fit=crop", alt: "Beautiful tropical beach paradise" },
  { src: "https://images.unsplash.com/photo-1570077188670-e3a8d69ac5ff?w=600&h=400&fit=crop", alt: "Aerial view of reef and sandbar" },
  { src: "https://images.unsplash.com/photo-1558981806-ec527fa84c39?w=600&h=400&fit=crop", alt: "Tropical fish and coral underwater" },
  { src: "https://images.unsplash.com/photo-1530053969600-caed2596d242?w=600&h=400&fit=crop", alt: "Stunning aerial view of tropical island" },
  { src: "https://images.unsplash.com/photo-1504681869696-d977211a5f4c?w=600&h=400&fit=crop", alt: "Snorkeling in crystal clear water" },
  { src: "https://images.unsplash.com/photo-1580308226145-044c1e0e3b7c?w=600&h=400&fit=crop", alt: "Yacht anchored off tropical island" },
  { src: "https://images.unsplash.com/photo-1578922746465-3a80a228f223?w=600&h=400&fit=crop", alt: "Underwater school of tropical fish" },
  { src: "https://images.unsplash.com/photo-1510414842594-a61c69b5ae57?w=600&h=400&fit=crop", alt: "Catamaran sailing at golden hour" },
  { src: "https://images.unsplash.com/photo-1506929562872-bb421503ef21?w=600&h=400&fit=crop", alt: "White sand beach and palm trees" },
  { src: "https://images.unsplash.com/photo-1559825481-12a05cc00344?w=600&h=400&fit=crop", alt: "Drone view of boat in blue water" },
  { src: "https://images.unsplash.com/photo-1568867716055-31d31c82e8e3?w=600&h=400&fit=crop", alt: "Sailing yacht on open ocean" },
  { src: "https://images.unsplash.com/photo-1580019542155-247062e19ce4?w=600&h=400&fit=crop", alt: "Tour boat cruising through islands" },
  { src: "https://images.unsplash.com/photo-1596394516093-501ba68a0ba6?w=600&h=400&fit=crop", alt: "Aerial view of sandbar in the Bahamas" },
  { src: "https://images.unsplash.com/photo-1569263970158-ea3a6eb4b3d0?w=600&h=400&fit=crop", alt: "Clear water meeting white sand beach" },
  { src: "https://images.unsplash.com/photo-1544200181-3ea25f787834?w=600&h=400&fit=crop", alt: "Snorkeler with tropical fish" },
  { src: "https://images.unsplash.com/photo-1590523741831-ab7e8b8f9c7f?w=600&h=400&fit=crop", alt: "Glass bottom boat tour experience" },
  { src: "https://images.unsplash.com/photo-1528127269322-539152f5ae1c?w=600&h=400&fit=crop", alt: "Luxury motor yacht on calm waters" },
  { src: "https://images.unsplash.com/photo-1544568100-847a948585b9?w=600&h=400&fit=crop", alt: "Aerial view of tropical atoll" },
  { src: "https://images.unsplash.com/photo-1502781252888-9143ba7f074e?w=600&h=400&fit=crop", alt: "Stunning tropical ocean sunset" },
  { src: "https://images.unsplash.com/photo-1549880338-65ddcdfd017b?w=600&h=400&fit=crop", alt: "Boat heading toward tropical island" },
  { src: "https://images.unsplash.com/photo-1573843981267-be1999ff37cd?w=600&h=400&fit=crop", alt: "Colorful coral reef underwater" },
];

const PER_PAGE = 20;

export default function GalleryPage() {
  const [visibleCount, setVisibleCount] = useState(PER_PAGE);
  const [lightboxIndex, setLightboxIndex] = useState<number | null>(null);

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

      {/* Mosaic Grid */}
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
                key={i}
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
              key={i}
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
      {lightboxIndex !== null && (
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
