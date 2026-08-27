import React, { useState } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import MaxWidthWrapper from '../../components/MaxWidthWrapper';
import TextInput from '../../components/inputs/TextInput';
import PasswordInput from '../../components/inputs/PasswordInput';
import ApiClient, { setAccessToken } from '../../ApiClient';
import type { ISignUpErrors } from '../../types';
import Popup from '../../components/Popup';
import { useDispatch } from 'react-redux';
import { setIsAuth, setUser } from '../../store/slices/userSlice';
import { useTranslation } from 'react-i18next';
import { FaUser, FaEnvelope, FaLock } from 'react-icons/fa';

const SignUp: React.FC = () => {
  const { t } = useTranslation(['auth', 'common']);
  const [data, setData] = useState({
    full_name: '',
    email: '',
    password: '',
    password_confirmation: '',
  });
  const [loading, setLoading] = useState<boolean>(false);
  const [errors, setErrors] = useState<ISignUpErrors | null>(null);
  const [showSuccessPopup, setShowSuccessPopup] = useState<boolean>(false);
  const navigate = useNavigate();
  const dispatch = useDispatch();

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    handleSignUp();
  };

  const handleSignUp = async () => {
    setLoading(true);
    setErrors(null);

    const userData = {
      full_name: data.full_name,
      email: data.email,
      password: data.password,
      password_confirmation: data.password_confirmation,
    };

    try {
      const response = await ApiClient.auth.register(userData);
      const resData = response.data.data;

      setAccessToken(resData.access_token.plain_token, resData.access_token.expires_at);

      dispatch(setUser(resData.user));
      dispatch(setIsAuth(true));
      setShowSuccessPopup(true);

    } catch (error: any) {
      console.error('Sign up failed:', error);
      setErrors(error.response?.data?.errors || 'Registration failed. Please try again.');
    } finally {
      setLoading(false);
    }
  };

  const handlePopupClose = () => {
    setShowSuccessPopup(false);
    navigate('/');
  };

  return (
    <MaxWidthWrapper>
      <div className="flex min-w-full py-10">
        {/* Left side - Image */}
        <div className="hidden md:flex md:w-1/2 bg-gray-100 dark:bg-[#151D2C] rounded-[24px] overflow-hidden relative border border-gray-200 dark:border-slate-800">
          <div className="absolute inset-0 bg-cover bg-center h-[47rem]" style={{ backgroundImage: "url('/assets/images/login/login-img.png')" }}>
            <div className="absolute inset-0 bg-black/40 backdrop-blur-[2px]"></div>
            <div className="absolute bottom-10 left-10 right-10 text-white">
              <h2 className="text-2xl font-bold mb-2">{t('auth:signup.welcomeTo')}</h2>
              <h1 className="text-4xl font-extrabold text-[#E03E41]">
                {t('common:title_part_1')}
                <span className="text-white">{t('common:title_part_2')}</span>
              </h1>
            </div>
          </div>
        </div>

        {/* Right side - Sign Up Form */}
        <div className="w-full md:w-1/2 flex items-center justify-center tablet:p-8">
          <div className="w-full max-w-md bg-white dark:bg-[#151D2C] p-8 rounded-3xl border border-gray-100 dark:border-slate-800 shadow-sm transition-colors">
            {/* Mobile view welcome text */}
            <div className="md:hidden mb-6 text-center">
              <h2 className="text-xl font-semibold text-gray-700 dark:text-slate-300">{t('auth:signup.welcomeTo')}</h2>
              <h1 className="text-3xl font-extrabold text-[#B12C2E] dark:text-[#E03E41]">{t('common:title')}</h1>
            </div>

            {/* Title */}
            <div className="mb-8 text-center">
              <h2 className="text-2xl font-bold text-gray-900 dark:text-slate-100">{t('auth:signup.title')}</h2>
              <p className="text-gray-500 dark:text-slate-400 mt-1 text-sm">{t('auth:signup.subtitle')}</p>
            </div>

            {/* Form */}
            <form
              onSubmit={handleSubmit}
              className="space-y-3"
            >
              <div>
                <TextInput
                  id="full_name"
                  label={t('auth:signup.fullNameLabel')}
                  value={data.full_name}
                  onChange={(e) => setData({ ...data, full_name: e.target.value })}
                  placeholder={t('auth:signup.fullNamePlaceholder')}
                  icon={<FaUser className="text-gray-400" />}
                />
                {errors?.full_name && <span className="text-red-500 text-xs mt-1 block">{errors.full_name[0]}</span>}
              </div>

              <div>
                <TextInput
                  id="email"
                  label={t('auth:signup.emailLabel')}
                  type="email"
                  value={data.email}
                  onChange={(e) => setData({ ...data, email: e.target.value })}
                  placeholder={t('auth:signup.emailPlaceholder')}
                  icon={<FaEnvelope className="text-gray-400" />}
                />
                {errors?.email && <span className="text-red-500 text-xs mt-1 block">{errors.email[0]}</span>}
              </div>

              <div>
                <PasswordInput
                  id="password"
                  label={t('auth:signup.passwordLabel')}
                  value={data.password}
                  onChange={(e) => setData({ ...data, password: e.target.value })}
                  placeholder={t('auth:signup.passwordPlaceholder')}
                  icon={<FaLock className="text-gray-400" />}
                />
                {errors?.password && <span className="text-red-500 text-xs mt-1 block">{errors.password[0]}</span>}
              </div>

              <div>
                <PasswordInput
                  id="confirmPassword"
                  label={t('auth:signup.confirmPasswordLabel')}
                  value={data.password_confirmation}
                  onChange={(e) => setData({ ...data, password_confirmation: e.target.value })}
                  placeholder={t('auth:signup.confirmPasswordPlaceholder')}
                  icon={<FaLock className="text-gray-400" />}
                />
                {errors?.password_confirmation && <span className="text-red-500 text-xs mt-1 block">{errors.password_confirmation[0]}</span>}
              </div>

              <button
                type="submit"
                className="w-full bg-[#B12C2E] hover:bg-[#972426] dark:bg-[#B12C2E] dark:hover:bg-[#C83437] cursor-pointer text-white py-3 px-4 rounded-xl font-bold transition-all shadow-md active:scale-[0.98] mt-4"
                disabled={loading}
              >
                {loading ? t('auth:signup.signingUp') : t('auth:signup.signupButton')}
              </button>
            </form>

            <p className="mt-6 text-center text-sm text-gray-600 dark:text-slate-400">
              {t('auth:signup.haveAccount')}{' '}
              <Link to="/login" className="text-[#B12C2E] dark:text-[#E03E41] hover:underline font-bold">
                {t('auth:signup.loginLink')}
              </Link>
            </p>
          </div>
        </div>
      </div>

      {/* Success Popup */}
      <Popup
        isOpen={showSuccessPopup}
        onClose={handlePopupClose}
        title={t('auth:signup.successTitle')}
        message={t('auth:signup.successMessage', { name: data.full_name })}
        type="success"
        primaryButtonText={t('common:buttons.goToHome')}
        autoCloseTime={5000}
      />
    </MaxWidthWrapper>
  );
};

export default SignUp;
