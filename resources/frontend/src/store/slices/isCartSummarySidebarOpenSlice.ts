import { createSlice, type PayloadAction } from '@reduxjs/toolkit';

const initialState = false

export const isCartSummarySidebarOpenSlice = createSlice({
  name: 'cartSummarySidebar',
  initialState,
  reducers: {
    setIsCartSummarySidebarOpen: (_state, action: PayloadAction<boolean>) => {
      return action.payload;
    },
  }
});

// Export actions
export const { 
  setIsCartSummarySidebarOpen, 
} = isCartSummarySidebarOpenSlice.actions;


export default isCartSummarySidebarOpenSlice.reducer;