import React, { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { useDispatch } from 'react-redux';
import MaxWidthWrapper from '../../../components/MaxWidthWrapper';
import TextInput from '../../../components/inputs/TextInput';
import ApiClient from '../../../ApiClient';
import { FaEnvelope, FaArrowLeft, FaArrowRight } from 'react-icons/fa';
import { useTranslation } from 'react-i18next';
import { openLoginModal } from '../../../store/slices/isAuthPopupOpenSlice';
import i18n from '../../../i18n';

const ForgotPassword: React.FC = () => {
    const { t } = useTranslation(['auth', 'common']);
    const [email, setEmail] = useState<string>('');
    const [loading, setLoading] = useState<boolean>(false);
    const [error, setError] = useState<string | null>(null);
    const [success, setSuccess] = useState<string | null>(null);

    const handleSubmit = async (e: React.FormEvent<HTMLFormElement>) => {
        e.preventDefault();
        setLoading(true);
        setError(null);
        setSuccess(null);

        try {
            const response = await ApiClient.auth.forgotPassword(email);
            setSuccess(response.data.message || t('auth:forgotPassword.successMessage'));
        } catch (error: any) {
            console.error('Forgot password request failed:', error);
            setError(error.response?.data?.message || t('auth:forgotPassword.errorMessages.emailNotFound'));
        } finally {
            setLoading(false);
        }
    };

    const navigate = useNavigate();
    const dispatch = useDispatch();

    const handleBackToLogin = () => {
        navigate('/');
        dispatch(openLoginModal());
    };

    const isRTL = i18n.language === 'ar';

    return (
        <MaxWidthWrapper>
            <div className="flex min-w-full py-12 justify-center">
                <div className="w-full max-w-md bg-white dark:bg-[#151D2C] p-8 rounded-3xl shadow-sm border border-gray-100 dark:border-slate-800 transition-colors">
                    <div className="mb-8 text-center">
                        <h2 className="text-2xl font-bold text-gray-900 dark:text-slate-100">{t('auth:forgotPassword.title')}</h2>
                        <p className="text-gray-500 dark:text-slate-400 mt-2 text-sm">{t('auth:forgotPassword.description') || t('auth:forgotPassword.emailPlaceholder')}</p>
                    </div>

                    {error && (
                        <div className="mb-4 p-3.5 bg-red-50 dark:bg-red-950/40 border border-red-200 dark:border-red-900/50 text-red-600 dark:text-red-400 rounded-xl text-sm">
                            {error}
                        </div>
                    )}

                    {success && (
                        <div className="mb-4 p-3.5 bg-green-50 dark:bg-green-950/40 border border-green-200 dark:border-green-900/50 text-green-600 dark:text-green-400 rounded-xl text-sm font-medium">
                            {success}
                        </div>
                    )}

                    <form onSubmit={handleSubmit} className="flex flex-col gap-y-5">
                        <TextInput
                            id="email"
                            label={t('auth:forgotPassword.emailLabel')}
                            type="email"
                            value={email}
                            onChange={(e: React.ChangeEvent<HTMLInputElement>) => setEmail(e.target.value)}
                            placeholder={t('auth:forgotPassword.emailPlaceholder')}
                            icon={<FaEnvelope className="text-gray-400" />}
                            required
                        />

                        <button
                            type="submit"
                            className="w-full bg-[#B12C2E] hover:bg-[#972426] dark:bg-[#B12C2E] dark:hover:bg-[#C83437] cursor-pointer text-white py-3 px-4 rounded-xl font-bold transition-all shadow-md active:scale-[0.98]"
                            disabled={loading}
                        >
                            {loading ? t('common:loading') : t('auth:forgotPassword.resetButton')}
                        </button>
                    </form>

                    <div className="mt-8 pt-6 border-t border-gray-100 dark:border-slate-800 text-center">
                        <button
                            type="button"
                            onClick={handleBackToLogin}
                            className="inline-flex items-center text-[#B12C2E] dark:text-[#E03E41] hover:underline font-bold text-sm transition-colors cursor-pointer"
                        >
                            {isRTL ? <FaArrowRight className="ml-2" /> : <FaArrowLeft className="mr-2" />}
                            {t('auth:forgotPassword.backToLogin')}
                        </button>
                    </div>
                </div>
            </div>
        </MaxWidthWrapper>
    );
};

export default ForgotPassword;
