import React, { useState } from 'react';
import { useTranslation } from 'react-i18next';
import type { AgentDashboardInfoCommissionAndBonus, AgentOrder, Paginated } from '../../../../../types';
import * as XLSX from 'xlsx';
import ApiClient from '../../../../../ApiClient';

interface MonthlySummaryCardProps {
    data: AgentDashboardInfoCommissionAndBonus;
}

const MonthlySummaryCard = ({ data }: MonthlySummaryCardProps) => {
    const { t, i18n } = useTranslation(["dashboard"]);
    const isRTL = i18n.language === "ar";
    const [isExporting, setIsExporting] = useState(false);

    const handleExport = async () => {
        setIsExporting(true);
        try {
            let allOrders: AgentOrder[] = [];
            let currentPage = 1;
            let lastPage = 1;

            do {
                const response = await ApiClient.agent.getRecentOrders(100, currentPage);
                const paginatedData = response.data.data as Paginated<AgentOrder>;
                allOrders = allOrders.concat(paginatedData.data);
                lastPage = paginatedData.last_page;
                currentPage++;
            } while (currentPage <= lastPage);

            const headers = [
                t('dashboard:ordersTable.orderId'),
                t('dashboard:ordersTable.customer'),
                t('dashboard:ordersTable.date'),
                t('dashboard:ordersTable.time'),
                t('dashboard:ordersTable.commissionText'),
                t('dashboard:ordersTable.status'),
            ];

            const rows = allOrders.map(order => {
                return [
                    { v: order.id, t: 's'},
                    { v: order.customer, t: isRTL ? 'n' : 's' },
                    { v: order.date, t: isRTL ? 'n' : 's' },
                    { v: order.time, t: isRTL ? 'n' : 's' },
                    { v: order.commission, t: isRTL ? 'n' : 's' },
                    { v: t(`dashboard:ordersTable.statusOptions.${order.status}`), t: isRTL ? 'n' : 's' }
                ];
            });

            const ws = XLSX.utils.aoa_to_sheet([headers, ...rows]);
            ws['!cols'] = [
                { wch: 15 },
                { wch: 30 },
                { wch: 15 },
                { wch: 15 },
                { wch: 15 },
                { wch: 20 },
            ];

            const wb = XLSX.utils.book_new();
            XLSX.utils.book_append_sheet(wb, ws, 'All Orders');
            const wbout = XLSX.write(wb, { bookType: 'xlsx', type: 'array' });
            const blob = new Blob([wbout], { type: 'application/octet-stream' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = 'all_orders.xlsx';
            a.click();
            URL.revokeObjectURL(url);
        } catch (error) {
            console.error("Error exporting orders:", error);
        } finally {
            setIsExporting(false);
        }
    };

    return (
    <div className="bg-white dark:bg-[#151D2C] border border-gray-100 dark:border-slate-800 rounded-2xl shadow-sm p-6 w-80 max-w-sm transition-colors">
      {/* Header */}
      <h2 className="text-lg sm:text-xl font-bold text-gray-900 dark:text-slate-100 mb-6">{t('dashboard:commissionAndBonus.monthlySummary')}</h2>
      
      {/* Stats Grid */}
      <div className="space-y-4 mb-6">
        <div className="flex justify-between items-center text-sm">
          <span className="text-gray-600 dark:text-slate-400">{t('dashboard:commissionAndBonus.totalOrders')}:</span>
          <span className="text-gray-900 dark:text-white font-bold">{data.total_orders_this_month}</span>
        </div>
        
        <div className="flex justify-between items-center text-sm">
          <span className="text-gray-600 dark:text-slate-400">{t('dashboard:commissionAndBonus.averageCommissions')}:</span>
          <span className="text-gray-900 dark:text-white font-bold">{data.average_commission_this_month?.toFixed(2)} {t('dashboard:values.egp')}</span>
        </div>
        
        <div className="flex justify-between items-center text-sm">
          <span className="text-gray-600 dark:text-slate-400">{t('dashboard:commissionAndBonus.highestCommission')}:</span>
          <span className="text-gray-900 dark:text-white font-bold">{data.highest_commission_this_month?.toFixed(2)} {t('dashboard:values.egp')}</span>
        </div>
        
        <div className="flex justify-between items-center text-sm">
          <span className="text-gray-600 dark:text-slate-400">{t('dashboard:commissionAndBonus.totalSalesValue')}:</span>
          <span className="text-gray-900 dark:text-white font-bold">{data.total_sales_this_month?.toFixed(2) || 0} {t('dashboard:values.egp')}</span>
        </div>
      </div>
      
      {/* Export Button */}
      <button 
        onClick={handleExport}
        disabled={isExporting}
        className="w-full bg-[#B12C2E] hover:bg-[#972426] dark:bg-[#B12C2E] dark:hover:bg-[#8F2325] text-white py-2.5 rounded-xl font-bold text-sm cursor-pointer transition-all shadow-sm disabled:opacity-50"
      >
        {isExporting ? t('dashboard:commissionAndBonus.downloading') : t('dashboard:commissionAndBonus.exportCSV')}
      </button>
    </div>
  );
};

export default MonthlySummaryCard;
