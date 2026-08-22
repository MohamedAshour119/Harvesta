import React, { useState, useRef, useEffect } from "react";
import {
    ResponsiveContainer,
    XAxis,
    YAxis,
    Tooltip,
    AreaChart,
    Area,
} from "recharts";
import MonthlySummaryCard from "./MonthlySummaryCard";
import type { AgentDashboardInfoCommissionAndBonus, Paginated } from "../../../../../types";
import { FiDownload } from "react-icons/fi";
import { useTranslation } from "react-i18next";
import ApiClient from "../../../../../ApiClient";
import * as XLSX from 'xlsx';

interface ChartProps {
    data: AgentDashboardInfoCommissionAndBonus;
    loading?: boolean;
}

export default function Chart({ data }: ChartProps) {
    const chartRef = useRef<HTMLDivElement>(null);
    const { t, i18n } = useTranslation(["dashboard"]);
    const lang = i18n.language;
    const isRTL = i18n.language === "ar";

    const [isSmallScreen, setIsSmallScreen] = useState<boolean>(window.innerWidth < 640);
    useEffect(() => {
        const handleResize = () => setIsSmallScreen(window.innerWidth < 640);
        window.addEventListener("resize", handleResize);
        return () => window.removeEventListener("resize", handleResize);
    }, []);

    const periodData = data.commissions_per_period;
    const monthLabel = new Date().toLocaleString(undefined, { month: 'long', year: 'numeric' });
    const [isDownloading, setIsDownloading] = useState(false);

    const handleDownloadChartAsCSV = async () => {
        setIsDownloading(true);
        try {
            let allCommissions: any[] = [];
            let currentPage = 1;
            let lastPage = 1;

            do {
                const response = await ApiClient.agent.getCommissionsTimeline(100, currentPage);
                const paginatedData = response.data.data as Paginated<any>;
                allCommissions = allCommissions.concat(paginatedData.data);
                lastPage = paginatedData.last_page;
                currentPage++;
            } while (currentPage <= lastPage);

            const headers = [
                t("dashboard:csvHeaders.orderNumber"),
                t("dashboard:csvHeaders.customerName"),
                t("dashboard:csvHeaders.commission"),
                t("dashboard:csvHeaders.date"),
            ];

            const rows = allCommissions.map(c => [
                { v: c.order_number, t: 's' },
                c.user_name,
                { v: c.total_commission, t: isRTL ? 'n' : 's' },
                { v: c.formatted_date, t: isRTL ? 'n' : 's' }
            ]);

            const ws = XLSX.utils.aoa_to_sheet([headers, ...rows]);
            ws['!cols'] = [
                { wch: 15 },
                { wch: 30 },
                { wch: 20 },
                { wch: 20 }
            ];

            const wb = XLSX.utils.book_new();
            XLSX.utils.book_append_sheet(wb, ws, 'Commissions Timeline');

            const wbout = XLSX.write(wb, { bookType: 'xlsx', type: 'array' });
            const blob = new Blob([wbout], { type: 'application/octet-stream' });
            const link = document.createElement("a");
            const url = URL.createObjectURL(blob);
            link.setAttribute("href", url);
            link.setAttribute("download", "commissions_timeline.xlsx");
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
            URL.revokeObjectURL(url);

        } catch (error) {
            console.error("Error downloading XLSX:", error);
        } finally {
            setIsDownloading(false);
        }
    };

    return (
        <div className="grid grid-cols-1 xl:grid-cols-[4fr_1fr] gap-4 sm:gap-6 px-0 sm:px-6 mt-1 min-h-max">
            {/* Sales Performance Chart */}
            <div className="bg-white dark:bg-[#151D2C] border border-gray-100 dark:border-slate-800 rounded-2xl px-0 sm:px-6 py-4 sm:py-6 shadow-sm max-sm:min-h-[450px] transition-colors">
                <div className="flex flex-col sm:flex-row sm:items-center justify-between mb-4 sm:mb-6 gap-2">
                    <div className="px-2">
                        <h3 className="text-base px-3 sm:text-lg font-bold text-gray-700 dark:text-slate-300">
                            {t("dashboard:commissionAndBonus.weeklyCommission", {
                                month: monthLabel,
                            })}
                        </h3>
                        <div className="flex items-center gap-2 px-3 sm:text-lg">
                            <span className="text-[#B12C2E] dark:text-[#E03E41] font-extrabold">{data.total_commission_this_month?.toFixed(2)} EGP</span>
                            <span className="text-gray-500 dark:text-slate-400 text-sm font-medium">{t("dashboard:commissionAndBonus.liveData", { month: monthLabel })}</span>
                        </div>
                    </div>

                    <button
                        onClick={handleDownloadChartAsCSV}
                        disabled={isDownloading}
                        className="w-fit inline-flex mx-4 items-center cursor-pointer gap-2 px-5 py-2.5 bg-[#B12C2E] hover:bg-[#972426] dark:bg-[#B12C2E] dark:hover:bg-[#8F2325] text-white font-bold rounded-xl transition-all shadow-sm disabled:opacity-50 text-sm"
                    >
                        <FiDownload size={18} />
                        {isDownloading ? t("dashboard:commissionAndBonus.downloading") : t("dashboard:commissionAndBonus.downloadChartAsCSV")}
                    </button>
                </div>

                <div className="h-48 sm:h-64">
                    <ResponsiveContainer ref={chartRef} width="100%" height="100%" minHeight={300}>
                        <AreaChart
                            data={periodData}
                            margin={{ top: 20, right: lang === "ar" ? 0 : 30, left: lang === "ar" ? 0 : 30, bottom: 40 }}
                        >
                            <defs>
                                <linearGradient id="colorGradient" x1="0" y1="0" x2="0" y2="1">
                                    <stop offset="5%" stopColor="#B12C2E" stopOpacity={0.3}/>
                                    <stop offset="95%" stopColor="#144995" stopOpacity={0.08}/>
                                </linearGradient>
                            </defs>
                            <XAxis
                                dataKey="period"
                                axisLine={false}
                                tickLine={false}
                                tick={{ fontSize: 11, fill: "#94A3B8" }}
                                interval={isSmallScreen ? 'preserveStartEnd' : 0}
                                angle={isSmallScreen ? -45 : 0}
                                textAnchor={isSmallScreen ? 'end' : 'middle'}
                                padding={{ left: lang === "ar" ? 80 : 40, right: lang === "ar" ? 80 : 40 }}
                                reversed={lang === "ar"}
                            />
                            <YAxis
                                axisLine={false}
                                tickLine={false}
                                tick={{ fontSize: 11, fill: "#94A3B8" }}
                                domain={[0, 5000]}
                                tickFormatter={(value) => `EGP ${(value/1000).toFixed(2)}k`}
                            />
                            <Tooltip
                                contentStyle={{
                                    backgroundColor: '#151D2C',
                                    borderColor: '#334155',
                                    borderRadius: '0.75rem',
                                    color: '#F8FAFC'
                                }}
                            />
                            <Area
                                type="monotone"
                                dataKey="value"
                                stroke="#B12C2E"
                                strokeWidth={3}
                                fill="url(#colorGradient)"
                                dot={{ fill: "#B12C2E", strokeWidth: 0, r: 5 }}
                                activeDot={{ r: 7, fill: "#B12C2E", strokeWidth: 2, stroke: "#fff" }}
                            />
                        </AreaChart>
                    </ResponsiveContainer>
                </div>
            </div>

            {/* Right Side Analytics */}
            <div className="space-y-4 sm:space-y-6">
                <div>
                    <MonthlySummaryCard data={data}/>
                </div>
            </div>
        </div>
    );
}
