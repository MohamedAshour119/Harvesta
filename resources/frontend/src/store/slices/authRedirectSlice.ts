import { createSlice, type PayloadAction } from '@reduxjs/toolkit';
import type { RootState } from '../store';

// Define the types of actions that require authentication
export type ActionType = 'wishlist' | 'checkout' | 'cart' | 'other';

// Data structure to store pending action information
interface PendingAction {
  type: ActionType;
  payload?: any; // Additional data needed for the action
  returnUrl: string; // URL to return to after login
}

interface AuthRedirectState {
  pendingAction: PendingAction | null;
}

const initialState: AuthRedirectState = {
  pendingAction: null,
};

export const authRedirectSlice = createSlice({
  name: 'authRedirect',
  initialState,
  reducers: {
    setPendingAction: (state, action: PayloadAction<PendingAction | null>) => {
      state.pendingAction = action.payload;
    },
    clearPendingAction: (state) => {
      state.pendingAction = null;
    },
  },
});

// Export actions
export const { setPendingAction, clearPendingAction } = authRedirectSlice.actions;

// Export selectors
export const selectPendingAction = (state: RootState) => state.authRedirect.pendingAction;

export default authRedirectSlice.reducer;