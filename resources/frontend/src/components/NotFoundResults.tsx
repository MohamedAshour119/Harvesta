import React from 'react';
import { FaSearch } from 'react-icons/fa';
import { useTranslation } from 'react-i18next';

interface NotFoundResultsProps {
    searchQuery: string;
    className?: string;
}

export default function NotFoundResults({ searchQuery = "wireless headphones", className }: NotFoundResultsProps) {
    const { t } = useTranslation('common');

    return (
        <div className={`flex justify-center items-center py-12 px-4 ${className}`}>
            <div className="max-w-4xl mx-auto">
                {/* Header with search results info */}
                <div className="text-center mb-12">
                    <div className="relative inline-block mb-6">
                        <div className="w-24 h-24 bg-gradient-to-r from-pink-500 to-yellow-500 rounded-full flex items-center justify-center mx-auto mb-4 shadow-lg">
                            <FaSearch className="w-10 h-10 text-white" />
                        </div>
                        <div className="absolute -top-2 -right-2 w-8 h-8 bg-yellow-500 rounded-full flex items-center justify-center">
                            <span className="text-white font-bold text-sm">0</span>
                        </div>
                    </div>

                    <h1 className="text-3xl md:text-4xl font-bold text-gray-800 mb-4">
                        {t('results.notFoundTitle')}
                    </h1>

                    <p className="text-lg text-gray-600 mb-2">
                        {t('results.notFoundSubtitle')}
                    </p>

                    <div className="inline-flex items-center gap-2 bg-white px-4 py-2 rounded-full border border-gray-200 shadow-sm">
                        <FaSearch className="w-4 h-4 text-gray-500" />
                        <span className="font-semibold text-gray-800">"{searchQuery}"</span>
                    </div>
                </div>


                {/* Decorative elements */}
                <div className="absolute top-20 left-10 w-16 h-16 rounded-full blur-xl animate-pulse" />
                <div className="absolute bottom-20 right-10 w-24 h-24 rounded-full blur-xl animate-pulse" style={{ animationDelay: '1s' }} />
            </div>
        </div>
    );
}
