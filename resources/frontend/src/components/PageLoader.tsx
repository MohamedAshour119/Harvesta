import React from 'react';
import { useTranslation } from 'react-i18next';

interface PageLoaderProps {
  message?: string;
  subMessage?: string;
  fullScreen?: boolean;
}

export const PageLoader: React.FC<PageLoaderProps> = ({
  message,
  subMessage,
  fullScreen = true,
}) => {
  const { t } = useTranslation(['common']);

  const displayMessage = message || t('common:loading', 'جاري التحميل...');

  return (
    <div
      className={`flex flex-col items-center justify-center w-full transition-colors duration-300 ${
        fullScreen ? 'min-h-[70vh]' : 'py-16'
      }`}
    >
      {/* Pure Floating Logo & Sleek Spinner */}
      <div className="relative flex items-center justify-center mb-6">
        {/* Outer Orbit Spinning Ring */}
        <div className="w-20 h-20 sm:w-24 sm:h-24 rounded-full border-2 border-transparent border-t-[#B12C2E] border-r-[#B12C2E]/60 dark:border-t-[#E03E41] dark:border-r-[#E03E41]/60 animate-spin" />

        {/* Center Pure Flower & Crimson Shield Emblem (No Box / No Square Background / No Borders) */}
        <div className="absolute inset-0 m-auto w-10 h-10 sm:w-12 sm:h-12 flex items-center justify-center pointer-events-none">
          <svg
            viewBox="0 0 100 100"
            fill="none"
            xmlns="http://www.w3.org/2000/svg"
            className="w-full h-full object-contain animate-pulse drop-shadow-sm"
          >
            <defs>
              <linearGradient id="loader-gold" x1="0%" y1="0%" x2="100%" y2="100%">
                <stop offset="0%" stopColor="#FEF08A" />
                <stop offset="50%" stopColor="#F59E0B" />
                <stop offset="100%" stopColor="#D97706" />
              </linearGradient>
              <linearGradient id="loader-crimson" x1="0%" y1="0%" x2="100%" y2="100%">
                <stop offset="0%" stopColor="#FF5255" />
                <stop offset="50%" stopColor="#E03E41" />
                <stop offset="100%" stopColor="#B12C2E" />
              </linearGradient>
              <linearGradient id="loader-emerald" x1="0%" y1="0%" x2="100%" y2="100%">
                <stop offset="0%" stopColor="#4ADE80" />
                <stop offset="50%" stopColor="#10B981" />
              </linearGradient>
            </defs>

            {/* Crimson Shield Wrapper */}
            <path
              d="M50 10 C74 10, 90 26, 90 48 C90 73, 62 86, 50 90 C38 86, 10 73, 10 48 C10 26, 26 10, 50 10 Z"
              fill="none"
              stroke="url(#loader-crimson)"
              strokeWidth="5"
              strokeLinecap="round"
            />

            {/* Inner Flower & Leaves */}
            <path d="M50 22 Q65 36 50 52 Q35 36 50 22 Z" fill="url(#loader-gold)" />
            <path d="M50 46 Q67 58 54 75 Q41 60 50 46 Z" fill="url(#loader-crimson)" />
            <path d="M33 38 Q46 45 42 59 Q29 52 33 38 Z" fill="url(#loader-emerald)" />
            <path d="M67 38 Q54 45 58 59 Q71 52 67 38 Z" fill="url(#loader-emerald)" />
            <circle cx="50" cy="27" r="5" fill="#FFF" opacity="0.95" />
          </svg>
        </div>
      </div>

      {/* Elegant Loading Typography */}
      <div className="text-center space-y-1.5 z-10">
        <div className="flex items-center justify-center gap-2">
          <p className="text-sm sm:text-base font-bold bg-gradient-to-r from-gray-900 via-gray-700 to-gray-900 dark:from-slate-100 dark:via-slate-300 dark:to-slate-100 bg-clip-text text-transparent tracking-wide">
            {displayMessage}
          </p>
          <div className="flex space-x-1 rtl:space-x-reverse items-center pt-0.5">
            <div className="w-1.5 h-1.5 rounded-full bg-[#B12C2E] dark:bg-[#E03E41] animate-bounce [animation-delay:-0.3s]"></div>
            <div className="w-1.5 h-1.5 rounded-full bg-[#B12C2E] dark:bg-[#E03E41] animate-bounce [animation-delay:-0.15s]"></div>
            <div className="w-1.5 h-1.5 rounded-full bg-[#B12C2E] dark:bg-[#E03E41] animate-bounce"></div>
          </div>
        </div>
        {subMessage && (
          <p className="text-xs text-gray-400 dark:text-slate-500 font-medium">
            {subMessage}
          </p>
        )}
      </div>
    </div>
  );
};

export default PageLoader;
