import { useEffect, useState } from "react";
import Container from "../components/Container";
import SectionHeader from "../components/SectionHeader";
import PropertyCard from "../components/PropertyCard";
import Loading from "../components/Loading";
import ErrorMessage from "../components/ErrorMessage";
import { api, type Property } from "../lib/api";

export default function Buy() {
  const [properties, setProperties] = useState<Property[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState("");

  useEffect(() => {
    async function loadBuy() {
      try {
        setLoading(true);
        setError("");
        const rows = await api.properties({ stype: "sale", limit: 24 });
        setProperties(rows.data || []);
      } catch (err) {
        setError(err instanceof Error ? err.message : "Failed to load properties");
      } finally {
        setLoading(false);
      }
    }

    loadBuy();
  }, []);

  if (loading) return <Loading />;
  if (error) return <ErrorMessage message={error} />;

  return (
    <main className="py-14">
      <Container>
        <SectionHeader eyebrow="Properties" title="Buy" />
        <p className="mt-5 text-slate-600">
          Explore properties available for sale.
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