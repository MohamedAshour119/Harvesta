import axios, {
    type AxiosInstance,
    type AxiosResponse,
    type AxiosRequestConfig,
    type InternalAxiosRequestConfig,
} from "axios";
import i18n from "./i18n";
import type {
    IFavoriteItem,
    Paginated,
    AgentOrder,
    AgentManualOrder,
    AgentManualOrderLookupCustomer,
    AgentManualOrderLookupProduct,
    ids,
} from "./types";

// Define base API URL
// const API_URL = import.meta.env.VITE_API_URL || 'http://localhost:8000/api';

// Define response types for better type safety
interface ApiResponse<T = any> {
    data: T;
    message?: string;
    status: number;
    success: boolean;
}

export interface SelectOption {
    label: string;
    value: string;
}

type RetryableAxiosConfig = InternalAxiosRequestConfig & {
    _retry?: boolean;
    skipAuthRefresh?: boolean;
};

let accessToken: string | null = null;
let accessTokenExpirationDate: string | null = null;
let accessTokenRefreshTimeoutId: number | null = null;
const REFRESH_SKEW_MS = 5 * 60 * 1000;

export const clearLegacyAuthStorage = () => {
    localStorage.removeItem("accessToken");
    localStorage.removeItem("accessTokenExpirationDate");
    localStorage.removeItem("refreshToken");
    localStorage.removeItem("refreshTokenExpirationDate");
};

export const setAccessToken = (token: string | null, expiresAt?: string | null) => {
    accessToken = token;
    accessTokenExpirationDate = expiresAt ?? null;
    scheduleAccessTokenRefresh();
};

export const getAccessTokenExpirationDate = () => accessTokenExpirationDate;
export const hasAccessToken = () => Boolean(accessToken);

export const clearAuthTokens = () => {
    setAccessToken(null, null);
    clearLegacyAuthStorage();
};

const clearScheduledAccessTokenRefresh = () => {
    if (accessTokenRefreshTimeoutId !== null) {
        window.clearTimeout(accessTokenRefreshTimeoutId);
        accessTokenRefreshTimeoutId = null;
    }
};

const scheduleAccessTokenRefresh = () => {
    clearScheduledAccessTokenRefresh();

    if (!accessToken || !accessTokenExpirationDate) {
        return;
    }

    const expiresAtTime = new Date(accessTokenExpirationDate).getTime();

    if (Number.isNaN(expiresAtTime)) {
        return;
    }

    const refreshIn = Math.max(0, expiresAtTime - Date.now() - REFRESH_SKEW_MS);

    accessTokenRefreshTimeoutId = window.setTimeout(async () => {
        try {
            const response = await requestRefreshToken();
            const tokens = response.data?.data;

            if (!tokens?.access_token?.plain_token) {
                throw new Error("Invalid refresh token response.");
            }

            setAccessToken(tokens.access_token.plain_token, tokens.access_token.expires_at);
        } catch (error) {
            clearAuthTokens();
            window.dispatchEvent(new CustomEvent("auth:session-expired"));
        }
    }, refreshIn);
};

// Create and configure Axios instance
const axiosInstance: AxiosInstance = axios.create({
    baseURL: "/api/",
    headers: {
        "Content-Type": "application/json",
        Accept: "application/json",
    },
    timeout: 30000, // 30 seconds timeout
    withCredentials: true,
});

let refreshTokenRequest: Promise<AxiosResponse<ApiResponse>> | null = null;

const requestRefreshToken = async (): Promise<AxiosResponse<ApiResponse>> => {
    if (!refreshTokenRequest) {
        refreshTokenRequest = axiosInstance.post(
            "/auth/refresh-token",
            {},
            { skipAuthRefresh: true } as RetryableAxiosConfig
        );
    }

    try {
        return await refreshTokenRequest;
    } finally {
        refreshTokenRequest = null;
    }
};

// Add request interceptor for auth token
axiosInstance.interceptors.request.use(
    (config) => {
        if (accessToken) {
            config.headers.Authorization = `Bearer ${accessToken}`;
        }
        return config;
    },
    (error) => Promise.reject(error)
);

axiosInstance.interceptors.request.use((config) => {
    const lang = i18n.language; // Get current language from i18next
    if (lang) {
        config.headers["Accept-Language"] = lang;
    }
    return config;
});

