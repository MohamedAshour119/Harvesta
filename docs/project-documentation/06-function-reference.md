# المرحلة 6: Function Reference

هذا الملف يشرح الدوال التي تخدم التطبيق مباشرة. تم استبعاد constructors والدوال `private`. تم أيضا عدم تفصيل helpers الصرفة إلا إذا كانت `protected` وتؤثر في behavior واضح.

## Controllers

### Auth

| الدالة | الطبقة | الوصف |
| --- | --- | --- |
| `AuthController@register` | Controller | يستقبل بيانات التسجيل، يستدعي `AuthService@registerUser`، ويرجع user/access token مع refresh cookie. |
| `AuthController@login` | Controller | يتحقق من بيانات الدخول، يصدر token pair، ويضع refresh token في cookie. |
| `AuthController@logout` | Controller | يحذف tokens عبر access token أو refresh token ثم يحذف refresh cookie. |
| `AuthController@refreshToken` | Controller | يجدد token pair باستخدام refresh cookie أو `refresh_token` في body. |
| `AuthController@verifyToken` | Controller | يتحقق من access token مرسل في request body. |
| `AuthController@consumePayload` | Controller | يفك payload مشفر يحتوي tokens، يتحقق من access token، ويعيد session للواجهة. |
| `AuthController@consumeImpersonationPayload` | Controller | يفك payload impersonation مشفر باستخدام private key وAES data. |
| `AuthController@forgotPassword` | Controller | يرسل reset password link عبر Laravel Password broker. |
| `AuthController@resetPassword` | Controller | يغير password بعد validation للtoken/email/password. |

### Products, Shop, Cart, Wishlist

| الدالة | الطبقة | الوصف |
| --- | --- | --- |
| `ProductController@index` | Controller | يرجع بيانات home products من `HomePageService`. |
| `ProductController@getAllProducts` | Controller | يرجع قائمة products paginated/filtered من `ProductService`. |
| `ProductController@getTopProducts` | Controller | يرجع المنتجات الأعلى مبيعا. |
| `ProductController@getProductBySlug` | Controller | يرجع product details بالـ slug أو error عند عدم وجوده. |
| `ProductController@requestCustomProduct` | Controller | يستقبل طلب منتج مخصص ويرسله إلى `ProductService`. |
| `ShopPageController@filterProducts` | Controller | يمرر filters والسعر والبحث إلى `ShopService`. |
| `ShopPageController@getProductsByCategoryName` | Controller | يرجع products حسب اسم category. |
| `CartController@getProductsByIds` | Controller | يستقبل IDs من cart ويرجع بيانات المنتجات والـ variants. |
| `WishlistController@index` | Controller | يرجع wishlist للمستخدم الحالي. |
| `WishlistController@add` | Controller | يضيف product/variant إلى wishlist ويرسل interaction للتوصيات. |
| `WishlistController@remove` | Controller | يزيل product/variant من wishlist ويرسل remove interaction. |
| `IndustrialCategories@index` | Controller | يرجع categories المتاحة. |

### Search and AI

| الدالة | الطبقة | الوصف |
| --- | --- | --- |
| `SearchController@search` | Controller | ينفذ keyword search من route parameter. |
| `SearchController@searchKeyword` | Controller | ينفذ search keyword مخصص ويرجع results/categories. |
| `SearchController@semanticSearch` | Controller | يجمع literal وsemantic results ويعيد categories ذات صلة. |
| `SearchController@visualSearch` | Controller | يستقبل image/file أو base64 ويعيد visual search results. |
| `ChatbotController@initSession` | Controller | ينشئ customer chat session آمنة. |
| `ChatbotController@sendMessage` | Controller | ينفذ pipeline كامل للرسالة: sanitize, safety, context, Groq, response guard, save history. |
| `ChatbotController@getHistory` | Controller | يرجع history إذا كانت session صالحة ومملوكة للطلب. |
| `AdminChatbotController@initSession` | Controller | ينشئ admin chat session بعد التحقق من super admin. |
| `AdminChatbotController@sendMessage` | Controller | يبني admin data context ويرسل الرسالة إلى Groq admin model. |
| `AdminChatbotController@getHistory` | Controller | يرجع history لجلسة admin chatbot. |
| `RecommendationController@forUser` | Controller | يرجع توصيات Recombee للمستخدم الحالي. |
| `RecommendationController@forItem` | Controller | يرجع توصيات مشابهة لمنتج محدد. |
| `RecommendationController@frequentlyBoughtTogether` | Controller | يرجع منتجات تشترى مع المنتج الحالي باستخدام cross-sell logic. |
| `CartTrackingController@track` | Controller | يسجل إضافة منتج إلى cart كـ Recombee interaction. |

