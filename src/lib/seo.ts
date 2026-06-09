import type { SeoSettings, SiteInfo } from "./api";

function setMetaTag(name: string, content: string) {
  let tag = document.querySelector(`meta[name="${name}"]`) as HTMLMetaElement | null;

  if (!tag) {
    tag = document.createElement("meta");
    tag.setAttribute("name", name);
    document.head.appendChild(tag);
  }

  tag.setAttribute("content", content);
}

function setFavicon(url?: string | null) {
  if (!url) return;

  let link = document.querySelector("link[rel='icon']") as HTMLLinkElement | null;
  if (!link) {
    link = document.createElement("link");
    link.rel = "icon";
    document.head.appendChild(link);
  }
  link.href = url;

  let apple = document.querySelector("link[rel='apple-touch-icon']") as HTMLLinkElement | null;
  if (!apple) {
    apple = document.createElement("link");
    apple.rel = "apple-touch-icon";
    document.head.appendChild(apple);
  }
  apple.href = url;
}

export function applySeo(
  seo: SeoSettings | null | undefined,
  siteInfo?: SiteInfo | null,
  fallbackTitle = "PropertySync Skyline",
  fallbackDescription = "Modern real estate website"
) {
  document.title = seo?.seo_title?.trim() || fallbackTitle;

  setMetaTag("description", seo?.seo_description?.trim() || fallbackDescription);

  if (seo?.seo_keywords) {
    setMetaTag("keywords", seo.seo_keywords);
  }

  if (seo?.seo_author) {
    setMetaTag("author", seo.seo_author);
  }

  if (siteInfo?.favicon_url) {
    setFavicon(siteInfo.favicon_url);
  }
}