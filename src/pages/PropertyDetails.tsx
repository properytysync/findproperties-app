import { useEffect, useMemo, useState } from "react";
import type { FormEvent } from "react";
import { useParams } from "react-router-dom";
import { Swiper, SwiperSlide } from "swiper/react";
import { Navigation, Thumbs } from "swiper/modules";
import type { Swiper as SwiperType } from "swiper";
import "swiper/css";
import "swiper/css/navigation";
import "swiper/css/thumbs";

import Container from "../components/Container";
import Loading from "../components/Loading";
import ErrorMessage from "../components/ErrorMessage";
import PropertyCard from "../components/PropertyCard";
import { api, type Property, type SiteInfo } from "../lib/api";

declare global {
  interface Window {
    PaystackPop?: any;
  }
}

function formatPrice(value: string | number | null | undefined, currency = "₦") {
  if (value === null || value === undefined || value === "") return `${currency}0`;

  const raw = String(value).trim();
  if (/[₦$€£]/.test(raw)) return raw;

  const num = Number(raw.replace(/,/g, ""));
  if (Number.isNaN(num)) return raw;

  return `${currency}${num.toLocaleString()}`;
}

function loadPaystackScript(): Promise<void> {
  return new Promise((resolve, reject) => {
    if (window.PaystackPop) {
      resolve();
      return;
    }

    const existing = document.querySelector(
      'script[data-paystack="true"]'
    ) as HTMLScriptElement | null;

    if (existing) {
      existing.addEventListener("load", () => resolve());
      existing.addEventListener("error", () => reject(new Error("Failed to load Paystack")));
      return;
    }

    const script = document.createElement("script");
    script.src = "https://js.paystack.co/v1/inline.js";
    script.async = true;
    script.dataset.paystack = "true";
    script.onload = () => resolve();
    script.onerror = () => reject(new Error("Failed to load Paystack"));

    document.body.appendChild(script);
  });
}

function convertYouTubeLinksToIframe(html: string) {
  if (!html) return "";

  const pattern =
    /(https?:\/\/(?:www\.)?youtube\.com\/watch\?v=|https?:\/\/youtu\.be\/)([a-zA-Z0-9_-]+)/gi;

  return html.replace(
    pattern,
    `<div class="mt-6 overflow-hidden rounded-2xl border border-slate-200">
      <iframe
        class="w-full aspect-video"
        src="https://www.youtube.com/embed/$2"
        frameborder="0"
        allowfullscreen
      ></iframe>
    </div>`
  );
}