### Orders, Payments, Agents, Profile

| الدالة | الطبقة | الوصف |
| --- | --- | --- |
| `PaymentController@applyCoupon` | Controller | يتحقق من coupon input ويطبق coupon للمستخدم. |
| `PaymentController@getShippingCost` | Controller | يحسب تكلفة الشحن من city/items. |
| `PaymentController@getShippingDestinations` | Controller | يرجع قائمة المدن/الوجهات المدعومة للشحن. |
| `PaymentController@tapCheckout` | Controller | ينشئ order وTap charge ويحسب الخصومات والشحن. |
| `PaymentController@stripeCheckout` | Controller | ينشئ order وStripe checkout session. |
| `PaymentController@paymentCallback` | Controller | يعالج Tap callback ويحدث order/inventory/coupon/recommendations. |
| `PaymentController@checkIfCouponApplied` | Controller | يرجع coupon المعلق للمستخدم إن وجد. |
| `PaymentController@stripeCallback` | Controller | يعالج Stripe redirect callback ويحدث order بعد الدفع. |
| `PaymentController@stripeWebhook` | Controller | يتحقق من Stripe signature ويعالج `checkout.session.completed`. |
| `AgentController@dashboardInfo` | Controller | يرجع dashboard metrics للوكيل. |
| `AgentController@salesPerformance` | Controller | يرجع أداء المبيعات لسنة محددة. |
| `AgentController@recentOrders` | Controller | يرجع أحدث طلبات الوكيل مع pagination. |
| `AgentController@commissionAndBonus` | Controller | يرجع ملخص العمولات والمكافآت. |
| `AgentController@commissionsTimeline` | Controller | يرجع timeline للعمولات. |
| `AgentController@impersonate` | Controller | ينشئ payload يسمح للوكيل بفتح تجربة مستخدم محدد. |
| `AgentManualOrderController@index` | Controller | يعرض manual orders الخاصة بالوكيل. |
| `AgentManualOrderController@store` | Controller | ينشئ manual sale order للوكيل. |
| `AgentManualOrderController@show` | Controller | يعرض manual order إذا كان يخص الوكيل. |
| `AgentManualOrderController@customerLookup` | Controller | يبحث عن عملاء لاستخدامهم في manual order. |
| `AgentManualOrderController@productLookup` | Controller | يبحث عن منتجات وvariants لإنشاء manual order. |
| `ProfileController@show` | Controller | يرجع profile data للمستخدم الحالي. |
| `ProfileController@update` | Controller | يحدث بيانات profile. |
| `ProfileController@destroy` | Controller | يحذف الحساب soft delete ويلغي tokens. |
| `ProfileController@orders` | Controller | يرجع طلبات المستخدم paginated. |
| `ProfileController@invoice` | Controller | يرجع invoice PDF لطلب مسموح. |
| `ProfileController@agentActivity` | Controller | يرجع نشاط الوكيل إذا كان المستخدم agent. |
| `RatingController@submitRating` | Controller | ينشئ أو يحدث rating لمنتج. |
| `RatingController@getProductRatings` | Controller | يرجع ratings الخاصة بمنتج. |
| `RatingController@getProductStats` | Controller | يرجع average/total/breakdown. |
| `RatingController@getUserRating` | Controller | يرجع rating الخاص بالمستخدم الحالي. |
| `RatingController@getPaginatedRatings` | Controller | يرجع ratings paginated. |
| `MessageController@sendMessage` | Controller | يرسل contact/support message عبر `HomePageService`. |

## Services

### Auth and User

