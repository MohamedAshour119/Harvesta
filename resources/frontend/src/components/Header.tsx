import React, { useState, useRef, useEffect } from 'react';
import { Link, useLocation, useNavigate } from 'react-router-dom';
import MaxWidthWrapper from './MaxWidthWrapper';
import { PiShoppingCartSimpleLight } from 'react-icons/pi';
import { FaRegHeart } from 'react-icons/fa';
import { HiMenuAlt3, HiX } from 'react-icons/hi';
import { useSelector, useDispatch } from 'react-redux';
import type { RootState } from '../store/store';
import { FiUser, FiSettings, FiBox, FiLogOut } from 'react-icons/fi';
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
      navigate('/login');
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
            <a href="/" className='cursor-pointer'>
              <img src="/logo.png" alt="Logo" className="w-[90px] drop-shadow-sm dark:brightness-110" />
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

              <Link to="/cart" className="relative hover:text-[#B12C2E] dark:hover:text-[#E03E41] transition-colors cursor-pointer p-1">
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
              {!isAuth &&
                <Link to="/login" className="text-gray-800 dark:text-slate-200 font-semibold hover:text-[#B12C2E] dark:hover:text-[#E03E41] transition-colors text-sm">{t('header.login')}</Link>
              }
              {!isAuth &&
                <Link to="/signup" className="bg-[#B12C2E] hover:bg-[#972426] dark:bg-[#B12C2E] dark:hover:bg-[#C83437] text-white px-4 min-w-[95px] h-[38px] flex justify-center items-center rounded-full font-medium transition-all shadow-md shadow-red-900/10 text-sm">{t('header.signup')}</Link>
              }
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

          {/* Mobile Menu */}
          <div
            ref={menuRef}
            className={`fixed top-0 right-0 bottom-0 z-[100] w-[20rem] transform ease-in-out duration-300 ${isRTL ? 'min-[980px]:hidden' : 'min-[870px]:hidden'} bg-white dark:bg-[#151D2C] border-l border-gray-100 dark:border-slate-800 shadow-2xl text-gray-800 dark:text-slate-100 ${isHeaderSidebarOpen ? 'translate-x-0' : 'translate-x-[60rem]'
              }`}
          >
            <div className="flex flex-col h-full overflow-y-auto pb-12 px-6">
              <div className="flex items-center justify-between pt-5 pb-3 border-b border-gray-100 dark:border-slate-800/80 mb-4 sticky top-0 bg-white dark:bg-[#151D2C] z-10">
                <Link to="/" onClick={() => dispatch(setIsHeaderSidebarOpen(false))} className="flex items-center">
                  <h1 className="text-2xl font-black text-[#B12C2E] dark:text-[#E03E41]">
                    CAIRO 3A
                  </h1>
                </Link>
                <div className="flex items-center gap-2">
                  <Link
                    to="/cart"
                    onClick={() => dispatch(setIsHeaderSidebarOpen(false))}
                    className="p-2 text-gray-700 dark:text-slate-300 hover:text-[#B12C2E] hover:bg-red-50 dark:hover:bg-slate-800 rounded-full transition-all"
                  >
                    <div className="relative">
                      <PiShoppingCartSimpleLight className='size-6' />
                      {cartItemsCount > 0 && (
                        <span className="absolute -top-1.5 -right-1.5 bg-[#B12C2E] text-white rounded-full w-4 h-4 flex items-center justify-center text-[10px] font-bold">
                          {cartItemsCount > 99 ? '99+' : cartItemsCount}
                        </span>
                      )}
                    </div>
                  </Link>
                  <button
                    ref={buttonRef}
                    className="p-2 text-gray-500 dark:text-slate-400 hover:text-red-500 hover:bg-red-50 dark:hover:bg-slate-800 rounded-full transition-all"
                    onClick={() => dispatch(setIsHeaderSidebarOpen(false))}
                  >
                    <HiX className="h-6 w-6" aria-hidden="true" />
                  </button>
                </div>
              </div>

              {/* Mobile Theme Toggle Section */}
              <div className="p-3 bg-gray-50 dark:bg-[#1C2638] rounded-xl border border-gray-100 dark:border-slate-700/60 mb-4 flex items-center justify-between">
                <span className="text-sm font-semibold text-gray-700 dark:text-slate-300">{t('theme.title', 'Appearance')}</span>
                <ThemeToggle showLabel={true} />
              </div>

              <div className="my-4">
                <ul className="flex flex-col space-y-4">
                  <li>
                    <Link
                      to="/"
                      className={`block py-1 text-base font-medium transition-colors ${location.pathname === '/' ? 'font-bold text-[#B12C2E] dark:text-[#E03E41]' : 'text-gray-800 dark:text-slate-200 hover:text-[#B12C2E]'}`}
                      onClick={() => dispatch(setIsHeaderSidebarOpen(false))}
                    >
                      {t('navigation.home')}
                    </Link>
                  </li>
                  <li>
                    <Link
                      to="/all-products"
                      className={`block py-1 text-base font-medium transition-colors ${location.pathname === '/all-products' && !new URLSearchParams(location.search).get('filter') ? 'font-bold text-[#B12C2E] dark:text-[#E03E41]' : 'text-gray-800 dark:text-slate-200 hover:text-[#B12C2E]'}`}
                      onClick={() => dispatch(setIsHeaderSidebarOpen(false))}
                    >
                      {t('navigation.products')}
                    </Link>
                  </li>
                  <li>
                    <Link
                      to="/all-products?filter=all-discounts"
                      className={`block py-1 text-base font-medium transition-colors ${location.pathname === '/all-products' && new URLSearchParams(location.search).get('filter') === 'all-discounts' ? 'font-bold text-[#B12C2E] dark:text-[#E03E41]' : 'text-gray-800 dark:text-slate-200 hover:text-[#B12C2E]'}`}
                      onClick={() => dispatch(setIsHeaderSidebarOpen(false))}
                    >
                      {t('navigation.deals', 'Deals')}
                    </Link>
                  </li>
                  <li>
                    <Link
                      to="/about"
                      className={`block py-1 text-base font-medium transition-colors ${location.pathname === '/about' ? 'font-bold text-[#B12C2E] dark:text-[#E03E41]' : 'text-gray-800 dark:text-slate-200 hover:text-[#B12C2E]'}`}
                      onClick={() => dispatch(setIsHeaderSidebarOpen(false))}
                    >
                      {t('navigation.about', 'About')}
                    </Link>
                  </li>
                </ul>
              </div>

              {!isAuth && (
                <div className="mt-4 mb-2 grid grid-cols-2 gap-3 pb-6 border-b border-gray-100 dark:border-slate-800">
                  <Link
                    to="/login"
                    className="flex items-center justify-center gap-2 px-4 py-3 border border-gray-200 dark:border-slate-700 bg-white dark:bg-slate-800 rounded-xl text-sm font-bold text-gray-700 dark:text-slate-200 hover:bg-gray-50 dark:hover:bg-slate-700 transition-all"
                    onClick={() => dispatch(setIsHeaderSidebarOpen(false))}
                  >
                    <FiUser size={18} className="text-[#B12C2E]" />
                    {t('header.login')}
                  </Link>
                  <Link
                    to="/signup"
                    className="flex items-center justify-center gap-2 px-4 py-3 bg-[#B12C2E] text-white rounded-xl text-sm font-bold hover:bg-[#972426] transition-all shadow-sm"
                    onClick={() => dispatch(setIsHeaderSidebarOpen(false))}
                  >
                    {t('header.signup')}
                  </Link>
                </div>
              )}
              {isAuth && (
                <div className="bg-gray-50 dark:bg-[#1C2638] rounded-2xl p-4 border border-gray-100 dark:border-slate-700/60 mb-6">
                  <div className="flex items-center gap-3 mb-5 border-b border-gray-200 dark:border-slate-700 pb-4">
                    <div className="w-12 min-w-12 h-12 rounded-full bg-gradient-to-br from-[#B12C2E] to-red-600 flex items-center justify-center text-white font-bold text-lg shadow-sm">
                      {user?.full_name?.split(' ').map((n: string) => n[0]).join('').toUpperCase().slice(0, 2) || '?'}
                    </div>
                    <div className="flex flex-col overflow-hidden">
                      <span className="font-bold text-gray-900 dark:text-slate-100 leading-tight truncate">{user?.full_name}</span>
                      <span className="text-[10px] text-gray-500 dark:text-slate-400 font-semibold uppercase tracking-wider mt-0.5">
                        {user?.is_agent ? (isRTL ? 'مندوب مبيعات' : 'Sales Agent') : (isRTL ? 'عميل' : 'Customer')}
                      </span>
                    </div>
                  </div>

                  <div className="space-y-2.5">
                    <Link
                      to="/profile"
                      className="flex items-center gap-3 w-full px-4 py-3 bg-white dark:bg-[#151D2C] border border-gray-200/60 dark:border-slate-700/60 rounded-xl text-[13px] font-bold text-gray-700 dark:text-slate-200 hover:border-[#B12C2E]/30 hover:bg-red-50/30 dark:hover:bg-slate-800 transition-all duration-300 shadow-sm"
                      onClick={() => dispatch(setIsHeaderSidebarOpen(false))}
                    >
                      <FiUser className="text-[#B12C2E]" size={18} />
                      {t('userProfile.myProfile')}
                    </Link>

                    {user?.is_agent ? (
                      <Link
                        to="/dashboard"
                        className="flex items-center gap-3 w-full px-4 py-3 bg-white dark:bg-[#151D2C] border border-gray-200/60 dark:border-slate-700/60 rounded-xl text-[13px] font-bold text-gray-700 dark:text-slate-200 hover:border-[#B12C2E]/30 hover:bg-red-50/30 dark:hover:bg-slate-800 transition-all duration-300 shadow-sm"
                        onClick={() => dispatch(setIsHeaderSidebarOpen(false))}
                      >
                        <FiSettings className="text-[#B12C2E]" size={18} />
                        {t('auth:authDropdown.Dashboard')}
                      </Link>
                    ) : (
                      <Link
                        to="/profile?section=orders"
                        className="flex items-center gap-3 w-full px-4 py-3 bg-white dark:bg-[#151D2C] border border-gray-200/60 dark:border-slate-700/60 rounded-xl text-[13px] font-bold text-gray-700 dark:text-slate-200 hover:border-[#B12C2E]/30 hover:bg-red-50/30 dark:hover:bg-slate-800 transition-all duration-300 shadow-sm"
                        onClick={() => dispatch(setIsHeaderSidebarOpen(false))}
                      >
                        <FiBox className="text-[#B12C2E]" size={18} />
                        {t('userProfile.myOrders')}
                      </Link>
                    )}

                    <button
                      onClick={() => {
                        handleLogout();
                        dispatch(setIsHeaderSidebarOpen(false));
                      }}
                      className="flex items-center gap-3 w-full px-4 py-3 bg-red-50/80 dark:bg-red-950/40 border border-red-100 dark:border-red-900/40 rounded-xl text-[13px] font-bold text-red-600 dark:text-red-400 hover:bg-red-600 hover:text-white dark:hover:bg-red-600 transition-all duration-300 mt-2 shadow-sm cursor-pointer"
                    >
                      <FiLogOut size={18} />
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
