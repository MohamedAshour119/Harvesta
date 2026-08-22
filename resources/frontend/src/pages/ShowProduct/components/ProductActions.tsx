import React from 'react';
import { FaHeart, FaRegHeart, FaCheck, FaSpinner, FaLink } from 'react-icons/fa';
import type { IProductCard, IProductVariant } from '../../../types';
import { FiShoppingCart } from 'react-icons/fi';
import { useTranslation } from 'react-i18next';
import { useState } from 'react';
import { isVariantAvailable } from '../../../utils/productAvailability';

interface AddToCartState {
  isLoading: boolean;
  isSuccess: boolean;
  showToast: boolean;
}

interface ProductActionsProps {
  product: IProductCard;
  selectedVariant: IProductVariant | null;
  quantity: number;
  isFavorite: boolean;
  isAgent: boolean;
  agentCode: string;
  onQuantityChange: (quantity: number) => void;
  onAddToCart: () => void;
  onBuyNow: () => void;
  onToggleFavorite: () => void;
  addToCartState: AddToCartState;
}

export const ProductActions: React.FC<ProductActionsProps> = ({
  product,
  selectedVariant,
  quantity,
  isFavorite,
  isAgent,
  agentCode,
  onQuantityChange,
  onAddToCart,
  onBuyNow,
  onToggleFavorite,
  addToCartState
}) => {
  const { t } = useTranslation(['showProduct']);
  const { isLoading, isSuccess } = addToCartState;
  const [isCopied, setIsCopied] = useState(false);
  const variantAvailable = isVariantAvailable(selectedVariant);

  const handleCopyReferralLink = async () => {
    const link = `${window.location.origin}/products/${product.slug}?ref=${agentCode}`;
    try {
      await navigator.clipboard.writeText(link);
      setIsCopied(true);
      setTimeout(() => setIsCopied(false), 2000);
    } catch (err) {
      console.error('Failed to copy referral link:', err);
    }
  };

  const getAddToCartButtonContent = () => {
    if (!variantAvailable) {
      return <span>{t('showProduct:stockAvailability.outOfStock')}</span>;
    }

    if (isLoading) {
      return (
        <>
          <FaSpinner className="size-5 animate-spin" />
          <span>{t('showProduct:actions.loadingAddToCart')}</span>
        </>
      );
    }

    if (isSuccess) {
      return (
        <>
          <FaCheck className="size-5" />
          <span>{t('showProduct:actions.successAddToCart')}</span>
        </>
      );
    }

    return (
      <>
        <FiShoppingCart className="size-5" />
        <span>{t('showProduct:actions.addToCart')}</span>
      </>
    );
  };

  const getAddToCartButtonStyles = () => {
    if (!variantAvailable) {
      return "flex-1 flex items-center gap-x-3 justify-center cursor-not-allowed bg-gray-200 dark:bg-slate-800 text-gray-400 dark:text-slate-600 py-3.5 px-6 rounded-xl font-semibold opacity-60 border-none";
    }

    if (isSuccess) {
      return "flex-1 flex items-center gap-x-3 justify-center cursor-pointer bg-green-600 text-white py-3.5 px-6 rounded-xl font-semibold transition-all duration-300 shadow-md";
    }

    if (isLoading) {
      return "flex-1 flex items-center gap-x-3 justify-center cursor-wait bg-[#B12C2E]/80 text-white py-3.5 px-6 rounded-xl font-semibold transition-all duration-300";
    }

    return "flex-1 flex items-center gap-x-3 justify-center cursor-pointer border-2 border-[#B12C2E] text-[#B12C2E] dark:text-[#E03E41] dark:border-[#E03E41] py-3.5 px-6 rounded-xl font-semibold hover:bg-[#B12C2E] hover:text-white dark:hover:bg-[#E03E41] dark:hover:text-white transition-all duration-300 shadow-sm";
  };

  const getBuyNowButtonContent = () => {
    if (!variantAvailable) {
      return <span>{t('showProduct:stockAvailability.outOfStock')}</span>;
    }
    return <span>{t('showProduct:actions.buyNow')}</span>;
  };

  const getBuyNowButtonStyles = () => {
    if (!variantAvailable) {
      return "flex-1 cursor-not-allowed bg-gray-200 dark:bg-slate-800 text-gray-400 dark:text-slate-600 py-3.5 px-6 rounded-xl font-semibold opacity-60";
    }
    return "flex-1 bg-[#B12C2E] hover:bg-[#972426] dark:bg-[#B12C2E] dark:hover:bg-[#C83437] text-white py-3.5 px-6 rounded-xl font-semibold transition-all duration-300 cursor-pointer shadow-md";
  };

  return (
    <div className="space-y-6">
      {/* Quantity Selector */}
      <div className="flex items-center space-x-4">
        <span className="font-semibold text-gray-700 dark:text-slate-300 text-sm">{t('showProduct:Qty')}</span>
        <div className="flex items-center border border-gray-300 dark:border-slate-700 rounded-xl overflow-hidden bg-white dark:bg-[#151D2C]">
          <button
            onClick={() =>
              onQuantityChange(Math.max(product.minimum_order_quantity || 1, quantity - 1))
            }
            className="px-4 py-2 cursor-pointer hover:bg-gray-100 dark:hover:bg-slate-800 text-gray-700 dark:text-slate-300 transition-colors font-bold"
            disabled={isLoading || quantity <= (product.minimum_order_quantity || 1)}
          >
            -
          </button>

          <input
            type="number"
            min={product.minimum_order_quantity || 1}
            max={selectedVariant?.total_quantity!}
            value={quantity}
            onChange={(e) => {
              const value = parseInt(e.target.value, 10) || 1;
              const clampedValue = Math.min(
                selectedVariant?.total_quantity!,
                Math.max(product.minimum_order_quantity || 1, value)
              );
              onQuantityChange(clampedValue);
            }}
            className="w-16 text-center px-2 py-2 border-x border-gray-300 dark:border-slate-700 focus:outline-none bg-transparent text-gray-900 dark:text-white font-semibold"
            disabled={isLoading}
            style={{ MozAppearance: 'textfield' }}
          />

          <button
            onClick={() =>
              onQuantityChange(
                Math.min(selectedVariant?.total_quantity!, quantity + 1)
              )
            }
            className="px-4 py-2 cursor-pointer hover:bg-gray-100 dark:hover:bg-slate-800 text-gray-700 dark:text-slate-300 transition-colors font-bold"
            disabled={isLoading || quantity >= selectedVariant?.total_quantity!}
          >
            +
          </button>
        </div>
      </div>

      {/* Referral Link for Agents */}
      {isAgent && (
        <div className="w-full">
          <button
            onClick={handleCopyReferralLink}
            className={`
              w-full flex items-center justify-center gap-2 px-6 py-3 rounded-xl font-semibold transition-all duration-300 cursor-pointer shadow-sm
              ${isCopied
                ? 'bg-green-600 text-white'
                : 'bg-white dark:bg-[#151D2C] border-2 border-[#B12C2E] text-[#B12C2E] dark:text-[#E03E41] hover:bg-[#B12C2E] hover:text-white'
              }
            `}
          >
            {isCopied ? (
              <>
                <FaCheck className="size-5" />
                <span>{t('showProduct:actions.referralLinkCopied')}</span>
              </>
            ) : (
              <>
                <FaLink className="size-5" />
                <span>{t('showProduct:actions.copyReferralLink')}</span>
              </>
            )}
          </button>
        </div>
      )}

      {/* Action Buttons */}
      <div className="flex flex-col desktop-small:flex-row gap-3 sm:gap-4 w-full">
        <button
          onClick={onBuyNow}
          className={`
          flex-1 sm:flex-none sm:min-w-[140px] 
          px-4 sm:px-6 py-3 sm:py-3.5
          text-sm sm:text-base font-semibold
          rounded-xl
          transition-all duration-300
          focus:outline-none focus:ring-2 focus:ring-offset-2
          disabled:cursor-not-allowed disabled:opacity-50
          ${getBuyNowButtonStyles()}
        `}
          disabled={!variantAvailable || isLoading}
        >
          <span className="flex items-center justify-center gap-2">
            {getBuyNowButtonContent()}
          </span>
        </button>

        <button
          onClick={onAddToCart}
          className={`
          flex-1 sm:flex-none sm:min-w-[160px]
          px-4 sm:px-6 py-3 sm:py-3.5
          text-sm sm:text-base font-semibold
          rounded-xl
          transition-all duration-300
          focus:outline-none focus:ring-2 focus:ring-offset-2
          disabled:cursor-not-allowed disabled:opacity-50
          ${getAddToCartButtonStyles()}
        `}
          disabled={!variantAvailable || isLoading}
        >
          <span className="flex items-center justify-center gap-2">
            {getAddToCartButtonContent()}
          </span>
        </button>

        <button
          onClick={onToggleFavorite}
          className="
          flex-shrink-0
          p-3 sm:p-3.5
          border-2 border-gray-200 dark:border-slate-700
          bg-white dark:bg-[#151D2C]
          rounded-xl
          cursor-pointer 
          group
          transition-all duration-300
          hover:border-pink/80
          focus:outline-none
          active:scale-95
          disabled:cursor-not-allowed disabled:opacity-50
          flex items-center justify-center flex-row-reverse gap-2 text-gray-700 dark:text-slate-300 font-medium
        "
          disabled={isLoading}
          aria-label={isFavorite ? t('showProduct:actions.removeFavorite') : t('showProduct:actions.favorite')}
        >
          <span className='block desktop-small:hidden'>{isFavorite ? t('showProduct:actions.removeFavorite') : t('showProduct:actions.favorite')}</span>
          {isFavorite ?
            <FaHeart className="text-pink group-hover:scale-110 transition-transform duration-300 w-5 h-5 sm:w-6 sm:h-6" /> :
            <FaRegHeart className="text-gray-400 dark:text-slate-400 group-hover:text-pink group-hover:scale-110 transition-transform duration-300 w-5 h-5 sm:w-6 sm:h-6" />
          }
        </button>
      </div>
    </div>
  );
};
