import React, { useEffect, useRef } from 'react';
import { useDispatch, useSelector } from 'react-redux';
import { FaTimes, FaHeart, FaRegHeart } from 'react-icons/fa';
import { removeFromFavorites, closeFavoritesSidebar, clearFavorites } from '../store/slices/favoritesSlice';
import { addToCart } from '../store/slices/cartSlice';
import type { RootState } from '../store/store';
import FavoriteItem from './FavoriteItem';
import { useTranslation } from 'react-i18next';
import i18n from '../i18n';
import type { CartItem, IFavoriteItem } from '../types';
import ApiClient from '../ApiClient';

const FavoritesSidebar: React.FC = () => {
  const dispatch = useDispatch();
  const { items, isOpen } = useSelector((state: RootState) => state.favorites);
  const isAuth = useSelector((state: RootState) => state.auth.isAuth);
  const sidebarRef = useRef<HTMLDivElement>(null);
  const { t } = useTranslation(['common']);
  const isRTL = i18n.language === 'ar';

  useEffect(() => {
    const handleClickOutside = (event: MouseEvent) => {
      if (sidebarRef.current && !sidebarRef.current.contains(event.target as Node) && isOpen) {
        dispatch(closeFavoritesSidebar());
      }
    };

    document.addEventListener('mousedown', handleClickOutside);
    return () => {
      document.removeEventListener('mousedown', handleClickOutside);
    };
  }, [dispatch, isOpen]);

  useEffect(() => {
    if (isOpen) {
      document.body.style.overflow = 'hidden';
    } else {
      document.body.style.overflow = 'auto';
    }
    return () => {
      document.body.style.overflow = 'auto';
    };
  }, [isOpen]);

  const handleRemoveItem = (productId: number, variantId: number) => {
    dispatch(removeFromFavorites({productId, variantId}));
    if (isAuth) {
      ApiClient.wishlist.removeFromWishlist(productId, variantId)
        .catch(error => {
          console.error('Failed to remove item from wishlist API:', error);
        });
    }
  };

  const handleAddToCart = (item: IFavoriteItem) => {
    const cartProductPayload: CartItem = {
      id: item.variantId!,
      productId: item.productId,
      name: item.productName,
      slug: item.slug,
      price: item.price, 
      image: item.product_main_image!,
      originalPrice: item.originalPrice,
      description: item.description,
      variantName: item.variantName,
      variantValue: item.variantValue,
      category: item.category,
      quantity: 1,
    };
    dispatch(addToCart(cartProductPayload));
  };

  useEffect(() => {
    if (!isAuth) {
      dispatch(clearFavorites())
    }
  }, [isAuth])
  
  return (
    <>
      {isOpen && (
        <div className="fixed inset-0 bg-black/60 backdrop-blur-xs z-[95] transition-opacity duration-300"></div>
      )}

      <div 
        ref={sidebarRef}
        className={`fixed top-0 ${isRTL ? 'left-0' : 'right-0'} h-full w-[23rem] bg-white dark:bg-[#151D2C] shadow-2xl border-x border-gray-100 dark:border-slate-800 z-[100] transform transition-transform duration-300 ease-in-out ${isOpen ? 'translate-x-0' : isRTL ? '-translate-x-full' : 'translate-x-full'}`}
      >
        <div className="flex justify-between items-center p-4 border-b border-gray-200 dark:border-slate-700/60 bg-white dark:bg-[#151D2C]">
          <h2 className="text-lg font-bold text-gray-900 dark:text-slate-100 flex items-center">
            <FaHeart className="text-pink me-2" /> {t('common:favorites.title')}
            <span className="ms-2 bg-gray-100 dark:bg-slate-800 text-gray-700 dark:text-slate-300 text-xs font-semibold px-2 py-0.5 rounded-full border border-gray-200 dark:border-slate-700">
              {items?.length}
            </span>
          </h2>
          <button 
            onClick={() => dispatch(closeFavoritesSidebar())}
            className="text-gray-500 dark:text-slate-400 hover:text-gray-800 dark:hover:text-white p-1 rounded-lg hover:bg-gray-100 dark:hover:bg-slate-800 transition-colors cursor-pointer"
            aria-label="Close favorites"
          >
            <FaTimes size={18} />
          </button>
        </div>

        <div className="overflow-y-auto h-[calc(100%-8rem)] p-4">
          {items?.length === 0 ? (
            <div className="flex flex-col items-center justify-center h-full text-center">
              <FaRegHeart className="text-gray-300 dark:text-slate-700 text-5xl mb-4" />
              <p className="text-gray-600 dark:text-slate-300 font-semibold mb-1">{t('common:favorites.emptyList')}</p>
              <p className="text-gray-400 dark:text-slate-500 text-sm">{t('common:favorites.emptyMessage')}</p>
            </div>
          ) : (
            <ul className="space-y-3">
              {items?.map((item) => (
                <FavoriteItem 
                  key={`${item.productId}-${item.variantId}`}
                  productId={item.productId}
                  variantId={item.variantId}
                  productName={item.productName}
                  category={item.category}
                  variantName={item.variantName}
                  variantValue={item.variantValue}
                  price={item.price}
                  originalPrice={item.originalPrice}
                  image={item.image}
                  product_main_image={item.product_main_image}
                  slug={item.slug}
                  onAddToCart={() => handleAddToCart(item)}
                  onRemove={() => handleRemoveItem(item.productId, item.variantId)}
                />
              ))}
            </ul>
          )}
        </div>

        {items?.length > 0 && (
          <div className="absolute bottom-0 left-0 right-0 p-4 border-t border-gray-200 dark:border-slate-700/60 bg-white dark:bg-[#151D2C]">
            <button 
              onClick={() => dispatch(closeFavoritesSidebar())}
              className="w-full py-2.5 bg-gray-100 dark:bg-slate-800 text-gray-800 dark:text-slate-200 font-semibold rounded-xl hover:bg-gray-200 dark:hover:bg-slate-700 transition-colors cursor-pointer border border-gray-200 dark:border-slate-700"
            >
              {t('common:favorites.continueShopping')}
            </button>
          </div>
        )}
      </div>
    </>
  );
};

export default FavoritesSidebar;
