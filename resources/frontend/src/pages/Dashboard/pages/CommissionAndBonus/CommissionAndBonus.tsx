import { Sidebar } from "../../components/Sidebar";
import { useEffect, useState } from "react";
import { useTranslation } from "react-i18next";
import Chart from "./components/Chart";
import type { AgentDashboardInfoCommissionAndBonus } from "../../../../types";
import ApiClient from "../../../../ApiClient";
import MetricsCardsSection from "./components/MetricsCardsSection";
import Header from "../../components/Header";
import { CommissionsTimeLine } from "./components/CommissionsTimeLine";
import OrdersTable from "../../components/OrdersTable";

const CommissionAndBonus = () => {
    const [sidebarOpen, setSidebarOpen] = useState(false);
    const { t } = useTranslation(["dashboard"]);

    const [dashboardInfo, setDashboardInfo] = useState<AgentDashboardInfoCommissionAndBonus>({
        total_commission: 0,
        pending_commissions: 0,
        total_commission_last_month: 0,
        top_commission_source: { name: "", commission: 0 },
        bonus: { 
            amount: 0, 
            paid_at: "" 
        },
        total_orders_this_month: 0,
        average_commission_this_month: 0,
        highest_commission_this_month: 0,
        total_sales_this_month: 0,
        commissions_per_period: [],
        total_commission_this_month: 0,
    });
    const [, setLoading] = useState(false);

    useEffect(() => {
        const fetchDashboardInfo = async () => {
            setLoading(true);
            try {
                const response: any =
                    await ApiClient.agent.getAgentDashboardInfoCommissionAndBonus();
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
                <Header setSidebarOpen={setSidebarOpen} title={t("dashboard:commissionAndBonus.headerTitle")}/>

                {/* Main Content Area - Scrollable */}
                <div className="flex-1 overflow-y-auto overflow-x-hidden">
                    <div className="min-h-full flex flex-col pb-12">
                        {/* Main Content */}
                        <div className="flex-1">
                            {/* Title Banner */}
                            <div className="mx-3 sm:mx-6 mt-4 sm:mt-6 rounded-2xl p-3 sm:p-4 font-bold text-2xl sm:text-3xl text-gray-900 dark:text-slate-100">
                                {t("dashboard:commissionAndBonus.title")}
                            </div>

                            {/* Metrics Cards */}
                            <MetricsCardsSection
                                data={dashboardInfo}
                            />

                            {/* Charts and Analytics */}
                            <Chart data={dashboardInfo} />

                            {/* Recent Orders Table */}
                            <OrdersTable />

                            {/* Commissions Timeline */}
                            <CommissionsTimeLine />
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

export default CommissionAndBonus;