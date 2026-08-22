import React from 'react';
import { useTranslation } from 'react-i18next';
import type { SmartBundleCTA } from '../../types';

export interface CartBundleCTAProps {
  cta: SmartBundleCTA;
}

export const CartBundleCTA: React.FC<CartBundleCTAProps> = ({ cta }) => {
  const { t, i18n } = useTranslation(['products']);
  const isWarning = cta.type === 'warning';

  const bundleName = typeof cta.bundle_name === 'string' 
    ? cta.bundle_name 
    : (cta.bundle_name[i18n.language] || cta.bundle_name['ar'] || cta.bundle_name['en']);

  return (
    <div className={`mt-2 p-3 rounded-xl flex items-center gap-3 text-sm font-semibold transition-all duration-300 ${
      isWarning 
        ? 'bg-gradient-to-r from-orange-50 to-orange-100 text-orange-800 border border-orange-200' 
        : 'bg-gradient-to-r from-green-50 to-green-100 text-green-800 border border-green-200 shadow-sm'
    }`}>
      <div className="flex-shrink-0 text-xl">
        {isWarning ? '💡' : '🎉'}
      </div>
      <div className="flex flex-col">
        <span className="text-xs uppercase tracking-wider opacity-70 mb-0.5">
          {bundleName}
        </span>
        <span>
          {isWarning ? (
            t('products:bundles.cart_warning', { 
              count: cta.missing_quantity, 
              discount: cta.discount_percentage 
            })
          ) : (
            t('products:bundles.cart_success', { 
              discount: cta.discount_percentage 
            })
          )}
        </span>
      </div>
    </div>
  );
};