| الدالة | الطبقة | الوصف |
| --- | --- | --- |
| `AuthService@registerUser` | Service | ينشئ user ويصدر token pair. |
| `AuthService@loginUser` | Service | يتحقق من user credentials ويصدر token pair. |
| `AuthService@logoutUser` | Service | يحذف tokens لمستخدم authenticated. |
| `AuthService@logoutUsingRefreshToken` | Service | يحذف tokens اعتمادا على refresh token فقط. |
| `AuthService@refreshToken` | Service | يتحقق من refresh token، يحذف access tokens القديمة، ويصدر token pair جديد. |
| `AuthService@verifyToken` | Service | يفوض التحقق من access token إلى repository. |
| `AuthService@sendPasswordResetLink` | Service | يرسل reset password link. |
| `AuthService@resetPassword` | Service | يغير password ويطلق `PasswordReset`. |
| `UserContextService@buildUserContext` | Service | يبني context مختصر عن طلبات ومنتجات المستخدم لاستخدامه في chatbot. |
| `UserRoleAssignmentService@assign` | Service | يطبق قواعد تعيين roles مع حماية super admin. |
| `UserRoleAssignmentService@resolveAuthorizedActor` | Protected Service | يحدد actor المصرح بتنفيذ role assignment. |
| `UserRoleAssignmentService@ensureTargetUserCanBeUpdated` | Protected Service | يتحقق من أن target user يمكن تحديث دوره. |
| `UserRoleAssignmentService@anotherSuperAdminAlreadyExists` | Protected Service | يمنع وجود أكثر من super admin عند اللزوم. |

### Products, Shop, Cart, Inventory

| الدالة | الطبقة | الوصف |
| --- | --- | --- |
| `HomePageService@index` | Service | يرجع بيانات home page من repository. |
| `HomePageService@sendMessage` | Service | يرسل رسائل contact form عبر Brevo أو mail fallback. |
| `ProductService@getAllProducts` | Service | يرجع قائمة products من repository. |
| `ProductService@getTopProducts` | Service | يرجع top products من repository. |
| `ProductService@getProductBySlug` | Service | يرجع product details وsimilar products. |
| `ProductService@requestCustomProduct` | Service | يحفظ طلب منتج مخصص. |
| `IndustrialCategoryService@index` | Service | يرجع categories من repository. |
| `CartService@getProductsByIds` | Service | يرجع المنتجات المطلوبة للـ cart. |
| `ShopService@filterProducts` | Service | يدمج filters مع semantic ids ثم يستدعي repository. |
| `ShopService@getProductsByCategoryName` | Service | يرجع products حسب category. |
| `InventoryAllocationService@allocate` | Service | يخصم كميات المخزون للـ variants المطلوبة. |
| `SmartBundleService@calculateCartCTAs` | Service | يحسب رسائل وخصومات smart bundles على عناصر السلة. |

### Orders and Payments

| الدالة | الطبقة | الوصف |
| --- | --- | --- |
| `PaymentService@applyCoupon` | Service | يطبق قواعد صلاحية الكوبون ويحدث pending coupon state. |
| `PaymentService@checkIfCouponApplied` | Service | يحرر pending coupons المنتهية ويرجع الكوبون الحالي. |
| `PaymentService@getShippingCost` | Service | يحسب الشحن من repository. |
| `PaymentService@ensureOrderMinimumValue` | Service | يتحقق من الحد الأدنى لقيمة الطلب. |
| `TapPaymentService@sendRequest` | Protected Service | ينفذ HTTP requests إلى Tap API. |
| `TapPaymentService@createCharge` | Service | ينشئ Tap charge. |
| `TapPaymentService@retrieveCharge` | Service | يجلب Tap charge. |
| `TapPaymentService@refundCharge` | Service | ينفذ refund في Tap. |
| `TapPaymentService@updateItemsQuantityInInventories` | Service | يخصم المخزون بعد نجاح Tap payment. |
| `StripePaymentService@createCheckoutSession` | Service | ينشئ Stripe checkout session. |
| `StripePaymentService@retrieveSession` | Service | يجلب Stripe session. |
| `StripePaymentService@updateItemsQuantityInInventories` | Service | يخصم المخزون بعد نجاح Stripe payment. |
| `InvoiceService@generatePdf` | Service | ينشئ PDF invoice لطلب. |
| `InvoiceService@getFileName` | Service | يولد اسم ملف invoice. |
| `ManualOrderApprovalService@approve` | Service | يعتمد manual sale ويخصم المخزون. |
| `ManualOrderApprovalService@reject` | Service | يرفض manual sale بدون خصم المخزون. |

### Agents and Profile

