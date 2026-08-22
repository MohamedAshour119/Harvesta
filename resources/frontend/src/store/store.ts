import { combineReducers, configureStore } from '@reduxjs/toolkit';
import cartReducer from './slices/cartSlice';
import filterReducer from './slices/filterSlice';
import storage from "redux-persist/lib/storage";
import { persistReducer, persistStore } from "redux-persist";
import authReducer from './slices/userSlice';
import favoritesReducer from './slices/favoritesSlice';
import authRedirectReducer from './slices/authRedirectSlice';
import isAuthPopupOpenReducer from './slices/isAuthPopupOpenSlice';
import isCartSummarySidebarOpenReducer from './slices/isCartSummarySidebarOpenSlice.ts';
import isHeaderSidebarOpenReducer from './slices/isHeaderSidebarOpenSlice.ts';
import couponReducer from './slices/couponSlice';
import agentBlockerReducer from './slices/agentBlockerSlice';
import isMinimumOrderValuePopupOpenReducer from './slices/isMinimumOrderValuePopupOpen.ts';
import chatbotReducer from './slices/chatbotSlice';
import { cartTransform } from './transforms';

// Configuring the persist for Redux
const persistConfig = {
  key: "root",
  version: 1,
  storage,
  transforms: [cartTransform],
  blacklist: [
    'auth',
    'filter',
    'isAuthPopupOpen',
    'isCartSummarySidebarOpen',
    'isHeaderSidebarOpen',
    'agentBlocker',
    'isMinimumOrderValuePopupOpen',
    'chatbot'
  ],
};

// Combine all the reducers
const reducers = combineReducers({
  cart: cartReducer,
  filter: filterReducer,
  auth: authReducer,
  favorites: favoritesReducer,
  authRedirect: authRedirectReducer,
  isAuthPopupOpen: isAuthPopupOpenReducer,
  isCartSummarySidebarOpen: isCartSummarySidebarOpenReducer,
  isHeaderSidebarOpen: isHeaderSidebarOpenReducer,
  coupon: couponReducer,
  agentBlocker: agentBlockerReducer,
  isMinimumOrderValuePopupOpen: isMinimumOrderValuePopupOpenReducer,
  chatbot: chatbotReducer,
});

const persistedReducer = persistReducer(persistConfig, reducers);

export const store = configureStore({
  reducer: persistedReducer,
  middleware: (getDefaultMiddleware) =>
      getDefaultMiddleware({
          serializableCheck: false,
      }),
});

export const persistor = persistStore(store);

export type RootState = ReturnType<typeof store.getState>;
export type AppDispatch = typeof store.dispatch;
