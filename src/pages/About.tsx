import { useEffect, useMemo, useState } from "react";
import Container from "../components/Container";
import Loading from "../components/Loading";
import ErrorMessage from "../components/ErrorMessage";
import { api, type AboutContent, type ChooseItem, type ChooseUs } from "../lib/api";

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

export default function About() {
  const [about, setAbout] = useState<AboutContent | null>(null);
  const [chooseSection, setChooseSection] = useState<ChooseUs | null>(null);
  const [chooseItems, setChooseItems] = useState<ChooseItem[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState("");

  const videoEmbed = useMemo(() => getYouTubeEmbed(about?.video_url), [about?.video_url]);

  useEffect(() => {
    async function loadAbout() {
      try {
        setLoading(true);
        setError("");

        const [aboutData, chooseSections] = await Promise.all([
          api.about(),
          api.chooseUs(),
        ]);

        setAbout(aboutData);

        if (chooseSections.length > 0) {
          const section = chooseSections[0];
          setChooseSection(section);

          const items = await api.chooseItems(section.id);
          setChooseItems(items);
        }
      } catch (err) {
        setError(err instanceof Error ? err.message : "Failed to load about page");
      } finally {
        setLoading(false);
      }
    }

    loadAbout();
  }, []);

  if (loading) return <Loading />;
  if (error) return <ErrorMessage message={error} />;

  const title = about?.title || "About Us";
  const subtitle = about?.subtitle || title;
  const years = Number(about?.years_experience || 20);

  return (
    <main>
      <section className="bg-slate-900 py-16 text-white">
        <Container>
          <div className="max-w-3xl">
            <h1 className="text-4xl md:text-5xl font-semibold tracking-tight">{title}</h1>
            <div className="mt-4 text-sm text-white/70">Home / {title}</div>
          </div>
        </Container>
      </section>

      <section className="py-16">
        <Container>
          <div className="grid gap-10 lg:grid-cols-2 lg:items-center">
            <div>
              {about?.image_url ? (
                <img
                  src={about.image_url}
                  alt={title}
                  className="w-full rounded-3xl border border-slate-200 object-cover"
                />
              ) : (
                <div className="w-full h-[420px] rounded-3xl bg-slate-200" />
              )}
            </div>

            <div>
              <div className="text-sm font-semibold uppercase tracking-wider text-slate-500">
                {subtitle}
              </div>

              <h2 className="mt-3 text-4xl font-semibold tracking-tight">{title}</h2>

              <div
                className="mt-6 prose prose-slate max-w-none"
                dangerouslySetInnerHTML={{ __html: about?.content || "" }}
              />

              <div className="mt-8 grid gap-6 md:grid-cols-2">
                <div className="rounded-2xl border border-slate-200 p-6">
                  <div className="text-4xl font-bold text-slate-950">{years}</div>
                  <div className="mt-2 text-sm font-medium text-slate-600">
                    Years Experience
                  </div>
                </div>

                <div className="rounded-2xl border border-slate-200 overflow-hidden">
                  {about?.image2_url ? (
                    <img
                      src={about.image2_url}
                      alt={`${title} secondary`}
                      className="h-full w-full object-cover"
                    />
                  ) : (
                    <div className="h-[180px] w-full bg-slate-200" />
                  )}
                </div>
              </div>

              {videoEmbed ? (
                <div className="mt-8 overflow-hidden rounded-3xl border border-slate-200">
                  <div className="aspect-video">
                    <iframe
                      src={videoEmbed}
                      title="About video"
                      className="h-full w-full"
                      allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                      allowFullScreen
                    />
                  </div>
                </div>
              ) : null}
            </div>
          </div>
        </Container>
      </section>

      {chooseSection && chooseSection.is_active === "1" ? (
        <section className="bg-slate-50 py-16">
          <Container>
            <div className="text-center max-w-3xl mx-auto">
              <div className="text-sm font-semibold uppercase tracking-wider text-slate-500">
                {chooseSection.title}
              </div>
              <h2 className="mt-3 text-4xl font-semibold tracking-tight">
                {chooseSection.heading}
              </h2>
              <p className="mt-4 text-slate-600 leading-relaxed">
                {chooseSection.description}
              </p>
            </div>

            <div className="mt-10 grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
              {chooseItems.map((item) => (
                <div
                  key={item.id}
                  className="rounded-3xl border border-slate-200 bg-white p-6"
                >
                  <div className="text-lg font-semibold">{item.title}</div>
                  <p className="mt-3 text-sm leading-relaxed text-slate-600">
                    {item.content}
                  </p>
                </div>
              ))}
            </div>
          </Container>
        </section>
      ) : null}
    </main>
  );
}