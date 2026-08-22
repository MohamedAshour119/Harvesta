import React, { useEffect, useRef, useState } from 'react';
import { useDispatch, useSelector } from 'react-redux';
import { setCategories, setSelectedCategorySlugs, setSelectedSortFilter } from '../../store/slices/filterSlice';
import type { RootState } from '../../store/store';
import FooterSection from '../../components/FooterSection';
import ApiClient from '../../ApiClient';
import type { IProductCard } from '../../types';
import i18n from '../../i18n';
import { batchUpdateCartItemLanguages } from '../../store/slices/cartSlice';
import FilterSection from './components/FilterSection';
import ProductGrid from './components/ProductGrid';
import { useLocation, useNavigate } from 'react-router-dom';
import NotFoundResults from './components/NotFoundResults';

const Products: React.FC = () => {
  const dispatch = useDispatch();
  const [products, setProducts] = useState<IProductCard[]>([]);
  const [loading, setLoading] = useState(true);
  const [currentPage, setCurrentPage] = useState<number>(1);
  const [lastPage, setLastPage] = useState<number>(1);
  const [searchTerm, setSearchTerm] = useState('');
  const [showNotFoundResults, setShowNotFoundResults] = useState(false);
  const isUpdatingUrlFromState = useRef(false);
  const filterState = useSelector((state: RootState) => state.filter);
  const selectedCategorySlugs = filterState?.selectedCategorySlugs || [];
  const selectedFilter = filterState ? filterState.selectedFilter : null;
  const priceRange = filterState?.priceRange || [0, 0];

  const navigate = useNavigate();
  const location = useLocation();
  const pathname = location.pathname;
  const searchParams = location.search;
  const searchQuery = new URLSearchParams(searchParams).get('query');
  const categoryParam = new URLSearchParams(searchParams).get('category');
  const filterParam = new URLSearchParams(searchParams).get('filter');

  useEffect(() => {
    const params = new URLSearchParams(location.search);
    const urlCategorySlugs = (params.get('category') ?? '')
      .split(',')
      .map((slug) => slug.trim())
      .filter(Boolean);
    const urlFilter = params.get('filter');

    if (isUpdatingUrlFromState.current) {
      isUpdatingUrlFromState.current = false;
      return;
    }

    if (selectedCategorySlugs.join(',') !== urlCategorySlugs.join(',')) {
      dispatch(setSelectedCategorySlugs(urlCategorySlugs));
    }

    if ((selectedFilter ?? null) !== (urlFilter ?? null)) {
      dispatch(setSelectedSortFilter(urlFilter));
    }
  }, [location.search, dispatch]);

  useEffect(() => {
    const params = new URLSearchParams(location.search);
    const nextCategory = selectedCategorySlugs.length > 0 ? selectedCategorySlugs.join(',') : null;
    const currentCategory = params.get('category');
    const currentFilter = params.get('filter');

    if (currentCategory === nextCategory && currentFilter === selectedFilter) {
      return;
    }

    if (nextCategory) {
      params.set('category', nextCategory);
    } else {
      params.delete('category');
    }

    if (selectedFilter) {
      params.set('filter', selectedFilter);
    } else {
      params.delete('filter');
    }

    isUpdatingUrlFromState.current = true;

    navigate(
      {
        pathname,
        search: params.toString() ? `?${params.toString()}` : '',
      },
      { replace: true }
    );
  }, [selectedCategorySlugs, selectedFilter, location.search, navigate, pathname]);

  const updateProductLanguages = async (productsToTranslate: IProductCard[]) => {
    if (!productsToTranslate || productsToTranslate.length === 0) return;
    try {
      const batchIds = productsToTranslate
        .filter(p => p.cheapestVariant)
        .map((product) => ({
          productId: product.id,
          productVariantId: product.cheapestVariant!.id
        }));

      if (batchIds.length > 0) {
        const languageResponse = await ApiClient.products.getProductsByIds(batchIds as any);
        const data = languageResponse.data.data;
        const translatedProducts = (data as any).products || data;
        if (translatedProducts && Array.isArray(translatedProducts) && translatedProducts.length > 0) {
          dispatch(batchUpdateCartItemLanguages(translatedProducts));
        }
      }
    } catch (error) {
      console.error('Error fetching updated product details for language:', error);
    }
  };



  useEffect(() => {
    // Prevent double request when URL params are being synchronized to Redux state
    const isSyncingFilter = (filterParam ?? null) !== (selectedFilter ?? null);
    const isSyncingCategory = ((categoryParam ?? '')
      .split(',')
      .map((slug) => slug.trim())
      .filter(Boolean)
      .join(',')) !== selectedCategorySlugs.join(',');
    const isSyncingBackToDefault = false;

    if (isSyncingFilter || isSyncingCategory || isSyncingBackToDefault) {
      return;
    }

    const fetchProducts = async () => {
      setLoading(true);
      try {
        let newProducts: IProductCard[] = [];
        let pagination: { current_page: number, last_page: number } = { current_page: 1, last_page: 1 };
        let industrialCategoriesFromResponse: any[] | undefined;

        const filterParts: string[] = [];

        // Add special filter if it exists
        if (selectedFilter && selectedFilter !== 'none') {
            filterParts.push(selectedFilter);
        }

        if (selectedCategorySlugs.length > 0) {
            filterParts.push(selectedCategorySlugs.join(','));
        }

        const filterString = filterParts.join('/');

        const response = await ApiClient.shop.getProductsByFilter(
          filterString,
          priceRange[0],
          priceRange[1],
          searchQuery || '',
          currentPage
        );

        newProducts = response.data.data.products.data;
        pagination.current_page = response.data.data.products.current_page;
        pagination.last_page = response.data.data.products.last_page;
        industrialCategoriesFromResponse = response.data.data.industrial_categories;

        if (newProducts.length === 0) {
          setShowNotFoundResults(true);
        } else {
          setShowNotFoundResults(false);
        }
        setProducts(newProducts);
        setCurrentPage(pagination.current_page);
        setLastPage(pagination.last_page);
        await updateProductLanguages(newProducts);

        if (industrialCategoriesFromResponse) {
          dispatch(setCategories(industrialCategoriesFromResponse));
        }

      } catch (error) {
        console.error('Error fetching products:', error);
      } finally {
        setLoading(false);
      }
    };

    fetchProducts();
  }, [selectedCategorySlugs, selectedFilter, priceRange, dispatch, pathname, currentPage, categoryParam, filterParam]);

  useEffect(() => {
    const handleLanguageChange = async () => {
      const currentProducts = products;
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
    <div className="min-h-screen px-4 md:px-0">
      <div className={`py-6 flex flex-col lg:flex-row gap-6 w-full`}>
        <FilterSection />
        {!showNotFoundResults ? (
          <ProductGrid
            products={products}
            currentPage={currentPage}
            lastPage={lastPage}
            setCurrentPage={setCurrentPage}
            setLastPage={setLastPage}
            setProducts={setProducts}
            searchTerm={searchTerm}
            setSearchTerm={setSearchTerm}
            isLoading={loading}
          />
        ) : (
          <NotFoundResults searchQuery={searchTerm} className="w-full" />
        )}
      </div>
      <FooterSection />
    </div>
  );
};

export default Products;
