import { createSlice, type PayloadAction } from '@reduxjs/toolkit';

const initialState = {
  visible: false,
  message: '',
}

export const isMinimumOrderValuePopupOpenSlice = createSlice({
  name: 'minimumOrderValuePopup',
  initialState,
  reducers: {
    setIsMinimumOrderValuePopupOpen: (_state, action: PayloadAction<{visible: boolean, message: string}>) => {
      return action.payload;
    },
  }
});

// Export actions
export const { 
  setIsMinimumOrderValuePopupOpen,
} = isMinimumOrderValuePopupOpenSlice.actions;


export default isMinimumOrderValuePopupOpenSlice.reducer;