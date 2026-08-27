import React from 'react';
import MaxWidthWrapper from '../../../components/MaxWidthWrapper';
import { useTranslation } from 'react-i18next';

const PHONE_DISPLAY = '+20 101 993 5417';
const PHONE_HREF = 'tel:+201019935417';
const WHATSAPP_HREF = 'https://wa.me/201019935417';
const EMAIL_DISPLAY = 'ashourmohamed123456@gmail.com';
const EMAIL_HREF = 'mailto:ashourmohamed123456@gmail.com';

const ContactUsSection: React.FC = () => {
  const { t } = useTranslation(['products', 'common']);
  
  return (
    <div className="bg-[#191919] dark:bg-[#151D2C] text-white py-12 md:py-16 w-full min-w-[100vw] relative left-[50%] right-[50%] mx-[-50vw] border-t border-transparent dark:border-slate-800 transition-colors duration-300">
      <MaxWidthWrapper className="px-0 sm:px-6 lg:px-8">
        <h2 className="text-2xl md:text-3xl font-bold mb-3 text-center">{t('contactUs.title', { defaultValue: 'اتصل بنا' })}</h2>
        <p className="text-gray-300 text-center mb-10 max-w-lg mx-auto text-sm md:text-base">{t('contactUs.subtitle', { defaultValue: 'نحن هنا للمساعدة. تواصل معنا من خلال أي من هذه القنوات.' })}</p>
        
        <div className="flex flex-col sm:flex-row justify-center items-stretch gap-4 md:gap-6 max-w-4xl mx-auto flex-wrap">
          {/* Phone Contact */}
          <a
            href={PHONE_HREF}
            className="flex-1 min-w-[220px] max-w-sm flex flex-col items-center justify-center bg-[#282828] dark:bg-[#1C2638] border border-transparent dark:border-slate-700/60 rounded-2xl p-6 shadow-md transition-all hover:scale-105 group"
          >
            <div className="w-12 h-12 bg-[#DBEAFE] dark:bg-blue-950/60 rounded-xl flex items-center justify-center mb-4">
              <svg xmlns="http://www.w3.org/2000/svg" className="h-6 w-6 text-[#2B61D6] dark:text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
              </svg>
            </div>
            <h3 className="font-bold text-lg mb-2">{t('contactUs.phone.title', { defaultValue: 'الهاتف' })}</h3>
            <p className="text-gray-300 dark:text-slate-300 text-center underline cursor-pointer hover:text-white transition-colors group-hover:text-white">
              <span dir="ltr" style={{ direction: 'ltr', unicodeBidi: 'isolate' }} className="inline-block font-medium whitespace-nowrap">
                {PHONE_DISPLAY}
              </span>
            </p>
          </a>
          
          {/* WhatsApp Contact */}
          <a
            href={WHATSAPP_HREF}
            target="_blank"
            rel="noopener noreferrer"
            className="flex-1 min-w-[220px] max-w-sm flex flex-col items-center justify-center bg-[#282828] dark:bg-[#1C2638] border border-transparent dark:border-slate-700/60 rounded-2xl p-6 shadow-md transition-all hover:scale-105 group"
          >
            <div className="w-12 h-12 bg-[#DCFCE7] dark:bg-green-950/60 rounded-xl flex items-center justify-center mb-4">
              <svg xmlns="http://www.w3.org/2000/svg" className="h-6 w-6 text-[#159A46] dark:text-green-400" viewBox="0 0 24 24" fill="currentColor">
                <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z" />
              </svg>
            </div>
            <h3 className="font-bold text-lg mb-2">{t('contactUs.whatsapp.title', { defaultValue: 'واتساب' })}</h3>
            <p className="text-gray-300 dark:text-slate-300 text-center underline cursor-pointer hover:text-white transition-colors group-hover:text-white">
              <span dir="ltr" style={{ direction: 'ltr', unicodeBidi: 'isolate' }} className="inline-block font-medium whitespace-nowrap">
                {PHONE_DISPLAY}
              </span>
            </p>
          </a>
          
          {/* Email Contact */}
          <a
            href={EMAIL_HREF}
            className="flex-1 min-w-[260px] sm:min-w-[300px] max-w-md flex flex-col items-center justify-center bg-[#282828] dark:bg-[#1C2638] border border-transparent dark:border-slate-700/60 rounded-2xl p-6 sm:px-8 shadow-md transition-all hover:scale-105 group"
          >
            <div className="w-12 h-12 bg-[#F3E8FF] dark:bg-purple-950/60 rounded-xl flex items-center justify-center mb-4">
              <svg xmlns="http://www.w3.org/2000/svg" className="h-6 w-6 text-[#9333EA] dark:text-purple-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
              </svg>
            </div>
            <h3 className="font-bold text-lg mb-2">{t('contactUs.email.title', { defaultValue: 'البريد الإلكتروني' })}</h3>
            <p className="text-gray-300 dark:text-slate-300 text-center underline cursor-pointer hover:text-white transition-colors group-hover:text-white text-xs sm:text-sm font-medium whitespace-nowrap">
              <span dir="ltr" style={{ direction: 'ltr', unicodeBidi: 'isolate' }} className="inline-block">
                {EMAIL_DISPLAY}
              </span>
            </p>
          </a>
        </div>
      </MaxWidthWrapper>
    </div>
  );
};

export default ContactUsSection;
