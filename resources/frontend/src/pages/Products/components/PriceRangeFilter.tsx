import React, { useState, useEffect } from 'react';
import { useDispatch, useSelector } from 'react-redux';
import { setPriceRange } from '../../../store/slices/filterSlice';
import type { RootState } from '../../../store/store';
import { useTranslation } from 'react-i18next';
import { useDebounceRange } from '../../../hooks/UseDebounceRange';
import Slider from 'rc-slider';
import 'rc-slider/assets/index.css';
import i18n from '../../../i18n';

const MAX_SLIDER_PRICE = 1000;
const MIN_SLIDER_PRICE = 0;

const PriceRangeFilter: React.FC = () => {
  const { t } = useTranslation(['shop']);
  const dispatch = useDispatch();
  const isRTL = i18n.language === 'ar';
  const storePriceRange = useSelector((state: RootState) => state.filter?.priceRange || [0, 10000]);

  const initialMin = Math.max(MIN_SLIDER_PRICE, storePriceRange[0]);
  const initialMax = Math.min(MAX_SLIDER_PRICE, storePriceRange[1] === 0 ? MAX_SLIDER_PRICE : storePriceRange[1]);
  const [currentRange, setCurrentRange] = useState<[number, number]>([initialMin, initialMax]);

  const debouncedRange = useDebounceRange(currentRange, 500);

  useEffect(() => {
    const newEffectiveMin = debouncedRange[0];
    const newEffectiveMax = debouncedRange[1] === MAX_SLIDER_PRICE ? 0 : debouncedRange[1];

    if (newEffectiveMin !== storePriceRange[0] || newEffectiveMax !== storePriceRange[1]) {
      dispatch(setPriceRange([newEffectiveMin, newEffectiveMax]));
    }
  }, [debouncedRange, dispatch]);

  useEffect(() => {
    const newMin = Math.max(MIN_SLIDER_PRICE, storePriceRange[0]);
    const newMax = Math.min(MAX_SLIDER_PRICE, storePriceRange[1] === 0 ? MAX_SLIDER_PRICE : storePriceRange[1]);
    setCurrentRange([newMin, newMax]);
  }, [storePriceRange]);

  const handleSliderChange = (newRange: number | number[]) => {
    if (Array.isArray(newRange)) {
      setCurrentRange(newRange as [number, number]);
    }
  };

  return (
    <div className="mb-0 px-1">
      <h4 className="text-base font-bold text-gray-900 dark:text-slate-100 mb-4">{t('shop:filterSection.priceRange')}</h4>
      <div className={`flex justify-between mb-3 text-sm text-gray-700 dark:text-slate-300 ${isRTL ? 'flex-row-reverse' : ''}`}>
        <span className='flex items-center font-medium'>
          <span className="text-xs font-bold mx-1 text-[#B12C2E] dark:text-[#E03E41]">EGP</span>
          {isRTL ? (currentRange[1] === MAX_SLIDER_PRICE ? `${MAX_SLIDER_PRICE}+` : currentRange[1]) : currentRange[0]}
        </span>
        <span className='flex items-center font-medium'>
          <span className="text-xs font-bold mx-1 text-[#B12C2E] dark:text-[#E03E41]">EGP</span>
          {isRTL ? currentRange[0] : (currentRange[1] === MAX_SLIDER_PRICE ? `${MAX_SLIDER_PRICE}+` : currentRange[1])}
        </span>
      </div>
      <Slider
        range
        min={MIN_SLIDER_PRICE}
        max={MAX_SLIDER_PRICE}
        value={currentRange}
        onChange={handleSliderChange}
        allowCross={false}
        reverse={isRTL}
        trackStyle={[{ backgroundColor: '#B12C2E' }]}
        handleStyle={[
          { borderColor: '#B12C2E', backgroundColor: 'white' },
          { borderColor: '#B12C2E', backgroundColor: 'white' }
        ]}
        railStyle={{ backgroundColor: 'rgba(150, 150, 150, 0.25)' }}
        activeDotStyle={{ backgroundColor: '#B12C2E' }}
      />
    </div>
  );
};

export default PriceRangeFilter;
