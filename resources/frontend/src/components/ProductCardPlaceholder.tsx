import React from 'react';

const ProductCardPlaceholder: React.FC = () => {
  return (
    <div className="rounded-2xl shadow-sm bg-white dark:bg-[#131B2A] border border-gray-100 dark:border-slate-800/80 relative flex flex-col justify-between overflow-hidden animate-pulse min-h-[350px] transition-colors duration-300">
      {/* Floating Wishlist Icon Placeholder */}
      <div className="absolute top-5 end-5 z-10 w-8 h-8 rounded-full bg-white/90 dark:bg-slate-700/60 shadow-sm border border-gray-100 dark:border-slate-700/40" />

      {/* Product Image Placeholder with Soft Gradient */}
      <div className="h-44 m-3 rounded-xl bg-gradient-to-b from-gray-100 to-gray-200/80 dark:from-slate-800/30 dark:to-slate-800/70 border border-gray-50 dark:border-slate-800/40 flex items-center justify-center relative overflow-hidden">
        {/* Subtle decorative icon placeholder */}
        <div className="w-10 h-10 rounded-xl bg-gray-200/60 dark:bg-slate-700/30" />
      </div>

      {/* Product Info Placeholder */}
      <div className="p-4 pt-1 flex flex-col flex-grow justify-between gap-3">
        <div className="space-y-2">
          {/* Category Pill */}
          <div className="h-3 w-20 rounded-full bg-gray-200/70 dark:bg-slate-700/40 mb-1" />
          
          {/* Product Title Lines */}
          <div className="h-4 bg-gray-200 dark:bg-slate-700/70 rounded-md w-5/6" />
          <div className="h-3.5 bg-gray-100 dark:bg-slate-800/80 rounded-md w-1/2" />
        </div>

        {/* Price and Cart Action Row */}
        <div className="flex justify-between items-center pt-2 border-t border-gray-50 dark:border-slate-800/50">
          <div className="space-y-1">
            <div className="h-5 bg-gray-200 dark:bg-slate-700/70 rounded-md w-20" />
            <div className="h-3 bg-gray-100 dark:bg-slate-800/60 rounded-md w-12" />
          </div>
          <div className="h-9 w-9 bg-gray-200 dark:bg-slate-700/60 rounded-xl shadow-xs" />
        </div>
      </div>
    </div>
  );
};

export default ProductCardPlaceholder;
