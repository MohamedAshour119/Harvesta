import { useState, type ChangeEvent } from 'react';
import { useNavigate } from 'react-router-dom';
import { FaUser, FaEnvelope, FaBoxOpen, FaRulerCombined, FaMoneyBillWave, FaCalendarAlt } from 'react-icons/fa';
import { useTranslation } from 'react-i18next';
import i18n from '../../i18n';
import FooterSection from '../../components/FooterSection';
import TextInput from '../../components/inputs/TextInput';
import PhoneInputComponent from '../../components/inputs/PhoneInputComponent';
import ApiClient from '../../ApiClient';

type FormData = {
    full_name: string;
    email: string;
    phone: string;
    product_name: string;
    product_description: string;
    estimated_quantity: string;
    budget: string;
    deadline: string;
    product_images: File[] | null;
};

const initialErrors = {
    full_name: '',
    email: '',
    phone: '',
    product_name: '',
    product_description: '',
    estimated_quantity: '',
    budget: '',
    deadline: '',
    product_images: ''
};

const CustomProductRequest = () => {
    const navigate = useNavigate();
    const { t } = useTranslation(['common', 'customProduct']);
    const isRTL = i18n.language === 'ar';
    const [formData, setFormData] = useState<FormData>({
        full_name: '',
        email: '',
        phone: '1019935417',
        product_name: '',
        product_description: '',
        estimated_quantity: '',
        budget: '',
        deadline: '',
        product_images: null as File[] | null
    });

    const [errors, setErrors] = useState(initialErrors);
    const [isSubmitting, setIsSubmitting] = useState(false);
    const [submitSuccess, setSubmitSuccess] = useState(false);
    const [submitError, setSubmitError] = useState('');

    // const handleChange = (e: React.ChangeEvent<HTMLInputElement | HTMLTextAreaElement | HTMLSelectElement>) => {
    //     const { name, value } = e.target;
    //     setFormData(prev => ({
    //         ...prev,
    //         [name]: value
    //     }));
    // };

    const handleChange = (field: keyof FormData, value: string | ChangeEvent<HTMLInputElement>) => {
        setFormData(prev => ({
            ...prev,
            [field]: value
        }));
    };

    const handleFileChange = (e: React.ChangeEvent<HTMLInputElement>) => {
        if (e.target.files) {
            setFormData(prev => ({
                ...prev,
                product_images: Array.from(e.target.files as FileList)
            }));
        }
    };

    const handleSubmit = async (e: React.FormEvent) => {
        e.preventDefault();
        setIsSubmitting(true);
        setSubmitError('');

        const data = new FormData();
        Object.keys(formData).forEach(key => {
            const value = formData[key as keyof FormData];
            if (key === 'product_images' && value) {
                (value as File[]).forEach(file => {
                    data.append('product_images[]', file);
                });
            } else if (value !== null && value !== undefined) {
                data.append(key, value as string);
            }
        });

        // Here you would typically send the form data to your API
        // For now, we'll just simulate a successful submission
        ApiClient.products.requestCustomProduct(data)
            .then(res => {
                console.log(res);

                setSubmitSuccess(true);
                // Reset form after successful submission
                setFormData({
                    full_name: '',
                    email: '',
                    phone: '',
                    product_name: '',
                    product_description: '',
                    estimated_quantity: '',
                    budget: '',
                    deadline: '',
                    product_images: null
                });
            })
            .catch(err => {
                console.log(err);
                setSubmitError(t('customProduct:submitError'));

                if (err.response?.data?.errors) {
                    const apiErrors = err.response.data.errors as Record<string, string[]>;

                    setErrors({
                        ...initialErrors,
                        ...Object.fromEntries(
                            Object.entries(apiErrors).map(([key, messages]) => [key, messages[0]])
                        )
                    });
                }

            })
            .finally(() => {
                setIsSubmitting(false);
            })
    };

    return (
        <div className="mt-10">
            <div className="text-center mb-10">
                <h1 className="text-3xl font-bold text-gray-900 mb-4">{t('customProduct:title')}</h1>
                <p className="text-lg text-gray-600">
                    {t('customProduct:subtitle')}
                </p>
            </div>

            {submitSuccess ? (
                <div className="bg-green-50 border border-green-200 rounded-lg p-8 text-center">
                    <svg className="w-16 h-16 text-green-500 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M5 13l4 4L19 7" />
                    </svg>
                    <h2 className="text-2xl font-semibold text-gray-800 mb-2">{t('customProduct:successTitle')}</h2>
                    <p className="text-gray-600 mb-6">{t('customProduct:successMessage')}</p>
                    <div className="flex justify-center space-x-4">
                        <button
                            onClick={() => navigate('/')}
                            className="bg-gray-200 hover:bg-gray-300 text-gray-800 font-medium py-2 px-6 rounded-lg transition-colors duration-300"
                        >
                            {t('common:returnHome')}
                        </button>
                        <button
                            onClick={() => setSubmitSuccess(false)}
                            className="bg-yellow hover:bg-yellow-600 text-white font-medium py-2 px-6 rounded-lg transition-colors duration-300"
                        >
                            {t('customProduct:newRequest')}
                        </button>
                    </div>
                </div>
            ) : (
                <form onSubmit={handleSubmit} className="bg-white shadow-lg rounded-lg p-6 sm:p-8">
                    {submitError && (
                        <div className="mb-6 p-4 bg-red-50 border-l-4 border-red-500 text-red-700">
                            <p>{submitError}</p>
                        </div>
                    )}

                    <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div className="col-span-1 flex flex-col gap-y-3">
                            <h2 className="text-xl font-semibold mb-4 text-gray-800">{t('customProduct:personalInfo')}</h2>

                            <TextInput
                                id="full_name"
                                name="full_name"
                                label={t('customProduct:fullName') + ' *'}
                                value={formData.full_name}
                                onChange={(e) => handleChange('full_name', e.target.value)}
                                icon={<FaUser className="text-gray-400" />}
                                error={errors.full_name}
                            />

                            <TextInput
                                id="email"
                                name="email"
                                label={t('customProduct:email') + ' *'}
                                value={formData.email}
                                onChange={(e) => handleChange('email', e.target.value)}
                                type="email"
                                icon={<FaEnvelope className="text-gray-400" />}
                                error={errors.email}
                            />

                            <PhoneInputComponent
                                id="phone"
                                name="phone"
                                label={t('customProduct:phone')}
                                value={formData.phone}
                                onChange={(value) => handleChange('phone', value)}
                                disabled={false}
                                error={errors.phone}
                            />
                        </div>

                        <div className="col-span-1 flex flex-col gap-y-3">
                            <h2 className="text-xl font-semibold mb-4 text-gray-800">{t('customProduct:productDetails')}</h2>

                            <TextInput
                                id="product_name"
                                name="product_name"
                                label={t('customProduct:productName') + ' *'}
                                value={formData.product_name}
                                onChange={(e) => handleChange('product_name', e.target.value)}
                                placeholder={t('customProduct:productNamePlaceholder')}
                                icon={<FaBoxOpen className="text-gray-400" />}
                                error={errors.product_name}
                            />

                            <TextInput
                                id="estimated_quantity"
                                name="estimated_quantity"
                                label={t('customProduct:quantity')}
                                value={formData.estimated_quantity}
                                onChange={(e) => handleChange('estimated_quantity', e.target.value)}
                                placeholder={t('customProduct:quantityPlaceholder')}
                                icon={<FaRulerCombined className="text-gray-400" />}
                                error={errors.estimated_quantity}
                            />

                            <TextInput
                                id="budget"
                                name="budget"
                                label={t('customProduct:budget')}
                                value={formData.budget}
                                onChange={(e) => handleChange('budget', e.target.value)}
                                placeholder={t('customProduct:budgetPlaceholder')}
                                icon={<FaMoneyBillWave className="text-gray-400" />}
                                error={errors.budget}
                            />
                        </div>
                    </div>

                    <div className="mt-6">
                        <label htmlFor="description" className="block text-sm font-medium text-gray-700 mb-1">{t('customProduct:description')} *</label>
                        <textarea
                            id="description"
                            name="description"
                            value={formData.product_description}
                            onChange={(e) => handleChange('product_description', e.target.value)}
                            rows={5}
                            className="w-full p-3 rounded-lg border-default focus:outline-none focus:border-yellow focus:shadow-[0px_0px_0px_3px_rgba(235,22,22,0.2)]"
                            placeholder={t('customProduct:descriptionPlaceholder')}
                        ></textarea>
                        {errors.product_description && (
                            <p className="mt-1 text-sm text-red-600">{errors.product_description}</p>
                        )}
                    </div>

                    <div className="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6 mt-4">
                        <div>
                            <TextInput
                                id="deadline"
                                name="deadline"
                                label={t('customProduct:deadline')}
                                value={formData.deadline}
                                onChange={(e) => handleChange('deadline', e.target.value)}
                                type="date"
                                icon={<FaCalendarAlt className="text-gray-400" />}
                                error={errors.deadline}
                            />
                        </div>

                        <div>
                            <label htmlFor="product_image" className="block text-sm font-medium text-gray-700 mb-1">{t('customProduct:attachments')}</label>
                            <input
                                type="file"
                                id="product_image"
                                name="product_image"
                                onChange={handleFileChange}
                                multiple
                                className="w-full p-2 rounded-md border-default focus:outline-none focus:border-yellow focus:shadow-[0px_0px_0px_3px_rgba(235,22,22,0.2)]"
                            />
                            <p className="mt-1 text-xs text-gray-500">{t('customProduct:attachmentsHelp')}</p>
                            {errors.product_images && (
                                <p className="mt-1 text-sm text-red-600">{errors.product_images}</p>
                            )}
                        </div>
                    </div>

                    <div className="flex justify-end mt-8">
                        <button
                            type="button"
                            onClick={() => navigate('/')}
                            className="cursor-pointer me-4 bg-gray-200 hover:bg-gray-300 text-gray-800 font-medium py-3 px-6 rounded-lg transition-colors duration-300"
                        >
                            {t('common:buttons.cancel')}
                        </button>
                        <button
                            type="submit"
                            disabled={isSubmitting}
                            className={`cursor-pointer bg-yellow hover:bg-yellow-600 text-white font-medium py-3 px-8 rounded-lg transition-colors duration-300 flex items-center ${isSubmitting ? 'opacity-70 cursor-not-allowed' : ''}`}
                        >
                            {isSubmitting ? (
                                <>
                                    <svg className={`animate-spin ${isRTL ? 'me-3 -ms-1' : '-ms-1 me-3'} h-5 w-5 text-white`} xmlns="http://www.w3.org/2000/svg" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4"></circle>
                                        <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    {t('common:submitting')}
                                </>
                            ) : t('customProduct:submitRequest')}
                        </button>
                    </div>
                </form>
            )}

            <FooterSection />

        </div>
    );
};

export default CustomProductRequest;