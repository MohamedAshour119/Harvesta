import React, { useState, useRef, useEffect } from 'react';
import { Link, useLocation, useNavigate } from 'react-router-dom';
import MaxWidthWrapper from './MaxWidthWrapper';
import { PiShoppingCartSimpleLight } from 'react-icons/pi';
import { FaRegHeart } from 'react-icons/fa';
import { HiMenuAlt3, HiX } from 'react-icons/hi';
import { useSelector, useDispatch } from 'react-redux';
import type { RootState } from '../store/store';
import { FiUser, FiUserPlus, FiSettings, FiBox, FiLogOut, FiHome, FiShoppingBag, FiTag, FiInfo } from 'react-icons/fi';
import { MdOutlineKeyboardArrowDown, MdOutlineKeyboardArrowUp } from 'react-icons/md';
import { setIsAuth, setUser } from '../store/slices/userSlice';
import ApiClient, { clearAuthTokens } from '../ApiClient';
import { clearFavorites, toggleFavoritesSidebar } from '../store/slices/favoritesSlice';
import { useTranslation } from 'react-i18next';
import i18n from '../i18n';
import HeaderDropdown from './HeaderDropdown';
import ThemeToggle from './ThemeToggle';
import { setIsCartSummarySidebarOpen } from '../store/slices/isCartSummarySidebarOpenSlice';
import { setIsHeaderSidebarOpen } from '../store/slices/isHeaderSidebarOpenSlice';
import { openEmptyCartModal } from '../store/slices/emptyCartModalSlice';
import { openLoginModal, openRegisterModal, openLogoutSuccessModal } from '../store/slices/isAuthPopupOpenSlice';
import { clearChatbotForLogout } from '../utils/chatbotSessionStorage';

