import React, { useState } from 'react';
import { useDispatch } from 'react-redux';
import { useTranslation } from 'react-i18next';
import { Link, useNavigate } from 'react-router-dom';
import { addToCart } from '../../store/slices/cartSlice';
import type { IProductCard, IProductVariant, CartItem } from '../../types';
import './frequently-bought-together.css';

interface Props {
  mainProduct: IProductCard;
  selectedVariant: IProductVariant | null;
  recommendations: IProductCard[];
}

export const FrequentlyBoughtTogether: React.FC<Props> = ({ mainProduct, selectedVariant, recommendations }) => {
  const { t } = useTranslation(['showProduct', 'common']);
  const dispatch = useDispatch();
  const navigate = useNavigate();

  // We only take up to 2 recommendations to avoid clutter
  const bundleProducts = recommendations.slice(0, 2);

  if (bundleProducts.length === 0) return null;

  // Initialize with main product ID. When bundle products are clicked they can be added as well
  const [selectedItems, setSelectedItems] = useState(
    [mainProduct.id, ...bundleProducts.map(p => p.id)]
  );
  const [isAdding, setIsAdding] = useState(false);

  const toggleSelection = (id: number) => {
    if (selectedItems.includes(id)) {
      setSelectedItems(selectedItems.filter(itemId => itemId !== id));
    } else {
      setSelectedItems([...selectedItems, id]);
    }
  };

  // Sync selected items when recommendations arrive
  React.useEffect(() => {
    if (bundleProducts.length > 0) {
      setSelectedItems([mainProduct.id, ...bundleProducts.map(p => p.id)]);
    }
  }, [mainProduct.id, recommendations]);

  const calculateTotal = () => {
    let total = 0;
    if (selectedItems.includes(mainProduct.id)) {
      total += selectedVariant?.price_after_discount || 0;
    }

    bundleProducts.forEach(product => {
      if (selectedItems.includes(product.id)) {
        // Assume cheapest variant for recommendations
        total += product.cheapestVariant?.price_after_discount || 0;
      }
    });

    return total;
  };

  const handleAddAllToCart = async () => {
    setIsAdding(true);

    // Add Main Product
    if (selectedItems.includes(mainProduct.id) && selectedVariant) {
      const mainCartPayload: CartItem = {
        id: selectedVariant.id,
        productId: mainProduct.id,
        name: mainProduct.name,
        slug: mainProduct.slug,
        image: selectedVariant.main_image || mainProduct.images?.main_image || '',
        price: selectedVariant.price_after_discount || 0,
        originalPrice: selectedVariant.unit_price_usd,
        description: mainProduct.description,
        variantName: selectedVariant.variant_name,
        variantValue: selectedVariant.variant_value,
        category: { id: mainProduct.category?.id || 0, name: mainProduct.category?.name || '' },
        quantity: mainProduct.minimum_order_quantity ? (mainProduct.minimum_order_quantity > 0 ? mainProduct.minimum_order_quantity : 1) : 1,
        totalQuantity: selectedVariant.total_quantity,
      };

      try {
        await new Promise(resolve => setTimeout(resolve, 300));
        dispatch(addToCart(mainCartPayload));
      } catch (e) {
        console.error("Failed to add main product", e);
      }
    }

    // Add Bundle Products
    for (const product of bundleProducts) {
      if (selectedItems.includes(product.id) && product.cheapestVariant) {
        const variant = product.cheapestVariant;
        const cartPayload: CartItem = {
          id: variant.id,
          productId: product.id,
          name: product.name,
          slug: product.slug,
          image: variant.main_image || product.images?.main_image || '',
          price: variant.price_after_discount || 0,
          originalPrice: variant.unit_price_usd,
          description: product.description,
          variantName: variant.variant_name,
          variantValue: variant.variant_value,
          category: { id: product.category?.id || 0, name: product.category?.name || '' },
          quantity: product.minimum_order_quantity ? (product.minimum_order_quantity > 0 ? product.minimum_order_quantity : 1) : 1,
          totalQuantity: variant.total_quantity,
        };
        try {
          await new Promise(resolve => setTimeout(resolve, 300));
          dispatch(addToCart(cartPayload));
        } catch (e) {
          console.error("Failed to add bundle product", e);
        }
      }
    }

    setIsAdding(false);
    navigate('/cart');
  };

  return (
    <div className="fbt-container mt-12 bg-gray-50 p-6 rounded-2xl border border-gray-100">
      <h3 className="text-xl font-bold text-gray-800 mb-6">{t('showProduct:fbt.title', 'Frequently Bought Together')}</h3>

      <div className="flex flex-col lg:flex-row gap-8">
        {/* Images Row */}
        <div className="flex items-center gap-4 fbt-images-wrapper">
          <div className="fbt-image-card relative cursor-pointer group" onClick={() => toggleSelection(mainProduct.id)}>
            <div className="absolute top-2 left-2 z-10">
              <input
                type="checkbox"
                checked={selectedItems.includes(mainProduct.id)}
                onChange={() => { }}
                className="w-4 h-4 text-[#B12C2E] rounded border-gray-300 pointer-events-none checked:bg-[#B12C2E]"
              />
            </div>
            <img src={selectedVariant?.main_image || mainProduct.images?.main_image} alt={mainProduct.name} className={!selectedItems.includes(mainProduct.id) ? 'opacity-40 grayscale' : ''} />
          </div>

          {bundleProducts.map(product => (
            <React.Fragment key={product.id}>
              <div className="fbt-plus-icon">+</div>
              <div className="fbt-image-card relative cursor-pointer group" onClick={() => toggleSelection(product.id)}>
                <div className="absolute top-2 left-2 z-10">
                  <input
                    type="checkbox"
                    checked={selectedItems.includes(product.id)}
                    onChange={() => { }}
                    className="w-4 h-4 text-[#B12C2E] rounded border-gray-300 pointer-events-none checked:bg-[#B12C2E]"
                  />
                </div>
                <img src={product.images?.main_image} alt={product.name} className={!selectedItems.includes(product.id) ? 'opacity-40 grayscale' : ''} />
              </div>
            </React.Fragment>
          ))}
        </div>

        {/* Details & Actions */}
        <div className="flex-1 flex flex-col justify-center">
          <div className="space-y-3 mb-6">
            {/* Main Product Checkbox */}
            <label className="flex items-start gap-3 cursor-pointer group">
              <input
                type="checkbox"
                checked={selectedItems.includes(mainProduct.id)}
                onChange={() => toggleSelection(mainProduct.id)}
                className="mt-1 w-5 h-5 text-[#B12C2E] rounded border-gray-300 focus:ring-[#B12C2E]/30"
              />
              <div>
                <span className="font-semibold text-gray-800 group-hover:text-[#B12C2E] transition-colors">
                  {t('showProduct:fbt.thisItem', 'This item')}: {mainProduct.name}
                </span>
                <div className="text-pink font-bold flex items-center gap-1">
                  <span className="text-sm font-bold mx-1">EGP</span>
                  {selectedVariant?.price_after_discount?.toFixed(2)}
                </div>
              </div>
            </label>

            {/* Bundle Checkboxes */}
            {bundleProducts.map(product => (
              <label key={product.id} className="flex items-start gap-3 cursor-pointer group">
                <input
                  type="checkbox"
                  checked={selectedItems.includes(product.id)}
                  onChange={() => toggleSelection(product.id)}
                  className="mt-1 w-5 h-5 text-[#B12C2E] rounded border-gray-300 focus:ring-[#B12C2E]/30"
                />
                <div>
                  <Link to={`/products/${product.slug}`} className="font-semibold text-gray-800 hover:text-[#B12C2E] transition-colors" onClick={(e) => e.stopPropagation()}>
                    {product.name}
                  </Link>
                  <div className="text-pink font-bold flex items-center gap-1">
                    <span className="text-sm font-bold mx-1">EGP</span>
                    {product.cheapestVariant?.price_after_discount?.toFixed(2)}
                  </div>
                </div>
              </label>
            ))}
          </div>

          <div className="border-t border-gray-200 pt-4 mt-auto">
            <div className="flex items-center gap-2 mb-4">
              <span className="text-gray-600">{t('showProduct:fbt.totalPrice', 'Total Price')}:</span>
              <span className="text-3xl font-bold text-gray-900 flex items-center gap-1">
                <span className="text-sm font-bold mx-1">EGP</span>
                {calculateTotal().toFixed(2)}
              </span>
            </div>
            <button
              onClick={handleAddAllToCart}
              disabled={selectedItems.length === 0 || isAdding}
              className="w-full sm:w-auto cursor-pointer bg-[#B12C2E] text-white px-8 py-3 rounded-xl font-bold hover:bg-[#972426] transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
            >
              {isAdding ? t('common:publishing', 'Adding...') : t('showProduct:fbt.addSelected', 'Add Selected to Cart')}
            </button>
          </div>
        </div>
      </div>
    </div>
  );
};
