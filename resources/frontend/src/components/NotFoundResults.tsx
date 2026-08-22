import React from 'react';
import { FaSearch } from 'react-icons/fa';
import { useTranslation } from 'react-i18next';

interface NotFoundResultsProps {
    searchQuery?: string;
    className?: string;
}

export default function NotFoundResults({ searchQuery, className }: NotFoundResultsProps) {
    const { t } = useTranslation('common');
    const hasSearchQuery = Boolean(searchQuery && searchQuery.trim().length > 0);

    return (
        <div className={`flex justify-center items-center py-12 px-4 ${className || ''}`}>
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

                    <h1 className="text-3xl md:text-4xl font-bold text-gray-800 dark:text-slate-100 mb-4">
                        {t('results.notFoundTitle')}
                    </h1>

                    <p className="text-lg text-gray-600 dark:text-slate-400 mb-4">
                        {hasSearchQuery 
                            ? t('results.notFoundSubtitle') 
                            : t('results.noFilterResultsSubtitle', { defaultValue: 'لم نتمكن من العثور على أي منتجات تطابق الفلاتر المحددة' })
                        }
                    </p>

                    {hasSearchQuery && (
                        <div className="inline-flex items-center gap-2 bg-white dark:bg-[#151D2C] px-4 py-2 rounded-full border border-gray-200 dark:border-slate-700 shadow-sm">
                            <FaSearch className="w-4 h-4 text-gray-500 dark:text-slate-400" />
                            <span className="font-semibold text-gray-800 dark:text-slate-200">"{searchQuery!.trim()}"</span>
                        </div>
                    )}
                </div>
            </div>
        </div>
    );
}
