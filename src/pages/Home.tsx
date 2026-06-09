import { useEffect, useMemo, useRef, useState } from "react";
import Container from "../components/Container";
import SectionHeader from "../components/SectionHeader";
import PropertyCard from "../components/PropertyCard";
import Loading from "../components/Loading";
import ErrorMessage from "../components/ErrorMessage";
import { applySeo } from "../lib/seo";
import {
  api,
  type AboutContent,
  type Agent,
  type BannerSlide,
  type BannerTab,
  type Property,
  type SeoSettings,
  type SiteInfo,
  type StateCountItem,
} from "../lib/api";

function normalizeFrontendLink(link?: string | null) {
  const value = String(link || "").trim().toLowerCase();

  if (!value) return "/buy";
  if (value === "buy" || value === "/buy") return "/buy";
  if (value === "rent" || value === "/rent") return "/rent";
  if (value === "shortlet" || value === "/shortlet") return "/shortlet";
  if (value === "contact" || value === "/contact") return "/contact";
  if (value === "about" || value === "/about") return "/about";
  if (value === "home" || value === "/" || value === "index.php") return "/";
  if (value.includes("property.php")) return "/buy";

  if (value.startsWith("http://") || value.startsWith("https://")) {
    return link as string;
  }

  return value.startsWith("/") ? value : `/${value}`;
}

function getYouTubeEmbed(url?: string | null) {
  if (!url) return null;
  const value = url.trim();

  if (/^[a-zA-Z0-9_-]{6,20}$/.test(value)) {
    return `https://www.youtube.com/embed/${value}`;
  }

  const vMatch = value.match(/[?&]v=([a-zA-Z0-9_-]{6,20})/);
  if (vMatch?.[1]) return `https://www.youtube.com/embed/${vMatch[1]}`;

  const shortMatch = value.match(/youtu\.be\/([a-zA-Z0-9_-]{6,20})/);
  if (shortMatch?.[1]) return `https://www.youtube.com/embed/${shortMatch[1]}`;

  return null;
}

type HomeApiResponse = {
  site_info?: SiteInfo | null;
  seo?: SeoSettings | null;
};

