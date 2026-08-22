import React from 'react';
import { useDispatch, useSelector } from 'react-redux';
import { clearFilters, setSelectedSortFilter } from '../../../store/slices/filterSlice';
import CategoryFilter from './CategoryFilter';
import PriceRangeFilter from './PriceRangeFilter';
import type { RootState } from '../../../store/store';
import { useTranslation } from 'react-i18next';
import { useLocation } from 'react-router-dom';

interface IFilterSectionProps {
  categoryName?: string;
  isVisible?: boolean;
  onClose?: () => void;
}

const FilterSection: React.FC<IFilterSectionProps> = ({
  categoryName,
  isVisible = true,
  onClose
}) => {
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

  // Base classes that are always applied
  const baseClasses = "lg:w-72 flex-shrink-0";

  // Responsive classes that change based on visibility
  const responsiveClasses = isVisible
    ? "w-full block"
    : "hidden lg:block w-0 lg:w-auto";

  return (
    <div className={`${baseClasses} ${responsiveClasses}`}>
      <div className="bg-white rounded-[16px] shadow-sm border border-gray-100 p-6 mb-4 relative">
        {/* Close button - only visible on mobile when filters are shown */}
        {onClose && isVisible && (
          <button
            onClick={onClose}
            className="absolute top-2 right-2 text-gray-500 lg:hidden"
            aria-label="Close filters"
          >
            <svg xmlns="http://www.w3.org/2000/svg" className="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        )}

        <div className="flex items-center justify-between mb-4 min-h-[32px]">
          <h3 className="font-bold text-lg text-gray-800">{t('shop:filterSection.title')}</h3>
          <div className="flex items-center gap-x-2">
            {appliedFilters > 0 && (
              <div className="px-2 py-0.5 bg-[#ff2b2b40] text-yellow rounded-[8px] text-xs font-medium">
                {appliedFilters}
              </div>
            )}
            <button
              className="text-sm text-gray-400 hover:text-yellow transition-colors cursor-pointer"
              onClick={handleClearFilters}
            >
              {t('shop:filterSection.clear')}
            </button>
          </div>
        </div>

        <hr className="border-gray-100 mb-4" />

        {/* Filter by popularity */}
        <div className="mb-4">
          <div className="flex gap-2 mb-4">
            <button
              className={`px-4 py-1.5 rounded-lg text-sm font-medium transition-all cursor-pointer border ${selectedFilter === 'most-popular'
                ? 'bg-yellow text-white border-yellow shadow-sm shadow-yellow/20'
                : 'bg-white text-gray-600 border-gray-100 hover:border-gray-200'}`}
              onClick={() => handleSortFilterChange('most-popular')}
            >
              {t('shop:filterSection.popular')}
            </button>
            <button
              className={`px-4 py-1.5 rounded-lg text-sm font-medium transition-all cursor-pointer border ${selectedFilter === 'newest'
                ? 'bg-yellow text-white border-yellow shadow-sm shadow-yellow/20'
                : 'bg-white text-gray-600 border-gray-100 hover:border-gray-200'}`}
              onClick={() => handleSortFilterChange('newest')}
            >
              {t('shop:filterSection.latest')}
            </button>
          </div>
        </div>

        <hr className="border-gray-100 mb-4" />

        {/* Categories */}
        {!categoryName && (
          <>
            <CategoryFilter />
            <hr className="border-gray-100 mb-4" />
          </>
        )}

        {/* Price Range */}
        <PriceRangeFilter />
        <hr className="border-gray-100 mb-4" />

        {/* Sale-Deals */}
        <div className="mb-4">
          <h4 className="font-bold text-gray-800 mb-3">{t('shop:filterSection.saleDeals.title')}</h4>
          <div className="space-y-2">
            <button onClick={() => handleSortFilterChange('all-discounts')} className="text-sm text-gray-500 hover:text-yellow block transition-colors cursor-pointer">{t('shop:filterSection.saleDeals.allDiscounts')}</button>
            <button onClick={() => handleSortFilterChange('today-deals')} className="text-sm text-gray-500 hover:text-yellow block transition-colors cursor-pointer">{t('shop:filterSection.saleDeals.todayDeals')}</button>
          </div>
        </div>

        <hr className="border-gray-100 mb-4" />

        {/* Availability */}
        <div className="mb-2">
          <h4 className="font-bold text-gray-800 mb-3">{t('shop:filterSection.availability.title')}</h4>
          <div className="space-y-2">
            <button onClick={() => handleSortFilterChange('in-stock')} className="text-sm text-gray-500 hover:text-yellow block transition-colors cursor-pointer">{t('shop:filterSection.availability.inStock')}</button>
            <button onClick={() => handleSortFilterChange('out-of-stock')} className="text-sm text-gray-500 hover:text-yellow block transition-colors cursor-pointer">{t('shop:filterSection.availability.outOfStock')}</button>
          </div>
        </div>
      </div>
    </div>
  );
};

export default FilterSection;
