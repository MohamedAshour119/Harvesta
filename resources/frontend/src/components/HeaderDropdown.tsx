import React, { forwardRef } from "react";
import { Link } from "react-router-dom";
import { useTranslation } from "react-i18next";
import { FiLogOut, FiSettings, FiShoppingBag, FiUser } from "react-icons/fi";
import i18n from "../i18n";

interface HeaderDropdownProps {
    isDropdownOpen: boolean;
    setIsDropdownOpen: (open: boolean) => void;
    user: any;
    handleLogout: () => void;
    className?: string;
}

const HeaderDropdown = forwardRef<HTMLDivElement, HeaderDropdownProps>(
    (props, ref) => {
        const {
            isDropdownOpen,
            setIsDropdownOpen,
            user,
            handleLogout,
            className,
        } = props;
        const { t } = useTranslation(["auth"]);

        return (
            <div
                ref={ref}
                className={`${className || ''} absolute end-0 mt-2 w-64 bg-white dark:bg-[#151D2C] rounded-xl shadow-2xl border border-gray-100 dark:border-slate-700/60 z-50 overflow-hidden transition-all duration-300 origin-top-right transform ${isDropdownOpen
                    ? "opacity-100 scale-100 translate-y-0"
                    : "opacity-0 scale-95 -translate-y-2 pointer-events-none"
                    }`}
            >
                <div className="p-2">
                    <div className="p-3 border-b border-gray-100 dark:border-slate-700/60">
                        <p className="text-xs font-medium text-gray-500 dark:text-slate-400">
                            {t("auth:authDropdown.SignedInAs")}
                        </p>
                        <p className="font-semibold text-gray-900 dark:text-slate-100 truncate text-sm mt-0.5">{user?.email}</p>
                    </div>

                    <ul className="py-2 space-y-1">
                        <li>
                            <Link
                                to="/profile"
                                className="flex items-center px-4 py-2.5 text-gray-800 dark:text-slate-200 hover:text-[#B12C2E] dark:hover:text-[#E03E41] hover:bg-gray-50 dark:hover:bg-slate-800/60 rounded-lg transition-colors group text-sm"
                                onClick={() => setIsDropdownOpen(false)}
                            >
                                <FiUser className="me-3 text-gray-500 dark:text-slate-400 group-hover:text-[#B12C2E] dark:group-hover:text-[#E03E41] transition-colors" />
                                <span>{t("auth:authDropdown.MyProfile")}</span>
                            </Link>
                        </li>
                        {!user?.is_agent && (
                            <li>
                                <Link
                                    to="/profile?section=orders"
                                    className="flex items-center px-4 py-2.5 text-gray-800 dark:text-slate-200 hover:text-[#B12C2E] dark:hover:text-[#E03E41] hover:bg-gray-50 dark:hover:bg-slate-800/60 rounded-lg transition-colors group text-sm"
                                    onClick={() => setIsDropdownOpen(false)}
                                >
                                    <FiShoppingBag className="me-3 text-gray-500 dark:text-slate-400 group-hover:text-[#B12C2E] dark:group-hover:text-[#E03E41] transition-colors" />
                                    <span>{t("auth:authDropdown.MyOrders")}</span>
                                </Link>
                            </li>
                        )}
                        {!!user?.is_agent && (
                            <li>
                                <Link
                                    to="/dashboard"
                                    className="flex items-center px-4 py-2.5 text-gray-800 dark:text-slate-200 hover:text-[#B12C2E] dark:hover:text-[#E03E41] hover:bg-gray-50 dark:hover:bg-slate-800/60 rounded-lg transition-colors group text-sm"
                                    onClick={() => setIsDropdownOpen(false)}
                                >
                                    <FiSettings className="me-3 text-gray-500 dark:text-slate-400 group-hover:text-[#B12C2E] dark:group-hover:text-[#E03E41] transition-colors" />
                                    <span>
                                        {t("auth:authDropdown.Dashboard")}
                                    </span>
                                </Link>
                            </li>
                        )}
                    </ul>

                    <div className="border-t border-gray-100 dark:border-slate-700/60 mt-2 pt-2">
                        <button
                            onClick={handleLogout}
                            className="cursor-pointer flex w-full items-center px-4 py-2.5 text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-950/30 rounded-lg transition-colors group text-sm font-medium"
                        >
                            <FiLogOut className="me-3 transition-colors" />
                            <span>{t("auth:authDropdown.Logout")}</span>
                        </button>
                    </div>
                </div>
            </div>
        );
    }
);

export default HeaderDropdown;
