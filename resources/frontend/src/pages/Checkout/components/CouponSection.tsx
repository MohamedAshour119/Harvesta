import React, { useState, type KeyboardEvent } from 'react';
import type { CouponState } from '../../../types';
import ApiClient from '../../../ApiClient';
import { useDispatch, useSelector } from 'react-redux';
import { setCoupon } from '../../../store/slices/couponSlice';
import type { RootState } from '../../../store/store';
import { calculateTotalWithDiscount } from '../../../helper/calculateTotalWithDiscount';
import { setTotal } from '../../../store/slices/cartSlice';
import { useTranslation } from 'react-i18next';

interface CouponSectionProps {
  coupon: CouponState;
  onCouponApply: (code: string) => void;
}

const CouponSection: React.FC<CouponSectionProps> = ({
  coupon,
}) => {
  const { t } = useTranslation('checkout');
  const [inputCode, setInputCode] = useState('');
  const [isApplying, setIsApplying] = useState(false);

  const dispatch = useDispatch();
  const cartItems = useSelector((state: RootState) => state.cart);

  const handleApplyCoupon = async () => {
    if (!inputCode.trim()) return;

    setIsApplying(true);

    ApiClient.coupon.applyCoupon(inputCode.trim())
      .then((response) => {
        const couponData = response.data.data.coupon;
        const couponDiscountValue = response.data.data.coupon.value;
        dispatch(setCoupon({ code: couponData.name, error: '', discount: couponDiscountValue, isApplied: true }));
        const updatedTotal = calculateTotalWithDiscount(cartItems.subtotal, couponDiscountValue);

        dispatch(setTotal(updatedTotal));
      })
      .catch((error) => {
        console.error('Error applying coupon:', error);
        dispatch(setCoupon({ code: '', error: error.response?.data?.errors?.code[0] || 'Invalid coupon code' }));
      })
      .finally(() => {
        setIsApplying(false);
      });

  };

  const handleKeyPress = (e: KeyboardEvent<HTMLInputElement>) => {
    if (e.key === 'Enter') {
      handleApplyCoupon();
    }
  };

  return (
    <div className="mb-6">
      <h3 className="text-sm font-semibold text-gray-900 dark:text-slate-100 mb-3">{t('coupon.title')}</h3>

      <div className="flex space-x-2">
        <input
          type="text"
          value={inputCode}
          onChange={(e) => setInputCode(e.target.value)}
          onKeyDown={handleKeyPress}
          placeholder={t('coupon.placeholder')}
          disabled={isApplying}
          className="flex-1 px-3 py-2.5 bg-white dark:bg-[#1C2638] border border-gray-300 dark:border-slate-700 rounded-xl text-sm outline-none
                  text-gray-900 dark:text-slate-100 placeholder-gray-400 dark:placeholder-slate-500
                  focus:border-[#B12C2E] focus:ring-2 focus:ring-[#B12C2E]/20
                  disabled:bg-gray-100 dark:disabled:bg-slate-800 disabled:cursor-not-allowed transition-all"
        />
        <button
          onClick={handleApplyCoupon}
          disabled={!inputCode.trim() || isApplying}
          className="px-4 py-2.5 cursor-pointer bg-[#B12C2E] hover:bg-[#972426] text-white text-sm font-semibold rounded-xl
                   focus:outline-none disabled:bg-gray-300 dark:disabled:bg-slate-700 disabled:cursor-not-allowed
                   transition-all duration-200 shadow-sm"
        >
          {isApplying ? (
            <div className="flex items-center space-x-2">
              <div className="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin"></div>
              <span>{t('coupon.applying')}</span>
            </div>
          ) : (
            <span>{t('coupon.apply')}</span>
          )}
        </button>
      </div>

      {/* Coupon Status Messages */}
      {coupon.code && (
        <div className="mt-3">
          {coupon.isApplied ? (
            <div className="flex space-x-2 text-green-600 dark:text-green-400 text-sm font-medium">
              <svg className="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                <path fillRule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clipRule="evenodd" />
              </svg>
              <span>{t('coupon.appliedSuccess', { code: coupon.code, discount: typeof coupon.discount === 'number' ? coupon.discount.toFixed(2) : coupon.discount })}</span>
            </div>
          ) : coupon.error ? (
            <div className="flex items-center space-x-2 text-red-600 dark:text-red-400 text-sm font-medium">
              <svg className="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                <path fillRule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clipRule="evenodd" />
              </svg>
              <span>{t('coupon.invalid', { error: coupon.error })}</span>
            </div>
          ) : (
            <></>
          )}
        </div>
      )}

      {/* Helpful hint */}
      <p className="mt-2 text-xs text-gray-500 dark:text-slate-400 flex items-start gap-1">
        <span>💡</span>
        <span>{t('coupon.hint')}</span>
      </p>
    </div>
  );
};

export default CouponSection;
