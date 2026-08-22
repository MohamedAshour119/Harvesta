import React, { useState, useRef, useEffect } from "react";
import {
    Cell,
    LineChart,
    Line,
    Pie,
    PieChart,
    ResponsiveContainer,
    XAxis,
    YAxis,
    Tooltip,
} from "recharts";
import { useTranslation } from "react-i18next";
import type { AgentDashboardInfo } from "../../../../../types";
import ApiClient from "../../../../../ApiClient";
import { FaChevronDown } from "react-icons/fa";
import { IoCheckmarkSharp } from "react-icons/io5";

interface ChartProps {
    data: AgentDashboardInfo;
    loading?: boolean;
}

export default function Chart({ data }: ChartProps) {
    const [selectedYear, setSelectedYear] = useState("2026");
    const [isOpen, setIsOpen] = useState(false);
    const [focusedIndex, setFocusedIndex] = useState(-1);
    const dropdownRef = useRef<HTMLDivElement>(null);
    const selectRef = useRef<HTMLButtonElement>(null);
    const { t } = useTranslation(["dashboard"]);
    const lang = useTranslation().i18n.language;
    const currentYear = new Date().getFullYear();
    const availableYears = Array.from(
        { length: currentYear - 2023 + 1 },
        (_, i) => (2023 + i).toString()
    );

    useEffect(() => {
        const handleClickOutside = (event: any) => {
            if (
                dropdownRef.current &&
                !dropdownRef.current.contains(event.target)
            ) {
                setIsOpen(false);
                setFocusedIndex(-1);
            }
        };

        document.addEventListener("mousedown", handleClickOutside);
        return () =>
            document.removeEventListener("mousedown", handleClickOutside);
    }, []);

    const handleKeyDown = (e: React.KeyboardEvent) => {
        if (!isOpen) {
            if (e.key === "Enter" || e.key === " " || e.key === "ArrowDown") {
                e.preventDefault();
                setIsOpen(true);
                setFocusedIndex(availableYears.indexOf(selectedYear));
            }
            return;
        }

        switch (e.key) {
            case "Escape":
                setIsOpen(false);
                setFocusedIndex(-1);
                selectRef.current?.focus();
                break;
            case "ArrowDown":
                e.preventDefault();
                setFocusedIndex((prev) =>
                    prev < availableYears.length - 1 ? prev + 1 : prev
                );
                break;
            case "ArrowUp":
                e.preventDefault();
                setFocusedIndex((prev) => (prev > 0 ? prev - 1 : prev));
                break;
            case "Enter":
                e.preventDefault();
                if (focusedIndex >= 0) {
                    setSelectedYear(availableYears[focusedIndex]);
                    setIsOpen(false);
                    setFocusedIndex(-1);
                    selectRef.current?.focus();
                }
                break;
            case "Tab":
                setIsOpen(false);
                setFocusedIndex(-1);
                break;
        }
    };

    const handleOptionClick = (year: string) => {
        setSelectedYear(year);
        setIsOpen(false);
        setFocusedIndex(-1);
        selectRef.current?.focus();
    };

    const [salesData, setSalesData] = useState(data.sales_performance);

    useEffect(() => {
        ApiClient.agent
            .getSalesPerformance(selectedYear)
            .then((res) => setSalesData(res.data.data))
            .catch((err) => console.error("Error loading sales data:", err));
    }, [selectedYear]);

    const hasSalesPerformanceData = salesData.some(
        (item) => Number(item?.value ?? 0) > 0
    );
    const maxSalesValue = salesData.reduce(
        (max, item) => Math.max(max, Number(item?.value ?? 0)),
        0
    );
    const chartUpperBound = maxSalesValue > 0
        ? Math.ceil(maxSalesValue * 1.15 / 1000) * 1000
        : 1000;

    const topSelling = data.top_selling_categories ?? [];
    const totalValue = topSelling.reduce((sum, item) => sum + item.value, 0);
    const COLORS = ["#B12C2E", "#144995", "#D95C5E", "#6A8FCC"];
    const pieData = topSelling.map((item, index) => {
        let name = item.name;
        try {
            const obj = JSON.parse(item.name);
            name = obj[lang] || obj.en;
        } catch { }
        return {
            name,
            value: item.value,
            color: COLORS[index % COLORS.length],
        };
    });
    const hasTopSellingCategories = pieData.length > 0 && totalValue > 0;

    return (
        <div className="grid grid-cols-1 xl:grid-cols-3 gap-4 sm:gap-6 px-0 sm:px-6 mt-4 sm:mt-6">
            {/* Sales Performance Chart */}
            <div className="xl:col-span-2 bg-white dark:bg-[#151D2C] border border-gray-100 dark:border-slate-800 rounded-2xl px-0 sm:px-6 py-4 sm:py-6 shadow-sm transition-colors">
                <div className="flex flex-col sm:flex-row sm:items-center justify-between mb-4 sm:mb-6 gap-2">
                    <h3 className="text-base px-3 sm:text-lg font-bold text-gray-900 dark:text-slate-100">
                        {t("dashboard:charts.salesPerformance")}
                    </h3>

                    {/* Custom Select Dropdown */}
                    <div className="relative px-3" ref={dropdownRef}>
                        <button
                            ref={selectRef}
                            type="button"
                            onClick={() => {
                                setIsOpen(!isOpen);
                                if (!isOpen) {
                                    setFocusedIndex(
                                        availableYears.indexOf(selectedYear)
                                    );
                                }
                            }}
                            onKeyDown={handleKeyDown}
                            className={`
                                w-full sm:w-auto min-w-[120px] px-4 py-2.5 cursor-pointer text-sm font-semibold text-gray-700 dark:text-slate-300
                                bg-white dark:bg-[#1C2638] border rounded-xl shadow-sm
                                flex items-center justify-between gap-2
                                transition-all duration-200 ease-in-out
                                hover:border-[#B12C2E] dark:hover:border-[#E03E41]
                                focus:outline-none focus:ring-2 focus:ring-[#B12C2E]/20
                                ${isOpen
                                    ? "border-[#B12C2E] dark:border-[#E03E41] shadow-md"
                                    : "border-gray-200 dark:border-slate-700"
                                }
                            `}
                            aria-haspopup="listbox"
                            aria-expanded={isOpen}
                            aria-labelledby="select-label"
                        >
                            <span className="truncate">{selectedYear}</span>
                            <FaChevronDown
                                className={`w-3 h-3 text-gray-400 dark:text-slate-500 transition-transform duration-200 ${isOpen ? "rotate-180" : ""
                                    }`}
                            />
                        </button>

                        {isOpen && (
                            <div className="absolute left-0 z-50 w-full mt-1 bg-white dark:bg-[#1C2638] border border-gray-200 dark:border-slate-700 rounded-xl shadow-xl overflow-hidden">
                                <ul
                                    role="listbox"
                                    className="py-1 max-h-60 overflow-auto"
                                    aria-labelledby="select-label"
                                >
                                    {availableYears.map((year, index) => (
                                        <li
                                            key={year}
                                            role="option"
                                            aria-selected={
                                                selectedYear === year
                                            }
                                            className={`
                                                px-4 py-2.5 text-sm cursor-pointer
                                                flex items-center justify-between
                                                transition-colors duration-150
                                                ${selectedYear === year
                                                    ? "bg-[#B12C2E]/10 text-[#B12C2E] dark:text-[#E03E41] font-bold"
                                                    : "text-gray-700 dark:text-slate-300 hover:bg-gray-50 dark:hover:bg-slate-800"
                                                }
                                                ${focusedIndex === index
                                                    ? "bg-gray-50 dark:bg-slate-800"
                                                    : ""
                                                }
                                            `}
                                            onClick={() =>
                                                handleOptionClick(year)
                                            }
                                            onMouseEnter={() =>
                                                setFocusedIndex(index)
                                            }
                                        >
                                            <span>{year}</span>
                                            {selectedYear === year && (
                                                <IoCheckmarkSharp className="w-4 h-4 text-[#B12C2E] dark:text-[#E03E41]" />
                                            )}
                                        </li>
                                    ))}
                                </ul>
                            </div>
                        )}
                    </div>
                </div>

                <div className="h-64 sm:h-72">
                    {hasSalesPerformanceData ? (
                        <ResponsiveContainer width="100%" height="100%">
                            <LineChart
                                data={salesData}
                                margin={{ top: 5, right: 0, left: 0, bottom: 5 }}
                            >
                                <Tooltip
                                    formatter={(value: number | string) =>
                                        new Intl.NumberFormat(undefined, {
                                            maximumFractionDigits: 2,
                                        }).format(Number(value))
                                    }
                                    contentStyle={{
                                        backgroundColor: '#151D2C',
                                        borderColor: '#334155',
                                        borderRadius: '0.75rem',
                                        color: '#F8FAFC'
                                    }}
                                />
                                <XAxis
                                    dataKey="month"
                                    axisLine={false}
                                    tickLine={false}
                                    tick={{ fontSize: 12, fill: "#94A3B8" }}
                                    padding={{ left: 50, right: 30 }}
                                />
                                <YAxis
                                    axisLine={false}
                                    tickLine={false}
                                    tick={{ fontSize: 12, fill: "#94A3B8" }}
                                    domain={[0, chartUpperBound]}
                                    tickFormatter={(value: number) =>
                                        new Intl.NumberFormat(undefined, {
                                            maximumFractionDigits: 2,
                                        }).format(value)
                                    }
                                />
                                <Line
                                    type="monotone"
                                    dataKey="value"
                                    stroke="#B12C2E"
                                    strokeWidth={3}
                                    dot={{ fill: "#B12C2E", strokeWidth: 0, r: 4 }}
                                    activeDot={{ r: 6, fill: "#B12C2E" }}
                                />
                            </LineChart>
                        </ResponsiveContainer>
                    ) : (
                        <div className="relative h-full overflow-hidden rounded-2xl border border-gray-100 dark:border-slate-800 bg-gray-50 dark:bg-[#1C2638] px-5 py-6 shadow-sm flex items-center justify-center text-center">
                            <div>
                                <h4 className="text-base font-bold text-gray-900 dark:text-slate-100">
                                    {t("dashboard:charts.emptyState.title")}
                                </h4>
                                <p className="mt-1 text-xs text-gray-500 dark:text-slate-400 max-w-sm">
                                    {t("dashboard:charts.emptyState.description")}
                                </p>
                            </div>
                        </div>
                    )}
                </div>
            </div>

            {/* Right Side Analytics */}
            <div className="space-y-4 sm:space-y-6">
                {/* Monthly Target Progress */}
                <div className="bg-white dark:bg-[#151D2C] border border-gray-100 dark:border-slate-800 rounded-2xl p-4 sm:p-6 shadow-sm transition-colors">
                    <h3 className="text-base sm:text-lg font-bold text-gray-900 dark:text-slate-100 mb-4">
                        {t("dashboard:charts.monthlyTargetProgress")}
                    </h3>
                    <div className="flex items-center justify-between mb-2">
                        <span className="text-gray-600 dark:text-slate-400 text-sm font-semibold">
                            {t("dashboard:charts.target")}
                            <span>
                                {" "}
                                {data.monthly_target?.toFixed(2)}{" "}
                                {t("dashboard:values.egp")}
                            </span>
                        </span>
                        <span className="font-bold text-gray-900 dark:text-white">
                            {data.monthly_target_progress_percent?.toFixed(2)}%
                        </span>
                    </div>
                    <div className="w-full bg-gray-200 dark:bg-slate-700 rounded-full h-2.5 mb-2">
                        <div
                            className="bg-[#B12C2E] dark:bg-[#E03E41] h-2.5 rounded-full transition-all"
                            style={{
                                width: `${Math.min(data.monthly_target_progress_percent, 100)}%`,
                            }}
                        ></div>
                    </div>
                    <div className="text-lg sm:text-xl font-extrabold text-gray-900 dark:text-white">
                        <span>
                            {data.monthly_target_progress?.toFixed(2)}{" "}
                            {t("dashboard:values.egp")}
                        </span>
                    </div>
                </div>

                {/* Customer Acquisition */}
                <div className="bg-white dark:bg-[#151D2C] border border-gray-100 dark:border-slate-800 rounded-2xl p-4 sm:p-6 shadow-sm transition-colors">
                    <h3 className="text-base sm:text-lg font-bold text-gray-900 dark:text-slate-100 mb-4">
                        {t("dashboard:charts.customerAcquisition")}
                    </h3>
                    <div className="space-y-3">
                        <div className="flex justify-between text-sm">
                            <span className="text-gray-600 dark:text-slate-400">
                                {t("dashboard:charts.newClientsMonth")}
                            </span>
                            <span className="font-bold text-gray-900 dark:text-slate-100">
                                {data.new_clients}
                            </span>
                        </div>
                        <div className="flex justify-between text-sm">
                            <span className="text-gray-600 dark:text-slate-400">
                                {t("dashboard:charts.repeatBuyers")}
                            </span>
                            <span className="font-bold text-gray-900 dark:text-slate-100">
                                {data.repeat_buyers}
                            </span>
                        </div>
                        <div className="flex justify-between text-sm">
                            <span className="text-gray-600 dark:text-slate-400">
                                {t("dashboard:charts.conversionRate")}
                            </span>
                            <span className="font-bold text-teal-600 dark:text-teal-400">
                                {data.conversion_rate}%
                            </span>
                        </div>
                    </div>
                </div>

                {/* Top-Selling Categories */}
                <div className="bg-white dark:bg-[#151D2C] border border-gray-100 dark:border-slate-800 rounded-2xl p-4 sm:p-6 shadow-sm transition-colors">
                    <h3 className="text-base sm:text-lg font-bold text-gray-900 dark:text-slate-100 mb-4">
                        {t("dashboard:charts.topSellingCategories")}
                    </h3>
                    {hasTopSellingCategories ? (
                        <>
                            <div className="h-24 sm:h-32 mb-4 flex items-center justify-center">
                                <ResponsiveContainer width="100%" height="100%">
                                    <PieChart>
                                        <Tooltip
                                            formatter={(
                                                value: number,
                                                name: string
                                            ) => [
                                                    `${((value / totalValue) * 100).toFixed(
                                                        2
                                                    )}%`,
                                                    name,
                                                ]}
                                            contentStyle={{
                                                backgroundColor: '#151D2C',
                                                borderColor: '#334155',
                                                borderRadius: '0.75rem',
                                                color: '#F8FAFC'
                                            }}
                                        />
                                        <Pie
                                            data={pieData}
                                            cx="50%"
                                            cy="50%"
                                            innerRadius={25}
                                            outerRadius={45}
                                            paddingAngle={2}
                                            dataKey="value"
                                            labelLine={false}
                                        >
                                            {pieData.map((entry, index) => (
                                                <Cell
                                                    key={`cell-${index}`}
                                                    fill={entry.color}
                                                />
                                            ))}
                                        </Pie>
                                    </PieChart>
                                </ResponsiveContainer>
                            </div>
                            <div className="space-y-2">
                                {pieData.map((item, index) => (
                                    <div
                                        key={index}
                                        className="flex items-center justify-between"
                                    >
                                        <div className="flex items-center">
                                            <div
                                                className={`w-3 h-3 rounded-full flex-shrink-0`}
                                                style={{ backgroundColor: item.color, marginLeft: lang === 'ar' ? '8px' : '0', marginRight: lang === 'en' ? '8px' : '0' }}
                                            ></div>
                                            <span className="text-xs sm:text-sm text-gray-600 dark:text-slate-400">
                                                {item.name}
                                            </span>
                                        </div>
                                        <span className="text-xs sm:text-sm font-bold text-gray-900 dark:text-slate-100">
                                            {item.value?.toFixed(2)} {t('dashboard:values.egp')}
                                        </span>
                                    </div>
                                ))}
                            </div>
                        </>
                    ) : (
                        <div className="relative overflow-hidden rounded-2xl border border-gray-100 dark:border-slate-800 bg-gray-50 dark:bg-[#1C2638] px-4 py-4 text-center">
                            <h4 className="text-sm font-bold text-gray-900 dark:text-slate-100">
                                {t("dashboard:charts.categoriesEmptyState.title")}
                            </h4>
                            <p className="mt-1 text-xs text-gray-500 dark:text-slate-400">
                                {t("dashboard:charts.categoriesEmptyState.description")}
                            </p>
                        </div>
                    )}
                </div>
            </div>
        </div>
    );
}
