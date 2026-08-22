import React from 'react';
import { useDispatch, useSelector } from 'react-redux';
import { toggleCategory } from '../../../store/slices/filterSlice';
import type { RootState } from '../../../store/store';
import i18n from '../../../i18n';
import { useTranslation } from 'react-i18next';

const CategoryFilter: React.FC = () => {
  const { t } = useTranslation(['shop']);
  const isRTL = i18n.language === 'ar';
  const dispatch = useDispatch();
  const categories = useSelector((state: RootState) => state.filter?.categories || []);

  const handleCategoryToggle = (categorySlug: string) => {
    dispatch(toggleCategory(categorySlug));
  };

  return (
    <div className="mb-0">
      <h4 className="text-base font-bold text-gray-900 dark:text-slate-100 mb-4">{t('shop:filterSection.categories')}</h4>
      <div className="space-y-3">
        {categories.length === 0 ? (
          Array.from({ length: 8 }).map((_, index) => (
            <div key={`category-placeholder-${index}`} className="flex items-center justify-between animate-pulse">
              <div className="flex items-center space-x-3 rtl:space-x-reverse">
                <div className="w-5 h-5 rounded-full bg-gray-200 dark:bg-slate-700"></div>
                <div className="h-3.5 bg-gray-200 dark:bg-slate-700 rounded-full w-24"></div>
              </div>
              <div className="h-3 bg-gray-200 dark:bg-slate-700 rounded-full w-6"></div>
            </div>
          ))
        ) : (
          categories.map((category: any) => (
            <div key={category.id} className="flex items-center justify-between">
              <label className="flex items-center text-sm text-gray-700 dark:text-slate-300 hover:text-gray-900 dark:hover:text-white cursor-pointer transition-colors">
                <div className="relative flex items-center">
                  <input
                    type="checkbox"
                    className="w-4 h-4 opacity-0 absolute cursor-pointer"
                    checked={category.selected}
                    onChange={() => handleCategoryToggle(category.slug)}
                  />
                  <div className="w-5 h-5 rounded-full flex items-center justify-center transition-all duration-200 ease-in-out">
                    {category.selected ? (
                      <div className="w-full h-full rounded-full bg-[#B12C2E]/20 dark:bg-[#E03E41]/30 border border-[#B12C2E] dark:border-[#E03E41] shadow-sm flex items-center justify-center">
                        <svg
                          xmlns="http://www.w3.org/2000/svg"
                          className="h-3 w-3 text-[#B12C2E] dark:text-[#E03E41]"
                          viewBox="0 0 20 20"
                          fill="currentColor"
                        >
                          <path
                            fillRule="evenodd"
                            d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                            clipRule="evenodd"
                          />
                        </svg>
                      </div>
                    ) : (
                      <div className="w-full h-full rounded-full border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-800"></div>
                    )}
                  </div>
                  {(() => {
                    let name = category.name;
                    let displayName = '';

                    if (typeof name === 'string') {
                      try {
                        const parsed = JSON.parse(name);
                        if (typeof parsed === 'object' && parsed !== null) {
                          name = parsed;
                        }
                      } catch (e) {
                        // Not a JSON string
                      }
                    }

                    if (typeof name === 'object' && name !== null) {
                      displayName = name[i18n.language] || name.en || name.ar || '';
                    } else if (typeof name === 'string') {
                      displayName = name;
                    }

                    return <span className={isRTL ? 'mr-2.5' : 'ml-2.5'}>{displayName}</span>;
                  })()}
                </div>
              </label>
              <span className="text-xs text-gray-400 dark:text-slate-500 font-mono">({category.products_count})</span>
            </div>
          ))
        )}
      </div>
    </div>
  );
};

export default CategoryFilter;
