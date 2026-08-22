import React, { useState } from 'react';
import { useTranslation } from 'react-i18next';

import ContactFormModal from './ContactFormModal';

// Placeholder images - replace with your actual image paths
const mainImage = '/assets/images/about-us/studio.png';
const businessImage = '/assets/images/about-us/business.png';
const happyCustomersImage = '/assets/images/about-us/customers.png';

const AboutUsSection: React.FC = () => {
  const { t } = useTranslation('home');
  const [showForm, setShowForm] = useState(false);

  return (
    <>
      <ContactFormModal show={showForm} onClose={() => setShowForm(false)} />

      {/* Main Section */}
      <section className="container mx-auto px-4 py-16 font-sans" dir={t('dir')}>
        <div className="grid grid-cols-1 lg:grid-cols-2 gap-8 lg:gap-16 items-start">
          {/* Right Column: Images and Stats */}
          <div className="flex flex-col space-y-6 order-1 lg:order-2">
            {/* Main Image */}
            <div className="rounded-lg overflow-hidden shadow-lg">
              <img
                src={mainImage}
                alt={t('aboutUs.AboutUs')}
                className="w-full h-[325px] object-cover"
                onError={(e) => { e.currentTarget.src = 'https://placehold.co/600x400/FFF0D8/2C2C2C?text=Image+Error'; }}
              />
            </div>

            {/* Business and Happy Customers Cards */}
            <div className="grid grid-cols-2 gap-6">
              <div className="rounded-lg overflow-hidden shadow-lg">
                <img
                  src={businessImage}
                  alt={t('aboutUs.Business')}
                  className="w-full h-[155px] object-cover"
                  onError={(e) => { e.currentTarget.src = 'https://placehold.co/300x200/D8546C/FFFFFF?text=Image+Error'; }}
                />
              </div>
              <div className="relative rounded-lg overflow-hidden shadow-lg">
                <img
                  src={happyCustomersImage}
                  alt={t('aboutUs.500HappyCustomers')}
                  className="w-full h-[155px] object-cover"
                  onError={(e) => { e.currentTarget.src = 'https://placehold.co/300x200/FDAC29/FFFFFF?text=Image+Error'; }}
                />
                <div className="absolute inset-0 flex flex-col items-center justify-center bg-black/10 text-white p-2">
                  <p className="text-3xl font-bold">500</p>
                  <p className="text-lg max-mobile-large:text-center">{t('aboutUs.500HappyCustomers').replace('500', '')}</p>
                </div>
              </div>
            </div>
          </div>

          {/* Left Column: About Us & Vision Text */}
          <div className="flex flex-col space-y-8 order-2 lg:order-1">
            <div>
              <h2 className="text-3xl mobile-large:text-4xl font-bold text-gray-900 dark:text-slate-100 mb-4">{t('aboutUs.AboutUs')}</h2>
              <p className="text-gray-600 dark:text-slate-300 leading-relaxed mb-4">
                {t('aboutUs.LoremipsumdolorsitametconsecteturTinciduntnonaliquamtristiquefacilisiInataliquamconsequatorcitinciduntamet')}
              </p>
              <p className="text-gray-600 dark:text-slate-300 leading-relaxed">
                {t('aboutUs.LoremipsumdolorsitametconsecteturTinciduntnonaliquamtristiquefacilisiInataliquamconsequatorcitinciduntametNullanullautultricesvitaeilibquamdiamsitMaurisliberofeugiatdiamsuspendisseTortortinciduntmaecenasnatoqueimperdietDuiegestasnetusipsumsedscelerisqueligulaquamclassparturient')}
              </p>
            </div>

            <div>
              <h3 className="text-2xl mobile-large:text-3xl font-bold text-gray-900 dark:text-slate-100 mb-4">{t('aboutUs.Vision')}</h3>
              <p className="text-gray-600 dark:text-slate-300 leading-relaxed text-start">
                {t('aboutUs.ReliableB2Bsourcingwithfastlogisticsandbulkfriendlypricing')}
              </p>
            </div>

            {/* Enhanced Contact Us Button */}
            <button
              onClick={() => setShowForm(true)}
              className="mt-8 px-8 py-4 cursor-pointer bg-[#B12C2E] hover:bg-[#972426] dark:bg-[#B12C2E] dark:hover:bg-[#C83437] text-white font-semibold rounded-xl shadow-lg hover:shadow-xl transform hover:scale-[1.02] active:scale-[0.98] transition-all duration-200"
            >
              {t('aboutUs.ContactUs')}
            </button>
          </div>
        </div>
      </section>
    </>
  );
};

export default AboutUsSection;