const PUBLIC_ORIGIN =
  import.meta.env.VITE_PUBLIC_ORIGIN?.trim() ||
  window.location.origin;

function encodePath(path: string): string {
  return path
    .split("/")
    .map((part) => encodeURIComponent(part))
    .join("/");
}

export function toPublicUrl(path?: string | null): string | null {
  if (!path) return null;

  let value = String(path).trim();
  if (!value) return null;

  value = value.replace(/\\/g, "/");

  // already full URL
  if (/^https?:\/\//i.test(value)) {
    return value;
  }

  value = value.replace(/^\/+/, "");

  // root images
  if (value.startsWith("images/")) {
    return `${PUBLIC_ORIGIN}/${encodePath(value)}`;
  }

  // already admin prefixed
  if (value.startsWith("admin/")) {
    return `${PUBLIC_ORIGIN}/${encodePath(value)}`;
  }

  // upload folder
  if (value.startsWith("upload/")) {
    return `${PUBLIC_ORIGIN}/admin/${encodePath(value)}`;
  }

  // agent images
  if (value.startsWith("ragents/")) {
    return `${PUBLIC_ORIGIN}/admin/${encodePath(value)}`;
  }

  // property folder
  if (value.startsWith("property/")) {
    return `${PUBLIC_ORIGIN}/admin/${encodePath(value)}`;
  }

  // plain agent file name
  if (/^agent_[^/]+\.(jpg|jpeg|png|webp|gif)$/i.test(value)) {
    return `${PUBLIC_ORIGIN}/admin/ragents/${encodePath(value)}`;
  }

  // plain generic image fallback
  if (/^[^/]+\.(jpg|jpeg|png|webp|gif)$/i.test(value)) {
    return `${PUBLIC_ORIGIN}/admin/property/${encodePath(value)}`;
  }

  return `${PUBLIC_ORIGIN}/${encodePath(value)}`;
}