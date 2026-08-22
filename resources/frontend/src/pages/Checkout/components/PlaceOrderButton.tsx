import React from 'react';
import { useTranslation } from 'react-i18next';
import { GoArrowLeft, GoArrowRight } from 'react-icons/go';
import i18n from '../../../i18n';

interface PlaceOrderButtonProps {
  onPlaceOrder: () => void;
  isProcessing: boolean;
  disabled?: boolean;
}

const PlaceOrderButton: React.FC<PlaceOrderButtonProps> = ({
  onPlaceOrder,
  isProcessing,
  disabled = false
}) => {
  const { t } = useTranslation('checkout');
  const isRTL = i18n.language === 'ar';

  return (
    <button
      onClick={onPlaceOrder}
      disabled={disabled || isProcessing}
      className={`
        w-full py-4 px-6 rounded-xl font-bold text-white text-base
        transition-all duration-200 cursor-pointer shadow-md
        focus:outline-none focus:ring-4 focus:ring-[#B12C2E]/20
        ${disabled || isProcessing
          ? 'bg-gray-300 dark:bg-slate-800 text-gray-500 cursor-not-allowed shadow-none'
          : 'bg-[#B12C2E] hover:bg-[#972426] dark:bg-[#B12C2E] dark:hover:bg-[#C83437] active:scale-[0.98]'
        }
      `}
    >
      {isProcessing ? (
        <div className="flex items-center justify-center space-x-3">
          <div className="w-5 h-5 border-2 border-white border-t-transparent rounded-full animate-spin"></div>
          <span>{t('placeOrder.processing')}</span>
        </div>
      ) : (
        <div className="flex items-center justify-center space-x-2">
          <span>{t('placeOrder.buttonText')}</span>
          {isRTL ? (
            <GoArrowLeft className="mt-0.5 text-lg"/>
          ) : (
            <GoArrowRight className="mt-0.5 text-lg"/>
          )}
        </div>
      )}
    </button>
  );
};

export default PlaceOrderButton;
