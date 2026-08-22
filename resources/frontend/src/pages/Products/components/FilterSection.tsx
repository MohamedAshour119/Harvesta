import React from 'react';
import { useDispatch, useSelector } from 'react-redux';
import { clearFilters, setSelectedSortFilter } from '../../../store/slices/filterSlice';
import type { RootState } from '../../../store/store';
import { useTranslation } from 'react-i18next';
import CategoryFilter from './CategoryFilter';
import PriceRangeFilter from './PriceRangeFilter';

const FilterSection: React.FC = () => {
  const { t } = useTranslation(['shop']);
  const dispatch = useDispatch();
  const { selectedFilter, appliedFilters } = useSelector((state: RootState) => state.filter || { selectedFilter: null, appliedFilters: 0 });

  const handleSortFilterChange = (filter: string) => {
    dispatch(setSelectedSortFilter(selectedFilter === filter ? null : filter));
  };

  const handleClearFilters = () => {
    dispatch(clearFilters());
  };

  return (
    <div className="w-full lg:w-72 flex-shrink-0">
      <div className="bg-white dark:bg-[#151D2C] rounded-[16px] shadow-sm border border-gray-100 dark:border-slate-700/60 p-6 mb-4 transition-colors">
        <div className="flex items-center mb-6 justify-between min-h-[32px]">
          <h3 className="font-bold text-lg text-gray-900 dark:text-slate-100">{t('shop:filterSection.title')}</h3>
          <div className="flex items-center gap-x-2">
            {appliedFilters > 0 && (
              <div className="px-2 py-0.5 h-max flex items-center justify-center text-xs font-semibold rounded-full bg-[#B12C2E]/10 text-[#B12C2E] dark:text-[#E03E41] dark:bg-red-950/40">
                {appliedFilters} {t('shop:filterSection.appliedFilters')}
              </div>
            )}
            <button
              className="text-sm font-medium text-gray-400 dark:text-slate-400 hover:text-red-500 dark:hover:text-red-400 transition-colors cursor-pointer"
              onClick={handleClearFilters}
            >
              {t('shop:filterSection.clear')}
            </button>
          </div>
        </div>

        {/* Filter by popularity */}
        <div className="mb-6">
          <div className="flex bg-gray-50 dark:bg-[#1C2638] p-1 rounded-xl border border-transparent dark:border-slate-800">
            <button
              className={`flex-1 py-1.5 px-3 rounded-lg text-sm font-medium transition-all cursor-pointer ${selectedFilter === 'most-popular'
                ? 'bg-white dark:bg-[#151D2C] text-gray-900 dark:text-white shadow-sm font-semibold'
                : 'text-gray-500 dark:text-slate-400 hover:text-gray-700 dark:hover:text-slate-200'}`}
              onClick={() => handleSortFilterChange('most-popular')}
            >
              {t('shop:filterSection.popular')}
            </button>
            <button
              className={`flex-1 py-1.5 px-3 rounded-lg text-sm font-medium transition-all cursor-pointer ${selectedFilter === 'newest'
                ? 'bg-white dark:bg-[#151D2C] text-gray-900 dark:text-white shadow-sm font-semibold'
                : 'text-gray-500 dark:text-slate-400 hover:text-gray-700 dark:hover:text-slate-200'}`}
              onClick={() => handleSortFilterChange('newest')}
            >
              {t('shop:filterSection.latest')}
            </button>
          </div>
        </div>

        <hr className="my-6 border-gray-100 dark:border-slate-800" />

        {/* Categories */}
        <CategoryFilter />

        <hr className="my-6 border-gray-100 dark:border-slate-800" />

        {/* Price Range */}
        <PriceRangeFilter />

        <hr className="my-6 border-gray-100 dark:border-slate-800" />

        {/* Sale-Deals */}
        <div className="mb-0">
          <h4 className="text-base font-bold text-gray-900 dark:text-slate-100 mb-4">{t('shop:filterSection.saleDeals.title')}</h4>
          <div className="space-y-3">
            <button
              onClick={() => handleSortFilterChange('all-discounts')}
              className={`text-sm font-medium transition-colors cursor-pointer flex items-center ${selectedFilter === 'all-discounts' ? 'text-[#B12C2E] dark:text-[#E03E41] font-bold' : 'text-gray-600 dark:text-slate-300 hover:text-[#B12C2E] dark:hover:text-[#E03E41]'}`}
            >
              {t('shop:filterSection.saleDeals.allDiscounts')}
            </button>
            <button
              onClick={() => handleSortFilterChange('today-deals')}
              className={`text-sm font-medium transition-colors cursor-pointer flex items-center ${selectedFilter === 'today-deals' ? 'text-[#B12C2E] dark:text-[#E03E41] font-bold' : 'text-gray-600 dark:text-slate-300 hover:text-[#B12C2E] dark:hover:text-[#E03E41]'}`}
            >
              {t('shop:filterSection.saleDeals.todayDeals')}
            </button>
          </div>
        </div>

        <hr className="my-6 border-gray-100 dark:border-slate-800" />

        {/* Availability */}
        <div className="mb-0">
          <h4 className="text-base font-bold text-gray-900 dark:text-slate-100 mb-4">{t('shop:filterSection.availability.title')}</h4>
          <div className="space-y-3">
            <button
              onClick={() => handleSortFilterChange('in-stock')}
              className={`text-sm font-medium transition-colors cursor-pointer flex items-center ${selectedFilter === 'in-stock' ? 'text-[#B12C2E] dark:text-[#E03E41] font-bold' : 'text-gray-600 dark:text-slate-300 hover:text-[#B12C2E] dark:hover:text-[#E03E41]'}`}
            >
              {t('shop:filterSection.availability.inStock')}
            </button>
            <button
              onClick={() => handleSortFilterChange('out-of-stock')}
              className={`text-sm font-medium transition-colors cursor-pointer flex items-center ${selectedFilter === 'out-of-stock' ? 'text-[#B12C2E] dark:text-[#E03E41] font-bold' : 'text-gray-600 dark:text-slate-300 hover:text-[#B12C2E] dark:hover:text-[#E03E41]'}`}
            >
              {t('shop:filterSection.availability.outOfStock')}
            </button>
          </div>
        </div>
      </div>
    </div>
  );
};

export default FilterSection;
