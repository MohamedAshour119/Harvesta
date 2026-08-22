import React, { useState, type ChangeEvent, type FormEvent, useEffect, useRef } from 'react';
import { useTranslation } from 'react-i18next';
import i18n from '../../../i18n';
import TextInput from '../../../components/inputs/TextInput';
import ApiClient from '../../../ApiClient';
import PhoneInputComponent from '../../../components/inputs/PhoneInputComponent';

interface ContactFormModalProps {
  show: boolean;
  onClose: () => void;
}

const MAX_MESSAGE_LENGTH = 1500;

const ContactFormModal: React.FC<ContactFormModalProps> = ({ show, onClose }) => {
  const { t } = useTranslation('home');
  const isRTL = i18n.language === 'ar';
  const [formData, setFormData] = useState({ full_name: '', email: '', phone: '', message: '' });
  const [errors, setErrors] = useState<{ [key: string]: string[] }>({});
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [showSuccess, setShowSuccess] = useState(false);
  const modalRef = useRef<HTMLDivElement>(null);

  const handleChange = (
    e: ChangeEvent<HTMLInputElement | HTMLTextAreaElement> | string
  ) => {
    if (typeof e === "string") {
      setFormData(prev => ({ ...prev, phone: e }));
    } else {
      const { name, value } = e.target;
      if (name === "message" && value.length > MAX_MESSAGE_LENGTH) {
        return;
      }
      setFormData(prev => ({ ...prev, [name]: value }));
    }
  };

  const handleSubmit = (e: FormEvent) => {
    e.preventDefault();
    setIsSubmitting(true);
    
    ApiClient.sendMessage.sendMessage(formData)
      .then(() => {
        setShowSuccess(true);
        setFormData({ full_name: '', email: '', phone: '', message: '' });
      })
      .catch((error) => {
        console.error('Error submitting contact form:', error);
        setErrors(error.response?.data?.errors || {});
      })
      .finally(() => {
        setIsSubmitting(false);
      });
  };

  const handleClose = () => {
    onClose();
    setTimeout(() => {
      setShowSuccess(false);
    }, 300);
  };

  useEffect(() => {
    const handleClickOutside = (event: MouseEvent) => {
      if (modalRef.current && !modalRef.current.contains(event.target as Node)) {
        handleClose();
      }
    };

    if (show) {
      document.addEventListener('mousedown', handleClickOutside);
      document.body.style.overflow = 'hidden';
    }

    return () => {
      document.removeEventListener('mousedown', handleClickOutside);
      document.body.style.overflow = 'unset';
    };
  }, [show]);

  useEffect(() => {
    const handleEscape = (event: KeyboardEvent) => {
      if (event.key === 'Escape') {
        handleClose();
      }
    };

    if (show) {
      document.addEventListener('keydown', handleEscape);
    }

    return () => {
      document.removeEventListener('keydown', handleEscape);
    };
  }, [show]);

  return (
    <div className={`fixed inset-0 z-50 flex items-center justify-center transition-all duration-300 ${show ? 'opacity-100 visible' : 'opacity-0 invisible'}`}>
      <div className={`absolute inset-0 bg-black/60 backdrop-blur-sm transition-opacity duration-300 ${show ? 'opacity-100' : 'opacity-0'}`} onClick={handleClose}></div>
      
      <div
        ref={modalRef}
        className={`relative bg-white dark:bg-[#151D2C] border border-gray-100 dark:border-slate-700/60 rounded-2xl shadow-2xl w-full max-w-md mx-4 transform transition-all duration-300 ${show ? 'scale-100 translate-y-0' : 'scale-95 translate-y-4'}`}
      >
        {/* Close Button */}
        <button
          onClick={handleClose}
          className={`absolute top-4 ${isRTL ? 'left-4' : 'right-4'} w-8 h-8 cursor-pointer flex items-center justify-center rounded-full bg-white/20 hover:bg-white/30 text-white transition-colors duration-200 z-10`}
        >
          <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>

        {showSuccess ? (
          <div className="p-8 text-center space-y-4">
            <svg className="mx-auto w-12 h-12 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M5 13l4 4L19 7" />
            </svg>
            <h3 className="text-xl font-bold text-gray-900 dark:text-slate-100">{t('aboutUs.MessageSent')}</h3>
            <p className="text-gray-600 dark:text-slate-400">{t('aboutUs.MessageSentDescription')}</p>
            <button onClick={handleClose} className="mt-4 px-6 py-2 cursor-pointer bg-[#B12C2E] hover:bg-[#972426] text-white rounded-xl transition-colors font-semibold">
              {t('aboutUs.Close')}
            </button>
          </div>
        ) : (
          <>
            {/* Form Header */}
            <div className="bg-[#B12C2E] rounded-t-2xl p-6 text-white text-center">
              <h2 className="text-2xl font-bold mb-2">{t('aboutUs.GetInTouch')}</h2>
              <p className="text-[#FFEFEF] text-sm">{t('aboutUs.ContactFormDescription')}</p>
            </div>
            {/* Form Content */}
            <form className="p-6" onSubmit={handleSubmit}>
              <div className="space-y-5">
                {/* Name Field */}
                <div className="relative">
                  <TextInput
                    id="full_name"
                    name="full_name"
                    label={t('aboutUs.name')}
                    placeholder={t('aboutUs.YourName')}
                    value={formData.full_name}
                    onChange={handleChange}
                    className='rounded-xl'
                    icon={
                      <svg className="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                      </svg>
                    }
                    error={errors.full_name?.[0]}
                  />
                </div>

                {/* Email Field */}
                <div className="relative">
                  <TextInput
                    id="email"
                    name="email"
                    label={t('aboutUs.email')}
                    placeholder={t('aboutUs.YourEmail')}
                    value={formData.email}
                    onChange={handleChange}
                    className='rounded-xl'
                    icon={
                      <svg className="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207" />
                      </svg>
                    }
                    error={errors.email?.[0]}
                  />
                </div>

                {/* Phone Field */}
                <PhoneInputComponent
                    id="phone"
                    name="phone"
                    label={t('aboutUs.phone')}
                    value={formData.phone}
                    onChange={handleChange}
                    disabled={false}
                    error={errors.phone?.[0]}
                />

                {/* Message Field */}
                <div>
                  <label htmlFor="message" className="block text-sm font-semibold text-gray-700 dark:text-slate-300 mb-1.5">
                    {t('aboutUs.message')}
                  </label>
                  <div className="relative">
                    <textarea
                      name="message"
                      placeholder={t('aboutUs.YourMessage')}
                      value={formData.message}
                      onChange={handleChange}
                      rows={4}
                      maxLength={MAX_MESSAGE_LENGTH}
                      className={`w-full ${formData.message.length > 0 ? '!px-3' : ''} ${isRTL ? 'pr-9' : 'pl-9'} py-2.5 bg-white dark:bg-[#1C2638] border border-gray-300 dark:border-slate-700 text-gray-900 dark:text-slate-100 placeholder:text-gray-400 dark:placeholder:text-slate-500 rounded-xl focus:ring-2 focus:ring-[#B12C2E] focus:border-[#B12C2E] outline-0 transition-all duration-200 resize-none text-sm`}
                    />
                    {formData.message.length === 0 && (
                      <div className={`absolute top-3.5 ${isRTL ? 'right-3' : 'left-3'} pointer-events-none`}>
                        <svg className="w-5 h-5 text-gray-400 dark:text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                        </svg>
                      </div>
                    )}
                    <div className="flex flex-row-reverse justify-between">
                      <div className="text-xs text-gray-500 dark:text-slate-400 text-right mt-1">
                        {formData.message.length} / {MAX_MESSAGE_LENGTH}
                      </div>
                      <div className="text-sm text-red-500 mt-1">
                        {errors.message?.[0]}
                      </div>
                    </div>
                  </div>
                </div>

                {/* Submit Button */}
                <button
                  type="submit"
                  disabled={isSubmitting}
                  className={`w-full py-3 px-6 cursor-pointer rounded-xl font-semibold text-white transition-all duration-200 transform ${isSubmitting ? 'bg-gray-400 cursor-not-allowed' : 'bg-[#B12C2E] hover:bg-[#972426] dark:bg-[#B12C2E] dark:hover:bg-[#C83437] hover:scale-[1.02] active:scale-[0.98] shadow-lg hover:shadow-xl'}`}>
                  {isSubmitting ? (
                    <div className="flex items-center justify-center">
                      <svg className={`animate-spin h-5 w-5 text-white ${isRTL ? '-mr-1 ml-2' : '-ml-1 mr-2'}`} fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4"></circle>
                        <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                      </svg>
                      {t('aboutUs.Sending')}
                    </div>
                  ) : (
                    t('aboutUs.SendMessage')
                  )}
                </button>
              </div>
            </form>
          </>
        )}
      </div>
    </div>
  );
};

export default ContactFormModal;
