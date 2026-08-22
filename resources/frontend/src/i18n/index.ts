import i18n from 'i18next';
import { initReactI18next } from 'react-i18next';
import LanguageDetector from 'i18next-browser-languagedetector';

import enCommon from './locales/en/common.json';
import enHome from './locales/en/home.json';
import enAuth from './locales/en/auth.json';
import enProducts from './locales/en/products.json';
import enShop from './locales/en/shop.json';
import enCart from './locales/en/cart.json';
import enCustomProduct from './locales/en/customProduct.json';
import enDashboard from './locales/en/dashboard.json';
import enShowProduct from './locales/en/showProduct.json';
import enCheckout from './locales/en/checkout.json';
import enProfile from './locales/en/profile.json';

import arCommon from './locales/ar/common.json';
import arHome from './locales/ar/home.json';
import arAuth from './locales/ar/auth.json';
import arProducts from './locales/ar/products.json';
import arShop from './locales/ar/shop.json';
import arCart from './locales/ar/cart.json';
import arCustomProduct from './locales/ar/customProduct.json';
import arDashboard from './locales/ar/dashboard.json';
import arShowProduct from './locales/ar/showProduct.json';
import arCheckout from './locales/ar/checkout.json';
import arProfile from './locales/ar/profile.json';

// Helper function to set document direction based on language
export const setDocumentDirection = (language: string) => {
  document.documentElement.dir = language === 'ar' ? 'rtl' : 'ltr';
  document.documentElement.lang = language;
};

// Helper function to detect initial language from cookies / localStorage
export const getInitialLanguage = (): string => {
  if (typeof document === 'undefined') return 'ar';
  
  // 1. Check user_locale cookie
  const match = document.cookie.match(/(?:^|;\s*)user_locale=([^;]+)/);
  if (match && (match[1] === 'ar' || match[1] === 'en')) {
    return match[1];
  }
  
  // 2. Check localStorage
  try {
    const savedLocal = localStorage.getItem('i18nextLng') || localStorage.getItem('user_locale');
    if (savedLocal && (savedLocal === 'ar' || savedLocal === 'en')) {
      return savedLocal;
    }
  } catch (e) {
    // LocalStorage access restricted
  }
  
  return document.documentElement.lang || 'ar';
};

const initialLanguage = getInitialLanguage();
setDocumentDirection(initialLanguage);

// Initialize i18next
i18n
  .use(LanguageDetector)
  .use(initReactI18next)
  .init({
    resources: {
      en: {
        common: enCommon,
        home: enHome,
        auth: enAuth,
        products: enProducts,
        shop: enShop,
        cart: enCart,
        customProduct: enCustomProduct,
        dashboard: enDashboard.dashboard,
        showProduct: enShowProduct,
        checkout: enCheckout,
        profile: enProfile,
      },
      ar: {
        common: arCommon,
        home: arHome,
        auth: arAuth,
        products: arProducts,
        shop: arShop,
        cart: arCart,
        customProduct: arCustomProduct,
        dashboard: arDashboard.dashboard,
        showProduct: arShowProduct,
        checkout: arCheckout,
        profile: arProfile,
      },
    },
    lng: initialLanguage,
    fallbackLng: 'en',
    interpolation: {
      escapeValue: false, // React already escapes values
    },
    detection: {
      order: ['cookie', 'localStorage', 'htmlTag', 'navigator'],
      lookupCookie: 'user_locale',
      lookupLocalStorage: 'i18nextLng',
      caches: ['localStorage', 'cookie'],
    },
    ns: ['common', 'home', 'auth', 'products', 'shop', 'cart', 'customProduct', 'dashboard', 'showProduct', 'checkout', 'profile'],
    defaultNS: 'common',
  });

export default i18n;