| الدالة | الطبقة | الوصف |
| --- | --- | --- |
| `AgentDashboardService@dashboardInfo` | Service | يغلف dashboard data في resource/response مناسب. |
| `AgentDashboardService@salesPerformance` | Service | يجلب sales performance من repository. |
| `AgentDashboardService@recentOrders` | Service | يجلب recent orders للوكيل. |
| `AgentDashboardService@commissionAndBonus` | Service | يجلب commission/bonus summary. |
| `AgentDashboardService@commissionsTimeline` | Service | يجلب timeline paginated للعمولات. |
| `AgentManualOrderService@listForAgent` | Service | يرجع manual orders الخاصة بوكيل. |
| `AgentManualOrderService@findForAgent` | Service | يتحقق أن order يخص الوكيل ويعيده. |
| `AgentManualOrderService@store` | Service | ينشئ manual sale order ويطبق approval rules. |
| `AgentManualOrderService@customerLookup` | Service | يبحث عن عملاء صالحين للوكيل. |
| `AgentManualOrderService@productLookup` | Service | يبحث عن منتجات مع variants متاحة للطلب اليدوي. |
| `ProfileService@getProfileData` | Service | يجمع بيانات profile وsummaries حسب نوع المستخدم. |
| `ProfileService@updateProfile` | Service | يحدث بيانات المستخدم. |
| `ProfileService@deleteAccount` | Service | يلغي tokens ويعمل soft delete. |
| `ProfileService@getPaginatedOrders` | Service | يرجع طلبات المستخدم paginated. |
| `ProfileService@getAgentActivity` | Service | يرجع نشاط الوكيل. |
| `ProfileService@downloadInvoice` | Service | يتحقق من صلاحية الوصول ويولد invoice. |

### Search, Chatbot, AI

