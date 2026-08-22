import { createSlice, type PayloadAction } from '@reduxjs/toolkit';

const initialState = false

export const isHeaderSidebarOpenSlice = createSlice({
  name: 'headerSidebar',
  initialState,
  reducers: {
    setIsHeaderSidebarOpen: (_state, action: PayloadAction<boolean>) => {
      return action.payload;
    },
  }
});

// Export actions
export const { 
  setIsHeaderSidebarOpen, 
} = isHeaderSidebarOpenSlice.actions;


export default isHeaderSidebarOpenSlice.reducer;
