import React, { useEffect, useState, useRef } from 'react';
import { useDispatch, useSelector } from 'react-redux';
import { setCategories } from '../../store/slices/filterSlice';
import type { RootState } from '../../store/store';
import FilterSection from './components/FilterSection';
import ProductGrid from './components/ProductGrid';
import FooterSection from '../../components/FooterSection';
import ApiClient from '../../ApiClient';
import type { IProductCard } from '../../types';
import i18n from '../../i18n';
import { batchUpdateCartItemLanguages } from '../../store/slices/cartSlice';
import { useLocation } from 'react-router-dom';
import { setIsCartSummarySidebarOpen } from '../../store/slices/isCartSummarySidebarOpenSlice';

interface ShopPageProps {
  onToggleCart?: () => void;
}

const ShopPage: React.FC<ShopPageProps> = ({ onToggleCart }) => {
  const dispatch = useDispatch();
  const [products, setProducts] = useState<IProductCard[]>([]);
  const [loading, setLoading] = useState(true);
  const [showFilters, setShowFilters] = useState(false);
  const { 
    selectedCategorySlugs, 
    selectedFilter, 
    priceRange 
  } = useSelector((state: RootState) => state.filter);
  const { items } = useSelector((state: RootState) => state.cart);

  const location = useLocation();
  const searchParams = location.search;
  const categoryName = new URLSearchParams(searchParams).get('category');  

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
        const translatedProducts = data.products || data; 
        if (translatedProducts && Array.isArray(translatedProducts) && translatedProducts.length > 0) {
          dispatch(batchUpdateCartItemLanguages(translatedProducts));
        }
      }
    } catch (error) {
      console.error('Error fetching updated product details for language:', error);
    }
  };

  useEffect(() => {
    const fetchProducts = async () => {
      setLoading(true);
      try {
        const filterParts = [];

        // Prioritize category slugs from the filter state. If none, use the category from the URL.
        const slugsToUse = selectedCategorySlugs.length > 0 ? selectedCategorySlugs : (categoryName ? [categoryName] : []);
        const categoryString = slugsToUse.join(',');

        if (categoryString) {
          filterParts.push(categoryString);
        }

        // Append the selected sort/filter option if it exists.
        if (selectedFilter) {
          filterParts.push(selectedFilter);
        }

        // Join all parts to create the final filter string for the API.
        const finalFilter = filterParts.join('/');

        const response = await ApiClient.shop.getProductsByFilter(
          finalFilter,
          priceRange[0],
          priceRange[1]
        );

        const newProducts = response.data.data.products.data;
        const industrialCategoriesFromResponse = response.data.data.industrial_categories;

        setProducts(newProducts);
        
        try {
          await updateProductLanguages(newProducts);
        } catch (langError) {
          console.error('Error updating product languages:', langError);
        }

        if (industrialCategoriesFromResponse) {
          dispatch(setCategories(industrialCategoriesFromResponse));
        }
      } catch (error) {
        console.error('Error fetching products:', error);
        setProducts([]);
      } finally {
        setLoading(false);
      }
    };

    fetchProducts();
  }, [categoryName, selectedCategorySlugs, selectedFilter, priceRange, dispatch]);

  const productsRef = useRef(products);
  useEffect(() => {
    productsRef.current = products;
  }, [products]);

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

  const toggleFilters = () => {
    setShowFilters(!showFilters);
  };

    const isCartSummarySidebarOpen = useSelector((state: RootState) => state.isCartSummarySidebarOpen);
    const toggleCartSummary = () => {
      dispatch(setIsCartSummarySidebarOpen(!isCartSummarySidebarOpen));
    };

  return (
    <div className="min-h-screen">
      {/* Mobile toggle buttons - only visible on small screens */}
      <div className="lg:hidden py-3 flex gap-2">
        <button 
          onClick={toggleFilters}
          className="flex-1 py-2 min-[330px]:px-4 px-3 bg-yellow text-white rounded-lg flex items-center justify-center gap-2"
        >
          <svg xmlns="http://www.w3.org/2000/svg" className="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
            <path fillRule="evenodd" d="M3 5a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 5a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 5a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1z" clipRule="evenodd" />
          </svg>
          {showFilters ? 'Hide Filters' : 'Show Filters'}
        </button>
        
        {items.length > 0 && (
          <button 
            onClick={toggleCartSummary}
            className="flex-1 py-2 min-[330px]:px-4 px-3 bg-yellow text-white rounded-lg flex items-center justify-center gap-2"
          >
            <svg xmlns="http://www.w3.org/2000/svg" className="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
              <path d="M3 1a1 1 0 000 2h1.22l.305 1.222a.997.997 0 00.01.042l1.358 5.43-.893.892C3.74 11.846 4.632 14 6.414 14H15a1 1 0 000-2H6.414l1-1H14a1 1 0 00.894-.553l3-6A1 1 0 0017 3H6.28l-.31-1.243A1 1 0 005 1H3zM16 16.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0zM6.5 18a1.5 1.5 0 100-3 1.5 1.5 0 000 3z" />
            </svg>
            Cart ({items.length})
          </button>
        )}
      </div>
      
      <div className="py-6 flex w-full flex-col lg:flex-row gap-6">
        <FilterSection 
          categoryName={categoryName || undefined} 
          isVisible={showFilters} 
          onClose={toggleFilters}
        />
        <ProductGrid products={products} isLoading={loading} />
      </div>
      <FooterSection />
    </div>
  );
};

export default ShopPage;
