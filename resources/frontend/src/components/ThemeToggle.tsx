import React from 'react';
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

  // 8 Rays at 45° intervals
  const rays = [0, 45, 90, 135, 180, 225, 270, 315];

  return (
    <motion.button
      type="button"
      onClick={toggleTheme}
      whileHover={{ scale: 1.12 }}
      whileTap={{ scale: 0.9 }}
      className={`relative inline-flex items-center justify-center p-1.5 rounded-full bg-transparent border-0 shadow-none cursor-pointer focus:outline-none focus-visible:ring-2 focus-visible:ring-[#B12C2E] transition-colors duration-200 hover:text-[#B12C2E] dark:hover:text-[#E03E41] ${
        isDark ? 'text-amber-400' : 'text-gray-700 dark:text-slate-200'
      } ${className}`}
      aria-label={isDark ? t('theme.switchToLight', 'Switch to Light Mode') : t('theme.switchToDark', 'Switch to Dark Mode')}
      title={isDark ? t('theme.switchToLight', 'Switch to Light Mode') : t('theme.switchToDark', 'Switch to Dark Mode')}
    >
      {/* Animated SVG Container - Matching size with Cart & Favorites */}
      <div className="relative w-7 h-7 flex items-center justify-center pointer-events-none">
        <svg
          xmlns="http://www.w3.org/2000/svg"
          viewBox="0 0 24 24"
          className="w-7 h-7 overflow-visible"
        >
          <defs>
            {/* Mask for Moon Crescent */}
            <mask id="theme-crescent-mask">
              <rect x="0" y="0" width="100%" height="100%" fill="white" />
              <motion.circle
                initial={false}
                animate={{
                  cx: isDark ? 28 : 17,
                  cy: isDark ? -4 : 7,
                  r: isDark ? 0 : 7,
                }}
                transition={{
                  type: 'spring',
                  stiffness: 260,
                  damping: 20,
                }}
                fill="black"
              />
            </mask>

            {/* Sun Core Gradient */}
            <radialGradient id="sun-core-gradient" cx="50%" cy="50%" r="50%">
              <stop offset="0%" stopColor="#FEF3C7" />
              <stop offset="50%" stopColor="#FBBF24" />
              <stop offset="100%" stopColor="#F59E0B" />
            </radialGradient>

            {/* Moon Crescent Gradient */}
            <linearGradient id="moon-core-gradient" x1="0%" y1="0%" x2="100%" y2="100%">
              <stop offset="0%" stopColor="#64748B" />
              <stop offset="60%" stopColor="#475569" />
              <stop offset="100%" stopColor="#334155" />
            </linearGradient>
          </defs>

          {/* ========================================================================= */}
          {/* SUN STATE (Dark Mode Active -> Showing Animated Sun to Switch to Light) */}
          {/* ========================================================================= */}
          <AnimatePresence>
            {isDark && (
              <motion.g
                key="sun-group"
                initial={{ opacity: 0, scale: 0.5, rotate: -45 }}
                animate={{ opacity: 1, scale: 1, rotate: 0 }}
                exit={{ opacity: 0, scale: 0.5, rotate: 45 }}
                transition={{ duration: 0.35, ease: 'easeOut' }}
                className="origin-center"
              >
                {/* 1. Continuous Solar Corona / Breathing Flare */}
                <motion.circle
                  cx="12"
                  cy="12"
                  r="6.5"
                  fill="none"
                  stroke="#FDE68A"
                  strokeWidth="1"
                  animate={{
                    scale: [0.95, 1.25, 0.95],
                    opacity: [0.5, 0, 0.5],
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
                    const innerR = 7.2;
                    const outerR = isAlt ? 10.2 : 11.2;

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
                        strokeWidth={isAlt ? '1.6' : '2.2'}
                        strokeLinecap="round"
                        animate={{
                          opacity: [0.75, 1, 0.75],
                          strokeWidth: isAlt ? ['1.4', '1.8', '1.4'] : ['2', '2.5', '2'],
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
                  fill="url(#sun-core-gradient)"
                  animate={{
                    scale: [1, 1.08, 1],
                  }}
                  transition={{
                    duration: 2,
                    repeat: Infinity,
                    ease: 'easeInOut',
                  }}
                  className="origin-center"
                />

                {/* 4. Orbiting solar particle */}
                <motion.circle
                  r="0.75"
                  fill="#FFF"
                  animate={{
                    cx: [12 + 6 * Math.cos(0), 12 + 6 * Math.cos(Math.PI), 12 + 6 * Math.cos(Math.PI * 2)],
                    cy: [12 + 6 * Math.sin(0), 12 + 6 * Math.sin(Math.PI), 12 + 6 * Math.sin(Math.PI * 2)],
                    opacity: [0.3, 0.9, 0.3],
                  }}
                  transition={{
                    duration: 4,
                    repeat: Infinity,
                    ease: 'linear',
                  }}
                />
              </motion.g>
            )}
          </AnimatePresence>

          {/* =========================================================================== */}
          {/* MOON STATE (Light Mode Active -> Showing Animated Moon to Switch to Dark) */}
          {/* =========================================================================== */}
          <AnimatePresence>
            {!isDark && (
              <motion.g
                key="moon-group"
                initial={{ opacity: 0, scale: 0.5, rotate: 45 }}
                animate={{ opacity: 1, scale: 1, rotate: 0 }}
                exit={{ opacity: 0, scale: 0.5, rotate: -45 }}
                transition={{ duration: 0.35, ease: 'easeOut' }}
                className="origin-center"
              >
                {/* 1. Continuous Floating / Bobbing Crescent Moon */}
                <motion.g
                  animate={{
                    y: [-1, 1, -1],
                    rotate: [-3, 3, -3],
                  }}
                  transition={{
                    duration: 3.2,
                    repeat: Infinity,
                    ease: 'easeInOut',
                  }}
                  className="origin-center"
                >
                  <circle
                    cx="12"
                    cy="12"
                    r="7.5"
                    mask="url(#theme-crescent-mask)"
                    fill="url(#moon-core-gradient)"
                  />
                </motion.g>

                {/* 2. Twinkling & Spinning Star #1 (Top Right) */}
                <motion.g
                  animate={{
                    scale: [0.6, 1.25, 0.6],
                    opacity: [0.35, 1, 0.35],
                    rotate: [0, 90, 180],
                  }}
                  transition={{
                    duration: 2.2,
                    repeat: Infinity,
                    ease: 'easeInOut',
                  }}
                  style={{ originX: '18px', originY: '6px' }}
                >
                  <circle cx="18" cy="6" r="1.3" fill="#64748B" />
                  <path
                    d="M 18 3.5 L 18 8.5 M 15.5 6 L 20.5 6"
                    stroke="#94A3B8"
                    strokeWidth="0.9"
                    strokeLinecap="round"
                  />
                </motion.g>

                {/* 3. Twinkling Star #2 (Bottom Right) */}
                <motion.g
                  animate={{
                    scale: [0.5, 1.15, 0.5],
                    opacity: [0.25, 0.9, 0.25],
                    rotate: [0, -90, -180],
                  }}
                  transition={{
                    duration: 2.8,
                    repeat: Infinity,
                    ease: 'easeInOut',
                    delay: 0.6,
                  }}
                  style={{ originX: '17px', originY: '17px' }}
                >
                  <circle cx="17" cy="17" r="1" fill="#94A3B8" />
                  <path
                    d="M 17 15 L 17 19 M 15 17 L 19 17"
                    stroke="#CBD5E1"
                    strokeWidth="0.8"
                    strokeLinecap="round"
                  />
                </motion.g>

                {/* 4. Twinkling Star #3 (Left Low) */}
                <motion.g
                  animate={{
                    scale: [0.7, 1.3, 0.7],
                    opacity: [0.3, 0.85, 0.3],
                  }}
                  transition={{
                    duration: 1.9,
                    repeat: Infinity,
                    ease: 'easeInOut',
                    delay: 1.1,
                  }}
                  style={{ originX: '6.5px', originY: '16.5px' }}
                >
                  <circle cx="6.5" cy="16.5" r="0.9" fill="#64748B" />
                </motion.g>

                {/* 5. Subtle Cosmic Orbiting Ring */}
                <motion.ellipse
                  cx="12"
                  cy="12"
                  rx="9"
                  ry="4.5"
                  fill="none"
                  stroke="rgba(148, 163, 184, 0.3)"
                  strokeWidth="0.75"
                  strokeDasharray="2 4"
                  animate={{
                    rotate: [-20, 20, -20],
                    opacity: [0.2, 0.5, 0.2],
                  }}
                  transition={{
                    duration: 4,
                    repeat: Infinity,
                    ease: 'easeInOut',
                  }}
                  className="origin-center"
                />
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
