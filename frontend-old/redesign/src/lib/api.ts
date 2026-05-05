import axios from "axios";

const api = axios.create({
  baseURL: process.env.NEXT_PUBLIC_API_URL || "https://clearwater-panel.ourea.tech/api",
  headers: { "Content-Type": "application/json" },
});

api.interceptors.response.use(
  (res) => res,
  (err) => {
    if (err.response?.status === 409) {
      const error = new Error("This time slot just filled up. Please select a different time.");
      (error as unknown as { status?: number }).status = 409;
      return Promise.reject(error);
    }
    return Promise.reject(err);
  }
);

export default api;
