import { useState, useEffect } from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import { useDispatch, useSelector } from 'react-redux';
import type { RootState } from '../../store/store';
import { addToCart } from '../../store/slices/cartSlice';
import { addToFavorites, removeFromFavorites } from '../../store/slices/favoritesSlice';
import { setCurrentProductSlug } from '../../store/slices/chatbotSlice';
import { useTranslation } from 'react-i18next';
import MaxWidthWrapper from '../../components/MaxWidthWrapper';
import ApiClient from '../../ApiClient';
import type { CartItem, IFavoriteItem, IProductCard, IProductVariant } from '../../types';
import { ProductImageGallery } from './components/ProductImageGallery';
import { ProductInfo } from './components/ProductInfo';
import { ProductActions } from './components/ProductActions';
import { RelatedProducts } from './components/RelatedProducts';
import { CustomerReviews } from './components/CustomerReviews';
import { FrequentlyBoughtTogether } from '../../components/FrequentlyBoughtTogether/FrequentlyBoughtTogether';
import { FaCheckCircle, FaTimes, FaExclamationCircle } from 'react-icons/fa';
import { useProtectedAction } from '../../hooks/useProtectedAction';
import FooterSection from '../../components/FooterSection';
import { isVariantAvailable } from '../../utils/productAvailability';
import PageLoader from '../../components/PageLoader';

interface AddToCartState {
  isLoading: boolean;
  isSuccess: boolean;
  showToast: boolean;
  errorToast: string | null;
}

