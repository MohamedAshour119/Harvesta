
import { FaSearch, FaHome } from 'react-icons/fa';
import { HiPlus } from 'react-icons/hi';
import { useTranslation } from 'react-i18next';
import { useNavigate } from 'react-router-dom';

interface NotFoundResultsProps {
    searchQuery: string;
}

export default function NotFoundResults({ searchQuery }: NotFoundResultsProps) {
    const { t } = useTranslation(['common', 'home']);
    const navigate = useNavigate();

    const handleBackHome = () => {
        navigate('/');
    };

    const handleRequestCustom = () => {
        navigate('/custom-product-request');
    };

    return (
        <div className="w-full flex justify-center items-center py-16 px-4 bg-gradient-to-b from-transparent to-gray-50/50">
            <div className="max-w-2xl w-full text-center">
                {/* Visual Icon Section */}
                <div className="relative inline-block mb-10">
                    <div className="w-32 h-32 bg-gradient-to-br from-yellow to-orange-400 rounded-3xl flex items-center justify-center mx-auto shadow-2xl rotate-3 hover:rotate-0 transition-transform duration-500">
                        <FaSearch className="w-12 h-12 text-white drop-shadow-md" />
                    </div>
                    {/* Decorative bits */}
                    <div className="absolute -top-4 -right-4 w-12 h-12 bg-purple-100 rounded-full blur-2xl opacity-60 animate-pulse" />
                    <div className="absolute -bottom-4 -left-4 w-16 h-16 bg-yellow/20 rounded-full blur-2xl opacity-60 animate-pulse delay-700" />
                </div>

                {/* Main Text Content */}
                <h1 className="text-3xl md:text-4xl font-extrabold text-title_and_price mb-4 tracking-tight">
                    {t('common:results.notFoundTitle')}
                </h1>

                <p className="text-lg text-gray-500 mb-6 max-w-md mx-auto leading-relaxed">
                    {t('common:results.notFoundSubtitle')}
                    <span className="block font-bold text-yellow mt-2 text-2xl truncate">"{searchQuery}"</span>
                </p>

                <p className="text-gray-400 mb-10 max-w-md mx-auto italic">
                    {t('common:results.tryAgain')}
                </p>

                {/* Search Tips Grid */}
                <div className="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-12">
                    <div className="bg-white p-4 rounded-2xl shadow-sm border border-gray-100 hover:shadow-md transition-shadow">
                        <p className="text-sm font-medium text-gray-600">{t('common:results.tipGeneral')}</p>
                    </div>
                    <div className="bg-white p-4 rounded-2xl shadow-sm border border-gray-100 hover:shadow-md transition-shadow">
                        <p className="text-sm font-medium text-gray-600">{t('common:results.tipSpelling')}</p>
                    </div>
                    <div className="bg-white p-4 rounded-2xl shadow-sm border border-gray-100 hover:shadow-md transition-shadow">
                        <p className="text-sm font-medium text-gray-600">{t('common:results.tipFilters')}</p>
                    </div>
                </div>

                {/* Action Buttons */}
                <div className="flex flex-col sm:flex-row gap-4 justify-center items-center">
                    <button
                        onClick={handleBackHome}
                        className="flex items-center cursor-pointer gap-2 px-8 py-3.5 bg-gray-900 hover:bg-black text-white rounded-2xl font-bold transition-all transform hover:-translate-y-1 shadow-lg hover:shadow-xl w-full sm:w-auto"
                    >
                        <FaHome className="w-4 h-4" />
                        {t('common:buttons.goToHome')}
                    </button>

                    <button
                        onClick={handleRequestCustom}
                        className="flex items-center cursor-pointer gap-2 px-8 py-3.5 bg-yellow hover:bg-yellow-400 text-white rounded-2xl font-bold transition-all transform hover:-translate-y-1 shadow-lg hover:shadow-xl w-full sm:w-auto"
                    >
                        <HiPlus className="w-5 h-5" />
                        {t('common:results.requestButton')}
                    </button>
                </div>

                {/* Custom Sourcing Message */}
                <div className="mt-16 pt-8 border-t border-gray-100">
                    <h3 className="text-lg font-bold text-gray-800 mb-2">
                        {t('common:results.requestTitle')}
                    </h3>
                    <p className="text-gray-500 text-sm max-w-md mx-auto">
                        {t('common:results.requestSubtitle')}
                    </p>
                </div>
            </div>
        </div>
    );
}