import React from 'react';
import { useTranslation } from 'react-i18next';
import { Link } from 'react-router-dom';

interface CategoryCardProps {
  name: string;
  image: string;
  alt?: string;
  slug?: string;
}

const CategoryCard: React.FC<CategoryCardProps> = ({ name, image, alt, slug }) => {
  const { t } = useTranslation('common');
  
  return (
    <Link to={`/all-products?category=${slug}`} className="flex flex-col items-center group">
      <div className="overflow-hidden rounded-xl shadow-md dark:shadow-[0_4px_15px_rgba(0,0,0,0.4)] cursor-pointer w-full border border-gray-100 dark:border-slate-700/60 bg-white dark:bg-[#151D2C]">
        <div className="aspect-square overflow-hidden relative">
          <img 
            src={image} 
            alt={alt || t('categories.altText', { category: name })} 
            className="w-full h-full object-cover transition-transform duration-300 group-hover:scale-108"
          />
        </div>
      </div>
      <h3 className="mt-3 text-[18px] mobile-large:text-base font-semibold text-gray-800 dark:text-slate-200 group-hover:text-[#B12C2E] dark:group-hover:text-[#E03E41] transition-colors text-center">{name}</h3>
    </Link>
  );
};

export default CategoryCard;
