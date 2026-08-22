import React, { useState, useEffect } from 'react';
import { useDispatch, useSelector } from 'react-redux';
import { setPriceRange } from '../../../store/slices/filterSlice';
import type { RootState } from '../../../store/store';
import { useTranslation } from 'react-i18next';
import { useDebounceRange } from '../../../hooks/UseDebounceRange';
import Slider from 'rc-slider'; 
import 'rc-slider/assets/index.css'; 

const MAX_SLIDER_PRICE = 1000; 
const MIN_SLIDER_PRICE = 0;


const PriceRangeFilter: React.FC = () => {
  const { t } = useTranslation(['shop']);
  const dispatch = useDispatch();
  const storePriceRange = useSelector((state: RootState) => state.filter.priceRange);

  const initialMin = Math.max(MIN_SLIDER_PRICE, storePriceRange[0]);
  const initialMax = Math.min(MAX_SLIDER_PRICE, storePriceRange[1] === 0 ? MAX_SLIDER_PRICE : storePriceRange[1]); 
  const [currentRange, setCurrentRange] = useState<[number, number]>([initialMin, initialMax]);

  const debouncedRange = useDebounceRange(currentRange, 500); 

  useEffect(() => {
    // Calculate the min and max values that would be dispatched
    const newEffectiveMin = debouncedRange[0];
    const newEffectiveMax = debouncedRange[1] === MAX_SLIDER_PRICE ? 0 : debouncedRange[1];

    // Only dispatch if these effective values are different from what's already in the store
    if (newEffectiveMin !== storePriceRange[0] || newEffectiveMax !== storePriceRange[1]) {
      dispatch(setPriceRange([newEffectiveMin, newEffectiveMax]));
    }
  }, [debouncedRange, storePriceRange, dispatch]);

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
    <div className="mb-6 px-2">
      <h4 className="font-medium text-gray-700 mb-3">{t('shop:filterSection.priceRange')}</h4>
      <div className="flex justify-between mb-2 text-sm text-gray-600">
        <span>₦{currentRange[0]}</span> 
        <span>₦{currentRange[1] === MAX_SLIDER_PRICE ? `${MAX_SLIDER_PRICE}+` : currentRange[1]}</span>
      </div>
      <Slider
        range 
        min={MIN_SLIDER_PRICE}
        max={MAX_SLIDER_PRICE}
        value={currentRange}
        onChange={handleSliderChange}
        allowCross={false} 
        trackStyle={[{ backgroundColor: 'var(--color-yellow)' }]} 
        handleStyle={[{ borderColor: 'var(--color-yellow)', backgroundColor: 'white' }, { borderColor: 'var(--color-yellow)', backgroundColor: 'white' }]}
        railStyle={{ backgroundColor: 'var(--color-card_border)' }}
        activeDotStyle={{ backgroundColor: 'var(--color-yellow)' }}
      />
    </div>
  );
};

export default PriceRangeFilter;
