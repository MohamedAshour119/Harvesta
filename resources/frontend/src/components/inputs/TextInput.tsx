import React, { type ReactNode } from 'react';
import i18n from '../../i18n';

interface TextInputProps {
  id: string;
  label?: string;
  name?: string;
  value: string;
  onChange: (e: React.ChangeEvent<HTMLInputElement>) => void;
  placeholder?: string;
  type?: string;
  required?: boolean;
  className?: string;
  icon?: ReactNode;
  error?: string;
}

const TextInput: React.FC<TextInputProps> = ({
  id,
  label,
  name,
  value,
  onChange,
  placeholder = '',
  type = 'text',
  required = false,
  className = '',
  icon,
  error,
}) => {

  const isRTL = i18n.language === 'ar';

  return (
    <div className="mb-1">
      {label && (
        <label htmlFor={id} className="block text-sm font-semibold text-gray-700 dark:text-slate-300 mb-1.5">
          {label}
        </label>
      )}
      <div className="relative">
        {icon && name !== 'deadline' && value.length === 0 && (
          <div className={`absolute inset-y-0 ${icon && !isRTL ? 'left-3' : 'right-3'} flex items-center pointer-events-none text-gray-400 dark:text-slate-500`}>
            {icon}
          </div>
        )}

        {icon && name === 'deadline' && value.length === 0 && (
          <div className={`absolute inset-y-0 left-3 flex items-center pointer-events-none text-gray-400 dark:text-slate-500`}>
            {icon}
          </div>
        )}
        <input
          id={id}
          name={name}
          type={type}
          value={value}
          onChange={onChange}
          className={`w-full ${
            icon && value.length === 0 && name === 'deadline' && isRTL ? 'pl-8 pr-3' :
            icon && value.length === 0 && !isRTL ? 'pl-8 pr-3' : icon && isRTL && value.length === 0 ? 'pr-8 pl-3' : ''
          } py-2.5 px-3 rounded-xl border ${
            error
              ? 'border-red-400 dark:border-red-500 focus:border-red-500 focus:ring-2 focus:ring-red-500/20'
              : 'border-gray-300 dark:border-slate-700 focus:border-[#B12C2E] focus:ring-2 focus:ring-[#B12C2E]/20'
          } bg-white dark:bg-[#1C2638] text-gray-900 dark:text-slate-100 placeholder:text-gray-400 dark:placeholder:text-slate-500 focus:outline-none transition-all text-sm ${className}`}
          placeholder={placeholder}
          required={required}
        />
      </div>
      {error && (
        <p className="mt-1 text-xs text-red-500 dark:text-red-400 font-medium leading-tight">{error}</p>
      )}
    </div>
  );
};

export default TextInput;
