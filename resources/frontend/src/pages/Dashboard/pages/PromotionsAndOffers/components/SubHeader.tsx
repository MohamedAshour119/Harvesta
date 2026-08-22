import { FaChevronDown } from "react-icons/fa";
import { useState } from "react";
import { useTranslation } from "react-i18next";

export function SubHeader() {
    const { t } = useTranslation(["dashboard"]);
    const [openDropdown, setOpenDropdown] = useState<string | null>(null);

    const dropdowns = [
      {
        id: 'categories',
        label: 'All categories',
        options: ['All categories', 'Electronics', 'Fashion', 'Home & Garden', 'Sports', 'Books', 'Beauty']
      },
      {
        id: 'status',
        label: 'Active',
        options: ['Active', 'Inactive', 'Expired', 'Upcoming']
      },
      {
        id: 'period',
        label: 'Last 30 days',
        options: ['Last 30 days', 'Last 7 days', 'Last 90 days', 'This month', 'This year']
      }
    ];
  
    const toggleDropdown = (dropdownId: string) => {
      setOpenDropdown(openDropdown === dropdownId ? null : dropdownId);
    };
  
    const selectOption = (dropdownId: string, option: string) => {
      // Update the selected option logic here
      setOpenDropdown(null);
    };
    
    return (
        <div className="bg-white p-6 rounded-lg shadow-sm">
          <h1 className="text-2xl font-semibold text-gray-900 mb-6">{t("dashboard:promotionsAndOffers.headerTitle")}</h1>
          
          <div className="flex gap-4 items-center flex-wrap">
            {dropdowns.map((dropdown) => (
              <div key={dropdown.id} className="relative">
                <button
                  onClick={() => toggleDropdown(dropdown.id)}
                  className="flex items-center gap-2 cursor-pointer px-4 py-2 bg-gray-50 hover:bg-gray-100 border border-gray-200 rounded-md text-sm font-medium text-gray-700 transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-[#B12C2E]/20 focus:border-[#B12C2E]"
                >
                  <span>{dropdown.label}</span>
                  <FaChevronDown
                    className={`w-4 h-4 transition-transform duration-200 ${
                      openDropdown === dropdown.id ? 'rotate-180' : ''
                    }`}
                  />
                </button>
                
                {/* Dropdown Menu */}
                <div
                  className={`absolute top-full left-0 mt-1 w-48 bg-white border border-gray-200 rounded-md shadow-lg z-10 transition-all duration-200 origin-top ${
                    openDropdown === dropdown.id
                      ? 'opacity-100 scale-y-100 translate-y-0'
                      : 'opacity-0 scale-y-95 -translate-y-2 pointer-events-none'
                  }`}
                >
                  <div className="py-1">
                    {dropdown.options.map((option, index) => (
                      <button
                        key={index}
                        onClick={() => selectOption(dropdown.id, option)}
                        className="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-red-50 hover:text-[#B12C2E] transition-colors duration-150 cursor-pointer"
                      >
                        {option}
                      </button>
                    ))}
                  </div>
                </div>
              </div>
            ))}
          </div>
          
          {/* Click outside to close dropdowns */}
          {openDropdown && (
            <div
              className="fixed inset-0 z-0"
              onClick={() => setOpenDropdown(null)}
            />
          )}
        </div>
      );
}
