import { createSlice, type PayloadAction } from '@reduxjs/toolkit';

interface EmptyCartModalState {
  isOpen: boolean;
}

const initialState: EmptyCartModalState = {
  isOpen: false,
};

export const emptyCartModalSlice = createSlice({
  name: 'emptyCartModal',
  initialState,
  reducers: {
    openEmptyCartModal: (state) => {
      state.isOpen = true;
    },
    closeEmptyCartModal: (state) => {
      state.isOpen = false;
    },
    setEmptyCartModalOpen: (state, action: PayloadAction<boolean>) => {
      state.isOpen = action.payload;
    },
  },
});

export const { openEmptyCartModal, closeEmptyCartModal, setEmptyCartModalOpen } = emptyCartModalSlice.actions;

export default emptyCartModalSlice.reducer;
