import { toPublicUrl } from "./url";

export type ApiMeta = {
  page: number;
  limit: number;
  total: number;
  total_pages: number;
};

export type ApiResponse<T> = {
  status: "success" | "error";
  message?: string;
  meta?: ApiMeta;
  data?: T;
};

export type MenuItem = {
  id: string;
  name: string;
  url: string;
  visible?: string;
};

export type SeoSettings = {
  id: string;
  page: string;
  seo_title: string;
  seo_description: string;
  seo_keywords?: string | null;
  seo_author?: string | null;
};

export type SiteInfo = {
  id: string;
  company_name?: string | null;
  logo_path?: string | null;
  banner_image_path?: string | null;
  currency?: string | null;
  banner_writeup?: string | null;
  favicon_path?: string | null;
  welcome_message?: string | null;
  display_mode?: string | null;
  page_type?: string | null;
  paystack_public_key?: string | null;
  viewing_fee?: string | null;
  enable_viewing_payment?: number | string | null;

  logo_url?: string | null;
  banner_image_url?: string | null;
  favicon_url?: string | null;
};

export type FooterContent = {
  id: string;
  company_name: string;
  welcome_message: string;
  address: string;
  phone_number: string;
  email: string;
  facebook_url: string;
  instagram_url: string;
  linkedin_url: string;
  twitter_url: string;
  logo_path: string | null;
  logo_url?: string | null;
};

export type BannerSlide = {
  id: string;
  page_type: string;
  background_image_path: string | null;
  background_image_url?: string | null;
  span_text: string | null;
  heading_text: string | null;
  button_text: string | null;
  button_link: string | null;
  sort_order: string;
  is_active: string;
};

export type BannerTab = {
  id: string;
  page_type: string;
  tab_label: string;
  tab_stype: string;
  sort_order: string;
  is_active: string;
};

export type ChooseUs = {
  id: string;
  title: string;
  heading: string;
  description: string;
  is_active: string;
  updated_at?: string;
};

export type ChooseItem = {
  id: string;
  choose_id: string;
  icon_class: string;
  title: string;
  content: string;
  sort_order: string;
  is_active: string;
};

export type Agent = {
  agent_id: string;
  name: string;
  description: string;
  picture: string | null;
  picture_url?: string | null;
};

export type AgentsResponse = {
  total: number;
  agents: Agent[];
};

export type StateCountItem = {
  sid: string;
  sname: string;
  total_properties: number;
  sale_count?: number;
  rent_count?: number;
  shortlet_count?: number;
  image_path?: string | null;
  image_url?: string | null;
};

export type StateCountsResponse = {
  total_states: number;
  items: StateCountItem[];
};

export type AboutContent = {
  id?: string;
  title?: string | null;
  subtitle?: string | null;
  years_experience?: string | number | null;
  video_url?: string | null;
  content?: string | null;
  image?: string | null;
  image2?: string | null;
  image_url?: string | null;
  image2_url?: string | null;
};

export type Property = {
  pid: string;
  title: string;
  pcontent: string;
  type: string;
  stype: string;
  status: string;
  bedroom: string;
  bathroom: string;
  balcony: string;
  kitchen: string;
  toilet: string;
  size: string;
  price: string;
  location: string;
  city: string;
  state: string;
  is_featured: string;
  views: string;
  date: string;
  uid?: string;
  name?: string;
  email?: string;
  phone?: string;
  feature?: string;

  images: string[];
  map_images: string[];
};

export type PropertyListQuery = {
  page?: number;
  limit?: number;
  type?: string;
  stype?: string;
  status?: string;
  state?: string;
  city?: string;
  featured?: 0 | 1;
  q?: string;
  [key: string]: any;
};

export type PropertyDetailsResponse = {
  property: Property;
  similar_properties: Property[];
  site_info: SiteInfo;
};

const API_BASE =
  import.meta.env.VITE_API_BASE?.trim() ||
  `${window.location.origin}/api/v1`;

async function apiGet<T>(path: string): Promise<ApiResponse<T>> {
  const res = await fetch(`${API_BASE}${path}`, {
    headers: { Accept: "application/json" },
  });

  const text = await res.text();

  try {
    const json = JSON.parse(text) as ApiResponse<T>;
    if (!res.ok) throw new Error(json.message || `HTTP ${res.status}`);
    return json;
  } catch {
    throw new Error(
      `API returned non-JSON for ${path}. First 200 chars:\n${text.slice(0, 200)}`
    );
  }
}