| الدالة | الطبقة | الوصف |
| --- | --- | --- |
| `SearchService@search` | Service | ينفذ search عادي من repository. |
| `SearchService@searchKeyword` | Service | ينفذ keyword search مخصص. |
| `CatalogSearchService@search` | Service | يجمع literal وsemantic candidates ويرتب النتائج. |
| `SemanticSearchService@search` | Service | ينشئ embedding للquery ويقارن مع product embeddings. |
| `SemanticSearchService@searchByImage` | Service | ينشئ image embedding ويقارن مع product image embeddings. |
| `JinaEmbeddingService@generateEmbeddings` | Service | يطلب embeddings من Jina لمجموعة inputs. |
| `JinaEmbeddingService@generateSingleEmbedding` | Service | ينشئ embedding لنص واحد. |
| `JinaEmbeddingService@generatePassageEmbeddings` | Service | ينشئ passage embeddings للمنتجات. |
| `JinaEmbeddingService@generateImageEmbedding` | Service | ينشئ embedding لصورة أو image data URI. |
| `GroqService@chat` | Service | يرسل messages إلى Groq ويرجع content/tokens. |
| `SearchQueryUnderstandingService@analyze` | Service | يحلل query باستخدام Groq ويعيد intent/terms. |
| `SearchQueryUnderstandingService@shouldAnalyze` | Service | يحدد هل query تستحق AI analysis. |
| `FuzzyProductMatcher@match` | Service | يطابق query مع المنتجات باستخدام fuzzy scoring. |
| `ProductSearchDocumentBuilder@build` | Service | يبني search document من product data. |
| `ChatbotContextBuilder@buildSystemPrompt` | Service | يبني prompt للـ customer chatbot. |
| `ChatbotContextBuilder@detectIntent` | Service | يكشف intent لرسالة chatbot. |
| `ChatbotIntentDetector@detect` | Service | يصنف الرسالة إلى greeting/purchase/catalog وغيرها. |
| `ChatProductLinker@linkProducts` | Service | يحول أسماء المنتجات المعروفة إلى markdown links. |
| `ChatProductResponseGuard@enforce` | Service | يفرض اتساق الرد مع الكتالوج وتوفر المنتجات. |
| `ConversationMemoryService@buildContext` | Service | يبني memory context للمحادثة. |
| `ConversationMemoryService@shouldSummarize` | Service | يقرر متى يجب تلخيص المحادثة. |
| `ConversationMemoryService@generateSummary` | Service | يولد summary ويحفظ topic context. |
| `MessageSanitizer@sanitize` | Service | ينظف رسالة المستخدم. |
| `MessageSanitizer@isValid` | Service | يتحقق من صلاحية الرسالة. |
| `MessageSanitizer@wrapForLLM` | Service | يغلف رسالة المستخدم قبل إرسالها للـ LLM. |
| `PromptInjectionDetector@analyze` | Service | يكتشف محاولات prompt injection. |
| `ResponseSanitizer@sanitize` | Service | ينظف رد LLM ويمنع التسريب. |
| `ResponseSanitizer@getFallbackResponse` | Protected Service | يرجع رد بديل عند فلترة الرد. |
| `ResponseSanitizer@repairEncodingArtifacts` | Protected Service | يصلح artifacts في النص. |
| `ResponseSanitizer@looksLikeEncodingArtifact` | Protected Service | يحدد هل النص يحتوي encoding artifacts. |
| `ResponseSanitizer@countEncodingNoise` | Protected Service | يحسب مؤشرات noise في النص. |
| `ResponseSanitizer@removeDisplayArtifacts` | Protected Service | يزيل artifacts ظاهرة من الرد. |
| `ChatAuditLogger@log` | Service | يسجل أحداث chatbot audit. |
| `AdminChatbotContextBuilder@buildSystemPrompt` | Service | يبني prompt للـ admin chatbot. |
| `AdminIntentRouter@route` | Service | يحدد intent وdata sections المطلوبة لسؤال الإدارة. |
| `AdminDataQueryService@getSalesOverview` | Service | يرجع ملخص المبيعات لفترة. |
| `AdminDataQueryService@getTopProducts` | Service | يرجع أكثر المنتجات مبيعا. |
| `AdminDataQueryService@getTopCustomers` | Service | يرجع أفضل العملاء حسب الفترة. |
| `AdminDataQueryService@getCustomerPerformance` | Service | يحلل أداء العملاء. |
| `AdminDataQueryService@getInventoryAlerts` | Service | يرجع تنبيهات المخزون. |
| `AdminDataQueryService@getOrdersByStatus` | Service | يجمع الطلبات حسب الحالة. |
| `AdminDataQueryService@lookupOrder` | Service | يبحث عن طلب بالرقم أو النص. |
| `AdminDataQueryService@getBoundaryOrderDetails` | Service | يرجع أول/آخر order حسب المطلوب. |
| `AdminDataQueryService@lookupCustomer` | Service | يبحث عن عميل. |
| `AdminDataQueryService@getOrdersForCustomer` | Service | يرجع طلبات عميل. |
| `AdminDataQueryService@getSalesTrends` | Service | يرجع trends شهرية للمبيعات. |
| `AdminDataQueryService@getAgentPerformance` | Service | يرجع أداء الوكلاء. |
| `AdminDataQueryService@searchAgents` | Service | يبحث عن وكلاء. |
| `AdminDataQueryService@searchProducts` | Service | يبحث عن منتجات للإدارة. |
| `AdminDataQueryService@getRecentOrders` | Service | يرجع آخر الطلبات. |
| `AdminDataQueryService@getPlatformSnapshot` | Service | يرجع snapshot عام للمنصة. |
| `AdminDataQueryService@getRelevantContext` | Service | يحدد context مناسب بناء على رسالة admin. |
| `RecombeeService@getClient` | Service | يرجع Recombee client أو null. |
| `RecombeeService@syncItem` | Service | يزامن بيانات product مع Recombee. |
| `RecombeeService@addDetailView` | Service | يرسل detail view interaction. |
| `RecombeeService@addBookmark` | Service | يرسل bookmark interaction. |
| `RecombeeService@removeBookmark` | Service | يحذف bookmark interaction. |
| `RecombeeService@addCartAddition` | Service | يرسل cart addition interaction. |
| `RecombeeService@addPurchase` | Service | يرسل purchase interaction. |
| `RecombeeService@recommendItemsToUser` | Service | يطلب توصيات للمستخدم. |
| `RecombeeService@recommendItemsToItem` | Service | يطلب توصيات مشابهة لمنتج. |

### Ratings

| الدالة | الطبقة | الوصف |
| --- | --- | --- |
| `RatingService@submitRating` | Service | ينشئ أو يحدث rating للمستخدم الحالي. |
| `RatingService@getProductRatings` | Service | يرجع قائمة ratings لمنتج. |
| `RatingService@getProductStats` | Service | يرجع average/total/breakdown. |
| `RatingService@getUserRating` | Service | يرجع rating المستخدم الحالي. |
| `RatingService@getPaginatedRatings` | Service | يرجع ratings paginated. |

