import { createSlice, type PayloadAction } from '@reduxjs/toolkit';

export interface AuthPopupState {
  isOpen: boolean;
  mode: 'login' | 'register' | 'forgot-password' | 'reset-password';
  resetToken?: string | null;
  resetEmail?: string | null;
  isLogoutSuccessOpen?: boolean;
}

const initialState: AuthPopupState = {
  isOpen: false,
  mode: 'login',
  resetToken: null,
  resetEmail: null,
  isLogoutSuccessOpen: false,
};

export const isAuthPopupOpenSlice = createSlice({
  name: 'authPopup',
  initialState,
  reducers: {
    setIsAuthPopupOpen: (
      state,
      action: PayloadAction<
        | boolean
        | {
            isOpen: boolean;
            mode?: 'login' | 'register' | 'forgot-password' | 'reset-password';
            resetToken?: string | null;
            resetEmail?: string | null;
          }
      >
    ) => {
      if (typeof action.payload === 'boolean') {
        state.isOpen = action.payload;
        if (!action.payload) {
          state.mode = 'login';
          state.resetToken = null;
          state.resetEmail = null;
        }
      } else {
        state.isOpen = action.payload.isOpen;
        if (action.payload.mode) {
          state.mode = action.payload.mode;
        }
        if (action.payload.resetToken !== undefined) {
          state.resetToken = action.payload.resetToken;
        }
        if (action.payload.resetEmail !== undefined) {
          state.resetEmail = action.payload.resetEmail;
        }
        if (!action.payload.isOpen) {
          state.mode = 'login';
          state.resetToken = null;
          state.resetEmail = null;
        }
      }
    },
    openLoginModal: (state) => {
      state.isOpen = true;
      state.mode = 'login';
      state.resetToken = null;
      state.resetEmail = null;
    },
    openRegisterModal: (state) => {
      state.isOpen = true;
      state.mode = 'register';
      state.resetToken = null;
      state.resetEmail = null;
    },
    openForgotPasswordModal: (state) => {
      state.isOpen = true;
      state.mode = 'forgot-password';
      state.resetToken = null;
      state.resetEmail = null;
    },
    openResetPasswordModal: (
      state,
      action: PayloadAction<{ token: string; email: string }>
    ) => {
      state.isOpen = true;
      state.mode = 'reset-password';
      state.resetToken = action.payload.token;
      state.resetEmail = action.payload.email;
    },
    closeAuthModal: (state) => {
      state.isOpen = false;
      state.mode = 'login';
      state.resetToken = null;
      state.resetEmail = null;
    },
    openLogoutSuccessModal: (state) => {
      state.isLogoutSuccessOpen = true;
    },
    closeLogoutSuccessModal: (state) => {
      state.isLogoutSuccessOpen = false;
    }
  }
});

// Export actions
export const { 
  setIsAuthPopupOpen, 
  openLoginModal,
  openRegisterModal,
  openForgotPasswordModal,
  openResetPasswordModal,
  closeAuthModal,
  openLogoutSuccessModal,
  closeLogoutSuccessModal
} = isAuthPopupOpenSlice.actions;

export default isAuthPopupOpenSlice.reducer;