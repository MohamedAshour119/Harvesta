import { MdKeyboardDoubleArrowLeft, MdKeyboardDoubleArrowRight } from "react-icons/md";
import { RiArrowLeftSLine, RiArrowRightSLine } from "react-icons/ri";
import { CommissionItem } from "./CommissionItem";
import { useTranslation } from "react-i18next";
import { useState, useEffect } from "react";
import ApiClient from "../../../../../ApiClient";
import type { Paginated, AgentOrder } from "../../../../../types";

export function CommissionsTimeLine() {
    const { t } = useTranslation(["dashboard"]);
    const lang = useTranslation().i18n.language;
    const [loadingTimeLine, setLoadingTimeLine] = useState<boolean>(false);
    const [nextPageUrl, setNextPageUrl] = useState<string | null>(null);
    const [currentPage, setCurrentPage] = useState<number>(1);
    const [lastPage, setLastPage] = useState<number>(1);
    const perPage = 10;
    const [commissionsTimeline, setCommissionsTimeline] = useState<any>([]);

    useEffect(() => {
        setLoadingTimeLine(true);
        ApiClient.agent
            .getCommissionsTimeline(perPage, currentPage)
            .then((response) => {
                const paginated = response.data.data as Paginated<AgentOrder>;
                setCommissionsTimeline(paginated.data);
                setCurrentPage(paginated.current_page);
                setLastPage(paginated.last_page);
                setNextPageUrl(paginated.next_page_url);
            })
            .catch((error) => console.error(error))
            .finally(() => setLoadingTimeLine(false));
    }, [currentPage]);

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
        <div className={`grid grid-cols-1 ${commissionsTimeline.length > 0 ? "xl:grid-cols-[4fr_1fr]" : ""} gap-4 sm:gap-6 bg-white dark:bg-[#151D2C] border border-gray-100 dark:border-slate-800 rounded-2xl mx-3 sm:mx-6 my-4 sm:my-6 transition-colors`}>
            <div className="p-4 sm:p-6 w-full">
                <h2 className="text-lg sm:text-xl font-bold text-gray-900 dark:text-slate-100 mb-6">{t("dashboard:commissionAndBonus.commissionTimeline")}</h2>
                <div className="space-y-0">
                    {commissionsTimeline.map((commission: any) => (
                    <CommissionItem
                        key={commission.id}
                        userName={commission.user_name}
                        orderNumber={commission.order_number}
                        amount={commission.total_commission}
                        date={commission.formatted_date}
                    />
                    ))}
                </div>

                {/* Pagination Controls */}
                {commissionsTimeline.length > 0 && nextPageUrl && <div className="flex justify-center items-center mt-8 px-2">
                    <div className="flex items-center space-x-1 sm:space-x-2">
                        {/* First page */}
                        <button
                            onClick={() => {
                                if (!loadingTimeLine && currentPage > 1)
                                    setCurrentPage(1);
                            }}
                            disabled={currentPage === 1 || loadingTimeLine}
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
                                if (!loadingTimeLine && currentPage > 1)
                                    setCurrentPage(currentPage - 1);
                            }}
                            disabled={currentPage === 1 || loadingTimeLine}
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
                                            if (!loadingTimeLine) setCurrentPage(page);
                                        }}
                                        disabled={loadingTimeLine}
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
                                if (!loadingTimeLine && currentPage < lastPage)
                                    setCurrentPage(currentPage + 1);
                            }}
                            disabled={currentPage === lastPage || loadingTimeLine}
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
                                if (!loadingTimeLine && currentPage < lastPage)
                                    setCurrentPage(lastPage);
                            }}
                            disabled={currentPage === lastPage || loadingTimeLine}
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

                {/* No Commissions Message */}
                {commissionsTimeline.length === 0 && (
                    <div className="flex flex-col items-center justify-center mt-12 mb-8 px-4">
                        <div className="bg-emerald-50 dark:bg-emerald-950/40 rounded-full p-6 mb-4">
                            <svg 
                                className="w-12 h-12 text-emerald-500 dark:text-emerald-400" 
                                fill="none" 
                                stroke="currentColor" 
                                viewBox="0 0 24 24"
                            >
                                <path 
                                    strokeLinecap="round" 
                                    strokeLinejoin="round" 
                                    strokeWidth={1.5} 
                                    d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" 
                                />
                            </svg>
                        </div>
                        <h3 className="text-lg font-bold text-gray-900 dark:text-slate-100 mb-2">
                            {t("dashboard:commissionAndBonus.noCommissionsTitle") || "No Commissions Yet"}
                        </h3>
                        <p className="text-gray-500 dark:text-slate-400 text-center max-w-md leading-relaxed mb-6 text-sm">
                            {t("dashboard:commissionAndBonus.noCommissions") || "Your commission earnings will appear here as you make sales and referrals."}
                        </p>
                    </div>
                )}
            </div>
        </div>
    );
}
