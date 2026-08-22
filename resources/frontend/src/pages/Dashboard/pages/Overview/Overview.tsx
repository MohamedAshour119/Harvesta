import { useEffect, useState } from "react";
import { useTranslation } from "react-i18next";
import Header from "../../components/Header";
import MetricsCardsSection from "./components/MetricsCardsSection";
import Chart from "./components/Chart";
import OrdersTable from "../../components/OrdersTable";
import { Sidebar } from "../../components/Sidebar";
import { useSelector } from "react-redux";
import type { RootState } from "../../../../store/store";
import ApiClient from "../../../../ApiClient";
import type { AgentDashboardInfo } from "../../../../types";

export const Overview = () => {
    const [sidebarOpen, setSidebarOpen] = useState(false);
    const { t } = useTranslation(["dashboard"]);
    const user = useSelector((state: RootState) => state.auth?.user);

    const [dashboardInfo, setDashboardInfo] = useState<AgentDashboardInfo>({
        total_commission_this_month: 0,
        pending_commissions: 0,
        total_commission_last_month: 0,
        percent_change: 0,
        top_commission_source: { name: "", commission: 0 },
        monthly_target: 0,
        monthly_target_progress: 0,
        monthly_target_progress_percent: 0,
        new_clients: 0,
        repeat_buyers: 0,
        conversion_rate: 0,
        top_selling_categories: [],
        sales_performance: [],
    });
    const [, setLoading] = useState(false);

    useEffect(() => {
        const fetchDashboardInfo = async () => {
            setLoading(true);
            try {
                const response: any =
                    await ApiClient.agent.getAgentDashboardInfo();
                setDashboardInfo(response.data.data);
            } catch (error) {
                console.error("Error fetching dashboard info:", error);
            } finally {
                setLoading(false);
            }
        };

        fetchDashboardInfo();
    }, []);

    return (
        <div className="flex h-screen bg-gray-50 dark:bg-[#0B0F17] overflow-hidden w-full transition-colors">
            {/* Sidebar */}
            <Sidebar
                sidebarOpen={sidebarOpen}
                setSidebarOpen={setSidebarOpen}
            />

            {/* Main Content */}
            <div className="flex-1 flex flex-col min-w-0">
                {/* Header */}
                <Header setSidebarOpen={setSidebarOpen} title={t("dashboard:overview")}/>

                {/* Main Content Area - Scrollable */}
                <div className="flex-1 overflow-y-auto overflow-x-hidden">
                    <div className="min-h-full flex flex-col pb-12">
                        {/* Main Content */}
                        <div className="flex-1">
                            {/* Welcome Banner */}
                            <div className="mx-3 sm:mx-6 mt-4 sm:mt-6 rounded-2xl bg-gradient-to-r from-red-50 via-rose-50 to-blue-50 dark:from-[#221316] dark:via-[#1c1622] dark:to-[#121929] border border-[#B12C2E]/15 dark:border-slate-800 p-4 sm:p-6 shadow-sm">
                                <h2 className="text-lg sm:text-2xl text-gray-900 dark:text-slate-100 font-extrabold">
                                    {t("dashboard:welcome.title", {
                                        name: user?.full_name,
                                    })}
                                </h2>
                                <p className="text-gray-600 dark:text-slate-400 text-xs sm:text-sm mt-1">
                                    {t("dashboard:welcome.subtitle")}
                                </p>
                            </div>

                            {/* Metrics Cards */}
                            <MetricsCardsSection
                                data={dashboardInfo}
                            />

                            {/* Charts and Analytics */}
                            <Chart 
                                data={dashboardInfo}
                            />

                            {/* Recent Orders Table */}
                            <OrdersTable />
                        </div>
                    </div>
                </div>
            </div>

            {/* Mobile Sidebar Overlay */}
            {sidebarOpen && (
                <div
                    className="fixed inset-0 bg-black/60 z-40 lg:hidden"
                    onClick={() => setSidebarOpen(false)}
                />
            )}
        </div>
    );
};
