import React, { useEffect, useState } from 'react';
import { useDispatch, useSelector } from 'react-redux';
import { FaLock, FaEnvelope, FaTimes, FaUser } from 'react-icons/fa';
import ApiClient, { setAccessToken } from '../ApiClient';
import { setUser, setIsAuth } from '../store/slices/userSlice';
import type { RootState } from '../store/store';
import { setIsAuthPopupOpen } from '../store/slices/isAuthPopupOpenSlice';
import { motion, AnimatePresence } from 'framer-motion';
import Popup from './Popup';
import TextInput from './inputs/TextInput';
import PasswordInput from './inputs/PasswordInput';
import { useTranslation } from 'react-i18next';

const LoginPrompt = () => {
  const { t } = useTranslation(['auth', 'common']);
  const [form, setForm] = useState({
    email: '',
    password: '',
    full_name: '',
    confirmPassword: '',
  });
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [showSuccessPopup, setShowSuccessPopup] = useState(false);
  const [successMessage, setSuccessMessage] = useState('');
  const [popupType, setPopupType] = useState<'login' | 'register'>('login');
  const [isFormTransitioning, setIsFormTransitioning] = useState(false);

  const authPopupState = useSelector((state: RootState) => (state as any).isAuthPopupOpen);
  const isAuthPopup = typeof authPopupState === 'boolean' ? authPopupState : !!authPopupState?.isOpen;
  const targetMode = typeof authPopupState === 'object' ? authPopupState?.mode : 'login';
  const dispatch = useDispatch();

  useEffect(() => {
    if (isAuthPopup) {
      setPopupType(targetMode || 'login');
      setError(null);
    } else {
      setForm({
        email: '',
        password: '',
        full_name: '',
        confirmPassword: '',
      });
      setError(null);
      setPopupType('login');
    }
  }, [isAuthPopup, targetMode]);

  useEffect(() => {
    if (isAuthPopup || showSuccessPopup) {
      document.body.style.overflow = 'hidden';
    } else {
      document.body.style.overflow = '';
    }

    return () => {
      document.body.style.overflow = '';
    };
  }, [isAuthPopup, showSuccessPopup]);

  const handleBackdropClick = (e: React.MouseEvent<HTMLDivElement>) => {
    if (e.target === e.currentTarget) {
      dispatch(setIsAuthPopupOpen(false));
    }
  };

  const handleLogin = async (e: React.FormEvent) => {
    e.preventDefault();
    setLoading(true);
    setError(null);

    try {
      const response = await ApiClient.auth.login(form.email, form.password);
      const data = response.data.data;

      setAccessToken(data.access_token.plain_token, data.access_token.expires_at);

      setPopupType('login');
      setSuccessMessage(t('auth:loginPrompt.loginSuccessMessage'));
      setShowSuccessPopup(true);
      setLoading(false);
      dispatch(setUser(data.user));
      dispatch(setIsAuth(true));
      dispatch(setIsAuthPopupOpen(false));

    } catch (error: any) {
      console.error('Login failed:', error);
      setError(error.response?.data?.message || 'Invalid email or password. Please try again.');
      setLoading(false);
    }
  };

  const handleSignup = async (e: React.FormEvent) => {
    e.preventDefault();

    setLoading(true);
    setError(null);

    try {
      const data = {
        full_name: form.full_name,
        email: form.email,
        password: form.password,
        password_confirmation: form.confirmPassword
      };
      const response = await ApiClient.auth.register(data);
      const responseData = response.data.data;

      setAccessToken(responseData.access_token.plain_token, responseData.access_token.expires_at);

      dispatch(setUser(responseData.user));
      dispatch(setIsAuth(true));

      setPopupType('register');
      setSuccessMessage(t('auth:loginPrompt.registerSuccessMessage', { name: form.full_name }));
      setShowSuccessPopup(true);
      setLoading(false);
      dispatch(setIsAuthPopupOpen(false));
      setForm({
        email: '',
        password: '',
        full_name: '',
        confirmPassword: '',
      });

    } catch (error: any) {
      console.error('Signup failed:', error);
      setError(error.response?.data?.message || 'Registration failed. Please try again.');
      setLoading(false);
    }
  };

  const toggleMode = () => {
    setIsFormTransitioning(true);
    setError(null);

    setTimeout(() => {
      setPopupType(popupType === 'login' ? 'register' : 'login');
      setTimeout(() => {
        setIsFormTransitioning(false);
      }, 500);
    }, 200);
  };

  const formVariants = {
    initial: {
      opacity: 0,
      scale: 0.95,
      y: 10,
    },
    visible: {
      opacity: 1,
      scale: 1,
      y: 0,
      transition: {
        duration: 0.3,
        ease: "easeOut"
      }
    },
    exit: {
      opacity: 0,
      scale: 0.95,
      y: -10,
      transition: {
        duration: 0.2,
        ease: "easeIn"
      }
    }
  };

  return (
    <>
      <AnimatePresence>
        {isAuthPopup && (
          <motion.div
            className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm"
            onClick={handleBackdropClick}
            initial={{ opacity: 0 }}
            animate={{ opacity: 1 }}
            exit={{ opacity: 0 }}
            transition={{ duration: 0.3 }}
          >
            <motion.div
              className="bg-white dark:bg-[#151D2C] rounded-2xl shadow-2xl p-0 max-w-md w-full overflow-hidden border border-gray-100 dark:border-slate-800 transition-colors"
              onClick={(e) => e.stopPropagation()}
              initial={{ y: 20, opacity: 0 }}
              animate={{ y: 0, opacity: 1 }}
              exit={{ y: 20, opacity: 0 }}
              transition={{ duration: 0.3 }}
            >
              <div className="bg-gradient-to-r from-[#B12C2E] to-[#8F2325] p-6 relative text-white">
                <button
                  onClick={() => dispatch(setIsAuthPopupOpen(false))}
                  className="absolute top-4 right-4 rtl:right-auto rtl:left-4 cursor-pointer text-white/80 hover:text-white hover:bg-white/10 rounded-full p-1.5 transition-colors"
                >
                  <FaTimes size={16} />
                </button>
                <div className="flex justify-center mb-2">
                  <div className="bg-white/15 backdrop-blur-sm p-3 rounded-full inline-flex items-center justify-center border border-white/20">
                    <FaLock className="text-white text-xl" />
                  </div>
                </div>
                <div className="text-center">
                  <h2 className="text-xl font-bold">
                    {popupType === 'login' ? t('auth:loginPrompt.title') : t('auth:signup.createAccount')}
                  </h2>
                  <p className="text-sm opacity-90 mt-1">
                    {popupType === 'login'
                      ? t('auth:loginPrompt.message')
                      : t('auth:loginPrompt.message')
                    }
                  </p>
                </div>
              </div>

              <div className="p-6 relative min-h-[300px]">
                {error && (
                  <div className="mb-4 p-3 bg-red-50 dark:bg-red-950/40 border border-red-100 dark:border-red-900/50 text-red-700 dark:text-red-400 rounded-xl text-sm font-medium">
                    {error}
                  </div>
                )}

                <AnimatePresence mode="wait">
                  {isFormTransitioning ? (
                    <motion.div
                      key="form-transition"
                      className="absolute inset-0 flex flex-col items-center justify-center p-6"
                      initial={{ opacity: 0 }}
                      animate={{ opacity: 1 }}
                      exit={{ opacity: 0 }}
                      transition={{ duration: 0.2 }}
                    >
                      <div className="w-10 h-10 rounded-full border-3 border-[#B12C2E] border-t-transparent animate-spin mb-3"></div>
                      <p className="text-gray-500 dark:text-slate-400 text-sm animate-pulse">
                        {t('auth:loginPrompt.message')}
                      </p>
                    </motion.div>
                  ) : (
                    <motion.form
                      key={popupType}
                      onSubmit={popupType === 'login' ? handleLogin : handleSignup}
                      variants={formVariants}
                      initial="initial"
                      animate="visible"
                      exit="exit"
                    >
                      {popupType === 'register' && (
                        <div className="mb-4">
                          <TextInput
                            id="full_name"
                            type="text"
                            label={t('auth:signup.fullNameLabel')}
                            value={form.full_name}
                            onChange={(e) => setForm({ ...form, full_name: e.target.value })}
                            placeholder={t('auth:signup.fullNamePlaceholder')}
                            required
                            icon={<FaUser className="text-gray-400 dark:text-slate-500" />}
                          />
                        </div>
                      )}

                      <div className="mb-4">
                        <TextInput
                          id="email"
                          type="email"
                          label={t('auth:signup.emailLabel')}
                          value={form.email}
                          onChange={(e) => setForm({ ...form, email: e.target.value })}
                          placeholder={t('auth:signup.emailPlaceholder')}
                          required
                          icon={<FaEnvelope className="text-gray-400 dark:text-slate-500" />}
                        />
                      </div>

                      <div className="mb-6">
                        <PasswordInput
                          id="password"
                          label={t('auth:signup.passwordLabel')}
                          placeholder={t('auth:signup.passwordPlaceholder')}
                          value={form.password}
                          onChange={(e) => setForm({ ...form, password: e.target.value })}
                          required
                          icon={<FaLock className="text-gray-400 dark:text-slate-500" />}
                        />
                      </div>

                      {popupType === 'register' && (
                        <div className="mb-6">
                          <PasswordInput
                            id="confirmPassword"
                            label={t('auth:signup.confirmPasswordLabel')}
                            placeholder={t('auth:signup.confirmPasswordPlaceholder')}
                            value={form.confirmPassword}
                            onChange={(e) => setForm({ ...form, confirmPassword: e.target.value })}
                            required
                            icon={<FaLock className="text-gray-400 dark:text-slate-500" />}
                          />
                        </div>
                      )}

                      {popupType === 'login' && (
                        <div className="flex items-center justify-between mb-4">
                          <div className="text-sm">
                            <a href="/forgot-password" className="text-[#B12C2E] dark:text-[#E03E41] hover:underline font-semibold">
                              {t('auth:login.forgotPassword')}
                            </a>
                          </div>
                        </div>
                      )}

                      <div className="flex flex-col space-y-3">
                        <button
                          type="submit"
                          disabled={loading}
                          className={`cursor-pointer w-full bg-[#B12C2E] hover:bg-[#972426] dark:bg-[#B12C2E] dark:hover:bg-[#8F2325] text-white font-bold py-3 px-4 rounded-xl transition-all shadow-sm ${loading ? 'opacity-70 cursor-not-allowed' : ''}`}
                        >
                          {loading
                            ? (popupType === 'login' ? t('auth:login.loginButton') : t('auth:signup.signupButton'))
                            : (popupType === 'login' ? t('auth:login.loginButton') : t('auth:signup.signupButton'))
                          }
                        </button>
                        <div className="text-center text-sm text-gray-600 dark:text-slate-400">
                          {popupType === 'login'
                            ? t('auth:login.noAccount')
                            : t('auth:signup.haveAccount')
                          }{' '}
                          <button
                            type="button"
                            onClick={toggleMode}
                            className="text-[#B12C2E] dark:text-[#E03E41] hover:underline font-bold cursor-pointer ml-1"
                          >
                            {popupType === 'login' ? t('auth:login.signupLink') : t('auth:login.loginButton')}
                          </button>
                        </div>
                      </div>
                    </motion.form>
                  )}
                </AnimatePresence>
              </div>
            </motion.div>
          </motion.div>
        )}
      </AnimatePresence>

      <Popup
        isOpen={showSuccessPopup}
        onClose={() => setShowSuccessPopup(false)}
        title={popupType === 'login' ? t('auth:login.successTitle') : t('auth:signup.successTitle')}
        message={successMessage}
        type="success"
        autoCloseTime={3000}
      />
    </>
  );
};

export default LoginPrompt;
