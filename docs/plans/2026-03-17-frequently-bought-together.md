# Frequently Bought Together Implementation Plan

> **For Antigravity:** REQUIRED WORKFLOW: Use `.agent/workflows/execute-plan.md` to execute this plan in single-flow mode.

**Goal:** Show complementary products on the product page (Amazon-style) and allow the customer to add them to the cart with one click along with the main product.

**Architecture:** Create a new React component `FrequentlyBoughtTogether.tsx` that utilizes the existing Recombee AI recommendations fetched in `ShowProduct.tsx`. It will display the main product and the top 2 recommended products with checkboxes. An "Add selected to cart" button will dispatch multiple `addToCart` actions to the Redux store sequentially.

**Tech Stack:** React 19, TypeScript, Redux Toolkit, Tailwind CSS / Custom CSS, i18next

---

### Task 1: Create FrequentlyBoughtTogether Component

**Files:**
- Create: `resources/frontend/src/components/FrequentlyBoughtTogether/FrequentlyBoughtTogether.tsx`
- Create: `resources/frontend/src/components/FrequentlyBoughtTogether/frequently-bought-together.css`

**Step 1: Write the basic component structure**

```tsx
import React, { useState, useEffect } from 'react';
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
        quantity: mainProduct.minimum_order_quantity > 0 ? mainProduct.minimum_order_quantity : 1,
        totalQuantity: selectedVariant.total_quantity,
      };
      dispatch(addToCart(mainCartPayload));
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
            quantity: product.minimum_order_quantity > 0 ? product.minimum_order_quantity : 1,
            totalQuantity: variant.total_quantity,
         };
         dispatch(addToCart(cartPayload));
      }
    }
    
    setIsAdding(false);
    navigate('/cart');
  };

  const allSelectedItems = [mainProduct, ...bundleProducts];

  return (
    <div className="fbt-container mt-12 bg-gray-50 p-6 rounded-2xl border border-gray-100">
      <h3 className="text-xl font-bold text-gray-800 mb-6">{t('showProduct:fbt.title', 'Frequently Bought Together')}</h3>
      
      <div className="flex flex-col lg:flex-row gap-8">
        {/* Images Row */}
        <div className="flex items-center gap-4 fbt-images-wrapper">
          <div className="fbt-image-card">
            <img src={selectedVariant?.main_image || mainProduct.images?.main_image} alt={mainProduct.name} />
          </div>
          
          {bundleProducts.map(product => (
            <React.Fragment key={product.id}>
              <div className="fbt-plus-icon">+</div>
              <div className="fbt-image-card">
                 <Link to={`/product/${product.slug}`}>
                   <img src={product.images?.main_image} alt={product.name} />
                 </Link>
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
                 className="mt-1 w-5 h-5 text-pink-500 rounded border-gray-300 focus:ring-pink-500"
              />
              <div>
                <span className="font-semibold text-gray-800 group-hover:text-pink-600 transition-colors">
                  {t('showProduct:fbt.thisItem', 'This item')}: {mainProduct.name}
                </span>
                <div className="text-pink-600 font-bold">${selectedVariant?.price_after_discount}</div>
              </div>
            </label>

            {/* Bundle Checkboxes */}
            {bundleProducts.map(product => (
               <label key={product.id} className="flex items-start gap-3 cursor-pointer group">
                 <input 
                    type="checkbox" 
                    checked={selectedItems.includes(product.id)}
                    onChange={() => toggleSelection(product.id)}
                    className="mt-1 w-5 h-5 text-pink-500 rounded border-gray-300 focus:ring-pink-500"
                 />
                 <div>
                   <Link to={`/product/${product.slug}`} className="font-semibold text-gray-800 hover:text-pink-600 transition-colors">
                     {product.name}
                   </Link>
                   <div className="text-pink-600 font-bold">${product.cheapestVariant?.price_after_discount}</div>
                 </div>
               </label>
            ))}
          </div>

          <div className="border-t border-gray-200 pt-4 mt-auto">
            <div className="flex items-end gap-2 mb-4">
               <span className="text-gray-600">{t('showProduct:fbt.totalPrice', 'Total Price')}:</span>
               <span className="text-3xl font-bold text-gray-900">${calculateTotal().toFixed(2)}</span>
            </div>
            <button 
              onClick={handleAddAllToCart}
              disabled={selectedItems.length === 0 || isAdding}
              className="w-full sm:w-auto bg-pink-500 text-white px-8 py-3 rounded-xl font-bold hover:bg-pink-600 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
            >
              {isAdding ? t('common:publishing', 'Adding...') : t('showProduct:fbt.addSelected', 'Add Selected to Cart')}
            </button>
          </div>
        </div>
      </div>
    </div>
  );
};
```

**Step 2: Add styles `frequently-bought-together.css`**

```css
.fbt-image-card {
    width: 140px;
    height: 140px;
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    overflow: hidden;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 8px;
    transition: transform 0.2s, box-shadow 0.2s;
}

.fbt-image-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.05);
}

.fbt-image-card img {
    max-width: 100%;
    max-height: 100%;
    object-fit: contain;
}

.fbt-plus-icon {
    font-size: 24px;
    color: #9ca3af;
    font-weight: 300;
}

@media (max-width: 640px) {
    .fbt-images-wrapper {
        flex-wrap: wrap;
        justify-content: center;
    }
}
```

### Task 2: Integrate into Product Page

**Files:**
- Modify: `resources/frontend/src/pages/ShowProduct/ShowProduct.tsx`

**Step 1: Import the component**
```tsx
import { FrequentlyBoughtTogether } from '../../components/FrequentlyBoughtTogether/FrequentlyBoughtTogether';
```

**Step 2: Render it**
Right after the Product Details section (around line `</div> {/* End of grid grid-cols-1 */}`), render the new component if recommendations are available and have loaded:

```tsx
        {/* Frequently Bought Together */}
        {product && !isLoadingRecommendations && productRecommendations.length > 0 && (
          <FrequentlyBoughtTogether 
            mainProduct={product}
            selectedVariant={selectedVariant}
            recommendations={productRecommendations}
          />
        )}
```

### Task 3: Add Locales

**Files:**
- Modify: `resources/frontend/src/i18n/locales/en/showProduct.json`
- Modify: `resources/frontend/src/i18n/locales/ar/showProduct.json`

**Step 1:** Add EN translations under a new `fbt` key:
```json
  "fbt": {
    "title": "Frequently Bought Together",
    "thisItem": "This item",
    "totalPrice": "Total Price",
    "addSelected": "Add Selected to Cart"
  }
```

**Step 2:** Add AR translations under a new `fbt` key:
```json
  "fbt": {
    "title": "منتجات مرتبطة ببعضها",
    "thisItem": "هذا المنتج",
    "totalPrice": "السعر الإجمالي",
    "addSelected": "إضافة المنتجات المحددة للسلة"
  }
```
