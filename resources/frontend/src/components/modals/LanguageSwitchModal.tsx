import React, { useEffect, useState } from 'react';
import { motion, AnimatePresence } from 'framer-motion';
import { FiGlobe } from 'react-icons/fi';
import i18n from '../../i18n';

interface LanguageSwitchEventDetail {
  language: 'ar' | 'en' | string;
}

export const LanguageSwitchModal: React.FC = () => {
  const [targetLang, setTargetLang] = useState<'ar' | 'en' | null>(null);
  const [sourceLang, setSourceLang] = useState<'ar' | 'en'>('ar');
  const [isOpen, setIsOpen] = useState(false);

  useEffect(() => {
    const handleSwitchEvent = (e: Event) => {
      const customEvent = e as CustomEvent<LanguageSwitchEventDetail>;
      const newLang = (customEvent.detail?.language || 'ar') as 'ar' | 'en';
      const current = (i18n.language || 'ar') as 'ar' | 'en';

      // If already on this language, do nothing
      if (newLang === current) {
        return;
      }

      setSourceLang(current);
      setTargetLang(newLang);
      setIsOpen(true);

      // Save cookie
      const expires = new Date();
      expires.setTime(expires.getTime() + (365 * 24 * 60 * 60 * 1000));
      document.cookie = `user_locale=${newLang};expires=${expires.toUTCString()};path=/;SameSite=Lax`;

      // Save localStorage
      try {
        localStorage.setItem('i18nextLng', newLang);
        localStorage.setItem('user_locale', newLang);
      } catch (err) {
        // Storage access restricted
      }

      // Smooth delay to showcase the luxury transition before refreshing
      const timer = setTimeout(() => {
        window.location.reload();
      }, 750);

      return () => clearTimeout(timer);
    };

    window.addEventListener('cairo3a:switch-language', handleSwitchEvent);
    return () => {
      window.removeEventListener('cairo3a:switch-language', handleSwitchEvent);
    };
  }, []);

  // When source is English (user is switching FROM English TO Arabic)
  const isFromEnglish = sourceLang === 'en';

  return (
    <AnimatePresence>
      {isOpen && (
        <motion.div
          initial={{ opacity: 0 }}
          animate={{ opacity: 1 }}
          exit={{ opacity: 0 }}
          transition={{ duration: 0.25 }}
          className="fixed inset-0 z-[99999] flex items-center justify-center p-4 bg-black/40 dark:bg-slate-950/75 backdrop-blur-xl select-none"
        >
          {/* Ambient Background Glow */}
          <div className="absolute w-80 h-80 bg-gradient-to-tr from-[#B12C2E]/20 via-[#E03E41]/15 to-amber-500/10 dark:from-[#B12C2E]/30 dark:via-[#E03E41]/20 dark:to-amber-500/15 rounded-full blur-3xl pointer-events-none animate-pulse" />

          {/* Modal Container with Light & Dark theme support */}
          <motion.div
            initial={{ scale: 0.85, opacity: 0, y: 15 }}
            animate={{ scale: 1, opacity: 1, y: 0 }}
            exit={{ scale: 0.9, opacity: 0, y: -10 }}
            transition={{ type: 'spring', damping: 25, stiffness: 350 }}
            className="relative w-full max-w-sm overflow-hidden rounded-3xl border border-gray-200/80 dark:border-slate-700/60 bg-white/95 dark:bg-gradient-to-b dark:from-slate-900/95 dark:via-[#0D1422]/95 dark:to-slate-950/98 p-8 text-center shadow-[0_25px_60px_-15px_rgba(0,0,0,0.15),0_0_35px_rgba(177,44,46,0.12)] dark:shadow-[0_25px_60px_-15px_rgba(0,0,0,0.7),0_0_40px_rgba(177,44,46,0.25)] text-gray-900 dark:text-white transition-colors duration-300"
          >
            {/* Top Gloss Flare */}
            <div className="absolute top-0 left-1/2 -translate-x-1/2 w-48 h-[1.5px] bg-gradient-to-r from-transparent via-[#E03E41] to-transparent shadow-[0_0_12px_#E03E41]" />

            {/* Animated Center Orb / Globe */}
            <div className="relative mx-auto mb-6 flex h-24 w-24 items-center justify-center">
              {/* Outer Orbiting Ring */}
              <motion.div
                animate={{ rotate: 360 }}
                transition={{ duration: 8, repeat: Infinity, ease: 'linear' }}
                className="absolute inset-0 rounded-full border border-dashed border-[#B12C2E]/30 dark:border-[#E03E41]/40"
              />

              {/* Inner Pulsing Ring with Glow */}
              <motion.div
                animate={{ rotate: -360 }}
                transition={{ duration: 5, repeat: Infinity, ease: 'linear' }}
                className="absolute inset-2 rounded-full border-2 border-transparent border-t-[#B12C2E] dark:border-t-[#E03E41] border-r-[#B12C2E]/50 dark:border-r-[#B12C2E]/60 shadow-[0_0_20px_rgba(224,62,65,0.3)] dark:shadow-[0_0_20px_rgba(224,62,65,0.4)]"
              />

              {/* Center Globe Badge */}
              <div className="relative flex h-14 w-14 items-center justify-center rounded-2xl bg-gradient-to-br from-[#B12C2E] via-[#8F2325] to-[#5C1416] shadow-[0_4px_25px_rgba(177,44,46,0.5)] border border-white/20">
                <FiGlobe className="h-7 w-7 text-white animate-spin [animation-duration:12s]" />
              </div>
            </div>

            {/* Titles with Light & Dark text styling */}
            <div className="space-y-1.5 mb-6">
              {isFromEnglish ? (
                // User is currently viewing in English -> show all in English
                <div dir="ltr" className="space-y-1.5">
                  <h3 className="text-lg sm:text-xl font-bold tracking-wide text-gray-900 dark:text-white drop-shadow-sm flex items-center justify-center gap-1 font-sans">
                    <span>Switching language to Arabic</span>
                    <span className="inline-flex text-[#B12C2E] dark:text-[#E03E41] font-bold animate-pulse">...</span>
                  </h3>
                  <p className="text-xs sm:text-sm font-medium text-gray-500 dark:text-slate-300/80 font-sans">
                    Updating interface language
                  </p>
                </div>
              ) : (
                // User is currently viewing in Arabic -> show all in Arabic
                <div dir="rtl" className="space-y-1.5">
                  <h3 className="text-lg sm:text-xl font-bold tracking-wide text-gray-900 dark:text-white drop-shadow-sm flex items-center justify-center gap-1 font-sans">
                    <span>جاري تغيير اللغة إلى الإنجليزية</span>
                    <span className="inline-flex text-[#B12C2E] dark:text-[#E03E41] font-bold animate-pulse">...</span>
                  </h3>
                  <p className="text-xs sm:text-sm font-medium text-gray-500 dark:text-slate-300/80 font-sans">
                    يتم تحديث لغة الواجهة
                  </p>
                </div>
              )}
            </div>

            {/* Shimmering Progress Bar */}
            <div className="relative h-1.5 w-full overflow-hidden rounded-full bg-gray-100 dark:bg-slate-800/80 border border-gray-200/60 dark:border-white/5">
              <motion.div
                initial={{ x: '-100%' }}
                animate={{ x: '100%' }}
                transition={{
                  repeat: Infinity,
                  duration: 1.1,
                  ease: 'easeInOut',
                }}
                className="h-full w-2/3 rounded-full bg-gradient-to-r from-transparent via-[#B12C2E] dark:via-[#E03E41] to-amber-500 shadow-[0_0_12px_#E03E41]"
              />
            </div>

            {/* Bottom Subtext */}
            <div
              dir={isFromEnglish ? 'ltr' : 'rtl'}
              className="mt-4 flex items-center justify-center gap-2 text-[11px] font-medium text-gray-400 dark:text-slate-400"
            >
              <span className="inline-block h-1.5 w-1.5 rounded-full bg-[#B12C2E] dark:bg-[#E03E41] animate-ping" />
              <span>
                {isFromEnglish
                  ? 'Applying preferences & reloading...'
                  : 'يتم تطبيق الإعدادات وإعادة التحميل...'}
              </span>
            </div>
          </motion.div>
        </motion.div>
      )}
    </AnimatePresence>
  );
};

export default LanguageSwitchModal;
