import { useTranslation } from 'react-i18next';

function normalizeDateString(s: string): string {
  if (/^\d{4}-\d{2}-\d{2}$/.test(s)) return s;
  const m = s.match(/^(\d{4})-([A-Za-z]{3})-(\d{2})$/);
  if (m) {
    const months: Record<string, string> = {
      Jan: '01', Feb: '02', Mar: '03', Apr: '04', May: '05', Jun: '06',
      Jul: '07', Aug: '08', Sep: '09', Oct: '10', Nov: '11', Dec: '12',
    };
    const mon = months[m[2]];
    if (mon) return `${m[1]}-${mon}-${m[3]}`;
  }
  return s;
}

function formatDateLocalized(dateStr: string, lang: string): string {
  const normalized = normalizeDateString(dateStr);
  const d = new Date(normalized);
  if (isNaN(d.getTime())) return dateStr;
  const locale = lang === 'ar' ? 'ar-EG' : 'en-US';
  return d.toLocaleDateString(locale, { year: 'numeric', month: 'long', day: 'numeric' });
}

interface MetricsCardsSectionProps {
  data: {
    total_commission: number;
    pending_commissions: number;
    total_commission_last_month: number;
    top_commission_source: {
      name: string;
      commission: number;
    };
    bonus: {
      amount: number;
      paid_at: string;
    };
  };
  loading?: boolean;
}

export default function MetricsCardsSection({ data, loading }: MetricsCardsSectionProps) {
  const { t, i18n } = useTranslation(['dashboard']);
  const lang = useTranslation().i18n.language;

  if (loading) {
    return <div>Loading...</div>;
  }

  const totalCommissionThisMonth = data?.total_commission ?? 0;
  const pendingCommissions = data?.pending_commissions ?? 0;
  const totalCommissionLastMonth = data?.total_commission_last_month ?? 0;

  const topSourceRaw = data?.top_commission_source || { name: '', commission: 0 };
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
      title: t('dashboard:commissionAndBonus.totalCommission'),
      value: `${totalCommissionThisMonth.toFixed(2)} ${t('dashboard:values.egp')}`,
      change: ``,
      dotColor: 'bg-teal-500',
    },
    {
      title: t('dashboard:commissionAndBonus.pendingCommissions'),
      value: `${pendingCommissions.toFixed(2)} ${t('dashboard:values.egp')}`,
      change: t('dashboard:metrics.waitingDelivery'),
      changeColor: 'text-gray-500 dark:text-slate-400',
      dotColor: 'bg-blue-500',
    },
    {
      title: t('dashboard:commissionAndBonus.commissionLastMonth'),
      value: `${totalCommissionLastMonth.toFixed(2)} ${t('dashboard:values.egp')}`,
      changeColor: 'text-gray-500 dark:text-slate-400',
      dotColor: 'bg-[#B12C2E]',
    },
    {
      title: t('dashboard:commissionAndBonus.topCategory'),
      value: topSourceName || '--',
      change: `${topSourceCommission.toFixed(2)} ${t('dashboard:values.egp')} (${topSourcePercent.toFixed(1)}%)`,
      changeColor: 'text-gray-500 dark:text-slate-400',
      dotColor: 'bg-purple-500',
    },
    {
      title: t('dashboard:commissionAndBonus.BonusEarned'),
      value: `${data?.bonus?.amount?.toFixed(2) || 0} ${t('dashboard:values.egp')}`,
      change: data?.bonus?.paid_at
        ? t('dashboard:metrics.paidOnDate', { date: formatDateLocalized(data.bonus.paid_at, lang) })
        : t('dashboard:metrics.notPaidYet'),
      changeColor: 'text-gray-500 dark:text-slate-400',
      dotColor: 'bg-green-500',
    },
  ];

  return (
    <div className="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-5 gap-3 sm:gap-6 px-3 sm:px-6 mt-4 sm:mt-6">
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