async function apiPost<T>(path: string, body: unknown): Promise<ApiResponse<T>> {
  const res = await fetch(`${API_BASE}${path}`, {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
      Accept: "application/json",
    },
    body: JSON.stringify(body),
  });

  const text = await res.text();

  try {
    const json = JSON.parse(text) as ApiResponse<T>;
    if (!res.ok) throw new Error(json.message || `HTTP ${res.status}`);
    return json;
  } catch {
    throw new Error(
      `API returned non-JSON for ${path}. First 200 chars:\n${text.slice(0, 200)}`
    );
  }
}

function normalizeProperty(p: any): Property {
  const rawImages: string[] = Array.isArray(p.images)
    ? p.images
    : [p.pimage, p.pimage1, p.pimage2, p.pimage3, p.pimage4].filter(Boolean);

  const images = rawImages.map((x) => toPublicUrl(x)).filter(Boolean) as string[];

  const rawMapImages: string[] = Array.isArray(p.map_images)
    ? p.map_images
    : [p.mapimage, p.topmapimage, p.groundmapimage].filter(Boolean);

  const map_images = rawMapImages.map((x) => toPublicUrl(x)).filter(Boolean) as string[];

  return {
    ...p,
    images,
    map_images,
  } as Property;
}

function normalizeStateImage(item: any): string | null {
  if (!item) return null;

  const directUrl = String(item.image_url || "").trim();
  if (directUrl) {
    return directUrl;
  }

  const imagePath = String(item.image_path || "").trim();
  if (!imagePath) return null;

  if (/^https?:\/\//i.test(imagePath)) {
    return imagePath;
  }

  const clean = imagePath.replace(/\\/g, "/").replace(/^\/+/, "");

  if (clean.startsWith("images/")) {
    return `${window.location.origin}/template8/${clean}`;
  }

  return toPublicUrl(clean);
}

