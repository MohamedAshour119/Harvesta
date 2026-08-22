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
  const { selectedFilter, appliedFilters } = useSelector((state: RootState) => state.filter);

  const handleSortFilterChange = (filter: string) => {
    dispatch(setSelectedSortFilter(filter));
  };

  const handleClearFilters = () => {
    dispatch(clearFilters());
    dispatch(setSelectedSortFilter('most-popular'));
  };

  return (
    <div className="w-full lg:w-64 flex-shrink-0">
      <div className="bg-white rounded-lg shadow mb-4">
        <div className="flex items-center mb-4 gap-x-2 min-h-[32px]">
          <h3 className="font-semibold text-gray-800">{t('shop:filterSection.title')}</h3>
          <div className="flex items-center gap-x-2">
            {appliedFilters > 0 && (
              <div className="w-[80px] h-max py-1 flex items-center justify-center flex-wrap bg-[#ff2b2b40] text-yellow rounded-[8px] text-xs font-medium text-center">
                {appliedFilters} {t('shop:filterSection.appliedFilters')}
              </div>
            )}
            <button
              className="text-sm text-gray-500 hover:text-gray-700 cursor-pointer"
              onClick={handleClearFilters}
            >
              {t('shop:filterSection.clear')}
            </button>
          </div>
        </div>

        {/* Filter by popularity */}
        <div className="mb-2">
          <div className="flex space-x-2 mb-4">
            <button
              className={`px-3 py-1 rounded-full text-sm cursor-pointer ${selectedFilter === 'most-popular'
                ? 'bg-yellow text-white'
                : 'bg-gray-100 text-gray-700'}`}
              onClick={() => handleSortFilterChange('most-popular')}
            >
              {t('shop:filterSection.popular')}
            </button>
            <button
              className={`px-3 py-1 rounded-full text-sm cursor-pointer ${selectedFilter === 'newest'
                ? 'bg-yellow text-white'
                : 'bg-gray-100 text-gray-700'}`}
              onClick={() => handleSortFilterChange('newest')}
            >
              {t('shop:filterSection.latest')}
            </button>
          </div>
        </div>

        {/* Categories */}
        <CategoryFilter />

        {/* Price Range */}
        <PriceRangeFilter />

        {/* Sale-Deals */}
        <div className="mb-6">
          <h4 className="font-medium text-gray-700 mb-3">{t('shop:filterSection.saleDeals.title')}</h4>
          <div className="space-y-2">
            <button onClick={() => handleSortFilterChange('all-discounts')} className="text-sm text-gray-600 hover:text-yellow block cursor-pointer">{t('shop:filterSection.saleDeals.allDiscounts')}</button>
            <button onClick={() => handleSortFilterChange('today-deals')} className="text-sm text-gray-600 hover:text-yellow block cursor-pointer">{t('shop:filterSection.saleDeals.todayDeals')}</button>
          </div>
        </div>

        {/* Availability */}
        <div className="mb-4">
          <h4 className="font-medium text-gray-700 mb-3">{t('shop:filterSection.availability.title')}</h4>
          <div className="space-y-2">
            <button onClick={() => handleSortFilterChange('in-stock')} className="text-sm text-gray-600 hover:text-yellow block cursor-pointer">{t('shop:filterSection.availability.inStock')}</button>
            <button onClick={() => handleSortFilterChange('out-of-stock')} className="text-sm text-gray-600 hover:text-yellow block cursor-pointer">{t('shop:filterSection.availability.outOfStock')}</button>
          </div>
        </div>
      </div>
    </div>
  );
};

export default FilterSection;