export default function PropertyDetails() {
  const { id } = useParams();

  const [property, setProperty] = useState<Property | null>(null);
  const [similar, setSimilar] = useState<Property[]>([]);
  const [siteInfo, setSiteInfo] = useState<SiteInfo | null>(null);

  const [thumbsSwiper, setThumbsSwiper] = useState<SwiperType | null>(null);
  const [lightboxIndex, setLightboxIndex] = useState<number | null>(null);

  const [loading, setLoading] = useState(true);
  const [error, setError] = useState("");

  const [formError, setFormError] = useState("");
  const [formSuccess, setFormSuccess] = useState("");
  const [submitting, setSubmitting] = useState(false);

  const [form, setForm] = useState({
    name: "",
    email: "",
    phone: "",
    subject: "",
    message: "",
  });

  useEffect(() => {
    async function loadProperty() {
      try {
        if (!id) throw new Error("Missing property ID");

        setLoading(true);
        setError("");

        const res = await api.propertyDetails(id);

        setProperty(res.property);
        setSimilar(res.similar_properties || []);
        setSiteInfo(res.site_info || null);

        const locationText = [
          res.property.location,
          res.property.city,
          res.property.state,
        ]
          .filter(Boolean)
          .join(", ");

        setForm((prev) => ({
          ...prev,
          subject: `Inquiry about ${res.property.title}`,
          message: `I'm interested in this property at ${locationText} and would like to schedule a viewing.`,
        }));
      } catch (err) {
        setError(err instanceof Error ? err.message : "Failed to load property");
      } finally {
        setLoading(false);
      }
    }

    loadProperty();
  }, [id]);

  useEffect(() => {
    function onKeyDown(e: KeyboardEvent) {
      if (lightboxIndex === null || !property) return;

      if (e.key === "Escape") setLightboxIndex(null);

      if (e.key === "ArrowRight") {
        setLightboxIndex((prev) => {
          if (prev === null) return 0;
          return (prev + 1) % property.images.length;
        });
      }

      if (e.key === "ArrowLeft") {
        setLightboxIndex((prev) => {
          if (prev === null) return 0;
          return (prev - 1 + property.images.length) % property.images.length;
        });
      }
    }

    window.addEventListener("keydown", onKeyDown);
    return () => window.removeEventListener("keydown", onKeyDown);
  }, [lightboxIndex, property]);

  const locationText = useMemo(() => {
    if (!property) return "";
    return [property.location, property.city, property.state].filter(Boolean).join(", ");
  }, [property]);

  const whatsappShare = useMemo(() => {
    if (typeof window === "undefined") return "#";
    return `https://wa.me/?text=${encodeURIComponent(
      `Check out this property: ${window.location.href}`
    )}`;
  }, []);

  const isLand = String(property?.type || "").toLowerCase() === "land";
  const priceText = formatPrice(property?.price, siteInfo?.currency || "₦");

  async function handleInquirySubmit(e: FormEvent) {
    e.preventDefault();

    if (!property || !siteInfo) return;

    try {
      setSubmitting(true);
      setFormError("");
      setFormSuccess("");

      const enableViewingPayment = Number(siteInfo.enable_viewing_payment || 0) === 1;
      const viewingFee = Number(siteInfo.viewing_fee || 0);
      const currency = siteInfo.currency || "₦";

      if (!enableViewingPayment) {
        await api.submitViewingRequest({
          ...form,
          pid: property.pid,
          property_title: property.title,
          property_location: locationText,
        });

        setFormSuccess("Message sent successfully! We’ll contact you shortly.");
        return;
      }

      await loadPaystackScript();

      if (!window.PaystackPop) {
        throw new Error("Paystack failed to load.");
      }

      const publicKey = siteInfo.paystack_public_key || "";
      if (!publicKey) {
        throw new Error("Paystack public key is missing.");
      }

      await new Promise<void>((resolve, reject) => {
        const handler = window.PaystackPop.setup({
          key: publicKey,
          email: form.email,
          amount: viewingFee * 100,
          currency: "NGN",
          ref: String(Date.now()),
          metadata: {
            custom_fields: [
              { display_name: "Name", variable_name: "name", value: form.name },
              { display_name: "Phone", variable_name: "phone", value: form.phone },
              { display_name: "PID", variable_name: "pid", value: property.pid },
            ],
          },
          callback: async (response: any) => {
            try {
              await api.submitViewingRequest({
                ...form,
                pid: property.pid,
                property_title: property.title,
                property_location: locationText,
                reference: response.reference,
              });

              setFormSuccess(
                `Payment successful (${currency}${viewingFee.toLocaleString()}) and request submitted successfully.`
              );
              resolve();
            } catch (err) {
              reject(err);
            }
          },
          onClose: () => {
            reject(new Error("Payment window closed."));
          },
        });

        handler.openIframe();
      });
    } catch (err) {
      setFormError(err instanceof Error ? err.message : "Failed to submit request");
    } finally {
      setSubmitting(false);
    }
  }

  if (loading) return <Loading />;
  if (error) return <ErrorMessage message={error} />;
  if (!property) return <ErrorMessage message="Property not found" />;

  const descriptionHtml = convertYouTubeLinksToIframe(property.pcontent || "");

  return (
    <main className="py-10 md:py-14 overflow-x-hidden">
      <Container>
        <div className="grid grid-cols-1 gap-10 xl:grid-cols-[minmax(0,2fr)_360px]">
          {/* Main content */}
          <section className="min-w-0">
            {/* Hero heading */}
            <div className="mb-8 rounded-3xl bg-slate-900 px-6 py-10 text-white md:px-8">
              <h1 className="text-3xl font-semibold tracking-tight md:text-5xl">
                {property.title}
              </h1>

              <div className="mt-4 text-sm text-white/80">
                Home / Property for {property.stype || property.type || "Sale"} / {property.title}
              </div>
            </div>

            {/* Gallery */}
            {property.images.length > 0 ? (
              <div className="mb-8 min-w-0">
                <div className="overflow-hidden rounded-3xl border border-slate-200 bg-white">
                  <Swiper
                    modules={[Navigation, Thumbs]}
                    navigation
                    spaceBetween={10}
                    thumbs={{ swiper: thumbsSwiper && !thumbsSwiper.destroyed ? thumbsSwiper : null }}
                    className="w-full"
                  >
                    {property.images.map((img, index) => (
                      <SwiperSlide key={`${img}-${index}`}>
                        <button
                          type="button"
                          className="block w-full"
                          onClick={() => setLightboxIndex(index)}
                        >
                          <img
                            src={img}
                            alt={`${property.title} ${index + 1}`}
                            className="h-[300px] w-full object-cover sm:h-[380px] md:h-[500px]"
                          />
                        </button>
                      </SwiperSlide>
                    ))}
                  </Swiper>
                </div>

                {property.images.length > 1 ? (
                  <div className="mt-4 min-w-0">
                    <Swiper
                      onSwiper={setThumbsSwiper}
                      spaceBetween={10}
                      slidesPerView={4}
                      watchSlidesProgress
                      className="w-full"
                    >
                      {property.images.map((img, index) => (
                        <SwiperSlide key={`${img}-thumb-${index}`}>
                          <img
                            src={img}
                            alt={`${property.title} thumb ${index + 1}`}
                            className="h-20 w-full rounded-2xl border border-slate-200 object-cover md:h-24"
                          />
                        </SwiperSlide>
                      ))}
                    </Swiper>
                  </div>
                ) : null}
              </div>
            ) : null}

            {/* Lightbox */}
            {lightboxIndex !== null && property.images[lightboxIndex] ? (
              <div
                className="fixed inset-0 z-[100] bg-black/90 p-4"
                onClick={() => setLightboxIndex(null)}
              >
                <button
                  className="absolute right-6 top-6 text-3xl text-white"
                  onClick={() => setLightboxIndex(null)}
                >
                  ×
                </button>

                <div className="absolute inset-0 flex items-center justify-center">
                  <img
                    src={property.images[lightboxIndex]}
                    alt={`${property.title} fullscreen`}
                    className="max-h-[90vh] max-w-[90vw] object-contain"
                  />
                </div>

                {property.images.length > 1 ? (
                  <>
                    <button
                      className="absolute left-6 top-1/2 -translate-y-1/2 rounded-full bg-white/20 px-4 py-3 text-white"
                      onClick={(e) => {
                        e.stopPropagation();
                        setLightboxIndex((prev) =>
                          prev === null ? 0 : (prev - 1 + property.images.length) % property.images.length
                        );
                      }}
                    >
                      ‹
                    </button>

                    <button
                      className="absolute right-6 top-1/2 -translate-y-1/2 rounded-full bg-white/20 px-4 py-3 text-white"
                      onClick={(e) => {
                        e.stopPropagation();
                        setLightboxIndex((prev) =>
                          prev === null ? 0 : (prev + 1) % property.images.length
                        );
                      }}
                    >
                      ›
                    </button>
                  </>
                ) : null}
              </div>
            ) : null}

            {/* Header */}
            <div className="mb-8 flex flex-col gap-4 rounded-3xl border border-slate-200 bg-white p-6 md:flex-row md:items-start md:justify-between">
              <div className="min-w-0">
                <h2 className="text-2xl font-semibold tracking-tight md:text-3xl">
                  {property.title}
                </h2>
                <p className="mt-2 text-slate-600">{locationText}</p>
              </div>

              <div className="text-2xl font-bold text-slate-950 md:text-3xl">
                {priceText}
              </div>
            </div>

            {/* Key features */}
            <div className="mb-10 flex flex-wrap gap-4">
              {property.type ? (
                <div className="rounded-2xl bg-slate-50 px-5 py-4">
                  <div className="text-sm text-slate-500">Type</div>
                  <div className="font-semibold">{property.type}</div>
                </div>
              ) : null}

              {property.stype ? (
                <div className="rounded-2xl bg-slate-50 px-5 py-4">
                  <div className="text-sm text-slate-500">Purpose</div>
                  <div className="font-semibold">{property.stype}</div>
                </div>
              ) : null}

              {!isLand && property.bedroom ? (
                <div className="rounded-2xl bg-slate-50 px-5 py-4">
                  <div className="text-sm text-slate-500">Bedrooms</div>
                  <div className="font-semibold">{property.bedroom}</div>
                </div>
              ) : null}

              {!isLand && property.bathroom ? (
                <div className="rounded-2xl bg-slate-50 px-5 py-4">
                  <div className="text-sm text-slate-500">Bathrooms</div>
                  <div className="font-semibold">{property.bathroom}</div>
                </div>
              ) : null}

              {!isLand && property.balcony ? (
                <div className="rounded-2xl bg-slate-50 px-5 py-4">
                  <div className="text-sm text-slate-500">Balconies</div>
                  <div className="font-semibold">{property.balcony}</div>
                </div>
              ) : null}

              {!isLand && property.kitchen ? (
                <div className="rounded-2xl bg-slate-50 px-5 py-4">
                  <div className="text-sm text-slate-500">Kitchens</div>
                  <div className="font-semibold">{property.kitchen}</div>
                </div>
              ) : null}

              {!isLand && property.toilet ? (
                <div className="rounded-2xl bg-slate-50 px-5 py-4">
                  <div className="text-sm text-slate-500">Toilets</div>
                  <div className="font-semibold">{property.toilet}</div>
                </div>
              ) : null}

              {property.size ? (
                <div className="rounded-2xl bg-slate-50 px-5 py-4">
                  <div className="text-sm text-slate-500">Size</div>
                  <div className="font-semibold">{property.size}</div>
                </div>
              ) : null}
            </div>

            {/* Description */}
            <section className="mb-10 rounded-3xl border border-slate-200 bg-white p-6">
              <h3 className="text-2xl font-semibold tracking-tight">Description</h3>
              <div
                className="prose prose-slate mt-4 max-w-none break-words"
                dangerouslySetInnerHTML={{ __html: descriptionHtml }}
              />
            </section>

            {/* Features */}
            {property.feature ? (
              <section className="mb-10 rounded-3xl border border-slate-200 bg-white p-6">
                <h3 className="text-2xl font-semibold tracking-tight">Features</h3>
                <div
                  className="prose prose-slate mt-4 max-w-none break-words"
                  dangerouslySetInnerHTML={{ __html: property.feature }}
                />
              </section>
            ) : null}

            {/* Additional images */}
            {property.map_images.length > 0 ? (
              <section className="mb-10 rounded-3xl border border-slate-200 bg-white p-6">
                <h3 className="text-2xl font-semibold tracking-tight">Additional Images</h3>
                <div className="mt-6 grid gap-4 md:grid-cols-2">
                  {property.map_images.map((img, index) => (
                    <img
                      key={`${img}-${index}`}
                      src={img}
                      alt={`${property.title} extra ${index + 1}`}
                      className="w-full rounded-2xl border border-slate-200 object-cover"
                    />
                  ))}
                </div>
              </section>
            ) : null}

            {/* Map */}
            <section className="mb-10 rounded-3xl border border-slate-200 bg-white p-6">
              <h3 className="text-2xl font-semibold tracking-tight">Location</h3>
              <div className="mt-6 overflow-hidden rounded-2xl border border-slate-200">
                <iframe
                  title="Google Map"
                  src={`https://www.google.com/maps?q=${encodeURIComponent(locationText || "Nigeria")}&output=embed`}
                  width="100%"
                  height="450"
                  style={{ border: 0 }}
                  loading="lazy"
                />
              </div>
            </section>

            {/* Inquiry form */}
            <section className="mb-10 rounded-3xl bg-slate-50 p-6">
              <h3 className="text-2xl font-semibold tracking-tight">
                Schedule a Viewing
                {Number(siteInfo?.enable_viewing_payment || 0) === 1
                  ? ` (Fee: ${formatPrice(siteInfo?.viewing_fee || 0, siteInfo?.currency || "₦")})`
                  : ""}
              </h3>

              {formError ? (
                <div className="mt-4 rounded-2xl border border-red-200 bg-red-50 p-4 text-red-700">
                  {formError}
                </div>
              ) : null}

              {formSuccess ? (
                <div className="mt-4 rounded-2xl border border-green-200 bg-green-50 p-4 text-green-700">
                  {formSuccess}
                </div>
              ) : null}

              <form className="mt-6 grid gap-4 md:grid-cols-2" onSubmit={handleInquirySubmit}>
                <input
                  className="rounded-xl border border-slate-200 px-4 py-3 outline-none focus:border-slate-950"
                  placeholder="Full Name"
                  value={form.name}
                  onChange={(e) => setForm((v) => ({ ...v, name: e.target.value }))}
                  required
                />

                <input
                  className="rounded-xl border border-slate-200 px-4 py-3 outline-none focus:border-slate-950"
                  placeholder="Email"
                  type="email"
                  value={form.email}
                  onChange={(e) => setForm((v) => ({ ...v, email: e.target.value }))}
                  required
                />

                <input
                  className="rounded-xl border border-slate-200 px-4 py-3 outline-none focus:border-slate-950"
                  placeholder="Phone Number"
                  value={form.phone}
                  onChange={(e) => setForm((v) => ({ ...v, phone: e.target.value }))}
                  required
                />

                <input
                  className="rounded-xl border border-slate-200 px-4 py-3 outline-none focus:border-slate-950"
                  placeholder="Subject"
                  value={form.subject}
                  onChange={(e) => setForm((v) => ({ ...v, subject: e.target.value }))}
                  required
                />

                <textarea
                  className="min-h-[160px] rounded-xl border border-slate-200 px-4 py-3 outline-none focus:border-slate-950 md:col-span-2"
                  placeholder="Message"
                  value={form.message}
                  onChange={(e) => setForm((v) => ({ ...v, message: e.target.value }))}
                  required
                />

                <div className="md:col-span-2">
                  <button
                    type="submit"
                    disabled={submitting}
                    className="inline-flex items-center justify-center rounded-xl bg-slate-950 px-5 py-3 text-sm font-semibold text-white hover:bg-slate-800 disabled:opacity-60"
                  >
                    {submitting
                      ? "Processing..."
                      : Number(siteInfo?.enable_viewing_payment || 0) === 1
                      ? "Pay and Send Message"
                      : "Send Message"}
                  </button>
                </div>
              </form>
            </section>
          </section>

          {/* Sidebar */}
          <aside className="min-w-0">
            <div className="space-y-6 xl:sticky xl:top-24">
              <div className="rounded-3xl border border-slate-200 bg-white p-6">
                <a
                  href={whatsappShare}
                  target="_blank"
                  rel="noreferrer"
                  className="inline-flex items-center justify-center rounded-xl bg-green-500 px-4 py-3 text-sm font-semibold text-white hover:bg-green-600"
                >
                  Share on WhatsApp
                </a>
              </div>

              <div className="rounded-3xl border border-slate-200 bg-white p-6">
                <h4 className="text-lg font-semibold tracking-tight">Property Summary</h4>

                <div className="mt-4 grid gap-4 text-sm">
                  <div className="flex items-center justify-between gap-4">
                    <span className="text-slate-600">Price</span>
                    <span className="font-semibold text-right">{priceText}</span>
                  </div>

                  <div className="flex items-center justify-between gap-4">
                    <span className="text-slate-600">Property Type</span>
                    <span className="font-semibold text-right">{property.type || "N/A"}</span>
                  </div>

                  {isLand && property.size ? (
                    <div className="flex items-center justify-between gap-4">
                      <span className="text-slate-600">Area</span>
                      <span className="font-semibold text-right">{property.size}</span>
                    </div>
                  ) : null}

                  <div className="flex items-center justify-between gap-4">
                    <span className="text-slate-600">Location</span>
                    <span className="font-semibold text-right">{property.location || "N/A"}</span>
                  </div>

                  <div className="flex items-center justify-between gap-4">
                    <span className="text-slate-600">City</span>
                    <span className="font-semibold text-right">{property.city || "N/A"}</span>
                  </div>

                  <div className="flex items-center justify-between gap-4">
                    <span className="text-slate-600">State</span>
                    <span className="font-semibold text-right">{property.state || "N/A"}</span>
                  </div>

                  <div className="flex items-center justify-between gap-4">
                    <span className="text-slate-600">Status</span>
                    <span className="font-semibold text-right">{property.status || "N/A"}</span>
                  </div>

                  <div className="flex items-center justify-between gap-4">
                    <span className="text-slate-600">Date Posted</span>
                    <span className="font-semibold text-right">
                      {property.date ? new Date(property.date).toLocaleDateString() : "N/A"}
                    </span>
                  </div>
                </div>
              </div>

              <div className="rounded-3xl border border-slate-200 bg-white p-6">
                <h4 className="text-lg font-semibold tracking-tight">Similar Properties</h4>

                <div className="mt-4 space-y-4">
                  {similar.length > 0 ? (
                    similar.map((item) => <PropertyCard key={item.pid} property={item} />)
                  ) : (
                    <div className="text-sm text-slate-500">
                      No similar properties found at the moment.
                    </div>
                  )}
                </div>
              </div>
            </div>
          </aside>
        </div>
      </Container>
    </main>
  );
}