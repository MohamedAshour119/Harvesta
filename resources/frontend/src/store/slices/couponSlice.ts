import { createSlice, type PayloadAction } from '@reduxjs/toolkit';

type Coupon = {
  code: string;
  error: string;
  discount: number;
  isApplied: boolean;
}

const initialState: Coupon = {
  code: '',
  error: '',
  discount: 0,
  isApplied: false
}

export const couponSlice = createSlice({
  name: 'coupon',
  initialState,
  reducers: {
    setCoupon: (state, action: PayloadAction<Partial<Coupon>>) => {
      console.log(action.payload.code);
      
      if (action.payload.code !== undefined) {
        state.code = action.payload.code;
      }
      if (action.payload.error !== undefined) {
        state.error = action.payload.error;
      }
      if (action.payload.discount !== undefined) {
        state.discount = action.payload.discount;
      }
      if (action.payload.isApplied !== undefined) {
        state.isApplied = action.payload.isApplied;
      }
    },
  }
});

// Export actions
export const { setCoupon } = couponSlice.actions;
export default couponSlice.reducer;
