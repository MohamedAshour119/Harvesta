import React from 'react';

const ChatTypingIndicator: React.FC = () => {
    return (
        <div className="chat-message chat-message--assistant">
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
            <div className="chat-message__bubble">
                <div className="chat-typing">
                    <span className="chat-typing__dot" />
                    <span className="chat-typing__dot" />
                    <span className="chat-typing__dot" />
                </div>
            </div>
        </div>
    );
};

export default ChatTypingIndicator;
