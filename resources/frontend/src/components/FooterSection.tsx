import { useState, useEffect } from 'react';
import { Link } from 'react-router-dom';
import { useTranslation } from 'react-i18next';
import i18n, { setDocumentDirection } from '../i18n';
import MaxWidthWrapper from './MaxWidthWrapper';
import ApiClient from '../ApiClient';
import type { IIndustrialCategory } from '../types';
import { triggerLanguageSwitch } from '../utils/languageSwitcher';

interface FooterProps {
  maxWidthWrapperClassName?: string;
}

const FooterSection: React.FC<FooterProps> = ({ maxWidthWrapperClassName }) => {
  const { t } = useTranslation(['common']);
  const [dbCategories, setDbCategories] = useState<IIndustrialCategory[]>([]);

  useEffect(() => {
    const fetchCats = async () => {
      try {
        const response = await ApiClient.industrialCategory.getIndustrialCategories();
        setDbCategories(response.data.data);
      } catch (err) {
        console.error("error fetching footer cats", err);
      }
    };
    fetchCats();
  }, []);

  // Split into columns if we have data
  const columnCount = 3;
  const itemsPerColumn = Math.ceil(dbCategories.length / columnCount);
  const categoriesColumns = Array.from({ length: columnCount }, (_, i) =>
    dbCategories.slice(i * itemsPerColumn, (i + 1) * itemsPerColumn)
  );

  const notRefreshInTheseRoutes = [''];

  // Language switching function - trigger luxury modal overlay and reload
  const changeLanguage = (language: string) => {
    triggerLanguageSwitch(language);
  };

  const currentLanguage = i18n.language || 'ar';

  return (
    <footer className="w-full bg-white dark:bg-[#070A10] pt-12 pb-8 border-t border-gray-200 dark:border-slate-800/80 transition-colors duration-300">
      <MaxWidthWrapper className={maxWidthWrapperClassName}>
        {/* Logo */}
        <div className="col-span-1">
          <a href="/" className="cursor-pointer inline-block">
            <img src="/logo.svg" alt="Harvesta" className="h-16 md:h-20 w-auto max-w-[260px] md:max-w-[300px] object-contain drop-shadow-md dark:brightness-110 transition-transform duration-200 hover:scale-[1.02]" />
          </a>
        </div>
        {/* Main content grid for top section */}
        <div className="flex flex-col-reverse max-tablet:items-center tablet:grid tablet:grid-cols-[3fr_1fr] lg:grid-cols-[1fr_1.5fr] gap-8 my-8">
          {/* Categories Section */}
          <div className='w-full desktop-very-small:w-max'>
            <h3 className="text-gray-900 dark:text-slate-100 text-[18px] font-bold mb-4">{t('common:footer.categories')}</h3>
            <div className="grid grid-cols-2 lg:grid-cols-3 gap-y-2.5 gap-x-6 w-fit">
              {categoriesColumns.map((column, colIndex) => (
                <div key={colIndex} className="flex flex-col space-y-2 w-fit">
                  {column.map((category, itemIndex) => (
                    <Link
                      key={itemIndex}
                      to={`/all-products?category=${category.slug}`}
                      className="text-gray-600 dark:text-slate-400 text-[14px] w-fit hover:text-[#B12C2E] dark:hover:text-[#E03E41] transition-colors"
                    >
                      {category.name}
                    </Link>
                  ))}
                </div>
              ))}
            </div>
            {/* Mobile screen */}
            <div className="min-[1110px]:hidden flex mobile-large:flex-row flex-col gap-y-8 gap-x-6 justify-between max-[1110px]:mt-10">
              <div>
                <h3 className="text-gray-900 dark:text-slate-100 font-bold mb-4 text-[18px]">{t('common:footer.contact')}</h3>
                <ul className="space-y-2">
                  <li>
                    <a href="tel:+966500000000" className="text-gray-600 dark:text-slate-400 hover:text-gray-900 dark:hover:text-white underline text-sm transition-colors">
                      +966 50 000 0000
                    </a>
                  </li>
                  <li>
                    <a href="mailto:support@harvesta.com" className="text-gray-600 dark:text-slate-400 hover:text-gray-900 dark:hover:text-white underline text-sm transition-colors">
                      support@harvesta.com
                    </a>
                  </li>
                </ul>
              </div>

              {/* Terms, Returns, Privacy */}
              <div className="flex flex-wrap flex-1 justify-between gap-x-6 gap-y-4">
                <Link to="/terms" className="text-gray-800 dark:text-slate-200 hover:text-[#B12C2E] dark:hover:text-[#E03E41] font-semibold text-[16px] h-fit transition-colors">
                  {t('common:footer.terms')}
                </Link>
                <Link to="/returns" className="text-gray-800 dark:text-slate-200 hover:text-[#B12C2E] dark:hover:text-[#E03E41] font-semibold text-[16px] h-fit transition-colors">
                  {t('common:footer.returns')}
                </Link>
                <Link to="/privacy" className="text-gray-800 dark:text-slate-200 hover:text-[#B12C2E] dark:hover:text-[#E03E41] font-semibold text-[16px] h-fit transition-colors">
                  {t('common:footer.privacy')}
                </Link>
              </div>
            </div>
          </div>

          {/* Contact Information & Bottom Links/Language */}
          <div className="flex justify-between">
            <div className='hidden min-[1110px]:block'>
              <h3 className="text-gray-900 dark:text-slate-100 font-bold mb-4 text-[18px]">{t('common:footer.contact')}</h3>
              <ul className="space-y-2">
                <li>
                  <a href="tel:+966500000000" className="text-gray-600 dark:text-slate-400 hover:text-gray-900 dark:hover:text-white underline text-sm transition-colors">
                    +966 50 000 0000
                  </a>
                </li>
                <li>
                  <a href="mailto:support@harvesta.com" className="text-gray-600 dark:text-slate-400 hover:text-gray-900 dark:hover:text-white underline text-sm transition-colors">
                    support@harvesta.com
                  </a>
                </li>
              </ul>
            </div>

            {/* Terms, Returns, Privacy, and Language Selector */}
            <div className="flex flex-col items-center gap-y-6 flex-1 w-full">
              {/* Links row with justify-between */}
              <div className="flex justify-between w-full mb-4">
                <Link to="/terms" className="hidden min-[1110px]:block text-gray-800 dark:text-slate-200 hover:text-[#B12C2E] dark:hover:text-[#E03E41] font-semibold text-[16px] h-fit transition-colors">
                  {t('common:footer.terms')}
                </Link>
                <Link to="/returns" className="hidden min-[1110px]:block text-gray-800 dark:text-slate-200 hover:text-[#B12C2E] dark:hover:text-[#E03E41] font-semibold text-[16px] h-fit transition-colors">
                  {t('common:footer.returns')}
                </Link>
                <Link to="/privacy" className="hidden min-[1110px]:block text-gray-800 dark:text-slate-200 hover:text-[#B12C2E] dark:hover:text-[#E03E41] font-semibold text-[16px] h-fit transition-colors">
                  {t('common:footer.privacy')}
                </Link>
              </div>

              {/* Language switcher */}
              <div className="flex items-center border border-gray-300 dark:border-slate-700 bg-gray-50 dark:bg-[#151D2C] rounded-xl overflow-hidden h-fit p-1 shadow-sm">
                <button
                  className={`cursor-pointer px-4 py-2 text-sm font-semibold rounded-lg transition-all ${currentLanguage === 'en' ? 'bg-[#B12C2E] text-white shadow-sm' : 'text-gray-600 dark:text-slate-400 hover:text-gray-900 dark:hover:text-white'}`}
                  onClick={() => changeLanguage('en')}
                  aria-label={t('common:languageNames.en')}
                >
                  {t('common:languageNames.en')}
                </button>
                <button
                  className={`cursor-pointer px-4 py-2 text-sm font-semibold rounded-lg transition-all ${currentLanguage === 'ar' ? 'bg-[#B12C2E] text-white shadow-sm' : 'text-gray-600 dark:text-slate-400 hover:text-gray-900 dark:hover:text-white'}`}
                  onClick={() => changeLanguage('ar')}
                  aria-label={t('common:languageNames.ar')}
                >
                  {t('common:languageNames.ar')}
                </button>
              </div>
            </div>
          </div>
        </div>
      </MaxWidthWrapper>
    </footer>
  );
};

export default FooterSection;