axiosInstance.interceptors.response.use(
    (response) => response,
    async (error) => {
        const originalRequest = error.config as RetryableAxiosConfig | undefined;
        const status = error.response?.status;
        const requestUrl = String(originalRequest?.url || "");

        if (
            status !== 401 ||
            !originalRequest ||
            originalRequest._retry ||
            originalRequest.skipAuthRefresh ||
            requestUrl.includes("/auth/refresh-token") ||
            requestUrl.includes("/auth/login") ||
            requestUrl.includes("/auth/register")
        ) {
            return Promise.reject(error);
        }

        originalRequest._retry = true;

        try {
            const response = await requestRefreshToken();
            const tokens = response.data?.data;

            if (!tokens?.access_token?.plain_token) {
                throw new Error("Invalid refresh token response.");
            }

            setAccessToken(tokens.access_token.plain_token, tokens.access_token.expires_at);

            originalRequest.headers = originalRequest.headers ?? {};
            originalRequest.headers.Authorization = `Bearer ${tokens.access_token.plain_token}`;

            return axiosInstance(originalRequest);
        } catch (refreshError) {
            clearAuthTokens();

            // Notify Redux to clear auth state
            window.dispatchEvent(new CustomEvent("auth:session-expired"));

            return Promise.reject(refreshError);
        }
    }
);

// Generic API request method for custom endpoints
const apiRequest = async <T = any>(
    method: string,
    url: string,
    data?: any,
    config?: AxiosRequestConfig
): Promise<AxiosResponse<T>> => {
    method = method.toLowerCase();

    if (method === "get") {
        return axiosInstance.get<T>(url, { ...config, params: data });
    } else if (method === "post") {
        return axiosInstance.post<T>(url, data, config);
    } else if (method === "put") {
        return axiosInstance.put<T>(url, data, config);
    } else if (method === "delete") {
        return axiosInstance.delete<T>(url, { ...config, data });
    } else {
        throw new Error(`Unsupported method: ${method}`);
    }
};

// Auth services
const AuthService = {
    login: async (
        email: string,
        password: string
    ): Promise<AxiosResponse<ApiResponse>> => {
        return axiosInstance.post("/auth/login", { email, password });
    },

    register: async (userData: {
        full_name: string;
        email: string;
        password: string;
        password_confirmation: string;
    }): Promise<AxiosResponse<ApiResponse>> => {
        return axiosInstance.post("/auth/register", userData);
    },

    logout: async (): Promise<AxiosResponse<ApiResponse>> => {
        return axiosInstance.post(
            "/auth/logout",
            {},
            { skipAuthRefresh: true } as RetryableAxiosConfig
        );
    },

    getUser: async (): Promise<AxiosResponse<ApiResponse>> => {
        return axiosInstance.get("/auth/user");
    },

    refreshToken: async (
        refreshToken?: string
    ): Promise<AxiosResponse<ApiResponse>> => {
        return axiosInstance.post(
            "/auth/refresh-token",
            refreshToken ? { refresh_token: refreshToken } : {},
            { skipAuthRefresh: true } as RetryableAxiosConfig
        );
    },

    verifyToken: async (token: string): Promise<AxiosResponse<ApiResponse>> => {
        return axiosInstance.post("auth/verify-token", { token });
    },
    consumePayload: async (payload: string): Promise<AxiosResponse<ApiResponse>> => {
        return axiosInstance.post("auth/consume-payload", { payload });
    },
    forgotPassword: async (email: string): Promise<AxiosResponse<ApiResponse>> => {
        return axiosInstance.post("auth/forgot-password", { email });
    },
    resetPassword: async (data: any): Promise<AxiosResponse<ApiResponse>> => {
        return axiosInstance.post("auth/reset-password", data);
    },
};

// Product services
const ProductService = {
    getHomeProducts: async (
        params?: any
    ): Promise<AxiosResponse<ApiResponse>> => {
        return axiosInstance.get("/home", { params });
    },

    getProduct: async (
        id: number | string
    ): Promise<AxiosResponse<ApiResponse>> => {
        return axiosInstance.get(`/products/${id}`);
    },

    getProductBySlug: async (
        slug: string
    ): Promise<AxiosResponse<ApiResponse>> => {
        return axiosInstance.get(`products/${slug}`);
    },

    getProductsByIds: async (
        ids: ids[]
    ): Promise<AxiosResponse<ApiResponse<any[]>>> => {
        return axiosInstance.post("/products/batch", { ids });
    },

    getAllProducts: async (perPage: number, currentPage: number): Promise<AxiosResponse<ApiResponse<any[]>>> => {
        return axiosInstance.get("/products", { params: { per_page: perPage, page: currentPage } });
    },

    requestCustomProduct: async (data: FormData): Promise<AxiosResponse<ApiResponse>> => {
        return axiosInstance.post("/request-custom-product", data, {
            headers: {
                'Content-Type': 'multipart/form-data',
            },
        });
    },
};

