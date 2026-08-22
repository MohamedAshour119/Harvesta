import { createTransform } from 'redux-persist';
import type { CartState } from '../types';

// This transform ensures only the 'items' array from the cart state is persisted.
// Subtotal and total are calculated values and should not be stored, as they can become stale.
export const cartTransform = createTransform(
  // transform state on its way to being serialized and persisted.
  (inboundState: CartState) => {
    return { items: inboundState.items };
  },
  // transform state being rehydrated
  (outboundState: { items: CartState['items'] }) => {
    // On rehydration, we only have items. Subtotal and total will be recalculated by the slice reducer.
    return { ...outboundState, subtotal: 0, total: 0, couponDiscountValue: 0 };
  },
  // define which reducer this transform gets called for.
  { whitelist: ['cart'] }
);
