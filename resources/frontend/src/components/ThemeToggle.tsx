import React, { useId } from 'react';
import { useTheme } from '../context/ThemeContext';
import { useTranslation } from 'react-i18next';
import { motion, AnimatePresence } from 'framer-motion';

interface ThemeToggleProps {
  className?: string;
  showLabel?: boolean;
}

const ThemeToggle: React.FC<ThemeToggleProps> = ({ className = '', showLabel = false }) => {
  const { isDark, toggleTheme } = useTheme();
  const { t } = useTranslation('common');
  const uniqueId = useId().replace(/:/g, '');

  const sunGradientId = `sun-grad-${uniqueId}`;
  const moonGradientId = `moon-grad-${uniqueId}`;

  // 8 Rays at 45° intervals
  const rays = [0, 45, 90, 135, 180, 225, 270, 315];

  return (
    <motion.button
      type="button"
      onClick={toggleTheme}
      whileHover={{ scale: 1.08 }}
      whileTap={{ scale: 0.92 }}
      className={`relative inline-flex items-center justify-center p-1.5 rounded-full bg-transparent border-0 shadow-none cursor-pointer focus:outline-none focus-visible:ring-2 focus-visible:ring-[#B12C2E] transition-colors duration-200 hover:text-[#B12C2E] dark:hover:text-[#E03E41] ${
        isDark ? 'text-amber-400' : 'text-slate-700 dark:text-slate-200'
      } ${className}`}
      aria-label={isDark ? t('theme.switchToLight', 'Switch to Light Mode') : t('theme.switchToDark', 'Switch to Dark Mode')}
      title={isDark ? t('theme.switchToLight', 'Switch to Light Mode') : t('theme.switchToDark', 'Switch to Dark Mode')}
    >
      {/* Animated SVG Container - 28x28 size */}
      <div className="relative w-7 h-7 flex items-center justify-center pointer-events-none">
        <svg
          xmlns="http://www.w3.org/2000/svg"
          viewBox="0 0 24 24"
          className="w-7 h-7 overflow-visible"
        >
          <defs>
            {/* Sun Core Gradient */}
            <radialGradient id={sunGradientId} cx="50%" cy="50%" r="50%">
              <stop offset="0%" stopColor="#FEF3C7" />
              <stop offset="45%" stopColor="#FBBF24" />
              <stop offset="100%" stopColor="#F59E0B" />
            </radialGradient>

            {/* Moon Solid Gradient - High contrast for light backgrounds */}
            <linearGradient id={moonGradientId} x1="0%" y1="0%" x2="100%" y2="100%">
              <stop offset="0%" stopColor="#475569" />
              <stop offset="50%" stopColor="#334155" />
              <stop offset="100%" stopColor="#1E293B" />
            </linearGradient>
          </defs>

          {/* ========================================================================= */}
          {/* SUN STATE (Dark Mode Active -> Showing Animated Sun to Switch to Light) */}
          {/* ========================================================================= */}
          <AnimatePresence mode="wait">
            {isDark ? (
              <motion.g
                key="sun-group"
                initial={{ opacity: 0, scale: 0.5, rotate: -60 }}
                animate={{ opacity: 1, scale: 1, rotate: 0 }}
                exit={{ opacity: 0, scale: 0.5, rotate: 60 }}
                transition={{ duration: 0.3, ease: 'easeOut' }}
                className="origin-center"
              >
                {/* 1. Continuous Solar Corona Flare */}
                <motion.circle
                  cx="12"
                  cy="12"
                  r="6.5"
                  fill="none"
                  stroke="#FDE68A"
                  strokeWidth="1"
                  animate={{
                    scale: [0.95, 1.2, 0.95],
                    opacity: [0.6, 0, 0.6],
                  }}
                  transition={{
                    duration: 2.4,
                    repeat: Infinity,
                    ease: 'easeInOut',
                  }}
                  className="origin-center"
                />

                {/* 2. Continuously Rotating Sun Rays */}
                <motion.g
                  animate={{
                    rotate: [0, 360],
                  }}
                  transition={{
                    duration: 14,
                    repeat: Infinity,
                    ease: 'linear',
                  }}
                  className="origin-center"
                >
                  {rays.map((angle, idx) => {
                    const rad = (angle * Math.PI) / 180;
                    const isAlt = idx % 2 === 1;
                    const innerR = 7;
                    const outerR = isAlt ? 10 : 11;

                    const x1 = 12 + innerR * Math.cos(rad);
                    const y1 = 12 + innerR * Math.sin(rad);
                    const x2 = 12 + outerR * Math.cos(rad);
                    const y2 = 12 + outerR * Math.sin(rad);

                    return (
                      <motion.line
                        key={`ray-${angle}`}
                        x1={x1}
                        y1={y1}
                        x2={x2}
                        y2={y2}
                        stroke="#FBBF24"
                        strokeWidth={isAlt ? '1.8' : '2.2'}
                        strokeLinecap="round"
                        animate={{
                          opacity: [0.8, 1, 0.8],
                        }}
                        transition={{
                          duration: 1.8,
                          repeat: Infinity,
                          ease: 'easeInOut',
                          delay: idx * 0.12,
                        }}
                      />
                    );
                  })}
                </motion.g>

                {/* 3. Pulsing Central Sun Core */}
                <motion.circle
                  cx="12"
                  cy="12"
                  r="5"
                  fill={`url(#${sunGradientId})`}
                  animate={{
                    scale: [1, 1.06, 1],
                  }}
                  transition={{
                    duration: 2,
                    repeat: Infinity,
                    ease: 'easeInOut',
                  }}
                  className="origin-center"
                />
              </motion.g>
            ) : (
              /* =========================================================================== */
              /* MOON STATE (Light Mode Active -> Showing Animated Moon to Switch to Dark) */
              /* =========================================================================== */
              <motion.g
                key="moon-group"
                initial={{ opacity: 0, scale: 0.5, rotate: 60 }}
                animate={{ opacity: 1, scale: 1, rotate: 0 }}
                exit={{ opacity: 0, scale: 0.5, rotate: -60 }}
                transition={{ duration: 0.3, ease: 'easeOut' }}
                className="origin-center"
              >
                {/* 1. Floating Crescent Moon Path (Solid & Crystal Clear) */}
                <motion.g
                  animate={{
                    y: [-0.75, 0.75, -0.75],
                    rotate: [-2, 2, -2],
                  }}
                  transition={{
                    duration: 3,
                    repeat: Infinity,
                    ease: 'easeInOut',
                  }}
                  className="origin-center"
                >
                  <path
                    d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"
                    fill={`url(#${moonGradientId})`}
                    stroke="#1E293B"
                    strokeWidth="0.8"
                    strokeLinejoin="round"
                    className="drop-shadow-[0_1px_2px_rgba(0,0,0,0.15)]"
                  />
                </motion.g>

                {/* 2. Twinkling Star #1 (Top Right) */}
                <motion.g
                  animate={{
                    scale: [0.7, 1.2, 0.7],
                    opacity: [0.4, 1, 0.4],
                  }}
                  transition={{
                    duration: 2,
                    repeat: Infinity,
                    ease: 'easeInOut',
                  }}
                  style={{ originX: '18px', originY: '5px' }}
                >
                  <path
                    d="M 18 2.5 L 18.7 4.3 L 20.5 5 L 18.7 5.7 L 18 7.5 L 17.3 5.7 L 15.5 5 L 17.3 4.3 Z"
                    fill="#3B82F6"
                  />
                </motion.g>

                {/* 3. Twinkling Star #2 (Bottom Right) */}
                <motion.g
                  animate={{
                    scale: [0.6, 1.15, 0.6],
                    opacity: [0.3, 0.9, 0.3],
                  }}
                  transition={{
                    duration: 2.5,
                    repeat: Infinity,
                    ease: 'easeInOut',
                    delay: 0.5,
                  }}
                  style={{ originX: '17px', originY: '18px' }}
                >
                  <path
                    d="M 17 16 L 17.5 17.2 L 18.7 17.7 L 17.5 18.2 L 17 19.5 L 16.5 18.2 L 15.3 17.7 L 16.5 17.2 Z"
                    fill="#6366F1"
                  />
                </motion.g>
              </motion.g>
            )}
          </AnimatePresence>
        </svg>
      </div>

      {showLabel && (
        <span className="ms-2 text-sm font-semibold transition-colors">
          {isDark ? t('theme.lightMode', 'Light Mode') : t('theme.darkMode', 'Dark Mode')}
        </span>
      )}
    </motion.button>
  );
};

export default ThemeToggle;