export default function Home() {
  const hasLoadedRef = useRef(false);

  const [siteInfo, setSiteInfo] = useState<SiteInfo | null>(null);
  const [seo, setSeo] = useState<SeoSettings | null>(null);
  const [about, setAbout] = useState<AboutContent | null>(null);
  const [featured, setFeatured] = useState<Property[]>([]);
  const [latest, setLatest] = useState<Property[]>([]);
  const [agents, setAgents] = useState<Agent[]>([]);
  const [locations, setLocations] = useState<StateCountItem[]>([]);
  const [slides, setSlides] = useState<BannerSlide[]>([]);
  const [tabs, setTabs] = useState<BannerTab[]>([]);
  const [activeSlide, setActiveSlide] = useState(0);

  const [selectedState, setSelectedState] = useState("");
  const [selectedStype, setSelectedStype] = useState("");

  const [loading, setLoading] = useState(true);
  const [aboutLoading, setAboutLoading] = useState(true);
  const [agentsLoading, setAgentsLoading] = useState(true);
  const [error, setError] = useState("");

  useEffect(() => {
    if (hasLoadedRef.current) return;
    hasLoadedRef.current = true;

    let mounted = true;

    async function loadHome() {
      try {
        setLoading(true);
        setError("");

        const homepageUrl = `${window.location.origin}/api/v1/homepage.php`;

        const [homepageRes, featuredData, latestData, locationsData, aboutData, agentsData, slideData, tabData] =
          await Promise.all([
            fetch(homepageUrl, {
              method: "GET",
              headers: {
                Accept: "application/json",
              },
            }),
            api.properties({ featured: 1, limit: 6 }),
            api.properties({ limit: 6 }),
            api.stateCounts(20),
            api.about(),
            api.agents(6),
            api.bannerSlides("home"),
            api.bannerTabs("home"),
          ]);

        if (!homepageRes.ok) {
          throw new Error(`Homepage request failed: ${homepageRes.status}`);
        }

        const homepageData: HomeApiResponse = await homepageRes.json();

        if (!mounted) return;

        setSiteInfo(homepageData.site_info || null);
        setSeo(homepageData.seo || null);

        setFeatured(featuredData.data || []);
        setLatest(latestData.data || []);
        setLocations(
          (locationsData.items || []).filter(
            (x) => Number(x.total_properties || 0) > 0
          )
        );
        setAbout(aboutData);
        setAgents(agentsData.agents || []);
        setSlides(slideData || []);
        setTabs(tabData || []);

        setLoading(false);
        setAboutLoading(false);
        setAgentsLoading(false);
      } catch (err) {
        if (!mounted) return;
        setError(err instanceof Error ? err.message : "Something went wrong");
        setLoading(false);
        setAboutLoading(false);
        setAgentsLoading(false);
      }
    }

    loadHome();

    return () => {
      mounted = false;
    };
  }, []);

  useEffect(() => {
    if (!siteInfo) return;

    applySeo(
      seo,
      siteInfo,
      "Home | PropertySync Skyline",
      "Modern real estate website"
    );
  }, [seo, siteInfo]);

  useEffect(() => {
    const mode = String(siteInfo?.display_mode || "").toLowerCase();
    const isSliderMode = mode === "slider" || mode === "banner";

    if (!isSliderMode || !slides.length) return;

    const timer = window.setInterval(() => {
      setActiveSlide((prev) => (prev + 1) % slides.length);
    }, 5000);

    return () => window.clearInterval(timer);
  }, [slides, siteInfo?.display_mode]);

  const rawMode = String(siteInfo?.display_mode || "hero").toLowerCase();
  const isSliderMode = rawMode === "slider" || rawMode === "banner";
  const currentSlide = slides[activeSlide] || null;

  const heroContent = useMemo(() => {
    const fallbackImage = siteInfo?.banner_image_url || siteInfo?.banner_image_path || null;
    const fallbackEyebrow = siteInfo?.welcome_message || "";
    const fallbackTitle =
      siteInfo?.banner_writeup || "Find a home that fits your life.";

    if (isSliderMode && currentSlide) {
      return {
        image: currentSlide.background_image_url || fallbackImage,
        eyebrow: currentSlide.span_text || fallbackEyebrow,
        title: currentSlide.heading_text || fallbackTitle,
        buttonText: currentSlide.button_text || "Explore Listings",
        buttonLink: normalizeFrontendLink(currentSlide.button_link),
      };
    }

    return {
      image: fallbackImage,
      eyebrow: fallbackEyebrow,
      title: fallbackTitle,
      buttonText: "Explore Listings",
      buttonLink: "/buy",
    };
  }, [isSliderMode, currentSlide, siteInfo]);

  const availableStates = useMemo(() => {
    return locations.filter((item) => Number(item.total_properties || 0) > 0);
  }, [locations]);

  const selectedStateData = useMemo(() => {
    return availableStates.find(
      (item) => item.sname.toLowerCase() === selectedState.toLowerCase()
    );
  }, [availableStates, selectedState]);

  const availableListingTypes = useMemo(() => {
    if (!selectedStateData) {
      const hasSale = availableStates.some((x) => Number(x.sale_count || 0) > 0);
      const hasRent = availableStates.some((x) => Number(x.rent_count || 0) > 0);
      const hasShortlet = availableStates.some((x) => Number(x.shortlet_count || 0) > 0);

      return [
        hasSale ? { value: "sale", label: "For Sale" } : null,
        hasRent ? { value: "rent", label: "For Rent" } : null,
        hasShortlet ? { value: "shortlet", label: "Shortlet" } : null,
      ].filter(Boolean) as { value: string; label: string }[];
    }

    return [
      Number(selectedStateData.sale_count || 0) > 0
        ? { value: "sale", label: "For Sale" }
        : null,
      Number(selectedStateData.rent_count || 0) > 0
        ? { value: "rent", label: "For Rent" }
        : null,
      Number(selectedStateData.shortlet_count || 0) > 0
        ? { value: "shortlet", label: "Shortlet" }
        : null,
    ].filter(Boolean) as { value: string; label: string }[];
  }, [availableStates, selectedStateData]);

  useEffect(() => {
    if (!selectedStype) return;

    const stillAvailable = availableListingTypes.some(
      (item) => item.value === selectedStype
    );

    if (!stillAvailable) {
      setSelectedStype("");
    }
  }, [availableListingTypes, selectedStype]);

  function handleSearchSubmit(e: React.FormEvent<HTMLFormElement>) {
    e.preventDefault();

    const params = new URLSearchParams();

    if (selectedState) {
      params.set("state", selectedState);
    }

    if (selectedStype) {
      params.set("stype", selectedStype);
    }

    window.location.href = `/search?${params.toString()}`;
  }

  const aboutVideo = useMemo(
    () => getYouTubeEmbed(about?.video_url),
    [about?.video_url]
  );
  const aboutTitle = about?.title || "About Us";
  const aboutSubtitle = about?.subtitle || "About Us";
  const aboutYears = Number(about?.years_experience || 20);

  if (loading) return <Loading />;
  if (error) return <ErrorMessage message={error} />;

  return (
    <>
      <section className="relative overflow-hidden">
        <div className="absolute inset-0">
          {heroContent.image ? (
            <img
              src={heroContent.image}
              alt="Banner"
              className="h-full w-full object-cover"
            />
          ) : (
            <div className="h-full w-full bg-slate-300" />
          )}
          <div className="absolute inset-0 bg-slate-950/60" />
        </div>

        <Container>
          <div className="relative py-24 md:py-36">
            <div className="max-w-3xl text-white">
              <div className="text-xs font-semibold tracking-[0.22em] uppercase text-white/70">
                {heroContent.eyebrow}
              </div>

              <h1 className="mt-5 text-4xl font-semibold leading-tight tracking-tight md:text-6xl">
                {heroContent.title}
              </h1>

              <div className="mt-8 flex flex-wrap gap-3">
                <a
                  href={heroContent.buttonLink}
                  className="inline-flex items-center justify-center rounded-xl bg-white px-5 py-3 text-sm font-semibold text-slate-950 hover:bg-slate-100"
                >
                  {heroContent.buttonText}
                </a>

                <a
                  href="/contact"
                  className="inline-flex items-center justify-center rounded-xl border border-white/30 px-5 py-3 text-sm font-semibold text-white hover:bg-white/10"
                >
                  Book Inspection
                </a>
              </div>

              <form
                onSubmit={handleSearchSubmit}
                className="mt-10 rounded-3xl bg-white p-4 shadow-2xl md:p-5"
              >
                <div className="grid gap-4 md:grid-cols-[1.2fr_1fr_auto]">
                  <div>
                    <label className="mb-2 block text-sm font-semibold text-slate-900">
                      Select State
                    </label>
                    <select
                      value={selectedState}
                      onChange={(e) => setSelectedState(e.target.value)}
                      className="h-12 w-full rounded-xl border border-slate-200 px-4 text-sm text-slate-900 outline-none focus:border-slate-950"
                    >
                      <option value="">All States</option>
                      {availableStates.map((state) => (
                        <option key={state.sid} value={state.sname}>
                          {state.sname} ({state.total_properties})
                        </option>
                      ))}
                    </select>
                  </div>

                  <div>
                    <label className="mb-2 block text-sm font-semibold text-slate-900">
                      Listing Type
                    </label>
                    <select
                      value={selectedStype}
                      onChange={(e) => setSelectedStype(e.target.value)}
                      className="h-12 w-full rounded-xl border border-slate-200 px-4 text-sm text-slate-900 outline-none focus:border-slate-950"
                    >
                      <option value="">All Types</option>
                      {availableListingTypes.map((item) => (
                        <option key={item.value} value={item.value}>
                          {item.label}
                        </option>
                      ))}
                    </select>
                  </div>

                  <div className="md:self-end">
                    <button
                      type="submit"
                      className="h-12 w-full rounded-xl bg-slate-950 px-6 text-sm font-semibold text-white hover:bg-slate-800 md:w-auto"
                    >
                      Search
                    </button>
                  </div>
                </div>
              </form>

              {tabs.length > 0 ? (
                <div className="mt-8 flex flex-wrap gap-3">
                  {tabs.map((tab) => (
                    <a
                      key={tab.id}
                      href={`/search?stype=${encodeURIComponent(tab.tab_stype)}`}
                      className="inline-flex items-center justify-center rounded-xl border border-white/20 bg-white/10 px-4 py-2 text-sm font-semibold text-white hover:bg-white/15"
                    >
                      {tab.tab_label}
                    </a>
                  ))}
                </div>
              ) : null}

              {isSliderMode && slides.length > 1 ? (
                <div className="mt-6 flex gap-2">
                  {slides.map((slide, index) => (
                    <button
                      key={slide.id}
                      onClick={() => setActiveSlide(index)}
                      className={`h-2.5 rounded-full transition-all ${
                        index === activeSlide ? "w-8 bg-white" : "w-2.5 bg-white/50"
                      }`}
                      aria-label={`Slide ${index + 1}`}
                    />
                  ))}
                </div>
              ) : null}
            </div>
          </div>
        </Container>
      </section>

      <section className="py-16">
        <Container>
          <SectionHeader
            eyebrow="Properties"
            title="Featured Listings"
            right={
              <a
                href="/buy"
                className="hidden sm:inline-flex items-center justify-center rounded-xl border border-slate-200 px-4 py-2 text-sm font-semibold hover:bg-slate-50"
              >
                View All
              </a>
            }
          />

          <div className="mt-10 grid gap-6 md:grid-cols-2 xl:grid-cols-3">
            {featured.map((property) => (
              <PropertyCard key={property.pid} property={property} />
            ))}
          </div>
        </Container>
      </section>

      <section className="bg-slate-50 py-16">
        <Container>
          <div className="grid gap-10 lg:grid-cols-2 lg:items-center">
            <div>
              {aboutLoading ? (
                <div className="h-[420px] w-full animate-pulse rounded-3xl bg-slate-200" />
              ) : about?.image_url ? (
                <img
                  src={about.image_url}
                  alt={aboutTitle}
                  className="w-full rounded-3xl border border-slate-200 object-cover"
                />
              ) : (
                <div className="h-[420px] w-full rounded-3xl bg-slate-200" />
              )}
            </div>

            <div>
              <div className="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">
                {aboutSubtitle}
              </div>

              <h2 className="mt-3 text-4xl font-semibold tracking-tight">
                {aboutTitle}
              </h2>

              {aboutLoading ? (
                <div className="mt-6 space-y-3">
                  <div className="h-4 w-full animate-pulse rounded bg-slate-200" />
                  <div className="h-4 w-11/12 animate-pulse rounded bg-slate-200" />
                  <div className="h-4 w-10/12 animate-pulse rounded bg-slate-200" />
                  <div className="h-4 w-8/12 animate-pulse rounded bg-slate-200" />
                </div>
              ) : about?.content ? (
                <div
                  className="prose prose-slate mt-6 max-w-none"
                  dangerouslySetInnerHTML={{ __html: about.content }}
                />
              ) : (
                <p className="mt-6 leading-relaxed text-slate-700">
                  Learn more about our agency, experience, and how we serve buyers, sellers, and investors.
                </p>
              )}

              <div className="mt-8 grid gap-6 sm:grid-cols-2">
                <div className="rounded-2xl border border-slate-200 bg-white p-6">
                  <div className="text-4xl font-bold text-slate-950">{aboutYears}</div>
                  <div className="mt-2 text-sm font-medium text-slate-600">
                    Years Experience
                  </div>
                </div>

                {!aboutLoading && about?.image2_url ? (
                  <div className="overflow-hidden rounded-2xl border border-slate-200 bg-white">
                    <img
                      src={about.image2_url}
                      alt={`${aboutTitle} secondary`}
                      className="h-full w-full object-cover"
                    />
                  </div>
                ) : null}
              </div>

              {!aboutLoading && aboutVideo ? (
                <div className="mt-8 overflow-hidden rounded-3xl border border-slate-200 bg-white">
                  <div className="aspect-video">
                    <iframe
                      src={aboutVideo}
                      title="About video"
                      className="h-full w-full"
                      allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                      allowFullScreen
                    />
                  </div>
                </div>
              ) : null}

              <div className="mt-8">
                <a
                  href="/about"
                  className="inline-flex items-center justify-center rounded-xl bg-slate-950 px-5 py-3 text-sm font-semibold text-white hover:bg-slate-800"
                >
                  Learn More
                </a>
              </div>
            </div>
          </div>
        </Container>
      </section>

      <section className="py-16">
        <Container>
          <SectionHeader eyebrow="People" title="Meet the Team" />
          <div className="mt-10 grid gap-6 md:grid-cols-2 xl:grid-cols-3">
            {agentsLoading
              ? Array.from({ length: 3 }).map((_, index) => (
                  <div
                    key={index}
                    className="rounded-3xl border border-slate-200 bg-white p-6"
                  >
                    <div className="h-16 w-16 animate-pulse rounded-2xl bg-slate-200" />
                    <div className="mt-4 h-5 w-40 animate-pulse rounded bg-slate-200" />
                    <div className="mt-2 h-4 w-28 animate-pulse rounded bg-slate-200" />
                    <div className="mt-4 h-4 w-full animate-pulse rounded bg-slate-200" />
                    <div className="mt-2 h-4 w-11/12 animate-pulse rounded bg-slate-200" />
                  </div>
                ))
              : agents.map((agent) => (
                  <div
                    key={agent.agent_id}
                    className="rounded-3xl border border-slate-200 bg-white p-6"
                  >
                    {agent.picture_url ? (
                      <img
                        src={agent.picture_url}
                        alt={agent.name}
                        className="h-16 w-16 rounded-2xl object-cover"
                      />
                    ) : (
                      <div className="h-16 w-16 rounded-2xl bg-slate-200" />
                    )}

                    <h3 className="mt-4 text-lg font-semibold">{agent.name}</h3>
                    <p className="mt-1 text-sm text-slate-600">Property Consultant</p>

                    {agent.description ? (
                      <p className="mt-4 text-sm leading-relaxed text-slate-600">
                        {agent.description}
                      </p>
                    ) : null}
                  </div>
                ))}
          </div>
        </Container>
      </section>

      <section className="bg-slate-50 py-16">
        <Container>
          <SectionHeader eyebrow="Coverage" title="Where We Have Properties" />
          <div className="mt-10 grid gap-6 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
            {locations.map((location) => (
              <div
                key={location.sid}
                className="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm transition hover:-translate-y-1 hover:shadow-md"
              >
                <div className="relative h-52 w-full bg-slate-200">
                  {location.image_url ? (
                    <img
                      src={location.image_url}
                      alt={location.sname}
                      className="h-full w-full object-cover"
                      onError={(e) => {
                        const img = e.currentTarget;
                        img.style.display = "none";
                        const fallback = img.parentElement?.querySelector(
                          ".coverage-fallback"
                        ) as HTMLElement | null;
                        if (fallback) {
                          fallback.style.display = "flex";
                        }
                      }}
                    />
                  ) : null}

                  <div
                    className="coverage-fallback absolute inset-0 hidden items-center justify-center text-sm font-medium text-slate-500"
                    style={{ display: location.image_url ? "none" : "flex" }}
                  >
                    No image available
                  </div>
                </div>

                <div className="p-6">
                  <div className="text-lg font-semibold tracking-tight text-slate-900">
                    {location.sname}
                  </div>

                  <div className="mt-2 text-sm text-slate-600">
                    {location.total_properties} total properties
                  </div>

                  <div className="mt-4 grid grid-cols-3 gap-2">
                    <div className="rounded-xl bg-slate-50 px-3 py-2 text-center">
                      <div className="text-base font-semibold text-slate-900">
                        {location.sale_count ?? 0}
                      </div>
                      <div className="text-[11px] text-slate-500">Sale</div>
                    </div>

                    <div className="rounded-xl bg-slate-50 px-3 py-2 text-center">
                      <div className="text-base font-semibold text-slate-900">
                        {location.rent_count ?? 0}
                      </div>
                      <div className="text-[11px] text-slate-500">Rent</div>
                    </div>

                    <div className="rounded-xl bg-slate-50 px-3 py-2 text-center">
                      <div className="text-base font-semibold text-slate-900">
                        {location.shortlet_count ?? 0}
                      </div>
                      <div className="text-[11px] text-slate-500">Shortlet</div>
                    </div>
                  </div>
                </div>
              </div>
            ))}
          </div>
        </Container>
      </section>

      <section className="py-16">
        <Container>
          <SectionHeader
            eyebrow="More Listings"
            title="Latest Properties"
            right={
              <a
                href="/buy"
                className="hidden sm:inline-flex items-center justify-center rounded-xl border border-slate-200 px-4 py-2 text-sm font-semibold hover:bg-slate-50"
              >
                Browse All
              </a>
            }
          />

          <div className="mt-10 grid gap-6 md:grid-cols-2 xl:grid-cols-3">
            {latest.map((property) => (
              <PropertyCard key={property.pid} property={property} />
            ))}
          </div>
        </Container>
      </section>
    </>
  );
}