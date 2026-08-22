import React from 'react';
import { useDispatch } from 'react-redux';
import ProductCard from '../../../components/ProductCard';
import ProductCardPlaceholder from '../../../components/ProductCardPlaceholder';
import { addToCart } from '../../../store/slices/cartSlice';
import type { CartItem, IProductCard } from '../../../types';

interface Product extends IProductCard {}

interface ProductGridProps {
  products: Product[];
  isLoading?: boolean;
}

const ProductGrid: React.FC<ProductGridProps> = ({ products, isLoading }: ProductGridProps) => {
  const dispatch = useDispatch();

  const handleAddToCart = (product: Product) => {
    if (product.cheapestVariant && typeof product.cheapestVariant.unit_price_usd === 'string') {
      const cartProductPayload: Omit<CartItem, 'quantity'> = {
        id: product.id,
        productId: product.id,
        name: product.name,
        description: product.description,
        category: {
          id: product.category?.id || 0,
          name: product.category?.name || ""
        },
        price: parseFloat(product.cheapestVariant.unit_price_usd), 
        image: product.images.main_image,
        slug: product.slug,
      };
      dispatch(addToCart(cartProductPayload));
    } else {
      console.error(
        "Product cannot be added to cart: cheapestVariant is missing or its price is malformed.",
        product
      );
    }
  };

  return (
    <div className="flex-1 w-full">
      <div className="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 [@media(min-width:1470px)]:grid-cols-4 gap-4">
        {isLoading ? (
          Array.from({ length: 8 }).map((_, index) => (
            <ProductCardPlaceholder key={`placeholder-${index}`} />
          ))
        ) : (
          products.map((product) => (
            <ProductCard
              key={product.id}
              id={product.id}
              name={product.name}
              description={product.description}
              price={product.price}
              category={product.category}
              images={
                {
                  main_image: product.images.main_image,
                }
              }
              cheapestVariant={product.cheapestVariant}
              status={product.status}
              slug={product.slug}
              onAddToCart={() => handleAddToCart(product)}
            />
          ))
        )}
      </div>
    </div>
  );
};

export default ProductGrid;
