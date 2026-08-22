<x-filament-panels::page>
    <div class="ca-chat-container" x-data="adminChatbot({
        sessionEndpoint: '{{ url('/admin/chatbot/session') }}',
        sendEndpoint: '{{ url('/admin/chatbot/send') }}',
        historyEndpoint: '{{ url('/admin/chatbot/history') }}',
        csrfToken: '{{ csrf_token() }}',
    })" x-init="init()">

        {{-- Ambient Background Glow --}}
        <div class="ca-glow ca-glow-left"></div>
        <div class="ca-glow ca-glow-right"></div>

        {{-- Main Chat Card --}}
        <div class="ca-chat-card">

            {{-- Header --}}
            <div class="ca-chat-header">
                <div class="ca-header-info">
                    <div class="ca-header-icon-box">
                        <x-heroicon-o-sparkles class="ca-icon-sparkle" />
                        <div class="ca-icon-ring"></div>
                    </div>
                    <div class="ca-header-text">
                        <h3 class="ca-title">{{ __('admin-chatbot.title') }}</h3>
                        <p class="ca-subtitle">{{ __('admin-chatbot.subtitle') }}</p>
                    </div>
                </div>
                <div class="ca-header-status">
                    <div class="ca-status-badge" x-show="isConnected" x-transition.opacity>
                        <span class="ca-status-dot-wrapper">
                            <span class="ca-status-dot-ping"></span>
                            <span class="ca-status-dot"></span>
                        </span>
                        {{ __('admin-chatbot.connected') }}
                    </div>
                </div>
            </div>

            {{-- Messages Area --}}
            <div class="ca-messages-body custom-scrollbar" x-ref="messagesContainer" id="admin-chat-messages">

                {{-- Welcome / Empty State --}}
                <template x-if="messages.length === 0">
                    <div class="ca-welcome-state">
                        <div class="ca-welcome-icon-wrapper">
                            <div class="ca-welcome-icon-glow"></div>
                            <div class="ca-welcome-icon-inner">
                                <x-heroicon-o-sparkles class="ca-welcome-icon" />
                            </div>
                        </div>
                        <h4 class="ca-welcome-title">
                            {{ __('admin-chatbot.welcome_title') }}
                        </h4>
                        <p class="ca-welcome-desc">
                            {{ __('admin-chatbot.welcome_desc') }}
                        </p>

                        <div class="ca-suggestions-grid">
                            <button @click="sendSuggestion('{{ __('admin-chatbot.suggestions.sales.prompt') }}')"
                                class="ca-suggestion-btn">
                                <div class="ca-suggestion-hover-bg"></div>
                                <div class="ca-suggestion-icon ca-icon-blue">
                                    <x-heroicon-o-chart-bar class="ca-s-icon" />
                                </div>
                                <div class="ca-suggestion-text">
                                    <h5>{{ __('admin-chatbot.suggestions.sales.title') }}</h5>
                                    <p>{{ __('admin-chatbot.suggestions.sales.desc') }}</p>
                                </div>
                            </button>

                            <button @click="sendSuggestion('{{ __('admin-chatbot.suggestions.inventory.prompt') }}')"
                                class="ca-suggestion-btn">
                                <div class="ca-suggestion-hover-bg"></div>
                                <div class="ca-suggestion-icon ca-icon-orange">
                                    <x-heroicon-o-exclamation-triangle class="ca-s-icon" />
                                </div>
                                <div class="ca-suggestion-text">
                                    <h5>{{ __('admin-chatbot.suggestions.inventory.title') }}</h5>
                                    <p>{{ __('admin-chatbot.suggestions.inventory.desc') }}</p>
                                </div>
                            </button>

                            <button @click="sendSuggestion('{{ __('admin-chatbot.suggestions.products.prompt') }}')"
                                class="ca-suggestion-btn">
                                <div class="ca-suggestion-hover-bg"></div>
                                <div class="ca-suggestion-icon ca-icon-emerald">
                                    <x-heroicon-o-trophy class="ca-s-icon" />
                                </div>
                                <div class="ca-suggestion-text">
                                    <h5>{{ __('admin-chatbot.suggestions.products.title') }}</h5>
                                    <p>{{ __('admin-chatbot.suggestions.products.desc') }}</p>
                                </div>
                            </button>

                            <button @click="sendSuggestion('{{ __('admin-chatbot.suggestions.agents.prompt') }}')"
                                class="ca-suggestion-btn">
                                <div class="ca-suggestion-hover-bg"></div>
                                <div class="ca-suggestion-icon ca-icon-purple">
                                    <x-heroicon-o-users class="ca-s-icon" />
                                </div>
                                <div class="ca-suggestion-text">
                                    <h5>{{ __('admin-chatbot.suggestions.agents.title') }}</h5>
                                    <p>{{ __('admin-chatbot.suggestions.agents.desc') }}</p>
                                </div>
                            </button>
                        </div>
                    </div>
                </template>

                {{-- Chat History --}}
                <template x-for="(msg, index) in messages" :key="index">
                    <div class="ca-message-row animate-fade-in-up"
                        :class="msg.role === 'user' ? 'ca-row-user' : 'ca-row-assistant'">

                        {{-- Assistant Avatar --}}
                        <template x-if="msg.role === 'assistant'">
                            <div class="ca-avatar">
                                <div class="ca-avatar-inner">
                                    <x-heroicon-s-sparkles class="ca-avatar-icon" />
                                </div>
                            </div>
                        </template>

                        <div class="ca-message-bubble"
                            :class="msg.role === 'user' ? 'ca-bubble-user' : 'ca-bubble-assistant'">
                            <div x-html="msg.role === 'assistant' ? formatMarkdown(msg.content.trim()) : escapeHtml(msg.content.trim())"
                                class="ca-markdown-content"></div>
                        </div>
                    </div>
                </template>

                {{-- Loading / Typing indicator --}}
                <div x-show="isLoading" class="ca-message-row ca-row-assistant animate-fade-in-up"
                    style="display: none;">
                    <div class="ca-avatar">
                        <div class="ca-avatar-inner ca-pulse-animation">
                            <x-heroicon-s-sparkles class="ca-avatar-icon" />
                        </div>
                    </div>
                    <div class="ca-message-bubble ca-bubble-assistant">
                        <div class="ca-typing-indicator">
                            <span class="ca-typing-dot" style="animation-delay: 0ms"></span>
                            <span class="ca-typing-dot" style="animation-delay: 150ms"></span>
                            <span class="ca-typing-dot" style="animation-delay: 300ms"></span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Input Area --}}
            <div class="ca-input-container">
                <form @submit.prevent="sendMessage()" class="ca-input-form">
                    <input x-model="input" type="text" placeholder="{{ __('admin-chatbot.input_placeholder') }}"
                        class="ca-input-field" :disabled="isLoading" x-ref="chatInput" />
                    <button type="submit" class="ca-submit-btn" :disabled="isLoading || !input.trim()">
                        <x-heroicon-s-paper-airplane class="ca-submit-icon" />
                    </button>
                </form>
                <div class="ca-footer-text">
                    <p>{{ __('admin-chatbot.disclaimer') }}</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Premium Native CSS styling (No Tailwind utilities required) --}}
    <style>
        /* Base Container */
        .ca-chat-container {
            position: relative;
            display: flex;
            flex-direction: column;
            height: 82vh;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            -webkit-font-smoothing: antialiased;
        }

        /* Ambient Glows */
        .ca-glow {
            position: absolute;
            width: 24rem;
            height: 24rem;
            border-radius: 50%;
            mix-blend-mode: screen;
            filter: blur(100px);
            pointer-events: none;
            opacity: 0;
            animation: caPulse 4s cubic-bezier(0.4, 0, 0.6, 1) infinite;
        }

        .dark .ca-glow {
            opacity: 1;
        }

        .ca-glow-left {
            top: 0;
            left: 25%;
            background-color: rgba(220, 38, 38, 0.18);
            /* Red tint */
        }

        .ca-glow-right {
            bottom: 0;
            right: 25%;
            background-color: rgba(239, 68, 68, 0.16);
            /* Red tint */
            animation-delay: 1s;
        }

        @keyframes caPulse {

            0%,
            100% {
                opacity: 1;
            }

            50% {
                opacity: .5;
            }
        }

        /* Chat Card Main Wrapper */
        .ca-chat-card {
            position: relative;
            z-index: 10;
            display: flex;
            flex-direction: column;
            flex: 1;
            overflow: hidden;
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(24px);
            -webkit-backdrop-filter: blur(24px);
            border: 1px solid rgba(0, 0, 0, 0.05);
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.05), border-box;
            border-radius: 2rem;
        }

        .dark .ca-chat-card {
            background: rgba(17, 24, 39, 0.6);
            border: 1px solid rgba(255, 255, 255, 0.1);
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
        }

        /* Header */
        .ca-chat-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 1.25rem 2rem;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            background: rgba(255, 255, 255, 0.4);
            backdrop-filter: blur(12px);
            z-index: 20;
        }

        .dark .ca-chat-header {
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
            background: rgba(17, 24, 39, 0.4);
        }

        @media (max-width: 640px) {
            .ca-chat-container {
                height: calc(100vh - 8rem);
            }

            .ca-chat-card {
                border-radius: 1.25rem;
            }

            .ca-chat-header {
                flex-direction: column;
                align-items: stretch;
                gap: 0.875rem;
                padding: 1rem;
            }

            .ca-header-info {
                align-items: flex-start;
                gap: 0.75rem;
                min-width: 0;
            }

            .ca-header-icon-box {
                width: 2.75rem;
                height: 2.75rem;
                flex-shrink: 0;
            }

            .ca-icon-sparkle {
                width: 1.25rem;
                height: 1.25rem;
            }

            .ca-header-text {
                min-width: 0;
            }

            .ca-header-text h3 {
                font-size: 1.05rem;
                line-height: 1.35;
                word-break: break-word;
            }

            .ca-header-text p {
                font-size: 0.8rem;
                line-height: 1.45;
            }

            .ca-header-status {
                display: flex;
                justify-content: flex-start;
            }

            .ca-status-badge {
                font-size: 0.7rem;
                padding: 0.35rem 0.65rem;
            }

            .ca-messages-body {
                padding: 0.85rem;
            }

            .ca-input-container {
                padding: 0.85rem;
            }

            .ca-input-field {
                font-size: 0.95rem;
                padding-inline-start: 1rem;
                padding-inline-end: 3.6rem;
            }

            .ca-submit-btn {
                width: 2.5rem;
                height: 2.5rem;
            }

            .ca-message-bubble {
                max-width: min(100%, 18.75rem) !important;
            }
        }

        .ca-header-info {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .ca-header-icon-box {
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
            width: 3rem;
            height: 3rem;
            border-radius: 1rem;
            background: linear-gradient(135deg, #dc2626 0%, #991b1b 100%);
            box-shadow: 0 10px 15px -3px rgba(220, 38, 38, 0.32);
        }

        .ca-icon-sparkle {
            width: 1.5rem;
            height: 1.5rem;
            color: #fff;
            animation: caSparklePulse 2s infinite;
        }

        .ca-icon-ring {
            position: absolute;
            inset: 0;
            border-radius: 1rem;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        @keyframes caSparklePulse {

            0%,
            100% {
                opacity: 1;
                transform: scale(1);
            }

            50% {
                opacity: 0.7;
                transform: scale(0.95);
            }
        }

        .ca-header-text h3 {
            margin: 0;
            font-size: 1.25rem;
            font-weight: 700;
            color: #111827;
            letter-spacing: -0.025em;
        }

        .ca-header-text p {
            margin: 0;
            font-size: 0.875rem;
            font-weight: 500;
            color: #6b7280;
        }

        .dark .ca-header-text h3 {
            color: #ffffff;
        }

        .dark .ca-header-text p {
            color: #9ca3af;
        }

        .ca-status-badge {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.375rem 0.75rem;
            border-radius: 9999px;
            background: rgba(220, 38, 38, 0.12);
            color: #dc2626;
            border: 1px solid rgba(220, 38, 38, 0.22);
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            transition: opacity 0.5s ease;
        }

        .dark .ca-status-badge {
            color: #f87171;
        }

        .ca-status-dot-wrapper {
            position: relative;
            display: flex;
            width: 0.5rem;
            height: 0.5rem;
        }

        .ca-status-dot {
            position: relative;
            display: inline-flex;
            width: 0.5rem;
            height: 0.5rem;
            border-radius: 50%;
            background-color: #dc2626;
        }

        .ca-status-dot-ping {
            position: absolute;
            display: inline-flex;
            width: 100%;
            height: 100%;
            border-radius: 50%;
            background-color: #f87171;
            opacity: 0.75;
            animation: caPing 1.5s cubic-bezier(0, 0, 0.2, 1) infinite;
        }

        @keyframes caPing {

            75%,
            100% {
                transform: scale(2.5);
                opacity: 0;
            }
        }

        /* Messages Body */
        .ca-messages-body {
            flex: 1;
            overflow-y: auto;
            padding: 1.25rem 1.75rem;
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
            scroll-behavior: smooth;
        }

        @media (max-width: 768px) {
            .ca-messages-body {
                padding: 1rem;
            }
        }

        /* Welcome State */
        .ca-welcome-state {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 100%;
            max-width: 48rem;
            margin: 0 auto;
            text-align: center;
            animation: caFadeInUp 0.5s cubic-bezier(0.16, 1, 0.3, 1) forwards;
        }

        .ca-welcome-icon-wrapper {
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
            width: 6rem;
            height: 6rem;
            margin-bottom: 2rem;
        }

        .ca-welcome-icon-glow {
            position: absolute;
            inset: 0;
            border-radius: 50%;
            background: rgba(220, 38, 38, 0.22);
            filter: blur(20px);
            animation: caPulse 2s infinite;
        }

        .ca-welcome-icon-inner {
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
            width: 5rem;
            height: 5rem;
            border-radius: 50%;
            background: linear-gradient(to top right, #f8fafc, #ffffff);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), border-box;
            border: 1px solid #f1f5f9;
        }

        .dark .ca-welcome-icon-inner {
            background: linear-gradient(to top right, #1f2937, #374151);
            border-color: #4b5563;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.5);
        }

        .ca-welcome-icon {
            width: 2.5rem;
            height: 2.5rem;
            color: #dc2626;
        }

        .ca-welcome-title {
            font-size: 1.875rem;
            font-weight: 800;
            color: #111827;
            margin-bottom: 1rem;
            letter-spacing: -0.025em;
            background: linear-gradient(to right, #111827, #4b5563);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .dark .ca-welcome-title {
            background: linear-gradient(to right, #ffffff, #d1d5db);
            -webkit-background-clip: text;
        }

        .ca-welcome-desc {
            font-size: 1rem;
            color: #6b7280;
            margin-bottom: 2.5rem;
            max-width: 32rem;
            line-height: 1.6;
        }

        .dark .ca-welcome-desc {
            color: #9ca3af;
        }

        .ca-suggestions-grid {
            display: grid;
            grid-template-columns: repeat(1, 1fr);
            gap: 1rem;
            width: 100%;
        }

        @media (min-width: 768px) {
            .ca-suggestions-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        .ca-suggestion-btn {
            position: relative;
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1rem;
            background: rgba(255, 255, 255, 1);
            border-radius: 1rem;
            border: 1px solid #f3f4f6;
            text-align: left;
            cursor: pointer;
            overflow: hidden;
            transition: all 0.3s ease;
        }

        .dark .ca-suggestion-btn {
            background: rgba(31, 41, 55, 0.8);
            border-color: #374151;
        }

        .ca-suggestion-btn:hover {
            border-color: rgba(220, 38, 38, 0.5);
            box-shadow: 0 10px 15px -3px rgba(220, 38, 38, 0.12);
            transform: translateY(-2px);
        }

        .ca-suggestion-hover-bg {
            position: absolute;
            inset: 0;
            background: linear-gradient(to right, transparent, rgba(220, 38, 38, 0.07));
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .ca-suggestion-btn:hover .ca-suggestion-hover-bg {
            opacity: 1;
        }

        .ca-suggestion-icon {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 3rem;
            height: 3rem;
            flex-shrink: 0;
            border-radius: 0.75rem;
            transition: transform 0.3s ease;
        }

        .ca-suggestion-btn:hover .ca-suggestion-icon {
            transform: scale(1.1);
        }

        .ca-s-icon {
            width: 1.5rem;
            height: 1.5rem;
        }

        .ca-icon-blue {
            background: rgba(220, 38, 38, 0.1);
            color: #dc2626;
        }

        .ca-icon-orange {
            background: rgba(239, 68, 68, 0.12);
            color: #ef4444;
        }

        .ca-icon-emerald {
            background: rgba(185, 28, 28, 0.14);
            color: #b91c1c;
        }

        .ca-icon-purple {
            background: rgba(127, 29, 29, 0.14);
            color: #7f1d1d;
        }

        .ca-suggestion-text h5 {
            margin: 0;
            font-size: 0.875rem;
            font-weight: 600;
            color: #111827;
        }

        .ca-suggestion-text p {
            margin: 0.25rem 0 0 0;
            font-size: 0.75rem;
            color: #6b7280;
        }

        .dark .ca-suggestion-text h5 {
            color: #ffffff;
        }

        .dark .ca-suggestion-text p {
            color: #9ca3af;
        }

        /* Chat History Bubbles */
        .ca-message-row {
            display: flex;
            width: auto;
            max-width: 100%;
            margin-bottom: 0.2rem;
            animation: caFadeInUp 0.4s ease-out forwards;
            align-items: flex-start;
        }

        .ca-row-user {
            align-self: flex-end;
            justify-content: flex-end;
        }

        .ca-row-assistant {
            align-self: flex-start;
            justify-content: flex-start;
        }

        .ca-avatar {
            display: none;
            flex-shrink: 0;
            margin-inline-end: 0.65rem;
            margin-top: 0.15rem;
        }

        @media (min-width: 640px) {
            .ca-avatar {
                display: block;
            }
        }

        .ca-avatar-inner {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 1.75rem;
            height: 1.75rem;
            border-radius: 50%;
            background: linear-gradient(135deg, #dc2626 0%, #991b1b 100%);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }

        .ca-avatar-icon {
            width: 1rem;
            height: 1rem;
            color: white;
        }

        .ca-pulse-animation {
            animation: caPulse 1.5s infinite;
        }

        .ca-message-bubble {
            display: inline-flex !important;
            align-items: center !important;
            width: fit-content !important;
            height: auto !important;
            min-height: 0 !important;
            min-height: 2.2rem !important;
            min-width: 0 !important;
            max-width: min(30rem, 78vw) !important;
            padding: 0.45rem 0.8rem !important;
            font-size: 0.875rem !important;
            line-height: 1.55 !important;
            border-radius: 1rem !important;
            white-space: pre-wrap !important;
            word-break: break-word !important;
            overflow-wrap: break-word !important;
        }

        .ca-markdown-content {
            display: block !important;
            width: 100% !important;
            margin: 0 !important;
            padding: 0 !important;
        }

        @media (min-width: 768px) {
            .ca-message-bubble {
                max-width: min(26rem, 72vw) !important;
            }
        }

        .ca-bubble-user {
            background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%);
            color: #ffffff;
            border-top-right-radius: 0.4rem;
            border-bottom-left-radius: 1rem;
            border-bottom-right-radius: 1rem;
            box-shadow: 0 4px 6px -1px rgba(220, 38, 38, 0.24);
        }

        .ca-bubble-assistant {
            background: #ffffff;
            color: #1f2937;
            border-top-left-radius: 0.4rem;
            border-bottom-left-radius: 1rem;
            border-bottom-right-radius: 1rem;
            border: 1px solid #f3f4f6;
            box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
        }

        .dark .ca-bubble-assistant {
            background: #1f2937;
            color: #e5e7eb;
            border-color: #374151;
        }

        /* Loading Indicator */
        .ca-typing-indicator {
            display: flex;
            gap: 0.375rem;
            align-items: center;
            justify-content: center;
            height: 1rem;
        }

        .ca-typing-dot {
            width: 0.5rem;
            height: 0.5rem;
            background: rgba(220, 38, 38, 0.7);
            border-radius: 50%;
            animation: caBounce 1s infinite;
        }

        @keyframes caBounce {

            0%,
            100% {
                transform: translateY(0);
            }

            50% {
                transform: translateY(-4px);
            }
        }

        /* Input Area */
        .ca-input-container {
            padding: 1rem 1.5rem;
            background: rgba(249, 250, 251, 0.8);
            backdrop-filter: blur(24px);
            border-top: 1px solid rgba(0, 0, 0, 0.05);
            z-index: 20;
        }

        .dark .ca-input-container {
            background: rgba(17, 24, 39, 0.8);
            border-top: 1px solid rgba(255, 255, 255, 0.05);
        }

        .ca-input-form {
            position: relative;
            display: flex;
            align-items: center;
            max-width: 64rem;
            margin: 0 auto;
        }

        .ca-input-field {
            width: 100%;
            padding: 1rem;
            padding-inline-start: 1.5rem;
            padding-inline-end: 4rem;
            border-radius: 9999px;
            border: 1px solid #e5e7eb;
            background: #ffffff;
            color: #111827;
            font-size: 1rem;
            transition: all 0.2s ease;
            box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
        }

        .dark .ca-input-field {
            background: #1f2937;
            border-color: #374151;
            color: #f3f4f6;
        }

        .ca-input-field:focus {
            outline: none;
            border-color: #dc2626;
            box-shadow: 0 0 0 3px rgba(220, 38, 38, 0.2);
        }

        .ca-input-field::placeholder {
            color: #9ca3af;
        }

        .dark .ca-input-field::placeholder {
            color: #6b7280;
        }

        .ca-submit-btn {
            position: absolute;
            inset-inline-end: 0.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            width: 2.75rem;
            height: 2.75rem;
            border-radius: 50%;
            background: #dc2626;
            color: #ffffff;
            border: none;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .ca-submit-btn:hover:not(:disabled) {
            background: #b91c1c;
            box-shadow: 0 10px 15px -3px rgba(220, 38, 38, 0.32);
            transform: translateY(-1px);
        }

        .ca-submit-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            box-shadow: none;
        }

        .ca-submit-icon {
            width: 1.25rem;
            height: 1.25rem;
            margin-inline-start: 0.25rem;
            transition: transform 0.2s ease;
        }

        [dir="rtl"] .ca-submit-icon {
            transform: scaleX(-1);
        }

        .ca-submit-btn:hover:not(:disabled) .ca-submit-icon {
            transform: translateY(-2px);
        }

        [dir="rtl"] .ca-submit-btn:hover:not(:disabled) .ca-submit-icon {
            transform: translateY(-2px) scaleX(-1);
        }

        .ca-footer-text {
            text-align: center;
            margin-top: 0.75rem;
        }

        .ca-footer-text p {
            margin: 0;
            font-size: 0.7rem;
            font-weight: 500;
            color: #9ca3af;
            letter-spacing: 0.025em;
        }

        .dark .ca-footer-text p {
            color: #6b7280;
        }

        /* General Utilities */
        @keyframes caFadeInUp {
            from {
                opacity: 0;
                transform: translateY(15px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .custom-scrollbar::-webkit-scrollbar {
            width: 6px;
        }

        .custom-scrollbar::-webkit-scrollbar-track {
            background: transparent;
        }

        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: rgba(156, 163, 175, 0.3);
            border-radius: 10px;
        }

        .dark .custom-scrollbar::-webkit-scrollbar-thumb {
            background: rgba(75, 85, 99, 0.4);
        }

        .custom-scrollbar::-webkit-scrollbar-thumb:hover {
            background: rgba(156, 163, 175, 0.5);
        }

        /* Markdown Styles */
        .ca-markdown-content p {
            margin: 0;
        }

        .ca-markdown-content p+p {
            margin-top: 0.5rem;
        }

        .ca-markdown-content p:last-child {
            margin-bottom: 0;
        }

        .ca-markdown-content ul,
        .ca-markdown-content ol {
            margin: 0.5rem 0;
            padding-left: 0;
            padding-inline-start: 1.25rem !important;
            list-style-position: outside;
        }

        .ca-markdown-content ul {
            list-style-type: disc;
        }

        .ca-markdown-content ol {
            list-style-type: decimal;
        }

        .ca-markdown-content li {
            margin-bottom: 0.35rem;
            line-height: 1.6;
        }

        html[dir="rtl"] .ca-markdown-content,
        body[dir="rtl"] .ca-markdown-content {
            direction: rtl;
            text-align: right;
            padding-inline-end: 1.5rem !important;
        }

        html[dir="rtl"] .ca-markdown-content ul,
        html[dir="rtl"] .ca-markdown-content ol,
        body[dir="rtl"] .ca-markdown-content ul,
        body[dir="rtl"] .ca-markdown-content ol {
            padding-inline-start: 0;
            padding-inline-end: 1.5rem !important;
        }

        html[dir="rtl"] .ca-markdown-content li,
        body[dir="rtl"] .ca-markdown-content li {
            padding-inline-end: 0.2rem !important;
        }

        .ca-markdown-content strong {
            font-weight: 700;
            color: inherit;
        }

        .ca-markdown-content a {
            color: #dc2626;
            text-decoration: underline;
            text-underline-offset: 2px;
            font-weight: 500;
        }

        .dark .ca-markdown-content a {
            color: #f87171;
        }

        .ca-markdown-content code {
            background: rgba(0, 0, 0, 0.05);
            color: #dc2626;
            padding: 0.125rem 0.375rem;
            border-radius: 0.25rem;
            font-size: 0.85em;
            font-family: monospace;
            border: 1px solid rgba(0, 0, 0, 0.05);
        }

        .dark .ca-markdown-content code {
            background: rgba(255, 255, 255, 0.1);
            color: #f87171;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        /* Note: Using Red accents for Admin UI highlights */
    </style>

    <script>
        function adminChatbot(config) {
            return {
                messages: [],
                input: '',
                isLoading: false,
                isConnected: false,
                sessionId: null,

                async init() {
                    await this.initSession();
                    await this.loadHistory();
                },

                async initSession() {
                    const stored = sessionStorage.getItem('admin_chat_session');
                    if (stored) {
                        this.sessionId = stored;
                        this.isConnected = true;
                        return;
                    }

                    try {
                        const res = await fetch(config.sessionEndpoint, {
                            method: 'POST',
                            headers: this.headers(),
                            credentials: 'same-origin',
                        });

                        const data = await res.json();
                        if (data.success && data.data?.session_id) {
                            this.sessionId = data.data.session_id;
                            sessionStorage.setItem('admin_chat_session', this.sessionId);
                            this.isConnected = true;
                        }
                    } catch (e) {
                        console.error('Failed to init admin chat session', e);
                    }
                },

                async loadHistory() {
                    if (!this.sessionId) return;

                    try {
                        const res = await fetch(`${config.historyEndpoint}?session_id=${encodeURIComponent(this.sessionId)}`, {
                            headers: this.headers(false),
                            credentials: 'same-origin',
                        });

                        const data = await res.json();
                        if (data.success && data.data?.messages?.length > 0) {
                            this.messages = data.data.messages;
                            this.$nextTick(() => {
                                setTimeout(() => this.scrollToBottom(), 100);
                            });
                        }
                    } catch (e) {
                        console.error('Failed to load history', e);
                    }
                },

                async sendMessage() {
                    const text = this.input.trim();
                    if (!text || this.isLoading) return;

                    this.messages.push({ role: 'user', content: text });
                    this.input = '';
                    this.isLoading = true;
                    this.$nextTick(() => this.scrollToBottom());

                    try {
                        const res = await fetch(config.sendEndpoint, {
                            method: 'POST',
                            headers: this.headers(),
                            credentials: 'same-origin',
                            body: JSON.stringify({
                                message: text,
                                session_id: this.sessionId,
                            }),
                        });

                        const data = await res.json();
                        if (data.success && data.data?.message) {
                            this.messages.push({ role: 'assistant', content: data.data.message });
                        } else {
                            this.messages.push({ role: 'assistant', content: {{ Illuminate\Support\Js::from(__('admin-chatbot.errors.generic')) }} });
                        }
                    } catch (e) {
                        this.messages.push({ role: 'assistant', content: {{ Illuminate\Support\Js::from(__('admin-chatbot.errors.connection')) }} });
                    } finally {
                        this.isLoading = false;
                        this.$nextTick(() => {
                            setTimeout(() => this.scrollToBottom(), 50);
                        });
                    }
                },

                sendSuggestion(text) {
                    this.input = text;
                    this.sendMessage();
                },

                scrollToBottom() {
                    const container = this.$refs.messagesContainer;
                    if (container) {
                        container.scrollTo({
                            top: container.scrollHeight,
                            behavior: 'smooth'
                        });
                    }
                },

                formatMarkdown(text) {
                    if (!text) return '';

                    // 1. URLs to anchor tags
                    let html = text.replace(/(https?:\/\/[^\s]+)/g, '<a href="$1" target="_blank" rel="noopener noreferrer">$1</a>');

                    // 2. Bold and Italic
                    html = html.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
                    html = html.replace(/\*(.*?)\*/g, '<em>$1</em>');

                    // 3. Inline Code
                    html = html.replace(/`([^`]+)`/g, '<code>$1</code>');

                    // 4. Code Blocks
                    html = html.replace(/```([\s\S]*?)```/g, '<pre style="background:#111827;color:#f3f4f6;padding:1rem;border-radius:0.75rem;margin:0.75rem 0;overflow-x:auto;font-size:0.875rem;"><code>$1</code></pre>');

                    // 5. Lists (bullet points starting with - )
                    html = html.replace(/^- (.*)$/gm, '<li>$1</li>');
                    html = html.replace(/(<li>.*<\/li>(\n)?)+/g, '<ul>$&</ul>');

                    // 6. Numbered Lists (1. 2. 3.)
                    html = html.replace(/^\d+\. (.*)$/gm, '<li>$1</li>');
                    html = html.replace(/(<li>.*<\/li>(\n)?)+/g, function (match) {
                        if (match.includes('<ul>')) return match;
                        return '<ol>' + match + '</ol>';
                    });

                    // Clear nested ol/ul
                    html = html.replace(/<\/ul>\n<ul>/g, '\n');
                    html = html.replace(/<\/ol>\n<ol>/g, '\n');

                    // 7. Line breaks
                    html = html.replace(/\n\s*\n/g, '<br><br>');
                    html = html.replace(/\n/g, '<br>');

                    return html.trim();
                },

                escapeHtml(text) {
                    const div = document.createElement('div');
                    div.textContent = text ?? '';
                    return div.innerHTML;
                },

                headers(withJson = true) {
                    const headers = {
                        'X-CSRF-TOKEN': config.csrfToken,
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                    };

                    if (withJson) {
                        headers['Content-Type'] = 'application/json';
                    }

                    return headers;
                },
            };
        }
    </script>
</x-filament-panels::page>
