import { createSlice, type PayloadAction } from '@reduxjs/toolkit';

// Updated interface to include user data
interface UserState {
  user: User | null;
  isAuth: boolean;
  authChecked: boolean;
}

export type User = {
    full_name: string | null;
    email: string | null;
    is_agent: boolean | null;
    code: string | null;
    commission: string | null;
    created_at: string | null;
    last_login_at?: string | null;
    monthly_target?: string | null;
}

const initialState: UserState | null = {
  user: {
    full_name: null,
    email: null,
    is_agent: null,
    code: null,
    commission: null,
    created_at: null,
    last_login_at: null,
    monthly_target: null,
  },
  isAuth: false,
  authChecked: false,
};

export const authSlice = createSlice({
  name: 'auth',
  initialState,
  reducers: {
    setUser: (state, action: PayloadAction<User | null>) => {
      console.log(action.payload);
      state.user = action.payload;
    },
    clearUser: (state) => {
      state.user = initialState.user;
      state.isAuth = false;
    },
    setIsAuth: (state, action: PayloadAction<boolean>) => {
      state.isAuth = action.payload;
    },
    setAuthChecked: (state, action: PayloadAction<boolean>) => {
      state.authChecked = action.payload;
    },
  }
});

// Export actions
export const { 
  setUser: setUser, 
  clearUser: clearUser, 
  setIsAuth: setIsAuth,
  setAuthChecked: setAuthChecked,
} = authSlice.actions;


export default authSlice.reducer;
