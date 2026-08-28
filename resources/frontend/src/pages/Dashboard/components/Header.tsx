import { HiMenuAlt2 } from "react-icons/hi";
import { AiOutlineGlobal } from "react-icons/ai";
import { useTranslation } from "react-i18next";
import HeaderDropdown from "../../../components/HeaderDropdown";
import ThemeToggle from "../../../components/ThemeToggle";
import { setIsAuth, setUser } from "../../../store/slices/userSlice";
import { clearFavorites } from "../../../store/slices/favoritesSlice";
import { useSelector, useDispatch } from "react-redux";
import ApiClient, { clearAuthTokens } from "../../../ApiClient";
import { useState, useRef, useEffect } from "react";
import type { RootState } from "../../../store/store";
import { useNavigate } from "react-router-dom";
import i18n, { setDocumentDirection } from "../../../i18n";
import { FiSearch } from "react-icons/fi";
import { clearChatbotForLogout } from "../../../utils/chatbotSessionStorage";
import { triggerLanguageSwitch } from "../../../utils/languageSwitcher";

interface HeaderProps {
    setSidebarOpen: (open: boolean) => void;
    title: string;
}

export default function Header({ setSidebarOpen, title }: HeaderProps) {
    const { t } = useTranslation(["dashboard"]);
    const isRLT = i18n.dir() === "rtl";
    const [isDropdownOpen, setIsDropdownOpen] = useState(false);
    const dropdownRef = useRef<HTMLDivElement>(null);
    const wrapperRef = useRef<HTMLDivElement>(null);
    const langWrapperRef = useRef<HTMLDivElement>(null);
    const [isLangDropdownOpen, setIsLangDropdownOpen] = useState(false);

    useEffect(() => {
        function handleClickOutside(event: MouseEvent) {
            if (
                wrapperRef.current &&
                !wrapperRef.current.contains(event.target as Node)
            ) {
                setIsDropdownOpen(false);
            }
        }
        document.addEventListener("mousedown", handleClickOutside);
        return () =>
            document.removeEventListener("mousedown", handleClickOutside);
    }, []);

    useEffect(() => {
        function handleClickOutsideLang(event: MouseEvent) {
            if (
                langWrapperRef.current &&
                !langWrapperRef.current.contains(event.target as Node)
            ) {
                setIsLangDropdownOpen(false);
            }
        }
        document.addEventListener("mousedown", handleClickOutsideLang);
        return () =>
            document.removeEventListener("mousedown", handleClickOutsideLang);
    }, []);

    const user = useSelector((state: RootState) => state.auth.user);
    const dispatch = useDispatch();
    const navigate = useNavigate();

    const handleLogout = async () => {
        try {
            await ApiClient.auth.logout();
        } catch (error) {
            console.error("Logout request failed, clearing local session anyway.", error);
        } finally {
            clearAuthTokens();
            clearChatbotForLogout(dispatch);
            dispatch(clearFavorites());
            dispatch(setIsAuth(false));
            dispatch(setUser(null));
            navigate("/");
            setIsDropdownOpen(false);
        }
    };

    const changeLanguage = (language: string) => {
        triggerLanguageSwitch(language);
    };

    return (
        <header className="bg-white dark:bg-[#151D2C] border-b border-gray-200 dark:border-slate-800 shadow-sm px-3 sm:px-6 py-3 sm:py-4 flex items-center justify-between transition-colors">
            <div className="flex items-center">
                <button
                    onClick={() => setSidebarOpen(true)}
                    className="p-2 mr-2 text-gray-700 dark:text-slate-300 hover:text-gray-900 dark:hover:text-white"
                >
                    <HiMenuAlt2 size={26} />
                </button>
                <span className="hidden mobile:block text-[#B12C2E] dark:text-[#E03E41] font-bold text-sm sm:text-base">
                    {title}
                </span>
            </div>
            <div className="flex items-center space-x-2 sm:space-x-4">
                {/* Theme Toggle */}
                <ThemeToggle />

                {/* Search on mobile */}
                <button className="sm:hidden p-2 text-gray-600 dark:text-slate-400">
                    <FiSearch size={24} />
                </button>

                {/* Language Dropdown */}
                <div className="relative" ref={langWrapperRef}>
                    <button
                        onClick={() => setIsLangDropdownOpen(!isLangDropdownOpen)}
                        className="p-1 sm:p-0 sm:mt-1 cursor-pointer text-gray-600 dark:text-slate-300 hover:text-gray-900 dark:hover:text-white transition-colors"
                        title="Change Language"
                    >
                        <AiOutlineGlobal size={24} />
                    </button>
                    {isLangDropdownOpen && (
                        <div
                            className={`absolute mt-2 w-32 bg-white dark:bg-[#1C2638] rounded-xl shadow-xl border border-gray-100 dark:border-slate-700 ring-1 ring-black/5 z-50 transition transform origin-top-left ${isRLT ? "left-0" : "right-0"}`}>
                            <button
                                onClick={() => { changeLanguage('en'); setIsLangDropdownOpen(false); }}
                                className="rounded-t-xl block w-full text-left px-4 py-2.5 text-sm text-gray-700 dark:text-slate-300 hover:bg-[#B12C2E] hover:text-white cursor-pointer transition-colors"
                            >
                                {t("dashboard:header.english")}
                            </button>
                            <button
                                onClick={() => { changeLanguage('ar'); setIsLangDropdownOpen(false); }}
                                className="rounded-b-xl block w-full text-left px-4 py-2.5 text-sm text-gray-700 dark:text-slate-300 hover:bg-[#B12C2E] hover:text-white cursor-pointer transition-colors"
                            >
                                {t("dashboard:header.arabic")}
                            </button>
                        </div>
                    )}
                </div>

                {/* User Dropdown */}
                <div className="relative" ref={wrapperRef}>
                    <div
                        onClick={() => setIsDropdownOpen(!isDropdownOpen)}
                        className="w-9 h-9 rounded-full bg-gradient-to-tr from-[#144995] to-[#B12C2E] cursor-pointer flex items-center justify-center shadow-md border-2 border-white dark:border-slate-800"
                    >
                        <svg
                            className="w-5 h-5 text-white"
                            fill="none"
                            stroke="currentColor"
                            strokeWidth={2}
                            viewBox="0 0 24 24"
                        >
                            <circle cx="12" cy="8" r="4" />
                            <path d="M4 20c0-4 8-4 8-4s8 0 8 4" />
                        </svg>
                    </div>
                    <div>
                        <HeaderDropdown
                            isDropdownOpen={isDropdownOpen}
                            setIsDropdownOpen={setIsDropdownOpen}
                            user={user}
                            handleLogout={handleLogout}
                            ref={dropdownRef}
                            className="mt-6"
                        />
                    </div>
                </div>
            </div>
        </header>
    );
}
