import React from 'react';
import MaxWidthWrapper from '../../../components/MaxWidthWrapper';
import { useTranslation } from 'react-i18next';

const WhyChooseUsSection: React.FC = () => {
  const { t } = useTranslation('products');

  return (
    <div className="bg-white dark:bg-[#0B0F17] py-12 md:py-16 transition-colors duration-300">
      <MaxWidthWrapper>
        <h2 className="text-2xl md:text-3xl font-bold mb-8 md:mb-10 text-center sm:text-start text-gray-900 dark:text-slate-100">{t('whyChooseUs.title')}</h2>
        
        {/* Desktop and tablet view */}
        <div className="hidden sm:flex flex-col md:flex-row gap-6 md:gap-8 lg:gap-12">
          {/* Bulk Prices */}
          <div className="flex-1 flex flex-col items-start p-6 bg-gray-50/60 dark:bg-[#151D2C] border border-gray-100 dark:border-slate-700/60 rounded-2xl transition-all duration-300">
            <div className="w-12 h-12 rounded-xl bg-red-50 dark:bg-red-950/50 flex items-center justify-center mb-4 text-[#B12C2E] dark:text-[#E03E41]">
              <svg xmlns="http://www.w3.org/2000/svg" className="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
            </div>
            <h3 className="font-bold text-lg text-gray-900 dark:text-slate-100 mb-2">{t('whyChooseUs.bulkPrices.title')}</h3>
            <p className="text-gray-600 dark:text-slate-300 text-sm md:text-base leading-relaxed">
              {t('whyChooseUs.bulkPrices.description')}
            </p>
          </div>
          
          {/* Fast Delivery */}
          <div className="flex-1 flex flex-col items-start p-6 bg-gray-50/60 dark:bg-[#151D2C] border border-gray-100 dark:border-slate-700/60 rounded-2xl transition-all duration-300">
            <div className="w-12 h-12 rounded-xl bg-red-50 dark:bg-red-950/50 flex items-center justify-center mb-4 text-[#B12C2E] dark:text-[#E03E41]">
              <svg xmlns="http://www.w3.org/2000/svg" className="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M13 10V3L4 14h7v7l9-11h-7z" />
              </svg>
            </div>
            <h3 className="font-bold text-lg text-gray-900 dark:text-slate-100 mb-2">{t('whyChooseUs.fastDelivery.title')}</h3>
            <p className="text-gray-600 dark:text-slate-300 text-sm md:text-base leading-relaxed">
              {t('whyChooseUs.fastDelivery.description')}
            </p>
          </div>
          
          {/* Verified Quality */}
          <div className="flex-1 flex flex-col items-start p-6 bg-gray-50/60 dark:bg-[#151D2C] border border-gray-100 dark:border-slate-700/60 rounded-2xl transition-all duration-300">
            <div className="w-12 h-12 rounded-xl bg-red-50 dark:bg-red-950/50 flex items-center justify-center mb-4 text-[#B12C2E] dark:text-[#E03E41]">
              <svg xmlns="http://www.w3.org/2000/svg" className="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
              </svg>
            </div>
            <h3 className="font-bold text-lg text-gray-900 dark:text-slate-100 mb-2">{t('whyChooseUs.verifiedQuality.title')}</h3>
            <p className="text-gray-600 dark:text-slate-300 text-sm md:text-base leading-relaxed">
              {t('whyChooseUs.verifiedQuality.description')}
            </p>
          </div>
        </div>
        
        {/* Mobile view */}
        <div className="sm:hidden space-y-4">
          {/* Bulk Prices */}
          <div className="flex items-start p-4 bg-gray-50/60 dark:bg-[#151D2C] border border-gray-100 dark:border-slate-700/60 rounded-xl">
            <div className="w-10 h-10 rounded-lg bg-red-50 dark:bg-red-950/50 flex items-center justify-center me-4 shrink-0 text-[#B12C2E] dark:text-[#E03E41]">
              <svg xmlns="http://www.w3.org/2000/svg" className="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
            </div>
            <div>
              <h3 className="font-bold text-base text-gray-900 dark:text-slate-100 mb-1">{t('whyChooseUs.bulkPrices.title')}</h3>
              <p className="text-gray-600 dark:text-slate-300 text-xs leading-relaxed">
                {t('whyChooseUs.bulkPrices.description')}
              </p>
            </div>
          </div>
          
          {/* Fast Delivery */}
          <div className="flex items-start p-4 bg-gray-50/60 dark:bg-[#151D2C] border border-gray-100 dark:border-slate-700/60 rounded-xl">
            <div className="w-10 h-10 rounded-lg bg-red-50 dark:bg-red-950/50 flex items-center justify-center me-4 shrink-0 text-[#B12C2E] dark:text-[#E03E41]">
              <svg xmlns="http://www.w3.org/2000/svg" className="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M13 10V3L4 14h7v7l9-11h-7z" />
              </svg>
            </div>
            <div>
              <h3 className="font-bold text-base text-gray-900 dark:text-slate-100 mb-1">{t('whyChooseUs.fastDelivery.title')}</h3>
              <p className="text-gray-600 dark:text-slate-300 text-xs leading-relaxed">
                {t('whyChooseUs.fastDelivery.description')}
              </p>
            </div>
          </div>
          
          {/* Verified Quality */}
          <div className="flex items-start p-4 bg-gray-50/60 dark:bg-[#151D2C] border border-gray-100 dark:border-slate-700/60 rounded-xl">
            <div className="w-10 h-10 rounded-lg bg-red-50 dark:bg-red-950/50 flex items-center justify-center me-4 shrink-0 text-[#B12C2E] dark:text-[#E03E41]">
              <svg xmlns="http://www.w3.org/2000/svg" className="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
              </svg>
            </div>
            <div>
              <h3 className="font-bold text-base text-gray-900 dark:text-slate-100 mb-1">{t('whyChooseUs.verifiedQuality.title')}</h3>
              <p className="text-gray-600 dark:text-slate-300 text-xs leading-relaxed">
                {t('whyChooseUs.verifiedQuality.description')}
              </p>
            </div>
          </div>
        </div>
      </MaxWidthWrapper>
    </div>
  );
};

export default WhyChooseUsSection;
