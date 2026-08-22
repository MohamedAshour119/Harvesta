import React, { useState } from 'react';
import { FaStar, FaStarHalfAlt, FaRegStar } from 'react-icons/fa';
import { useTranslation } from 'react-i18next';

// Try to import @smastrom/react-rating, fallback to react-icons if not available
let Rating: any = null;
try {
  const ratingModule = require('@smastrom/react-rating');
  Rating = ratingModule.Rating;
  require('@smastrom/react-rating/style.css');
} catch (e) {
  console.log('Using fallback star rating with react-icons');
}

interface StarRatingProps {
  rating: number | null;
  onRatingChange?: (rating: number) => void;
  interactive?: boolean;
  size?: 'sm' | 'md' | 'lg';
  allowHalf?: boolean;
}

export const StarRating: React.FC<StarRatingProps> = ({
  rating,
  onRatingChange,
  interactive = false,
  size = 'md',
  allowHalf = true,
}) => {
  const [hoveredStar, setHoveredStar] = useState<number>(0);
  const { t } = useTranslation(['showProduct']);

  // If @smastrom/react-rating is available, use it
  if (Rating) {
    const getSizeValue = () => {
      switch (size) {
        case 'sm':
          return 20;
        case 'md':
          return 24;
        case 'lg':
          return 32;
        default:
          return 24;
      }
    };

    const customStyles = {
      itemShapes: {
        activeFillColor: '#fbbf24',
        inactiveFillColor: '#e5e7eb',
      },
      activeFillColor: '#fbbf24',
      inactiveFillColor: '#e5e7eb',
    };

    return (
      <div className="flex items-center space-x-2">
        <Rating
          value={rating}
          onChange={interactive ? onRatingChange : undefined}
          readOnly={!interactive}
          style={{ maxWidth: getSizeValue() * 5.5 }}
          itemStyles={customStyles}
          halfFillMode={allowHalf ? 'svg' : 'box'}
          transition="zoom"
          spaceBetween="small"
          highlightOnlySelected={interactive}
        />
        {interactive && onRatingChange && (
          <span className="text-sm text-gray-600 min-w-[60px]">
            {(rating && rating > 0) ? `${rating}/5` : t('showProduct:rate_this')}
          </span>
        )}
      </div>
    );
  }

  // Fallback implementation using react-icons
  const getSizeClasses = () => {
    switch (size) {
      case 'sm':
        return 'w-4 h-4';
      case 'md':
        return 'w-5 h-5';
      case 'lg':
        return 'w-6 h-6';
      default:
        return 'w-5 h-5';
    }
  };

  const getStarIcon = (starIndex: number, currentRating: number, isHovered: boolean = false) => {
    const sizeClasses = getSizeClasses();
    const effectiveRating = isHovered ? hoveredStar : currentRating;
    const starValue = starIndex + 1;
    const halfStarValue = starIndex + 0.5;

    if (effectiveRating >= starValue) {
      return <FaStar className={`${sizeClasses} text-yellow-400`} />;
    } else if (allowHalf && effectiveRating >= halfStarValue) {
      return <FaStarHalfAlt className={`${sizeClasses} text-yellow-400`} />;
    } else {
      return <FaRegStar className={`${sizeClasses} text-gray-300`} />;
    }
  };

  const handleStarClick = (starIndex: number, isHalf: boolean = false) => {
    if (!interactive || !onRatingChange) return;
    
    const newRating = starIndex + (isHalf ? 0.5 : 1);
    onRatingChange(newRating);
  };

  const handleStarHover = (starIndex: number, isHalf: boolean = false) => {
    if (!interactive) return;
    
    setHoveredStar(starIndex + (isHalf ? 0.5 : 1));
  };

  const handleMouseLeave = () => {
    if (!interactive) return;
    
    setHoveredStar(0);
  };

  const renderStar = (starIndex: number) => {
    const isHovered = interactive && hoveredStar > 0;
    const icon = getStarIcon(starIndex, rating || 0, isHovered);

    if (!interactive) {
      return (
        <span key={starIndex} className="inline-block">
          {icon}
        </span>
      );
    }

    return (
      <button
        key={starIndex}
        type="button"
        className="relative inline-block focus:outline-none focus:ring-2 focus:ring-yellow-400 focus:ring-opacity-50 rounded"
        onMouseLeave={handleMouseLeave}
      >
        {/* Full star area */}
        <div
          className="absolute inset-0 cursor-pointer hover:scale-110 transition-transform"
          onClick={() => handleStarClick(starIndex, false)}
          onMouseEnter={() => handleStarHover(starIndex, false)}
        />
        
        {/* Half star area (left side) */}
        {allowHalf && (
          <div
            className="absolute left-0 top-0 w-1/2 h-full cursor-pointer hover:scale-110 transition-transform z-10"
            onClick={() => handleStarClick(starIndex, true)}
            onMouseEnter={() => handleStarHover(starIndex, true)}
          />
        )}
        
        {icon}
      </button>
    );
  };

  return (
    <div className="flex items-center space-x-1">
      {Array.from({ length: 5 }, (_, index) => renderStar(index))}
      {interactive && (
        <span className="ml-2 text-sm text-gray-600">
          {hoveredStar > 0 
            ? `${hoveredStar}/5` 
            : rating && rating > 0 
            ? `${Number.isInteger(Number(rating)) ? Number(rating) : Number(rating).toFixed(1)}/5`
            : t('rate_this')
          }
        </span>
      )}
    </div>
  );
};
