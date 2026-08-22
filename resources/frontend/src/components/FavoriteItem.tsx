import React from 'react';
import { FaRegTrashAlt, FaShoppingCart } from 'react-icons/fa';
import type { IFavoriteItem as FavoriteItemType } from '../types';
import { Link, useNavigate } from 'react-router-dom';
import { useDispatch } from 'react-redux';
import { closeFavoritesSidebar } from '../store/slices/favoritesSlice';

interface FavoriteItemProps {
  productId: number;
  variantId: number | undefined;
  productName: string;
  description?: string;
  category: {
    id: number,
    name: string
  };
  variantName?: string;
  variantValue?: string;
  price: number;
  originalPrice?: number;
  image: string | undefined;
  product_main_image: string | undefined;
  slug: string;
  onAddToCart: (item: FavoriteItemType) => void;
  onRemove: (productId: number, variantId: number | undefined) => void;
}

const FavoriteItem: React.FC<FavoriteItemProps> = ({
  productId,
  variantId,
  productName,
  description,
  category,
  variantName,
  variantValue,
  price,
  originalPrice,
  image,
  product_main_image,
  slug,
  onAddToCart,
  onRemove,
}) => {
  const itemDetails: FavoriteItemType = {
    productId,
    variantId,
    slug,
    productName,
    description,
    category,
    variantName,
    variantValue,
    price,
    originalPrice,
    image,
    product_main_image,
  };

  const dispatch = useDispatch();
  const navigate = useNavigate();

  const handleNavigate = (e: React.MouseEvent) => {
    e.preventDefault();
    dispatch(closeFavoritesSidebar());
    navigate(`/products/${slug}`);
  };

  return (
    <li className="flex items-center space-x-3 p-2.5 border border-gray-100 dark:border-slate-700/60 bg-gray-50/50 dark:bg-[#1C2638] rounded-xl hover:bg-gray-100/70 dark:hover:bg-slate-800 transition-colors">
      <div onClick={handleNavigate} className="w-18 h-18 bg-white dark:bg-slate-900 rounded-lg p-1 flex items-center justify-center flex-shrink-0 cursor-pointer border border-gray-100 dark:border-slate-800">
        <img src={image ? image : product_main_image} alt={productName} className="w-full h-full object-contain rounded-md" />
      </div>
      <div className="flex-1 min-w-0">
        <div onClick={handleNavigate} className="text-sm font-semibold text-gray-900 dark:text-slate-100 truncate flex flex-col cursor-pointer hover:text-[#B12C2E] dark:hover:text-[#E03E41] transition-colors" title={productName}>
          <span>{productName}</span>
          {variantName && variantValue && (
            <span className="text-xs text-gray-500 dark:text-slate-400 mt-0.5">{variantName}: {variantValue}</span>
          )}
        </div>
        <div className="text-sm text-gray-700 dark:text-slate-300 mt-1">
          {originalPrice && originalPrice > price && (
            <del className="flex items-center text-xs text-gray-400 dark:text-slate-500">
              <span className="text-xs font-bold mx-1">EGP</span>
              {originalPrice?.toFixed(2)}
            </del>
          )}
          <div className="flex items-center font-bold text-gray-900 dark:text-white">
            <span className="text-xs font-bold mx-1 text-[#B12C2E] dark:text-[#E03E41]">EGP</span> {price?.toFixed(2)}
          </div>
        </div>
      </div>
      <div className="flex flex-col space-y-2">
        <button 
          onClick={() => onAddToCart(itemDetails)}
          className="p-2 bg-[#B12C2E] text-white rounded-lg hover:bg-[#972426] dark:hover:bg-[#C83437] transition-all cursor-pointer shadow-sm"
          title="Add to cart"
        >
          <FaShoppingCart size={13} />
        </button>
        <button 
          onClick={() => onRemove(productId, variantId)}
          className="p-2 bg-gray-100 dark:bg-slate-800 text-gray-500 dark:text-slate-400 rounded-lg hover:bg-red-50 hover:text-red-600 dark:hover:bg-red-950/40 dark:hover:text-red-400 transition-colors cursor-pointer"
          title="Remove from favorites"
        >
          <FaRegTrashAlt size={13} />
        </button>
      </div>
    </li>
  );
};

export default FavoriteItem;