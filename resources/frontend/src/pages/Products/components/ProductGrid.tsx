import React, { useState, useRef } from 'react';
import { useDispatch, useSelector } from 'react-redux';
import ProductCard from '../../../components/ProductCard';
import ProductCardPlaceholder from '../../../components/ProductCardPlaceholder';
import { addToCart } from '../../../store/slices/cartSlice';
import type { CartItem, IProductCard, Paginated } from '../../../types';
import { MdKeyboardDoubleArrowLeft, MdKeyboardDoubleArrowRight } from 'react-icons/md';
import { RiArrowLeftSLine, RiArrowRightSLine } from 'react-icons/ri';
import { useTranslation } from 'react-i18next';
import { FaSearch } from 'react-icons/fa';
import { IoClose } from 'react-icons/io5';
import ApiClient from '../../../ApiClient';
import NotFoundResults from './NotFoundResults';
import { setSelectedFilter } from '../../../store/slices/filterSlice';
import type { RootState } from '../../../store/store';

interface Product extends IProductCard { }

interface ProductGridProps {
  products: Product[];
  currentPage: number;
  lastPage: number;
  searchTerm: string;
  setCurrentPage: (page: number) => void;
  setLastPage: (page: number) => void;
  setProducts: (products: Product[]) => void;
  setSearchTerm: (searchTerm: string) => void;
  isLoading?: boolean;
}

