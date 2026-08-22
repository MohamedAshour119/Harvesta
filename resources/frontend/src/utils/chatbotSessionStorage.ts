import type { Dispatch } from '@reduxjs/toolkit';
import { resetChat } from '../store/slices/chatbotSlice';

const LEGACY_SESSION_KEY = 'chatbot_session';
const GUEST_SESSION_KEY = 'chatbot_session_guest';
const USER_SESSION_PREFIX = 'chatbot_session_user_';

const normalizeEmail = (email?: string | null) => email?.trim().toLowerCase() || null;

export const getChatbotSessionStorageKey = (email?: string | null) => {
    const normalizedEmail = normalizeEmail(email);

    return normalizedEmail
        ? `${USER_SESSION_PREFIX}${normalizedEmail}`
        : GUEST_SESSION_KEY;
};

export const getStoredChatbotSessionId = (email?: string | null) => {
    const storageKey = getChatbotSessionStorageKey(email);
    const scopedSessionId = localStorage.getItem(storageKey);

    if (scopedSessionId) {
        return scopedSessionId;
    }

    const legacySessionId = sessionStorage.getItem(LEGACY_SESSION_KEY);

    if (!legacySessionId) {
        return null;
    }

    localStorage.setItem(storageKey, legacySessionId);
    sessionStorage.removeItem(LEGACY_SESSION_KEY);

    return legacySessionId;
};

export const persistChatbotSessionId = (sessionId: string, email?: string | null) => {
    localStorage.setItem(getChatbotSessionStorageKey(email), sessionId);
    sessionStorage.removeItem(LEGACY_SESSION_KEY);
};

export const clearVisibleChatbotSession = (dispatch: Dispatch) => {
    sessionStorage.removeItem(LEGACY_SESSION_KEY);
    dispatch(resetChat());
};

export const clearChatbotForLogout = (dispatch: Dispatch) => {
    localStorage.removeItem(GUEST_SESSION_KEY);
    clearVisibleChatbotSession(dispatch);
};
