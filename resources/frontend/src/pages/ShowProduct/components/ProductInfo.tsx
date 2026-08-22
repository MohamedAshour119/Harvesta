import React, { useEffect, useState } from 'react';
import type { IProductCard, IProductVariant, VariantRatingData } from '../../../types';
import { useTranslation } from 'react-i18next';
import { StarRating } from '../../../components/StarRating';
import { isVariantAvailable } from '../../../utils/productAvailability';

interface ProductInfoProps {
  product: IProductCard;
  selectedVariant: IProductVariant | null;
  onVariantSelect: (variant: IProductVariant) => void;
  variantRatingData: VariantRatingData | null;
}

export const ProductInfo: React.FC<ProductInfoProps> = ({ product, selectedVariant, onVariantSelect, variantRatingData }) => {
  const { t, i18n } = useTranslation(['showProduct', 'common', 'products']);
  const variantAvailable = isVariantAvailable(selectedVariant);

  const [currentRatingStats, setCurrentRatingStats] = useState<VariantRatingData | null>(null);

  useEffect(() => {
    setCurrentRatingStats(variantRatingData);

    const handleRatingUpdate = (event: Event) => {
      const customEvent = event as CustomEvent<VariantRatingData>;
      setCurrentRatingStats(customEvent.detail);
    };

    document.addEventListener('ratingUpdated', handleRatingUpdate);

    return () => {
      document.removeEventListener('ratingUpdated', handleRatingUpdate);
    };
  }, [variantRatingData]);

  return (
    <div className="space-y-6">
      {/* Rating & Category */}
      <div className="flex items-center space-x-2 gap-3 flex-wrap">
        <div className="flex items-center space-x-2">
          <span className="text-xl font-bold text-gray-900 dark:text-white">{Math.round(currentRatingStats?.average_rating || 0)}</span>
          <StarRating rating={Math.round(currentRatingStats?.average_rating || 0)} allowHalf={false} />
          <span className="text-gray-500 dark:text-slate-400 text-sm">{currentRatingStats?.total_ratings} {t('showProduct:reviews.ratings')}</span>
        </div>

        <nav className="text-sm text-gray-500 dark:text-slate-400">
          <span className="inline-block border border-[#B12C2E]/25 bg-[#B12C2E]/10 dark:bg-red-950/40 px-3 py-1 rounded-full text-xs font-semibold text-[#B12C2E] dark:text-[#E03E41]">
            {product.category?.name}
          </span>
        </nav>
      </div>

      {/* Product Name */}
      <h1 className="text-2xl tablet:text-3xl font-bold text-gray-900 dark:text-slate-100">{product.name}</h1>

      {/* Description */}
      <p className="text-gray-600 dark:text-slate-300 leading-relaxed text-sm md:text-base">{product.description}</p>

      {/* Stock Status */}
      <div className="flex flex-col justify-center gap-y-2 p-4 bg-gray-50 dark:bg-[#151D2C] border border-gray-100 dark:border-slate-800 rounded-xl">
        <div className="flex items-center space-x-2">
          <span className={`inline-block w-3 h-3 rounded-full ${variantAvailable ? 'bg-green-500 animate-pulse' : 'bg-red-500'}`}></span>
          <span className={`font-semibold text-sm ${variantAvailable ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400'}`}>
            {variantAvailable ? t('stockAvailability.inStock') : t('stockAvailability.outOfStock')}
          </span>
        </div>

        {variantAvailable && selectedVariant?.total_quantity! > 0 && selectedVariant?.total_quantity! < 10 && (
          <span className="text-xs font-medium text-[#B12C2E] dark:text-[#E03E41]">
            {t('stockAvailability.lowStock', { count: selectedVariant?.total_quantity! })}
          </span>
        )}

        {/* Minimum Order Info */}
        {(product.min_order_value! > 0 || product.minimum_order_quantity! > 0) && (
          <div className="flex flex-col gap-y-1.5 pt-2 border-t border-gray-200 dark:border-slate-700/60">
            {product.min_order_value! > 0 && (
              <div className="flex items-center space-x-2 text-sm">
                <span className="text-gray-500 dark:text-slate-400">{t('stockAvailability.minOrderPrice')}:</span>
                <span className="font-bold text-gray-900 dark:text-slate-100 flex items-center gap-x-1">
                  {product.min_order_value}
                  <span className="text-xs font-bold mx-1 text-[#B12C2E] dark:text-[#E03E41]">EGP</span>
                </span>
              </div>
            )}
            {product.minimum_order_quantity! > 0 && (
              <div className="flex items-center space-x-2 text-sm">
                <span className="text-gray-500 dark:text-slate-400">{t('stockAvailability.minOrderQty')}:</span>
                <span className="font-bold text-gray-900 dark:text-slate-100">{product.minimum_order_quantity}</span>
              </div>
            )}
          </div>
        )}
      </div>

      {/* Price */}
      <div className="space-y-1">
        {selectedVariant?.discount_percentage !== undefined && selectedVariant?.discount_percentage > 0 && (
          <div className="text-gray-400 dark:text-slate-500 line-through flex items-center text-sm">
            <span className="text-xs font-bold mx-1">EGP</span>
            {selectedVariant?.unit_price_usd}
          </div>
        )}
        <div className="text-3xl font-extrabold text-gray-900 dark:text-white flex items-center">
          <span className="text-lg font-bold mx-1 text-[#B12C2E] dark:text-[#E03E41]">EGP</span>
          {selectedVariant?.price_after_discount}
        </div>
      </div>

      {/* Smart Bundle Offer */}
      {product.smart_bundle && (
        <div className="relative overflow-hidden bg-gradient-to-br from-[#fff8f8] via-[#fff3f3] to-[#f7e1e1] dark:from-[#261517] dark:via-[#1e1315] dark:to-[#171012] border border-[#B12C2E]/20 dark:border-red-900/50 rounded-2xl p-5 shadow-sm group hover:shadow-md transition-all duration-300">
          <div className="absolute top-0 right-0 p-3 opacity-10 group-hover:opacity-20 transition-opacity">
            <span className="text-6xl">✨</span>
          </div>
          <div className="flex items-center gap-4 relative z-10">
            <div className="bg-white dark:bg-slate-900 p-3 rounded-2xl shadow-inner text-2xl border border-gray-100 dark:border-slate-800">
              🎁
            </div>
            <div className="space-y-1">
              <h4 className="font-extrabold text-[#8F2325] dark:text-red-400 text-lg tracking-tight">
                {typeof product.smart_bundle.name === 'string' 
                  ? product.smart_bundle.name 
                  : (product.smart_bundle.name?.[i18n.language] || product.smart_bundle.name?.['ar'] || product.smart_bundle.name?.['en'])}
              </h4>
              <p className="text-[#B12C2E] dark:text-red-300 font-medium text-sm leading-snug">
                {t('products:bundles.offer_details', {
                  count: product.smart_bundle.target_quantity,
                  discount: product.smart_bundle.discount_percentage
                })}
              </p>
            </div>
          </div>
        </div>
      )}

      {/* Variants */}
      {(product.variants?.length && product.variants?.length > 1) && (
        <div className="space-y-2">
          <label className="text-sm font-semibold text-gray-700 dark:text-slate-300">{t('showProduct:variant', 'Variant')}</label>
          <div className="flex items-center space-x-2 flex-wrap gap-2">
            {product.variants?.map((variant) => (
              <button
                key={variant.id}
                type="button"
                onClick={() => onVariantSelect(variant)}
                className={`flex px-4 py-2 cursor-pointer min-w-[40px] items-center justify-center rounded-xl font-medium text-sm border transition-all duration-200 ${selectedVariant?.id === variant.id
                  ? 'bg-[#B12C2E] border-[#B12C2E] text-white shadow-md transform scale-105'
                  : 'bg-white dark:bg-[#151D2C] border-gray-200 dark:border-slate-700 text-gray-700 dark:text-slate-300 hover:border-[#B12C2E] dark:hover:border-[#E03E41]'
                  }`}
              >
                {variant.variant_value}
              </button>
            ))}
          </div>
        </div>
      )}
    </div>
  );
};
