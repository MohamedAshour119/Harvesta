import React, { useEffect, useState } from 'react';
import { useDispatch, useSelector } from 'react-redux';
import { FaLock, FaEnvelope, FaTimes, FaUser, FaKey, FaArrowLeft, FaArrowRight, FaClock } from 'react-icons/fa';
import ApiClient, { setAccessToken } from '../ApiClient';
import { setUser, setIsAuth } from '../store/slices/userSlice';
import type { RootState } from '../store/store';
import { setIsAuthPopupOpen } from '../store/slices/isAuthPopupOpenSlice';
import { motion, AnimatePresence } from 'framer-motion';
import Popup from './Popup';
import TextInput from './inputs/TextInput';
import PasswordInput from './inputs/PasswordInput';
import { useTranslation } from 'react-i18next';
import PageLoader from './PageLoader';

const LoginPrompt = () => {
  const { t, i18n } = useTranslation(['auth', 'common']);
  const isRTL = i18n.language === 'ar';

  const [form, setForm] = useState({
    email: '',
    password: '',
    full_name: '',
    confirmPassword: '',
  });
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [fieldErrors, setFieldErrors] = useState<{ [key: string]: string }>({});
  const [showSuccessPopup, setShowSuccessPopup] = useState(false);
  const [successMessage, setSuccessMessage] = useState('');
  const [successPopupTitle, setSuccessPopupTitle] = useState('');
  const [forgotPasswordSuccess, setForgotPasswordSuccess] = useState<string | null>(null);
  const [popupType, setPopupType] = useState<'login' | 'register' | 'forgot-password' | 'reset-password'>('login');
  const [isFormTransitioning, setIsFormTransitioning] = useState(false);
  const [countdown, setCountdown] = useState<number>(0);

  useEffect(() => {
    if (countdown <= 0) return;
    const timer = setInterval(() => {
      setCountdown((prev) => {
        if (prev <= 1) {
          clearInterval(timer);
          return 0;
        }
        return prev - 1;
      });
    }, 1000);
    return () => clearInterval(timer);
  }, [countdown]);

  const authPopupState = useSelector((state: RootState) => (state as any).isAuthPopupOpen);
  const isAuthPopup = typeof authPopupState === 'boolean' ? authPopupState : !!authPopupState?.isOpen;
  const targetMode = typeof authPopupState === 'object' ? authPopupState?.mode : 'login';
  const resetToken = typeof authPopupState === 'object' ? authPopupState?.resetToken : null;
  const resetEmail = typeof authPopupState === 'object' ? authPopupState?.resetEmail : null;
  const dispatch = useDispatch();

  useEffect(() => {
    if (isAuthPopup) {
      setPopupType(targetMode || 'login');
      setError(null);
      setFieldErrors({});
      setForgotPasswordSuccess(null);
      setIsFormTransitioning(false);

      if (targetMode === 'reset-password' && (!resetToken || !resetEmail)) {
        setError(t('auth:resetPassword.errorMessages.invalidToken'));
      }
    } else {
      setForm({
        email: '',
        password: '',
        full_name: '',
        confirmPassword: '',
      });
      setError(null);
      setFieldErrors({});
      setForgotPasswordSuccess(null);
      setPopupType('login');
      setIsFormTransitioning(false);
    }
  }, [isAuthPopup, targetMode, resetToken, resetEmail, t]);

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

  const handleInputChange = (field: string, value: string) => {
    setForm((prev) => ({ ...prev, [field]: value }));
    if (error) setError(null);
    if (fieldErrors[field] || (field === 'confirmPassword' && fieldErrors.password_confirmation)) {
      setFieldErrors((prev) => {
        const updated = { ...prev };
        delete updated[field];
        if (field === 'confirmPassword') {
          delete updated.password_confirmation;
        }
        return updated;
      });
    }
  };

  const handleBackdropClick = (e: React.MouseEvent<HTMLDivElement>) => {
    if (e.target === e.currentTarget) {
      dispatch(setIsAuthPopupOpen(false));
    }
  };

  const switchMode = (target: 'login' | 'register' | 'forgot-password' | 'reset-password') => {
    setIsFormTransitioning(true);
    setError(null);
    setFieldErrors({});
    setForgotPasswordSuccess(null);
    setPopupType(target);

    setTimeout(() => {
      setIsFormTransitioning(false);
    }, 1000);
  };

  const handleLogin = async (e: React.FormEvent) => {
    e.preventDefault();
    setLoading(true);
    setError(null);
    setFieldErrors({});

    try {
      const response = await ApiClient.auth.login(form.email, form.password);
      const data = response.data.data;

      setAccessToken(data.access_token.plain_token, data.access_token.expires_at);

      setPopupType('login');
      setSuccessPopupTitle(t('auth:login.successTitle'));
      setSuccessMessage(t('auth:loginPrompt.loginSuccessMessage'));
      setShowSuccessPopup(true);
      setLoading(false);
      dispatch(setUser(data.user));
      dispatch(setIsAuth(true));
      dispatch(setIsAuthPopupOpen(false));

    } catch (error: any) {
      console.error('Login failed:', error);
      const apiErrors = error.response?.data?.errors;
      const hasFieldErrors = apiErrors && typeof apiErrors === 'object' && !Array.isArray(apiErrors) && Object.keys(apiErrors).length > 0;
      if (hasFieldErrors) {
        const formattedFieldErrors: { [key: string]: string } = {};
        Object.keys(apiErrors).forEach((field) => {
          const errorMsg = Array.isArray(apiErrors[field]) ? apiErrors[field][0] : apiErrors[field];
          formattedFieldErrors[field] = errorMsg;
        });
        setFieldErrors(formattedFieldErrors);
        setError(null);
      } else {
        setFieldErrors({});
        setError(error.response?.data?.message || t('auth:login.invalidCredentials'));
      }
      setLoading(false);
    }
  };

  const handleSignup = async (e: React.FormEvent) => {
    e.preventDefault();

    setLoading(true);
    setError(null);
    setFieldErrors({});

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
      setSuccessPopupTitle(t('auth:signup.successTitle'));
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
      const apiErrors = error.response?.data?.errors;
      const hasFieldErrors = apiErrors && typeof apiErrors === 'object' && !Array.isArray(apiErrors) && Object.keys(apiErrors).length > 0;
      if (hasFieldErrors) {
        const formattedFieldErrors: { [key: string]: string } = {};
        Object.keys(apiErrors).forEach((field) => {
          const errorMsg = Array.isArray(apiErrors[field]) ? apiErrors[field][0] : apiErrors[field];
          formattedFieldErrors[field] = errorMsg;
        });
        setFieldErrors(formattedFieldErrors);
        setError(null);
      } else {
        setFieldErrors({});
        setError(error.response?.data?.message || t('auth:signup.errorMessages.failed'));
      }
      setLoading(false);
    }
  };

  const handleForgotPassword = async (e?: React.FormEvent) => {
    if (e) e.preventDefault();
    if (!form.email || !form.email.trim()) {
      setFieldErrors({ email: t('auth:forgotPassword.errorMessages.requiredEmail') });
      return;
    }

    setLoading(true);
    setError(null);
    setFieldErrors({});

    try {
      const response = await ApiClient.auth.forgotPassword(form.email);
      setForgotPasswordSuccess(response.data?.message || t('auth:forgotPassword.successMessage'));
      const retryAfter = response.data?.data?.retry_after;
      setCountdown(typeof retryAfter === 'number' ? retryAfter : 0);
    } catch (error: any) {
      console.error('Forgot password failed:', error);
      const apiErrors = error.response?.data?.errors;
      const status = error.response?.status;
      const message = error.response?.data?.message || '';

      if (status === 429 || message.includes('wait') || message.includes('انتظار') || message.includes('throttle')) {
        const errorRetryAfter = error.response?.data?.data?.retry_after;
        setCountdown(typeof errorRetryAfter === 'number' && errorRetryAfter > 0 ? errorRetryAfter : 60);
      }

      const hasFieldErrors = apiErrors && typeof apiErrors === 'object' && !Array.isArray(apiErrors) && Object.keys(apiErrors).length > 0;

      if (hasFieldErrors) {
        const formattedFieldErrors: { [key: string]: string } = {};
        Object.keys(apiErrors).forEach((field) => {
          const errorMsg = Array.isArray(apiErrors[field]) ? apiErrors[field][0] : apiErrors[field];
          formattedFieldErrors[field] = errorMsg;
        });
        setFieldErrors(formattedFieldErrors);
        setError(null);
      } else {
        const resolvedError = message || t('auth:forgotPassword.errorMessages.emailNotFound');
        setFieldErrors({});
        setError(resolvedError);
      }
    } finally {
      setLoading(false);
    }
  };

  const handleResetPassword = async (e: React.FormEvent) => {
    e.preventDefault();

    if (!form.password) {
      setFieldErrors({ password: t('auth:resetPassword.errorMessages.requiredPassword') });
      return;
    }

    if (form.password.length < 8) {
      setFieldErrors({ password: t('auth:resetPassword.errorMessages.passwordLength') });
      return;
    }

    if (form.password !== form.confirmPassword) {
      setFieldErrors({ confirmPassword: t('auth:resetPassword.errorMessages.passwordMismatch') });
      return;
    }

    if (!resetToken || !resetEmail) {
      setError(t('auth:resetPassword.errorMessages.invalidToken'));
      return;
    }

    setLoading(true);
    setError(null);
    setFieldErrors({});

    try {
      const response = await ApiClient.auth.resetPassword({
        token: resetToken,
        email: resetEmail,
        password: form.password,
        password_confirmation: form.confirmPassword,
      });

      setSuccessPopupTitle(t('auth:resetPassword.successTitle'));
      setSuccessMessage(response.data?.message || t('auth:resetPassword.successMessage'));
      setShowSuccessPopup(true);
      setForm({
        email: '',
        password: '',
        full_name: '',
        confirmPassword: '',
      });
      switchMode('login');
    } catch (error: any) {
      console.error('Password reset failed:', error);
      const apiErrors = error.response?.data?.errors;
      const hasFieldErrors = apiErrors && typeof apiErrors === 'object' && !Array.isArray(apiErrors) && Object.keys(apiErrors).length > 0;
      if (hasFieldErrors) {
        const formattedFieldErrors: { [key: string]: string } = {};
        Object.keys(apiErrors).forEach((field) => {
          const errorMsg = Array.isArray(apiErrors[field]) ? apiErrors[field][0] : apiErrors[field];
          formattedFieldErrors[field] = errorMsg;
        });
        setFieldErrors(formattedFieldErrors);
        setError(null);
      } else {
        setFieldErrors({});
        setError(error.response?.data?.message || t('auth:resetPassword.errorMessages.failed'));
      }
    } finally {
      setLoading(false);
    }
  };

  const formVariants = {
    initial: {
      opacity: 0,
      y: 8,
    },
    visible: {
      opacity: 1,
      y: 0,
      transition: {
        duration: 0.2,
        ease: "easeOut"
      }
    },
    exit: {
      opacity: 0,
      y: -8,
      transition: {
        duration: 0.15,
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
            transition={{ duration: 0.25 }}
          >
            <motion.div
              layout
              className="bg-white dark:bg-[#151D2C] rounded-2xl shadow-2xl p-0 max-w-md w-full overflow-hidden border border-gray-100 dark:border-slate-800 transition-colors"
              onClick={(e) => e.stopPropagation()}
              initial={{ y: 20, opacity: 0, scale: 0.96 }}
              animate={{ y: 0, opacity: 1, scale: 1 }}
              exit={{ y: 20, opacity: 0, scale: 0.96 }}
              transition={{ 
                duration: 0.25,
                layout: { duration: 0.25, ease: "easeOut" }
              }}
            >
              <div className="bg-gradient-to-r from-[#B12C2E] to-[#8F2325] p-5 sm:p-6 relative text-white">
                <button
                  onClick={() => dispatch(setIsAuthPopupOpen(false))}
                  className="absolute top-4 right-4 rtl:right-auto rtl:left-4 cursor-pointer text-white/80 hover:text-white hover:bg-white/10 rounded-full p-1.5 transition-colors"
                >
                  <FaTimes size={16} />
                </button>
                <div className="flex justify-center mb-2">
                  <div className="bg-white/15 backdrop-blur-sm p-3 rounded-full inline-flex items-center justify-center border border-white/20">
                    {popupType === 'login' ? (
                      <FaLock className="text-white text-xl" />
                    ) : popupType === 'register' ? (
                      <FaUser className="text-white text-xl" />
                    ) : (
                      <FaKey className="text-white text-xl" />
                    )}
                  </div>
                </div>
                <div className="text-center">
                  <h2 className="text-xl font-bold">
                    {popupType === 'login'
                      ? t('auth:loginPrompt.title')
                      : popupType === 'register'
                      ? t('auth:loginPrompt.registerTitle')
                      : popupType === 'reset-password'
                      ? t('auth:resetPassword.title')
                      : t('auth:forgotPassword.title')
                    }
                  </h2>
                  <p className="text-sm opacity-90 mt-1 max-w-xs mx-auto leading-relaxed">
                    {popupType === 'login'
                      ? t('auth:loginPrompt.message')
                      : popupType === 'register'
                      ? t('auth:loginPrompt.registerMessage')
                      : popupType === 'reset-password'
                      ? t('auth:resetPassword.subtitle')
                      : t('auth:forgotPassword.description')
                    }
                  </p>
                </div>
              </div>

              <div className="p-6 relative">
                {error && Object.keys(fieldErrors).length === 0 && (
                  <div className="mb-4 p-3 bg-red-50 dark:bg-red-950/40 border border-red-100 dark:border-red-900/50 text-red-700 dark:text-red-400 rounded-xl text-sm font-medium">
                    {error}
                  </div>
                )}

                <AnimatePresence mode="wait" initial={false}>
                  {isFormTransitioning ? (
                    <motion.div
                      key="form-transition-spinner"
                      className="w-full flex items-center justify-center"
                      initial={{ opacity: 0, scale: 0.94 }}
                      animate={{ opacity: 1, scale: 1 }}
                      exit={{ opacity: 0, scale: 0.94 }}
                      transition={{ duration: 0.15 }}
                    >
                      <PageLoader
                        fullScreen={false}
                        size="md"
                        className="py-6 sm:py-8"
                        message={t('common:loading')}
                      />
                    </motion.div>
                  ) : popupType === 'forgot-password' ? (
                    <motion.div
                      key="forgot-password-panel"
                      variants={formVariants}
                      initial="initial"
                      animate="visible"
                      exit="exit"
                    >
                      {forgotPasswordSuccess ? (
                        <div className="py-2 text-center">
                          <div className="mb-4 p-4 bg-green-50 dark:bg-green-950/40 border border-green-200 dark:border-green-900/50 text-green-700 dark:text-green-400 rounded-2xl text-sm font-medium leading-relaxed">
                            {forgotPasswordSuccess}
                          </div>

                          {countdown > 0 ? (
                            <div className="mb-4 p-3 bg-amber-50 dark:bg-amber-950/30 border border-amber-200/60 dark:border-amber-800/40 rounded-xl flex items-center justify-center gap-2.5 text-amber-800 dark:text-amber-300 text-sm font-medium">
                              <FaClock className="text-amber-600 dark:text-amber-400 text-sm shrink-0 animate-pulse" />
                              <span>{t('auth:forgotPassword.resendCooldown', { seconds: countdown })}</span>
                            </div>
                          ) : (
                            <div className="mb-4 p-3 bg-gray-50 dark:bg-slate-800/60 rounded-xl border border-gray-100 dark:border-slate-700/60 text-center">
                              <p className="text-xs text-gray-600 dark:text-slate-400 mb-1.5">
                                {t('auth:forgotPassword.didNotReceive')}
                              </p>
                              <button
                                type="button"
                                onClick={() => handleForgotPassword()}
                                disabled={loading}
                                className="text-sm text-[#B12C2E] dark:text-[#E03E41] hover:underline font-bold inline-flex items-center justify-center gap-1.5 cursor-pointer disabled:opacity-50"
                              >
                                <span>{t('auth:forgotPassword.resendButton')}</span>
                              </button>
                            </div>
                          )}

                          <button
                            type="button"
                            onClick={() => {
                              setForgotPasswordSuccess(null);
                              switchMode('login');
                            }}
                            className="w-full bg-[#B12C2E] hover:bg-[#972426] dark:bg-[#B12C2E] dark:hover:bg-[#8F2325] text-white font-bold py-3 px-4 rounded-xl transition-all shadow-sm cursor-pointer"
                          >
                            {t('auth:forgotPassword.backToLogin')}
                          </button>
                        </div>
                      ) : (
                        <form onSubmit={handleForgotPassword} noValidate className="flex flex-col space-y-4">
                          <div className="mb-1">
                            <TextInput
                              id="forgot-email"
                              type="email"
                              label={t('auth:forgotPassword.emailLabel')}
                              value={form.email}
                              onChange={(e) => handleInputChange('email', e.target.value)}
                              placeholder={t('auth:forgotPassword.emailPlaceholder')}
                              error={fieldErrors.email}
                              icon={<FaEnvelope className="text-gray-400 dark:text-slate-500" />}
                            />
                          </div>

                          {countdown > 0 && (
                            <div className="p-3 bg-amber-50 dark:bg-amber-950/30 border border-amber-200/60 dark:border-amber-800/40 rounded-xl flex items-center justify-center gap-2.5 text-amber-800 dark:text-amber-300 text-xs font-medium">
                              <FaClock className="text-amber-600 dark:text-amber-400 text-sm shrink-0 animate-pulse" />
                              <span>{t('auth:forgotPassword.resendCooldown', { seconds: countdown })}</span>
                            </div>
                          )}

                          <button
                            type="submit"
                            disabled={loading || countdown > 0}
                            className={`cursor-pointer w-full bg-[#B12C2E] hover:bg-[#972426] dark:bg-[#B12C2E] dark:hover:bg-[#8F2325] text-white font-bold py-3 px-4 rounded-xl transition-all shadow-sm ${loading || countdown > 0 ? 'opacity-70 cursor-not-allowed' : ''}`}
                          >
                            {loading
                              ? t('auth:forgotPassword.sending', { defaultValue: t('common:loading') })
                              : countdown > 0
                              ? t('auth:forgotPassword.resendIn', { seconds: countdown })
                              : t('auth:forgotPassword.resetButton')
                            }
                          </button>

                          <div className="text-center text-sm pt-2">
                            <button
                              type="button"
                              onClick={() => switchMode('login')}
                              className="inline-flex items-center text-[#B12C2E] dark:text-[#E03E41] hover:underline font-bold cursor-pointer transition-colors"
                            >
                              {isRTL ? <FaArrowRight className="ml-1.5 text-xs" /> : <FaArrowLeft className="mr-1.5 text-xs" />}
                              {t('auth:forgotPassword.backToLogin')}
                            </button>
                          </div>
                        </form>
                      )}
                    </motion.div>
                  ) : popupType === 'reset-password' ? (
                    <motion.form
                      key="reset-password-panel"
                      onSubmit={handleResetPassword}
                      variants={formVariants}
                      initial="initial"
                      animate="visible"
                      exit="exit"
                      noValidate
                    >
                      <div className="mb-4">
                        <PasswordInput
                          id="reset-password"
                          label={t('auth:resetPassword.passwordLabel')}
                          placeholder={t('auth:resetPassword.passwordPlaceholder')}
                          value={form.password}
                          onChange={(e) => handleInputChange('password', e.target.value)}
                          error={fieldErrors.password}
                          icon={<FaLock className="text-gray-400 dark:text-slate-500" />}
                        />
                      </div>

                      <div className="mb-6">
                        <PasswordInput
                          id="reset-confirmPassword"
                          label={t('auth:resetPassword.confirmPasswordLabel')}
                          placeholder={t('auth:resetPassword.confirmPasswordPlaceholder')}
                          value={form.confirmPassword}
                          onChange={(e) => handleInputChange('confirmPassword', e.target.value)}
                          error={fieldErrors.confirmPassword || fieldErrors.password_confirmation}
                          icon={<FaLock className="text-gray-400 dark:text-slate-500" />}
                        />
                      </div>

                      <div className="flex flex-col space-y-3">
                        <button
                          type="submit"
                          disabled={loading || !resetToken || !resetEmail}
                          className={`cursor-pointer w-full bg-[#B12C2E] hover:bg-[#972426] dark:bg-[#B12C2E] dark:hover:bg-[#8F2325] text-white font-bold py-3 px-4 rounded-xl transition-all shadow-sm ${
                            loading || !resetToken || !resetEmail ? 'opacity-70 cursor-not-allowed' : ''
                          }`}
                        >
                          {loading ? t('common:loading') : t('auth:resetPassword.resetButton')}
                        </button>

                        <div className="text-center text-sm pt-2">
                          <button
                            type="button"
                            onClick={() => switchMode('login')}
                            className="inline-flex items-center text-[#B12C2E] dark:text-[#E03E41] hover:underline font-bold cursor-pointer transition-colors"
                          >
                            {isRTL ? <FaArrowRight className="ml-1.5 text-xs" /> : <FaArrowLeft className="mr-1.5 text-xs" />}
                            {t('auth:forgotPassword.backToLogin')}
                          </button>
                        </div>
                      </div>
                    </motion.form>
                  ) : (
                    <motion.form
                      key={popupType}
                      onSubmit={popupType === 'login' ? handleLogin : handleSignup}
                      variants={formVariants}
                      initial="initial"
                      animate="visible"
                      exit="exit"
                      noValidate
                    >
                      {popupType === 'register' && (
                        <div className="mb-4">
                          <TextInput
                            id="full_name"
                            type="text"
                            label={t('auth:signup.fullNameLabel')}
                            value={form.full_name}
                            onChange={(e) => handleInputChange('full_name', e.target.value)}
                            placeholder={t('auth:signup.fullNamePlaceholder')}
                            error={fieldErrors.full_name}
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
                          onChange={(e) => handleInputChange('email', e.target.value)}
                          placeholder={t('auth:signup.emailPlaceholder')}
                          error={fieldErrors.email}
                          icon={<FaEnvelope className="text-gray-400 dark:text-slate-500" />}
                        />
                      </div>

                      <div className="mb-4">
                        <PasswordInput
                          id="password"
                          label={t('auth:signup.passwordLabel')}
                          placeholder={t('auth:signup.passwordPlaceholder')}
                          value={form.password}
                          onChange={(e) => handleInputChange('password', e.target.value)}
                          error={fieldErrors.password}
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
                            onChange={(e) => handleInputChange('confirmPassword', e.target.value)}
                            error={fieldErrors.confirmPassword || fieldErrors.password_confirmation}
                            icon={<FaLock className="text-gray-400 dark:text-slate-500" />}
                          />
                        </div>
                      )}

                      {popupType === 'login' && (
                        <div className="flex items-center justify-between mb-4">
                          <div className="text-sm">
                            <button
                              type="button"
                              onClick={() => switchMode('forgot-password')}
                              className="text-[#B12C2E] dark:text-[#E03E41] hover:underline font-semibold cursor-pointer"
                            >
                              {t('auth:login.forgotPassword')}
                            </button>
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
                            ? (popupType === 'login' ? t('auth:login.loggingIn') : t('auth:signup.signingUp'))
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
                            onClick={() => switchMode(popupType === 'login' ? 'register' : 'login')}
                            className="text-[#B12C2E] dark:text-[#E03E41] hover:underline font-bold cursor-pointer ml-1"
                          >
                            {popupType === 'login' ? t('auth:login.signupLink') : t('auth:signup.loginLink')}
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
        title={
          successPopupTitle ||
          (popupType === 'login'
            ? t('auth:login.successTitle')
            : popupType === 'register'
            ? t('auth:signup.successTitle')
            : t('auth:resetPassword.successTitle'))
        }
        message={successMessage}
        type="success"
        primaryButtonText={t('common:buttons.continue', { defaultValue: 'متابعة' })}
        autoCloseTime={3000}
      />
    </>
  );
};

export default LoginPrompt;
