import React, { useState, useEffect } from 'react';
import { FaHeart, FaRegHeart, FaRegTrashAlt } from 'react-icons/fa';
import { MdDone } from 'react-icons/md';
import { PiPlusBold } from 'react-icons/pi';
import { useDispatch, useSelector } from 'react-redux';
import type { RootState } from '../store/store';
import { decreaseQuantity, increaseQuantity, removeFromCart, addToCart, updateCartItemQuantity } from '../store/slices/cartSlice';
import { addToFavorites, removeFromFavorites } from '../store/slices/favoritesSlice';
import { useProtectedAction } from '../hooks/useProtectedAction';
import ApiClient from '../ApiClient';
import { useTranslation } from 'react-i18next';
import { motion, AnimatePresence } from 'framer-motion';
import i18n from '../i18n';
import type { IProductCard, IFavoriteItem, CartItem } from '../types';
import { Link } from 'react-router-dom';
import { isVariantAvailable } from '../utils/productAvailability';

interface ProductCardProps extends IProductCard {
  additionalInfo?: string;
  onAddToCart?: () => void; 
  isAvailable?: boolean;
  stock?: number;
}

const ProductCard: React.FC<ProductCardProps> = (props: ProductCardProps) => {
  const { t } = useTranslation(['products', 'common']);
  const isRTL = i18n.language === 'ar';
  const { 
    id, 
    name, 
    cheapestVariant, 
    slug,
    images, 
    additionalInfo, 
    price: topLevelPriceFallback, 
    description, 
    category,
  } = props;

  const productAvailable = isVariantAvailable(cheapestVariant);

  const dispatch = useDispatch();
  const { items: cartItems } = useSelector((state: RootState) => state.cart);
  const favoriteItems = useSelector((state: RootState) => state.favorites.items);
  const isAuth = useSelector((state: RootState) => state.auth.isAuth);
  
  const cartItem = cartItems.find(item => item.productId === id);
  
  const isFavorite = cheapestVariant ? favoriteItems?.some(
    item => item.productId === id && item.variantId === cheapestVariant.id
  ) : false;

  const [showAlert, setShowAlert] = useState(false);

  const [finalDisplayPrice, setFinalDisplayPrice] = useState<number | undefined>();
  const [originalPriceForDisplay, setOriginalPriceForDisplay] = useState<number | undefined>();

  useEffect(() => {
    let newDisplayPrice: number | undefined;
    let newOriginalPrice: number | undefined;

    if (cheapestVariant) {
      newDisplayPrice = cheapestVariant.price_after_discount !== undefined && cheapestVariant.price_after_discount < cheapestVariant.unit_price_usd
        ? cheapestVariant.price_after_discount
        : cheapestVariant.unit_price_usd;

      newOriginalPrice = cheapestVariant.unit_price_usd;
    } else {
      newDisplayPrice = topLevelPriceFallback;
      newOriginalPrice = topLevelPriceFallback;
    }

    setFinalDisplayPrice(newDisplayPrice);
    setOriginalPriceForDisplay(newOriginalPrice);
  }, [cheapestVariant, topLevelPriceFallback]);

  useEffect(() => {
    if (showAlert) {
      const timer = setTimeout(() => {
        setShowAlert(false);
      }, 2000);
      return () => clearTimeout(timer);
    }
  }, [showAlert]);

  const { 
    checkPendingAction,
    handleProtectedAction,
  } = useProtectedAction();

  useEffect(() => {
    if (isAuth) {
      checkPendingAction();
    }
  }, [isAuth, checkPendingAction]);

  const handleIncrement = () => {
    if (!productAvailable) return;

    if (cartItem) {
      dispatch(increaseQuantity(cheapestVariant ? cheapestVariant.id : id as number));
    } else {
      setShowAlert(true);
      if (cheapestVariant) {
        const cartPayload: Omit<CartItem, 'quantity'> = {
          id: cheapestVariant.id,
          productId: id,
          name: name,
          slug: slug,
          image: images?.main_image || '/placeholder.jpg',
          price: finalDisplayPrice !== undefined ? finalDisplayPrice : Number(cheapestVariant.unit_price_usd),
          totalQuantity: cheapestVariant.total_quantity,
          originalPrice: originalPriceForDisplay,
          description: description || additionalInfo,
          variantName: cheapestVariant.variant_name,
          variantValue: cheapestVariant.variant_value,
          category: category, 
        };

        dispatch(addToCart(cartPayload));
        
        handleProtectedAction('cart', () => {
             ApiClient.recommendations.trackCartAddition(
                id,
                cartPayload.price
             );
        }, {
             productId: id,
             price: cartPayload.price,
        });
      }
    }
  };

  const handleDecrement = () => {    
    const itemIdInCart = cheapestVariant ? cheapestVariant.id : id as number;
    
    if (cartItem && cartItem.quantity) {
      if (cartItem.quantity > 1) {
        dispatch(decreaseQuantity(itemIdInCart));
      } else {
        dispatch(removeFromCart(itemIdInCart));
        setShowAlert(false);
      }
    }
  };

  const addProductToFavorites = async () => {
    if (!cheapestVariant || typeof cheapestVariant.id === 'undefined') {
      console.error('Cannot add to favorites: cheapestVariant or cheapestVariant.id is missing for product ID:', id);
      return;
    }

    const priceForFavorite = finalDisplayPrice !== undefined 
      ? finalDisplayPrice 
      : Number(cheapestVariant.unit_price_usd);

    const favoriteItemPayload: IFavoriteItem = {
      productId: id,
      variantId: cheapestVariant.id,
      productName: name,
      slug: slug,
      category: props.category || { id: 0, name: ""},
      variantName: cheapestVariant.variant_name,
      variantValue: cheapestVariant.variant_value,
      price: priceForFavorite,
      originalPrice: originalPriceForDisplay,
      image: images?.main_image || '', 
    };

    dispatch(addToFavorites(favoriteItemPayload));
    
    const event = new CustomEvent('favoriteStatusChanged', {
        detail: { productId: id, variantId: cheapestVariant.id, canAddToFavorites: false }
    });
    window.dispatchEvent(event);
    
    if (isAuth) {
        ApiClient.wishlist.addToWishlist(id, cheapestVariant.id);
    }
  };

  const removeProductFromFavorites = async () => {
    if (!cheapestVariant || typeof cheapestVariant.id === 'undefined') {
      console.error('Cannot remove from favorites: cheapestVariant or cheapestVariant.id is missing for product ID:', id);
      return;
    }

    dispatch(removeFromFavorites({ productId: id, variantId: cheapestVariant.id }));

    const event = new CustomEvent('favoriteStatusChanged', {
        detail: { productId: id, variantId: cheapestVariant.id, canAddToFavorites: true }
    });
    window.dispatchEvent(event);

    if (isAuth) {
        ApiClient.wishlist.removeFromWishlist(id, cheapestVariant.id);
    }
  };

  const handleWishlistClick = () => {
    if (!cheapestVariant || typeof cheapestVariant.id === 'undefined') {
      console.warn('Wishlist action aborted: cheapest variant information is missing for product ID:', id);
      return;
    }

    if (!isFavorite) { 
      const priceForFavorite = finalDisplayPrice !== undefined ? finalDisplayPrice : Number(cheapestVariant.unit_price_usd);
      const originalPriceForFavorite = originalPriceForDisplay !== undefined ? originalPriceForDisplay : Number(cheapestVariant.unit_price_usd);

      const favoriteItemPayload: IFavoriteItem = {
        productId: id,
        variantId: cheapestVariant.id,
        productName: name,
        slug: slug,
        category: {
            id: 0,
            name: ""
        },
        variantName: cheapestVariant.variant_name,
        variantValue: cheapestVariant.variant_value,
        price: priceForFavorite,
        originalPrice: originalPriceForFavorite,
        image: images?.main_image || '',  
      };

      handleProtectedAction('wishlist', addProductToFavorites, {
        productId: id,
        variantId: cheapestVariant.id,
        action: 'add',
        product: favoriteItemPayload, 
      });
    } else { 
      handleProtectedAction('wishlist', removeProductFromFavorites, {
        productId: id,
        variantId: cheapestVariant.id,
        action: 'remove',
      });
    }
  };

  const onQuantityChange = (quantity: number) => {
    dispatch(updateCartItemQuantity({ id: cheapestVariant?.id!, quantity }));
  };

  const mainImage = images?.main_image || '/placeholder.jpg'; 

  return (
    <>
      <div
        data-product-id={id}
        className="bg-white dark:bg-[#151D2C] h-full rounded-[16px] overflow-hidden shadow-sm hover:shadow-lg dark:hover:shadow-[0_10px_25px_rgba(0,0,0,0.4)] transition-all duration-300 relative border-[2px] border-card_border dark:border-slate-700/60 hover:border-yellow dark:hover:border-[#E03E41] cursor-pointer"
      >
        {/* Favorite Button */}
        <button
          className="absolute top-2 right-2 z-10 bg-white/90 dark:bg-slate-800/90 backdrop-blur-xs rounded-full p-1.5 shadow-sm border border-gray-100 dark:border-slate-700 cursor-pointer transition-transform hover:scale-110"
          onClick={(e) => {
            e.stopPropagation();
            e.preventDefault();
            handleWishlistClick();
          }}
          aria-label={isFavorite ? t('products:product.removeFromFavorites') : t('products:product.addToFavorites')}
        >
          {isFavorite ? (
            <FaHeart className='text-pink size-4' />
          ) : (
            <FaRegHeart className='text-pink size-4' />
          )}
        </button>

        {/* Product Image */}
        <Link to={`/products/${slug}`} className="w-full h-40 relative p-2 flex justify-center items-center bg-gray-50/50 dark:bg-slate-900/40 transition-colors">
          <img 
            src={mainImage} 
            alt={name} 
            className="max-w-full w-fit h-fit max-h-full object-contain rounded-lg transition-transform duration-300 hover:scale-105" 
          />
        </Link>

        {/* Unavailable Badge - Centered on the entire card */}
        {!productAvailable && (
          <Link to={`/products/${slug}`} className="absolute inset-0 flex items-center justify-center z-50">
            <div className="bg-red-500 text-white px-4 py-2 rounded-full font-medium text-sm shadow-lg">
              {t('products:product.unavailable', 'Out of Stock')}
            </div>
          </Link>
        )}

        {/* out of stock bg inset */}
        {!productAvailable && (
          <Link to={`/products/${slug}`} className="absolute inset-0 bg-black/20 backdrop-blur-[1px] flex items-center justify-center z-50"></Link>
        )}

        {/* Added to Cart Indicator with animation */}
        <AnimatePresence>
          {showAlert && (
            <motion.div 
              className="absolute top-[20%] left-1/2 -translate-x-1/2 w-[90%] h-max z-50"
              initial={{ opacity: 0, y: -20 }}
              animate={{ opacity: 1, y: 0 }}
              exit={{ opacity: 0, y: -20 }}
              transition={{ 
                duration: 0.3,
                type: "spring",
                stiffness: 300,
                damping: 24
              }}
            >
              <motion.div 
                className={`bg-success text-white text-[14px] font-medium px-2 gap-x-2 rounded-full flex items-center justify-center shadow-lg ${isRTL ? 'h-[55px]' : 'h-[40px]'}`}
                initial={{ scale: 0.9 }}
                animate={{ scale: 1 }}
                transition={{ duration: 0.2, delay: 0.1 }}
              >
                <MdDone className='size-6'/>
                {cartItem 
                  ? t('products:product.addedMultipleToCart', { count: cartItem.quantity }) 
                  : t('products:product.addedOneToCart')}
              </motion.div>
            </motion.div>
          )}
        </AnimatePresence>

        {/* Product Info */}
        <div className="p-4">
          <Link to={`/products/${slug}`}>
            <h3 className="font-semibold text-sm text-gray-900 dark:text-slate-100 line-clamp-2 mb-2 hover:text-[#B12C2E] dark:hover:text-[#E03E41] transition-colors">
              {name}
            </h3>
            {description && (
              <p
                className="text-xs text-gray-600 dark:text-slate-400 mb-3 line-clamp-2"
                title={description}
              >
                {description}
              </p>
            )}
          </Link>

          <div className="flex justify-between items-center mt-2 min-h-[32px] gap-x-1">
            <Link to={`/products/${slug}`} className="flex items-center flex-wrap">
              {finalDisplayPrice !== undefined ? (
                <>
                  {originalPriceForDisplay !== undefined &&
                    props.cheapestVariant?.discount_percentage !== undefined &&
                    props.cheapestVariant.discount_percentage > 0 && (
                      <del className={`text-gray-400 dark:text-slate-500 text-xs flex items-center ${isRTL ? 'ml-2' : 'mr-2'}`}>
                        <span className="text-sm font-bold mx-1">EGP</span> {originalPriceForDisplay.toFixed(2)}
                      </del>
                  )}

                  <span className="text-gray-900 dark:text-slate-100 font-bold flex items-center">
                    <span className="text-xs font-bold mx-1 text-[#B12C2E] dark:text-[#E03E41]">EGP</span> {typeof finalDisplayPrice === 'number' ? finalDisplayPrice.toFixed(2) : t('products:product.loadingPrice')}
                  </span>
                </>
              ) : (
                <span className="text-gray-800 dark:text-slate-200 font-medium">{t('products:product.loadingPrice')}</span>
              )}
            </Link>

            {/* Cart Controls */}
            {cartItem ? (
              <div className="flex items-center justify-between rounded-full bg-[#B12C2E] text-white min-w-[84px] shadow-sm">
                <button
                  onClick={(e) => {
                    e.stopPropagation();
                    handleDecrement();
                  }}
                  className="px-2 py-1 cursor-pointer hover:bg-black/10 rounded-l-full"
                  aria-label={cartItem.quantity === 1 
                    ? t('products:product.removeFromCart') 
                    : t('products:product.decreaseQuantity')}
                >
                  {cartItem.quantity === 1 ? <FaRegTrashAlt className='size-3 sm:size-4' /> : '-'}
                </button>

                <span className="px-2 py-1">
                  <input
                    type="number"
                    min={1}
                    max={cartItem.totalQuantity!}
                    value={cartItem.quantity}
                    onClick={(e) => {
                      e.stopPropagation();
                      e.preventDefault();
                    }}
                    onChange={(e) => {
                      e.stopPropagation();
                      e.preventDefault();
                      const parsedValue = parseInt(e.target.value, 10);
                      const value = isNaN(parsedValue) ? 1 : parsedValue;
                      const clampedValue = Math.min(
                        cheapestVariant?.total_quantity!,
                        Math.max(1, value)
                      );
                      onQuantityChange(clampedValue);
                    }}
                    className="w-4 sm:w-5 flex items-center justify-center text-white font-medium focus:outline-none bg-transparent"
                  />
                </span>

                <button
                  onClick={(e) => {
                    e.stopPropagation();
                    handleIncrement();
                  }}
                  className="px-2 py-1 cursor-pointer hover:bg-black/10 rounded-r-full"
                  aria-label={t('products:product.increaseQuantity')}
                >
                  +
                </button>
              </div>
            ) : (
              <button
                onClick={(e) => {
                  e.stopPropagation();
                  handleIncrement();
                }}
                className="bg-[#B12C2E] hover:bg-[#972426] dark:bg-[#B12C2E] dark:hover:bg-[#C83437] text-white rounded-full p-1.5 cursor-pointer shadow-sm transition-all"
                aria-label={t('products:product.addToCart')}
              >
                <PiPlusBold className='text-white size-4'/>
              </button>
            )}
          </div>
        </div>
      </div>
    </>
  );
};

export default ProductCard;
