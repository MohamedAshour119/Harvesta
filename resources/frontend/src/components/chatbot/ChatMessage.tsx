import React from 'react';
import type { ChatMessage as ChatMessageType } from '../../types';

interface ChatMessageProps {
    message: ChatMessageType;
}

/**
 * Render text with markdown-style links [text](url) as clickable <a> elements.
 */
const renderContent = (text: string) => {
    // Split by markdown link pattern [text](url)
    const parts = text.split(/(\[.+?\]\(.+?\))/g);

    return parts.map((part, i) => {
        const match = part.match(/\[(.+?)\]\((.+?)\)/);
        if (match) {
            return (
                <a
                    key={i}
                    href={match[2]}
                    className="chat-message__link"
                >
                    {match[1]}
                </a>
            );
        }
        return <span key={i}>{part}</span>;
    });
};

const ChatMessage: React.FC<ChatMessageProps> = ({ message }) => {
    const isUser = message.role === 'user';

    return (
        <div className={`chat-message ${isUser ? 'chat-message--user' : 'chat-message--assistant'}`}>
            {!isUser && (
                <div className="chat-message__avatar">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                        <path d="M12 8V4H8" />
                        <rect width="16" height="12" x="4" y="8" rx="2" />
                        <path d="M2 14h2" />
                        <path d="M20 14h2" />
                        <path d="M15 13v2" />
                        <path d="M9 13v2" />
                    </svg>
                </div>
            )}
            <div className="chat-message__bubble">
                <p className="chat-message__text">
                    {isUser ? message.content : renderContent(message.content)}
                </p>
            </div>
        </div>
    );
};

export default ChatMessage;
