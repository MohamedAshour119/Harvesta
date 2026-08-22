import React from 'react';
import FormInput from './FormInput';
import { useTranslation } from 'react-i18next';
import type { BillingInfo } from '../../../types';
import "react-phone-number-input/style.css";
import type { SelectOption } from '../../../ApiClient';

interface BillingDetailsProps {
  billingInfo: BillingInfo;
  onBillingInfoChange: (field: keyof BillingInfo, value: string) => void;
  errors: Record<string, string[]>;
  cityOptions: SelectOption[];
}

const BillingDetails: React.FC<BillingDetailsProps> = ({
  billingInfo,
  onBillingInfoChange,
  errors,
  cityOptions
}) => {
  const { t } = useTranslation('checkout');

  return (
    <div className="bg-white dark:bg-[#151D2C] border border-gray-100 dark:border-slate-800 rounded-3xl p-6 sm:p-8 shadow-sm transition-colors">
      <h2 className="text-xl font-bold text-gray-900 dark:text-slate-100 mb-6">{t('billingDetails.title')}</h2>
      <div className="space-y-6">
        {/* Personal Information */}
        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
          <FormInput
            label={t('billingDetails.firstName')}
            value={billingInfo.firstName}
            onChange={(value) => onBillingInfoChange('firstName', value as string)}
            placeholder={t('billingDetails.firstName')}
            required
            errors={errors['firstName']}
          />
          <FormInput
            label={t('billingDetails.lastName')}
            value={billingInfo.lastName}
            onChange={(value) => onBillingInfoChange('lastName', value as string)}
            placeholder={t('billingDetails.lastName')}
            required
            errors={errors['lastName']}
          />
        </div>

        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
          <FormInput
            label={t('billingDetails.email')}
            value={billingInfo.email}
            onChange={(value) => onBillingInfoChange('email', value as string)}
            placeholder={t('billingDetails.email')}
            type='email'
            errors={errors['email']}
          />
          <FormInput
            label={t('billingDetails.companyName')}
            value={billingInfo.companyName}
            onChange={(value) => onBillingInfoChange('companyName', value as string)}
            placeholder={t('billingDetails.companyName')}
            errors={errors['companyName']}
          />
        </div>

        <FormInput
          label={t('billingDetails.streetAddress')}
          value={billingInfo.streetAddress}
          onChange={(value) => onBillingInfoChange('streetAddress', value as string)}
          placeholder={t('billingDetails.streetAddress')}
          required
          errors={errors['streetAddress']}
        />

        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
          <FormInput
            label={t('billingDetails.province')}
            value={cityOptions.find(c => c.value === billingInfo.city) || null}
            onChange={(value) => onBillingInfoChange('city', (value as { value: string }).value)}
            placeholder={t('billingDetails.province')}
            required
            type="select"
            options={cityOptions}
            errors={errors['city']}
          />
          <FormInput
            label={t('billingDetails.city')}
            value={billingInfo.province}
            onChange={(value) => onBillingInfoChange('province', value as string)}
            placeholder={t('billingDetails.city')}
            required
            errors={errors['province']}
          />
        </div>
        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
          <FormInput
            label={t('billingDetails.phone')}
            value={billingInfo.phone}
            type='phone'
            onChange={(value) => onBillingInfoChange('phone', value as string)}
            onCountryChange={(countryCode) => onBillingInfoChange('phoneCountryCode', countryCode)}
            placeholder={t('billingDetails.phone')}
            required
            errors={errors['phone']}
          />
          <FormInput
            label={t('billingDetails.postalCode')}
            value={billingInfo.postalCode}
            onChange={(value) => onBillingInfoChange('postalCode', value as string)}
            placeholder={t('billingDetails.postalCode')}
            errors={errors['postalCode']}
          />
        </div>
      </div>
    </div>
  );
};

export default BillingDetails;
