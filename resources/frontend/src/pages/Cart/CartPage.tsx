import React, { useEffect, useState, useMemo } from 'react';
import { useDispatch, useSelector } from 'react-redux';
import { useTranslation } from 'react-i18next';
import { increaseQuantity, decreaseQuantity, batchUpdateCartItemLanguages, setSmartBundleCTAs } from '../../store/slices/cartSlice';
import { openEmptyCartModal } from '../../store/slices/emptyCartModalSlice';
import type { RootState } from '../../store/store';
import MaxWidthWrapper from '../../components/MaxWidthWrapper';
import FooterSection from '../../components/FooterSection';
import CartItem from '../Shop/components/CartItem';
import { FaArrowLeft, FaArrowRight } from 'react-icons/fa';
import { LuShoppingCart } from 'react-icons/lu';
import ApiClient from '../../ApiClient';
import type { CartItem as CartItemType } from '../../types';
import { Link, useNavigate } from 'react-router-dom';
import { useProtectedAction } from '../../hooks/useProtectedAction';
import AgentCheckoutBlocker from '../../auth/AgentCheckoutBlocker';

const CartPage: React.FC = () => {
  const { t } = useTranslation(['cart']);
  const i18n = useTranslation().i18n;
  const dispatch = useDispatch();
  const navigate = useNavigate();
  const cartState = useSelector((state: RootState) => state.cart);
  const items = cartState?.items || [];
  const subtotal = cartState?.subtotal || 0;
  const total = cartState?.total || 0;
  const smartBundleCTAs = cartState?.smartBundleCTAs || [];

  // Prevent accessing empty cart page directly
  useEffect(() => {
    if (items.length === 0) {
      dispatch(openEmptyCartModal());
      navigate('/all-products', { replace: true });
    }
  }, [items.length, dispatch, navigate]);

  const [selectedCategoryId, setSelectedCategoryId] = useState<number | null>(null);

  const cartProductCategories = useMemo(() => {
    const uniqueCategoriesMap = new Map<number, { id: number; name: string }>();

    items.forEach((item: CartItemType) => {
      if (item.category && typeof item.category.id === 'number' && typeof item.category.name === 'string') {
        if (!uniqueCategoriesMap.has(item.category.id)) {
          uniqueCategoriesMap.set(item.category.id, { id: item.category.id, name: item.category.name });
        }
      }
    });

    return Array.from(uniqueCategoriesMap.values());
  }, [items]);

  const handleIncreaseQuantity = (productId: number) => {
    dispatch(increaseQuantity(productId));
  };

  const handleDecreaseQuantity = (productId: number) => {
    dispatch(decreaseQuantity(productId));
  };

  const handleCategoryFilterClick = (categoryId: number | null) => {
    if (selectedCategoryId !== categoryId) {
      setSelectedCategoryId(categoryId);
    }
  };

  useEffect(() => {
    if (selectedCategoryId !== null) {
      const isSelectedCategoryStillPresent = cartProductCategories.some(cat => cat.id === selectedCategoryId);
      if (!isSelectedCategoryStillPresent) {
        setSelectedCategoryId(null);
      }
    }
  }, [cartProductCategories, selectedCategoryId]);

  const filteredCartItems = selectedCategoryId
    ? items.filter((item: CartItemType) => item.category?.id === selectedCategoryId)
    : items;

  useEffect(() => {
    const handleLanguageChange = async () => {
      if (items.length > 0) {
        const productIds: any[] = items.map((item: CartItemType) => ({
          productId: item.productId,
          productVariantId: item.id,
          quantity: item.quantity
        }));

        try {
          const response = await ApiClient.products.getProductsByIds(productIds);
          const responseData = response.data.data as any;
          const translatedProducts = responseData?.products || responseData || [];

          if (Array.isArray(translatedProducts) && translatedProducts.length > 0) {
            dispatch(batchUpdateCartItemLanguages(translatedProducts));
          }

          if (responseData?.smart_bundle_ctas) {
            dispatch(setSmartBundleCTAs(responseData.smart_bundle_ctas));
          }
        } catch (error) {
          console.error('Error fetching updated product details in batch:', error);
        }
      }
    };

    handleLanguageChange();

    i18n.on('languageChanged', handleLanguageChange);

    return () => {
      i18n.off('languageChanged', handleLanguageChange);
    };
  }, [i18n, i18n.language, items]);

  const { handleProtectedAction } = useProtectedAction();

  return (
    <>
      <MaxWidthWrapper>
        <div className="py-8">
          <h1 className="text-2xl font-bold mb-6 flex items-center gap-2 text-gray-900 dark:text-slate-100">
            <LuShoppingCart className="text-[#B12C2E] dark:text-[#E03E41]" /> {t('cart')} ({filteredCartItems.length})
          </h1>

          {/* Category tabs */}
          {cartProductCategories.length > 0 && (
            <div className="flex flex-wrap gap-2 mb-6">
              <button
                onClick={() => handleCategoryFilterClick(null)}
                className={`px-4 py-2 text-sm font-semibold rounded-full cursor-pointer border transition-all
                  ${selectedCategoryId === null
                    ? 'bg-[#B12C2E] text-white border-[#B12C2E] shadow-sm'
                    : 'bg-white dark:bg-[#151D2C] text-gray-700 dark:text-slate-300 border-gray-200 dark:border-slate-700 hover:bg-gray-50 dark:hover:bg-slate-800'
                  }`}
              >
                {t('all_categories')}
              </button>
              {cartProductCategories.map((category) => (
                <button
                  key={category.id}
                  onClick={() => handleCategoryFilterClick(category.id)}
                  className={`px-4 py-2 text-sm font-semibold rounded-full border transition-all cursor-pointer
                    ${selectedCategoryId === category.id
                      ? 'bg-[#B12C2E] text-white border-[#B12C2E] shadow-sm'
                      : 'bg-white dark:bg-[#151D2C] text-gray-700 dark:text-slate-300 border-gray-200 dark:border-slate-700 hover:bg-gray-50 dark:hover:bg-slate-800'
                    }`}
                >
                  {category.name}
                </button>
              ))}
            </div>
          )}

          <div className="flex flex-col lg:flex-row gap-6">
            {/* Left side - Cart Items */}
            <div className="flex-1">
              {filteredCartItems.length === 0 ? (
                <div className="text-center py-16 bg-white dark:bg-[#151D2C] border border-gray-100 dark:border-slate-800 rounded-2xl shadow-sm">
                  <LuShoppingCart className="size-16 mx-auto mb-4 text-gray-300 dark:text-slate-700" />
                  <p className="text-gray-600 dark:text-slate-400 font-medium text-lg">{t('emptyCart')}</p>
                </div>
              ) : (
                <div className="space-y-4">
                  {filteredCartItems.map((item: CartItemType) => (
                    <div key={item.id} className="min-h-[195px]">
                      <CartItem
                        id={item.id}
                        name={item.name}
                        slug={item.slug}
                        description={item.description}
                        image={item.image}
                        price={item.price}
                        originalPrice={item.originalPrice}
                        quantity={item.quantity}
                        total_quantity={item.totalQuantity}
                        onIncreaseQuantity={handleIncreaseQuantity}
                        onDecreaseQuantity={handleDecreaseQuantity}
                        cta={smartBundleCTAs?.find(c => c.product_id === item.productId)}
                      />
                    </div>
                  ))}
                </div>
              )}
            </div>

            {/* Right side - Order Summary */}
            <div className="w-full lg:w-80 flex-shrink-0 bg-gray-50 dark:bg-[#151D2C] border border-gray-200 dark:border-slate-700/60 rounded-3xl h-fit p-6 shadow-sm">
              <h2 className="text-xl font-bold mb-6 text-gray-900 dark:text-slate-100">{t('orderSummary')}</h2>

              <div className="space-y-4 mb-6">
                <div className="flex justify-between text-sm">
                  <span className="text-gray-600 dark:text-slate-400">{t(filteredCartItems.length === 1 ? 'subtotal_one' : 'subtotal_other', { count: filteredCartItems.length })}</span>
                  <span className="font-semibold text-gray-900 dark:text-slate-100 flex items-center">
                    <span className="text-xs font-bold mx-1 text-[#B12C2E] dark:text-[#E03E41]">EGP</span>
                    {subtotal?.toFixed(2)}
                  </span>
                </div>

                <div className="border-t border-gray-200 dark:border-slate-700/60 pt-4">
                  <div className="flex justify-between font-bold text-base text-gray-900 dark:text-white">
                    <span>{t('total')}</span>
                    <span className="flex items-center text-lg text-[#B12C2E] dark:text-[#E03E41]">
                      <span className="text-xs font-bold mx-1">EGP</span>
                      {total?.toFixed(2)}
                    </span>
                  </div>
                </div>
              </div>

              <AgentCheckoutBlocker onAllowed={() => filteredCartItems.length > 0 && handleProtectedAction('checkout', () => navigate('/checkout'))}>
                <Link
                  to={filteredCartItems.length === 0 ? '#' : "/checkout"}
                  onClick={(e) => filteredCartItems.length === 0 && e.preventDefault()}
                  className={`flex items-center justify-center w-full font-semibold py-3.5 rounded-xl transition-all duration-200 shadow-md
                    ${filteredCartItems.length === 0
                      ? 'bg-gray-200 dark:bg-slate-800 cursor-not-allowed text-gray-400 dark:text-slate-600'
                      : 'bg-[#B12C2E] hover:bg-[#972426] dark:bg-[#B12C2E] dark:hover:bg-[#C83437] cursor-pointer text-white active:scale-[0.98]'
                    }`}
                >
                  {t('proceedToCheckout')}
                  <span className={i18n.language === 'ar' ? 'mr-2' : 'ml-2'}>
                    {i18n.language === 'ar' ? (
                      <FaArrowLeft />
                    ) : (
                      <FaArrowRight />
                    )}
                  </span>
                </Link>
              </AgentCheckoutBlocker>
            </div>
          </div>
        </div>
      </MaxWidthWrapper>
      <FooterSection />
    </>
  );
};

export default CartPage;