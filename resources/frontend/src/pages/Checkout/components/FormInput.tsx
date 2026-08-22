import React from 'react';
import Select, { type OnChangeValue } from 'react-select';
import 'react-phone-number-input/style.css';
import PhoneInput from 'react-phone-input-2';

export interface OptionType {
  label: string;
  value: string;
}

interface FormInputProps {
  label: string;
  value: string | OptionType | null | undefined;
  onChange?: (value: string | OptionType) => void;
  onCountryChange?: (countryCode: string) => void;
  placeholder?: string;
  type?: 'text' | 'email' | 'tel' | 'select' | 'phone';
  required?: boolean;
  maxLength?: number;
  disabled?: boolean;
  options?: readonly OptionType[];
  errors?: string[];
}

const FormInput: React.FC<FormInputProps> = ({
  label,
  value,
  onChange,
  onCountryChange,
  placeholder,
  type = 'text',
  required = false,
  maxLength,
  disabled = false,
  options = [],
  errors = [],
}) => {

  const handleChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    let newValue = e.target.value;

    if (label === 'Card Number') {
      newValue = newValue.replace(/\s/g, '').replace(/(.{4})/g, '$1 ').trim();
    }

    if (label === 'Expiry Date') {
      newValue = newValue.replace(/\D/g, '').replace(/(\d{2})(\d)/, '$1/$2');
    }

    if (label === 'CVV') {
      newValue = newValue.replace(/\D/g, '');
    }

    if (onChange) {
      onChange(newValue);
    }
  };

  if (type === 'select') {
    const handleSelectChange = (
      selectedOption: OnChangeValue<OptionType, false>
    ) => {
      if (onChange && selectedOption) {
        onChange(selectedOption);
      }
    };

    return (
      <div className="space-y-2">
        <label className="block text-sm font-semibold text-gray-700 dark:text-slate-300">
          {label}
          {required && <span className="text-[#B12C2E] dark:text-[#E03E41] ml-1">*</span>}
        </label>
        <Select<OptionType>
          options={options}
          value={value as OptionType}
          onChange={handleSelectChange}
          placeholder={placeholder}
          isDisabled={disabled}
          required={required}
          styles={{
            control: (base, state) => ({
              ...base,
              padding: '4px 2px',
              borderWidth: '1px',
              borderRadius: '0.75rem',
              backgroundColor: 'var(--tw-select-bg, transparent)',
              borderColor: state.isFocused ? '#B12C2E' : 'rgba(156, 163, 175, 0.4)',
              boxShadow: state.isFocused ? '0 0 0 2px rgba(177, 44, 46, 0.2)' : 'none',
              '&:hover': {
                borderColor: state.isFocused ? '#B12C2E' : 'rgba(156, 163, 175, 0.6)',
              },
            }),
            menu: (base) => ({
              ...base,
              backgroundColor: '#151D2C',
              color: '#F8FAFC',
              borderRadius: '0.75rem',
              overflow: 'hidden',
              zIndex: 50,
            }),
            option: (base, state) => ({
              ...base,
              backgroundColor: state.isSelected ? '#B12C2E' : state.isFocused ? 'rgba(255,255,255,0.08)' : 'transparent',
              color: '#F8FAFC',
              cursor: 'pointer',
            }),
            singleValue: (base) => ({
              ...base,
              color: 'inherit',
            }),
          }}
          className="text-gray-900 dark:text-slate-100"
        />
        {errors && errors.length > 0 && (
          <p className="text-red-500 text-xs mt-1">
            {errors[0]}
          </p>
        )}
      </div>
    );
  }

  if (type === 'phone') {
    return (
      <div className="space-y-2 phone-input-container">
        <label className="block text-sm font-semibold text-gray-700 dark:text-slate-300">
          {label}
          {required && <span className="text-[#B12C2E] dark:text-[#E03E41] ml-1">*</span>}
        </label>
        <PhoneInput
          placeholder={placeholder}
          country={'eg'}
          value={value as string}
          onChange={(phoneValue, data: { dialCode: string }) => {
            if (onChange) onChange(phoneValue || '');
            if (onCountryChange) onCountryChange(data.dialCode);
          }}
          disabled={disabled}
          inputClass="custom-phone-input custom-phone-input-checkout"
          buttonClass="custom-phone-input-button custom-phone-input-button-checkout"
        />
        {errors && errors.length > 0 && (
          <p className="text-red-500 text-xs mt-1">
            {errors[0]}
          </p>
        )}
      </div>
    );
  }

  return (
    <div className="space-y-2">
      <label className="block text-sm font-semibold text-gray-700 dark:text-slate-300">
        {label}
        {required && <span className="text-[#B12C2E] dark:text-[#E03E41] ml-1">*</span>}
      </label>

      <input
        type={type}
        value={value as string}
        onChange={handleChange}
        placeholder={placeholder}
        maxLength={maxLength}
        disabled={disabled}
        required={required}
        className={`
          w-full px-4 py-3 border border-gray-300 dark:border-slate-700 rounded-xl
          focus:border-[#B12C2E] focus:ring-2 focus:ring-[#B12C2E]/20
          placeholder-gray-400 dark:placeholder-slate-500 text-gray-900 dark:text-slate-100 text-sm
          outline-none
          transition-all duration-200
          ${disabled ? 'bg-gray-100 dark:bg-slate-800 cursor-not-allowed opacity-60' : 'bg-white dark:bg-[#1C2638]'}
        `}
      />
      {errors && errors.length > 0 && (
        <p className="text-red-500 text-xs mt-1">
          {errors[0]}
        </p>
      )}
    </div>
  );
};

export default FormInput;
