import React from 'react';
import type { CartItem, CouponState } from '../../../types';
import OrderItem from './OrderItem';
import CouponSection from './CouponSection';
import OrderTotals from './OrderTotals';
import PlaceOrderButton from './PlaceOrderButton';
import { useTranslation } from 'react-i18next';

interface OrderSummaryProps {
  items: CartItem[];
  subtotal: number;
  shippingFee: number;
  isCalculatingShipping: boolean;
  discount: number;
  coupon: CouponState;
  onCouponApply: (code: string) => void;
  onPlaceOrder: () => void;
  isProcessing: boolean;
}

const OrderSummary: React.FC<OrderSummaryProps> = ({
  items,
  subtotal,
  shippingFee,
  isCalculatingShipping,
  discount,
  coupon,
  onCouponApply,
  onPlaceOrder,
  isProcessing,
}) => {
  const { t } = useTranslation('checkout');

  return (
    <div className="bg-gray-50 dark:bg-[#151D2C] border border-gray-200 dark:border-slate-700/60 rounded-3xl p-6 shadow-sm transition-colors">
      <h2 className="text-xl font-bold text-gray-900 dark:text-slate-100 mb-6">{t('orderSummary.title')}</h2>
      
      {/* Coupon Section */}
      <CouponSection
        coupon={coupon}
        onCouponApply={onCouponApply}
      />
      
      {/* Order Items */}
      <div className="space-y-3 mb-6">
        {items.map((item) => (
          <OrderItem
            key={item.id}
            item={item}
          />
        ))}
      </div>
      
      {/* Order Totals */}
      <OrderTotals
        subtotal={subtotal}
        shippingFee={shippingFee}
        isCalculatingShipping={isCalculatingShipping}
        discount={discount}
        itemCount={items.length}
      />
      
      {/* Place Order Button */}
      <PlaceOrderButton
        onPlaceOrder={onPlaceOrder}
        isProcessing={isProcessing}
        disabled={items.length === 0}
      />
    </div>
  );
};

export default OrderSummary;
