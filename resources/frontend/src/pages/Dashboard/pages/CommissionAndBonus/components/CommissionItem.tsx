import { useTranslation } from "react-i18next";

interface CommissionItemProps {
    orderNumber: string;
    userName: string;
    amount: string;
    date: string;
}

export function CommissionItem(props: CommissionItemProps) {
    const { orderNumber, userName, amount, date } = props;
    const { t } = useTranslation(["dashboard"]);
    
    return (
    <div className="flex justify-between items-center py-3.5 border-b border-gray-100 dark:border-slate-800/80 last:border-b-0">
        <div className="flex flex-col">
          <span className="text-sm font-bold text-gray-900 dark:text-slate-100">Order #{orderNumber}</span>
          <span className="text-xs text-gray-500 dark:text-slate-400 mt-0.5">{userName}</span>
        </div>
        <div className="flex flex-col items-end">
          <span className="text-base font-extrabold text-[#B12C2E] dark:text-[#E03E41]">{amount} {t("dashboard:values.egp")}</span>
          <span className="text-xs text-gray-400 dark:text-slate-500 mt-0.5">{date}</span>
        </div>
      </div>
    );
}
