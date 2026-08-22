import React, { useEffect, useState, useRef } from 'react';
import { useDispatch, useSelector } from 'react-redux';
import { setCategories } from '../../store/slices/filterSlice';
import type { RootState } from '../../store/store';
import FooterSection from '../../components/FooterSection';
import ApiClient from '../../ApiClient';
import type { IProductCard } from '../../types';
import i18n from '../../i18n';
import { batchUpdateCartItemLanguages } from '../../store/slices/cartSlice';
import FilterSection from '../Shop/components/FilterSection';
import ProductGrid from './components/ProductGrid';
import { useLocation } from 'react-router-dom';
import NotFoundResults from './components/NotFoundResults';
import { useTranslation } from 'react-i18next';
import { setIsCartSummarySidebarOpen } from '../../store/slices/isCartSummarySidebarOpenSlice';
import MaxWidthWrapper from '../../components/MaxWidthWrapper';

const ShowResults: React.FC = () => {
  const dispatch = useDispatch();
  const { t } = useTranslation(['common']);
  const [results, setResults] = useState<IProductCard[]>([]);
  const [showNotFoundResults, setShowNotFoundResults] = useState(false)
  const [showFilters, setShowFilters] = useState(false);

  // Semantic search state
  const [semanticResults, setSemanticResults] = useState<IProductCard[]>([]);
  const [hasSemanticResults, setHasSemanticResults] = useState(false);

  const filterState = useSelector((state: RootState) => state.filter);
  const selectedCategorySlugs = filterState?.selectedCategorySlugs || [];
  const selectedFilter = filterState?.selectedFilter;
  const priceRange = filterState?.priceRange || [0, 10000];
  
  const cartState = useSelector((state: RootState) => state.cart);
  const cartItems = cartState?.items || [];
  
  const location = useLocation();
  const pathname = location.pathname;
  const searchParams = location.search;
  const searchQuery = new URLSearchParams(searchParams).get('query');  

  const toggleFilters = () => {
    setShowFilters(!showFilters);
  };

  const isCartSummarySidebarOpen = useSelector((state: RootState) => state.isCartSummarySidebarOpen);
  const toggleCartSummary = () => {
    dispatch(setIsCartSummarySidebarOpen(!isCartSummarySidebarOpen));
  };

  useEffect(() => {
    const fetchResults = async () => {
      try {
        // 1. Literal keyword search
        const response = await ApiClient.search.searchForProductsKeyword(searchQuery || '');
        const newResults = response.data.data.data;
        setResults(newResults);

        // Update categories based on search results
        const categoriesFromResponse = response.data.data.categories || response.data.data.industrial_categories;
        if (categoriesFromResponse && categoriesFromResponse.length > 0) {
          dispatch(setCategories(categoriesFromResponse));
        }

        // 2. Also run semantic search in parallel
        try {
          const semanticResponse = await ApiClient.search.semanticSearch(searchQuery || '');
          const semanticData = semanticResponse.data.data;
          const semanticProducts = semanticData?.semantic_results || [];
          
          // Update categories from semantic response too
          const semanticCategories = semanticData?.industrial_categories;
          if (semanticCategories && semanticCategories.length > 0) {
            dispatch(setCategories(semanticCategories));
          }
          
          // Filter out products already in literal results
          const literalIds = newResults.map((p: IProductCard) => p.id);
          const filteredSemantic = semanticProducts.filter(
            (p: any) => !literalIds.includes(p.id)
          );
          
          setSemanticResults(filteredSemantic);
          setHasSemanticResults(filteredSemantic.length > 0);

          // Only show not found if BOTH are empty
          if (newResults.length === 0 && filteredSemantic.length === 0) {
            setShowNotFoundResults(true);
          }
        } catch {
          // Semantic search failed — only show not found if literal also empty
          if (newResults.length === 0) {
            setShowNotFoundResults(true);
          }
        }

      } catch (error) {
        console.error('Error fetching results:', error);
        
        // If literal search fails, try semantic as fallback
        try {
          const semanticResponse = await ApiClient.search.semanticSearch(searchQuery || '');
          const semanticData = semanticResponse.data.data;
          const semanticProducts = semanticData?.semantic_results || [];
          const literalProducts = semanticData?.literal_results || [];
          
          if (literalProducts.length > 0 || semanticProducts.length > 0) {
            setResults(literalProducts);
            setSemanticResults(semanticProducts);
            setHasSemanticResults(semanticProducts.length > 0);
            
            const cats = semanticData?.industrial_categories || semanticData?.categories;
            if (cats) {
              dispatch(setCategories(cats));
            }
          } else {
            setShowNotFoundResults(true);
          }
        } catch {
          setShowNotFoundResults(true); 
        }
      } finally {
        // fetch complete
      }
    };
    fetchResults();
  }, [searchQuery])
  
  const updateProductLanguages = async (productsToTranslate: IProductCard[]) => {
    if (!productsToTranslate || productsToTranslate.length === 0) return;
    try {
      const productIds = productsToTranslate.map((product) => ({
        productId: product.id,
        productVariantId: product.cheapestVariant?.id || 0
      }));
      if (productIds.length > 0) {
        const languageResponse = await ApiClient.products.getProductsByIds(productIds);
        const translatedProducts = languageResponse.data.data || languageResponse.data; 
        if (translatedProducts && Array.isArray(translatedProducts) && translatedProducts.length > 0) {
          dispatch(batchUpdateCartItemLanguages(translatedProducts));
        }
      }
    } catch (error) {
      console.error('Error fetching updated product details for language:', error);
    }
  };

  const skipFetchOnMountRef = useRef(true);
  useEffect(() => {
    if (skipFetchOnMountRef.current) {
      skipFetchOnMountRef.current = false;
      if (pathname === '/show-results') {
        return;
      }
    }
    const fetchProducts = async () => {
      try {
        let newProducts: IProductCard[] = [];

        if (selectedFilter === 'most-popular' || selectedFilter === 'newest' || selectedFilter === 'all-discounts' || selectedFilter === 'today-deals' || selectedFilter === 'in-stock' || selectedFilter === 'out-of-stock') {
          const response = await ApiClient.shop.getProductsByFilter(
            selectedFilter, 
            priceRange[0],
            priceRange[1],
            searchQuery || ''
          );
          newProducts = response.data.data.products.data; 
          const cats = response.data.data.industrial_categories || response.data.data.categories;
          if (cats) {
            dispatch(setCategories(cats));
          }
        } else if (selectedCategorySlugs && selectedCategorySlugs.length > 0) {
          const slugsString = selectedCategorySlugs.join(',');
          const response = await ApiClient.shop.getProductsByFilter(
            slugsString, 
            priceRange[0],
            priceRange[1],
            searchQuery || ''
          );
          newProducts = response.data.data.products.data; 
          const cats = response.data.data.industrial_categories || response.data.data.categories;
          if (cats) {
            dispatch(setCategories(cats));
          }
        } else {
          const response = await ApiClient.shop.getProductsByFilter(
            '',
            priceRange[0],
            priceRange[1],
            searchQuery || ''
          );
          newProducts = response.data.data.products.data; 
          const cats = response.data.data.industrial_categories || response.data.data.categories;
          if (cats) {
            dispatch(setCategories(cats));
          }
        }
        setResults(newProducts);
        setHasSemanticResults(false);
        await updateProductLanguages(newProducts);

      } catch (error) {
        console.error('Error fetching products:', error);
        setResults([]); 
      }
    };

    fetchProducts();
  }, [selectedCategorySlugs, selectedFilter, priceRange, dispatch, pathname]);

  const productsRef = useRef(results);
  useEffect(() => {
    productsRef.current = results;
  }, [results]);

  useEffect(() => {
    const handleLanguageChange = async () => {
      const currentProducts = productsRef.current;
      if (currentProducts.length > 0) {
        await updateProductLanguages(currentProducts);
      }
    };

    i18n.on('languageChanged', handleLanguageChange);
    return () => {
      i18n.off('languageChanged', handleLanguageChange);
    };
  }, [dispatch]); 

  return (
    <div className="min-h-screen flex flex-col justify-between w-full">
      <MaxWidthWrapper className="flex-1 w-full px-4">
        {/* Mobile toggle buttons - only visible on small screens */}
        {((!showNotFoundResults || hasSemanticResults) || cartItems.length > 0) && (
          <div className="lg:hidden py-3 flex gap-2">
            {(!showNotFoundResults || hasSemanticResults) && (
              <button 
                onClick={toggleFilters}
                className="flex-1 py-2 min-[330px]:px-4 px-3 bg-yellow text-white rounded-lg flex items-center justify-center gap-2"
              >
                <svg xmlns="http://www.w3.org/2000/svg" className="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                  <path fillRule="evenodd" d="M3 5a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 5a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 5a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1z" clipRule="evenodd" />
                </svg>
                {showFilters ? t('shop:filterSection.hideFilters', 'Hide Filters') : t('shop:filterSection.showFilters', 'Show Filters')}
              </button>
            )}
            
            {cartItems.length > 0 && (
              <button 
                onClick={toggleCartSummary}
                className="flex-1 py-2 min-[330px]:px-4 px-3 bg-yellow text-white rounded-lg flex items-center justify-center gap-2"
              >
                <svg xmlns="http://www.w3.org/2000/svg" className="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                  <path d="M3 1a1 1 0 000 2h1.22l.305 1.222a.997.997 0 00.01.042l1.358 5.43-.893.892C3.74 11.846 4.632 14 6.414 14H15a1 1 0 000-2H6.414l1-1H14a1 1 0 00.894-.553l3-6A1 1 0 0017 3H6.28l-.31-1.243A1 1 0 005 1H3zM16 16.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0zM6.5 18a1.5 1.5 0 100-3 1.5 1.5 0 000 3z" />
                </svg>
                {t('shop:cart', 'Cart')} ({cartItems.length})
              </button>
            )}
          </div>
        )}

        <div className={`py-6 flex flex-col lg:flex-row gap-6 ${showNotFoundResults && !hasSemanticResults ? 'justify-center items-center' : ''}`}>
          {!showNotFoundResults || hasSemanticResults ? (
            <>
              <FilterSection isVisible={showFilters} onClose={toggleFilters} />
              <div className="flex-1">
                {results.length > 0 && (
                  <ProductGrid products={results} searchQuery={searchQuery || ''} />
                )}
                
                {/* AI Semantic Results Section */}
                {hasSemanticResults && (
                  <div className="mt-8">
                    <div className="flex items-center gap-2 mb-4">
                      <span className="px-2.5 py-1 text-xs font-bold bg-purple-100 dark:bg-purple-900/60 text-purple-700 dark:text-purple-300 rounded-full">AI</span>
                      <h3 className="text-lg font-bold text-gray-900 dark:text-slate-100">
                        {t('common:search.semanticTitle', 'You might also be looking for')}
                      </h3>
                    </div>
                    <ProductGrid products={semanticResults} searchQuery={searchQuery || ''} />
                  </div>
                )}
              </div>
            </>
          ) : (
            <NotFoundResults searchQuery={searchQuery || ''} />
          )}
        </div>
      </MaxWidthWrapper>
      <FooterSection />
    </div>
  );
};

export default ShowResults;
