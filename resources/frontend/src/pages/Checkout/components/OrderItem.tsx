import React from 'react';
import type { CartItem } from '../../../types';

interface OrderItemProps {
  item: CartItem;
}

const OrderItem: React.FC<OrderItemProps> = ({ item }) => {
  return (
    <div className="flex items-center space-x-4 p-3.5 bg-white dark:bg-[#1C2638] rounded-2xl border border-gray-100 dark:border-slate-700/60 shadow-sm transition-colors">
      {/* Product Image */}
      <div className="flex-shrink-0 w-14 h-14 bg-gray-50 dark:bg-[#151D2C] border border-gray-100 dark:border-slate-700/60 rounded-xl p-1 flex items-center justify-center overflow-hidden">
        <img
          src={item.image || '/placeholder-product.jpg'}
          alt={item.name}
          className="max-w-full max-h-full object-contain rounded-lg"
        />
      </div>

      {/* Product Details */}
      <div className="flex-1 min-w-0">
        <h3 className="text-sm font-semibold text-gray-900 dark:text-slate-100 truncate">
          {item.name}
        </h3>
        {item.variantName && item.variantValue && (
          <p className="text-xs text-gray-500 dark:text-slate-400 mt-0.5">
            {item.variantName}: {item.variantValue}
          </p>
        )}
        <div className="flex items-center mt-1">
          <span className="text-xs text-gray-500 dark:text-slate-400">Qty: </span>
          <span className="flex items-center justify-center ml-1 px-2 py-0.5 bg-[#B12C2E]/10 dark:bg-red-950/40 border border-[#B12C2E]/20 text-[#B12C2E] dark:text-[#E03E41] text-xs font-bold rounded-md">
            {item.quantity || 1}
          </span>
        </div>
      </div>

      {/* Price */}
      <div className="flex-shrink-0 text-right">
        <div className="flex items-center font-bold text-gray-900 dark:text-white">
          <span className="text-xs mx-1 text-[#B12C2E] dark:text-[#E03E41]">EGP</span>
          <span className="text-base">
            {item.price?.toFixed(2)}
          </span>
        </div>
        {item.originalPrice && item.originalPrice > item.price && (
          <div className="flex items-center mt-0.5 justify-end">
            <span className="text-[10px] text-gray-400 dark:text-slate-500 mx-0.5">EGP</span>
            <span className="text-xs text-gray-400 dark:text-slate-500 line-through">
              {item.originalPrice.toFixed(2)}
            </span>
          </div>
        )}
      </div>
    </div>
  );
};

export default OrderItem;
