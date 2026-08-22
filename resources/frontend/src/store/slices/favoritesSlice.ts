import { createSlice, createAsyncThunk } from '@reduxjs/toolkit';
import type { PayloadAction } from '@reduxjs/toolkit';
import type { IFavoriteItem } from '../../types';
import ApiClient from '../../ApiClient';

export const fetchUserWishlist = createAsyncThunk(
  'favorites/fetchUserWishlist',
  async (_, { rejectWithValue }) => {
    try {
      const response = await ApiClient.wishlist.fetchWishlist();
      return response.data.data; 
    } catch (error: any) {
      const message = error.response?.data?.message || error.message || 'Failed to fetch wishlist';
      return rejectWithValue(message);
    }
  }
);

interface FavoritesState {
  items: IFavoriteItem[];
  isOpen: boolean;
  status: 'idle' | 'loading' | 'succeeded' | 'failed'; 
  error: string | null; 
}

const initialState: FavoritesState = {
  items: [],
  isOpen: false,
  status: 'idle', 
  error: null, 
};

export const favoritesSlice = createSlice({
  name: 'favorites',
  initialState,
  reducers: {
    setFavorites: (state, action: PayloadAction<IFavoriteItem[]>) => {
      state.items = action.payload;
      state.status = 'succeeded'; 
      state.error = null; 
    },
    addToFavorites: (state, action: PayloadAction<IFavoriteItem>) => {
      console.log('actionPayload: ', action.payload);
      
      const existingItem = state.items.find(item => item.productId === action.payload.productId && item.variantId === action.payload.variantId);
      if (!existingItem) {
        state.items.push(action.payload);
      }
    },
    removeFromFavorites: (state, action: PayloadAction<{ productId: number; variantId: number | undefined }>) => {
      state.items = state.items.filter(
        item => !(item.productId === action.payload.productId && item.variantId === action.payload.variantId)
      );
    },
    toggleFavoritesSidebar: (state) => {
      state.isOpen = !state.isOpen;
    },
    closeFavoritesSidebar: (state) => {
      state.isOpen = false;
    },
    clearFavorites: (state) => {
      state.items = [];
    },
  },
  extraReducers: (builder) => {
    builder
      .addCase(fetchUserWishlist.pending, (state) => {
        state.status = 'loading';
        state.error = null;
      })
      .addCase(fetchUserWishlist.fulfilled, (state, action: PayloadAction<IFavoriteItem[]>) => {
        favoritesSlice.caseReducers.setFavorites(state, action);
      })
      .addCase(fetchUserWishlist.rejected, (state, action) => {
        state.status = 'failed';
        state.error = action.payload as string; 
      });
  },
});

export const { 
  setFavorites, 
  addToFavorites, 
  removeFromFavorites, 
  toggleFavoritesSidebar,
  closeFavoritesSidebar,
  clearFavorites
} = favoritesSlice.actions;

export default favoritesSlice.reducer;