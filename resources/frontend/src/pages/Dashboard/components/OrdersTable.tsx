import { useState, useEffect } from "react";
import { useTranslation } from "react-i18next";
import ApiClient from "../../../ApiClient";
import type { Paginated, AgentOrder } from "../../../types";
import {
    MdKeyboardDoubleArrowLeft,
    MdKeyboardDoubleArrowRight,
} from "react-icons/md";
import { RiArrowLeftSLine, RiArrowRightSLine } from "react-icons/ri";
import { useSelector } from "react-redux";

export default function OrdersTable() {
    const { t } = useTranslation(["dashboard"]);
    const lang = useTranslation().i18n.language;
    const [orders, setOrders] = useState<AgentOrder[]>([]);
    const [nextPageUrl, setNextPageUrl] = useState<string | null>(null);
    const [currentPage, setCurrentPage] = useState<number>(1);
    const [lastPage, setLastPage] = useState<number>(1);
    const perPage = 10;
    const [loadingOrders, setLoadingOrders] = useState<boolean>(false);

    const user = useSelector((state: any) => state.auth.user);

    useEffect(() => {
        setLoadingOrders(true);
        ApiClient.agent
            .getRecentOrders(perPage, currentPage)
            .then((response) => {
                const paginated = response.data.data as Paginated<AgentOrder>;
                setOrders(paginated.data);
                setCurrentPage(paginated.current_page);
                setLastPage(paginated.last_page);
                setNextPageUrl(paginated.next_page_url);
            })
            .catch((error) => console.error(error))
            .finally(() => setLoadingOrders(false));
    }, [currentPage]);

    const statusColorMap: Record<string, string> = {
        delivered: "text-teal-500 dark:text-teal-400",
        processing: "text-[#144995] dark:text-blue-400",
        cancelled: "text-red-500 dark:text-red-400",
        pending: "text-[#B12C2E] dark:text-[#E03E41]",
        shipped: "text-blue-500 dark:text-blue-400",
        returned: "text-purple-500 dark:text-purple-400",
        approved: "text-green-500 dark:text-green-400",
    };

    const getStatusColor = (status: string) =>
        statusColorMap[status] || "text-gray-500 dark:text-slate-400";

    const maxPageButtons = 5;
    let startPage = Math.max(1, currentPage - Math.floor(maxPageButtons / 2));
    let endPage = Math.min(lastPage, startPage + maxPageButtons - 1);
    if (endPage - startPage + 1 < maxPageButtons) {
        startPage = Math.max(1, endPage - maxPageButtons + 1);
    }
    const pageNumbers: number[] = [];
    for (let i = startPage; i <= endPage; i++) {
        pageNumbers.push(i);
    }

    return (
        <div className="mx-3 sm:mx-6 my-4 sm:my-6 bg-white dark:bg-[#151D2C] border border-gray-100 dark:border-slate-800 rounded-2xl shadow-sm transition-colors">
            <div className="p-4 sm:p-6">
                <div className="flex flex-col sm:flex-row sm:items-center justify-between mb-4 sm:mb-6 gap-2">
                    <h3 className="text-base sm:text-lg font-bold text-gray-900 dark:text-slate-100">
                        {t("dashboard:ordersTable.recentOrders")}
                    </h3>
                </div>

                {/* Desktop Table View */}
                <div className="orders-table-scroll overflow-x-auto pb-1">
                    <table className="w-full min-w-[600px]">
                        <thead>
                            <tr className="border-b border-gray-200 dark:border-slate-800">
                                <th
                                    className={`py-3 px-2 sm:px-4 font-semibold text-gray-600 dark:text-slate-400 text-sm whitespace-nowrap ${
                                        lang === "ar"
                                            ? "text-right"
                                            : "text-left"
                                    }`}
                                >
                                    {t("dashboard:ordersTable.orderId")}
                                </th>
                                <th
                                    className={`py-3 px-2 sm:px-4 font-semibold text-gray-600 dark:text-slate-400 text-sm whitespace-nowrap ${
                                        lang === "ar"
                                            ? "text-right"
                                            : "text-left"
                                    }`}
                                >
                                    {t("dashboard:ordersTable.customer")}
                                </th>
                                <th
                                    className={`py-3 px-2 sm:px-4 font-semibold text-gray-600 dark:text-slate-400 text-sm whitespace-nowrap ${
                                        lang === "ar"
                                            ? "text-right"
                                            : "text-left"
                                    }`}
                                >
                                    {t("dashboard:ordersTable.status")}
                                </th>
                                <th
                                    className={`py-3 px-2 sm:px-4 font-semibold text-gray-600 dark:text-slate-400 text-sm whitespace-nowrap ${
                                        lang === "ar"
                                            ? "text-right"
                                            : "text-left"
                                    }`}
                                >
                                    {t("dashboard:ordersTable.date")}
                                </th>
                                <th
                                    className={`py-3 px-2 sm:px-4 font-semibold text-gray-600 dark:text-slate-400 text-sm whitespace-nowrap ${
                                        lang === "ar"
                                            ? "text-right"
                                            : "text-left"
                                    }`}
                                >
                                    {t("dashboard:ordersTable.time")}
                                </th>
                                <th
                                    className={`py-3 px-2 sm:px-4 font-semibold text-gray-600 dark:text-slate-400 text-sm whitespace-nowrap ${
                                        lang === "ar"
                                            ? "text-right"
                                            : "text-left"
                                    }`}
                                >
                                    {t("dashboard:ordersTable.commission", {
                                        commission: user.commission,
                                    })}
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            {orders.map((order, index) => (
                                <tr
                                    key={index}
                                    className="border-b border-gray-100 dark:border-slate-800/60 hover:bg-gray-50 dark:hover:bg-slate-800/40 transition-colors"
                                >
                                    <td className="py-3 px-2 sm:px-4 text-sm font-medium text-gray-900 dark:text-slate-100 whitespace-nowrap">
                                        {order.id}
                                    </td>
                                    <td className="py-3 px-2 sm:px-4 text-sm text-gray-800 dark:text-slate-200 whitespace-nowrap">
                                        {order.customer}
                                    </td>
                                    <td className="py-3 px-2 sm:px-4 whitespace-nowrap">
                                        <span
                                            className={`text-sm font-semibold ${getStatusColor(
                                                order.status
                                            )}`}
                                        >
                                            {t(
                                                `dashboard:ordersTable.statusOptions.${order.status}`
                                            )}
                                        </span>
                                    </td>
                                    <td className="py-3 px-2 sm:px-4 text-sm text-gray-600 dark:text-slate-400 whitespace-nowrap">
                                        {order.date}
                                    </td>
                                    <td className="py-3 px-2 sm:px-4 text-sm text-gray-600 dark:text-slate-400 whitespace-nowrap">
                                        {order.time}
                                    </td>
                                    <td className="py-3 px-2 sm:px-4 text-sm text-gray-900 dark:text-white font-bold whitespace-nowrap">
                                        {order.commission}{" "}
                                        {t("dashboard:values.egp")}
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>

                {/* Pagination Controls */}
                {orders.length > 0 && nextPageUrl && <div className="flex justify-center items-center mt-8 px-2">
                    <div className="flex items-center space-x-1 sm:space-x-2">
                        {/* First page */}
                        <button
                            onClick={() => {
                                if (!loadingOrders && currentPage > 1)
                                    setCurrentPage(1);
                            }}
                            disabled={currentPage === 1 || loadingOrders}
                            className="p-2 cursor-pointer bg-gray-100 dark:bg-slate-800 text-gray-700 dark:text-slate-300 border border-gray-200 dark:border-slate-700 rounded-lg disabled:opacity-40 hover:bg-gray-200 dark:hover:bg-slate-700 transition-colors flex-shrink-0"
                        >
                            {lang === "en" ? (
                                <MdKeyboardDoubleArrowLeft className="w-4 h-4" />
                            ) : (
                                <MdKeyboardDoubleArrowRight className="w-4 h-4" />
                            )}
                        </button>
                        
                        {/* Prev arrow */}
                        <button
                            onClick={() => {
                                if (!loadingOrders && currentPage > 1)
                                    setCurrentPage(currentPage - 1);
                            }}
                            disabled={currentPage === 1 || loadingOrders}
                            className="p-2 cursor-pointer bg-gray-100 dark:bg-slate-800 text-gray-700 dark:text-slate-300 border border-gray-200 dark:border-slate-700 rounded-lg disabled:opacity-40 hover:bg-gray-200 dark:hover:bg-slate-700 transition-colors flex-shrink-0"
                        >
                            {lang === "en" ? (
                                <RiArrowLeftSLine className="w-4 h-4" />
                            ) : (
                                <RiArrowRightSLine className="w-4 h-4" />
                            )}
                        </button>
                        
                        {/* Page numbers */}
                        <div className="flex items-center space-x-1 overflow-x-auto scrollbar-hide max-w-[200px] mobile:max-w-[300px] sm:max-w-none">
                            {pageNumbers.map((page) => {
                                let shouldShow = false;
                                if (window.innerWidth < 384) {
                                    shouldShow = Math.abs(page - currentPage) <= 1;
                                } else if (window.innerWidth < 768) {
                                    shouldShow = Math.abs(page - currentPage) <= 2;
                                } else {
                                    shouldShow = Math.abs(page - currentPage) <= 3;
                                }
                                
                                return shouldShow ? (
                                    <button
                                        key={page}
                                        onClick={() => {
                                            if (!loadingOrders) setCurrentPage(page);
                                        }}
                                        disabled={loadingOrders}
                                        className={`px-3 py-1.5 rounded-lg cursor-pointer text-sm font-semibold min-w-[34px] flex-shrink-0 transition-all ${
                                            page === currentPage
                                                ? "bg-[#B12C2E] text-white shadow-sm"
                                                : "bg-gray-100 dark:bg-slate-800 text-gray-700 dark:text-slate-300 border border-gray-200 dark:border-slate-700 hover:bg-gray-200 dark:hover:bg-slate-700"
                                        } disabled:opacity-50`}
                                    >
                                        {page}
                                    </button>
                                ) : null;
                            })}
                        </div>
                        
                        {/* Next arrow */}
                        <button
                            onClick={() => {
                                if (!loadingOrders && currentPage < lastPage)
                                    setCurrentPage(currentPage + 1);
                            }}
                            disabled={currentPage === lastPage || loadingOrders}
                            className="p-2 cursor-pointer bg-gray-100 dark:bg-slate-800 text-gray-700 dark:text-slate-300 border border-gray-200 dark:border-slate-700 rounded-lg disabled:opacity-40 hover:bg-gray-200 dark:hover:bg-slate-700 transition-colors flex-shrink-0"
                        >
                            {lang === "en" ? (
                                <RiArrowRightSLine className="w-4 h-4" />
                            ) : (
                                <RiArrowLeftSLine className="w-4 h-4" />
                            )}
                        </button>
                        
                        {/* Last page */}
                        <button
                            onClick={() => {
                                if (!loadingOrders && currentPage < lastPage)
                                    setCurrentPage(lastPage);
                            }}
                            disabled={currentPage === lastPage || loadingOrders}
                            className="p-2 cursor-pointer bg-gray-100 dark:bg-slate-800 text-gray-700 dark:text-slate-300 border border-gray-200 dark:border-slate-700 rounded-lg disabled:opacity-40 hover:bg-gray-200 dark:hover:bg-slate-700 transition-colors flex-shrink-0"
                        >
                            {lang === "en" ? (
                                <MdKeyboardDoubleArrowRight className="w-4 h-4" />
                            ) : (
                                <MdKeyboardDoubleArrowLeft className="w-4 h-4" />
                            )}
                        </button>
                    </div>
                </div>}

                {/* No orders message */}
                {orders.length === 0 && !loadingOrders && (
                    <div className="flex flex-col items-center justify-center mt-12 mb-8 px-4">
                        <div className="bg-gray-50 dark:bg-[#1C2638] rounded-full p-6 mb-4">
                            <svg 
                                className="w-12 h-12 text-gray-400 dark:text-slate-500" 
                                fill="none" 
                                stroke="currentColor" 
                                viewBox="0 0 24 24"
                            >
                                <path 
                                    strokeLinecap="round" 
                                    strokeLinejoin="round" 
                                    strokeWidth={1.5} 
                                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" 
                                />
                            </svg>
                        </div>
                        <h3 className="text-lg font-bold text-gray-900 dark:text-slate-100 mb-2">
                            {t("dashboard:ordersTable.empty.title")}
                        </h3>
                        <p className="text-gray-500 dark:text-slate-400 text-center max-w-md leading-relaxed text-sm">
                            {t("dashboard:ordersTable.empty.subtitle")}
                        </p>
                    </div>
                )}

            </div>
        </div>
    );
}
