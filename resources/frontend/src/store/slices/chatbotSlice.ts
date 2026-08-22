import { createSlice, type PayloadAction } from '@reduxjs/toolkit';
import type { ChatMessage, ChatbotState } from '../../types';

const initialState: ChatbotState = {
    isOpen: false,
    messages: [],
    sessionId: null,
    isLoading: false,
    currentProductSlug: null,
};

const chatbotSlice = createSlice({
    name: 'chatbot',
    initialState,
    reducers: {
        toggleChat(state) {
            state.isOpen = !state.isOpen;
        },
        openChat(state) {
            state.isOpen = true;
        },
        closeChat(state) {
            state.isOpen = false;
        },
        addMessage(state, action: PayloadAction<ChatMessage>) {
            state.messages.push(action.payload);
        },
        setMessages(state, action: PayloadAction<ChatMessage[]>) {
            state.messages = action.payload;
        },
        setLoading(state, action: PayloadAction<boolean>) {
            state.isLoading = action.payload;
        },
        setCurrentProductSlug(state, action: PayloadAction<string | null>) {
            state.currentProductSlug = action.payload;
        },
        setSessionId(state, action: PayloadAction<string>) {
            state.sessionId = action.payload;
        },
        resetChat(state) {
            Object.assign(state, initialState);
        },
    },
});

export const {
    toggleChat,
    openChat,
    closeChat,
    addMessage,
    setMessages,
    setLoading,
    setCurrentProductSlug,
    setSessionId,
    resetChat,
} = chatbotSlice.actions;

export default chatbotSlice.reducer;
