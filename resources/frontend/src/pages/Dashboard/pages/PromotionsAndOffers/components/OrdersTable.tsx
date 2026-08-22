import { useState, useEffect } from "react";
import { useTranslation } from "react-i18next";
import ApiClient from "../../../../../ApiClient";
import type { Paginated, AgentOrder } from "../../../../../types";
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
            })
            .catch((error) => console.error(error))
            .finally(() => setLoadingOrders(false));
    }, [currentPage]);

    const statusColorMap: Record<string, string> = {
        delivered: "text-teal-500",
        processing: "text-[#144995]",
        cancelled: "text-red-500",
        pending: "text-[#B12C2E]",
        shipped: "text-blue-500",
        returned: "text-purple-500",
        approved: "text-green-500",
    };

    const getStatusColor = (status: string) =>
        statusColorMap[status] || "text-gray-500";

    const getCategory = (category: any) => {
        if (category && typeof category === "object") {
            return category[lang] || category.en || "";
        }
        return category;
    };

    // Pagination page number calculation
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
        <div className="sm:gap-6 mx-3 sm:mx-6 my-4 sm:my-6 bg-white rounded-lg">
            <div className="p-4 sm:p-6 border-b border-card_border">
                <div className="flex flex-col sm:flex-row sm:items-center justify-between mb-4 sm:mb-6 gap-2">
                    <h3 className="text-base sm:text-lg font-semibold text-gray-800">
                        {t("dashboard:promotionsAndOffers.offerPerformanceTable.title")}
                    </h3>
                </div>

                {/* Mobile Card View */}
                {/* <div className="block sm:hidden space-y-3">
                    {orders.slice(0, 3).map((order, index) => (
                        <div
                            key={index}
                            className="border border-gray-200 rounded-lg p-3"
                        >
                            <div className="flex justify-between items-start mb-2">
                                <span className="font-medium text-sm">
                                    {order.id}
                                </span>
                                <span
                                    className={`text-sm font-medium ${getStatusColor(
                                        order.status
                                    )}`}
                                >
                                    {t(
                                        `dashboard:ordersTable.statusOptions.${order.status}`
                                    )}
                                </span>
                            </div>
                            <div className="text-sm text-gray-600 space-y-1">
                                <div>
                                    {order.customer} •{" "}
                                    {getCategory(order.category)}
                                </div>
                                <div>
                                    {order.date} at {order.time}
                                </div>
                                <div className="font-medium text-gray-800">
                                    {order.commission}
                                </div>
                            </div>
                        </div>
                    ))}
                </div> */}

                {/* Desktop Table View */}
                <div className="overflow-x-auto">
                    <table className="w-full min-w-[600px]">
                        <thead>
                            <tr className="border-b border-gray-200">
                                <th
                                    className={`py-3 px-2 sm:px-4 font-medium text-gray-600 text-sm whitespace-nowrap ${
                                        lang === "ar"
                                            ? "text-right"
                                            : "text-left"
                                    }`}
                                >
                                    {t("dashboard:ordersTable.orderId")}
                                </th>
                                <th
                                    className={`py-3 px-2 sm:px-4 font-medium text-gray-600 text-sm whitespace-nowrap ${
                                        lang === "ar"
                                            ? "text-right"
                                            : "text-left"
                                    }`}
                                >
                                    {t("dashboard:ordersTable.customer")}
                                </th>
                                <th
                                    className={`py-3 px-2 sm:px-4 font-medium text-gray-600 text-sm whitespace-nowrap ${
                                        lang === "ar"
                                            ? "text-right"
                                            : "text-left"
                                    }`}
                                >
                                    {t("dashboard:ordersTable.status")}
                                </th>
                                <th
                                    className={`py-3 px-2 sm:px-4 font-medium text-gray-600 text-sm whitespace-nowrap ${
                                        lang === "ar"
                                            ? "text-right"
                                            : "text-left"
                                    }`}
                                >
                                    {t("dashboard:ordersTable.date")}
                                </th>
                                <th
                                    className={`py-3 px-2 sm:px-4 font-medium text-gray-600 text-sm whitespace-nowrap ${
                                        lang === "ar"
                                            ? "text-right"
                                            : "text-left"
                                    }`}
                                >
                                    {t("dashboard:ordersTable.time")}
                                </th>
                                <th
                                    className={`py-3 px-2 sm:px-4 font-medium text-gray-600 text-sm whitespace-nowrap ${
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
                                    className="border-b border-gray-100 hover:bg-gray-50"
                                >
                                    <td className="py-3 px-2 sm:px-4 text-sm text-gray-800 whitespace-nowrap">
                                        {order.id}
                                    </td>
                                    <td className="py-3 px-2 sm:px-4 text-sm text-gray-800 whitespace-nowrap">
                                        {order.customer}
                                    </td>
                                    <td className="py-3 px-2 sm:px-4 whitespace-nowrap">
                                        <span
                                            className={`text-sm font-medium ${getStatusColor(
                                                order.status
                                            )}`}
                                        >
                                            {t(
                                                `dashboard:ordersTable.statusOptions.${order.status}`
                                            )}
                                        </span>
                                    </td>
                                    <td className="py-3 px-2 sm:px-4 text-sm text-gray-600 whitespace-nowrap">
                                        {order.date}
                                    </td>
                                    <td className="py-3 px-2 sm:px-4 text-sm text-gray-600 whitespace-nowrap">
                                        {order.time}
                                    </td>
                                    <td className="py-3 px-2 sm:px-4 text-sm text-gray-800 font-medium whitespace-nowrap">
                                        {order.commission}{" "}
                                        {t("dashboard:values.egp")}
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>

                {/* Pagination Controls */}
                <div className="flex justify-center items-center mt-8 px-2">
                    <div className="flex items-center space-x-1 sm:space-x-2">
                        {/* First page */}
                        <button
                            onClick={() => {
                                if (!loadingOrders && currentPage > 1)
                                    setCurrentPage(1);
                            }}
                            disabled={currentPage === 1 || loadingOrders}
                            className="px-2 py-1 cursor-pointer bg-gray-200 rounded disabled:opacity-50 flex-shrink-0"
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
                            className="px-2 py-1 cursor-pointer bg-gray-200 rounded disabled:opacity-50 flex-shrink-0"
                        >
                            {lang === "en" ? (
                                <RiArrowLeftSLine className="w-4 h-4" />
                            ) : (
                                <RiArrowRightSLine className="w-4 h-4" />
                            )}
                        </button>
                        
                        {/* Page numbers - Responsive display */}
                        <div className="flex items-center space-x-1 overflow-x-auto scrollbar-hide max-w-[200px] mobile:max-w-[300px] sm:max-w-none">
                            {pageNumbers.map((page) => {
                                // Different logic for different screen sizes
                                let shouldShow = false;
                                
                                // Very small screens: show current page ± 1
                                if (window.innerWidth < 384) { // mobile-tiny to mobile
                                    shouldShow = Math.abs(page - currentPage) <= 1;
                                }
                                // Small to medium screens: show current page ± 2  
                                else if (window.innerWidth < 768) { // mobile to tablet
                                    shouldShow = Math.abs(page - currentPage) <= 2;
                                }
                                // Large screens: show current page ± 3
                                else {
                                    shouldShow = Math.abs(page - currentPage) <= 3;
                                }
                                
                                return shouldShow ? (
                                    <button
                                        key={page}
                                        onClick={() => {
                                            if (!loadingOrders) setCurrentPage(page);
                                        }}
                                        disabled={loadingOrders}
                                        className={`px-2 mobile:px-3 py-1 rounded cursor-pointer text-sm sm:text-base min-w-[32px] flex-shrink-0 ${
                                            page === currentPage
                                                ? "bg-[#B12C2E] text-white"
                                                : "bg-gray-200 text-gray-700"
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
                            className="px-2 py-1 cursor-pointer bg-gray-200 rounded disabled:opacity-50 flex-shrink-0"
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
                            className="px-2 py-1 cursor-pointer bg-gray-200 rounded disabled:opacity-50 flex-shrink-0"
                        >
                            {lang === "en" ? (
                                <MdKeyboardDoubleArrowRight className="w-4 h-4" />
                            ) : (
                                <MdKeyboardDoubleArrowLeft className="w-4 h-4" />
                            )}
                        </button>
                    </div>
                </div>

            </div>
        </div>
    );
}
