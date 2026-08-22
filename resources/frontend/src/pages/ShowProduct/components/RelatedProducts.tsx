import React, { useState, useRef } from 'react';
import { Swiper, SwiperSlide } from 'swiper/react';
import { Navigation, Pagination } from 'swiper/modules';
import type { Swiper as SwiperType } from 'swiper';

// Import Swiper styles
import 'swiper/css';
import 'swiper/css/navigation';
import 'swiper/css/pagination';

import ProductCard from '../../../components/ProductCard';
import type { IProductCard } from '../../../types';
import ProductCardPlaceholder from '../../../components/ProductCardPlaceholder';
import { useTranslation } from 'react-i18next';

interface RelatedProductsProps {
  relatedProducts: IProductCard[];
  isLoading?: boolean;
  title?: string;
}

export const RelatedProducts: React.FC<RelatedProductsProps> = ({
  relatedProducts,
  isLoading,
  title
}) => {
  const { t } = useTranslation(['showProduct']);
  const prevRef = useRef<HTMLButtonElement>(null);
  const nextRef = useRef<HTMLButtonElement>(null);
  const swiperRef = useRef<SwiperType | null>(null);

  const [isBeginning, setIsBeginning] = useState(true);
  const [isEnd, setIsEnd] = useState(false);
  const isRTL = document.documentElement.dir === 'rtl';

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between mb-6">
        <div className="flex items-center gap-x-2">
          <h2 className="text-2xl font-bold text-gray-900 dark:text-slate-100">
            {title || t('similarProducts')}
          </h2>
          <div className="flex items-center space-x-2">
            <button
              ref={prevRef}
              className={`bg-white dark:bg-[#151D2C] border border-gray-200 dark:border-slate-700 rounded-full shadow-sm w-8 h-8 flex items-center justify-center transition-colors ${isBeginning
                  ? 'opacity-40 cursor-not-allowed'
                  : 'hover:bg-gray-100 dark:hover:bg-slate-800 cursor-pointer'
                }`}
              aria-label="Previous Slide"
              aria-disabled={isBeginning}
            >
              <svg xmlns="http://www.w3.org/2000/svg" className={`h-4 w-4 ${isBeginning ? 'text-gray-300 dark:text-slate-600' : 'text-gray-600 dark:text-slate-300'}`} fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d={isRTL ? "M9 5l7 7-7 7" : "M15 19l-7-7 7-7"} />
              </svg>
            </button>
            <button
              ref={nextRef}
              className={`bg-white dark:bg-[#151D2C] border border-gray-200 dark:border-slate-700 rounded-full shadow-sm w-8 h-8 flex items-center justify-center transition-colors ${isEnd
                  ? 'opacity-40 cursor-not-allowed'
                  : 'hover:bg-gray-100 dark:hover:bg-slate-800 cursor-pointer'
                }`}
              aria-label="Next Slide"
              aria-disabled={isEnd}
            >
              <svg xmlns="http://www.w3.org/2000/svg" className={`h-4 w-4 ${isEnd ? 'text-gray-300 dark:text-slate-600' : 'text-gray-600 dark:text-slate-300'}`} fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d={isRTL ? "M15 19l-7-7 7-7" : "M9 5l7 7-7 7"} />
              </svg>
            </button>
          </div>
        </div>
      </div>

      <div className="relative w-full max-w-full">
        <Swiper
          modules={[Navigation, Pagination]}
          spaceBetween={20}
          slidesPerView={4}
          slidesPerGroup={4}
          watchSlidesProgress={true}
          onSwiper={(swiper) => {
            swiperRef.current = swiper;
            setIsBeginning(swiper.isBeginning);
            setIsEnd(swiper.isEnd);
          }}
          onBeforeInit={(swiper) => {
            // @ts-ignore
            swiper.params.navigation.prevEl = prevRef.current;
            // @ts-ignore
            swiper.params.navigation.nextEl = nextRef.current;
          }}
          onSlideChange={(swiper: SwiperType) => {
            setIsBeginning(swiper.isBeginning);
            setIsEnd(swiper.isEnd);
          }}
          navigation={{
            prevEl: prevRef.current,
            nextEl: nextRef.current,
          }}
          pagination={{
            clickable: true,
            bulletActiveClass: 'swiper-pagination-bullet-active bg-[#B12C2E]',
          }}
          breakpoints={{
            0: { slidesPerView: 1, slidesPerGroup: 1 },
            500: { slidesPerView: 2, slidesPerGroup: 2 },
            1024: { slidesPerView: 3, slidesPerGroup: 3 },
            1280: { slidesPerView: 4, slidesPerGroup: 4 },
          }}
        >
          {isLoading
            ? Array.from({ length: 4 }).map((_, index) => (
              <SwiperSlide key={`placeholder-related-${index}`}>
                <ProductCardPlaceholder />
              </SwiperSlide>
            ))
            : Array.isArray(relatedProducts) && relatedProducts.map(product => (
              <SwiperSlide key={product.id}>
                <ProductCard {...product} />
              </SwiperSlide>
            ))}
        </Swiper>
      </div>

      <style>{`
        .swiper-pagination {
          position: relative;
          bottom: 0 !important;
          margin-top: 1.5rem;
        }
        
        .swiper-pagination-bullet {
          width: 8px;
          height: 8px;
          background-color: #D1D5DB;
          opacity: 1;
          margin: 0 4px !important;
        }
        
        .swiper-pagination-bullet-active {
          background-color: #B12C2E !important;
        }
      `}</style>
    </div>
  );
};
