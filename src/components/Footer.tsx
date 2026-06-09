import { Link } from "react-router-dom";
import { useEffect, useState } from "react";
import Container from "./Container";
import { api, type FooterContent } from "../lib/api";

const fallbackFooter: FooterContent = {
  id: "1",
  company_name: "PropertySync Skyline",
  welcome_message:
    "Whether you’re buying, renting, or investing, we provide carefully verified listings and expert guidance.",
  address: "Lekki, Lagos",
  phone_number: "+234 800 000 0000",
  email: "hello@website.com",
  facebook_url: "",
  instagram_url: "",
  linkedin_url: "",
  twitter_url: "",
  logo_path: null,
  logo_url: null,
};

export default function Footer() {
  const [footer, setFooter] = useState<FooterContent>(fallbackFooter);

  useEffect(() => {
    async function loadFooter() {
      try {
        const data = await api.footerContent(1);
        setFooter(data);
      } catch (err) {
        console.error("Footer load failed:", err);
      }
    }

    loadFooter();
  }, []);

  return (
    <footer className="border-t border-slate-100 bg-white">
      <Container>
        <div className="grid gap-10 py-14 lg:grid-cols-4">
          <div className="lg:col-span-2">
            {/* Logo */}
            <div className="mb-4">
              {footer.logo_url ? (
                <img
                  src={footer.logo_url}
                  alt={footer.company_name}
                  className="h-14 w-auto object-contain"
                />
              ) : (
                <div className="text-lg font-semibold tracking-tight">
                  {footer.company_name}
                </div>
              )}
            </div>

            {/* Company Name */}
            <div className="text-lg font-semibold tracking-tight">
              {footer.company_name}
            </div>

            {/* Welcome Message */}
            <p className="mt-3 max-w-xl text-sm leading-relaxed text-slate-600">
              {footer.welcome_message}
            </p>

            {/* Email and Phone */}
            <div className="mt-6 flex flex-wrap gap-3">
              <a
                className="rounded-xl border border-slate-200 px-4 py-2 text-sm font-semibold hover:bg-slate-50"
                href={`mailto:${footer.email}`}
              >
                {footer.email}
              </a>

              <a
                className="rounded-xl border border-slate-200 px-4 py-2 text-sm font-semibold hover:bg-slate-50"
                href={`tel:${footer.phone_number}`}
              >
                {footer.phone_number}
              </a>
            </div>

            {/* Social Links */}
            <div className="mt-6 flex flex-wrap gap-4 text-sm text-slate-600">
              {footer.facebook_url && (
                <a
                  href={footer.facebook_url}
                  target="_blank"
                  rel="noopener noreferrer"
                  className="hover:text-slate-950"
                >
                  Facebook
                </a>
              )}

              {footer.instagram_url && (
                <a
                  href={footer.instagram_url}
                  target="_blank"
                  rel="noopener noreferrer"
                  className="hover:text-slate-950"
                >
                  Instagram
                </a>
              )}

              {footer.linkedin_url && (
                <a
                  href={footer.linkedin_url}
                  target="_blank"
                  rel="noopener noreferrer"
                  className="hover:text-slate-950"
                >
                  LinkedIn
                </a>
              )}

              {footer.twitter_url && (
                <a
                  href={footer.twitter_url}
                  target="_blank"
                  rel="noopener noreferrer"
                  className="hover:text-slate-950"
                >
                  Twitter
                </a>
              )}
            </div>
          </div>

          <div>
            <div className="text-sm font-semibold">Quick Links</div>
            <div className="mt-4 grid gap-2 text-sm text-slate-600">
              <Link className="hover:text-slate-950" to="/about">
                About Us
              </Link>
              <Link className="hover:text-slate-950" to="/buy">
                Buy
              </Link>
              <Link className="hover:text-slate-950" to="/rent">
                Rent
              </Link>
              <Link className="hover:text-slate-950" to="/contact">
                Contact
              </Link>
            </div>
          </div>

          <div>
            <div className="text-sm font-semibold">Contact</div>
            <div className="mt-4 grid gap-3 text-sm text-slate-600">
              <div>{footer.address}</div>
              <div>9 AM – 5 PM (Mon-Sat)</div>
              <div className="font-semibold text-slate-900">
                {footer.phone_number}
              </div>
            </div>
          </div>
        </div>

        <div className="flex flex-col gap-2 border-t border-slate-100 py-6 text-xs text-slate-500 sm:flex-row sm:items-center sm:justify-between">
          <div>
            Copyright © {new Date().getFullYear()} {footer.company_name}. All rights reserved.
          </div>
          <div className="flex gap-4">
            <a href="#" className="hover:text-slate-950">
              Privacy
            </a>
            <a href="#" className="hover:text-slate-950">
              Terms
            </a>
          </div>
        </div>
      </Container>
    </footer>
  );
}