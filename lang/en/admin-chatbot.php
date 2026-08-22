<?php

return [
    'title' => 'Cairo 3A Analytics Assistant',
    'subtitle' => 'AI-Powered Business Intelligence',
    'connected' => 'Connected',
    
    'welcome_title' => 'Welcome to the Analytics Assistant',
    'welcome_desc' => 'Interact with your data naturally. Ask questions about sales performance, inventory alerts, agent metrics, or top-selling products.',
    
    'suggestions' => [
        'sales' => [
            'prompt' => 'What are today\'s sales?',
            'title' => 'Today\'s Sales',
            'desc' => 'Quick overview of daily revenue',
        ],
        'inventory' => [
            'prompt' => 'Show me inventory alerts and low stock items',
            'title' => 'Inventory Alerts',
            'desc' => 'View low stock & warnings',
        ],
        'products' => [
            'prompt' => 'What are the top selling products this month?',
            'title' => 'Top Products',
            'desc' => 'Discover highest performers',
        ],
        'agents' => [
            'prompt' => 'Show me agent performance and commissions',
            'title' => 'Agent Metrics',
            'desc' => 'Analyze team operations',
        ],
    ],
    
    'input_placeholder' => 'Ask about sales, revenue, low stock, or team performance...',
    'disclaimer' => 'AI Analytics uses advanced reasoning. It may occasionally produce inaccurate data for complex multi-step analysis.',
    
    'nav_label' => 'AI Analytics Bot',
    'nav_group' => 'Tools',
    'page_title' => 'AI Analytics Assistant',
    
    'errors' => [
        'generic' => 'Sorry, something went wrong. Please try again.',
        'connection' => 'Connection error. Please try again.',
    ],
];