## Repositories

### Auth and Users

| الدالة | الطبقة | الوصف |
| --- | --- | --- |
| `UserRepository@create` | Repository | ينشئ user جديد مع password hashed. |
| `UserRepository@loginUser` | Repository | يبحث عن user ويتحقق من password ويحدث `last_login_at`. |
| `UserRepository@logoutUser` | Repository | يحذف كل tokens الخاصة بالمستخدم. |
| `UserRepository@logoutUsingRefreshToken` | Repository | يتحقق من refresh token ويحذف tokens. |
| `UserRepository@refreshToken` | Repository | يتحقق من refresh token ويرجع user ثم يحذف refresh token القديم. |
| `UserRepository@verifyToken` | Repository | يتحقق من access token ويرجع user وtoken metadata. |

### Product, Shop, Search

| الدالة | الطبقة | الوصف |
| --- | --- | --- |
| `ProductRepository@getAllProducts` | Repository | يرجع products approved التي لها variants. |
| `ProductRepository@getTopProducts` | Repository | يرتب المنتجات حسب إجمالي الكمية المباعة. |
| `ProductRepository@getProductBySlug` | Repository | يرجع product details بالـ slug مع العلاقات. |
| `ProductRepository@requestCustomProduct` | Repository | يحفظ custom product request. |
| `HomePageRepository@index` | Repository | يجمع بيانات home page categories/top categories/products. |
| `IndustrialCategoryRepository@index` | Repository | يرجع categories مع counts مناسبة. |
| `CartRepository@getProductsByIds` | Repository | يرجع المنتجات والـ variants المطلوبة للسلة. |
| `ShopPageRepository@filterProducts` | Repository | يطبق category/price/search/stock/semantic filters. |
| `ShopPageRepository@getProductsByCategoryName` | Repository | يرجع products حسب category name. |
| `SearchRepository@search` | Repository | ينفذ search مباشر على المنتجات. |
| `SearchRepository@searchKeyword` | Repository | ينفذ keyword search مع pagination/categories. |

### Payments, Coupons, Ratings

| الدالة | الطبقة | الوصف |
| --- | --- | --- |
| `PaymentRepository@findCouponByName` | Repository | يبحث عن coupon بالاسم. |
| `PaymentRepository@hasUsedCoupon` | Repository | يتحقق هل المستخدم استخدم coupon سابقا. |
| `PaymentRepository@hasPendingCoupon` | Repository | يتحقق هل coupon معلق للمستخدم. |
| `PaymentRepository@releaseExpiredPendingCoupons` | Repository | يحرر pending coupons المنتهية ويخفض usage count. |
| `PaymentRepository@detachPendingCoupons` | Repository | يزيل pending coupons للمستخدم. |
| `PaymentRepository@attachCoupon` | Repository | يضيف coupon للمستخدم بحالة pending ويزيد usage count. |
| `PaymentRepository@checkIfCouponApplied` | Repository | يرجع coupon pending للمستخدم الحالي. |
| `PaymentRepository@getShippingCost` | Repository | يحسب الشحن من city rate وأبعاد/وزن variants. |
| `RatingRepository@findExistingRating` | Repository | يبحث عن rating سابق للمستخدم والمنتج. |
| `RatingRepository@createRating` | Repository | ينشئ rating جديد. |
| `RatingRepository@updateRating` | Repository | يحدث rating موجود. |
| `RatingRepository@findProduct` | Repository | يتحقق من وجود product للrating. |
| `RatingRepository@getProductRatings` | Repository | يرجع ratings للمنتج. |
| `RatingRepository@getAverageRating` | Repository | يحسب average rating. |
| `RatingRepository@getTotalRatings` | Repository | يحسب عدد ratings. |
| `RatingRepository@getRatingBreakdown` | Repository | يحسب توزيع النجوم. |
| `RatingRepository@getUserRating` | Repository | يرجع rating مستخدم محدد. |
| `RatingRepository@getPaginatedRatings` | Repository | يرجع ratings paginated. |

### Agents and Profile

