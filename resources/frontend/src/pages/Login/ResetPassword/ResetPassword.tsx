import React, { useState, useEffect } from 'react';
import { useNavigate, useSearchParams } from 'react-router-dom';
import MaxWidthWrapper from '../../../components/MaxWidthWrapper';
import PasswordInput from '../../../components/inputs/PasswordInput';
import ApiClient from '../../../ApiClient';
import Popup from '../../../components/Popup';
import { FaLock } from 'react-icons/fa';
import { useTranslation } from 'react-i18next';

const ResetPassword: React.FC = () => {
    const { t } = useTranslation(['auth', 'common']);
    const [password, setPassword] = useState<string>('');
    const [passwordConfirmation, setPasswordConfirmation] = useState<string>('');
    const [loading, setLoading] = useState<boolean>(false);
    const [error, setError] = useState<string | null>(null);
    const [showSuccessPopup, setShowSuccessPopup] = useState<boolean>(false);

    const [searchParams] = useSearchParams();
    const navigate = useNavigate();

    const token = searchParams.get('token');
    const email = searchParams.get('email');

    useEffect(() => {
        if (!token || !email) {
            setError(t('auth:resetPassword.errorMessages.invalidToken') || 'Invalid or missing token/email');
        }
    }, [token, email, t]);

    const handleSubmit = async (e: React.FormEvent<HTMLFormElement>) => {
        e.preventDefault();

        if (password !== passwordConfirmation) {
            setError(t('auth:signup.errorMessages.passwordMismatch'));
            return;
        }

        setLoading(true);
        setError(null);

        try {
            await ApiClient.auth.resetPassword({
                token,
                email,
                password,
                password_confirmation: passwordConfirmation,
            });

            setShowSuccessPopup(true);
        } catch (error: any) {
            console.error('Password reset failed:', error);
            setError(error.response?.data?.message || t('auth:resetPassword.errorMessages.failed'));
        } finally {
            setLoading(false);
        }
    };

    const handlePopupClose = () => {
        setShowSuccessPopup(false);
        navigate('/login');
    };

    return (
        <MaxWidthWrapper>
            <div className="flex min-w-full py-12 justify-center">
                <div className="w-full max-w-md bg-white dark:bg-[#151D2C] p-8 rounded-3xl shadow-sm border border-gray-100 dark:border-slate-800 transition-colors">
                    <div className="mb-8 text-center">
                        <h2 className="text-2xl font-bold text-gray-900 dark:text-slate-100">{t('auth:resetPassword.title')}</h2>
                        <p className="text-gray-500 dark:text-slate-400 mt-2 text-sm">{t('auth:resetPassword.subtitle') || t('auth:resetPassword.passwordPlaceholder')}</p>
                    </div>

                    {error && (
                        <div className="mb-4 p-3.5 bg-red-50 dark:bg-red-950/40 border border-red-200 dark:border-red-900/50 text-red-600 dark:text-red-400 rounded-xl text-sm">
                            {error}
                        </div>
                    )}

                    <form onSubmit={handleSubmit} className="flex flex-col gap-y-5">
                        <PasswordInput
                            id="password"
                            label={t('auth:resetPassword.passwordLabel')}
                            value={password}
                            onChange={(e: React.ChangeEvent<HTMLInputElement>) => setPassword(e.target.value)}
                            placeholder={t('auth:resetPassword.passwordPlaceholder')}
                            icon={<FaLock className="text-gray-400" />}
                            required
                        />

                        <PasswordInput
                            id="password_confirmation"
                            label={t('auth:resetPassword.confirmPasswordLabel')}
                            value={passwordConfirmation}
                            onChange={(e: React.ChangeEvent<HTMLInputElement>) => setPasswordConfirmation(e.target.value)}
                            placeholder={t('auth:resetPassword.confirmPasswordPlaceholder')}
                            icon={<FaLock className="text-gray-400" />}
                            required
                        />

                        <button
                            type="submit"
                            className="w-full bg-[#B12C2E] hover:bg-[#972426] dark:bg-[#B12C2E] dark:hover:bg-[#C83437] cursor-pointer text-white py-3 px-4 rounded-xl font-bold transition-all shadow-md active:scale-[0.98]"
                            disabled={loading || !token || !email}
                        >
                            {loading ? t('common:loading') : t('auth:resetPassword.resetButton')}
                        </button>
                    </form>
                </div>
            </div>

            <Popup
                isOpen={showSuccessPopup}
                onClose={handlePopupClose}
                title={t('auth:resetPassword.successTitle') || t('common:success')}
                message={t('auth:resetPassword.successMessage')}
                type="success"
                primaryButtonText={t('auth:login.loginButton')}
            />
        </MaxWidthWrapper>
    );
};

export default ResetPassword;
