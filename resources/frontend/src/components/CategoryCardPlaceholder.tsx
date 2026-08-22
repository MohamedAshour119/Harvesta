import React from 'react';

const CategoryCardPlaceholder: React.FC = () => {
  return (
    <div className="flex flex-col items-center w-full">
      <div className="overflow-hidden rounded-xl shadow-md w-full border border-gray-100 dark:border-slate-800 bg-white dark:bg-[#151D2C]">
        <div className="aspect-square bg-gray-200 dark:bg-slate-700 rounded-xl animate-pulse"></div>
      </div>
      <div className="mt-3 h-5 w-3/4 bg-gray-200 dark:bg-slate-700 rounded-md animate-pulse mobile-large:h-4 self-center"></div>
    </div>
  );
};

export default CategoryCardPlaceholder;
