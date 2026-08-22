import React, { useState, useRef, useEffect } from 'react';
import { FaRegTrashAlt } from 'react-icons/fa';
import i18n from '../../../i18n';
import { useTranslation } from 'react-i18next';
import { Link } from 'react-router-dom';
import { updateCartItemQuantity } from '../../../store/slices/cartSlice';
import { useDispatch } from 'react-redux';
import { CartBundleCTA } from '../../../components/marketing/CartBundleCTA';
import type { SmartBundleCTA } from '../../../types';

interface CartItemProps {
  id: number;
  name: string;
  slug: string;
  description?: string;
  image: string;
  price: number;
  originalPrice?: number;
  quantity?: number;
  total_quantity?: number;
  onIncreaseQuantity: (id: number) => void;
  onDecreaseQuantity: (id: number) => void;
  cta?: SmartBundleCTA | null;
}

const CartItem: React.FC<CartItemProps> = ({ 
  id,
  name,
  slug,
  image,
  price,
  originalPrice,
  quantity,
  total_quantity,
  onIncreaseQuantity, 
  onDecreaseQuantity,
  description,
  cta
}) => {
  const [isExpanded, setIsExpanded] = useState(false);
  const [canToggle, setCanToggle] = useState(false);
  const descriptionRef = useRef<HTMLParagraphElement>(null);

  const isRTL = i18n.language === 'ar';
  const { t } = useTranslation(['cart']);
  const dispatch = useDispatch();

  useEffect(() => {
    if (descriptionRef.current && description && !isExpanded) {
      setCanToggle(descriptionRef.current.scrollHeight > descriptionRef.current.clientHeight);
    } else if (!description) {
      setCanToggle(false);
    }
  }, [description, isExpanded]);

  const onQuantityChange = (quantity: number) => {
    dispatch(updateCartItemQuantity({ id, quantity }));
  };

  return (
    <div className="flex flex-col sm:flex-row items-start w-full h-auto p-4 bg-white dark:bg-[#151D2C] rounded-2xl border border-gray-200 dark:border-slate-700/60 shadow-sm transition-colors">
      {/* Product Image */}
      <Link to={`/products/${slug}`} className="w-[140px] h-[140px] bg-gray-50 dark:bg-[#1C2638] flex-shrink-0 p-2 flex items-center justify-center border border-gray-100 dark:border-slate-700/60 rounded-xl overflow-hidden">
        <img 
          src={image} 
          alt={name} 
          className="max-w-full max-h-full object-contain rounded-lg" 
        />
      </Link>
      
      {/* Product Details */}
      <div className="flex-1 px-0 sm:px-4 mt-3 sm:mt-0 w-full">
        {/* Product Description */}
        <div className="mb-3">
          <Link to={`/products/${slug}`}>
            <h3 className="text-base font-bold text-gray-900 dark:text-slate-100 hover:text-[#B12C2E] dark:hover:text-[#E03E41] transition-colors mb-1">{name}</h3>
          </Link>
          {description && (
            <>
              <p 
                ref={descriptionRef} 
                className={`text-xs md:text-sm text-gray-600 dark:text-slate-400 leading-5 ${!isExpanded ? 'line-clamp-2' : ''}`}
              >
                {description}
              </p>
              {canToggle && (
                <button 
                  onClick={() => setIsExpanded(!isExpanded)} 
                  className="text-xs text-[#B12C2E] dark:text-[#E03E41] hover:underline font-semibold mt-1 cursor-pointer"
                  aria-expanded={isExpanded}
                >
                  {isExpanded ? t('showLess') : t('showMore')}
                </button>
              )}
            </>
          )}
          {cta && <CartBundleCTA cta={cta} />}
        </div>
        
        {/* Price and Quantity Controls */}
        <div className="flex justify-between items-center gap-y-3 pt-2 border-t border-gray-100 dark:border-slate-800">
          <div className={`flex flex-col ${isRTL ? 'items-start' : 'items-start'}`}>
            <div className="flex flex-row-reverse flex-wrap items-center gap-x-2">
              {originalPrice && originalPrice > price && (
                <del className="text-gray-400 dark:text-slate-500 text-xs flex items-center">
                  <span className="text-xs font-bold mx-1">EGP</span>
                  {originalPrice.toFixed(2)}
                </del>
              )}
              <span className="text-xl font-bold text-gray-900 dark:text-white flex items-center">
                <span className="text-xs font-bold mx-1 text-[#B12C2E] dark:text-[#E03E41]">EGP</span>
                {price.toFixed(2)}
              </span>
            </div>
            {quantity && quantity > 1 && (
              <div className="mt-1 text-xs font-semibold uppercase tracking-wider text-[#B12C2E] dark:text-[#E03E41] bg-red-50/70 dark:bg-red-950/40 px-2 py-0.5 rounded-md inline-flex items-center gap-1">
                <span className="text-slate-500 dark:text-slate-400 font-medium lowercase tracking-normal">{t('total')}:</span>
                <span className="flex items-center font-bold">
                  <span className="text-[10px] mx-0.5">EGP</span>
                  {(price * quantity).toFixed(2)}
                </span>
              </div>
            )}
          </div>
          <div className="flex items-center rounded-xl bg-[#B12C2E] text-white shadow-sm overflow-hidden">
            <button 
              className="w-8 h-8 flex items-center justify-center hover:bg-black/10 transition-colors cursor-pointer"
              onClick={(e) => {
                e.stopPropagation();
                e.preventDefault();
                onDecreaseQuantity(id);
              }}
              title="Decrease quantity"
            >
              {quantity === 1 ? <FaRegTrashAlt size={12} /> : '-'}
            </button>

            <span className="w-8 h-8 flex items-center justify-center font-bold text-sm bg-black/10">
              <input
                type="number"
                min={1}
                max={total_quantity!}
                value={quantity}
                onClick={(e) => {
                  e.stopPropagation();
                  e.preventDefault();
                }}
                onChange={(e) => {
                  const parsedValue = parseInt(e.target.value, 10);
                  const value = isNaN(parsedValue) ? 1 : parsedValue;
                  const clampedValue = Math.min(
                    total_quantity!,
                    Math.max(1, value)
                  );
                  
                  onQuantityChange(clampedValue);
                }}
                className="w-8 text-center border-none focus:outline-none bg-transparent text-white font-bold"
                style={{ MozAppearance: 'textfield' }}
              />
            </span>

            <button 
              className="w-8 h-8 flex items-center justify-center hover:bg-black/10 transition-colors cursor-pointer"
              onClick={(e) => {
                e.stopPropagation();
                e.preventDefault();
                onIncreaseQuantity(id);
              }}
              title="Increase quantity"
            >
              +
            </button>
          </div>
        </div>
      </div>
    </div>
  );
};

export default CartItem;