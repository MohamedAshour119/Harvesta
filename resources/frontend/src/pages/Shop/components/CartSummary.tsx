import React from 'react';
import { useDispatch, useSelector } from 'react-redux';
import { increaseQuantity, decreaseQuantity } from '../../../store/slices/cartSlice';
import type { RootState } from '../../../store/store';
import i18n from '../../../i18n';
import { useTranslation } from 'react-i18next';
import SidebarCartItem from './SidebarCartItem';
import { Link } from 'react-router-dom';
import { IoClose } from 'react-icons/io5';
import { setIsCartSummarySidebarOpen } from '../../../store/slices/isCartSummarySidebarOpenSlice';
import { useNavigate } from 'react-router-dom';
// Add these imports
import { useRef, useEffect, useState } from 'react';
import { motion, AnimatePresence } from 'framer-motion';
import AgentCheckoutBlocker from '../../../auth/AgentCheckoutBlocker';
import { useProtectedAction } from '../../../hooks/useProtectedAction';

const CartSummary: React.FC = () => {
  const { t } = useTranslation(['cart']);
  const isRTL = i18n.language === 'ar';
  const dispatch = useDispatch();
  const { items, subtotal } = useSelector((state: RootState) => state.cart);
  const isCartSummarySidebarOpen = useSelector((state: RootState) => state.isCartSummarySidebarOpen);
  const isAuth = useSelector((state: RootState) => state.auth.isAuth);
  const navigate = useNavigate();
  const [isSmallScreen, setIsSmallScreen] = useState(window.innerWidth < 1130);

  const { 
    handleProtectedAction, 
    checkPendingAction,
  } = useProtectedAction();

  useEffect(() => {
    if (isAuth) {
      checkPendingAction();
    }
  }, [isAuth, checkPendingAction]);

  const handleCheckout = () => {        
    if (isAuth) {
        // dispatch(setIsAddToCartDrawerOpen(false))
        navigate('/checkout');
    } else {
        // If not authenticated, use the protected action handler
        handleProtectedAction('checkout', () => {
            navigate('/checkout');
        });
    }
};

  // Track screen size
  useEffect(() => {
    const handleResize = () => {
      setIsSmallScreen(window.innerWidth < 1130);
    };

    window.addEventListener('resize', handleResize);
    return () => window.removeEventListener('resize', handleResize);
  }, []);

  const handleIncreaseQuantity = (productId: number) => {
    dispatch(increaseQuantity(productId));
  };

  const handleDecreaseQuantity = (productId: number) => {
    dispatch(decreaseQuantity(productId));
  };

  const sidebarRef = useRef<HTMLDivElement>(null);
  
  useEffect(() => {
    const handleClickOutside = (event: MouseEvent) => {
      if (sidebarRef.current && !sidebarRef.current.contains(event.target as Node) && isCartSummarySidebarOpen) {
        dispatch(setIsCartSummarySidebarOpen(false));
      }
    };
    
    document.addEventListener('mousedown', handleClickOutside);
    return () => {
      document.removeEventListener('mousedown', handleClickOutside);
    };
  }, [dispatch, isCartSummarySidebarOpen]);
  
  // Animation variants
  const sidebarVariants = {
    hidden: { 
      x: isRTL ? -300 : 300,
      opacity: 0.5
    },
    visible: { 
      x: 0,
      opacity: 1,
      transition: {
        type: "spring",
        stiffness: 300,
        damping: 30
      }
    },
    exit: {
      x: isRTL ? -300 : 300,
      opacity: 0,
      transition: {
        duration: 0.3,
        ease: "easeInOut"
      }
    }
  };
  
  // Content of the sidebar
  const sidebarContent = (
    <div className="bg-white rounded-lg p-4 sticky top-4">
      <div className="flex justify-between items-center">
        <h3 className="font-semibold text-gray-800 mb-2">
          {t(items.length === 1 ? 'subtotal_one' : 'subtotal_other', { count: items.length })}
        </h3>
        <button className="min-[1130px]:hidden block cursor-pointer bg-gray-300 rounded-full p-1">
          <IoClose size={20} onClick={() => dispatch(setIsCartSummarySidebarOpen(false))} />
        </button>
      </div>
      <div className="border-gray-200 mb-4">
        <div className="flex justify-between items-center text-lg font-medium">
          <span className="flex items-center">
            {t('total')}: &nbsp;
            <span className="flex items-center">
              <span className="text-sm font-bold mx-1">EGP</span> {subtotal?.toFixed(2)}
            </span>
          </span>
        </div>
      </div>

      <AgentCheckoutBlocker onAllowed={() => handleProtectedAction('checkout', ()=>navigate('/checkout'))}>
        <button onClick={handleCheckout} className="w-full bg-yellow hover:bg-yellow/90 text-white font-medium py-2 rounded-full mb-3 cursor-pointer">
          {t('proceedToCheckout')}
        </button>
      </AgentCheckoutBlocker>
      
      <button className="w-full border border-yellow text-yellow font-medium py-2 rounded-full cursor-pointer">
        <Link to="/cart"> {t('goToCart')}</Link>
      </button>
      
      {/* Cart Items */}
      <div className="mt-6">          
        {items.length === 0 ? (
          <div className="text-center py-6">
            <p className="text-gray-500">{t('emptyCart')}</p>
          </div>
        ) : (
          <div className="space-y-4">
            {items.map((item) => (
              <SidebarCartItem
                key={item.id}
                id={item.id}
                name={item.name}
                variantName={item.variantName}
                variantValue={item.variantValue}
                image={item.image}
                price={item.price}
                quantity={item.quantity}
                onIncreaseQuantity={handleIncreaseQuantity}
                onDecreaseQuantity={handleDecreaseQuantity}
              />
            ))}
          </div>
        )}
      </div>
    </div>
  );
  
  // Render different versions based on screen size
  return isSmallScreen ? (
    <AnimatePresence>
      {isCartSummarySidebarOpen && (
        <motion.div 
          ref={sidebarRef}
          className={`fixed right-0 z-50 h-svh bg-white 2xl:w-[24rem] xl:w-[20rem] lg:w-[17rem] w-[20rem] flex-shrink-0 border-[#DDDDDD] ${isRTL ? 'border-r-[2px]' : 'border-l-[2px]'}`}
          variants={sidebarVariants}
          initial="hidden"
          animate="visible"
          exit="exit"
        >
          {sidebarContent}
        </motion.div>
      )}
    </AnimatePresence>
  ) : (
    <div 
      ref={sidebarRef}
      className={`fixed right-0 z-50 h-svh bg-white 2xl:w-[24rem] xl:w-[20rem] lg:w-[17rem] w-[20rem] flex-shrink-0 border-[#DDDDDD] transform transition-transform ease-in-out duration-300 ${isRTL ? 'border-r-[2px]' : 'border-l-[2px]'}`}
    >
      {sidebarContent}
    </div>
  );
};

export default CartSummary;
