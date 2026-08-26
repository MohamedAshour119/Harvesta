import React, { useState, useEffect, useRef, useCallback } from 'react';
import { useTranslation } from 'react-i18next';
import { useNavigate } from 'react-router-dom';
import ApiClient from '../../../ApiClient';

interface VisualSearchModalProps {
    show: boolean;
    onClose: () => void;
}

interface ProductResult {
    id: number;
    name: string;
    slug?: string;
    description?: string;
    industrial_category?: {
        id: number;
        name: string;
    };
    main_image?: string;
    images?: Array<{ url: string }>;
    variants?: Array<{
        price: number;
        discount?: number;
    }>;
}

const VisualSearchModal: React.FC<VisualSearchModalProps> = ({ show, onClose }) => {
    const { t, i18n } = useTranslation(['home', 'common']);
    const isRTL = i18n.language === 'ar';
    const navigate = useNavigate();

    const [isDragging, setIsDragging] = useState(false);
    const [selectedImagePreview, setSelectedImagePreview] = useState<string | null>(null);
    const [isLoading, setIsLoading] = useState(false);
    const [results, setResults] = useState<ProductResult[]>([]);
    const [hasSearched, setHasSearched] = useState(false);
    const [errorMessage, setErrorMessage] = useState<string | null>(null);

    const fileInputRef = useRef<HTMLInputElement>(null);
    const modalRef = useRef<HTMLDivElement>(null);
    const dragCounter = useRef(0);

    const resetState = useCallback(() => {
        setSelectedImagePreview(null);
        setResults([]);
        setHasSearched(false);
        setIsLoading(false);
        setErrorMessage(null);
        setIsDragging(false);
        dragCounter.current = 0;
        if (fileInputRef.current) {
            fileInputRef.current.value = '';
        }
    }, []);

    const handleClose = useCallback(() => {
        onClose();
        setTimeout(resetState, 300);
    }, [onClose, resetState]);

    const performVisualSearch = useCallback(async (file: File) => {
        setIsLoading(true);
        setErrorMessage(null);
        setHasSearched(true);
        setResults([]);

        // Create local preview
        const reader = new FileReader();
        reader.onload = (e) => {
            setSelectedImagePreview(e.target?.result as string);
        };
        reader.readAsDataURL(file);

        try {
            const response = await ApiClient.search.visualSearch(file);
            const data = response.data?.data;
            const visualResults = data?.visual_results || [];
            setResults(visualResults);
        } catch (error) {
            console.error('Visual search error:', error);
            setErrorMessage(t('home:visualSearchModal.visualSearchError', 'Error processing image search. Please try another image.'));
        } finally {
            setIsLoading(false);
        }
    }, [t]);

    const handleFileSelect = (e: React.ChangeEvent<HTMLInputElement>) => {
        const file = e.target.files?.[0];
        if (file) {
            if (!file.type.startsWith('image/')) {
                setErrorMessage(t('home:visualSearchModal.invalidImageFormat', 'Please select a valid image file'));
                return;
            }
            performVisualSearch(file);
        }
    };

    const handleDragEnter = (e: React.DragEvent) => {
        e.preventDefault();
        e.stopPropagation();
        dragCounter.current += 1;
        if (e.dataTransfer.items && e.dataTransfer.items.length > 0) {
            setIsDragging(true);
        }
    };

    const handleDragLeave = (e: React.DragEvent) => {
        e.preventDefault();
        e.stopPropagation();
        dragCounter.current -= 1;
        if (dragCounter.current <= 0) {
            setIsDragging(false);
            dragCounter.current = 0;
        }
    };

    const handleDragOver = (e: React.DragEvent) => {
        e.preventDefault();
        e.stopPropagation();
    };

    const handleDrop = (e: React.DragEvent) => {
        e.preventDefault();
        e.stopPropagation();
        setIsDragging(false);
        dragCounter.current = 0;

        const files = e.dataTransfer.files;
        if (files && files.length > 0) {
            const file = files[0];
            if (file.type.startsWith('image/')) {
                performVisualSearch(file);
            } else {
                setErrorMessage(t('home:visualSearchModal.invalidImageFormat', 'Please select a valid image file'));
            }
        }
    };

    const handlePaste = useCallback((e: ClipboardEvent) => {
        if (!show) return;
        const items = e.clipboardData?.items;
        if (!items) return;

        for (let i = 0; i < items.length; i++) {
            if (items[i].type.indexOf('image') !== -1) {
                const file = items[i].getAsFile();
                if (file) {
                    performVisualSearch(file);
                    break;
                }
            }
        }
    }, [show, performVisualSearch]);

    // Handle Escape key & Paste
    useEffect(() => {
        const handleKeyDown = (e: KeyboardEvent) => {
            if (e.key === 'Escape' && show) {
                handleClose();
            }
        };

        if (show) {
            window.addEventListener('keydown', handleKeyDown);
            window.addEventListener('paste', handlePaste);
            document.body.style.overflow = 'hidden';
        }

        return () => {
            window.removeEventListener('keydown', handleKeyDown);
            window.removeEventListener('paste', handlePaste);
            document.body.style.overflow = 'unset';
        };
    }, [show, handleClose, handlePaste]);

    const handleProductClick = (product: ProductResult) => {
        handleClose();
        if (product.slug) {
            navigate(`/products/${product.slug}`);
        } else {
            navigate(`/products/${product.id}`);
        }
    };

    return (
        <div
            className={`fixed inset-0 z-50 flex items-center justify-center p-4 sm:p-6 transition-all duration-300 ${show ? 'opacity-100 visible' : 'opacity-0 invisible pointer-events-none'
                }`}
        >
            {/* Backdrop */}
            <div
                className={`fixed inset-0 bg-black/70 backdrop-blur-md transition-opacity duration-300 ${show ? 'opacity-100' : 'opacity-0'
                    }`}
                onClick={handleClose}
            />

            {/* Modal Box */}
            <div
                ref={modalRef}
                className={`relative w-full max-w-2xl bg-white dark:bg-[#151D2C] border border-gray-200 dark:border-slate-700/80 rounded-3xl shadow-2xl overflow-hidden flex flex-col max-h-[90vh] transform transition-all duration-300 ${show ? 'scale-100 translate-y-0 opacity-100' : 'scale-95 translate-y-4 opacity-0'
                    }`}
                onDragEnter={handleDragEnter}
                onDragOver={handleDragOver}
                onDragLeave={handleDragLeave}
                onDrop={handleDrop}
            >
                {/* Modal Header */}
                <div className="relative px-6 py-5 border-b border-gray-100 dark:border-slate-800 flex items-center justify-between bg-gradient-to-r from-gray-50/50 to-white dark:from-[#192335] dark:to-[#151D2C]">
                    <div className="flex items-center gap-3">
                        <div className="w-10 h-10 rounded-2xl bg-gradient-to-br from-[#B12C2E] to-[#E03E41] flex items-center justify-center text-white shadow-md shadow-red-500/20">
                            <svg xmlns="http://www.w3.org/2000/svg" className="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                        </div>
                        <div>
                            <h3 className="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2">
                                <span>{t('home:visualSearchModal.title', 'البحث البصري الذكي')}</span>
                                <span className="text-[10px] font-extrabold uppercase px-2 py-0.5 rounded-full bg-purple-100 dark:bg-purple-900/60 text-purple-700 dark:text-purple-300 border border-purple-200 dark:border-purple-800/50">
                                    {t('home:visualSearchModal.aiVisionBadge', 'رؤية بالذكاء الاصطناعي')}
                                </span>
                            </h3>
                            <p className="text-xs text-gray-500 dark:text-slate-400">
                                {t('home:visualSearchModal.subtitle', 'ابحث عن أي منتج بدقة متناهية بمجرد رفع صورته')}
                            </p>
                        </div>
                    </div>

                    <button
                        type="button"
                        onClick={handleClose}
                        className="w-9 h-9 flex items-center justify-center rounded-full cursor-pointer text-gray-400 hover:text-gray-700 dark:text-slate-400 dark:hover:text-white hover:bg-gray-100 dark:hover:bg-slate-800 transition-colors"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" className="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                {/* Modal Body */}
                <div className="p-6 overflow-y-auto space-y-6 flex-1">
                    {/* Hidden File Input */}
                    <input
                        ref={fileInputRef}
                        type="file"
                        accept="image/*"
                        onChange={handleFileSelect}
                        className="hidden"
                    />

                    {/* Upload / Drop Area */}
                    {!selectedImagePreview ? (
                        <div
                            onClick={() => fileInputRef.current?.click()}
                            className={`relative border-2 border-dashed rounded-3xl p-8 text-center cursor-pointer transition-all duration-300 flex flex-col items-center justify-center gap-4 ${isDragging
                                ? 'border-[#B12C2E] dark:border-[#E03E41] bg-red-50/70 dark:bg-red-950/20 scale-[1.01] shadow-lg shadow-red-500/10'
                                : 'border-gray-200 dark:border-slate-700/80 bg-gray-50/50 dark:bg-[#1C2638]/60 hover:bg-gray-100/60 dark:hover:bg-[#1C2638] hover:border-[#B12C2E]/40 dark:hover:border-[#E03E41]/40'
                                }`}
                        >
                            <div className={`w-16 h-16 rounded-2xl flex items-center justify-center transition-transform duration-300 ${isDragging
                                ? 'bg-[#B12C2E] text-white scale-110 shadow-lg'
                                : 'bg-red-50 dark:bg-red-950/40 text-[#B12C2E] dark:text-[#E03E41]'
                                }`}>
                                <svg xmlns="http://www.w3.org/2000/svg" className="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.75} d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                            </div>

                            <div className="space-y-1 max-w-sm">
                                <h4 className="text-base font-bold text-gray-900 dark:text-slate-100">
                                    {isDragging
                                        ? t('home:visualSearchModal.dropNow', 'أفلت الصورة هنا لبدء البحث الفوري')
                                        : t('home:visualSearchModal.dropPrompt', 'اسحب وأفلت الصورة هنا أو اضغط للاختيار')}
                                </h4>
                                <p className="text-xs text-gray-500 dark:text-slate-400">
                                    {t('home:visualSearchModal.supportedFormats', 'يدعم JPG, PNG, WEBP — يمكنك أيضًا اللصق مباشرة عبر (Ctrl + V)')}
                                </p>
                            </div>

                            <button
                                type="button"
                                className="px-5 py-2.5 rounded-xl bg-[#B12C2E] hover:bg-[#972426] text-white font-semibold text-xs transition-all shadow-md hover:shadow-lg flex items-center gap-2 pointer-events-none"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" className="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 4v16m8-8H4" />
                                </svg>
                                <span>{t('home:visualSearchModal.chooseImage', 'اختيار صورة من جهازك')}</span>
                            </button>
                        </div>
                    ) : (
                        /* Selected Image Active Bar */
                        <div className="bg-gray-50 dark:bg-[#1C2638] rounded-2xl p-4 border border-gray-200 dark:border-slate-700/80 flex items-center justify-between gap-4">
                            <div className="flex items-center gap-3.5">
                                <div className="relative w-14 h-14 rounded-xl overflow-hidden border-2 border-white dark:border-slate-700 shadow-sm bg-white dark:bg-slate-900 shrink-0">
                                    <img
                                        src={selectedImagePreview}
                                        alt="Uploaded query"
                                        className="w-full h-full object-cover"
                                    />
                                    {isLoading && (
                                        <div className="absolute inset-0 bg-red-600/20 backdrop-blur-[1px] flex items-center justify-center">
                                            <div className="w-full h-0.5 bg-red-500 shadow-[0_0_8px_#ef4444] animate-pulse" />
                                        </div>
                                    )}
                                </div>
                                <div className="space-y-0.5">
                                    <div className="flex items-center gap-2">
                                        <span className="inline-block w-2 h-2 rounded-full bg-green-500" />
                                        <span className="text-xs font-bold text-gray-800 dark:text-slate-200">
                                            {t('home:visualSearchModal.activeQuery', 'صورة البحث الحالية')}
                                        </span>
                                    </div>
                                    <p className="text-[11px] text-gray-500 dark:text-slate-400">
                                        {isLoading
                                            ? t('home:visualSearchModal.analyzingFeatures', 'جاري تحليل الأنماط البصرية ومطابقة المنتجات...')
                                            : t('home:visualSearchModal.searchCompleted', 'تمت المطابقة بواسطة الذكاء الاصطناعي')}
                                    </p>
                                </div>
                            </div>

                            <button
                                type="button"
                                onClick={() => fileInputRef.current?.click()}
                                className="px-3.5 py-1.5 cursor-pointer rounded-xl border border-gray-300 dark:border-slate-600 hover:bg-gray-100 dark:hover:bg-slate-700 text-gray-700 dark:text-slate-200 text-xs font-semibold transition-colors flex items-center gap-1.5 shrink-0"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" className="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                                </svg>
                                <span>{t('home:visualSearchModal.changeImage', 'تغيير الصورة')}</span>
                            </button>
                        </div>
                    )}

                    {/* Error Message */}
                    {errorMessage && (
                        <div className="p-4 rounded-2xl bg-red-50 dark:bg-red-950/30 border border-red-200 dark:border-red-800/50 text-red-700 dark:text-red-300 text-xs flex items-center gap-2.5">
                            <svg xmlns="http://www.w3.org/2000/svg" className="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <span>{errorMessage}</span>
                        </div>
                    )}

                    {/* Results Section */}
                    {isLoading ? (
                        /* Scanning Skeleton */
                        <div className="space-y-3">
                            <div className="flex items-center justify-between pb-1">
                                <div className="h-4 w-32 bg-gray-200 dark:bg-slate-800 rounded animate-pulse" />
                                <div className="h-4 w-16 bg-gray-200 dark:bg-slate-800 rounded animate-pulse" />
                            </div>
                            {[1, 2, 3].map((i) => (
                                <div
                                    key={i}
                                    className="p-3.5 rounded-2xl border border-gray-100 dark:border-slate-800 bg-gray-50/50 dark:bg-slate-800/40 flex items-center gap-4 animate-pulse"
                                >
                                    <div className="w-16 h-16 rounded-xl bg-gray-200 dark:bg-slate-700 shrink-0" />
                                    <div className="flex-1 space-y-2">
                                        <div className="h-4 bg-gray-200 dark:bg-slate-700 rounded w-3/5" />
                                        <div className="h-3 bg-gray-200 dark:bg-slate-700 rounded w-2/5" />
                                    </div>
                                    <div className="h-8 w-20 bg-gray-200 dark:bg-slate-700 rounded-lg shrink-0" />
                                </div>
                            ))}
                        </div>
                    ) : hasSearched && results.length > 0 ? (
                        /* Matched Products List */
                        <div className="space-y-3">
                            <div className="flex items-center justify-between pb-1">
                                <h4 className="text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-slate-400">
                                    {t('home:visualSearchModal.matchedProducts', 'المنتجات المتطابقة')} ({results.length})
                                </h4>
                                <span className="text-[11px] text-purple-600 dark:text-purple-400 font-semibold flex items-center gap-1">
                                    <span className="w-1.5 h-1.5 rounded-full bg-purple-500 animate-ping" />
                                    {t('home:visualSearchModal.multimodalVerified', 'مطابقة دلالية وبصرية موثوقة')}
                                </span>
                            </div>

                            <div className="space-y-2.5">
                                {results.map((product) => {
                                    const imgUrl = 
                                        (typeof product.images === 'object' && !Array.isArray(product.images) && product.images?.main_image)
                                        || product.cheapestVariant?.main_image
                                        || product.main_image
                                        || (Array.isArray(product.images) && product.images[0]?.url)
                                        || null;

                                    const categoryName = product.category?.name || product.industrial_category?.name;
                                    const price = product.cheapestVariant?.price_after_discount ?? product.cheapestVariant?.unit_price_usd ?? product.variants?.[0]?.price;

                                    return (
                                        <div
                                            key={product.id}
                                            className="group p-3.5 rounded-2xl border border-gray-100 dark:border-slate-800/80 bg-white dark:bg-[#1A2436] hover:border-gray-300 dark:hover:border-slate-600 transition-all duration-200 flex items-center justify-between gap-4 shadow-sm hover:shadow-md"
                                        >
                                            <div className="flex items-center gap-3.5 min-w-0">
                                                {/* Thumbnail */}
                                                <div className="w-16 h-16 rounded-xl bg-gray-50 dark:bg-slate-800 border border-gray-100 dark:border-slate-700 overflow-hidden shrink-0 flex items-center justify-center">
                                                    {imgUrl ? (
                                                        <img
                                                            src={imgUrl}
                                                            alt={product.name}
                                                            className="w-full h-full object-contain p-1"
                                                        />
                                                    ) : (
                                                        <svg xmlns="http://www.w3.org/2000/svg" className="w-6 h-6 text-gray-300 dark:text-slate-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                                        </svg>
                                                    )}
                                                </div>

                                                {/* Info */}
                                                <div className="space-y-1 min-w-0">
                                                    <div className="flex items-center gap-2 flex-wrap">
                                                        <h5 className="text-sm font-bold text-gray-900 dark:text-white truncate">
                                                            <bdi>{product.name}</bdi>
                                                        </h5>
                                                        <span dir="ltr" className="inline-flex items-center px-1.5 py-0.5 rounded-full text-[10px] font-extrabold bg-purple-100 dark:bg-purple-900/60 text-purple-700 dark:text-purple-300">
                                                            AI
                                                        </span>
                                                    </div>

                                                    <div className="flex items-center gap-2 text-xs text-gray-500 dark:text-slate-400">
                                                        {categoryName && (
                                                            <span className="px-2 py-0.5 rounded-md bg-gray-100 dark:bg-slate-800 text-gray-600 dark:text-slate-300 text-[10px]">
                                                                {categoryName}
                                                            </span>
                                                        )}
                                                        {price !== undefined && (
                                                            <span className="font-bold text-[#B12C2E] dark:text-[#E03E41]">
                                                                {price} {t('common:currency', 'ج.م')}
                                                            </span>
                                                        )}
                                                    </div>
                                                </div>
                                            </div>

                                            {/* Action Button: Dedicated View Product Link */}
                                            <div className="flex items-center gap-2 shrink-0">
                                                <button
                                                    type="button"
                                                    onClick={() => handleProductClick(product)}
                                                    className="px-4 py-2 cursor-pointer rounded-xl bg-gray-100 hover:bg-[#B12C2E] hover:text-white dark:bg-slate-800 dark:hover:bg-[#E03E41] dark:hover:text-white text-gray-800 dark:text-slate-200 text-xs font-semibold transition-all shadow-sm hover:shadow-md flex items-center gap-1.5"
                                                >
                                                    <span>{t('home:visualSearchModal.viewProduct', 'عرض المنتج')}</span>
                                                    <svg xmlns="http://www.w3.org/2000/svg" className={`w-3.5 h-3.5 transform ${isRTL ? 'rotate-180' : ''}`} fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 5l7 7-7 7" />
                                                    </svg>
                                                </button>
                                            </div>
                                        </div>
                                    );
                                })}
                            </div>
                        </div>
                    ) : hasSearched && results.length === 0 ? (
                        /* Empty State */
                        <div className="p-8 text-center space-y-3 rounded-2xl bg-gray-50/50 dark:bg-slate-800/30 border border-gray-100 dark:border-slate-800">
                            <div className="w-12 h-12 rounded-full bg-gray-100 dark:bg-slate-800 text-gray-400 dark:text-slate-500 mx-auto flex items-center justify-center">
                                <svg xmlns="http://www.w3.org/2000/svg" className="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <h4 className="text-sm font-bold text-gray-800 dark:text-slate-200">
                                {t('home:visualSearchModal.noMatchesTitle', 'لم يتم العثور على منتجات مطابقة لهذه الصورة')}
                            </h4>
                            <p className="text-xs text-gray-500 dark:text-slate-400 max-w-sm mx-auto">
                                {t('home:visualSearchModal.noMatchesDesc', 'حاول استخدام صورة أكثر وضوحاً للمنتج بزاوية وإضاءة أفضل، أو جرب البحث بالاسم')}
                            </p>
                            <button
                                type="button"
                                onClick={() => fileInputRef.current?.click()}
                                className="mt-2 px-4 py-2 cursor-pointer rounded-xl bg-gray-200 dark:bg-slate-700 hover:bg-gray-300 dark:hover:bg-slate-600 text-gray-800 dark:text-slate-200 text-xs font-semibold transition-colors"
                            >
                                {t('home:visualSearchModal.tryAnotherImage', 'تجربة صورة أخرى')}
                            </button>
                        </div>
                    ) : null}
                </div>

                {/* Modal Footer */}
                <div className="px-6 py-3.5 bg-gray-50/80 dark:bg-[#111722] border-t border-gray-100 dark:border-slate-800 flex items-center justify-center text-xs text-gray-500 dark:text-slate-400">
                    <div className="flex items-center gap-1.5">
                        <svg xmlns="http://www.w3.org/2000/svg" className="w-4 h-4 text-purple-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M13 10V3L4 14h7v7l9-11h-7z" />
                        </svg>
                        <span>{t('home:visualSearchModal.poweredByAi', 'مدعوم بنموذج الرؤية الحاسوبية والذكاء الاصطناعي')}</span>
                    </div>
                </div>
            </div>
        </div>
    );
};

export default VisualSearchModal;