| الدالة | الطبقة | الوصف |
| --- | --- | --- |
| `AgentDashboardRepository@dashboardInfo` | Repository | يحسب dashboard metrics للوكيل. |
| `AgentDashboardRepository@salesPerformance` | Repository | يرجع أداء المبيعات حسب السنة. |
| `AgentDashboardRepository@recentOrders` | Repository | يرجع recent orders للوكيل. |
| `AgentDashboardRepository@commissionAndBonus` | Repository | يحسب العمولات والمكافآت. |
| `AgentDashboardRepository@commissionsTimeline` | Repository | يرجع timeline للعمولات. |
| `AgentDashboardRepository@getTotalDeliveredCommissions` | Repository | يحسب إجمالي العمولات المكتملة. |
| `AgentDashboardRepository@getTotalPendingCommissions` | Repository | يحسب إجمالي العمولات المعلقة. |
| `AgentDashboardRepository@getTopSellingCategory` | Repository | يرجع أعلى category مبيعا. |
| `AgentDashboardRepository@getTotalOrdersCount` | Repository | يحسب عدد الطلبات. |
| `AgentDashboardRepository@getTotalSales` | Repository | يحسب إجمالي المبيعات. |
| `ProfileRepository@getCustomerSummary` | Repository | يحسب ملخص طلبات العميل. |
| `ProfileRepository@getRecentOrders` | Repository | يرجع أحدث طلبات العميل. |
| `ProfileRepository@getPaginatedOrders` | Repository | يرجع طلبات العميل paginated. |
| `ProfileRepository@getAgentSummary` | Repository | يحسب ملخص الوكيل. |
| `ProfileRepository@getAgentActivity` | Repository | يرجع نشاط الوكيل. |
| `ProfileRepository@findOrderForUser` | Repository | يجد order يخص user. |
| `ProfileRepository@findOrderForAgent` | Repository | يجد order يخص agent code. |

## Frontend API Client

كل الدوال التالية موجودة في `resources/frontend/src/ApiClient.ts` وتستخدم Axios instance موحد.

### Auth Client

| method | الوصف |
| --- | --- |
| `ApiClient.auth.login` | يرسل email/password إلى `/auth/login`. |
| `ApiClient.auth.register` | يرسل بيانات التسجيل إلى `/auth/register`. |
| `ApiClient.auth.logout` | ينهي الجلسة عبر `/auth/logout`. |
| `ApiClient.auth.getUser` | يحاول جلب user من `/auth/user`. |
| `ApiClient.auth.refreshToken` | يجدد access token عبر `/auth/refresh-token`. |
| `ApiClient.auth.verifyToken` | يتحقق من token عبر `auth/verify-token`. |
| `ApiClient.auth.consumePayload` | يستهلك payload مشفر عبر `auth/consume-payload`. |
| `ApiClient.auth.forgotPassword` | يرسل forgot password request. |
| `ApiClient.auth.resetPassword` | يرسل reset password data. |
| `consumeImpersonationPayload` | يرسل payload إلى `/auth/consume-impersonation-payload`. |

### Catalog and Shopping Client

| method | الوصف |
| --- | --- |
| `ApiClient.products.getHomeProducts` | يجلب بيانات home. |
| `ApiClient.products.getProduct` | يجلب منتج بالـ id/slug حسب caller. |
| `ApiClient.products.getProductBySlug` | يجلب منتج بالـ slug. |
| `ApiClient.products.getProductsByIds` | يجلب منتجات cart من IDs. |
| `ApiClient.products.getAllProducts` | يجلب products paginated. |
| `ApiClient.products.requestCustomProduct` | يرسل FormData لطلب منتج مخصص. |
| `ApiClient.cart.getCart` | يجلب cart endpoint إذا كان متاحا. |
| `ApiClient.cart.addToCart` | يضيف item إلى cart endpoint. |
| `ApiClient.cart.updateCartItem` | يحدث كمية cart item. |
| `ApiClient.cart.removeCartItem` | يحذف item من cart. |
| `ApiClient.cart.clearCart` | يفرغ cart. |
| `ApiClient.cart.getCartCategories` | يجلب cart categories. |
| `ApiClient.wishlist.fetchWishlist` | يجلب wishlist. |
| `ApiClient.wishlist.addToWishlist` | يضيف منتج إلى wishlist ويطلق event. |
| `ApiClient.wishlist.removeFromWishlist` | يزيل منتج من wishlist ويطلق event. |
| `ApiClient.shop.getShopPageData` | يجلب shop page data إذا كان endpoint متاحا. |
| `ApiClient.shop.getProductsByFilter` | يجلب products حسب filters. |
| `ApiClient.shop.getProductsByCategoryName` | يجلب products حسب category. |
| `ApiClient.industrialCategory.getIndustrialCategories` | يجلب categories. |

