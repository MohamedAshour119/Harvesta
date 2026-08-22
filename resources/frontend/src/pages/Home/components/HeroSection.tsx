import { useEffect, useRef, useState } from "react";
import type { ISearchResult } from "../../../types";
import ApiClient from "../../../ApiClient";
import { useNavigate } from "react-router-dom";
import { useTranslation } from "react-i18next";
import { useDebounceRange } from "../../../hooks/UseDebounceRange";
import { motion } from "framer-motion";
import { HiArrowRight } from "react-icons/hi2";

const HeroSection = () => {
    const { t, i18n } = useTranslation(['home', 'common', 'shop']);
    const navigate = useNavigate();
    const isRTL = i18n.language === 'ar';
// ... (rest of the component state/logic stays same)
    const [searchQuery, setSearchQuery] = useState('');
    const [searchResults, setSearchResults] = useState<ISearchResult[]>([]);
    const [isDropdownOpen, setIsDropdownOpen] = useState(false);
    const [isLoading, setIsLoading] = useState(false);
    const [showNotFound, setShowNotFound] = useState(false)
    const [notFoundMessage, setNotFoundMessage] = useState('');
    const [isSemanticResult, setIsSemanticResult] = useState(false);

    const fileInputRef = useRef<HTMLInputElement>(null);
    const dropdownRef = useRef<HTMLDivElement>(null);
    const inputRef = useRef<HTMLInputElement>(null);
    const [isVisualSearch, setIsVisualSearch] = useState(false);
    const [visualImagePreview, setVisualImagePreview] = useState<string | null>(null);

    const debouncedSearchQuery = useDebounceRange(searchQuery, 500);

    // Handle input change
    const handleSearchChange = (e: React.ChangeEvent<HTMLInputElement>) => {
        setSearchQuery(e.target.value);
    };

    // Handle image upload
    const handleImageUpload = (e: React.ChangeEvent<HTMLInputElement>) => {
        const file = e.target.files?.[0];
        if (file) {
            setIsLoading(true);
            setSearchResults([]);
            setShowNotFound(false);
            setIsVisualSearch(true);
            setIsSemanticResult(false);
            setSearchQuery(''); // Clear text search when uploading image
            setIsDropdownOpen(true); // Open dropdown immediately to show loading state

            // Create preview
            const reader = new FileReader();
            reader.onloadend = () => {
                setVisualImagePreview(reader.result as string);
            };
            reader.readAsDataURL(file);

            // Reset the input value so the same file can be selected again
            if (e.target) e.target.value = '';

            ApiClient.search.visualSearch(file)
                .then(response => {
                    const data = response.data.data;
                    const visualResults = data?.visual_results || [];

                    if (visualResults.length > 0) {
                        setSearchResults(visualResults);
                    } else {
                        setShowNotFound(true);
                        setNotFoundMessage(t('home:hero.noVisualMatches', 'No visually similar products found'));
                    }
                })
                .catch(() => {
                    setShowNotFound(true);
                    setNotFoundMessage(t('home:hero.visualSearchError', 'Error processing image search'));
                })
                .finally(() => {
                    setIsLoading(false);
                });
        }
    };

    const triggerImageUpload = () => {
        fileInputRef.current?.click();
    };

    const clearVisualSearch = () => {
        setIsVisualSearch(false);
        setVisualImagePreview(null);
        setIsDropdownOpen(false);
        setSearchResults([]);
        if (fileInputRef.current) fileInputRef.current.value = '';
    };

    // Effect for debounced search API call — now uses semantic search
    useEffect(() => {
        if (debouncedSearchQuery.length > 0) {
            setIsLoading(true);
            setSearchResults([]);
            setShowNotFound(false);
            setIsSemanticResult(false);
            setIsVisualSearch(false);
            setVisualImagePreview(null);
            setIsDropdownOpen(true);

            ApiClient.search.semanticSearch(debouncedSearchQuery)
                .then(response => {
                    const data = response.data.data;
                    const literal = data?.literal_results || [];
                    const semantic = data?.semantic_results || [];

                    if (literal.length > 0) {
                        // Show literal results (exact keyword matches)
                        setSearchResults(literal);
                        setIsSemanticResult(false);
                    } else if (semantic.length > 0) {
                        // Fallback to AI semantic results
                        setSearchResults(semantic);
                        setIsSemanticResult(true);
                    } else {
                        setShowNotFound(true);
                        setNotFoundMessage(t('home:hero.noProductsFound', { query: debouncedSearchQuery }));
                    }
                })
                .catch(err => {
                    setShowNotFound(true);
                    setNotFoundMessage(err.response?.data?.message || t('home:hero.noProductsFound', { query: debouncedSearchQuery }));
                    setSearchResults([]);
                })
                .finally(() => setIsLoading(false));
        } else if (!isVisualSearch) {
            setIsDropdownOpen(false);
            setSearchResults([]);
            setShowNotFound(false);
            setIsSemanticResult(false);
        }
    }, [debouncedSearchQuery, t, isVisualSearch]);

    // Close dropdown when clicking outside
    useEffect(() => {
        const handleClickOutside = (event: MouseEvent) => {
            if (
                dropdownRef.current &&
                inputRef.current &&
                !dropdownRef.current.contains(event.target as Node) &&
                !inputRef.current.contains(event.target as Node)
            ) {
                setIsDropdownOpen(false);
            }
        };

        document.addEventListener('mousedown', handleClickOutside);
        return () => {
            document.removeEventListener('mousedown', handleClickOutside);
        };
    }, []);

    // Handle result click
    const handleResultClick = (result: any) => {
        setSearchQuery(result.name);
        setIsDropdownOpen(false);
        if (result.slug) {
            navigate(`/products/${result.slug}`);
        }
    };

    // Navigate to custom product request page
    const handleCustomProductRequest = () => {
        navigate('/custom-product-request');
    };

    const handleSubmit = (e: React.FormEvent<HTMLFormElement>) => {
        e.preventDefault();
        navigate(`/show-results?query=${encodeURIComponent(searchQuery)}`);
    };

    return (
        <div className="text-center mb-12 py-8 mt-12">
            <div className='flex justify-center'>
                <h1 className="text-2xl mobile-large:text-3xl tablet:text-4xl desktop-very-small:text-[40px] text-center max-w-[650px] leading-[34px] mobile-large:leading-[44px] tablet:leading-[54px] desktop-very-small:leading-[64px] font-[700] mb-4 font-display text-title_and_price dark:text-white">
                    {t('home:hero.title')}
                </h1>
            </div>
            <h2 className="text-xl mobile-large:text-2xl tablet:text-3xl font-medium mb-8 text-gray-700 dark:text-slate-300">
                {t('home:hero.subtitle')}
            </h2>
            <div className="max-w-xl mx-auto">
                <form onSubmit={handleSubmit} className="relative">
                    <input
                        ref={inputRef}
                        type="text"
                        value={searchQuery}
                        onChange={handleSearchChange}
                        placeholder={t('home:hero.searchPlaceholder')}
                        className="w-full p-3 ps-10 pe-12 bg-white dark:bg-[#151D2C] border border-gray-300 dark:border-slate-700 text-gray-900 dark:text-slate-100 placeholder:text-gray-400 dark:placeholder:text-slate-500 rounded-[16px] focus:outline-none focus:border-yellow dark:focus:border-primary shadow-sm"
                    />
                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        className="h-5 w-5 absolute start-3 top-1/2 transform -translate-y-1/2 text-gray-400 dark:text-slate-400"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                    >
                        <path
                            strokeLinecap="round"
                            strokeLinejoin="round"
                            strokeWidth={2}
                            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"
                        />
                    </svg>

                    {/* Camera Icon for Visual Search */}
                    <button
                        type="button"
                        onClick={triggerImageUpload}
                        className="absolute end-4 top-1/2 cursor-pointer transform -translate-y-1/2 text-gray-400 hover:text-yellow transition-colors"
                        title={t('home:hero.visualSearchTip', 'Search by image')}
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" className="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                    </button>

                    <input
                        type="file"
                        ref={fileInputRef}
                        onChange={handleImageUpload}
                        className="hidden"
                        accept="image/*"
                    />
                    {(isDropdownOpen && (searchResults?.length > 0 || showNotFound || isLoading)) && (
                        <div
                            ref={dropdownRef}
                            className={`absolute z-50 mt-2 w-full bg-white dark:bg-[#151D2C] rounded-xl shadow-2xl border border-gray-100 dark:border-slate-700/60 overflow-hidden transition-all duration-300 transform origin-top ${isDropdownOpen ? 'opacity-100 scale-100 translate-y-0' : 'opacity-0 scale-95 -translate-y-2 pointer-events-none'}`}
                        >
                            {/* Visual Search Preview Header */}
                            {isVisualSearch && visualImagePreview && (
                                <div className="px-3 py-2 bg-gray-50 dark:bg-[#1C2638] border-b border-gray-100 dark:border-slate-700 flex items-center gap-3">
                                    <img src={visualImagePreview} alt="Target" className="w-10 h-10 object-cover rounded-md border border-gray-200 dark:border-slate-700" />
                                    <div className="flex flex-col text-left">
                                        <span className="text-xs font-bold text-gray-700 dark:text-slate-200">{t('home:hero.visualResultsTitle', 'Visual Matches')}</span>
                                        <span className="text-[10px] text-gray-400 dark:text-slate-400">{t('home:hero.visualResultsSubtitle', 'Found similar products based on your image')}</span>
                                    </div>
                                    <button
                                        onClick={clearVisualSearch}
                                        className="ms-auto text-gray-400 hover:text-red-500 p-1 rounded"
                                    >
                                        <svg xmlns="http://www.w3.org/2000/svg" className="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                    </button>
                                </div>
                            )}

                            {/* AI Badge Header */}
                            {isSemanticResult && searchResults.length > 0 && (
                                <div className="px-3 py-1.5 bg-purple-50 dark:bg-purple-950/40 border-b border-purple-100 dark:border-purple-900/40 flex items-center gap-1.5">
                                    <span className="px-1.5 py-0.5 text-[10px] font-bold bg-purple-100 dark:bg-purple-900/60 text-purple-700 dark:text-purple-300 rounded-full">AI</span>
                                    <span className="text-xs text-purple-600 dark:text-purple-300">{t('common:search.semanticTitle', 'You might also be looking for')}</span>
                                </div>
                            )}
                            {isLoading ? (
                                isVisualSearch ? (
                                    <div className="p-8 flex flex-col items-center justify-center space-y-4">
                                        <div className="relative w-48 h-32 bg-gray-100 dark:bg-slate-800 rounded-xl border border-gray-200 dark:border-slate-700 overflow-hidden shadow-inner">
                                            <div className="absolute inset-0 bg-gradient-to-r from-transparent via-white/50 dark:via-white/10 to-transparent animate-shimmer -translate-x-full"></div>
                                            <div className="absolute w-full h-1 bg-gradient-to-r from-transparent via-[#B12C2E] to-transparent shadow-[0_0_15px_rgba(177,44,46,0.42)] animate-scan"></div>
                                            <div className="grid grid-cols-2 gap-2 p-3 mt-4">
                                                {[1, 2, 3, 4].map(i => (
                                                    <div key={i} className="h-10 bg-gray-200 dark:bg-slate-700 rounded-lg animate-pulse" style={{ animationDelay: `${i * 150}ms` }}></div>
                                                ))}
                                            </div>
                                        </div>

                                        <div className="text-center">
                                            <p className="text-sm font-bold text-gray-700 dark:text-slate-200 animate-pulse">
                                                {t('common:search.analyzingImage', 'Analyzing your image...')}
                                            </p>
                                            <p className="text-[10px] text-gray-400 dark:text-slate-500 mt-1">
                                                {t('common:search.matchingPatterns', 'Comparing visual patterns with thousands of products')}
                                            </p>
                                        </div>

                                        <div className="flex space-x-1.5">
                                            <div className="w-1.5 h-1.5 rounded-full bg-yellow animate-bounce [animation-delay:-0.3s]"></div>
                                            <div className="w-1.5 h-1.5 rounded-full bg-yellow animate-bounce [animation-delay:-0.15s]"></div>
                                            <div className="w-1.5 h-1.5 rounded-full bg-yellow animate-bounce"></div>
                                        </div>
                                    </div>
                                ) : (
                                    <div className="p-4 space-y-4">
                                        {[1, 2, 3].map(i => (
                                            <div key={i} className="flex items-center gap-3">
                                                <div className="w-8 h-8 bg-gray-100 dark:bg-slate-800 rounded-md animate-pulse"></div>
                                                <div className="flex-1 space-y-2">
                                                    <div className="h-3 bg-gray-100 dark:bg-slate-800 rounded animate-pulse w-3/4"></div>
                                                    <div className="h-2 bg-gray-50 dark:bg-slate-850 rounded animate-pulse w-1/2"></div>
                                                </div>
                                            </div>
                                        ))}
                                        <div className="flex items-center justify-center pt-2 border-t border-gray-50 dark:border-slate-800 gap-2">
                                            <div className="flex space-x-1">
                                                <div className="w-1 h-1 rounded-full bg-yellow animate-bounce [animation-delay:-0.3s]"></div>
                                                <div className="w-1 h-1 rounded-full bg-yellow animate-bounce [animation-delay:-0.15s]"></div>
                                                <div className="w-1 h-1 rounded-full bg-yellow animate-bounce"></div>
                                            </div>
                                            <span className="text-xs text-gray-400 dark:text-slate-400 font-medium">{t('common:search.searching', 'Searching...')}</span>
                                        </div>
                                    </div>
                                )
                            ) : !showNotFound ? (
                                <div className="max-h-[300px] overflow-y-auto">
                                    {searchResults.map((result) => (
                                        <div
                                            key={result.id}
                                            className="p-3 hover:bg-gray-50 dark:hover:bg-slate-800/80 cursor-pointer border-b border-gray-100 dark:border-slate-800/60 last:border-b-0 transition-colors"
                                            onClick={() => handleResultClick(result)}
                                        >
                                            <div className="flex justify-between items-center">
                                                <div className="text-left">
                                                    <p className="font-semibold text-gray-900 dark:text-slate-100 text-sm">
                                                        {result.name}
                                                        {(isSemanticResult || isVisualSearch) && (
                                                            <span className="ltr:ml-2 rtl:mr-2 px-1.5 py-0.5 text-[10px] font-bold bg-purple-100 dark:bg-purple-900/60 text-purple-700 dark:text-purple-300 rounded-full">
                                                                AI
                                                            </span>
                                                        )}
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            ) : (
                                <div>
                                    <div className="p-4 text-center text-gray-500 dark:text-slate-400 text-sm">
                                        {notFoundMessage}
                                    </div>

                                    {/* Custom Product Request Button */}
                                    <div className="p-4 pt-0 border-t border-gray-100 dark:border-slate-800">
                                        <button
                                            type="button"
                                            onClick={handleCustomProductRequest}
                                            className="bg-yellow hover:bg-yellow-400 cursor-pointer text-white font-semibold py-2.5 px-4 rounded-xl transition-colors duration-300 shadow-sm flex items-center justify-center w-full text-sm"
                                        >
                                            <svg
                                                xmlns="http://www.w3.org/2000/svg"
                                                className="h-5 w-5 mr-2"
                                                fill="none"
                                                viewBox="0 0 24 24"
                                                stroke="currentColor"
                                            >
                                                <path
                                                    strokeLinecap="round"
                                                    strokeLinejoin="round"
                                                    strokeWidth={2}
                                                    d="M12 6v6m0 0v6m0-6h6m-6 0H6"
                                                />
                                            </svg>
                                            {t('home:hero.requestCustomButton')}
                                        </button>
                                    </div>
                                </div>
                            )}
                        </div>
                    )}
                </form>

                {/* Premium Browse All Products Button */}
                <div className="flex justify-center mt-10">
                    <motion.button
                        whileHover={{ scale: 1.05 }}
                        whileTap={{ scale: 0.98 }}
                        initial={{ opacity: 0, y: 20 }}
                        animate={{ opacity: 1, y: 0 }}
                        transition={{ duration: 0.5, delay: 0.3 }}
                        onClick={() => navigate('/all-products')}
                        className="group relative flex items-center gap-3 px-12 py-4 bg-primary text-white font-bold rounded-[22px] transition-all duration-300 cursor-pointer overflow-hidden"
                    >
                        {/* Shimmer effect overlay */}
                        <div className="absolute inset-0 w-[200%] h-full bg-gradient-to-r from-transparent via-white/20 to-transparent -translate-x-[100%] group-hover:translate-x-[100%] transition-transform duration-1000 ease-in-out"></div>
                        
                        <span className="relative z-10 text-lg tracking-wide">{t('home:hero.browseAllProducts')}</span>
                        
                        <div className={`relative z-10 transition-transform duration-300 ${isRTL ? 'group-hover:-translate-x-1.5' : 'group-hover:translate-x-1.5'}`}>
                            <HiArrowRight className={`w-6 h-6 ${isRTL ? 'rotate-180' : ''}`} />
                        </div>
                    </motion.button>
                </div>
            </div>
        </div>
    )
}

export default HeroSection
