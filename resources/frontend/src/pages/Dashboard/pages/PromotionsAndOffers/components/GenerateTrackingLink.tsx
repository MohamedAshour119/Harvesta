import { useState, useEffect, useRef } from 'react';
import { FaCheck, FaCopy, FaSearch, FaTimes } from 'react-icons/fa';
import { useSelector } from 'react-redux';
import type { RootState } from '../../../../../store/store';
import ApiClient from '../../../../../ApiClient';
import { useTranslation } from 'react-i18next';

interface SearchResult {
  id: number;
  name: string;
  slug: string;
}

const GenerateTrackingLink = () => {
  const { t } = useTranslation(['dashboard']);
  const user = useSelector((state: RootState) => state.auth?.user);
  const agentCode = user?.code || '';

  const [searchQuery, setSearchQuery] = useState('');
  const [searchResults, setSearchResults] = useState<SearchResult[]>([]);
  const [isSearching, setIsSearching] = useState(false);
  const [isDropdownOpen, setIsDropdownOpen] = useState(false);
  const [selectedProduct, setSelectedProduct] = useState<SearchResult | null>(null);
  const [isCopied, setIsCopied] = useState(false);
  const [generatedLink, setGeneratedLink] = useState('');

  const searchTimeoutRef = useRef<ReturnType<typeof setTimeout> | null>(null);
  const dropdownRef = useRef<HTMLDivElement>(null);

  // Debounced search
  useEffect(() => {
    if (searchQuery.length < 2) {
      setSearchResults([]);
      setIsDropdownOpen(false);
      return;
    }

    if (searchTimeoutRef.current) {
      clearTimeout(searchTimeoutRef.current);
    }

    searchTimeoutRef.current = setTimeout(async () => {
      setIsSearching(true);
      try {
        const response = await ApiClient.search.searchForProducts(searchQuery);
        const results = response.data?.data || [];
        setSearchResults(results);
        setIsDropdownOpen(results.length > 0);
      } catch (error) {
        console.error('Search failed:', error);
        setSearchResults([]);
      } finally {
        setIsSearching(false);
      }
    }, 300);

    return () => {
      if (searchTimeoutRef.current) {
        clearTimeout(searchTimeoutRef.current);
      }
    };
  }, [searchQuery]);

  // Click outside to close dropdown
  useEffect(() => {
    const handleClickOutside = (event: MouseEvent) => {
      if (dropdownRef.current && !dropdownRef.current.contains(event.target as Node)) {
        setIsDropdownOpen(false);
      }
    };
    document.addEventListener('mousedown', handleClickOutside);
    return () => document.removeEventListener('mousedown', handleClickOutside);
  }, []);

  const handleProductSelect = (product: SearchResult) => {
    setSelectedProduct(product);
    setSearchQuery(product.name);
    setIsDropdownOpen(false);

    const link = `${window.location.origin}/products/${product.slug}?ref=${agentCode}`;
    setGeneratedLink(link);
    setIsCopied(false);
  };

  const handleClearSelection = () => {
    setSelectedProduct(null);
    setSearchQuery('');
    setGeneratedLink('');
    setIsCopied(false);
  };

  const handleCopyLink = async () => {
    if (!generatedLink) return;

    try {
      await navigator.clipboard.writeText(generatedLink);
      setIsCopied(true);
      setTimeout(() => setIsCopied(false), 2000);
    } catch (err) {
      console.error('Failed to copy link:', err);
    }
  };

  return (
    <div className="bg-white rounded-lg border border-gray-200 p-6 shadow-sm mx-4">
      <h2 className="text-lg font-semibold text-gray-900 mb-1">
        {t('dashboard:trackingLink.title')}
      </h2>
      <p className="text-sm text-gray-500 mb-4">
        {t('dashboard:trackingLink.description')}
      </p>

      <div className="flex items-start flex-wrap gap-4">
        {/* Product Search */}
        <div className="relative md:min-w-[40rem] flex-1" ref={dropdownRef}>
          <div className="relative">
            <FaSearch className="absolute start-3 top-1/2 transform -translate-y-1/2 text-gray-400 w-4 h-4" />
            <input
              type="text"
              value={searchQuery}
              onChange={(e) => {
                setSearchQuery(e.target.value);
                if (selectedProduct) setSelectedProduct(null);
              }}
              placeholder={t('dashboard:trackingLink.searchPlaceholder')}
              className="w-full ps-10 pe-10 py-3 bg-gray-50 hover:bg-gray-100 border border-gray-200 rounded-md text-gray-900 transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-[#B12C2E]/20 focus:border-[#B12C2E]"
            />
            {(searchQuery || selectedProduct) && (
              <button
                onClick={handleClearSelection}
                className="absolute end-3 top-1/2 transform -translate-y-1/2 text-gray-400 hover:text-gray-600 cursor-pointer"
              >
                <FaTimes className="w-4 h-4" />
              </button>
            )}
          </div>

          {/* Loading indicator */}
          {isSearching && (
            <div className="absolute top-full start-0 mt-1 w-full bg-white border border-gray-200 rounded-md shadow-lg z-20 p-4 text-center text-gray-500 text-sm">
              <div className="animate-pulse">{t('dashboard:trackingLink.searching')}</div>
            </div>
          )}

          {/* Search Results Dropdown */}
          {isDropdownOpen && !isSearching && (
            <div className="absolute top-full start-0 mt-1 w-full bg-white border border-gray-200 rounded-md shadow-lg z-20">
              <div className="py-1 max-h-64 overflow-y-auto">
                {searchResults.length > 0 ? (
                  searchResults.map((product) => (
                    <button
                      key={product.id}
                      onClick={() => handleProductSelect(product)}
                      className="w-full text-start px-4 py-3 text-sm text-gray-700 hover:bg-red-50 hover:text-[#B12C2E] cursor-pointer transition-colors duration-150 border-b border-gray-100 last:border-b-0"
                    >
                      {product.name}
                    </button>
                  ))
                ) : (
                  <div className="px-4 py-3 text-sm text-gray-500">
                    {t('dashboard:trackingLink.noResults')}
                  </div>
                )}
              </div>
            </div>
          )}
        </div>

        {/* Copy Tracking Link Button */}
        <button
          onClick={handleCopyLink}
          disabled={!selectedProduct}
          className={`flex items-center gap-2 px-6 py-3 rounded-md font-medium text-sm transition-all duration-200 whitespace-nowrap ${selectedProduct
              ? isCopied
                ? 'bg-green-500 hover:bg-green-600 text-white'
                : 'bg-[#B12C2E] hover:bg-[#972426] cursor-pointer text-white shadow-sm hover:shadow-md'
              : 'bg-gray-300 text-gray-500 cursor-not-allowed'
            }`}
        >
          {isCopied ? (
            <>
              <FaCheck className="w-4 h-4" />
              {t('dashboard:trackingLink.copied')}
            </>
          ) : (
            <>
              <FaCopy className="w-4 h-4" />
              {t('dashboard:trackingLink.copyLink')}
            </>
          )}
        </button>
      </div>

      {/* Generated Link Display */}
      {generatedLink && (
        <div className="mt-4 p-3 bg-gray-50 rounded-md border border-gray-200">
          <div className="text-xs text-gray-500 mb-1">{t('dashboard:trackingLink.generatedLink')}</div>
          <div className="text-sm text-[#B12C2E] font-mono break-all select-all">{generatedLink}</div>
        </div>
      )}

      {/* Agent Code Info */}
      {agentCode && (
        <div className="mt-3 flex items-center gap-2 text-xs text-gray-400">
          <span>{t('dashboard:trackingLink.yourCode')}: <span className="font-mono text-gray-600 bg-gray-100 px-1.5 py-0.5 rounded">{agentCode}</span></span>
        </div>
      )}
    </div>
  );
};

export default GenerateTrackingLink;
