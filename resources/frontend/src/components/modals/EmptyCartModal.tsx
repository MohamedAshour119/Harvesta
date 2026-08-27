import React, { useEffect } from 'react';
import { useDispatch, useSelector } from 'react-redux';
import { useNavigate } from 'react-router-dom';
import { motion, AnimatePresence } from 'framer-motion';
import { useTranslation } from 'react-i18next';
import { PiShoppingCartSimpleLight } from 'react-icons/pi';
import { FiShoppingBag, FiX } from 'react-icons/fi';
import type { RootState } from '../../store/store';
import { closeEmptyCartModal } from '../../store/slices/emptyCartModalSlice';

const EmptyCartModal: React.FC = () => {
  const { t } = useTranslation(['cart', 'common']);
  const dispatch = useDispatch();
  const navigate = useNavigate();
  const isOpen = useSelector((state: RootState) => state.emptyCartModal?.isOpen);

  const handleClose = () => {
    dispatch(closeEmptyCartModal());
  };

  const handleBrowseProducts = () => {
    dispatch(closeEmptyCartModal());
    navigate('/all-products');
  };

  // Lock body scroll when modal is open
  useEffect(() => {
    if (isOpen) {
      document.body.style.overflow = 'hidden';
    } else {
      document.body.style.overflow = '';
    }
    return () => {
      document.body.style.overflow = '';
    };
  }, [isOpen]);

  // Handle escape key
  useEffect(() => {
    const handleKeyDown = (e: KeyboardEvent) => {
      if (e.key === 'Escape' && isOpen) {
        handleClose();
      }
    };
    window.addEventListener('keydown', handleKeyDown);
    return () => window.removeEventListener('keydown', handleKeyDown);
  }, [isOpen]);

  return (
    <AnimatePresence>
      {isOpen && (
        <div className="fixed inset-0 z-[1000] flex items-center justify-center p-4">
          {/* Backdrop */}
          <motion.div
            key="empty-cart-backdrop"
            initial={{ opacity: 0 }}
            animate={{ opacity: 1 }}
            exit={{ opacity: 0 }}
            transition={{ duration: 0.25 }}
            className="fixed inset-0 bg-black/60 backdrop-blur-md"
            onClick={handleClose}
          />

          {/* Modal Content */}
          <motion.div
            key="empty-cart-modal"
            initial={{ scale: 0.92, opacity: 0, y: 16 }}
            animate={{ scale: 1, opacity: 1, y: 0 }}
            exit={{ scale: 0.92, opacity: 0, y: 16 }}
            transition={{ type: 'spring', duration: 0.35, bounce: 0.2 }}
            className="relative w-full max-w-md bg-white dark:bg-[#131B2A] border border-gray-100 dark:border-slate-700/60 rounded-3xl shadow-[0_25px_60px_-15px_rgba(0,0,0,0.3)] dark:shadow-[0_25px_60px_-15px_rgba(0,0,0,0.7)] p-6 sm:p-8 overflow-hidden z-10"
            onClick={(e) => e.stopPropagation()}
          >
            {/* Ambient Background Glow Effect */}
            <div className="absolute -top-24 -left-24 w-48 h-48 bg-gradient-to-br from-[#B12C2E]/20 to-red-500/5 rounded-full blur-3xl pointer-events-none" />
            <div className="absolute -bottom-24 -right-24 w-48 h-48 bg-gradient-to-tl from-[#B12C2E]/15 to-transparent rounded-full blur-3xl pointer-events-none" />

            {/* Close Button */}
            <button
              onClick={handleClose}
              className="absolute top-4 right-4 rtl:right-auto rtl:left-4 p-2.5 text-gray-400 dark:text-slate-400 hover:text-gray-700 dark:hover:text-slate-200 hover:bg-gray-100 dark:hover:bg-slate-800/80 rounded-full transition-all duration-200 cursor-pointer z-20"
              aria-label={t('emptyCartModal.close', 'Close')}
            >
              <FiX className="w-5 h-5" />
            </button>

            <div className="flex flex-col items-center text-center relative z-10">
              {/* Premium Icon Badge */}
              <div className="relative mb-5">
                <div className="w-20 h-20 rounded-2xl bg-gradient-to-br from-red-50 to-red-100/60 dark:from-red-950/40 dark:to-[#1C2638] border border-red-200/60 dark:border-red-900/40 flex items-center justify-center shadow-inner">
                  <PiShoppingCartSimpleLight className="w-10 h-10 text-[#B12C2E] dark:text-[#E03E41] drop-shadow-sm" />
                </div>
              </div>

              {/* Title */}
              <h3 className="text-xl sm:text-2xl font-black text-gray-900 dark:text-slate-100 mb-2.5 tracking-tight">
                {t('emptyCartModal.title', 'عربة التسوق فارغة')}
              </h3>

              {/* Description */}
              <p className="text-gray-600 dark:text-slate-400 text-sm leading-relaxed mb-6 max-w-sm">
                {t(
                  'emptyCartModal.description',
                  'لم تقم بإضافة أي منتجات إلى سلة التسوق حتى الآن. يُرجى إضافة منتجات للبدء بالتسوق والوصول إلى صفحة الدفع.'
                )}
              </p>

              {/* Action Buttons */}
              <div className="w-full flex flex-col sm:flex-row gap-3">
                <button
                  onClick={handleBrowseProducts}
                  className="flex-1 flex items-center justify-center gap-2 py-3 px-5 bg-[#B12C2E] hover:bg-[#972426] dark:bg-[#B12C2E] dark:hover:bg-[#C83437] text-white font-bold rounded-xl transition-all duration-200 shadow-md hover:shadow-lg shadow-red-500/20 active:scale-[0.98] cursor-pointer text-sm"
                >
                  <FiShoppingBag className="w-4 h-4" />
                  <span>{t('emptyCartModal.browseProducts', 'تصفح المنتجات الآن')}</span>
                </button>

                <button
                  onClick={handleClose}
                  className="py-3 px-5 bg-gray-100 hover:bg-gray-200 dark:bg-slate-800/80 dark:hover:bg-slate-700/80 text-gray-700 dark:text-slate-300 font-semibold rounded-xl transition-all duration-200 active:scale-[0.98] cursor-pointer text-sm"
                >
                  {t('emptyCartModal.close', 'إغلاق')}
                </button>
              </div>
            </div>
          </motion.div>
        </div>
      )}
    </AnimatePresence>
  );
};

export default EmptyCartModal;