### Search, AI, Recommendations Client

| method | الوصف |
| --- | --- |
| `ApiClient.search.searchForProducts` | keyword search عبر `/search/{query}`. |
| `ApiClient.search.searchForProductsKeyword` | keyword search مخصص. |
| `ApiClient.search.semanticSearch` | semantic search. |
| `ApiClient.search.visualSearch` | visual search باستخدام file أو base64. |
| `ApiClient.chatbot.initSession` | يبدأ customer chatbot session. |
| `ApiClient.chatbot.sendMessage` | يرسل رسالة chatbot مع session/product slug. |
| `ApiClient.chatbot.getHistory` | يجلب conversation history. |
| `ApiClient.recommendations.getForUser` | يجلب user recommendations. |
| `ApiClient.recommendations.getForItem` | يجلب item recommendations. |
| `ApiClient.recommendations.getFrequentForItem` | يجلب frequently bought together. |
| `ApiClient.recommendations.trackCartAddition` | يسجل cart interaction. |

### Orders, Payments, Agents, Profile Client

| method | الوصف |
| --- | --- |
| `ApiClient.agent.getSalesPerformance` | يجلب أداء مبيعات الوكيل. |
| `ApiClient.agent.getAgentDashboardInfo` | يجلب dashboard info. |
| `ApiClient.agent.getRecentOrders` | يجلب recent orders. |
| `ApiClient.agent.getAgentDashboardInfoCommissionAndBonus` | يجلب commission/bonus summary. |
| `ApiClient.agent.getCommissionsTimeline` | يجلب commissions timeline. |
| `ApiClient.agent.getManualOrders` | يجلب manual orders. |
| `ApiClient.agent.submitManualOrder` | يرسل manual order FormData. |
| `ApiClient.agent.lookupManualOrderCustomers` | يبحث عن عملاء للطلب اليدوي. |
| `ApiClient.agent.lookupManualOrderProducts` | يبحث عن منتجات للطلب اليدوي. |
| `ApiClient.ratings.submitRating` | يرسل rating. |
| `ApiClient.ratings.getUserRating` | يجلب rating المستخدم. |
| `ApiClient.ratings.getPaginatedRatings` | يجلب ratings paginated. |
| `ApiClient.coupon.applyCoupon` | يطبق coupon. |
| `ApiClient.checkout.getShippingDestinations` | يجلب shipping destinations. |
| `ApiClient.checkout.getShippingCost` | يحسب shipping cost. |
| `ApiClient.checkout.checkout` | يبدأ checkout. |
| `ApiClient.checkout.checkIfCouponApplied` | يتحقق من pending coupon. |
| `ApiClient.sendMessage.sendMessage` | يرسل contact message. |
| `ApiClient.profile.getProfile` | يجلب profile. |
| `ApiClient.profile.updateProfile` | يحدث profile. |
| `ApiClient.profile.deleteAccount` | يحذف الحساب. |
| `ApiClient.profile.getOrders` | يجلب orders. |
| `ApiClient.profile.downloadInvoice` | ينزل invoice blob. |
| `ApiClient.profile.getAgentActivity` | يجلب agent activity. |
| `ApiClient.request` | generic request helper لأي endpoint مخصص. |
| `ApiClient.axios` | يعرض Axios instance للاستخدام المباشر عند الحاجة. |

## Jobs Supporting Functions

| الدالة | الطبقة | الوصف |
| --- | --- | --- |
| `SendRecombeeInteractionJob@handle` | Job | يوجه interaction action إلى method المناسبة في `RecombeeService`. |
| `UpdateAgentMonthlyAchievedAmount@handle` | Job | يحدث إنجاز الوكيل الشهري عند تغير حالة order. |
| `UpdateInventoryStatusJob@handle` | Job | يحدث حالة المخزون عند الحاجة. |
| `UpdateRecombeeItemSync@handle` | Job | placeholder لمزامنة Recombee item sync مستقبلا. |