const Header: React.FC = () => {
  const location = useLocation();
  const isHeaderSidebarOpen = useSelector((state: RootState) => state.isHeaderSidebarOpen);
  const menuRef = useRef<HTMLDivElement>(null);
  const buttonRef = useRef<HTMLButtonElement>(null);
  const [isDropdownOpen, setIsDropdownOpen] = useState(false);
  const dropdownRef = useRef<HTMLDivElement>(null);
  const userProfileRef = useRef<HTMLDivElement>(null);
  const navigate = useNavigate();
  const dispatch = useDispatch();
  const { t } = useTranslation(['common', 'auth']);
  const isRTL = i18n.language === 'ar';

  const isAuth = useSelector((state: RootState) => state.auth.isAuth);
  const user = useSelector((state: RootState) => state.auth.user);

  // Get cart items from Redux store
  const { items } = useSelector((state: RootState) => state.cart);
  const cartItemsCount = items.length;

  const favoriteItems = useSelector((state: RootState) => state.favorites.items);
  const favoritesCount = favoriteItems?.length;

  // Close mobile menu when clicking outside
  useEffect(() => {
    const handleClickOutside = (event: MouseEvent) => {
      if (
        menuRef.current &&
        buttonRef.current &&
        !menuRef.current.contains(event.target as Node) &&
        !buttonRef.current.contains(event.target as Node)
      ) {
        dispatch(setIsHeaderSidebarOpen(false));
      }
    };

    document.addEventListener('mousedown', handleClickOutside);
    return () => {
      document.removeEventListener('mousedown', handleClickOutside);
    };
  }, [isHeaderSidebarOpen, dispatch]);

  // Close dropdown when clicking outside
  useEffect(() => {
    const handleClickOutside = (event: MouseEvent) => {
      if (
        dropdownRef.current &&
        userProfileRef.current &&
        !dropdownRef.current.contains(event.target as Node) &&
        !userProfileRef.current.contains(event.target as Node)
      ) {
        setIsDropdownOpen(false);
      }
    };

    document.addEventListener('mousedown', handleClickOutside);
    return () => {
      document.removeEventListener('mousedown', handleClickOutside);
    };
  }, [isDropdownOpen]);

  // Toggle mobile menu
  const toggleMobileMenu = () => {
    dispatch(setIsHeaderSidebarOpen(!isHeaderSidebarOpen));
  };

  // Toggle user dropdown
  const toggleDropdown = () => {
    setIsDropdownOpen(!isDropdownOpen);
  };

  // Handle cart button click with empty cart prevention
  const handleCartClick = (e: React.MouseEvent) => {
    if (cartItemsCount === 0) {
      e.preventDefault();
      dispatch(openEmptyCartModal());
    }
  };

  const handleMobileCartClick = (e: React.MouseEvent) => {
    if (cartItemsCount === 0) {
      e.preventDefault();
      dispatch(setIsHeaderSidebarOpen(false));
      dispatch(openEmptyCartModal());
    } else {
      dispatch(setIsHeaderSidebarOpen(false));
    }
  };

  // Handle logout
  const handleLogout = async () => {
    try {
      await ApiClient.auth.logout();
    } catch (error) {
      console.error('Logout request failed, clearing local session anyway.', error);
    } finally {
      clearAuthTokens();
      clearChatbotForLogout(dispatch);
      dispatch(clearFavorites());
      dispatch(setIsAuth(false));
      dispatch(setUser(null));
      dispatch(openLogoutSuccessModal());
      navigate('/');
      setIsDropdownOpen(false);
    }
  };

  const pathname = useLocation().pathname;


  return (
    <MaxWidthWrapper className={pathname === '/products' || pathname === '/all-products' ? '!px-0' : '!px-4'}>
      <header className="w-full py-6 md:py-8 bg-white dark:bg-[#0B0F17] transition-colors duration-300 relative">
        <div className="max-w-container mx-auto">
          <div className="flex flex-row items-center justify-between gap-x-2">
            {/* Logo */}
            <a href="/" className='cursor-pointer flex items-center py-1'>
              <img src={isRTL ? '/logo-ar.png' : '/logo.png'} alt={t('common:title')} className="h-12 md:h-14 w-auto max-w-[220px] md:max-w-[250px] object-contain drop-shadow-md dark:brightness-110 transition-transform duration-200 hover:scale-[1.02]" />
            </a>

            {/* Desktop Navigation */}
            <nav className={`hidden flex-1 ${isRTL ? 'min-[980px]:block' : 'min-[870px]:block'}`}>
              <ul className="flex justify-center space-x-5 text-gray-700 dark:text-slate-200">
                <li>
                  <Link
                    to="/"
                    className={`transition-colors ${location.pathname === '/' ? 'font-bold text-[#B12C2E] dark:text-[#E03E41]' : 'hover:text-[#B12C2E] dark:hover:text-[#E03E41]'}`}
                  >
                    {t('navigation.home')}
                  </Link>
                </li>
                <li>
                  <Link
                    to="/all-products"
                    className={`transition-colors ${location.pathname === '/all-products' && !new URLSearchParams(location.search).get('filter') ? 'font-bold text-[#B12C2E] dark:text-[#E03E41]' : 'hover:text-[#B12C2E] dark:hover:text-[#E03E41]'}`}
                  >
                    {t('navigation.products')}
                  </Link>
                </li>
                <li>
                  <Link
                    to="/all-products?filter=all-discounts"
                    className={`transition-colors ${(location.pathname === '/all-products' && new URLSearchParams(location.search).get('filter') === 'all-discounts') ? 'font-bold text-[#B12C2E] dark:text-[#E03E41]' : 'hover:text-[#B12C2E] dark:hover:text-[#E03E41]'}`}
                  >
                    {t('navigation.deals', 'Deals')}
                  </Link>
                </li>
                <li>
                  <Link
                    to="/about"
                    className={`transition-colors ${location.pathname === '/about' ? 'font-bold text-[#B12C2E] dark:text-[#E03E41]' : 'hover:text-[#B12C2E] dark:hover:text-[#E03E41]'}`}
                  >
                    {t('navigation.about', 'About')}
                  </Link>
                </li>
              </ul>
            </nav>

            {/* Desktop User Actions */}
            <div className={`hidden ${isRTL ? 'min-[980px]:flex' : 'min-[870px]:flex'} items-center space-x-4 min-[1200px]:space-x-5 text-gray-700 dark:text-slate-200`}>

              {/* Theme Toggle Button */}
              <ThemeToggle />

              <Link
                to="/cart"
                onClick={handleCartClick}
                className="relative hover:text-[#B12C2E] dark:hover:text-[#E03E41] transition-colors cursor-pointer p-1"
                aria-label={t('header.cart', 'Cart')}
              >
                <PiShoppingCartSimpleLight className='size-8' />
                {cartItemsCount > 0 && (
                  <span className="absolute -top-1 -right-2 bg-pink text-white rounded-full w-5 h-5 flex items-center justify-center text-xs font-bold shadow-md">
                    {cartItemsCount > 99 ? '99+' : cartItemsCount}
                  </span>
                )}
              </Link>

              {/* Favorites button */}
              <button
                onClick={() => dispatch(toggleFavoritesSidebar())}
                className="relative p-1.5 hover:text-[#B12C2E] dark:hover:text-[#E03E41] transition-colors cursor-pointer"
              >
                <FaRegHeart className='size-6' />
                {favoritesCount > 0 && (
                  <span className="absolute -top-1 right-0 bg-pink text-white text-xs w-5 h-5 flex items-center justify-center rounded-full font-bold shadow-md">
                    {favoritesCount}
                  </span>
                )}
              </button>

              {isAuth && (
                <div className="relative" ref={userProfileRef}>
                  <div
                    className="flex items-center gap-x-2 cursor-pointer group"
                    onClick={toggleDropdown}
                  >
                    <FiUser className='size-7 group-hover:text-[#B12C2E] dark:group-hover:text-[#E03E41] transition' />
                    <div className="flex items-center group-hover:text-[#B12C2E] dark:group-hover:text-[#E03E41] transition text-sm">
                      {t('auth:authDropdown.Hi')}, <span className="font-semibold ms-1 text-gray-900 dark:text-slate-100 group-hover:text-[#B12C2E] dark:group-hover:text-[#E03E41] transition">{user?.full_name}</span>
                    </div>
                    {isDropdownOpen ?
                      <MdOutlineKeyboardArrowUp className="size-6 group-hover:text-[#B12C2E] dark:group-hover:text-[#E03E41] transition" /> :
                      <MdOutlineKeyboardArrowDown className="size-6 group-hover:text-[#B12C2E] dark:group-hover:text-[#E03E41] transition" />
                    }
                  </div>

                  {/* User Dropdown Menu with Animation */}
                  <HeaderDropdown
                    isDropdownOpen={isDropdownOpen}
                    setIsDropdownOpen={setIsDropdownOpen}
                    user={user}
                    handleLogout={handleLogout}
                    ref={dropdownRef}
                  />
                </div>
              )}
              {!isAuth && (
                <button
                  type="button"
                  onClick={() => dispatch(openLoginModal())}
                  className="text-gray-800 dark:text-slate-200 font-semibold hover:text-[#B12C2E] dark:hover:text-[#E03E41] transition-colors text-sm cursor-pointer"
                >
                  {t('header.login')}
                </button>
              )}
              {!isAuth && (
                <button
                  type="button"
                  onClick={() => dispatch(openRegisterModal())}
                  className="bg-[#B12C2E] hover:bg-[#972426] dark:bg-[#B12C2E] dark:hover:bg-[#C83437] text-white px-4 min-w-[95px] h-[38px] flex justify-center items-center rounded-full font-medium transition-all shadow-md shadow-red-900/10 text-sm cursor-pointer"
                >
                  {t('header.signup')}
                </button>
              )}
            </div>

            {/* Mobile Actions (Menu Button) */}
            <div className={`inline-flex items-center gap-2 ${isRTL ? 'min-[980px]:hidden' : 'min-[870px]:hidden'}`}>
              <button
                ref={buttonRef}
                className="inline-flex items-center justify-center p-2 text-gray-800 dark:text-slate-200 hover:bg-gray-100 dark:hover:bg-slate-800 rounded-lg transition-colors"
                onClick={toggleMobileMenu}
                aria-label="Toggle mobile menu"
              >
                {isHeaderSidebarOpen ? (
                  <HiX className="h-6 w-6" aria-hidden="true" />
                ) : (
                  <HiMenuAlt3 className="h-6 w-6" aria-hidden="true" />
                )}
              </button>
            </div>
          </div>

          {/* Mobile Menu Backdrop */}
          {isHeaderSidebarOpen && (
            <div
              className={`fixed inset-0 bg-black/60 backdrop-blur-sm z-[95] transition-opacity duration-300 ${isRTL ? 'min-[980px]:hidden' : 'min-[870px]:hidden'}`}
              onClick={() => dispatch(setIsHeaderSidebarOpen(false))}
            />
          )}

          {/* Mobile Menu */}
          <div
            ref={menuRef}
            className={`fixed top-0 ${isRTL ? 'right-0' : 'left-0'} bottom-0 z-[100] w-[21rem] sm:w-[23rem] transform ease-in-out duration-300 ${isRTL ? 'min-[980px]:hidden' : 'min-[870px]:hidden'} bg-white dark:bg-[#131B2A] border-gray-100 dark:border-slate-800 shadow-2xl text-gray-800 dark:text-slate-100 flex flex-col ${
              isHeaderSidebarOpen ? 'translate-x-0' : (isRTL ? 'translate-x-full' : '-translate-x-full')
            } ${isRTL ? 'border-s' : 'border-e'}`}
          >
            <div className="flex flex-col h-full overflow-y-auto pb-12 px-5">
              {/* Sidebar Header */}
              <div className="flex items-center justify-between pt-5 pb-3.5 border-b border-gray-100 dark:border-slate-800/80 mb-4 sticky top-0 bg-white/95 dark:bg-[#131B2A]/95 backdrop-blur-sm z-10">
                <Link to="/" onClick={() => dispatch(setIsHeaderSidebarOpen(false))} className="flex items-center gap-2">
                  <img src="/favicon.svg" alt="Harvesta" className="w-7 h-7 object-contain rounded-lg" />
                  <h1 className="text-xl font-black text-[#B12C2E] dark:text-[#E03E41] tracking-tight">
                    {t('common:title')}
                  </h1>
                </Link>
                <div className="flex items-center gap-1.5">
                  {/* Favorites Button in Mobile Header */}
                  <button
                    type="button"
                    onClick={() => {
                      dispatch(setIsHeaderSidebarOpen(false));
                      dispatch(toggleFavoritesSidebar());
                    }}
                    className="p-2 text-gray-600 dark:text-slate-300 hover:text-[#B12C2E] dark:hover:text-[#E03E41] hover:bg-red-50 dark:hover:bg-slate-800 rounded-full transition-all cursor-pointer"
                    aria-label={t('header.favorites')}
                    title={t('header.favorites')}
                  >
                    <div className="relative flex items-center justify-center">
                      <FaRegHeart className="size-5" />
                      {favoritesCount > 0 && (
                        <span className="absolute -top-1.5 -right-1.5 bg-[#B12C2E] text-white rounded-full w-4 h-4 flex items-center justify-center text-[10px] font-bold shadow-sm">
                          {favoritesCount > 99 ? '99+' : favoritesCount}
                        </span>
                      )}
                    </div>
                  </button>

                  {/* Cart Button in Mobile Header */}
                  <Link
                    to="/cart"
                    onClick={handleMobileCartClick}
                    className="p-2 text-gray-600 dark:text-slate-300 hover:text-[#B12C2E] dark:hover:text-[#E03E41] hover:bg-red-50 dark:hover:bg-slate-800 rounded-full transition-all cursor-pointer"
                    aria-label={t('header.cart')}
                    title={t('header.cart')}
                  >
                    <div className="relative flex items-center justify-center">
                      <PiShoppingCartSimpleLight className="size-6" />
                      {cartItemsCount > 0 && (
                        <span className="absolute -top-1.5 -right-1.5 bg-[#B12C2E] text-white rounded-full w-4 h-4 flex items-center justify-center text-[10px] font-bold shadow-sm">
                          {cartItemsCount > 99 ? '99+' : cartItemsCount}
                        </span>
                      )}
                    </div>
                  </Link>

                  {/* Close Button */}
                  <button
                    ref={buttonRef}
                    type="button"
                    className="p-2 text-gray-400 hover:text-red-500 dark:text-slate-400 dark:hover:text-red-400 hover:bg-red-50 dark:hover:bg-slate-800 rounded-full transition-all cursor-pointer"
                    onClick={() => dispatch(setIsHeaderSidebarOpen(false))}
                    aria-label={t('buttons.close', 'Close')}
                  >
                    <HiX className="h-6 w-6" aria-hidden="true" />
                  </button>
                </div>
              </div>

              {/* Mobile Theme Toggle Section */}
              <div className="p-3 bg-gray-50/80 dark:bg-[#1A2333] rounded-2xl border border-gray-100 dark:border-slate-800/80 mb-4 flex items-center justify-between shadow-sm">
                <div className="flex items-center gap-2">
                  <span className="w-2 h-2 rounded-full bg-[#B12C2E] dark:bg-[#E03E41]" />
                  <span className="text-xs font-bold text-gray-700 dark:text-slate-200">
                    {t('theme.title', 'Appearance')}
                  </span>
                </div>
                <ThemeToggle showLabel={true} />
              </div>

              {/* Navigation Links */}
              <div className="my-2">
                <ul className="flex flex-col space-y-1">
                  <li>
                    <Link
                      to="/"
                      className={`flex items-center gap-3 px-3.5 py-2.5 rounded-xl font-medium text-sm transition-all duration-200 ${
                        location.pathname === '/'
                          ? 'bg-red-50 dark:bg-red-950/40 text-[#B12C2E] dark:text-[#E03E41] font-bold shadow-sm'
                          : 'text-gray-700 dark:text-slate-300 hover:bg-gray-100/70 dark:hover:bg-slate-800 hover:text-[#B12C2E] dark:hover:text-[#E03E41]'
                      }`}
                      onClick={() => dispatch(setIsHeaderSidebarOpen(false))}
                    >
                      <FiHome className="size-4.5 shrink-0" />
                      <span>{t('navigation.home')}</span>
                    </Link>
                  </li>

                  <li>
                    <Link
                      to="/all-products"
                      className={`flex items-center gap-3 px-3.5 py-2.5 rounded-xl font-medium text-sm transition-all duration-200 ${
                        location.pathname === '/all-products' && !new URLSearchParams(location.search).get('filter')
                          ? 'bg-red-50 dark:bg-red-950/40 text-[#B12C2E] dark:text-[#E03E41] font-bold shadow-sm'
                          : 'text-gray-700 dark:text-slate-300 hover:bg-gray-100/70 dark:hover:bg-slate-800 hover:text-[#B12C2E] dark:hover:text-[#E03E41]'
                      }`}
                      onClick={() => dispatch(setIsHeaderSidebarOpen(false))}
                    >
                      <FiShoppingBag className="size-4.5 shrink-0" />
                      <span>{t('navigation.products')}</span>
                    </Link>
                  </li>

                  <li>
                    <Link
                      to="/all-products?filter=all-discounts"
                      className={`flex items-center gap-3 px-3.5 py-2.5 rounded-xl font-medium text-sm transition-all duration-200 ${
                        location.pathname === '/all-products' && new URLSearchParams(location.search).get('filter') === 'all-discounts'
                          ? 'bg-red-50 dark:bg-red-950/40 text-[#B12C2E] dark:text-[#E03E41] font-bold shadow-sm'
                          : 'text-gray-700 dark:text-slate-300 hover:bg-gray-100/70 dark:hover:bg-slate-800 hover:text-[#B12C2E] dark:hover:text-[#E03E41]'
                      }`}
                      onClick={() => dispatch(setIsHeaderSidebarOpen(false))}
                    >
                      <FiTag className="size-4.5 shrink-0" />
                      <span>{t('navigation.deals', 'Deals')}</span>
                    </Link>
                  </li>

                  <li>
                    <Link
                      to="/about"
                      className={`flex items-center gap-3 px-3.5 py-2.5 rounded-xl font-medium text-sm transition-all duration-200 ${
                        location.pathname === '/about'
                          ? 'bg-red-50 dark:bg-red-950/40 text-[#B12C2E] dark:text-[#E03E41] font-bold shadow-sm'
                          : 'text-gray-700 dark:text-slate-300 hover:bg-gray-100/70 dark:hover:bg-slate-800 hover:text-[#B12C2E] dark:hover:text-[#E03E41]'
                      }`}
                      onClick={() => dispatch(setIsHeaderSidebarOpen(false))}
                    >
                      <FiInfo className="size-4.5 shrink-0" />
                      <span>{t('navigation.about', 'About')}</span>
                    </Link>
                  </li>
                </ul>
              </div>

              {!isAuth && (
                <div className="mt-4 pt-4 border-t border-gray-100 dark:border-slate-800 space-y-2.5">
                  <button
                    type="button"
                    className="flex items-center justify-center gap-2.5 w-full py-3 px-4 bg-[#B12C2E] hover:bg-[#972426] text-white rounded-2xl text-sm font-bold transition-all shadow-md hover:shadow-lg shadow-red-500/10 cursor-pointer"
                    onClick={() => {
                      dispatch(setIsHeaderSidebarOpen(false));
                      dispatch(openLoginModal());
                    }}
                  >
                    <FiUser size={18} />
                    <span>{t('header.login')}</span>
                  </button>
                  <button
                    type="button"
                    className="flex items-center justify-center gap-2.5 w-full py-2.5 px-4 border border-gray-200 dark:border-slate-700 bg-gray-50/80 hover:bg-gray-100 dark:bg-slate-800/60 dark:hover:bg-slate-800 rounded-2xl text-sm font-bold text-gray-700 dark:text-slate-200 transition-all shadow-sm cursor-pointer"
                    onClick={() => {
                      dispatch(setIsHeaderSidebarOpen(false));
                      dispatch(openRegisterModal());
                    }}
                  >
                    <FiUserPlus size={18} className="text-[#B12C2E] dark:text-[#E03E41]" />
                    <span>{t('header.signup')}</span>
                  </button>
                </div>
              )}

              {isAuth && (
                <div className="bg-gray-50 dark:bg-[#1C2638] rounded-2xl p-4 border border-gray-100 dark:border-slate-700/60 mt-3 mb-4">
                  <div className="flex items-center gap-3 mb-4 border-b border-gray-200 dark:border-slate-700 pb-3">
                    <div className="w-11 min-w-11 h-11 rounded-full bg-gradient-to-br from-[#B12C2E] to-red-600 flex items-center justify-center text-white font-bold text-base shadow-sm">
                      {user?.full_name?.split(' ').map((n: string) => n[0]).join('').toUpperCase().slice(0, 2) || '?'}
                    </div>
                    <div className="flex flex-col overflow-hidden">
                      <span className="font-bold text-gray-900 dark:text-slate-100 text-sm leading-tight truncate">{user?.full_name}</span>
                      <span className="text-[10px] text-gray-500 dark:text-slate-400 font-semibold uppercase tracking-wider mt-0.5">
                        {user?.is_agent ? (isRTL ? 'مندوب مبيعات' : 'Sales Agent') : (isRTL ? 'عميل' : 'Customer')}
                      </span>
                    </div>
                  </div>

                  <div className="space-y-2">
                    <Link
                      to="/profile"
                      className="flex items-center gap-3 w-full px-3.5 py-2.5 bg-white dark:bg-[#151D2C] border border-gray-200/60 dark:border-slate-700/60 rounded-xl text-xs font-bold text-gray-700 dark:text-slate-200 hover:border-[#B12C2E]/30 hover:bg-red-50/30 dark:hover:bg-slate-800 transition-all duration-300 shadow-sm"
                      onClick={() => dispatch(setIsHeaderSidebarOpen(false))}
                    >
                      <FiUser className="text-[#B12C2E] dark:text-[#E03E41]" size={16} />
                      {t('userProfile.myProfile')}
                    </Link>

                    {user?.is_agent ? (
                      <Link
                        to="/dashboard"
                        className="flex items-center gap-3 w-full px-3.5 py-2.5 bg-white dark:bg-[#151D2C] border border-gray-200/60 dark:border-slate-700/60 rounded-xl text-xs font-bold text-gray-700 dark:text-slate-200 hover:border-[#B12C2E]/30 hover:bg-red-50/30 dark:hover:bg-slate-800 transition-all duration-300 shadow-sm"
                        onClick={() => dispatch(setIsHeaderSidebarOpen(false))}
                      >
                        <FiSettings className="text-[#B12C2E] dark:text-[#E03E41]" size={16} />
                        {t('auth:authDropdown.Dashboard')}
                      </Link>
                    ) : (
                      <Link
                        to="/profile?section=orders"
                        className="flex items-center gap-3 w-full px-3.5 py-2.5 bg-white dark:bg-[#151D2C] border border-gray-200/60 dark:border-slate-700/60 rounded-xl text-xs font-bold text-gray-700 dark:text-slate-200 hover:border-[#B12C2E]/30 hover:bg-red-50/30 dark:hover:bg-slate-800 transition-all duration-300 shadow-sm"
                        onClick={() => dispatch(setIsHeaderSidebarOpen(false))}
                      >
                        <FiBox className="text-[#B12C2E] dark:text-[#E03E41]" size={16} />
                        {t('userProfile.myOrders')}
                      </Link>
                    )}

                    <button
                      type="button"
                      onClick={() => {
                        handleLogout();
                        dispatch(setIsHeaderSidebarOpen(false));
                      }}
                      className="flex items-center gap-3 w-full px-3.5 py-2.5 bg-red-50/80 dark:bg-red-950/40 border border-red-100 dark:border-red-900/40 rounded-xl text-xs font-bold text-red-600 dark:text-red-400 hover:bg-red-600 hover:text-white dark:hover:bg-red-600 transition-all duration-300 mt-1 shadow-sm cursor-pointer"
                    >
                      <FiLogOut size={16} />
                      {t('userProfile.logout')}
                    </button>
                  </div>
                </div>
              )}
            </div>
          </div>
        </div>
      </header>
    </MaxWidthWrapper>
  );
};

export default Header;
