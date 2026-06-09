import { Link, NavLink } from "react-router-dom";
import { useEffect, useMemo, useState } from "react";
import { api, type MenuItem, type SiteInfo } from "../lib/api";

const linkBase =
  "text-sm font-semibold tracking-tight text-slate-800 hover:text-slate-950 transition";

const activeClass = "text-slate-950";

function NavItem({
  to,
  children,
}: {
  to: string;
  children: React.ReactNode;
}) {
  return (
    <NavLink
      to={to}
      className={({ isActive }) => `${linkBase} ${isActive ? activeClass : ""}`}
    >
      {children}
    </NavLink>
  );
}

function normalizeMenuUrl(url: string): string {
  const value = (url || "").trim().toLowerCase();

  if (
    value.includes("index") ||
    value === "/" ||
    value === "home" ||
    value === "index.php"
  ) {
    return "/";
  }

  if (value.includes("about")) return "/about";
  if (value.includes("rent")) return "/rent";
  if (value.includes("shortlet")) return "/shortlet";
  if (value.includes("contact")) return "/contact";
  if (value.includes("search")) return "/search";

  if (
    value.includes("property") ||
    value.includes("buy") ||
    value.includes("listing")
  ) {
    return "/buy";
  }

  return "/";
}

const fallbackMenu: MenuItem[] = [
  { id: "1", name: "Home", url: "/" },
  { id: "2", name: "About", url: "/about" },
  { id: "3", name: "Buy", url: "/buy" },
  { id: "4", name: "Rent", url: "/rent" },
  { id: "5", name: "Shortlet", url: "/shortlet" },
  { id: "6", name: "Contact", url: "/contact" },
];

export default function Navigation() {
  const [open, setOpen] = useState(false);
  const [siteInfo, setSiteInfo] = useState<SiteInfo | null>(null);
  const [menuItems, setMenuItems] = useState<MenuItem[]>(fallbackMenu);

  useEffect(() => {
    let mounted = true;

    async function loadNav() {
      try {
        const [site, menu] = await Promise.all([api.siteInfo(1), api.menuItems()]);

        if (!mounted) return;

        setSiteInfo(site);

        if (menu.length) {
          setMenuItems(
            menu.map((m) => ({
              ...m,
              url: normalizeMenuUrl(m.url),
            }))
          );
        }
      } catch (err) {
        console.error("Navigation load failed:", err);
      }
    }

    loadNav();

    return () => {
      mounted = false;
    };
  }, []);

  const menu = useMemo(() => menuItems, [menuItems]);
  const companyName = siteInfo?.company_name?.trim() || "PropertySync Skyline";

  return (
    <header className="sticky top-0 z-50 border-b border-slate-100 bg-white/80 backdrop-blur">
      <div className="h-1 bg-gradient-to-r from-slate-950 via-slate-700 to-slate-950" />

      <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div className="flex h-20 items-center justify-between">
          <Link to="/" className="flex min-w-0 items-center gap-3">
            {siteInfo?.logo_url ? (
              <div className="flex h-14 w-[170px] items-center justify-center overflow-hidden rounded-xl border border-slate-200 bg-white px-3 py-2 shadow-sm">
                <img
                  src={siteInfo.logo_url}
                  alt={companyName}
                  className="max-h-full max-w-full object-contain"
                />
              </div>
            ) : (
              <div className="grid h-12 w-12 place-items-center rounded-xl bg-slate-950 font-bold text-white shadow-sm">
                PS
              </div>
            )}

            <div className="hidden leading-tight sm:block">
              <div className="text-sm font-semibold tracking-tight text-slate-900">
                {companyName}
              </div>
            </div>
          </Link>

          <nav className="hidden items-center gap-8 lg:flex">
            {menu.map((m) => (
              <NavItem key={`${m.id}-${m.name}`} to={m.url}>
                {m.name}
              </NavItem>
            ))}

            <Link
              to="/contact"
              className="inline-flex items-center justify-center rounded-xl bg-slate-950 px-4 py-2 text-sm font-semibold text-white transition hover:bg-slate-800"
            >
              Get A Quote
            </Link>
          </nav>

          <button
            className="inline-flex items-center justify-center rounded-xl border border-slate-200 px-3 py-2 text-sm font-semibold lg:hidden"
            onClick={() => setOpen((v) => !v)}
            type="button"
            aria-expanded={open}
            aria-label={open ? "Close menu" : "Open menu"}
          >
            {open ? "Close" : "Menu"}
          </button>
        </div>

        {open ? (
          <div className="pb-4 lg:hidden">
            <div className="rounded-2xl border border-slate-100 bg-white p-3 shadow-sm">
              <div className="grid gap-2">
                {menu.map((m) => (
                  <NavLink
                    key={`${m.id}-${m.name}`}
                    to={m.url}
                    onClick={() => setOpen(false)}
                    className="block rounded-xl px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50 hover:text-slate-950"
                  >
                    {m.name}
                  </NavLink>
                ))}

                <NavLink
                  to="/contact"
                  onClick={() => setOpen(false)}
                  className="mt-2 inline-flex items-center justify-center rounded-xl bg-slate-950 px-4 py-2 text-sm font-semibold text-white"
                >
                  Get A Quote
                </NavLink>
              </div>
            </div>
          </div>
        ) : null}
      </div>
    </header>
  );
}