export const api = {
  seoSettings: async (page: string): Promise<SeoSettings | null> => {
    try {
      const json = await apiGet<SeoSettings>(
        `/seo_settings.php?page=${encodeURIComponent(page)}`
      );
      if (json.status !== "success" || !json.data) return null;
      return json.data;
    } catch {
      return null;
    }
  },

  siteInfo: async (id = 1): Promise<SiteInfo> => {
    const json = await apiGet<SiteInfo>(`/site_info.php?id=${id}`);
    if (json.status !== "success" || !json.data) {
      throw new Error(json.message || "Site info failed");
    }

    const s = json.data;

    return {
      ...s,
      company_name: s.company_name ?? null,
      logo_url: toPublicUrl(s.logo_path),
      banner_image_url: toPublicUrl(s.banner_image_path),
      favicon_url: toPublicUrl(s.favicon_path),
    };
  },

  footerContent: async (id = 1): Promise<FooterContent> => {
    const json = await apiGet<FooterContent>(`/footer_content.php?id=${id}`);
    if (json.status !== "success" || !json.data) {
      throw new Error(json.message || "Footer content failed");
    }

    const f = json.data;
    return { ...f, logo_url: toPublicUrl(f.logo_path) };
  },

  menuItems: async (): Promise<MenuItem[]> => {
    const json = await apiGet<MenuItem[]>(`/menu_items.php`);
    if (json.status !== "success") {
      throw new Error(json.message || "Menu request failed");
    }
    return json.data || [];
  },

  about: async (): Promise<AboutContent> => {
    const json = await apiGet<AboutContent>(`/about.php`);
    if (json.status !== "success" || !json.data) {
      throw new Error(json.message || "About request failed");
    }

    const row = json.data;

    return {
      ...row,
      image_url: row.image_url ? toPublicUrl(row.image_url) : null,
      image2_url: row.image2_url ? toPublicUrl(row.image2_url) : null,
    };
  },

  bannerSlides: async (pageType = "home"): Promise<BannerSlide[]> => {
    const json = await apiGet<BannerSlide[]>(
      `/banner_slides_api.php?page_type=${encodeURIComponent(pageType)}`
    );
    if (json.status !== "success") {
      throw new Error(json.message || "Banner slides failed");
    }

    return (json.data || []).map((s) => ({
      ...s,
      background_image_url: toPublicUrl(s.background_image_path),
    }));
  },

  bannerTabs: async (pageType = "home"): Promise<BannerTab[]> => {
    const json = await apiGet<BannerTab[]>(
      `/banner_tabs_api.php?page_type=${encodeURIComponent(pageType)}`
    );
    if (json.status !== "success") {
      throw new Error(json.message || "Banner tabs failed");
    }

    return json.data || [];
  },

  chooseUs: async (): Promise<ChooseUs[]> => {
    const json = await apiGet<ChooseUs[]>(`/choose_us.php`);
    if (json.status !== "success") {
      throw new Error(json.message || "Choose us failed");
    }
    return json.data || [];
  },

  chooseItems: async (chooseId: number | string): Promise<ChooseItem[]> => {
    const json = await apiGet<ChooseItem[]>(
      `/choose_items.php?choose_id=${encodeURIComponent(String(chooseId))}`
    );
    if (json.status !== "success") {
      throw new Error(json.message || "Choose items failed");
    }
    return json.data || [];
  },

  agents: async (limit = 12): Promise<AgentsResponse> => {
    const json = await apiGet<AgentsResponse>(`/agents_api.php?limit=${limit}`);
    if (json.status !== "success" || !json.data) {
      throw new Error(json.message || "Agents failed");
    }

    return {
      total: json.data.total,
      agents: (json.data.agents || []).map((a: any) => ({
        ...a,
        picture_url: toPublicUrl(a.picture),
      })),
    };
  },

  stateCounts: async (limit = 6): Promise<StateCountsResponse> => {
    const json = await apiGet<any>(`/state_counts.php?limit=${limit}`);
    if (json.status !== "success" || !json.data) {
      throw new Error(json.message || "State counts failed");
    }

    const rawItems = Array.isArray(json.data)
      ? json.data
      : Array.isArray(json.data.items)
      ? json.data.items
      : [];

    const items: StateCountItem[] = rawItems.map((s: any) => ({
      sid: String(s.sid ?? ""),
      sname: String(s.sname ?? ""),
      total_properties: Number(s.total_properties ?? 0),
      sale_count: Number(s.sale_count ?? 0),
      rent_count: Number(s.rent_count ?? 0),
      shortlet_count: Number(s.shortlet_count ?? 0),
      image_path: s.image_path ?? null,
      image_url: normalizeStateImage(s),
    }));

    const total_states =
      typeof json.data.total_states === "number"
        ? json.data.total_states
        : items.length;

    return {
      total_states,
      items,
    };
  },

  properties: async (query: PropertyListQuery = {}) => {
    const qs = new URLSearchParams();

    Object.entries(query).forEach(([k, v]) => {
      if (v === undefined || v === null || v === "") return;
      qs.set(k, String(v));
    });

    const json = await apiGet<any>(`/property.php?${qs.toString()}`);
    if (json.status !== "success") {
      throw new Error(json.message || "Property list failed");
    }

    const list = Array.isArray(json.data) ? json.data : json.data ? [json.data] : [];
    const data = list.map(normalizeProperty);

    return {
      meta:
        json.meta || {
          page: query.page || 1,
          limit: query.limit || 12,
          total: data.length,
          total_pages: 1,
        },
      data,
    };
  },

  propertyById: async (pid: string | number): Promise<Property> => {
    const json = await apiGet<any>(`/property.php?pid=${encodeURIComponent(String(pid))}`);
    if (json.status !== "success") {
      throw new Error(json.message || "Property request failed");
    }
    if (!json.data) {
      throw new Error("Property not found");
    }

    return normalizeProperty(json.data);
  },

  propertyDetails: async (pid: string | number): Promise<PropertyDetailsResponse> => {
    const json = await apiGet<any>(
      `/property_details.php?pid=${encodeURIComponent(String(pid))}`
    );
    if (json.status !== "success" || !json.data) {
      throw new Error(json.message || "Property details failed");
    }

    return {
      property: normalizeProperty(json.data.property),
      similar_properties: (json.data.similar_properties || []).map(normalizeProperty),
      site_info: {
        ...json.data.site_info,
        company_name: json.data.site_info?.company_name ?? null,
        logo_url: toPublicUrl(json.data.site_info?.logo_path),
        banner_image_url: toPublicUrl(json.data.site_info?.banner_image_path),
        favicon_url: toPublicUrl(json.data.site_info?.favicon_path),
      },
    };
  },

  submitContact: async (payload: {
    name: string;
    email: string;
    phone: string;
    subject: string;
    message: string;
  }) => {
    const json = await apiPost<any>(`/contact.php`, payload);
    if (json.status !== "success") {
      throw new Error(json.message || "Contact submit failed");
    }
    return json;
  },

  submitViewingRequest: async (payload: {
    name: string;
    email: string;
    phone: string;
    subject: string;
    message: string;
    pid: string | number;
    property_title: string;
    property_location: string;
    reference?: string;
  }) => {
    const json = await apiPost<any>(`/property_viewing_request.php`, payload);
    if (json.status !== "success") {
      throw new Error(json.message || "Viewing request failed");
    }
    return json;
  },
};