const ProductGrid: React.FC<ProductGridProps> = ({ products, currentPage, lastPage, searchTerm, setCurrentPage, setLastPage, setProducts, setSearchTerm, isLoading }: ProductGridProps) => {
  const { t } = useTranslation(['shop']);
  const dispatch = useDispatch();
  const lang = useTranslation().i18n.language;
  const [loadingProducts, setLoadingProducts] = useState<boolean>(false);
  const searchInputRef = useRef<HTMLInputElement>(null);
  const [showNotFoundResults, setShowNotFoundResults] = useState(false);
  const selectedFilter = useSelector((state: RootState) => state.filter?.selectedFilter);

  const handleAddToCart = (product: Product) => {
    if (product.cheapestVariant && typeof product.cheapestVariant.unit_price_usd === 'string') {
      const cartProductPayload: Omit<CartItem, 'quantity'> = {
        productId: product.id,
        id: product.cheapestVariant.id,
        variantName: product.cheapestVariant.variant_name,
        variantValue: product.cheapestVariant.variant_value,
        name: product.name,
        description: product.description,
        category: {
          id: product.category?.id || 0,
          name: product.category?.name || ""
        },
        price: parseFloat(product.cheapestVariant.unit_price_usd),
        image: product.images.main_image,
        slug: product.slug || '',
      };
      dispatch(addToCart(cartProductPayload));
    } else {
      console.error(
        "Product cannot be added to cart: cheapestVariant is missing or its price is malformed.",
        product
      );
    }
  };

  const handleSearchSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    if (searchInputRef.current) {
      searchInputRef.current.blur();
    }

    if (searchTerm.length > 0) {
      setLoadingProducts(true);
      ApiClient.search.searchForProductsKeyword(searchTerm)
        .then(response => {
          const productsResponse = response.data.data.data;
          setProducts(productsResponse);
          const paginated = response.data.data.pagination as Paginated<IProductCard>;
          setCurrentPage(paginated.current_page);
          setLastPage(paginated.last_page);
        })
        .catch(err => {
          console.error(err);
          if (err.response?.data?.status === 404) {
            setShowNotFoundResults(true);
            setProducts([]);
          }
        })
        .finally(() => {
          setLoadingProducts(false);
        });
    }
  };

  const handleClearSearch = () => {
    setSearchTerm('');
    setProducts([]);
    setShowNotFoundResults(false);
    if (selectedFilter !== null) {
      dispatch(setSelectedFilter(null));
    }
  };

  const maxPageButtons = 5;
  let startPage = Math.max(1, currentPage - Math.floor(maxPageButtons / 2));
  let endPage = Math.min(lastPage, startPage + maxPageButtons - 1);
  if (endPage - startPage + 1 < maxPageButtons) {
    startPage = Math.max(1, endPage - maxPageButtons + 1);
  }
  const pageNumbers: number[] = [];
  for (let i = startPage; i <= endPage; i++) {
    pageNumbers.push(i);
  }

  return (
    <div className="flex-1">
      <form onSubmit={handleSearchSubmit} className="mb-6">
        <div className="relative">
          <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400 dark:text-slate-400">
            <FaSearch className="size-4" />
          </div>
          <input
            ref={searchInputRef}
            id="search"
            name="search"
            className="block w-full pl-10 pr-10 py-2.5 border border-gray-300 dark:border-slate-700 rounded-xl leading-5 bg-white dark:bg-[#151D2C] text-gray-900 dark:text-slate-100 placeholder-gray-500 dark:placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-[#B12C2E]/20 focus:border-[#B12C2E] text-sm shadow-sm transition-all"
            placeholder={t('shop:searchPlaceholder')}
            type="text"
            value={searchTerm}
            onChange={(e) => setSearchTerm(e.target.value)}
          />
          {searchTerm && (
            <button
              type="button"
              onClick={handleClearSearch}
              className="absolute inset-y-0 right-0 pr-3 flex items-center cursor-pointer text-gray-500 dark:text-slate-400 hover:text-gray-800 dark:hover:text-white"
            >
              <IoClose className="size-5" />
            </button>
          )}
        </div>
      </form>

      {isLoading || loadingProducts ? (
        <div className="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 [@media(min-width:1470px)]:grid-cols-4 gap-4">
          {Array.from({ length: 8 }).map((_, index) => (
            <ProductCardPlaceholder key={`placeholder-${index}`} />
          ))}
        </div>
      ) : products.length > 0 && (
        <div className="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 [@media(min-width:1470px)]:grid-cols-4 gap-4">
          {products.map((product) => (
            <ProductCard
              key={product.id}
              id={product.id}
              name={product.name}
              description={product.description}
              price={product.price}
              category={product.category}
              images={{ main_image: product.images.main_image }}
              cheapestVariant={product.cheapestVariant}
              status={product.status}
              slug={product.slug || ''}
              onAddToCart={() => handleAddToCart(product)}
            />
          ))}
        </div>
      )}

      {!isLoading && !loadingProducts && showNotFoundResults && <NotFoundResults searchQuery={searchTerm || ''} />}

      {/* Pagination Controls */}
      {!showNotFoundResults && lastPage > 1 &&
        <div className="flex justify-center items-center mt-8 px-2">
          <div className="flex items-center space-x-1 sm:space-x-2">
            {/* First page */}
            <button
              onClick={() => {
                if (!loadingProducts && currentPage > 1)
                  setCurrentPage(1);
              }}
              disabled={currentPage === 1 || loadingProducts}
              className="p-2 cursor-pointer bg-gray-100 dark:bg-slate-800 text-gray-700 dark:text-slate-300 border border-gray-200 dark:border-slate-700 rounded-lg disabled:opacity-40 hover:bg-gray-200 dark:hover:bg-slate-700 transition-colors flex-shrink-0"
            >
              {lang === "en" ? (
                <MdKeyboardDoubleArrowLeft className="w-4 h-4" />
              ) : (
                <MdKeyboardDoubleArrowRight className="w-4 h-4" />
              )}
            </button>

            {/* Prev arrow */}
            <button
              onClick={() => {
                if (!loadingProducts && currentPage > 1)
                  setCurrentPage(currentPage - 1);
              }}
              disabled={currentPage === 1 || loadingProducts}
              className="p-2 cursor-pointer bg-gray-100 dark:bg-slate-800 text-gray-700 dark:text-slate-300 border border-gray-200 dark:border-slate-700 rounded-lg disabled:opacity-40 hover:bg-gray-200 dark:hover:bg-slate-700 transition-colors flex-shrink-0"
            >
              {lang === "en" ? (
                <RiArrowLeftSLine className="w-4 h-4" />
              ) : (
                <RiArrowRightSLine className="w-4 h-4" />
              )}
            </button>

            {/* Page numbers */}
            <div className="flex items-center space-x-1 overflow-x-auto scrollbar-hide max-w-[200px] mobile:max-w-[300px] sm:max-w-none">
              {pageNumbers.map((page) => {
                let shouldShow = false;
                if (window.innerWidth < 384) {
                  shouldShow = Math.abs(page - currentPage) <= 1;
                } else if (window.innerWidth < 768) {
                  shouldShow = Math.abs(page - currentPage) <= 2;
                } else {
                  shouldShow = Math.abs(page - currentPage) <= 3;
                }

                return shouldShow ? (
                  <button
                    key={page}
                    onClick={() => {
                      if (!loadingProducts) setCurrentPage(page);
                    }}
                    disabled={loadingProducts}
                    className={`px-3 py-1.5 rounded-lg cursor-pointer text-sm font-semibold min-w-[36px] flex-shrink-0 transition-all ${page === currentPage
                      ? "bg-[#B12C2E] text-white shadow-sm"
                      : "bg-gray-100 dark:bg-slate-800 text-gray-700 dark:text-slate-300 border border-gray-200 dark:border-slate-700 hover:bg-gray-200 dark:hover:bg-slate-700"
                      } disabled:opacity-50`}
                  >
                    {page}
                  </button>
                ) : null;
              })}
            </div>

            {/* Next arrow */}
            <button
              onClick={() => {
                if (!loadingProducts && currentPage < lastPage)
                  setCurrentPage(currentPage + 1);
              }}
              disabled={currentPage === lastPage || loadingProducts}
              className="p-2 cursor-pointer bg-gray-100 dark:bg-slate-800 text-gray-700 dark:text-slate-300 border border-gray-200 dark:border-slate-700 rounded-lg disabled:opacity-40 hover:bg-gray-200 dark:hover:bg-slate-700 transition-colors flex-shrink-0"
            >
              {lang === "en" ? (
                <RiArrowRightSLine className="w-4 h-4" />
              ) : (
                <RiArrowLeftSLine className="w-4 h-4" />
              )}
            </button>

            {/* Last page */}
            <button
              onClick={() => {
                if (!loadingProducts && currentPage < lastPage)
                  setCurrentPage(lastPage);
              }}
              disabled={currentPage === lastPage || loadingProducts}
              className="p-2 cursor-pointer bg-gray-100 dark:bg-slate-800 text-gray-700 dark:text-slate-300 border border-gray-200 dark:border-slate-700 rounded-lg disabled:opacity-40 hover:bg-gray-200 dark:hover:bg-slate-700 transition-colors flex-shrink-0"
            >
              {lang === "en" ? (
                <MdKeyboardDoubleArrowRight className="w-4 h-4" />
              ) : (
                <MdKeyboardDoubleArrowLeft className="w-4 h-4" />
              )}
            </button>
          </div>
        </div>
      }
    </div>
  );
};

export default ProductGrid;
