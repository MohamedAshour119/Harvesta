import React, { useEffect } from 'react';
import { motion, AnimatePresence } from 'framer-motion';
import { useTranslation } from 'react-i18next';

interface PopupProps {
  isOpen: boolean;
  onClose: () => void;
  title: string;
  message: string;
  type?: 'success' | 'error' | 'info';
  autoCloseTime?: number; // in milliseconds
  primaryButtonText?: string;
}

const Popup: React.FC<PopupProps> = ({
  isOpen,
  onClose,
  title,
  message,
  type = 'success',
  autoCloseTime,
  primaryButtonText = 'Continue',
}) => {
  const { t } = useTranslation(['common']);

  useEffect(() => {
    if (isOpen && autoCloseTime) {
      const timer = setTimeout(() => {
        onClose();
      }, autoCloseTime);
      
      return () => clearTimeout(timer);
    }
  }, [isOpen, autoCloseTime, onClose]);

  const handleBackdropClick = (e: React.MouseEvent<HTMLDivElement>) => {
    if (e.target === e.currentTarget) {
      onClose();
    }
  };

  const backdropVariants = {
    hidden: { opacity: 0 },
    visible: { opacity: 1 }
  };

  const popupVariants = {
    hidden: { 
      y: 20,
      opacity: 0,
      scale: 0.95
    },
    visible: { 
      y: 0,
      opacity: 1,
      scale: 1,
      transition: {
        type: "spring",
        stiffness: 300,
        damping: 25
      }
    },
    exit: { 
      y: 20,
      opacity: 0,
      scale: 0.95,
      transition: {
        duration: 0.2
      }
    }
  };

  const iconAndColors = {
    success: {
      icon: (
        <svg xmlns="http://www.w3.org/2000/svg" className="h-14 w-14 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M5 13l4 4L19 7" />
        </svg>
      ),
      bg: 'bg-white dark:bg-[#151D2C]',
      border: 'border-emerald-100 dark:border-emerald-900/40',
    },
    error: {
      icon: (
        <svg xmlns="http://www.w3.org/2000/svg" className="h-14 w-14 text-[#B12C2E] dark:text-[#E03E41]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
        </svg>
      ),
      bg: 'bg-white dark:bg-[#151D2C]',
      border: 'border-red-100 dark:border-red-900/40',
    },
    info: {
      icon: (
        <svg xmlns="http://www.w3.org/2000/svg" className="h-14 w-14 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
      ),
      bg: 'bg-white dark:bg-[#151D2C]',
      border: 'border-blue-100 dark:border-blue-900/40',
    },
  };

  const { icon, bg, border } = iconAndColors[type];

  return (
    <AnimatePresence>
      {isOpen && (
        <motion.div 
          className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm"
          onClick={handleBackdropClick}
          variants={backdropVariants}
          initial="hidden"
          animate="visible"
          exit="hidden"
        >
          <motion.div 
            className={`${bg} ${border} border rounded-2xl shadow-2xl p-6 max-w-md w-full transition-colors`}
            onClick={(e) => e.stopPropagation()}
            variants={popupVariants}
            initial="hidden"
            animate="visible"
            exit="exit"
          >
            <div className="flex flex-col items-center text-center">
              <div className="mb-4">{icon}</div>
              <h3 className="text-xl font-bold text-gray-900 dark:text-slate-100 mb-2">{title}</h3>
              <p className="text-gray-600 dark:text-slate-400 mb-6 text-sm leading-relaxed">{message}</p>
              <div className="flex space-x-3 rtl:space-x-reverse">
                <button
                  onClick={() => onClose()}
                  className="bg-[#B12C2E] hover:bg-[#972426] dark:bg-[#B12C2E] dark:hover:bg-[#8F2325] cursor-pointer text-white font-bold px-6 py-2.5 rounded-xl transition-all shadow-sm"
                >
                  {primaryButtonText}
                </button>
                <button
                  onClick={() => onClose()}
                  className="border border-gray-200 dark:border-slate-700 bg-white dark:bg-[#1C2638] text-gray-700 dark:text-slate-300 font-semibold px-6 py-2.5 rounded-xl hover:bg-gray-50 dark:hover:bg-slate-800 transition-colors cursor-pointer"
                >
                  {t('common:buttons.close')}
                </button>
              </div>
            </div>
          </motion.div>
        </motion.div>
      )}
    </AnimatePresence>
  );
};

export default Popup;
