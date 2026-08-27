import React, { useEffect, useState } from 'react';
import MaxWidthWrapper from '../../components/MaxWidthWrapper';
import HeroSection from './components/HeroSection';
import CategoryCard from '../../components/CategoryCard';
import GenericProductSlider from './components/GenericProductSlider';
import AboutUsSection from './components/AboutUsSection';
import HowItWorksSection from './components/HowItWorksSection';
import WhyChooseUsSection from './components/WhyChooseUsSection';
import FAQsSection from './components/FAQsSection';
import ContactUsSection from './components/ContactUsSection';
import PaymentStatusPopup from '../../components/PaymentStatusPopup';
import { useTranslation } from 'react-i18next';
import type { IIndustrialCategory, IProductCard } from '../../types';
import ApiClient from '../../ApiClient';
import FooterSection from '../../components/FooterSection';
import CategoryCardPlaceholder from '../../components/CategoryCardPlaceholder';

import { clearCart } from '../../store/slices/cartSlice';
import { setCoupon } from '../../store/slices/couponSlice';
import { useDispatch, useSelector } from 'react-redux';
import type { RootState } from '../../store/store';

interface ITopCategoryProducts {
  category: IIndustrialCategory;
  products: IProductCard[];
}

const HomePage: React.FC = () => {
  const { t } = useTranslation(['home', 'common']);
  const dispatch = useDispatch();
  const [categories, setCategories] = useState<IIndustrialCategory[]>([]);
  const [topCategoriesProducts, setTopCategoriesProducts] = useState<ITopCategoryProducts[]>([]);
  const [isLoadingContent, setIsLoadingContent] = useState<boolean>(true);
  const [paymentStatus, setPaymentStatus] = useState<'success' | 'cancel' | null>(null);

  const [userRecommendations, setUserRecommendations] = useState<IProductCard[]>([]);
  const [isLoadingRecommendations, setIsLoadingRecommendations] = useState<boolean>(false);
  const isAuth = useSelector((state: RootState) => state?.auth?.isAuth);

  useEffect(() => {
    const fetchHomePageData = async () => {
      setIsLoadingContent(true);
      try {
        const response = await ApiClient.products.getHomeProducts();
        setCategories(response.data.data.industrial_categories);
        setTopCategoriesProducts(response.data.data.top_categories_products);
      } catch (error) {
        console.error('Error fetching home page data:', error);
        setCategories([]);
        setTopCategoriesProducts([]);
      } finally {
        setIsLoadingContent(false);
      }
    };
    fetchHomePageData();
  }, []);

  useEffect(() => {
    const fetchRecommendations = async () => {
      if (!isAuth) {
        setUserRecommendations([]);
        return;
      }

      setIsLoadingRecommendations(true);
      try {
        const response = await ApiClient.recommendations.getForUser(8);
        if (response.data.data) {
          setUserRecommendations(response.data.data);
        }
      } catch (error) {
        console.error('Error fetching recommendations:', error);
      } finally {
        setIsLoadingRecommendations(false);
      }
    };

    fetchRecommendations();
  }, [isAuth]);

  useEffect(() => {
    // Check for payment status in URL parameters
    const urlParams = new URLSearchParams(window.location.search);
    const status = urlParams.get('status');

    if (status === 'success' || status === 'cancel') {
      setPaymentStatus(status);

      if (status === 'success') {
        dispatch(clearCart());
        dispatch(setCoupon({ code: '', discount: 0, isApplied: false, error: '' }));
      }

      // Clean the URL by removing the status parameter
      const newUrl = new URL(window.location.href);
      newUrl.searchParams.delete('status');
      window.history.replaceState({}, document.title, newUrl.toString());
    }
  }, [dispatch]);

  const handleClosePaymentPopup = () => {
    setPaymentStatus(null);
  };

  return (
    <div className="bg-white dark:bg-[#0B0F17] transition-colors duration-300">
      <MaxWidthWrapper>
        {/* Hero Section with Search */}
        <HeroSection />

        {/* Categories Section */}
        <div className="mb-12 py-8 my-12">
          <h2 className="text-xl mobile-large:text-[32px] font-bold mb-6 text-navy dark:text-slate-100 max-desktop-small:text-center">
            {t('categories.title')}
          </h2>
          <div className="grid mobile-large:grid-cols-2 desktop-small:grid-cols-3 desktop-medium:grid-cols-4 gap-4 mobile-large:gap-5 desktop-small:gap-6">
            {isLoadingContent
              ? Array.from({ length: 8 }).map((_, index) => (
                <CategoryCardPlaceholder key={`placeholder-${index}`} />
              ))
              : categories?.length > 0
                ? categories.slice(0, 8).map((category) => (
                  <CategoryCard
                    key={category.id}
                    name={category.name}
                    image={category.image}
                    slug={category.slug}
                  />
                ))
                : null}
          </div>
        </div>

        {/* Recommended for you Section (Only if authenticated and has recommendations) */}
        {(isLoadingRecommendations || userRecommendations.length > 0) && isAuth && (
          <GenericProductSlider
            title={t('common:recommendations.forYou', 'Recommended for You')}
            viewAllLink="/all-products"
            products={userRecommendations}
            isLoading={isLoadingRecommendations}
          />
        )}

        {/* Dynamic Sliders based on Top Categories */}
        {isLoadingContent ? (
          <>
            <GenericProductSlider title="..." viewAllLink="#" products={[]} isLoading={true} />
            <GenericProductSlider title="..." viewAllLink="#" products={[]} isLoading={true} />
            <GenericProductSlider title="..." viewAllLink="#" products={[]} isLoading={true} />
          </>
        ) : (
          topCategoriesProducts.map((item, index) => (
            <GenericProductSlider
              key={item.category.id || index}
              title={item.category.name}
              viewAllLink={`/all-products?category=${item.category.slug}`}
              products={item.products}
              isLoading={isLoadingContent}
            />
          ))
        )}

        {/* About us Section */}
        <AboutUsSection />

      </MaxWidthWrapper>

      {/* How it works Section */}
      {/* <HowItWorksSection /> */}

      {/* Why Choose Us Section */}
      <WhyChooseUsSection />

      {/* FAQs Section */}
      <FAQsSection />

      {/* Contact Us Section */}
      <ContactUsSection />

      {/* Footer Section */}
      <FooterSection />

      {/* Payment Status Popup */}
      <PaymentStatusPopup
        status={paymentStatus}
        onClose={handleClosePaymentPopup}
      />
    </div>
  );
};

export default HomePage;
