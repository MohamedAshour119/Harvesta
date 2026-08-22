import React, { useState } from 'react';
import MaxWidthWrapper from '../../../components/MaxWidthWrapper';
import { useTranslation } from 'react-i18next';

// FAQ item type
type FAQItemProps = {
  question: string;
  answer: string;
  isOpen?: boolean;
  toggleOpen: () => void;
};

// FAQ item component
const FAQItem: React.FC<FAQItemProps> = ({ question, answer, isOpen, toggleOpen }) => {
  return (
    <div 
      className={`rounded-xl mb-4 overflow-hidden border bg-white dark:bg-[#151D2C] transition-all duration-300 ${
        isOpen 
          ? 'border-[#B12C2E] shadow-[0_0_12px_rgba(177,44,46,0.2)] ring-1 ring-[#B12C2E]/20' 
          : 'border-gray-200 dark:border-slate-700/60 hover:border-gray-300 dark:hover:border-slate-600'
      }`}
    >
      <div 
        className="flex justify-between items-center p-5 cursor-pointer select-none"
        onClick={toggleOpen}
      >
        <h3 className={`font-semibold text-sm sm:text-[16px] transition-colors duration-300 ${isOpen ? 'text-[#B12C2E] dark:text-[#E03E41]' : 'text-gray-850 dark:text-slate-100'}`}>
          {question}
        </h3>
        <button 
          className={`flex-shrink-0 ml-4 transition-transform duration-300 ${isOpen ? 'text-[#B12C2E] dark:text-[#E03E41] rotate-90' : 'text-gray-400 dark:text-slate-500 hover:text-gray-600 dark:hover:text-slate-300'}`}
          aria-label={isOpen ? "Close FAQ" : "Open FAQ"}
        >
          {isOpen ? (
            <svg xmlns="http://www.w3.org/2000/svg" className="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
            </svg>
          ) : (
            <svg xmlns="http://www.w3.org/2000/svg" className="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
            </svg>
          )}
        </button>
      </div>
      
      <div 
        className={`grid transition-all duration-300 ease-in-out ${
          isOpen ? 'grid-rows-[1fr] opacity-100' : 'grid-rows-[0fr] opacity-0'
        }`}
      >
        <div className="overflow-hidden">
          <div className="p-5 pt-0 text-[15px] leading-relaxed text-gray-600 dark:text-slate-300">
            <div className="border-t border-gray-100 dark:border-slate-800 pt-4">
              {answer}
            </div>
          </div>
        </div>
      </div>
    </div>
  );
};

const FAQsSection: React.FC = () => {
  const { t } = useTranslation('products');
  
  const faqKeys = [
    'businessRequirement',
    'minimumOrder',
    'productVariants',
    'shippingTime',
    'paymentMethods',
    'customProducts'
  ];

  const [openFAQId, setOpenFAQId] = useState<string>(faqKeys[0]);

  return (
    <div className="py-12 md:py-16">
      <MaxWidthWrapper>
        <div className="text-center mb-8 flex flex-col items-center">
          <div className="flex items-center justify-center bg-[#B12C2E]/10 text-[#B12C2E] dark:text-[#E03E41] border border-[#B12C2E]/20 min-w-[108px] w-max px-4 h-[44px] rounded-full text-sm font-bold mb-4">
            {t('faqs.badge')}
          </div>
          <h2 className="text-2xl md:text-[32px] font-bold text-gray-900 dark:text-slate-100 mb-3">{t('faqs.title')}</h2>
          <p className="text-gray-600 dark:text-slate-400 max-w-2xl mx-auto text-base md:text-lg font-medium leading-relaxed">
            {t('faqs.subtitle')}
          </p>
        </div>

        <div className="max-w-3xl mx-auto flex flex-col gap-y-2">
          {faqKeys.map((key) => (
            <FAQItem
              key={key}
              question={t(`faqs.questions.${key}.question`)}
              answer={t(`faqs.questions.${key}.answer`)}
              isOpen={openFAQId === key}
              toggleOpen={() => setOpenFAQId(openFAQId === key ? '' : key)}
            />
          ))}
        </div>
      </MaxWidthWrapper>
    </div>
  );
};

export default FAQsSection;
