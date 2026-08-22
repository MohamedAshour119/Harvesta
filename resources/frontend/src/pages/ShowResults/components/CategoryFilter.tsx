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
  const { categories } = useSelector((state: RootState) => state.filter);

  const handleCategoryToggle = (categorySlug: string) => {
    dispatch(toggleCategory(categorySlug));
  };

  return (
    <div className="mb-6">
      <h4 className="font-medium text-gray-700 mb-3">{t('shop:filterSection.categories')}</h4>
      <div className="space-y-2">
        {categories.map((category) => (
          <div key={category.id} className="flex items-center justify-between">
            <label className="flex items-center text-sm text-gray-600 cursor-pointer">
              <div className="relative flex items-center">
                <input
                  type="checkbox"
                  className="w-4 h-4 opacity-0 absolute cursor-pointer"
                  checked={category.selected}
                  onChange={() => handleCategoryToggle(category.slug)}
                />
                {/* Modified checkbox visual */}
                <div className="w-5 h-5 rounded-full flex items-center justify-center transition-all duration-200 ease-in-out">
                  {category.selected ? (
                    // State when selected: filled orange with glow
                    <div className="w-full h-full rounded-full bg-[#ff2b2b40] border border-red-300 shadow-md shadow-red-300/50 flex items-center justify-center">
                      <svg
                        xmlns="http://www.w3.org/2000/svg"
                        className="h-3 w-3 text-red-300"
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
                    // State when not selected: empty circle with gray border
                    <div className="w-full h-full rounded-full border border-gray-300"></div>
                  )}
                </div>
                {(() => {
                  let nameObj: any = category.name;
                  if (typeof nameObj === 'string') {
                    try { nameObj = JSON.parse(nameObj); } catch { /* invalid JSON */ }
                  }
                  const displayName: string = nameObj[i18n.language] || nameObj.en || nameObj.ar || '';
                  return <span className={isRTL ? 'mr-2' : 'ml-2'}>{displayName}</span>;
                })()}
              </div>
            </label>
            <span className="text-xs text-gray-500">({category.products_count})</span>
          </div>
        ))}
      </div>
    </div>
  );
};

export default CategoryFilter;
