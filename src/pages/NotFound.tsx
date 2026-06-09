import { Link } from "react-router-dom";
import Container from "../components/Container";

export default function NotFound() {
  return (
    <main className="py-20">
      <Container>
        <h1 className="text-4xl font-semibold">404</h1>
        <p className="mt-3 text-slate-600">Page not found.</p>
        <Link to="/" className="mt-6 inline-flex rounded-xl bg-slate-950 px-5 py-3 text-sm font-semibold text-white">
          Go Home
        </Link>
      </Container>
    </main>
  );
}