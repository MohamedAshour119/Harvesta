import { createSlice } from '@reduxjs/toolkit';
import type { PayloadAction } from '@reduxjs/toolkit';
import type { CartItem, CartState, IProductCard } from '../../types';
import { REHYDRATE } from 'redux-persist';

const initialState: CartState = {
  items: [],
  subtotal: 0,
  total: 0,
  couponDiscountValue: 0,
  smartBundleCTAs: [],
};

export const cartSlice = createSlice({
  name: 'cart',
  initialState,
  reducers: {
    addToCart: (state, action: PayloadAction<CartItem>) => {
      console.log('[cartSlice/addToCart]', JSON.stringify(action.payload, null, 2));

      const existingItem = state.items.find(item => item.id === action.payload.id);
      console.log('existingItem: ', existingItem);

      if (existingItem && existingItem.quantity && action.payload.quantity === 1) {
        existingItem.quantity += 1;
      } else if (existingItem && existingItem.quantity && action.payload.quantity && action.payload.quantity > 1) {
        existingItem.quantity += action.payload.quantity;
      } else if (!existingItem && !action.payload.quantity) {
        state.items.push({ ...action.payload, quantity: 1 });
      } else {
        state.items.push({ ...action.payload, quantity: action.payload.quantity });
      }

      const subtotal = calculateSubtotal(state.items, state.smartBundleCTAs);
      state.subtotal = subtotal;
      state.total = calculateTotal(subtotal, state.couponDiscountValue);
    },
    removeFromCart: (state, action: PayloadAction<number>) => {
      state.items = state.items.filter(item => item.id !== action.payload);
      const subtotal = calculateSubtotal(state.items, state.smartBundleCTAs);
      state.subtotal = subtotal;
      state.total = calculateTotal(subtotal, state.couponDiscountValue);
    },
    increaseQuantity: (state, action: PayloadAction<number>) => {
      const item = state.items.find(item => item.id === action.payload);
      if (item && item.quantity) {
        if (item.quantity < item.totalQuantity!) {
          item.quantity += 1;
        }

        const subtotal = calculateSubtotal(state.items, state.smartBundleCTAs);
        state.subtotal = subtotal;
        state.total = calculateTotal(subtotal, state.couponDiscountValue);
      }
    },
    decreaseQuantity: (state, action: PayloadAction<number>) => {
      const item = state.items.find(item => item.id === action.payload);
      if (item && item.quantity) {
        if (item.quantity > 1) {
          item.quantity -= 1;
        } else {
          state.items = state.items.filter(i => i.id !== action.payload);
        }
        const subtotal = calculateSubtotal(state.items, state.smartBundleCTAs);
        state.subtotal = subtotal;
        state.total = calculateTotal(subtotal, state.couponDiscountValue);
      }
    },
    batchUpdateCartItemLanguages: (state, action: PayloadAction<IProductCard[]>) => {
      action.payload.forEach(productData => {
        const item = state.items.find(cartItem => cartItem.productId === productData.id);
        if (item) {
          item.name = productData.name;
          if (productData.description !== undefined) {
            item.description = productData.description;
          }
          // Update category name if provided and item has a category object
          if (productData.category && item.category) {
            item.category.name = productData.category.name;
          }
        }
      });
      // Subtotal may change if prices change? Usually not here, but let's be safe
      const subtotal = calculateSubtotal(state.items, state.smartBundleCTAs);
      state.subtotal = subtotal;
      state.total = calculateTotal(subtotal, state.couponDiscountValue);
    },
    setTotal: (state, action: PayloadAction<number>) => {
      state.total = action.payload;
    },
    updateCartItemQuantity: (state, action: PayloadAction<{ id: number; quantity: number }>) => {
      const item = state.items.find(item => item.id === action.payload.id);
      if (item && typeof item.quantity === 'number') {
        item.quantity = action.payload.quantity;
        const subtotal = calculateSubtotal(state.items, state.smartBundleCTAs);
        state.subtotal = subtotal;
        state.total = calculateTotal(subtotal, state.couponDiscountValue);
      }
    },
    setSmartBundleCTAs: (state, action: PayloadAction<any[]>) => {
      state.smartBundleCTAs = action.payload;
      const subtotal = calculateSubtotal(state.items, state.smartBundleCTAs);
      state.subtotal = subtotal;
      state.total = calculateTotal(subtotal, state.couponDiscountValue);
    },
    clearCart: () => {
      return {
        ...initialState
      };
    },
  },
  extraReducers: (builder) => {
    builder.addCase(REHYDRATE, (state, action: any) => {
      if (action.payload && action.payload.cart) {
        const cart = action.payload.cart;
        state.items = cart.items || [];
        state.smartBundleCTAs = cart.smartBundleCTAs || [];
        const subtotal = calculateSubtotal(state.items, state.smartBundleCTAs);
        state.subtotal = subtotal;
        state.total = calculateTotal(subtotal, state.couponDiscountValue);
      }
    });
  }
});

// Helper function to calculate subtotal with per-item bundle discounts
const calculateSubtotal = (items: CartItem[], ctas: any[] = []): number => {
  return items.reduce((total, item) => {
    let itemPrice = item.price;
    const itemQuantity = item.quantity || 0;
    
    // Check if this item has an applied smart bundle discount
    const cta = ctas.find(c => c.product_id === item.productId);
    if (cta && cta.is_applied) {
      const discountFac = (100 - cta.discount_percentage) / 100;
      itemPrice = item.price * discountFac;
    }
    
    return total + (itemPrice * itemQuantity);
  }, 0);
};

const calculateTotal = (subtotal: number, couponDiscountValue: number | null): number => {
  if (!couponDiscountValue) return subtotal;
  const cut_price = (subtotal * couponDiscountValue) / 100;
  return subtotal - cut_price;
};

export const { addToCart, removeFromCart, increaseQuantity, decreaseQuantity, updateCartItemQuantity, batchUpdateCartItemLanguages, setTotal, clearCart, setSmartBundleCTAs } = cartSlice.actions;

export default cartSlice.reducer;
