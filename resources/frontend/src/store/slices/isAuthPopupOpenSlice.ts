import { createSlice, type PayloadAction } from '@reduxjs/toolkit';

const initialState = false

export const isAuthPopupOpenSlice = createSlice({
  name: 'auth',
  initialState,
  reducers: {
    setIsAuthPopupOpen: (_state, action: PayloadAction<boolean>) => {
      return action.payload;
    },
  }
});

// Export actions
export const { 
  setIsAuthPopupOpen, 
} = isAuthPopupOpenSlice.actions;


export default isAuthPopupOpenSlice.reducer;