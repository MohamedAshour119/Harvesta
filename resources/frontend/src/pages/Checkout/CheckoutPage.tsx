import React, { useEffect, useState, type ChangeEvent } from 'react';
import { useSelector, useDispatch } from 'react-redux';
import { useTranslation } from 'react-i18next';
import type { RootState } from '../../store/store';
import MaxWidthWrapper from '../../components/MaxWidthWrapper';
import FooterSection from '../../components/FooterSection';
import BillingDetails from './components/BillingDetails';
import OrderSummary from './components/OrderSummary';
import type { BillingInfo } from '../../types';
import { setCoupon } from '../../store/slices/couponSlice';
import ApiClient from '../../ApiClient';
import { setTotal } from '../../store/slices/cartSlice';
import MinimumOrderValuePopup from '../../components/MinimumOrderValuePopup';
import { setIsMinimumOrderValuePopupOpen } from '../../store/slices/isMinimumOrderValuePopupOpen';
import type { SelectOption } from '../../ApiClient';

const CheckoutPage: React.FC = () => {
  const { t } = useTranslation('checkout');
  const { items, subtotal } = useSelector((state: RootState) => state.cart);
  const coupon = useSelector((state: RootState) => state.coupon);

  const dispatch = useDispatch();

  const [billingInfo, setBillingInfo] = useState<BillingInfo>({
    firstName: '',
    lastName: '',
    companyName: '',
    streetAddress: '',
    email: '',
    phone: '',
    phoneCountryCode: '20',
    city: '',
    province: '',
    postalCode: '',
  });

  const [billingInfoErrors, setbillingInfoErrors] = useState({});
  const [cityOptions, setCityOptions] = useState<SelectOption[]>([]);

  const [isProcessing, setIsProcessing] = useState(false);
  const [shippingCost, setShippingCost] = useState<number | null>(null);
  const [isCalculatingShipping, setIsCalculatingShipping] = useState(false);

  const discountAmount = coupon?.code ? coupon.discount : 0;

  useEffect(() => {
    dispatch(setTotal(subtotal - discountAmount * subtotal / 100));
  }, [dispatch, subtotal, discountAmount]);

  useEffect(() => {
    const loadShippingDestinations = async () => {
      try {
        const response = await ApiClient.checkout.getShippingDestinations();
        setCityOptions(response.data.data ?? []);
      } catch (error) {
        console.error('Error fetching shipping destinations:', error);
        setCityOptions([]);
      }
    };

    loadShippingDestinations();
  }, []);

  useEffect(() => {
    const getShippingCost = async () => {
      if (!billingInfo.city || items.length === 0) {
        setShippingCost(null);
        return;
      }

      setIsCalculatingShipping(true);
      try {
        const payload = {
          city: billingInfo.city,
          items: items.map((item: any) => ({
            productVariantId: item.id,
            quantity: item.quantity
          }))
        };

        const response = await ApiClient.checkout.getShippingCost(payload);
        setShippingCost(response.data.data.shippingCost);

        const discountValue = discountAmount * subtotal / 100;
        dispatch(setTotal(subtotal + response.data.data.shippingCost - discountValue));

      } catch (error) {
        console.error('Error fetching shipping cost:', error);
        setShippingCost(null);
      } finally {
        setIsCalculatingShipping(false);
      }
    };

    const debounceTimer = setTimeout(() => {
      getShippingCost();
    }, 500);

    return () => clearTimeout(debounceTimer);
  }, [billingInfo.city, items]);

  const handleBillingInfoChange = (field: keyof BillingInfo, value: string | ChangeEvent<HTMLInputElement>) => {
    setBillingInfo(prev => ({
      ...prev,
      [field]: value
    }));
  };

  const handleCouponApply = (code: string) => {
    if (code.toLowerCase() === 'save10') {
      setCoupon({
        code,
        isApplied: true,
        discount: subtotal * 0.1
      });
    } else {
      setCoupon({
        code,
        isApplied: false,
        discount: 0
      });
    }
  };

  const handlePlaceOrder = async () => {
    setIsProcessing(true);
    setbillingInfoErrors({});

    try {
      const payload = {
        address: billingInfo,
        products: items.map((item: any) => ({
          productId: item.productId,
          productVariantId: item.id,
          quantity: item.quantity
        })),
        coupon: coupon?.code || null,
        agent_code: sessionStorage.getItem('agent_ref_code') || null,
      };

      const response = await ApiClient.checkout.checkout(payload);
      if (response.data.data.session_url) {
        window.location.href = response.data.data.session_url;
      }

    } catch (error: any) {
      console.error('Order processing failed:', error);
      if (error.response && error.response.data && error.response.data.message === 'Minimum order value not met') {
        dispatch(setIsMinimumOrderValuePopupOpen({ visible: true, message: error.response.data.errors.minimum_order_value }));
        return;
      }

      if (error.response && error.response.data && error.response.data.errors) {
        setbillingInfoErrors(error.response.data.errors);
      }
    } finally {
      setIsProcessing(false);
    }
  };

  useEffect(() => {
    const checkIfCouponApplied = async () => {
      try {
        const response = await ApiClient.checkout.checkIfCouponApplied();
        const payload = {
          code: response.data.data.coupon.name,
          discount: +response.data.data.coupon.value,
          isApplied: true,
          error: ''
        };
        dispatch(setCoupon(payload));
      } catch (error) {
        const payload = {
          code: '',
          discount: 0,
          isApplied: false,
          error: ''
        };
        dispatch(setCoupon(payload));
      }
    };

    checkIfCouponApplied();
  }, []);

  return (
    <>
      <MaxWidthWrapper>
        <div className="py-8">
          {/* Header */}
          <div className="text-center mb-8">
            <h1 className="text-3xl font-bold text-gray-900 dark:text-slate-100 mb-2">{t('title')}</h1>
            <p className="text-gray-600 dark:text-slate-400">{t('completeYourPurchase')}</p>
          </div>

          {/* Main Content */}
          <div className="flex flex-col lg:flex-row gap-8">
            {/* Left Side - Billing Details */}
            <div className="flex-1">
              <BillingDetails
                billingInfo={billingInfo}
                onBillingInfoChange={handleBillingInfoChange}
                errors={billingInfoErrors}
                cityOptions={cityOptions}
              />
            </div>

            {/* Right Side - Order Summary */}
            <div className="w-full lg:w-96 flex-shrink-0">
              <OrderSummary
                items={items}
                subtotal={subtotal}
                shippingFee={shippingCost || 0}
                isCalculatingShipping={isCalculatingShipping}
                discount={discountAmount}
                coupon={coupon!}
                onCouponApply={handleCouponApply}
                onPlaceOrder={handlePlaceOrder}
                isProcessing={isProcessing}
              />
            </div>
          </div>
        </div>

        <MinimumOrderValuePopup />

      </MaxWidthWrapper>
      <FooterSection />
    </>
  );
};

export default CheckoutPage;
