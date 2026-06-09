import { defineConfig } from "vite";
import react from "@vitejs/plugin-react";

export default defineConfig({
  plugins: [react()],
  base: "/",
  server: {
    proxy: {
      "/api/v1": { target: "http://localhost", changeOrigin: true },
      "/images": { target: "http://localhost", changeOrigin: true },
      "/assets": { target: "http://localhost", changeOrigin: true },
      "/uploads": { target: "http://localhost", changeOrigin: true },
      "/admin": { target: "http://localhost", changeOrigin: true },
    },
  },
});