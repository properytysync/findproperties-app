import { useEffect, useState } from "react";
import type { FormEvent } from "react";
import Container from "../components/Container";
import Loading from "../components/Loading";
import ErrorMessage from "../components/ErrorMessage";
import { api, type FooterContent } from "../lib/api";

export default function Contact() {
  const [footer, setFooter] = useState<FooterContent | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState("");
  const [success, setSuccess] = useState("");

  const [form, setForm] = useState({
    name: "",
    email: "",
    phone: "",
    subject: "",
    message: "",
  });

  useEffect(() => {
    async function loadContactInfo() {
      try {
        setLoading(true);
        setError("");
        const data = await api.footerContent(1);
        setFooter(data);
      } catch (err) {
        setError(err instanceof Error ? err.message : "Failed to load contact info");
      } finally {
        setLoading(false);
      }
    }

    loadContactInfo();
  }, []);

  async function handleSubmit(e: FormEvent) {
    e.preventDefault();

    try {
      setError("");
      setSuccess("");

      await api.submitContact(form);

      setSuccess("Message sent successfully.");
      setForm({
        name: "",
        email: "",
        phone: "",
        subject: "",
        message: "",
      });
    } catch (err) {
      setError(err instanceof Error ? err.message : "Failed to send message");
    }
  }

  if (loading) return <Loading />;

  const address = footer?.address || "";
  const mapSrc = `https://www.google.com/maps?q=${encodeURIComponent(address)}&output=embed`;

  return (
    <main>
      <section className="bg-slate-900 py-16 text-white">
        <Container>
          <div className="max-w-3xl">
            <h1 className="text-4xl md:text-5xl font-semibold tracking-tight">Contact</h1>
            <div className="mt-4 text-sm text-white/70">Home / Contact</div>
          </div>
        </Container>
      </section>

      <section className="py-16">
        <Container>
          <div className="grid gap-10 lg:grid-cols-2">
            <div className="rounded-3xl border border-slate-200 bg-white p-6 md:p-8">
              <h2 className="text-2xl font-semibold tracking-tight">Get In Touch</h2>

              {error ? <div className="mt-4"><ErrorMessage message={error} /></div> : null}
              {success ? (
                <div className="mt-4 rounded-2xl border border-green-200 bg-green-50 p-4 text-green-700">
                  {success}
                </div>
              ) : null}

              <form className="mt-6 grid gap-4" onSubmit={handleSubmit}>
                <input
                  className="rounded-xl border border-slate-200 px-4 py-3 outline-none focus:border-slate-950"
                  placeholder="Your name"
                  value={form.name}
                  onChange={(e) => setForm((v) => ({ ...v, name: e.target.value }))}
                  required
                />

                <div className="grid gap-4 md:grid-cols-2">
                  <input
                    className="rounded-xl border border-slate-200 px-4 py-3 outline-none focus:border-slate-950"
                    placeholder="Email address"
                    type="email"
                    value={form.email}
                    onChange={(e) => setForm((v) => ({ ...v, email: e.target.value }))}
                    required
                  />

                  <input
                    className="rounded-xl border border-slate-200 px-4 py-3 outline-none focus:border-slate-950"
                    placeholder="Phone number"
                    value={form.phone}
                    onChange={(e) => setForm((v) => ({ ...v, phone: e.target.value }))}
                    required
                  />
                </div>

                <input
                  className="rounded-xl border border-slate-200 px-4 py-3 outline-none focus:border-slate-950"
                  placeholder="Subject"
                  value={form.subject}
                  onChange={(e) => setForm((v) => ({ ...v, subject: e.target.value }))}
                  required
                />

                <textarea
                  className="min-h-[150px] rounded-xl border border-slate-200 px-4 py-3 outline-none focus:border-slate-950"
                  placeholder="Write your message..."
                  value={form.message}
                  onChange={(e) => setForm((v) => ({ ...v, message: e.target.value }))}
                  required
                />

                <label className="flex items-center gap-3 rounded-xl border border-dashed border-slate-300 bg-slate-50 px-4 py-3 text-sm text-slate-600">
                  <input type="checkbox" required />
                  Confirm you are Human
                </label>

                <button
                  type="submit"
                  className="inline-flex items-center justify-center rounded-xl bg-slate-950 px-5 py-3 text-sm font-semibold text-white hover:bg-slate-800"
                >
                  Send Message
                </button>
              </form>
            </div>

            <div>
              <div className="rounded-3xl border border-slate-200 bg-white p-6 md:p-8">
                <h3 className="text-xl font-semibold tracking-tight">Contact Information</h3>
                <p className="mt-4 text-slate-600">{footer?.welcome_message}</p>

                <div className="mt-6 grid gap-4 text-slate-700">
                  <div>
                    <div className="text-sm font-semibold text-slate-900">Address</div>
                    <div className="mt-1">{footer?.address}</div>
                  </div>

                  <div>
                    <div className="text-sm font-semibold text-slate-900">Email</div>
                    <div className="mt-1">{footer?.email}</div>
                  </div>

                  <div>
                    <div className="text-sm font-semibold text-slate-900">Phone</div>
                    <div className="mt-1">{footer?.phone_number}</div>
                  </div>
                </div>

                {address ? (
                  <div className="mt-6">
                    <a
                      className="inline-flex items-center justify-center rounded-xl border border-slate-200 px-4 py-2 text-sm font-semibold hover:bg-slate-50"
                      target="_blank"
                      rel="noreferrer"
                      href={`https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(address)}`}
                    >
                      Open in Google Maps
                    </a>
                  </div>
                ) : null}
              </div>
            </div>
          </div>
        </Container>
      </section>

      <section className="pb-16">
        <Container>
          <div className="overflow-hidden rounded-3xl border border-slate-200">
            <iframe
              src={mapSrc}
              style={{ width: "100%", height: "450px", border: 0 }}
              loading="lazy"
              referrerPolicy="no-referrer-when-downgrade"
              allowFullScreen
              title="Map"
            />
          </div>
        </Container>
      </section>
    </main>
  );
}