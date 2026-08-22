import { useCallback, useEffect } from 'react';
import { useDispatch, useSelector } from 'react-redux';

import { clearPendingAction, selectPendingAction, setPendingAction, type ActionType } from '../store/slices/authRedirectSlice';
import type { RootState } from '../store/store';
import { addToFavorites, removeFromFavorites, fetchUserWishlist } from '../store/slices/favoritesSlice';
import ApiClient from '../ApiClient';
import { setIsAuthPopupOpen } from '../store/slices/isAuthPopupOpenSlice';
import { show } from '../store/slices/agentBlockerSlice';

// Global variables to track action processing across all hook instances
const globalProcessingState = {
  isProcessing: false,
  processedActionIds: new Set<string>()
};

export const useProtectedAction = () => {
  const dispatch = useDispatch();
  const pendingAction = useSelector(selectPendingAction);
  const isAuth = useSelector((state: RootState) => state.auth.isAuth);
  const user = useSelector((state: RootState) => state.auth.user);

  const handleProtectedAction = (
    actionType: ActionType,
    onAction: () => void,
    payload?: any
  ) => {    
    if (isAuth) {
      // User is authenticated, perform the action immediately
      onAction();
    } else {
      // Generate a unique ID for this action
      const actionId = Date.now().toString();
      
      // User is not authenticated, store the action for later and show login prompt
      dispatch(setPendingAction({
        type: actionType,
        payload: { ...payload, actionId },
        returnUrl: window.location.pathname + window.location.search
      }));
      dispatch(setIsAuthPopupOpen(true));   
      console.log('handleProtectedAction called with actionType:', actionType);

    }
  };

  // useEffect(() => {
  //   console.log('ShowLoginPrompt: ', showLoginPrompt);
  // }, [showLoginPrompt])


  // Check if there's a pending action to execute after login
  const checkPendingAction = useCallback(async () => {
    // Only proceed if:
    // 1. User is authenticated
    // 2. There's a pending action
    // 3. We're not already processing an action globally
    // 4. We haven't processed this specific action before
    if (
      isAuth && // Ensure isAuth is checked here as well, as it might change
      pendingAction && 
      !globalProcessingState.isProcessing &&
      pendingAction.payload?.actionId && 
      !globalProcessingState.processedActionIds.has(pendingAction.payload.actionId)
    ) {
      // Mark that we're processing an action globally
      globalProcessingState.isProcessing = true;
      
      // Add this action ID to our processed set
      globalProcessingState.processedActionIds.add(pendingAction.payload.actionId);

      // Important: Create a stable copy of pendingAction for this execution scope
      const currentPendingAction = { ...pendingAction };

      console.log(`[checkPendingAction] START for actionId: ${currentPendingAction.payload?.actionId}, type: ${currentPendingAction.type}`);

      try {
        let apiPromise;
        switch (currentPendingAction.type) {
          case 'wishlist':
            if (currentPendingAction.payload?.productId && currentPendingAction.payload?.variantId) {
              const { productId, variantId, action, product } = currentPendingAction.payload;
              console.log(`[checkPendingAction] Wishlist action (raw from payload): action='${action}', product exists=${!!product}`);
              console.log(`[checkPendingAction] Wishlist action: ${action}, productId: ${productId}, variantId: ${variantId}`);
              
              if (action === 'add' && product) {
                dispatch(addToFavorites(product)); 
                apiPromise = ApiClient.wishlist.addToWishlist(productId, variantId);
              } else if (action === 'remove') {
                dispatch(removeFromFavorites({ productId, variantId }));
                apiPromise = ApiClient.wishlist.removeFromWishlist(productId, variantId);
              } else {
                console.warn(`[checkPendingAction] Unknown wishlist sub-action: ${action}`);
                apiPromise = Promise.resolve(); // No actual API call, resolve immediately
              }
            } else {
              console.warn('[checkPendingAction] Missing productId or variantId for wishlist action.');
              apiPromise = Promise.resolve(); // No actual API call
            }
            break;
          case 'checkout':
            if (user?.is_agent) {
              // Agent should not be redirected; just clear
              dispatch(clearPendingAction());
              dispatch(show());
            } else {
              // Non-agent: proceed to checkout
              window.location.href = '/checkout';
            }
            break;
          case 'cart':
            if (currentPendingAction.payload?.productId) {
                apiPromise = ApiClient.recommendations.trackCartAddition(
                    currentPendingAction.payload.productId,
                    currentPendingAction.payload.price
                );
            }
            break;
          default:
            console.warn(`[checkPendingAction] Unknown pending action type: ${currentPendingAction.type}`);
            apiPromise = Promise.resolve(); // No actual API call
        }

        if (apiPromise) {
          console.log(`[checkPendingAction] Awaiting API promise for actionId: ${currentPendingAction.payload?.actionId}`);
          await apiPromise;
          console.log(`[checkPendingAction] API promise COMPLETED for actionId: ${currentPendingAction.payload?.actionId}`);
          
          // If the action was a wishlist modification, re-fetch the wishlist
          if (currentPendingAction.type === 'wishlist') {
            console.log('[checkPendingAction] Wishlist modified, dispatching fetchUserWishlist.');
            await dispatch(fetchUserWishlist() as any); // Await this to ensure it completes
            console.log('[checkPendingAction] fetchUserWishlist COMPLETED.');
          }

          dispatch(clearPendingAction());
          console.log(`[checkPendingAction] Pending action CLEARED (after success) for actionId: ${currentPendingAction.payload?.actionId}`);
        } else {
          // If no apiPromise was created (e.g., bad payload), clear immediately to avoid getting stuck
          dispatch(clearPendingAction());
          console.log(`[checkPendingAction] Pending action CLEARED (no API call made) for actionId: ${currentPendingAction.payload?.actionId}`);
        }

      } catch (error) {
        console.error(`[checkPendingAction] Error processing pending action for actionId: ${currentPendingAction.payload?.actionId}`, error);
        dispatch(clearPendingAction());
        console.log(`[checkPendingAction] Pending action CLEARED (after error) for actionId: ${currentPendingAction.payload?.actionId}`);
      } finally {
        globalProcessingState.isProcessing = false;
        console.log(`[checkPendingAction] FINALLY - Released processing lock for actionId: ${currentPendingAction.payload?.actionId}`);
      }
    }
  }, [isAuth, pendingAction, dispatch, globalProcessingState, user]); // Add isAuth to dependencies

  // Only check for pending actions when authentication state changes
  useEffect(() => {
    if (isAuth) {
      // Add a small delay to ensure all components have rendered
      const timeoutId = setTimeout(() => {
        checkPendingAction();
      }, 100);
      
      return () => clearTimeout(timeoutId);
    }
  }, [isAuth, checkPendingAction]);

  return {
    handleProtectedAction,
    checkPendingAction,
  };
};