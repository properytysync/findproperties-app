import { Routes, Route } from "react-router-dom";
import Layout from "./components/Layout";

import Home from "./pages/Home";
import About from "./pages/About";
import Buy from "./pages/Buy";
import Rent from "./pages/Rent";
import Shortlet from "./pages/Shortlet";
import Contact from "./pages/Contact";
import PropertyDetails from "./pages/PropertyDetails";
import Search from "./pages/Search";
import NotFound from "./pages/NotFound";

export default function App() {
  return (
    <Routes>
      <Route element={<Layout />}>
        <Route path="/" element={<Home />} />
        <Route path="/about" element={<About />} />
        <Route path="/buy" element={<Buy />} />
        <Route path="/rent" element={<Rent />} />
        <Route path="/shortlet" element={<Shortlet />} />
        <Route path="/contact" element={<Contact />} />
        <Route path="/search" element={<Search />} />
        <Route path="/property/:id" element={<PropertyDetails />} />
      </Route>

      <Route path="*" element={<NotFound />} />
    </Routes>
  );
}