// Cart services
const CartService = {
    getCart: async (): Promise<AxiosResponse<ApiResponse>> => {
        return axiosInstance.get("/cart");
    },

    addToCart: async (
        productId: number,
        quantity: number = 1
    ): Promise<AxiosResponse<ApiResponse>> => {
        return axiosInstance.post("/cart/add", {
            product_id: productId,
            quantity,
        });
    },

    updateCartItem: async (
        itemId: number,
        quantity: number
    ): Promise<AxiosResponse<ApiResponse>> => {
        return axiosInstance.put(`/cart/items/${itemId}`, { quantity });
    },

    removeCartItem: async (
        itemId: number
    ): Promise<AxiosResponse<ApiResponse>> => {
        return axiosInstance.delete(`/cart/items/${itemId}`);
    },

    clearCart: async (): Promise<AxiosResponse<ApiResponse>> => {
        return axiosInstance.delete("/cart");
    },

    getCartCategories: async (): Promise<AxiosResponse<ApiResponse>> => {
        return axiosInstance.get("/cart-categories");
    },
};

// Wishlist services
const WishlistService = {
    fetchWishlist: async (): Promise<
        AxiosResponse<ApiResponse<IFavoriteItem[]>>
    > => {
        return axiosInstance.get<ApiResponse<IFavoriteItem[]>>(`/wishlist`);
    },

    addToWishlist: async (
        productId: number,
        variantId: number | undefined
    ): Promise<AxiosResponse<ApiResponse>> => {
        const response = await axiosInstance.post(`/wishlist/add`, {
            product_id: productId,
            variant_id: variantId,
        });

        // Dispatch custom event to update UI
        const event = new CustomEvent("favoriteStatusChanged", {
            detail: { productId, variantId, canAddToFavorites: false },
        });
        window.dispatchEvent(event);

        return response;
    },

    removeFromWishlist: async (
        productId: number,
        variantId: number | undefined
    ): Promise<AxiosResponse<ApiResponse>> => {
        // For DELETE requests with a body, axios expects data to be in the config object.
        const response = await axiosInstance.delete(`/wishlist/remove`, {
            data: { product_id: productId, variant_id: variantId },
        });

        // Dispatch custom event to update UI
        // Note: The querySelector for productCards might need adjustment if it was product-ID specific.
        // For now, the event carries enough info for components to react.
        const event = new CustomEvent("favoriteStatusChanged", {
            detail: { productId, variantId, canAddToFavorites: true },
        });
        window.dispatchEvent(event);

        return response;
    },
};

const SearchServices = {
    searchForProducts: async (
        query: string
    ): Promise<AxiosResponse<ApiResponse>> => {
        return axiosInstance.get(`/search/${query}`);
    },
    searchForProductsKeyword: async (
        query: string
    ): Promise<AxiosResponse<ApiResponse>> => {
        return axiosInstance.get(`/search-keyword/${query}`);
    },
    semanticSearch: async (
        query: string
    ): Promise<AxiosResponse<ApiResponse>> => {
        return axiosInstance.get(`/semantic-search`, { params: { query } });
    },
    visualSearch: async (
        image: File | string
    ): Promise<AxiosResponse<ApiResponse>> => {
        if (image instanceof File) {
            const formData = new FormData();
            formData.append('image', image);
            return axiosInstance.post('/visual-search', formData, {
                headers: { 'Content-Type': 'multipart/form-data' }
            });
        }
        return axiosInstance.post('/visual-search', { image_base64: image });
    },
};

const IndustrialCategoryServices = {
    getIndustrialCategories: async (): Promise<AxiosResponse<ApiResponse>> => {
        return axiosInstance.get("/industrial-categories");
    },
};

