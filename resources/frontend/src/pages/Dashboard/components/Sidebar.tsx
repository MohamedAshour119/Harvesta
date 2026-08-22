import { Link } from "react-router-dom";
import { useTranslation } from "react-i18next";
import { NavLink } from "react-router-dom";

interface SidebarProps {
  sidebarOpen: boolean;
  setSidebarOpen: (open: boolean) => void;
}

export const Sidebar = ({ sidebarOpen, setSidebarOpen }: SidebarProps) => {
  const { t } = useTranslation(['common', 'dashboard']);
  const lang = useTranslation().i18n.language;

  const menuItems = [
    { icon: 'home', label: t('dashboard:sidebar.overview'), to: '/dashboard' },
    { icon: 'receipt', label: t('dashboard:sidebar.fieldSales', 'Field Sales'), to: '/dashboard/field-sales' },
    { icon: 'users', label: t('dashboard:sidebar.commissionBonus'), to: '/dashboard/commission-bonus' },
  ];

  const IconComponent = ({ icon }: { icon: string }) => {
    const iconProps = { className: `${lang === 'en' ? 'mr-3' : 'ml-3'} w-5 h-5 flex-shrink-0` };

    switch (icon) {
      case 'home':
        return <svg {...iconProps} fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" /></svg>;
      case 'users':
        return <svg {...iconProps} fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z" /></svg>;
      case 'receipt':
        return <svg {...iconProps} fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 14l2 2 4-4m-9 8V5a2 2 0 012-2h8a2 2 0 012 2v15l-3-2-3 2-3-2-3 2z" /></svg>;
      case 'shopping-bag':
        return <svg {...iconProps} fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M16 11V7a4 4 0 00-8 0v4M5 9h14l-1 12H6L5 9z" /></svg>;
      case 'package':
        return <svg {...iconProps} fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" /></svg>;
      case 'calendar':
        return <svg {...iconProps} fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>;
      default:
        return null;
    }
  };

  return (
    <>
      {/* Desktop Sidebar */}
      <div className="hidden lg:flex w-64 bg-white dark:bg-[#151D2C] shadow-sm flex-col border-r border-gray-200 dark:border-slate-800 transition-colors">
        {/* Logo */}
        <div className="p-4 sm:p-6">
          <Link to="/" className="flex items-center">
            <img src="/logo.png" alt="Logo" className="w-[90px]" />
          </Link>
        </div>
        <nav className="mt-6 flex-1 space-y-1">
          {menuItems.map((item, index) => (
            <NavLink
              key={index}
              to={item.to}
              end={item.to === '/dashboard'}
              className={({ isActive }) =>
                `px-4 sm:px-6 py-3.5 flex items-center cursor-pointer transition-colors font-medium text-sm sm:text-base ${isActive
                  ? `text-[#B12C2E] dark:text-[#E03E41] bg-red-50 dark:bg-red-950/40 ${lang === "en" ? 'border-l-4' : 'border-r-4'} border-[#B12C2E] dark:border-[#E03E41]`
                  : 'text-gray-600 dark:text-slate-400 hover:bg-gray-50 dark:hover:bg-slate-800/60 hover:text-gray-900 dark:hover:text-slate-200'
                }`
              }
            >
              <IconComponent icon={item.icon} />
              <span>{item.label}</span>
            </NavLink>
          ))}
        </nav>
      </div>

      {/* Mobile Sidebar */}
      <div className={`lg:hidden fixed inset-y-0 ${lang === "en" ? 'left-0' : 'right-0'} z-50 w-64 bg-white dark:bg-[#151D2C] shadow-2xl border-r border-gray-200 dark:border-slate-800 transform transition-transform duration-300 ease-in-out ${sidebarOpen ? 'translate-x-0' : `${lang === "en" ? '-translate-x-full' : 'translate-x-full'}`
        }`}>
        <div className="p-4 border-b border-gray-100 dark:border-slate-800">
          <div className="flex items-center justify-between">
            <Link to="/" className="flex items-center">
              <h1 className="text-2xl font-extrabold text-[#B12C2E] dark:text-[#E03E41]">
                {t('common:title_part_1')}
                <span className="text-black dark:text-white">{t('common:title_part_2')}</span>
              </h1>
            </Link>
            <button
              onClick={() => setSidebarOpen(false)}
              className="p-2 text-gray-500 dark:text-slate-400 hover:text-gray-700 dark:hover:text-slate-200"
            >
              <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>
        </div>
        <nav className="mt-4 space-y-1">
          {menuItems.map((item, index) => (
            <NavLink
              key={index}
              to={item.to}
              end={item.to === '/dashboard'}
              className={({ isActive }) =>
                `px-4 sm:px-6 py-3.5 flex items-center cursor-pointer transition-colors font-medium text-sm ${isActive
                  ? `text-[#B12C2E] dark:text-[#E03E41] bg-red-50 dark:bg-red-950/40 ${lang === "en" ? 'border-l-4' : 'border-r-4'} border-[#B12C2E] dark:border-[#E03E41]`
                  : 'text-gray-600 dark:text-slate-400 hover:bg-gray-50 dark:hover:bg-slate-800/60'
                }`
              }
              onClick={() => setSidebarOpen(false)}
            >
              <IconComponent icon={item.icon} />
              <span>{item.label}</span>
            </NavLink>
          ))}
        </nav>
      </div>
    </>
  );
};
