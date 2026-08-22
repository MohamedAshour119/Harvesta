import React from 'react';
import MaxWidthWrapper from '../../../components/MaxWidthWrapper';
import { useTranslation } from 'react-i18next';
import type { RootState } from '../../../store/store';
import { useSelector } from 'react-redux';
import { Link } from 'react-router-dom';

const HowItWorksSection: React.FC = () => {
  const { t, i18n } = useTranslation('products');
  const isRTL = i18n.language === 'ar';
  const isAuth = useSelector((state: RootState) => state.auth.isAuth);
  
  return (
    <div className='bg-[#222222] hidden tablet-small:block w-full min-w-[100vw] relative left-[50%] right-[50%] mx-[-50vw]'>

    <MaxWidthWrapper className='!lg:py-16 px-4 sm:px-6 lg:px-8'>
        <section className="hidden lg:block text-white px-4 sm:px-6 lg:px-8 lg:py-16 relative h-[700px]">
            <div className="container mx-auto px-4 flex flex-col lg:flex-row justify-between items-start lg:items-center mb-8">
                <div>
                <h2 className="text-3xl md:text-4xl font-medium mb-2">{t('howItWorks.title')}</h2>
                <p className="text-gray-300">{t('howItWorks.subtitle')}</p>
                </div>
                <Link to={isAuth ? '/products' : '/login'} className="mt-6 lg:mt-0 bg-white text-black font-medium py-3 px-8 rounded-md hover:bg-gray-100 transition-colors">
                {t('howItWorks.startNow')}
                </Link>
            </div>
            
            <div className="relative mt-12 pb-8 px-4">
                {/* Step 1 */}
                <div className="ms-0 mb-20 md:mb-0 md:ms-0 md:absolute md:top-0 md:start-4 w-full md:w-1/4 z-10">
                <div className="bg-[#2A2A2A] p-6 rounded-lg">
                    <h3 className="font-medium text-lg">
                    {t('howItWorks.step1')}
                    </h3>
                </div>
                </div>
                
                {/* Connecting Line 1 */}
                <div className={`hidden md:block absolute top-[60px] start-[3%] w-[25%] h-[120px] border-s-2 border-b-2 border-dashed ${isRTL ? 'rounded-br-[40px]' : 'rounded-bl-[40px]'} border-[#FFFFFF66] z-0`}></div>
                
                {/* Step 2 */}
                <div className="mb-20 md:mb-0 md:absolute md:top-[120px] md:start-[25%] w-full md:w-1/4 z-10">
                <div className="bg-[#2A2A2A] p-6 rounded-lg">
                    <h3 className="font-medium text-lg">
                    {t('howItWorks.step2')}
                    </h3>
                </div>
                </div>
                
                {/* Connecting Line 2 */}
                <div className={`hidden md:block absolute top-[180px] start-[26.5%] w-[25%] h-[120px] border-s-2 border-b-2 border-dashed ${isRTL ? 'rounded-br-[40px]' : 'rounded-bl-[40px]'} border-[#FFFFFF66] z-0`}></div>
                
                {/* Step 3 */}
                <div className="mb-20 md:mb-0 md:absolute md:top-[240px] md:start-[49%] w-full md:w-1/4 z-10">
                <div className="bg-[#2A2A2A] p-6 rounded-lg">
                    <h3 className="font-medium text-lg">
                    {t('howItWorks.step3')}
                    </h3>
                </div>
                </div>
                
                {/* Connecting Line 3 */}
                <div className={`hidden md:block absolute top-[300px] start-[51.5%] w-[25%] h-[120px] border-s-2 border-b-2 border-dashed ${isRTL ? 'rounded-br-[40px]' : 'rounded-bl-[40px]'} border-[#FFFFFF66] z-0`}></div>
                
                {/* Step 4 */}
                <div className="md:absolute md:top-[360px] md:start-[72.5%] w-full md:w-1/4 z-10">
                <div className="bg-[#2A2A2A] p-6 rounded-lg">
                    <h3 className="font-medium text-lg">
                    {t('howItWorks.step4')}
                    </h3>
                </div>
                </div>
                
                {/* Mobile Spacer */}
                <div className="md:hidden h-[350px]"></div>
            </div>
        </section>
    </MaxWidthWrapper>{/* Mobile Section */}
    <section className="hidden tablet-small:block lg:hidden text-white py-20 px-4">
        <div className="flex flex-col justify-between items-start mb-8">
            <div>
                <h2 className="text-[32px] font-[600] mb-2">{t('howItWorks.title')}</h2>
                <p className="text-gray-300 text-[22px]">{t('howItWorks.subtitle')}</p>
            </div>
            <button className="mt-6 bg-white text-black font-medium py-3 px-8 rounded-md hover:bg-gray-100 transition-colors">
                {t('howItWorks.startNow')}
            </button>
        </div>
        
        <div className="grid grid-cols-1 tablet-small:grid-cols-2 gap-5 mt-8">
            {/* Step 1 */}
            <div className="bg-[#2A2A2A] p-6 rounded-lg">
                <h3 className="font-medium text-lg">
                    {t('howItWorks.step1')}
                </h3>
            </div>
            
            {/* Step 2 */}
            <div className="bg-[#2A2A2A] p-6 rounded-lg">
                <h3 className="font-medium text-lg">
                    {t('howItWorks.step2')}
                </h3>
            </div>
            
            {/* Step 3 */}
            <div className="bg-[#2A2A2A] p-6 rounded-lg">
                <h3 className="font-medium text-lg">
                    {t('howItWorks.step3')}
                </h3>
            </div>
            
            {/* Step 4 */}
            <div className="bg-[#2A2A2A] p-6 rounded-lg">
                <h3 className="font-medium text-lg">
                    {t('howItWorks.step4')}
                </h3>
            </div>
        </div>
    </section>
    </div>
  );
};

export default HowItWorksSection;
