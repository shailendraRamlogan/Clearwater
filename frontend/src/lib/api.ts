import axios from "axios";
import { getToken } from "@/lib/admin-auth";

const api = axios.create({
  baseURL: process.env.NEXT_PUBLIC_API_URL || "http://localhost:8000/api",
  headers: {
    "Content-Type": "application/json",
  },
  timeout: 30000,
});

// Attach admin token for /admin/ routes
api.interceptors.request.use((config) => {
  const url = config.url || "";
  if (url.startsWith("/admin")) {
    const token = getToken();
    if (token) {
      config.headers.Authorization = `Bearer ${token}`;
    }
  }
  return config;
});

api.interceptors.response.use(
  (response) => response,
  (error) => {
    if (!error.response) {
      return Promise.reject(new Error("Unable to connect. Please check your internet connection."));
    }
    const { message, errors } = error.response.data || {};
    const msg = message || "Something went wrong";
    // Return a structured object with both message and errors
    const err = new Error(msg) as Error & { errors?: Record<string, string[]>; status?: number };
    if (errors) err.errors = errors;
    err.status = error.response.status;
    return Promise.reject(err);
  }
);

export default api;