export function ShowProduct() {
  const { t } = useTranslation(['products', 'common']);
  const { slug } = useParams<{ slug: string }>();
  const navigate = useNavigate();
  const dispatch = useDispatch();

  const [product, setProduct] = useState<IProductCard | null>(null);
  const [selectedVariant, setSelectedVariant] = useState<IProductVariant | null>(null)
  const [similarProducts, setSimilarProducts] = useState<IProductCard[]>([])
  const [selectedImage, setSelectedImage] = useState(0);
  const [quantity, setQuantity] = useState(1);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [addToCartState, setAddToCartState] = useState<AddToCartState>({
    isLoading: false,
    isSuccess: false,
    showToast: false,
    errorToast: null
  });
  const [can_add_to_favorites, set_can_add_to_favorites] = useState(false);

  // const { items: cartItems } = useSelector((state: RootState) => state?.cart || { items: [] });
  const favoriteItems = useSelector((state: RootState) => state?.favorites?.items || []);
  const isAuth = useSelector((state: RootState) => state?.auth?.isAuth);
  const user = useSelector((state: RootState) => state?.auth?.user);

  // const cartItem = cartItems.find((item: any) => item.productId === product?.id);

  const [productRecommendations, setProductRecommendations] = useState<IProductCard[]>([]);
  const [fbtRecommendations, setFbtRecommendations] = useState<IProductCard[]>([]);
  const [isLoadingRecommendations, setIsLoadingRecommendations] = useState<boolean>(false);
  const [isLoadingFbt, setIsLoadingFbt] = useState<boolean>(false);

  const {
    handleProtectedAction,
    checkPendingAction,
  } = useProtectedAction();

  useEffect(() => {
    if (isAuth) {
      checkPendingAction();
    }
  }, [isAuth, checkPendingAction]);

  useEffect(() => {
    const isFavorite = favoriteItems?.some(item => item.productId === product?.id && item.variantId === selectedVariant?.id);
    set_can_add_to_favorites(!isFavorite);
  }, [favoriteItems, selectedVariant])

  // Update chatbot context with product slug
  useEffect(() => {
    if (slug) {
      dispatch(setCurrentProductSlug(slug));
    }
    return () => {
      dispatch(setCurrentProductSlug(null));
    };
  }, [slug, dispatch]);

  // Fetch product by slug
  useEffect(() => {
    const fetchProduct = async () => {
      if (!slug) {
        setError('Product slug not provided');
        setLoading(false);
        return;
      }

      setLoading(true);
      setError(null);

      try {
        const response = await ApiClient.products.getProductBySlug(slug);
        console.log(response);

        if (response.data.data) {
          const productData = response.data.data.product;
          const similarProductsData = response.data.data.similar_products;
          const cheapestVariant = response.data.data.product.cheapestVariant;

          setProduct(productData);
          setSimilarProducts(similarProductsData)
          setSelectedVariant(cheapestVariant)

          if (productData.minimum_order_quantity > 1) {
            setQuantity(productData.minimum_order_quantity);
          }

          // Fetch related products based on category
          if (productData.category?.name) {
            fetchRelatedProducts(productData.category.name);
          }
          
          // Fetch AI Recommendations based on the product
          if (productData.id) {
             fetchProductRecommendations(productData.id);
             fetchFbtRecommendations(productData.id);
          }
        } else {
          setError('Product not found');
        }
      } catch (error: any) {
        console.error('Error fetching product:', error);
        setError(error.response?.data?.message || 'Failed to load product');
      } finally {
        setLoading(false);
      }
    };

    fetchProduct();
  }, [slug]);

  // Scroll to top on component mount or slug change
  useEffect(() => {
    window.scrollTo(0, 0);
  }, [slug]);

  const fetchRelatedProducts = async (categoryName: string) => {
    try {
      const response = await ApiClient.shop.getProductsByCategoryName(categoryName);
      if (response.data.success && response.data.data?.products) {
        // Filter out the current product and take first 4
        const filtered = response.data.data.products
          .filter((p: any) => p.id !== product?.id)
          .slice(0, 4);
        setSimilarProducts(filtered);
      }
    } catch (error) {
      console.error('Error fetching related products:', error);
    }
  };

  const fetchProductRecommendations = async (productId: number) => {
    setIsLoadingRecommendations(true);
    try {
      const response = await ApiClient.recommendations.getForItem(productId, 8);
      if (response.data.data) {
        // Filter out the current product itself
        const filtered = response.data.data.filter((p: any) => p.id !== productId);
        setProductRecommendations(filtered);
      }
    } catch (error) {
      console.error('Error fetching product recommendations:', error);
    } finally {
      setIsLoadingRecommendations(false);
    }
  };

  const fetchFbtRecommendations = async (productId: number) => {
    setIsLoadingFbt(true);
    try {
      const response = await ApiClient.recommendations.getFrequentForItem(productId, 2);
      if (response.data.data) {
        const filtered = response.data.data.filter((p: any) => p.id !== productId);
        setFbtRecommendations(filtered);
      }
    } catch (error) {
      console.error('Error fetching FBT recommendations:', error);
    } finally {
      setIsLoadingFbt(false);
    }
  };

  const handleAddToCart = async () => {
    if (product && isVariantAvailable(selectedVariant) && selectedVariant?.total_quantity! > 0 && !addToCartState.isLoading) {

      const totalPrice = (selectedVariant?.price_after_discount || 0) * quantity;
      if (product.min_order_value && totalPrice < product.min_order_value) {
        setAddToCartState({
          isLoading: false,
          isSuccess: false,
          showToast: false,
          errorToast: t('showProduct:actions.minOrderValueRequired', { value: product.min_order_value })
        });

        setTimeout(() => {
          setAddToCartState(prev => ({ ...prev, errorToast: null }));
        }, 5000);
        return false;
      }

      // Start loading state
      setAddToCartState({ isLoading: true, isSuccess: false, showToast: false, errorToast: null });

      const cartPayload: CartItem = {
        id: selectedVariant?.id!, // Variant ID is the primary ID for cart item
        productId: product.id, // Product ID
        name: product.name,
        slug: product.slug,
        image: selectedVariant?.main_image ? selectedVariant?.main_image : product.images.main_image || '',
        price: selectedVariant?.price_after_discount ? selectedVariant?.price_after_discount : 0,
        originalPrice: selectedVariant?.unit_price_usd, // Original price, if applicable
        description: product.description, // Product description or additional info
        variantName: selectedVariant?.variant_name,
        variantValue: selectedVariant?.variant_value,
        category: {
          id: product.category?.id || 0,
          name: product.category?.name || ''
        },
        quantity: quantity,
        totalQuantity: selectedVariant?.total_quantity,
      };

      try {
        // Simulate API delay for smooth animation
        await new Promise(resolve => setTimeout(resolve, 800));

        dispatch(addToCart(cartPayload));

        // Track cart addition on backend 
        handleProtectedAction('cart', () => {
             ApiClient.recommendations.trackCartAddition(
                product.id,
                cartPayload.price
             );
        }, {
             productId: product.id,
             price: cartPayload.price,
        });

        // Success state
        setAddToCartState({ isLoading: false, isSuccess: true, showToast: true, errorToast: null });

        // Reset success state after animation
        setTimeout(() => {
          setAddToCartState({ isLoading: false, isSuccess: false, showToast: false, errorToast: null });
        }, 3000);

        return true;
      } catch (error) {
        // Reset on error
        setAddToCartState({ isLoading: false, isSuccess: false, showToast: false, errorToast: null });
        console.error('Error adding to cart:', error);
        return false;
      }
    }
    return false;
  };

  const handleBuyNow = async () => {
    const success = await handleAddToCart();
    if (success) {
      navigate('/cart');
    }
  };

  const addProductToFavorites = () => {
    if (!product || !selectedVariant) return;
    dispatch(addToFavorites({
      productName: product.name,
      slug: product.slug,
      price: selectedVariant?.price_after_discount || 0,
      originalPrice: selectedVariant?.unit_price_usd || 0,
      image: selectedVariant?.main_image ? selectedVariant?.main_image : product?.images?.main_image || '',
      productId: product.id,
      variantId: selectedVariant?.id,
      variantName: selectedVariant?.variant_name,
      variantValue: selectedVariant?.variant_value,
      category: {
        id: product.category?.id || 0,
        name: product.category?.name || ''
      }
    }));

    const event = new CustomEvent('favoriteStatusChanged', {
      detail: { productId: product.id, variantId: selectedVariant?.id, canAddToFavorites: false }
    });
    window.dispatchEvent(event);

    if (isAuth) {
      ApiClient.wishlist.addToWishlist(product.id, selectedVariant?.id);
    }
  }

  const removeProductFromFavorites = () => {
    if (!product || !selectedVariant) return;
    if (!can_add_to_favorites) {
      dispatch(removeFromFavorites({ productId: product.id, variantId: selectedVariant?.id }));

      const event = new CustomEvent('favoriteStatusChanged', {
        detail: { productId: product.id, variantId: selectedVariant?.id, canAddToFavorites: true }
      });
      window.dispatchEvent(event);

      if (isAuth) {
        ApiClient.wishlist.removeFromWishlist(product.id, selectedVariant?.id);
      }

    }
  }

  const handleToggleFavorite = () => {
    if (can_add_to_favorites && product && selectedVariant) {
      const favoriteItemPayload: IFavoriteItem = {
        productId: product?.id,
        variantId: selectedVariant?.id,
        productName: product?.name,
        slug: product?.slug,
        category: {
          id: product?.category?.id || 0,
          name: product?.category?.name || ''
        },
        variantName: selectedVariant?.variant_name,
        variantValue: selectedVariant?.variant_value,
        price: selectedVariant?.price_after_discount || 0,
        originalPrice: selectedVariant?.unit_price_usd || 0,
        image: selectedVariant?.main_image ? selectedVariant?.main_image : product?.images?.main_image || '',
      };

      handleProtectedAction('wishlist', addProductToFavorites, {
        productId: product?.id,
        variantId: selectedVariant?.id,
        action: 'add',
        product: favoriteItemPayload,
      });

    } else {
      handleProtectedAction('wishlist', removeProductFromFavorites, {
        productId: product?.id,
        variantId: selectedVariant?.id,
        action: 'remove',
      });
    }
  };

  const handleVariantSelect = (variant: IProductVariant) => {
    setSelectedVariant(variant);
    // Reset selected image when variant changes
    setSelectedImage(0);
  };

  // const handleRatingSubmitted = async () => {
  //   // Refresh product data to get updated ratings
  //   if (slug) {
  //     await fetchProduct(slug);
  //   }
  // };

  const getSelectedVariantRatingData = () => {
    if (!selectedVariant) {
      return null;
    }

    // Use the rating_stats provided by the backend if available
    if (product?.rating_stats) {
      return {
        ratings: product?.ratings || [],
        average_rating: product?.rating_stats.average_rating,
        total_ratings: product?.rating_stats.total_ratings,
        rating_breakdown: product?.rating_stats.rating_breakdown.map(item => ({
          stars: item.star,
          count: item.count,
          percentage: item.percentage,
        })),
      };
    }

    // Fallback to manual calculation if rating_stats is not available
    if (!product?.ratings) {
      return {
        ratings: [],
        average_rating: 0,
        total_ratings: 0,
        rating_breakdown: [],
      };
    }

    const ratings = product?.ratings;
    const totalRatings = ratings.length;

    if (totalRatings === 0) {
      return {
        ratings: [],
        average_rating: 0,
        total_ratings: 0,
        rating_breakdown: [],
      };
    }

    // Calculate average rating
    const averageRating = ratings.reduce((sum, rating) => sum + rating.rating, 0) / totalRatings;

    // Calculate rating breakdown
    const ratingCounts = { 1: 0, 2: 0, 3: 0, 4: 0, 5: 0 };
    ratings.forEach(rating => {
      const roundedRating = Math.round(rating.rating);
      if (roundedRating >= 1 && roundedRating <= 5) {
        ratingCounts[roundedRating as keyof typeof ratingCounts]++;
      }
    });

    const ratingBreakdown = Object.entries(ratingCounts).map(([stars, count]) => ({
      stars: parseInt(stars),
      count,
      percentage: totalRatings > 0 ? Math.round((count / totalRatings) * 100) : 0,
    })).reverse(); // Show 5 stars first

    return {
      ratings,
      average_rating: averageRating,
      total_ratings: totalRatings,
      rating_breakdown: ratingBreakdown,
    };
  };

  // Build dynamic image array based on selected variant and product images
  const getDisplayImages = () => {
    const images: string[] = [];
    const uniqueUrls = new Set<string>();

    const addImage = (url: string) => {
      if (url && !uniqueUrls.has(url)) {
        uniqueUrls.add(url);
        images.push(url);
      }
    };

    // 1. Selected variant's image first (main/hero image)
    if (selectedVariant?.main_image) {
      addImage(selectedVariant.main_image);
    }

    // 2. Other variants' images (so user can see all variant visuals)
    if (product?.variants && product.variants.length > 1) {
      for (const variant of product.variants) {
        if (variant.id !== selectedVariant?.id && variant.main_image) {
          addImage(variant.main_image);
        }
      }
    }

    // 3. Product main image (only if no variant images exist at all)
    if (images.length === 0 && product?.images?.main_image) {
      addImage(product.images.main_image);
    }

    // 4. Product other images
    if (product?.images?.other_images) {
      for (const img of product.images.other_images) {
        addImage(img);
      }
    }

    return images;
  };

  if (loading) {
    return (
      <MaxWidthWrapper>
        <PageLoader message={t('products:loadingProduct', 'جاري تجهيز تفاصيل المنتج...')} />
      </MaxWidthWrapper>
    );
  }

  if (error) {
    return (
      <MaxWidthWrapper>
        <div className="text-center py-20">
          <h2 className="text-2xl font-bold text-gray-800 mb-4">{error}</h2>
          <button
            onClick={() => navigate('/all-products')}
            className="bg-yellow text-white px-6 py-2 rounded-lg hover:bg-yellow-600 transition-colors cursor-pointer"
          >
            Back to Shop
          </button>
        </div>
      </MaxWidthWrapper>
    );
  }

  if (!product) {
    return (
      <MaxWidthWrapper>
        <div className="text-center py-20">
          <h2 className="text-2xl font-bold text-gray-800 mb-4">Product not found</h2>
          <button
            onClick={() => navigate('/all-products')}
            className="bg-yellow text-white px-6 py-2 rounded-lg hover:bg-yellow-600 transition-colors cursor-pointer"
          >
            Back to Shop
          </button>
        </div>
      </MaxWidthWrapper>
    );
  }

  return (
    <div className="min-h-screen flex flex-col justify-between w-full">
      <MaxWidthWrapper className="flex-1 w-full">
        {/* Toast Notification */}
        <div className={`fixed top-4 right-4 z-50 transform transition-all duration-500 ease-in-out ${addToCartState.showToast
          ? 'translate-x-0 opacity-100'
          : 'translate-x-full opacity-0'
          }`}>
          <div className="bg-white border-l-4 border-green-500 rounded-lg shadow-lg p-4 min-w-[300px]">
            <div className="flex items-center">
              <FaCheckCircle className="text-green-500 text-xl mr-3" />
              <div className="flex-1">
                <p className="text-sm font-medium text-gray-900">{t('showProduct:actions.addedToCart')}</p>
                <p className="text-xs text-gray-600 mt-1">{product?.name}</p>
              </div>
              <button
                onClick={() => setAddToCartState(prev => ({ ...prev, showToast: false }))}
                className="ml-4 text-gray-400 hover:text-gray-600"
              >
                <FaTimes />
              </button>
            </div>
          </div>
        </div>

        {/* Error Toast Notification */}
        <div className={`fixed top-4 right-4 z-50 transform transition-all duration-500 ease-in-out ${addToCartState.errorToast
          ? 'translate-x-0 opacity-100'
          : 'translate-x-full opacity-0'
          }`}>
          <div className="bg-white border-l-4 border-red-500 rounded-lg shadow-lg p-4 min-w-[300px]">
            <div className="flex items-center">
              <FaExclamationCircle className="text-red-500 text-xl mr-3" />
              <div className="flex-1">
                <p className="text-sm font-medium text-gray-900">{addToCartState.errorToast}</p>
              </div>
              <button
                onClick={() => setAddToCartState(prev => ({ ...prev, errorToast: null }))}
                className="ml-4 text-gray-400 hover:text-gray-600"
              >
                <FaTimes />
              </button>
            </div>
          </div>
        </div>

        <div className="py-8 space-y-16">
          {/* Product Details Section */}
          <div className="grid grid-cols-1 tablet-small:grid-cols-2 tablet-small:gap-6 lg:gap-12">
            {/* Product Images */}
            <ProductImageGallery
              images={getDisplayImages()}
              productName={product.name}
              selectedImage={selectedImage}
              onImageSelect={setSelectedImage}
            />

            {/* Product Info and Actions */}
            <div className="space-y-8 relative">
              <ProductInfo
                product={product}
                variantRatingData={getSelectedVariantRatingData()}
                selectedVariant={selectedVariant}
                onVariantSelect={handleVariantSelect}
              />
              <ProductActions
                product={product}
                selectedVariant={selectedVariant}
                quantity={quantity}
                isFavorite={!can_add_to_favorites}
                isAgent={user?.is_agent || false}
                agentCode={user?.code || ''}
                onQuantityChange={setQuantity}
                onAddToCart={handleAddToCart}
                onBuyNow={handleBuyNow}
                onToggleFavorite={handleToggleFavorite}
                addToCartState={addToCartState}
              />
            </div>
          </div>

          {/* Customer Reviews Section */}
          <CustomerReviews
            variantRatingData={getSelectedVariantRatingData()}
            productId={product?.id || null}
          />

          {/* AI Recommendations */}
          <div className="mt-8">
              {product && !isLoadingFbt && fbtRecommendations.length > 0 && (
                <FrequentlyBoughtTogether 
                  mainProduct={product}
                  selectedVariant={selectedVariant}
                  recommendations={fbtRecommendations}
                />
              )}
              
              {(isLoadingRecommendations || productRecommendations.length > 0) && (
                <RelatedProducts
                  title={t('common:recommendations.similar', 'Similar Products')}
                  relatedProducts={productRecommendations}
                  isLoading={isLoadingRecommendations}
                />
              )}
          </div>

          {/* Related Products (Fallback if no AI recommendations) */}
          {productRecommendations.length === 0 && !isLoadingRecommendations && similarProducts.length > 0 && (
            <RelatedProducts
              relatedProducts={similarProducts}
            />
          )}
        </div>
      </MaxWidthWrapper>
      <FooterSection />
    </div>
  );
}
