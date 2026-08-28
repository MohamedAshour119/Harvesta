import { Routes, Route, useLocation, Navigate } from 'react-router-dom'
import ChatWidget from './components/chatbot/ChatWidget'
import './App.css'
import HomePage from './pages/Home/HomePage'
import CartPage from './pages/Cart/CartPage'
import { useState, useEffect } from 'react'
import MaxWidthWrapper from './components/MaxWidthWrapper'
import { TokenRefresher } from './components/TokenRefresher'
import CustomProductRequest from './pages/CustomProductRequest/CustomProductRequest'
import FavoritesSidebar from './components/FavoritesSidebar'
import Header from './components/Header'
import LoginPrompt from './components/LoginPrompt'
import LanguageSwitchModal from './components/modals/LanguageSwitchModal'
import i18n, { setDocumentDirection } from './i18n'
import { Overview } from './pages/Dashboard/pages/Overview/Overview'
import { FieldSales } from './pages/Dashboard/pages/FieldSales/FieldSales'
import DashboardAuthRoute from './auth/DashboardAuthRoute'
import CommissionAndBonus from './pages/Dashboard/pages/CommissionAndBonus/CommissionAndBonus'
import { PromotionsAndOffers } from './pages/Dashboard/pages/PromotionsAndOffers/PromotionsAndOffers'
import ShowResults from './pages/ShowResults/ShowResults'
import Products from './pages/Products/Products'
import { About } from './pages/About/About'
import { Privacy } from './pages/Privacy/Privacy'
import { Terms } from './pages/Terms/Terms'
import { Return } from './pages/Return/Return'
import { useDispatch, useSelector } from 'react-redux'
import type { RootState } from './store/store'
import { setIsCartSummarySidebarOpen } from './store/slices/isCartSummarySidebarOpenSlice'
import { ShowProduct } from './pages/ShowProduct/ShowProduct'
import CheckoutPage from './pages/Checkout/CheckoutPage'
import RequireNotAuthRoute from './auth/RequireNotAuthRoute'
import RequireAuthRoute from './auth/RequireAuthRoute'
import ForgotPassword from './pages/Login/ForgotPassword/ForgotPassword'
import ResetPassword from './pages/Login/ResetPassword/ResetPassword'
import Profile from './pages/Profile/Profile';
import PreventAgentCheckoutRoute from './auth/PreventAgentCheckoutRoute';
import EmptyCartModal from './components/modals/EmptyCartModal';

function App() {
  const pathname = useLocation().pathname;

  // State for cart summary visibility
  const [isShowCartSummary, setIsShowCartSummary] = useState(false);
  const isCartSummarySidebarOpen = useSelector((state: RootState) => state.isCartSummarySidebarOpen);
  const isHeaderSidebarOpen = useSelector((state: RootState) => state.isHeaderSidebarOpen);
  const [isSmallScreen, setIsSmallScreen] = useState(window.innerWidth < 1130);

  const dispatch = useDispatch();

  useEffect(() => {
    const handleResize = () => {
      setIsSmallScreen(window.innerWidth < 1130);
    };

    window.addEventListener('resize', handleResize);
    return () => window.removeEventListener('resize', handleResize);
  }, []);

  useEffect(() => {
    setIsShowCartSummary(false);
  }, [pathname]);

  // Set the document direction based on the current language
  useEffect(() => {
    setDocumentDirection(i18n.language);
  }, [i18n.language]);

  // Capture agent referral code from URL (?ref=CODE) and persist in sessionStorage
  useEffect(() => {
    const params = new URLSearchParams(window.location.search);
    const refCode = params.get('ref');
    if (refCode) {
      sessionStorage.setItem('agent_ref_code', refCode);
      params.delete('ref');
      const newUrl = window.location.pathname + (params.toString() ? '?' + params.toString() : '');
      window.history.replaceState({}, '', newUrl);
    }
  }, [pathname]);

  const dashboardPathnames = ['/dashboard', '/dashboard/overview', '/dashboard/field-sales', '/dashboard/commission-bonus', '/dashboard/promotions-offers'];

  return (
    <div className={`min-h-screen bg-white dark:bg-[#0B0F17] text-gray-900 dark:text-slate-100 flex flex-col w-full transition-colors duration-300 ${isShowCartSummary ? 'flex-row-reverse' : ''}`}>
      {!dashboardPathnames.includes(pathname) && (
        <div className="w-full flex flex-col flex-1">
          <Header />
          <main className="flex-1 w-full">
            <Routes>
              <Route path="/" element={<HomePage />} />
              <Route path="/all-products" element={<Products />} />
              <Route path="/cart" element={<CartPage />} />
              <Route path="/about" element={<MaxWidthWrapper><About /></MaxWidthWrapper>} />
              <Route path="/privacy" element={<MaxWidthWrapper><Privacy /></MaxWidthWrapper>} />
              <Route path="/terms" element={<MaxWidthWrapper><Terms /></MaxWidthWrapper>} />
              <Route path="/returns" element={<MaxWidthWrapper><Return /></MaxWidthWrapper>} />
              <Route path="/products/:slug" element={<ShowProduct />} />
              <Route element={<RequireNotAuthRoute />}>
                <Route path="/login" element={<Navigate to="/" replace />} />
                <Route path="/signup" element={<Navigate to="/" replace />} />
                <Route path="/forgot-password" element={<MaxWidthWrapper><ForgotPassword /></MaxWidthWrapper>} />
                <Route path="/reset-password" element={<ResetPassword />} />
              </Route>
              <Route element={<PreventAgentCheckoutRoute />}>
                <Route path="/checkout" element={<MaxWidthWrapper><CheckoutPage /></MaxWidthWrapper>} />
              </Route>
              <Route path="/show-results" element={<ShowResults />} />
              <Route path="/custom-product-request" element={<MaxWidthWrapper><CustomProductRequest /></MaxWidthWrapper>} />
              <Route element={<RequireAuthRoute />}>
                <Route path="/profile" element={<MaxWidthWrapper><Profile /></MaxWidthWrapper>} />
              </Route>
              <Route path="*" element={<Navigate to="/" replace />} />
            </Routes>
          </main>
        </div>
      )}

      {/* Add overlay for mobile when cart sidebar is open */}
      {((isSmallScreen && isCartSummarySidebarOpen) || isHeaderSidebarOpen) && (
        <div
          className="fixed inset-0 bg-black/50 z-[95] transition-opacity duration-300"
          onClick={() => dispatch(setIsCartSummarySidebarOpen(false))}
        />
      )}

      <Routes>
        <Route element={<DashboardAuthRoute />}>
          <Route path="/dashboard" element={<Overview />} />
          <Route path="/dashboard/field-sales" element={<FieldSales />} />
          <Route path="/dashboard/commission-bonus" element={<CommissionAndBonus />} />
          <Route path="/dashboard/promotions-offers" element={<PromotionsAndOffers />} />
        </Route>
      </Routes>

      <FavoritesSidebar />
      <TokenRefresher />
      {!dashboardPathnames.includes(pathname) && <ChatWidget />}
      <LoginPrompt />
      <LanguageSwitchModal />
      <EmptyCartModal />
    </div>
  )
}

export default App
