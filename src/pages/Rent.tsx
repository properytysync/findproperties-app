import { useEffect, useState } from "react";
import Container from "../components/Container";
import SectionHeader from "../components/SectionHeader";
import PropertyCard from "../components/PropertyCard";
import Loading from "../components/Loading";
import ErrorMessage from "../components/ErrorMessage";
import { api, type Property } from "../lib/api";

export default function Rent() {
  const [properties, setProperties] = useState<Property[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState("");

  useEffect(() => {
    async function loadRent() {
      try {
        setLoading(true);
        setError("");
        const rows = await api.properties({ stype: "rent", limit: 24 });
        setProperties(rows.data || []);
      } catch (err) {
        setError(err instanceof Error ? err.message : "Failed to load rentals");
      } finally {
        setLoading(false);
      }
    }

    loadRent();
  }, []);

  if (loading) return <Loading />;
  if (error) return <ErrorMessage message={error} />;

  return (
    <main className="py-14">
      <Container>
        <SectionHeader eyebrow="Properties" title="Rent" />
        <p className="mt-5 text-slate-600">
          Explore rental properties.
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