const ShopServices = {
    getShopPageData: async (): Promise<AxiosResponse<ApiResponse>> => {
        return axiosInstance.get("/shop");
    },
    getProductsByFilter: async (
        filter: string,
        minPrice?: number,
        maxPrice?: number,
        searchQuery?: string,
        page?: number
    ): Promise<AxiosResponse<ApiResponse>> => {
        const params = new URLSearchParams();
        if (filter) {
            params.append("categories", filter); // 'categories' can be slugs or 'most-popular'/'newest'
        }
        if (minPrice !== undefined) {
            params.append("min_price", minPrice.toString());
        }
        if (maxPrice !== undefined && maxPrice > 0) {
            // Ensure maxPrice is meaningful
            params.append("max_price", maxPrice.toString());
        }
        if (searchQuery) {
            params.append("search_query", searchQuery);
        }
        if (page) {
            params.append("page", page.toString());
        }
        return await axiosInstance.get(`/shop/filters?${params.toString()}`);
    },

    getProductsByCategoryName: async (
        categoryName: string
    ): Promise<AxiosResponse<ApiResponse>> => {
        return axiosInstance.get(`/shop/category/${categoryName}`);
    },
};

const AgentServices = {
    getSalesPerformance: async (
        year: string
    ): Promise<
        AxiosResponse<ApiResponse<{ month: string; value: number }[]>>
    > => {
        return axiosInstance.get(
            `/agents/dashboard-info/sales-performance/${year}`
        );
    },
    getAgentDashboardInfo: async (): Promise<AxiosResponse<ApiResponse>> => {
        return axiosInstance.get("/agents/dashboard-info");
    },
    getRecentOrders: async (
        perPage: number = 10,
        page: number = 1
    ): Promise<AxiosResponse<ApiResponse<Paginated<AgentOrder>>>> => {
        return axiosInstance.get("/agents/dashboard-info/orders", {
            params: { per_page: perPage, page },
        });
    },
    getAgentDashboardInfoCommissionAndBonus: async (): Promise<AxiosResponse<ApiResponse>> => {
        return axiosInstance.get("/agents/dashboard-info/commission-and-bonus");
    },

    getCommissionsTimeline: async (
        perPage: number = 10,
        page: number = 1
    ): Promise<AxiosResponse<ApiResponse<Paginated<AgentOrder>>>> => {
        return axiosInstance.get("/agents/dashboard-info/commissions-timeline", {
            params: { per_page: perPage, page },
        });
    },
    getManualOrders: async (
        perPage: number = 10,
        page: number = 1
    ): Promise<AxiosResponse<ApiResponse<Paginated<AgentManualOrder>>>> => {
        return axiosInstance.get("/agents/manual-orders", {
            params: { per_page: perPage, page },
        });
    },
    submitManualOrder: async (
        data: FormData
    ): Promise<AxiosResponse<ApiResponse<AgentManualOrder>>> => {
        return axiosInstance.post("/agents/manual-orders", data, {
            headers: {
                "Content-Type": "multipart/form-data",
            },
        });
    },
    lookupManualOrderCustomers: async (
        search: string
    ): Promise<AxiosResponse<ApiResponse<AgentManualOrderLookupCustomer[]>>> => {
        return axiosInstance.get("/agents/manual-orders/lookups/customers", {
            params: { search },
        });
    },
    lookupManualOrderProducts: async (
        search: string
    ): Promise<AxiosResponse<ApiResponse<AgentManualOrderLookupProduct[]>>> => {
        return axiosInstance.get("/agents/manual-orders/lookups/products", {
            params: { search },
        });
    },
};

// Rating services
const RatingService = {
    submitRating: async (
        productId: number,
        rating: number | null,
        comment?: string
    ): Promise<AxiosResponse<ApiResponse>> => {
        return axiosInstance.post("/ratings", {
            product_id: productId,
            rating,
            comment,
        });
    },

    getUserRating: async (
        productId: number
    ): Promise<AxiosResponse<ApiResponse>> => {
        return axiosInstance.get(`/ratings/product/${productId}/user`);
    },

    getPaginatedRatings: async (
        productId: number,
        page: number = 1,
        perPage: number = 5
    ): Promise<AxiosResponse<ApiResponse>> => {
        return axiosInstance.get(`/ratings/product/${productId}/paginated`, {
            params: {
                page,
                per_page: perPage,
            },
        });
    },
};

// Coupon services
const CouponService = {
    applyCoupon: async (couponCode: string): Promise<AxiosResponse<ApiResponse>> => {
        return axiosInstance.post("/apply-coupon", { coupon: couponCode });
    },
};


