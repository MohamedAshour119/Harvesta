import { createSlice, type PayloadAction } from '@reduxjs/toolkit';

export interface AuthPopupState {
  isOpen: boolean;
  mode: 'login' | 'register';
}

const initialState: AuthPopupState = {
  isOpen: false,
  mode: 'login',
};

export const isAuthPopupOpenSlice = createSlice({
  name: 'authPopup',
  initialState,
  reducers: {
    setIsAuthPopupOpen: (state, action: PayloadAction<boolean | { isOpen: boolean; mode?: 'login' | 'register' }>) => {
      if (typeof action.payload === 'boolean') {
        state.isOpen = action.payload;
        if (!action.payload) {
          state.mode = 'login';
        }
      } else {
        state.isOpen = action.payload.isOpen;
        if (action.payload.mode) {
          state.mode = action.payload.mode;
        }
      }
    },
    openLoginModal: (state) => {
      state.isOpen = true;
      state.mode = 'login';
    },
    openRegisterModal: (state) => {
      state.isOpen = true;
      state.mode = 'register';
    },
    closeAuthModal: (state) => {
      state.isOpen = false;
      state.mode = 'login';
    }
  }
});

// Export actions
export const { 
  setIsAuthPopupOpen, 
  openLoginModal,
  openRegisterModal,
  closeAuthModal
} = isAuthPopupOpenSlice.actions;

export default isAuthPopupOpenSlice.reducer;