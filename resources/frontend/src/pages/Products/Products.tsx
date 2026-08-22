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
import MaxWidthWrapper from '../../components/MaxWidthWrapper';

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

  // Extract query params directly from URL for bidirectional sync
  const queryParams = new URLSearchParams(location.search);
  const categoryParam = queryParams.get('category');
  const filterParam = queryParams.get('filter');

  // Sync state FROM URL on initial load and back/forward navigation
  useEffect(() => {
    if (isUpdatingUrlFromState.current) {
      isUpdatingUrlFromState.current = false;
      return;
    }

    const categoriesFromUrl = categoryParam
      ? categoryParam.split(',').map((slug) => slug.trim()).filter(Boolean)
      : [];

    if (categoriesFromUrl.length > 0) {
      dispatch(setSelectedCategorySlugs(categoriesFromUrl));
    }

    if (filterParam) {
      dispatch(setSelectedSortFilter(filterParam));
    }
  }, [categoryParam, filterParam, dispatch]);

  // Sync state TO URL when Redux state changes
  useEffect(() => {
    const params = new URLSearchParams(location.search);

    // Update or delete 'category'
    if (selectedCategorySlugs.length > 0) {
      params.set('category', selectedCategorySlugs.join(','));
    } else {
      params.delete('category');
    }

    // Update or delete 'filter'
    if (selectedFilter) {
      params.set('filter', selectedFilter);
    } else {
      params.delete('filter');
    }

    // Don't push identical URL
    const currentParamsString = location.search.replace(/^\?/, '');
    const newParamsString = params.toString();
    if (currentParamsString === newParamsString) {
      return;
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
        if (selectedFilter) {
          filterParts.push(`filter:${selectedFilter}`);
        }

        // Add categories if they exist
        if (selectedCategorySlugs && selectedCategorySlugs.length > 0) {
          filterParts.push(`categories:${selectedCategorySlugs.join(',')}`);
        }

        // Add price range if set
        if (priceRange[0] > 0 || priceRange[1] > 0) {
          filterParts.push(`price_range:${priceRange[0]}-${priceRange[1]}`);
        }

        const filterQuery = filterParts.join(';');

        if (filterQuery) {
          const response = await ApiClient.products.getProductsWithFilter(filterQuery, currentPage);
          const responseData = response.data.data;
          newProducts = responseData.data;
          pagination = {
            current_page: responseData.current_page,
            last_page: responseData.last_page
          };
          industrialCategoriesFromResponse = responseData.industrial_categories;
        } else {
          const response = await ApiClient.products.getAllProducts(currentPage);
          const responseData = response.data.data;
          newProducts = responseData.data;
          pagination = {
            current_page: responseData.current_page,
            last_page: responseData.last_page
          };
          industrialCategoriesFromResponse = responseData.industrial_categories;
        }

        if (industrialCategoriesFromResponse && industrialCategoriesFromResponse.length > 0) {
          dispatch(setCategories(industrialCategoriesFromResponse));
        }

        setProducts(newProducts);
        setCurrentPage(pagination.current_page);
        setLastPage(pagination.last_page);
        setShowNotFoundResults(newProducts.length === 0);

      } catch (error) {
        console.error('Error fetching products:', error);
        setProducts([]);
        setShowNotFoundResults(true);
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
    <div className="min-h-screen flex flex-col justify-between w-full">
      <MaxWidthWrapper className="flex-1 w-full px-4 md:px-0">
        <div className="py-6 flex flex-col lg:flex-row gap-6 w-full">
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
      </MaxWidthWrapper>
      <FooterSection />
    </div>
  );
};

export default Products;
