import { useEffect, useState } from "react";
import { useSearchParams } from "react-router-dom";
import Container from "../components/Container";
import SectionHeader from "../components/SectionHeader";
import PropertyCard from "../components/PropertyCard";
import Loading from "../components/Loading";
import ErrorMessage from "../components/ErrorMessage";
import { api, type Property } from "../lib/api";

export default function Search() {
  const [searchParams] = useSearchParams();
  const [properties, setProperties] = useState<Property[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState("");

  const q = searchParams.get("q") || "";
  const stype = searchParams.get("stype") || "";

  useEffect(() => {
    async function loadSearch() {
      try {
        setLoading(true);
        setError("");

        const rows = await api.properties({
          q,
          stype,
          limit: 24,
        });

        setProperties(rows.data || []);
      } catch (err) {
        setError(err instanceof Error ? err.message : "Failed to search");
      } finally {
        setLoading(false);
      }
    }

    loadSearch();
  }, [q, stype]);

  if (loading) return <Loading />;
  if (error) return <ErrorMessage message={error} />;

  return (
    <main className="py-14">
      <Container>
        <SectionHeader eyebrow="Search" title="Search Results" />
        <p className="mt-5 text-slate-600">
          {properties.length} result(s) found.
        </p>

        <div className="mt-10 grid gap-6 md:grid-cols-2 xl:grid-cols-3">
          {properties.map((property) => (
            <PropertyCard key={property.pid} property={property as any} />
          ))}
        </div>
      </Container>
    </main>
  );
}