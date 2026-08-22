import React, { useState, type ReactNode } from 'react';
import i18n from '../../i18n';

interface PasswordInputProps {
  id: string;
  label: string;
  value: string;
  onChange: (e: React.ChangeEvent<HTMLInputElement>) => void;
  placeholder?: string;
  required?: boolean;
  className?: string;
  icon?: ReactNode;
}

const PasswordInput: React.FC<PasswordInputProps> = ({
  id,
  label,
  value,
  onChange,
  placeholder = 'password',
  required = false,
  className = '',
  icon,
}) => {
  const [showPassword, setShowPassword] = useState<boolean>(false);
  const isRTL = i18n.language === 'ar';

  return (
    <div className="mb-1">
      <label htmlFor={id} className="block text-sm font-semibold text-gray-700 dark:text-slate-300 mb-1.5">
        {label}
      </label>
      <div className="relative">
        {icon && (
          <div className={`absolute inset-y-0 ${icon && !isRTL ? 'left-3' : 'right-3'} flex items-center pointer-events-none text-gray-400 dark:text-slate-500`}>
            {icon}
          </div>
        )}
        <input
          id={id}
          type={showPassword ? "text" : "password"}
          value={value}
          onChange={onChange}
          className={`w-full ${icon && !isRTL ? 'pl-9 pr-10' : icon && isRTL ? 'pr-9 pl-10' : 'px-4'} py-2.5 rounded-xl border border-gray-300 dark:border-slate-700 bg-white dark:bg-[#1C2638] text-gray-900 dark:text-slate-100 placeholder-gray-400 dark:placeholder-slate-500 focus:outline-none focus:border-[#B12C2E] focus:ring-2 focus:ring-[#B12C2E]/20 text-sm transition-all shadow-sm ${className}`}
          placeholder={placeholder}
          required={required}
        />
        <button
          type="button"
          className={`absolute inset-y-0 ${!isRTL ? 'right-3 pl-2' : 'left-3 pr-2'} flex items-center text-gray-400 dark:text-slate-500 hover:text-gray-600 dark:hover:text-slate-300 transition-colors`}
          onClick={() => setShowPassword(!showPassword)}
        >
          {showPassword ? (
            <svg xmlns="http://www.w3.org/2000/svg" className="h-5 w-5 cursor-pointer" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
            </svg>
          ) : (
            <svg xmlns="http://www.w3.org/2000/svg" className="h-5 w-5 cursor-pointer" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
            </svg>
          )}
        </button>
      </div>
    </div>
  );
};

export default PasswordInput;
