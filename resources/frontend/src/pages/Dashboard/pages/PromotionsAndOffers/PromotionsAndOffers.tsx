import React, { useEffect, useState } from "react";
import { Trans, useTranslation } from "react-i18next";
import Header from "../../components/Header";
import { Sidebar } from "../../components/Sidebar";
import { useSelector } from "react-redux";
import type { RootState } from "../../../../store/store";
import ApiClient from "../../../../ApiClient";
import type { AgentDashboardInfo } from "../../../../types";
import { SubHeader } from "./components/SubHeader";
import { FeaturedPromotions } from "./components/FeaturedPromotions";
import { BonusTierRewards } from "./components/BonusTierRewards";
import GenerateTrackingLink from "./components/GenerateTrackingLink";
import OrdersTable from "./components/OrdersTable";


export const PromotionsAndOffers = () => {
    const [sidebarOpen, setSidebarOpen] = useState(false);
    const { t } = useTranslation(["dashboard"]);
    const user = useSelector((state: RootState) => state.auth.user);

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
    const [loading, setLoading] = useState(false);
    const [detailSidebarOpen, setDetailSidebarOpen] = useState(false);
    const [selectedPromo, setSelectedPromo] = useState<any | null>(null);

    useEffect(() => {
        const fetchDashboardInfo = async () => {
            setLoading(true);
            try {
                const response: any =
                    await ApiClient.agent.getAgentDashboardInfo();
                console.log(response.data.data);
                setDashboardInfo(response.data.data);
            } catch (error) {
                console.error("Error fetching dashboard info:", error);
            } finally {
                setLoading(false);
            }
        };

        fetchDashboardInfo();
    }, []);

      // Sample data
  const promotions = [
    {
      title: "Summer Bakery Supplies Sale - 15% Off All Orders",
      discount: "12%",
      daysLeft: 5,
      badgeText: "Featured",
      imageUrl: "https://example.com/image.jpg"
    },
    {
      title: "Summer Bakery Supplies Sale - 15% Off All Orders", 
      discount: "12%",
      daysLeft: 5,
      badgeText: "Featured",
      imageUrl: "https://example.com/image.jpg"
    },
    {
      title: "Summer Bakery Supplies Sale - 15% Off All Orders",
      discount: "12%", 
      daysLeft: 5,
      badgeText: "Featured",
      imageUrl: "https://example.com/image.jpg"
    },
    {
      title: "Summer Bakery Supplies Sale - 15% Off All Orders",
      discount: "12%",
      daysLeft: 5,
      badgeText: "Featured",
      imageUrl: "https://example.com/image.jpg"
    },
    {
      title: "Summer Bakery Supplies Sale - 15% Off All Orders",
      discount: "12%",
      daysLeft: 5,
      badgeText: "Featured",
      imageUrl: "https://example.com/image.jpg"
    },
    {
      title: "Summer Bakery Supplies Sale - 15% Off All Orders",
      discount: "12%",
      daysLeft: 5,
      badgeText: "Featured",
      imageUrl: "https://example.com/image.jpg"
    },
    {
      title: "Summer Bakery Supplies Sale - 15% Off All Orders",
      discount: "12%",
      daysLeft: 5,
      badgeText: "Featured",
      imageUrl: "https://example.com/image.jpg"
    },
    {
      title: "Summer Bakery Supplies Sale - 15% Off All Orders",
      discount: "12%",
      daysLeft: 5,
      badgeText: "Featured",
      imageUrl: "https://example.com/image.jpg"
    }
  ];

  const bonusTiers = [
    { sales: 10, reward: "EGP 250" },
    { sales: 25, reward: "EGP 750" },
    { sales: 50, reward: "EGP 1150" }
  ];

    return (
        <div className="flex h-screen bg-gray-50 overflow-hidden w-full">
            {/* Sidebar */}
            <Sidebar
                sidebarOpen={sidebarOpen}
                setSidebarOpen={setSidebarOpen}
            />

            {/* Main Content */}
            <div className="flex-1 flex flex-col min-w-0">
                {/* Header */}
                <Header setSidebarOpen={setSidebarOpen} title={t("dashboard:promotionsAndOffers.headerTitle")}/>

                {/* Main Content Area - Scrollable */}
                <div className="flex-1 overflow-y-auto overflow-x-hidden">
                    <div className="min-h-full flex flex-col">
                        {/* Main Content */}
                        <SubHeader/>

                        {/* Featured Promotions */}
                        <FeaturedPromotions promotions={promotions} onViewDetails={(promo) => { setSelectedPromo(promo); setDetailSidebarOpen(true); }}/>

                        {/* Bonus Tier Rewards */}
                        <BonusTierRewards tiers={bonusTiers} currentSales={dashboardInfo.new_clients}/>

                        {/* Generate Tracking Link */}
                        <GenerateTrackingLink/>

                        {/* Orders Table */}
                        <OrdersTable/>

                        {/* Dark overlay behind detail sidebar */}
                        {detailSidebarOpen && (
                          <div
                            className="fixed inset-0 bg-black/50 z-40"
                            onClick={() => setDetailSidebarOpen(false)}
                          />
                        )}

                        {/* Promotion Detail Sidebar */}
                        <div className={`fixed top-0 right-0 h-full w-80 bg-white shadow-2xl z-50 transform transition-transform duration-300 ${detailSidebarOpen ? 'translate-x-0' : 'translate-x-full'}`}>
                          <div className="flex justify-end p-4">
                            <button onClick={() => setDetailSidebarOpen(false)} className="text-gray-500 hover:text-gray-700">&times;</button>
                          </div>
                          {selectedPromo && (
                            <div className="p-4 overflow-y-auto">
                              <img src={selectedPromo.imageUrl} alt={selectedPromo.title} className="w-full h-40 object-cover rounded-lg mb-4"/>
                              <div className="flex items-center gap-2 mb-2">
                                <span className="bg-gray-200 text-sm px-2 py-1 rounded">{selectedPromo.badgeText}</span>
                                <span className="text-green-500 text-sm">Active</span>
                              </div>
                              <h3 className="text-lg font-semibold mb-2">{selectedPromo.title}</h3>
                              <div className="text-sm text-gray-600 mb-4">Earn {selectedPromo.discount} per sale</div>
                              {/* Additional details here */}
                            </div>
                          )}
                        </div>
                    </div>
                </div>
            </div>

            {/* Mobile Sidebar Overlay */}
            {sidebarOpen && (
                <div
                    className="fixed inset-0 bg-black/50 z-40 lg:hidden"
                    onClick={() => setSidebarOpen(false)}
                />
            )}
        </div>
    );
};
