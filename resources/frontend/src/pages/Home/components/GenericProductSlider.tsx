import React, { useState, useRef, useEffect } from 'react';
import { Link } from 'react-router-dom';
import { Swiper, SwiperSlide } from 'swiper/react';
import { Navigation, Pagination } from 'swiper/modules';
import type { Swiper as SwiperType } from 'swiper';
import { useTranslation } from 'react-i18next';

// Import Swiper styles
import 'swiper/css';
import 'swiper/css/navigation';
import 'swiper/css/pagination';

import ProductCard from '../../../components/ProductCard';
import ProductCardPlaceholder from '../../../components/ProductCardPlaceholder';
import type { IProductCard } from '../../../types';

interface IGenericProductSliderProps {
    title: string;
    viewAllLink: string;
    products: IProductCard[];
    isLoading: boolean;
}

const GenericProductSlider: React.FC<IGenericProductSliderProps> = ({ title, viewAllLink, products, isLoading }) => {
    const { t, i18n } = useTranslation(['common']);
    const prevRef = useRef<HTMLButtonElement>(null);
    const nextRef = useRef<HTMLButtonElement>(null);
    const isRTL = i18n.language === 'ar';
    const swiperRef = useRef<SwiperType | null>(null);

    const [isBeginning, setIsBeginning] = useState(true);
    const [isEnd, setIsEnd] = useState(false);

    const updateNavigationState = (swiper: SwiperType) => {
        setIsBeginning(swiper.isBeginning);
        setIsEnd(swiper.isEnd);
    };

    useEffect(() => {
        if (swiperRef.current) {
            const swiper = swiperRef.current;
            swiper.changeLanguageDirection(isRTL ? 'rtl' : 'ltr');
            swiper.update();
            updateNavigationState(swiper);
        }
    }, [isRTL]);

    useEffect(() => {
        if (swiperRef.current) {
            const swiper = swiperRef.current;
            swiper.update();
            updateNavigationState(swiper);
        }
    }, [products, isLoading]);

    return (
        <div className="mb-16">
            <div className="flex items-center justify-between mb-6">
                <div className="flex items-center gap-x-2">
                    <h2 className="text-xl mobile-large:text-[32px] font-bold text-navy dark:text-slate-100 mr-4">
                        {title}
                    </h2>
                    <div className="flex items-center space-x-2">
                        <button
                            ref={prevRef}
                            disabled={isBeginning}
                            className={`bg-white dark:bg-[#151D2C] border border-gray-200 dark:border-slate-700 rounded-full shadow-sm w-8 h-8 flex items-center justify-center transition-colors ${isBeginning
                                    ? 'opacity-40 cursor-not-allowed'
                                    : 'hover:bg-gray-100 dark:hover:bg-slate-800 cursor-pointer'
                                }`}
                            aria-label={t('common:buttons.previousSlide')}
                            aria-disabled={isBeginning}
                        >
                            {isRTL ? (
                                <svg xmlns="http://www.w3.org/2000/svg" className={`h-4 w-4 ${isBeginning ? 'text-gray-300 dark:text-slate-600' : 'text-gray-600 dark:text-slate-300'}`} fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 5l7 7-7 7" />
                                </svg>
                            ) : (
                                <svg xmlns="http://www.w3.org/2000/svg" className={`h-4 w-4 ${isBeginning ? 'text-gray-300 dark:text-slate-600' : 'text-gray-600 dark:text-slate-300'}`} fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 19l-7-7 7-7" />
                                </svg>
                            )}
                        </button>
                        <button
                            ref={nextRef}
                            disabled={isEnd}
                            className={`bg-white dark:bg-[#151D2C] border border-gray-200 dark:border-slate-700 rounded-full shadow-sm w-8 h-8 flex items-center justify-center transition-colors ${isEnd
                                    ? 'opacity-40 cursor-not-allowed'
                                    : 'hover:bg-gray-100 dark:hover:bg-slate-800 cursor-pointer'
                                }`}
                            aria-label={t('common:buttons.nextSlide')}
                            aria-disabled={isEnd}
                        >
                            {isRTL ? (
                                <svg xmlns="http://www.w3.org/2000/svg" className={`h-4 w-4 ${isEnd ? 'text-gray-300 dark:text-slate-600' : 'text-gray-600 dark:text-slate-300'}`} fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 19l-7-7 7-7" />
                                </svg>
                            ) : (
                                <svg xmlns="http://www.w3.org/2000/svg" className={`h-4 w-4 ${isEnd ? 'text-gray-300 dark:text-slate-600' : 'text-gray-600 dark:text-slate-300'}`} fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 5l7 7-7 7" />
                                </svg>
                            )}
                        </button>
                    </div>
                </div>
                <Link to={viewAllLink} className="text-yellow-500 dark:text-[#E03E41] hover:underline text-sm font-semibold">
                    {t('common:buttons.viewAll')}
                </Link>
            </div>

            <div className="relative w-full max-w-full">
                <Swiper
                    modules={[Navigation, Pagination]}
                    spaceBetween={20}
                    slidesPerView={4}
                    slidesPerGroup={4}
                    watchSlidesProgress={true}
                    dir={isRTL ? 'rtl' : 'ltr'}
                    onSwiper={(swiper) => {
                        swiperRef.current = swiper;
                        swiper.changeLanguageDirection(isRTL ? 'rtl' : 'ltr');
                        swiper.update();
                        updateNavigationState(swiper);
                    }}
                    onBeforeInit={(swiper) => {
                        // @ts-ignore
                        swiper.params.navigation.prevEl = prevRef.current;
                        // @ts-ignore
                        swiper.params.navigation.nextEl = nextRef.current;
                    }}
                    onSlideChange={updateNavigationState}
                    onSlideChangeTransitionEnd={updateNavigationState}
                    onProgress={updateNavigationState}
                    onTouchEnd={updateNavigationState}
                    onReachBeginning={() => setIsBeginning(true)}
                    onReachEnd={() => setIsEnd(true)}
                    onFromEdge={updateNavigationState}
                    onToEdge={updateNavigationState}
                    onActiveIndexChange={updateNavigationState}
                    onResize={updateNavigationState}
                    onBreakpoint={updateNavigationState}
                    navigation={{
                        prevEl: prevRef.current,
                        nextEl: nextRef.current,
                    }}
                    pagination={{
                        clickable: true,
                        bulletActiveClass: 'swiper-pagination-bullet-active bg-yellow-500',
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
                            <SwiperSlide key={`placeholder-product-${index}`}>
                                <ProductCardPlaceholder />
                            </SwiperSlide>
                        ))
                        : products?.map((product) => (
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
          background-color: #F59E0B !important;
        }
      `}</style>
        </div>
    );
};

export default GenericProductSlider;
