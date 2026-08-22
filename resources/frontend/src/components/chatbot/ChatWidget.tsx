import React, { useEffect, useRef, useCallback } from 'react';
import { useSelector, useDispatch } from 'react-redux';
import { useTranslation } from 'react-i18next';
import type { RootState } from '../../store/store';
import {
    toggleChat,
    closeChat,
    addMessage,
    setMessages,
    setLoading,
    setSessionId,
    resetChat,
} from '../../store/slices/chatbotSlice';
import ApiClient from '../../ApiClient';
import ChatInput from './ChatInput';
import ChatTypingIndicator from './ChatTypingIndicator';
import './chatbot.css';
import ChatMessage from './ChatMessage';
import {
    getChatbotSessionStorageKey,
    getStoredChatbotSessionId,
    persistChatbotSessionId,
} from '../../utils/chatbotSessionStorage';

const ChatWidget: React.FC = () => {
    const { t } = useTranslation('common');
    const dispatch = useDispatch();
    const messagesEndRef = useRef<HTMLDivElement>(null);
    const hasLoadedHistory = useRef(false);
    const isInitializing = useRef(false);
    const activeStorageKeyRef = useRef<string | null>(null);

    const {
        isOpen,
        messages,
        isLoading,
        sessionId,
        currentProductSlug,
    } = useSelector((state: RootState) => state.chatbot as any);

    const userName = useSelector((state: RootState) => (state as any).auth?.user?.full_name || '');
    const userEmail = useSelector((state: RootState) => (state as any).auth?.isAuth ? (state as any).auth?.user?.email || null : null);
    const storageKey = getChatbotSessionStorageKey(userEmail);

    useEffect(() => {
        if (activeStorageKeyRef.current === null) {
            activeStorageKeyRef.current = storageKey;
            return;
        }

        if (activeStorageKeyRef.current !== storageKey) {
            hasLoadedHistory.current = false;
            isInitializing.current = false;
            activeStorageKeyRef.current = storageKey;
            dispatch(resetChat());
            return;
        }

        activeStorageKeyRef.current = storageKey;
    }, [storageKey, dispatch]);

    // Initialize session from server when chat opens
    const initSession = useCallback(async () => {
        if (sessionId || isInitializing.current) return;
        isInitializing.current = true;

        try {
            const res = await ApiClient.chatbot.initSession();
            const newSessionId = res.data?.data?.session_id;
            if (newSessionId) {
                dispatch(setSessionId(newSessionId));
                persistChatbotSessionId(newSessionId, userEmail);
            }
        } catch {
            // Session init failed - will retry on next open
        } finally {
            isInitializing.current = false;
        }
    }, [sessionId, dispatch, userEmail]);

    // Restore session for the current guest/account scope or init a new one
    useEffect(() => {
        if (isOpen && !sessionId) {
            const stored = getStoredChatbotSessionId(userEmail);
            if (stored) {
                dispatch(setSessionId(stored));
            } else {
                initSession();
            }
        }
    }, [isOpen, sessionId, dispatch, initSession, userEmail]);

    // Load history when chat opens for the first time
    useEffect(() => {
        if (isOpen && sessionId && !hasLoadedHistory.current && messages.length === 0) {
            hasLoadedHistory.current = true;
            ApiClient.chatbot.getHistory(sessionId)
                .then((res) => {
                    const history = res.data?.data?.messages;
                    if (history && history.length > 0) {
                        dispatch(setMessages(history));
                    }
                })
                .catch(() => {
                    // No history, that's fine
                });
        }
    }, [isOpen, sessionId, messages.length, dispatch]);

    // Auto-scroll to bottom when new messages arrive
    useEffect(() => {
        if (messagesEndRef.current) {
            messagesEndRef.current.scrollIntoView({ behavior: 'smooth' });
        }
    }, [messages.length, isLoading]);

    const handleSend = async (text: string) => {
        if (!sessionId) {
            await initSession();
            return;
        }

        // Add user message to UI immediately
        dispatch(addMessage({ role: 'user', content: text }));
        dispatch(setLoading(true));

        try {
            const response = await ApiClient.chatbot.sendMessage(
                text,
                sessionId,
                currentProductSlug
            );

            const aiMessage = response.data?.data?.message;
            if (aiMessage) {
                dispatch(addMessage({ role: 'assistant', content: aiMessage }));
            } else {
                dispatch(addMessage({ role: 'assistant', content: t('chatbot.error') }));
            }
        } catch {
            dispatch(addMessage({ role: 'assistant', content: t('chatbot.error') }));
        } finally {
            dispatch(setLoading(false));
        }
    };

    return (
        <>
            {/* Chat Panel */}
            {isOpen && (
                <div className="chat-panel" id="chatbot-panel">
                    {/* Header */}
                    <div className="chat-header">
                        <div className="chat-header__info">
                            <div className="chat-header__icon">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                                    <path d="M12 8V4H8" />
                                    <rect width="16" height="12" x="4" y="8" rx="2" />
                                    <path d="M2 14h2" />
                                    <path d="M20 14h2" />
                                    <path d="M15 13v2" />
                                    <path d="M9 13v2" />
                                </svg>
                            </div>
                            <div>
                                <h3 className="chat-header__title">{t('chatbot.title')}</h3>
                                <p className="chat-header__subtitle">{t('chatbot.powered_by')}</p>
                            </div>
                        </div>
                        <button
                            className="chat-header__close"
                            onClick={() => dispatch(closeChat())}
                            aria-label={t('chatbot.close')}
                        >
                            ✕
                        </button>
                    </div>

                    {/* Messages */}
                    <div className="chat-messages">
                        {/* Welcome message if no messages */}
                        {messages.length === 0 && !isLoading && (
                            <div className="chat-welcome">
                                <div className="chat-welcome__icon">
                                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                                        <path d="M12 8V4H8" />
                                        <rect width="16" height="12" x="4" y="8" rx="2" />
                                        <path d="M2 14h2" />
                                        <path d="M20 14h2" />
                                        <path d="M15 13v2" />
                                        <path d="M9 13v2" />
                                    </svg>
                                </div>
                                <p className="chat-welcome__text">
                                    {userName
                                        ? t('chatbot.welcome_user', { name: userName.split(' ')[0] })
                                        : t('chatbot.welcome')
                                    }
                                </p>
                            </div>
                        )}

                        {messages.map((msg: any, index: number) => (
                            <ChatMessage key={msg.id || `msg-${index}`} message={msg} />
                        ))}

                        {isLoading && <ChatTypingIndicator />}

                        <div ref={messagesEndRef} />
                    </div>

                    {/* Input */}
                    <ChatInput onSend={handleSend} disabled={isLoading || !sessionId} />
                </div>
            )}

            {/* Floating Action Button */}
            <button
                className={`chat-fab ${isOpen ? 'chat-fab--active' : ''}`}
                onClick={() => dispatch(toggleChat())}
                aria-label={isOpen ? t('chatbot.close') : t('chatbot.open')}
                id="chatbot-fab"
            >
                {isOpen ? (
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                        <line x1="18" y1="6" x2="6" y2="18" />
                        <line x1="6" y1="6" x2="18" y2="18" />
                    </svg>
                ) : (
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                        <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z" />
                        <path d="M8 10h.01" />
                        <path d="M12 10h.01" />
                        <path d="M16 10h.01" />
                    </svg>
                )}
            </button>
        </>
    );
};

export default ChatWidget;
