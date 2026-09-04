import React, { useState } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import MaxWidthWrapper from '../../components/MaxWidthWrapper';
import TextInput from '../../components/inputs/TextInput';
import PasswordInput from '../../components/inputs/PasswordInput';
import ApiClient, { setAccessToken } from '../../ApiClient';
import Popup from '../../components/Popup';
import { useDispatch, useSelector } from 'react-redux';
import { setUser, setIsAuth } from '../../store/slices/userSlice';
import type { RootState } from '../../store/store';
import { clearPendingAction, selectPendingAction } from '../../store/slices/authRedirectSlice';
import { FaEnvelope, FaLock } from 'react-icons/fa';
import { useTranslation } from 'react-i18next';
import { setFavorites } from '../../store/slices/favoritesSlice';

const Login: React.FC = () => {
  const { t } = useTranslation(['auth', 'common', 'dashboard']);
  const [email, setEmail] = useState<string>('');
  const [password, setPassword] = useState<string>('');
  const [loading, setLoading] = useState<boolean>(false);
  const [error, setError] = useState<string | null>(null);
  const [showSuccessPopup, setShowSuccessPopup] = useState<boolean>(false);
  const [isAgentLogin, setIsAgentLogin] = useState<boolean>(false);
  const navigate = useNavigate();

  const user = useSelector((state: RootState) => state.auth.user);
  const pendingAction = useSelector(selectPendingAction);
  const dispatch = useDispatch();

  const handleSuccessfulLogin = () => {
    if (pendingAction && pendingAction.type === 'checkout') {
      navigate('/checkout');
      dispatch(clearPendingAction());
    } else if (pendingAction && pendingAction.type === 'wishlist') {
      dispatch(clearPendingAction());
    }
  };

  const handleSubmit = (e: React.FormEvent<HTMLFormElement>) => {
    e.preventDefault();
    handleLogin();
  };

  const handleLogin = async () => {
    setLoading(true);
    setError(null);

    try {
      const response = await ApiClient.auth.login(email, password);
      const data = response.data.data;

      setAccessToken(data.access_token.plain_token, data.access_token.expires_at);

      dispatch(setUser(data.user));
      dispatch(setIsAuth(true));
      dispatch(setFavorites(data.user.wishlist_items));

      const isAgent = !!data.user.is_agent;
      setIsAgentLogin(isAgent);
      setShowSuccessPopup(true);

      if (isAgent) {
        navigate('/dashboard');
        if (pendingAction) {
          dispatch(clearPendingAction());
        }
      } else if (pendingAction) {
        handleSuccessfulLogin();
      }

    } catch (error: any) {
      setError(error.response?.data?.message || t('auth:login.invalidCredentials'));
    } finally {
      setLoading(false);
    }
  };

  const handlePopupClose = () => {
    setShowSuccessPopup(false);
    if (isAgentLogin) {
      navigate('/dashboard');
    } else {
      navigate('/');
    }
  };

  return (
    <MaxWidthWrapper>
      <div className="flex min-w-full py-10">
        {/* Left side - Image */}
        <div className="hidden md:flex md:w-1/2 bg-gray-100 dark:bg-[#151D2C] rounded-[24px] overflow-hidden relative border border-gray-200 dark:border-slate-800">
          <div className="absolute inset-0 bg-cover bg-center h-[47rem]" style={{ backgroundImage: "url('/assets/images/login/login-img.png')" }}>
            <div className="absolute inset-0 bg-black/40 backdrop-blur-[2px]"></div>
            <div className="absolute bottom-10 left-10 right-10 text-white">
              <h2 className="text-2xl font-bold mb-2">{t('auth:login.welcomeTo')}</h2>
              <h1 className="text-4xl font-extrabold text-[#E03E41]">
                {t('common:title_part_1')}
                <span className="text-white">{t('common:title_part_2')}</span>
              </h1>
            </div>
          </div>
        </div>

        {/* Right side - Login Form */}
        <div className="w-full md:w-1/2 flex items-center justify-center tablet:p-8">
          <div className="w-full max-w-md bg-white dark:bg-[#151D2C] p-8 rounded-3xl border border-gray-100 dark:border-slate-800 shadow-sm transition-colors">
            {/* Mobile view welcome text */}
            <div className="md:hidden mb-6 text-center">
              <h2 className="text-xl font-semibold text-gray-700 dark:text-slate-300">{t('auth:login.welcomeTo')}</h2>
              <h1 className="text-3xl font-extrabold text-[#B12C2E] dark:text-[#E03E41]">{t('common:title')}</h1>
            </div>

            {/* Title */}
            <div className="mb-8 text-center">
              <h2 className="text-2xl font-bold text-gray-900 dark:text-slate-100">{t('auth:login.title')}</h2>
              <p className="text-gray-500 dark:text-slate-400 mt-1 text-sm">{t('auth:login.subtitle')}</p>
            </div>

            {/* Error message */}
            {error && (
              <div className="mb-4 p-3 bg-red-50 dark:bg-red-950/40 border border-red-200 dark:border-red-900/50 text-red-600 dark:text-red-400 rounded-xl text-sm">
                {error}
              </div>
            )}

            {/* Form */}
            <form
              onSubmit={handleSubmit}
              noValidate
              className="flex flex-col gap-y-4"
            >
              <TextInput
                id="email"
                label={t('auth:login.emailLabel')}
                type="email"
                value={email}
                onChange={(e) => setEmail(e.target.value)}
                placeholder={t('auth:login.emailPlaceholder')}
                icon={<FaEnvelope className="text-gray-400" />}
              />

              <PasswordInput
                id="password"
                label={t('auth:login.passwordLabel')}
                value={password}
                onChange={(e) => setPassword(e.target.value)}
                placeholder={t('auth:login.passwordPlaceholder')}
                icon={<FaLock className="text-gray-400" />}
              />

              <div className="flex justify-end -mt-1">
                <Link
                  to="/forgot-password"
                  className="text-xs font-semibold text-[#B12C2E] dark:text-[#E03E41] hover:underline"
                >
                  {t('auth:login.forgotPassword')}
                </Link>
              </div>

              <button
                type="submit"
                className="w-full bg-[#B12C2E] hover:bg-[#972426] dark:bg-[#B12C2E] dark:hover:bg-[#C83437] cursor-pointer text-white py-3 px-4 rounded-xl font-bold transition-all shadow-md active:scale-[0.98] mt-2"
                disabled={loading}
              >
                {loading ? t('auth:login.loggingIn') : t('auth:login.loginButton')}
              </button>
            </form>

            <p className="mt-6 text-center text-sm text-gray-600 dark:text-slate-400">
              {t('auth:login.noAccountText')}{' '}
              <Link to="/signup" className="text-[#B12C2E] dark:text-[#E03E41] hover:underline font-bold">
                {t('auth:login.signupLink')}
              </Link>
            </p>
          </div>
        </div>
      </div>

      {/* Success Popup */}
      <Popup
        isOpen={showSuccessPopup}
        onClose={handlePopupClose}
        title={t('auth:login.successTitle')}
        message={t('auth:login.successMessage', { name: user?.full_name || '' })}
        type="success"
        primaryButtonText={isAgentLogin ? t('dashboard:sidebar.overview') : t('common:buttons.goToHome')}
        autoCloseTime={4000}
      />
    </MaxWidthWrapper>
  );
};

export default Login;
