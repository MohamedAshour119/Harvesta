import React from 'react';
import { useSelector } from 'react-redux';
import { useTranslation } from 'react-i18next';
import type { RootState } from '../../../store/store';

interface OrderTotalsProps {
  subtotal: number;
  shippingFee: number;
  isCalculatingShipping: boolean;
  discount: number;
  itemCount: number;
}

const OrderTotals: React.FC<OrderTotalsProps> = ({
  subtotal,
  shippingFee,
  isCalculatingShipping,
  discount,
  itemCount
}) => {
  const { t } = useTranslation('checkout');
  const formatPrice = (price: number) => {
    return (
      <div className="flex items-center gap-x-1">
        <span className="text-xs font-bold text-[#B12C2E] dark:text-[#E03E41]">EGP</span>
        <span>{price?.toFixed(2)}</span>
      </div>
    );
  };

  const total = useSelector((state: RootState) => state.cart?.total);

  return (
    <div className="space-y-3 mb-6 pt-4 border-t border-gray-200 dark:border-slate-700/60">
      {/* Subtotal */}
      <div className="flex justify-between items-center text-sm">
        <span className="text-gray-600 dark:text-slate-400">
          {t('orderTotals.subtotal')} ({itemCount} {itemCount !== 1 ? t('items') : t('item')})
        </span>
        <span className="font-semibold text-gray-900 dark:text-slate-100">
          {formatPrice(subtotal)}
        </span>
      </div>

      {/* Shipping Fee */}
      <div className="flex justify-between items-center text-sm">
        <span className="text-gray-600 dark:text-slate-400">{t('orderTotals.shipping')}</span>
        <span className="font-semibold text-gray-900 dark:text-slate-100">
          {isCalculatingShipping ? 'Calculating...' : formatPrice(shippingFee)}
        </span>
      </div>

      {/* Discount */}
      {discount > 0 && (
        <div className="flex justify-between items-center text-sm text-green-600 dark:text-green-400">
          <span>{t('orderTotals.discount')}</span>
          <span className="font-semibold flex items-center gap-x-1">
            <span>-</span>
            {formatPrice(discount * subtotal / 100)}
          </span>
        </div>
      )}

      {/* Total */}
      <div className="flex justify-between items-center pt-3 border-t border-gray-200 dark:border-slate-700/60">
        <span className="text-base font-bold text-gray-900 dark:text-slate-100">{t('orderTotals.total')}</span>
        <span className="text-xl font-extrabold text-gray-900 dark:text-white">
          {formatPrice(total || 0)}
        </span>
      </div>
    </div>
  );
};

export default OrderTotals;
