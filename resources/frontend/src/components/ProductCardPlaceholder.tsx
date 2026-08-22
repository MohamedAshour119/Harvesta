import React from 'react';

const ProductCardPlaceholder: React.FC = () => {
  return (
    <div className="rounded-xl shadow-sm bg-white dark:bg-[#151D2C] border border-gray-200 dark:border-slate-700/60 relative flex flex-col justify-between overflow-hidden animate-pulse min-h-[330px]">
      {/* Wishlist Icon Placeholder */}
      <div className="absolute top-3 end-3 bg-gray-200 dark:bg-slate-700 h-6 w-6 rounded-full"></div>

      {/* Product Image Placeholder */}
      <div className="w-full h-40 bg-gray-200 dark:bg-slate-700 mt-6 mx-auto rounded-md"></div>

      {/* Product Info Placeholder */}
      <div className="p-4 flex flex-col flex-grow justify-between">
        <div>
          <div className="h-4 bg-gray-200 dark:bg-slate-700 rounded w-3/4 mb-2"></div>
          <div className="h-4 bg-gray-200 dark:bg-slate-700 rounded w-1/2 mb-3"></div>
        </div>

        <div className="flex justify-between items-center mt-2">
          <div className="h-5 bg-gray-200 dark:bg-slate-700 rounded w-1/4"></div>
          <div className="h-8 w-8 bg-gray-200 dark:bg-slate-700 rounded-full"></div>
        </div>
      </div>
    </div>
  );
};

export default ProductCardPlaceholder;
