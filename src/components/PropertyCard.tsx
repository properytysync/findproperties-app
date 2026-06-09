import { Link } from "react-router-dom";
import type { Property } from "../lib/api";

type Props = {
  property: Property;
};

function formatPrice(value: string | number | null | undefined) {
  if (value === null || value === undefined || value === "") return "₦0";
  const raw = String(value).trim();
  if (/[₦$€£]/.test(raw)) return raw;

  const num = Number(raw.replace(/,/g, ""));
  if (Number.isNaN(num)) return raw;
  return `₦${num.toLocaleString()}`;
}

export default function PropertyCard({ property }: Props) {
  return (
    <Link
      to={`/property/${property.pid}`}
      className="group block overflow-hidden rounded-3xl border border-slate-200 bg-white transition hover:shadow-xl"
    >
      <div className="relative">
        {property.images?.[0] ? (
          <img
            src={property.images[0]}
            alt={property.title}
            className="h-64 w-full object-cover transition duration-500 group-hover:scale-105"
          />
        ) : (
          <div className="h-64 w-full bg-slate-200" />
        )}

        <div className="absolute left-4 top-4 rounded-full bg-black/70 px-3 py-1 text-xs font-bold text-white">
          {property.stype || property.type || "Property"}
        </div>
      </div>

      <div className="p-5">
        <h3 className="text-lg font-extrabold text-slate-900">{property.title}</h3>
        <p className="mt-1 text-sm text-slate-600">
          {[property.location, property.city, property.state].filter(Boolean).join(", ")}
        </p>
        <p className="mt-4 text-xl font-black text-slate-900">{formatPrice(property.price)}</p>
      </div>
    </Link>
  );
}