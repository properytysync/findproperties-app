export type SiteInfo = {
  id: number;
  logoPath: string | null;
  bannerImagePath: string | null;
  currency: string;
  bannerWriteup: string;
  faviconPath: string | null;
  welcomeMessage: string;
  displayMode: string;
  pageType: string;
  paystackPublicKey?: string;
  viewingFee?: number;
  enableViewingPayment?: number;
  logoUrl: string | null;
  bannerImageUrl: string | null;
  faviconUrl: string | null;
};

export type FooterContent = {
  id: number;
  companyName: string;
  welcomeMessage: string;
  address: string;
  phoneNumber: string;
  email: string;
  facebookUrl?: string;
  instagramUrl?: string;
  linkedinUrl?: string;
  twitterUrl?: string;
  logoPath?: string | null;
  logoUrl?: string | null;
};

export type MenuItem = {
  id: number;
  name: string;
  url: string;
  visible?: number | string;
};

export type AboutContent = {
  title?: string;
  heading?: string;
  description?: string;
  content?: string;
  image_url?: string | null;
  [key: string]: unknown;
};

export type ChooseUsSection = {
  id: number;
  title: string;
  heading: string;
  description: string;
};

export type ChooseItem = {
  id: number;
  chooseId: number;
  iconClass?: string;
  title: string;
  content: string;
};

export type Agent = {
  id: number;
  name: string;
  role: string;
  rating: string;
  image: string;
  description?: string;
};

export type LocationStat = {
  id: number;
  name: string;
  count: string;
  image?: string | null;
};

export type Property = {
  id: number;
  title: string;
  location: string;
  price: string;
  tag: string;
  image: string;
  description?: string;
  purpose?: string;
  images?: string[];
  mapImages?: string[];
  bedroom?: string;
  bathroom?: string;
  balcony?: string;
  kitchen?: string;
  toilet?: string;
  size?: string;
  raw?: any;
};