import React, { useState, useEffect } from 'react';
import { useDispatch, useSelector } from 'react-redux';
import { useNavigate } from 'react-router-dom';
import { motion, AnimatePresence } from 'framer-motion';
import type { RootState } from '../store/store';
import { useTranslation } from 'react-i18next';
import { hide, show } from '../store/slices/agentBlockerSlice';

interface Props {
  children: React.ReactElement;
  onAllowed: () => void;
}

const AgentCheckoutBlocker: React.FC<Props> = ({ children, onAllowed }) => {
  const { t } = useTranslation(['common']);

  const user = useSelector((s: RootState) => s.auth.user);
  const isAuth = useSelector((s: RootState) => s.auth.isAuth);
  const navigate = useNavigate();
  const visible = useSelector((s: RootState) => s.agentBlocker.visible);
  const dispatch = useDispatch();

  const handleClick = (e: React.MouseEvent) => {
    e.preventDefault();
    if (isAuth && user?.is_agent) {
      dispatch(show())
    } else {
      onAllowed();
    }
  };

  const closePopup = () => {
    dispatch(hide());
  };

  // Prevent background scroll when popup is shown
  useEffect(() => {
    if (visible) {
      document.body.style.overflow = 'hidden';
    } else {
      document.body.style.overflow = '';
    }
    return () => {
      document.body.style.overflow = '';
    };
  }, [visible]);

  return (
    <>
      {React.cloneElement(children, { onClick: handleClick })}

      <AnimatePresence>
        {visible && (
          <motion.div
            key="backdrop"
            initial={{ opacity: 0 }}
            animate={{ opacity: 1 }}
            exit={{ opacity: 0 }}
            transition={{ duration: 0.2 }}
            className="fixed inset-0 z-[1000] flex items-center justify-center bg-black/60 backdrop-blur-sm"
            onClick={closePopup}
          >
            <motion.div
              key="popup"
              initial={{ scale: 0.9, opacity: 0 }}
              animate={{ scale: 1, opacity: 1 }}
              exit={{ scale: 0.9, opacity: 0 }}
              transition={{ duration: 0.2 }}
              className="bg-white rounded-2xl shadow-2xl p-8 max-w-md mx-4"
              onClick={(e) => e.stopPropagation()}
            >
              {/* Icon */}
              <div className="flex justify-center mb-6">
                <div className="w-16 h-16 bg-red-100 rounded-full flex items-center justify-center">
                  <svg
                    className="w-8 h-8 text-red-500"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                  >
                    <path
                      strokeLinecap="round"
                      strokeLinejoin="round"
                      strokeWidth={2}
                      d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.99-.833-2.464 0L4.35 16.5c-.77.833.192 2.5 1.732 2.5z"
                    />
                  </svg>
                </div>
              </div>

              {/* Content */}
              <div className="text-center">
                <h2 className="text-2xl font-bold text-gray-900 mb-3">
                  {t('agentCheckoutBlocker.title')}
                </h2>
                <p className="text-gray-600 mb-6 leading-relaxed">
                  {t('agentCheckoutBlocker.message')}
                </p>
              </div>

              {/* Actions */}
              <div className="flex gap-3">
                <button
                  onClick={closePopup}
                  className="flex-1 bg-yellow hover:bg-yellow-600 text-white cursor-pointer font-medium py-3 px-6 rounded-xl transition-colors duration-200"
                >
                  {t('buttons.cancel')}
                </button>
              </div>
            </motion.div>
          </motion.div>
        )}
      </AnimatePresence>
    </>
  );
};

export default AgentCheckoutBlocker;