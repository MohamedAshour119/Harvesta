import { useTranslation } from 'react-i18next';

interface MetricsCardsSectionProps {
  data: {
    total_commission_this_month: number;
    pending_commissions: number;
    total_commission_last_month: number;
    percent_change: number;
    top_commission_source: {
      name: string;
      commission: number;
    };
  };
  loading?: boolean;
}

export default function MetricsCardsSection({ data, loading }: MetricsCardsSectionProps) {
  const { t, i18n } = useTranslation(['dashboard']);

  if (loading) {
    return <div>Loading...</div>;
  }

  const totalCommissionThisMonth = data.total_commission_this_month ?? 0;
  const pendingCommissions = data.pending_commissions ?? 0;
  const totalCommissionLastMonth = data.total_commission_last_month ?? 0;
  const percentChange = data.percent_change ?? 0;

  const topSourceRaw = data.top_commission_source || { name: '', commission: 0 };
  let topSourceName = '';
  try {
    const nameObj = JSON.parse(topSourceRaw.name);
    topSourceName = nameObj[i18n.language] || nameObj.en || '';
  } catch {
    topSourceName = topSourceRaw.name;
  }
  const topSourceCommission = topSourceRaw.commission ?? 0;
  const topSourcePercent =
    totalCommissionThisMonth > 0
      ? (topSourceCommission / totalCommissionThisMonth) * 100
      : 0;

  const metrics = [
    {
      title: t('dashboard:metrics.totalCommissionMonth'),
      value: `${totalCommissionThisMonth.toFixed(2)} ${t('dashboard:values.egp')}`,
      change: `${percentChange.toFixed(2)}% ${t('dashboard:metrics.fromLastMonth')}`,
      changeColor: percentChange >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400',
      dotColor: 'bg-teal-500',
    },
    {
      title: t('dashboard:metrics.pendingCommissions'),
      value: `${pendingCommissions.toFixed(2)} ${t('dashboard:values.egp')}`,
      change: t('dashboard:metrics.waitingDelivery'),
      changeColor: 'text-gray-500 dark:text-slate-400',
      dotColor: 'bg-blue-500',
    },
    {
      title: t('dashboard:metrics.commissionLastMonth'),
      value: `${totalCommissionLastMonth.toFixed(2)} ${t('dashboard:values.egp')}`,
      changeColor: 'text-gray-500 dark:text-slate-400',
      dotColor: 'bg-[#B12C2E]',
    },
    {
      title: t('dashboard:metrics.topCommissionSources'),
      value: topSourceName || '--',
      change: `${topSourceCommission.toFixed(2)} ${t('dashboard:values.egp')} (${topSourcePercent.toFixed(1)}%)`,
      changeColor: 'text-gray-500 dark:text-slate-400',
      dotColor: 'bg-purple-500',
    },
  ];

  return (
    <div className="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-3 sm:gap-6 px-3 sm:px-6 mt-4 sm:mt-6">
      {metrics.map((metric, index) => (
        <div key={index} className="bg-white dark:bg-[#151D2C] border border-gray-100 dark:border-slate-800 rounded-2xl p-4 sm:p-6 shadow-sm transition-colors">
          <div className="flex items-center justify-between mb-2">
            <span className="text-gray-500 dark:text-slate-400 text-xs sm:text-sm line-clamp-2 font-medium">{metric.title}</span>
            <div className={`w-3 h-3 ${metric.dotColor} rounded-full flex-shrink-0 shadow-sm`}></div>
          </div>
          <div className="text-lg sm:text-2xl font-extrabold text-gray-900 dark:text-white mb-1">{metric.value}</div>
          <div className={`${metric.changeColor} text-xs sm:text-sm font-semibold`}>{metric.change}</div>
        </div>
      ))}
    </div>
  );
}
