import React from 'react';
import { useDispatch } from 'react-redux';
import ProductCard from '../../../components/ProductCard';
import { addToCart } from '../../../store/slices/cartSlice';
import type { CartItem, IProductCard } from '../../../types';

interface Product extends IProductCard {}

interface ProductGridProps {
  products: Product[];
  searchQuery: string;
}

import { useTranslation } from 'react-i18next';

interface Product extends IProductCard {}

interface ProductGridProps {
  products: Product[];
  searchQuery: string;
}

const ProductGrid: React.FC<ProductGridProps> = ({ products, searchQuery }: ProductGridProps) => {
  const dispatch = useDispatch();
  const { t } = useTranslation(['common']);

  const handleAddToCart = (product: Product) => {
    if (product.cheapestVariant) {
      const priceVal = typeof product.cheapestVariant.unit_price_usd === 'string' 
        ? parseFloat(product.cheapestVariant.unit_price_usd)
        : (typeof product.cheapestVariant.unit_price_usd === 'number' ? product.cheapestVariant.unit_price_usd : 0);

      const cartProductPayload: Omit<CartItem, 'quantity'> = {
        productId: product.id,
        id: product.cheapestVariant.id,
        slug: product.slug,
        variantName: product.cheapestVariant.variant_name,
        variantValue: product.cheapestVariant.variant_value,
        name: product.name,
        description: product.description,
        category: {
          id: product.category?.id || 0,
          name: product.category?.name || ""
        },
        price: priceVal, 
        image: product.images.main_image,
      };
      dispatch(addToCart(cartProductPayload));
    }
  };

  return (
    <div className="flex-1">
      <h2 className="text-xl mobile-large:text-xl tablet:text-2xl font-bold mb-8 text-gray-900 dark:text-slate-100">
        {t('common:search.resultsFor', 'Searched for: ')} <span className="text-[#B12C2E] dark:text-[#E03E41]">"{searchQuery}"</span>
      </h2>
      <div className="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 [@media(min-width:1470px)]:grid-cols-4 gap-4">
        {products.map((product) => (
          <ProductCard
            key={product.id}
            id={product.id}
            name={product.name}
            price={product.price}
            slug={product.slug}
            category={product.category}
            images={
              {
                main_image: product.images.main_image,
              }
            }
            cheapestVariant={product.cheapestVariant}
            status={product.status}
            onAddToCart={() => handleAddToCart(product)}
            isAvailable={product.status}
            stock={10} 
          />
        ))}
      </div>
    </div>
  );
};

export default ProductGrid;
