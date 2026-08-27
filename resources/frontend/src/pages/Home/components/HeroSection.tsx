import { useEffect, useRef, useState } from "react";
import type { ISearchResult } from "../../../types";
import ApiClient from "../../../ApiClient";
import { useNavigate } from "react-router-dom";
import { useTranslation } from "react-i18next";
import { useDebounceRange } from "../../../hooks/UseDebounceRange";
import { motion } from "framer-motion";
import { HiArrowRight } from "react-icons/hi2";
import VisualSearchModal from "./VisualSearchModal";

const HeroSection = () => {
    const { t, i18n } = useTranslation(['home', 'common', 'shop']);
    const navigate = useNavigate();
    const isRTL = i18n.language === 'ar';

    const [searchQuery, setSearchQuery] = useState('');
    const [searchResults, setSearchResults] = useState<ISearchResult[]>([]);
    const [isDropdownOpen, setIsDropdownOpen] = useState(false);
    const [isLoading, setIsLoading] = useState(false);
    const [showNotFound, setShowNotFound] = useState(false);
    const [notFoundMessage, setNotFoundMessage] = useState('');
    const [isSemanticResult, setIsSemanticResult] = useState(false);
    const [isVisualModalOpen, setIsVisualModalOpen] = useState(false);

    const dropdownRef = useRef<HTMLDivElement>(null);
    const inputRef = useRef<HTMLInputElement>(null);

    const debouncedSearchQuery = useDebounceRange(searchQuery, 250);

    // Handle input change
    const handleSearchChange = (e: React.ChangeEvent<HTMLInputElement>) => {
        setSearchQuery(e.target.value);
    };

    // Effect for debounced search API call — uses semantic search
    useEffect(() => {
        if (debouncedSearchQuery.length > 0) {
            setIsLoading(true);
            setSearchResults([]);
            setShowNotFound(false);
            setIsSemanticResult(false);
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
        } else {
            setIsDropdownOpen(false);
            setSearchResults([]);
            setShowNotFound(false);
            setIsSemanticResult(false);
        }
    }, [debouncedSearchQuery, t]);

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
        } else {
            navigate(`/products/${result.id}`);
        }
    };

    // Navigate to custom product request page
    const handleCustomProductRequest = () => {
        navigate('/custom-product-request');
    };

    const handleSubmit = (e: React.FormEvent<HTMLFormElement>) => {
        e.preventDefault();
        if (searchQuery.trim()) {
            navigate(`/show-results?query=${encodeURIComponent(searchQuery)}`);
        }
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
                <form 
                    onSubmit={handleSubmit} 
                    className="relative"
                >
                    <div className="relative rounded-[16px]">
                        <input
                            ref={inputRef}
                            type="text"
                            value={searchQuery}
                            onChange={handleSearchChange}
                            placeholder={t('home:hero.searchPlaceholder')}
                            className="w-full p-3 ps-10 pe-12 bg-white dark:bg-[#151D2C] border border-gray-300 dark:border-slate-700 text-gray-900 dark:text-slate-100 placeholder:text-gray-400 dark:placeholder:text-slate-500 rounded-[16px] focus:outline-none focus:border-yellow dark:focus:border-primary shadow-sm transition-all duration-300"
                        />
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            className="h-5 w-5 absolute start-3 top-1/2 transform -translate-y-1/2 text-gray-400 dark:text-slate-400 transition-colors"
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

                        {/* Camera Icon for Visual Search Modal Trigger */}
                        <button
                            type="button"
                            onClick={() => setIsVisualModalOpen(true)}
                            className="absolute end-3 top-1/2 cursor-pointer transform -translate-y-1/2 transition-all duration-200 p-1.5 rounded-full text-gray-400 dark:text-slate-400 hover:text-yellow dark:hover:text-[#E03E41] hover:bg-gray-100 dark:hover:bg-slate-800"
                            title={t('home:hero.visualSearchTip', 'Search by image')}
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" className="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                        </button>
                    </div>

                    {/* Text / Semantic Search Results Dropdown */}
                    {(isDropdownOpen && (searchResults?.length > 0 || showNotFound || isLoading)) && (
                        <div
                            ref={dropdownRef}
                            className={`absolute z-50 mt-2 w-full bg-white dark:bg-[#151D2C] rounded-xl shadow-2xl border border-gray-100 dark:border-slate-700/60 overflow-hidden transition-all duration-300 transform origin-top ${isDropdownOpen ? 'opacity-100 scale-100 translate-y-0' : 'opacity-0 scale-95 -translate-y-2 pointer-events-none'}`}
                        >
                            {/* AI Badge Header */}
                            {isSemanticResult && searchResults.length > 0 && (
                                <div className="px-3 py-1.5 bg-purple-50 dark:bg-purple-950/40 border-b border-purple-100 dark:border-purple-900/40 flex items-center gap-1.5">
                                    <span className="px-1.5 py-0.5 text-[10px] font-bold bg-purple-100 dark:bg-purple-900/60 text-purple-700 dark:text-purple-300 rounded-full">AI</span>
                                    <span className="text-xs text-purple-600 dark:text-purple-300">{t('common:search.semanticTitle', 'You might also be looking for')}</span>
                                </div>
                            )}

                            {isLoading ? (
                                <div className="p-2 space-y-0.5">
                                    {[1, 2, 3, 4].map(i => (
                                        <div key={i} className="p-3 flex items-center gap-3 border-b border-gray-100 dark:border-slate-800/50 last:border-b-0">
                                            <div className="w-4 h-4 rounded-full bg-gray-200 dark:bg-slate-700/60 shrink-0 animate-pulse" />
                                            <div className="flex-1">
                                                <div 
                                                    className="h-3.5 bg-gray-200 dark:bg-slate-700/60 rounded-full animate-pulse"
                                                    style={{ width: i === 1 ? '68%' : i === 2 ? '82%' : i === 3 ? '54%' : '72%' }}
                                                />
                                            </div>
                                        </div>
                                    ))}
                                    <div className="flex items-center justify-center py-2.5 pt-3 border-t border-gray-100 dark:border-slate-800/80 gap-2">
                                        <div className="flex space-x-1.5 rtl:space-x-reverse">
                                            <div className="w-1.5 h-1.5 rounded-full bg-[#B12C2E] dark:bg-[#E03E41] animate-bounce [animation-delay:-0.3s]"></div>
                                            <div className="w-1.5 h-1.5 rounded-full bg-[#B12C2E] dark:bg-[#E03E41] animate-bounce [animation-delay:-0.15s]"></div>
                                            <div className="w-1.5 h-1.5 rounded-full bg-[#B12C2E] dark:bg-[#E03E41] animate-bounce"></div>
                                        </div>
                                        <span className="text-xs text-gray-500 dark:text-slate-400 font-medium">{t('common:search.searching', 'Searching...')}</span>
                                    </div>
                                </div>
                            ) : !showNotFound ? (
                                <div className="max-h-[300px] overflow-y-auto">
                                    {searchResults.map((result) => (
                                        <div
                                            key={result.id}
                                            className="p-3 hover:bg-gray-50 dark:hover:bg-slate-800/80 cursor-pointer border-b border-gray-100 dark:border-slate-800/60 last:border-b-0 transition-colors"
                                            onClick={() => handleResultClick(result)}
                                        >
                                            <div className="flex justify-between items-center">
                                                <div className="text-start flex items-center gap-2">
                                                    <p className="font-semibold text-gray-900 dark:text-slate-100 text-sm inline-flex items-center gap-2">
                                                        <bdi>{result.name}</bdi>
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

            {/* Dedicated Visual Search Modal */}
            <VisualSearchModal
                show={isVisualModalOpen}
                onClose={() => setIsVisualModalOpen(false)}
            />
        </div>
    );
};

export default HeroSection;
