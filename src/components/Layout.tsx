import { Outlet } from "react-router-dom";
import { useEffect, useState } from "react";
import Navigation from "./Navigation";
import Footer from "./Footer";
import { api, type SiteInfo } from "../lib/api";

function setFavicon(url?: string | null) {
  if (!url) return;

  let icon = document.querySelector("link[rel='icon']") as HTMLLinkElement | null;
  if (!icon) {
    icon = document.createElement("link");
    icon.rel = "icon";
    document.head.appendChild(icon);
  }
  icon.href = url;

  let apple = document.querySelector("link[rel='apple-touch-icon']") as HTMLLinkElement | null;
  if (!apple) {
    apple = document.createElement("link");
    apple.rel = "apple-touch-icon";
    document.head.appendChild(apple);
  }
  apple.href = url;
}

export default function Layout() {
  const [siteInfo, setSiteInfo] = useState<SiteInfo | null>(null);

  useEffect(() => {
    async function loadSiteInfo() {
      try {
        const data = await api.siteInfo(1);
        setSiteInfo(data);
      } catch (err) {
        console.error("Failed to load site info for layout:", err);
      }
    }

    loadSiteInfo();
  }, []);

  useEffect(() => {
    if (siteInfo?.favicon_url) {
      setFavicon(siteInfo.favicon_url);
    }
  }, [siteInfo]);

  return (
    <div className="min-h-screen bg-white text-slate-900">
      <Navigation />
      <Outlet />
      <Footer />
    </div>
  );
}