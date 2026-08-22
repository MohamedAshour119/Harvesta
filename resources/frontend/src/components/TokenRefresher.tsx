import { useCallback, useEffect } from "react";
import { useDispatch } from "react-redux";
import { clearUser, setAuthChecked, setIsAuth, setUser } from "../store/slices/userSlice";
import { clearFavorites, setFavorites } from "../store/slices/favoritesSlice";
import ApiClient, { clearAuthTokens, clearLegacyAuthStorage, hasAccessToken, setAccessToken } from "../ApiClient";

export const TokenRefresher = () => {
  const dispatch = useDispatch();

  const clearAuthData = useCallback(() => {
    clearAuthTokens();
    dispatch(clearUser());
    dispatch(clearFavorites());
    dispatch(setAuthChecked(true));
  }, [dispatch]);

  const applyAuthPayload = useCallback((data: any) => {
    const plainToken = data?.access_token?.plain_token;
    const expiresAt = data?.access_token?.expires_at ?? null;

    if (!plainToken) {
      throw new Error("Missing access token.");
    }

    setAccessToken(plainToken, expiresAt);
    dispatch(setUser(data.user));
    dispatch(setIsAuth(true));
    dispatch(setFavorites(data.user?.wishlist_items ?? []));
  }, [dispatch]);

  useEffect(() => {
    let cancelled = false;

    const bootstrapAuth = async () => {
      clearLegacyAuthStorage();

      try {
        const response = await ApiClient.auth.refreshToken();

        if (!cancelled) {
          applyAuthPayload(response.data.data);
        }
      } catch (error) {
        if (!cancelled && !hasAccessToken()) {
          clearAuthData();
        }
      } finally {
        if (!cancelled) {
          dispatch(setAuthChecked(true));
        }
      }
    };

    bootstrapAuth();

    return () => {
      cancelled = true;
    };
  }, [applyAuthPayload, clearAuthData, dispatch]);

  useEffect(() => {
    const handleSessionExpired = () => {
      clearAuthData();
    };

    window.addEventListener("auth:session-expired", handleSessionExpired);

    return () => {
      window.removeEventListener("auth:session-expired", handleSessionExpired);
    };
  }, [clearAuthData]);

  return null;
};
