export interface IProductCard {
    id: number;
    name: string;
    slug: string;
    description?: string;
    min_order_value?: number;
    agent_min_order_value?: number;
    minimum_order_quantity?: number;
    is_approved?: boolean;
    status: boolean;
    oem?: boolean;
    stock_availability?: string
    notes?: string;
    images: {
        main_image: string;
        other_images?: string[];
    };
    price: number;
    quantity?: number;
    category?: {
        id: number;
        name: string;
    };
    cheapestVariant?: {
        id: number;
        product_id: number;
        variant_name?: string;
        variant_value?: string;
        unit_price_usd: number;
        price_after_discount?: number;
        total_quantity?: number;
        discount_percentage?: number;
        main_image?: string;
        stock_availability?: string;
        created_at: string;
        updated_at?: string;
    };
    variants?: IProductVariant[];
    created_at?: string;
    is_in_wishlist?: boolean;
    ratings?: IProductRating[];
    rating_stats?: {
        average_rating: number;
        total_ratings: number;
        rating_breakdown: {
            star: number;
            count: number;
            percentage: number;
        }[];
    };
    smart_bundle?: {
        name: string | any;
        target_quantity: number;
        discount_percentage: number;
    } | null;
}

export interface IProductVariant {
    id: number;
    product_id: number;
    variant_name?: string;
    variant_value?: string;
    unit_price_usd: number;
    total_quantity?: number;
    price_after_discount?: number;
    discount_percentage?: number;
    main_image?: string;
    stock_availability?: string;
    created_at: string;
    updated_at?: string;
}

export interface IProductRating {
    id: number;
    rating: number;
    comment: string;
    created_at: string;
    user: {
        id: number;
        full_name: string;
    };
}

export interface RatingBreakdown {
    stars: number;
    count: number;
    percentage: number;
}

export interface VariantRatingData {
    ratings: Review[];
    average_rating: number;
    total_ratings: number;
    rating_breakdown: RatingBreakdown[];
}

export interface Review {
    id: number;
    user: {
        id: number;
        full_name: string;
    };
    rating: number;
    comment: string;
    created_at: string;
}

export interface ISignUpErrors {
    full_name: string[];
    email: string[];
    password: string[];
    password_confirmation: string[];
}

export interface ISearchResult {
    id: number;
    name: string;
    slug: string;
}

export interface IFavoriteItem {
    productId: number; // ID of the main product
    variantId: number | undefined; // ID of the specific variant wishlisted
    productName: string; // Name of the main product
    description?: string;
    slug: string;
    category: {
        id: number;
        name: string;
    };
    variantName?: string; // e.g., "Color", "Size"
    variantValue?: string; // e.g., "Red", "Large"
    price: number; // Price of the specific variant
    originalPrice?: number; // Original price of the variant
    image?: string; // Image (can be main product image or variant-specific if available)
    product_main_image?: string;
}

export interface IIndustrialCategory {
    id: number;
    name: string;
    slug: string;
    products_count: number;
    image: string;
}

export interface CartItem {
    id: number;
    productId: number;
    name: string;
    slug: string;
    image: string;
    price: number;
    originalPrice?: number;
    quantity?: number;
    totalQuantity?: number;
    description?: string;
    variantName?: string;
    variantValue?: string;
    category?: {
        id: number;
        name: string;
    };
}

export interface SmartBundleCTA {
    product_id: number;
    type: 'warning' | 'success';
    missing_quantity: number;
    target_quantity: number;
    discount_percentage: number;
    bundle_name: string | { [key: string]: string };
    is_applied: boolean;
}

export interface CartState {
    items: CartItem[];
    subtotal: number;
    total: number;
    couponDiscountValue: number | null;
    smartBundleCTAs?: SmartBundleCTA[];
}

export interface CartCategory {
    id: number;
    name: string;
}