// Checkout services
const CheckoutService = {
    getShippingDestinations: async (): Promise<AxiosResponse<ApiResponse<SelectOption[]>>> => {
        return axiosInstance.get("/shipping-destinations");
    },
    getShippingCost: async (data: any): Promise<AxiosResponse<ApiResponse>> => {
        return axiosInstance.post("/shipping-cost", data);
    },
    checkout: async (data: any): Promise<AxiosResponse<ApiResponse>> => {
        return axiosInstance.post("/checkout", data);
    },
    checkIfCouponApplied: async (): Promise<AxiosResponse<ApiResponse>> => {
        return axiosInstance.get("/check-if-coupon-applied");
    },
};

const SendMessageService = {
    sendMessage: async (data: any): Promise<AxiosResponse<ApiResponse>> => {
        return axiosInstance.post("/send-message", data);
    },
};

// Recombee Recommendation Services
const RecommendationService = {
    getForUser: async (count: number = 10): Promise<AxiosResponse<ApiResponse>> => {
        return axiosInstance.get('/recommendations/user', { params: { count } });
    },
    getForItem: async (productId: number | string, count: number = 10): Promise<AxiosResponse<ApiResponse>> => {
        return axiosInstance.get(`/recommendations/item/${productId}`, { params: { count } });
    },
    getFrequentForItem: async (productId: number | string, count: number = 3): Promise<AxiosResponse<ApiResponse>> => {
        return axiosInstance.get(`/recommendations/frequent/${productId}`, { params: { count } });
    },
    trackCartAddition: async (productId: number, price?: number): Promise<AxiosResponse<ApiResponse>> => {
        return axiosInstance.post('/cart/track', { product_id: productId, price });
    }
};

// Profile services
const ProfileService = {
    getProfile: async (): Promise<AxiosResponse<ApiResponse>> => {
        return axiosInstance.get("/profile");
    },
    updateProfile: async (data: { full_name: string }): Promise<AxiosResponse<ApiResponse>> => {
        return axiosInstance.patch("/profile", data);
    },
    deleteAccount: async (): Promise<AxiosResponse<ApiResponse>> => {
        return axiosInstance.delete("/profile");
    },
    getOrders: async (page: number = 1, perPage: number = 10): Promise<AxiosResponse<ApiResponse>> => {
        return axiosInstance.get("/profile/orders", { params: { page, per_page: perPage } });
    },
    downloadInvoice: async (orderId: number): Promise<AxiosResponse<Blob>> => {
        return axiosInstance.get(`/profile/orders/${orderId}/invoice`, {
            responseType: 'blob',
        });
    },
    getAgentActivity: async (page: number = 1, perPage: number = 10): Promise<AxiosResponse<ApiResponse>> => {
        return axiosInstance.get("/profile/agent-activity", { params: { page, per_page: perPage } });
    },
};

const ChatbotService = {
    initSession: async (): Promise<AxiosResponse<ApiResponse>> => {
        return axiosInstance.post('/chatbot/session');
    },
    sendMessage: async (
        message: string,
        sessionId: string,
        productSlug?: string | null
    ): Promise<AxiosResponse<ApiResponse>> => {
        return axiosInstance.post('/chatbot/send', {
            message,
            session_id: sessionId,
            product_slug: productSlug || undefined,
        });
    },
    getHistory: async (
        sessionId: string
    ): Promise<AxiosResponse<ApiResponse>> => {
        return axiosInstance.get('/chatbot/history', {
            params: { session_id: sessionId },
        });
    },
};

// Export all services
const ApiClient = {
    auth: AuthService,
    products: ProductService,
    cart: CartService,
    wishlist: WishlistService,
    search: SearchServices,
    industrialCategory: IndustrialCategoryServices,
    shop: ShopServices,
    agent: AgentServices,
    ratings: RatingService,
    coupon: CouponService,
    checkout: CheckoutService,
    sendMessage: SendMessageService,
    recommendations: RecommendationService,
    profile: ProfileService,
    chatbot: ChatbotService,
    request: apiRequest,
    // Expose the axios instance for direct access if needed
    axios: axiosInstance,
};

export const consumeImpersonationPayload = (payload: string) => {
    return ApiClient.axios.post('/auth/consume-impersonation-payload', { payload });
};

export default ApiClient;
