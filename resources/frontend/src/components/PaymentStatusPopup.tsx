import React, { useEffect, useState } from 'react';
import { useTranslation } from 'react-i18next';
import { HiOutlineCheckCircle, HiOutlineXCircle, HiOutlineXMark } from 'react-icons/hi2';

interface PaymentStatusPopupProps {
  status: 'success' | 'cancel' | null;
  onClose: () => void;
}

const PaymentStatusPopup: React.FC<PaymentStatusPopupProps> = ({ status, onClose }) => {
  const { t } = useTranslation('common');
  const [isVisible, setIsVisible] = useState(false);
  const [isAnimating, setIsAnimating] = useState(false);

  useEffect(() => {
    if (status) {
      setIsVisible(true);
      setTimeout(() => setIsAnimating(true), 50);
    }
  }, [status]);

  const handleClose = () => {
    setIsAnimating(false);
    setTimeout(() => {
      setIsVisible(false);
      onClose();
    }, 300);
  };

  const handleBackdropClick = (e: React.MouseEvent) => {
    if (e.target === e.currentTarget) {
      handleClose();
    }
  };

  if (!isVisible || !status) return null;

  const isSuccess = status === 'success';
  const config = isSuccess
    ? {
      icon: HiOutlineCheckCircle,
      iconColor: 'text-emerald-500',
      bgColor: 'bg-white dark:bg-[#151D2C]',
      borderColor: 'border-emerald-100 dark:border-emerald-900/40',
      buttonBg: 'bg-[#B12C2E] hover:bg-[#972426] dark:bg-[#B12C2E] dark:hover:bg-[#8F2325]',
      title: t('paymentStatus.success.title'),
      message: t('paymentStatus.success.message'),
      buttonText: t('paymentStatus.success.buttonText')
    }
    : {
      icon: HiOutlineXCircle,
      iconColor: 'text-[#B12C2E] dark:text-[#E03E41]',
      bgColor: 'bg-white dark:bg-[#151D2C]',
      borderColor: 'border-red-100 dark:border-red-900/40',
      buttonBg: 'bg-[#B12C2E] hover:bg-[#972426] dark:bg-[#B12C2E] dark:hover:bg-[#8F2325]',
      title: t('paymentStatus.cancel.title'),
      message: t('paymentStatus.cancel.message'),
      buttonText: t('paymentStatus.cancel.buttonText')
    };

  const IconComponent = config.icon;

  return (
    <div
      className={`fixed inset-0 z-[1000] flex items-center justify-center p-4 bg-black transition-opacity duration-300 ${isAnimating ? 'bg-black/60 backdrop-blur-sm' : 'bg-transparent'
        }`}
      onClick={handleBackdropClick}
    >
      <div
        className={`relative w-full max-w-md mx-auto ${config.bgColor} border ${config.borderColor} rounded-2xl shadow-2xl transform transition-all duration-300 ${isAnimating ? 'scale-100 opacity-100 translate-y-0' : 'scale-95 opacity-0 translate-y-4'
          }`}
      >
        {/* Close button */}
        <button
          onClick={handleClose}
          className="absolute top-4 right-4 rtl:right-auto rtl:left-4 p-2 cursor-pointer text-gray-400 dark:text-slate-500 hover:text-gray-600 dark:hover:text-slate-300 transition-colors duration-200 rounded-full hover:bg-gray-100 dark:hover:bg-slate-800"
        >
          <HiOutlineXMark className="w-5 h-5" />
        </button>

        {/* Content */}
        <div className="p-8 text-center">
          {/* Icon */}
          <div className="flex justify-center mb-4">
            <div className={`p-3 rounded-full ${config.bgColor} ${config.borderColor} border`}>
              <IconComponent className={`w-12 h-12 ${config.iconColor}`} />
            </div>
          </div>

          {/* Title */}
          <h3 className="text-2xl font-bold text-gray-900 dark:text-slate-100 mb-3">
            {config.title}
          </h3>

          {/* Message */}
          <p className="text-gray-600 dark:text-slate-400 mb-6 leading-relaxed text-sm">
            {config.message}
          </p>

          {/* Action button */}
          <button
            onClick={handleClose}
            className={`w-full ${config.buttonBg} cursor-pointer text-white font-bold py-3 px-6 rounded-xl transition-all duration-200 transform hover:scale-[1.02] shadow-sm`}
          >
            {config.buttonText}
          </button>
        </div>
      </div>
    </div>
  );
};

export default PaymentStatusPopup;
