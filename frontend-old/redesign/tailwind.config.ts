import type { Config } from "tailwindcss";

const config: Config = {
  content: [
    "./src/pages/**/*.{js,ts,jsx,tsx,mdx}",
    "./src/components/**/*.{js,ts,jsx,tsx,mdx}",
    "./src/app/**/*.{js,ts,jsx,tsx,mdx}",
  ],
  theme: {
    extend: {
      colors: {
        brand: {
          50: "#e6f7f8",
          100: "#cceff1",
          200: "#99dfe3",
          300: "#66cfd5",
          400: "#33bfc7",
          500: "#00ABB3",
          600: "#009A9F",
          700: "#00636C",
          800: "#00484E",
          900: "#003038",
          950: "#001820",
        },
        gold: {
          50: "#fff9eb",
          100: "#fef0c7",
          200: "#fee08a",
          300: "#fdcf4d",
          400: "#FFB53C",
          500: "#FF9D2E",
          600: "#e68a1a",
          700: "#b36b14",
          800: "#804c0e",
          900: "#4d2e09",
        },
        sand: {
          50: "#fefcf3",
          100: "#fdf6e1",
          200: "#fbebc0",
          300: "#f8de9a",
          400: "#f5cf6d",
          500: "#f0be46",
        },
        sea: {
          50: "#f0fafb",
          100: "#d6f2f4",
          200: "#ade5e9",
          300: "#84d8de",
          400: "#5bcbd3",
          500: "#32bec8",
          600: "#2898a0",
          700: "#1e7279",
          800: "#144c51",
          900: "#0a2628",
        },
      },
      fontFamily: {
        sans: ["DM Sans", "system-ui", "sans-serif"],
        display: ["DM Serif Display", "Georgia", "serif"],
        mono: ["JetBrains Mono", "monospace"],
      },
      backgroundImage: {
        "hero-gradient":
          "linear-gradient(180deg, rgba(0,99,108,0.3) 0%, rgba(0,72,78,0.6) 50%, rgba(0,48,56,0.85) 100%)",
      },
      maxWidth: {
        "8xl": "88rem",
      },
      animation: {
        "fade-up": "fadeUp 0.6s ease-out forwards",
        "fade-in": "fadeIn 0.5s ease-out forwards",
        "slide-in": "slideIn 0.4s ease-out forwards",
      },
      keyframes: {
        fadeUp: {
          from: { opacity: "0", transform: "translateY(20px)" },
          to: { opacity: "1", transform: "translateY(0)" },
        },
        fadeIn: {
          from: { opacity: "0" },
          to: { opacity: "1" },
        },
        slideIn: {
          from: { opacity: "0", transform: "translateX(-10px)" },
          to: { opacity: "1", transform: "translateX(0)" },
        },
      },
    },
  },
  plugins: [],
};
export default config;