export interface AgentDashboardInfo {
    total_commission_this_month: number;
    pending_commissions: number;
    total_commission_last_month: number;
    percent_change: number;
    top_commission_source: {
        name: string;
        commission: number;
    };
    monthly_target: number;
    monthly_target_progress: number;
    monthly_target_progress_percent: number;
    new_clients: number;
    repeat_buyers: number;
    conversion_rate: number;
    sales_performance: { month: string; value: number }[];
    top_selling_categories: { name: string; value: number }[];
}

export interface AgentOrder {
    id: number;
    customer: string;
    category: string;
    status: string;
    date: string;
    time: string;
    commission: number;
}

export interface AgentManualOrderLookupCustomer {
    id: number;
    full_name: string;
    email: string;
    phone_number?: string | null;
}

export interface AgentManualOrderLookupVariant {
    id: number;
    variant_name?: string | null;
    variant_value?: string | null;
    agent_price: number;
    price_after_discount: number;
    unit_price_usd: number;
    total_quantity: number;
}

export interface AgentManualOrderLookupProduct {
    id: number;
    name: string;
    slug: string;
    image?: string;
    variants: AgentManualOrderLookupVariant[];
}

export interface AgentManualOrderItem {
    id?: number;
    product_id: number;
    product_name?: string;
    product_variant_id: number;
    variant_value?: string | null;
    quantity: number;
    unit_price: number;
    line_total: number;
    stock?: number;
}

export interface AgentManualOrder {
    id: number;
    source: string;
    manual_sale_status: string;
    status: string;
    payment_status?: string;
    customer_name?: string;
    total: number;
    subtotal?: number;
    discount_amount?: number;
    sold_at?: string | null;
    created_at?: string | null;
    proof_photo_url?: string;
    items_count?: number | null;
    invoice_uuid?: string | null;
    special_sales_options?: Record<string, unknown> | null;
    customer?: {
        user_id?: number | null;
        first_name?: string | null;
        last_name?: string | null;
        email?: string | null;
        phone_number?: string | null;
        company_name?: string | null;
        address?: string | null;
        city?: string | null;
        province?: string | null;
        postal_code?: string | null;
    };
    items?: AgentManualOrderItem[];
}

export interface Paginated<T> {
    next_page_url: string | null;
    current_page: number;
    data: T[];
    last_page: number;
    per_page: number;
    total: number;
}

export interface IPaginationData {
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number | null;
    to: number | null;
}

export interface IPaginatedRatingsResponse {
    ratings: IProductRating[];
    pagination: IPaginationData;
}

export interface AgentDashboardInfoCommissionAndBonus {
    total_commission: number;
    pending_commissions: number;
    total_commission_last_month: number;
    total_commission_this_month: number;
    top_commission_source: {
        name: string;
        commission: number;
    };
    bonus: {
        amount: number;
        paid_at: string;
    };
    total_orders_this_month: number;
    average_commission_this_month: number;
    highest_commission_this_month: number;
    total_sales_this_month: number;
    commissions_per_period: { period: string; value: number }[];
}

export interface BillingInfo {
    firstName: string;
    lastName: string;
    email: string;
    companyName: string;
    streetAddress: string;
    province: string;
    phone: string;
    phoneCountryCode: string;
    city: string;
    postalCode: string;
    //   country: {
    //     label: string;
    //     value: string;
    //   };

    //   paymentMethod: 'visa' | 'mastercard' | 'applepay';
    //   cardNumber: string;
    //   expiryDate: string;
    //   cvv: string;
    //   nameOnCard: string;
}

export interface CouponState {
    code: string;
    isApplied: boolean;
    discount: number;
    error: string
}

export type ids = {
    productId: number,
    productVariantId: number
}

export interface ChatMessage {
    id?: number;
    role: 'user' | 'assistant';
    content: string;
    created_at?: string;
}

export interface ChatbotState {
    isOpen: boolean;
    messages: ChatMessage[];
    sessionId: string | null;
    isLoading: boolean;
    currentProductSlug: string | null;
}
