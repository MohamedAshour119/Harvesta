import React from 'react';
import { FaCreditCard, FaApple } from 'react-icons/fa';

interface PaymentMethodSelectorProps {
  // selectedMethod: 'visa' | 'mastercard' | 'applepay';
  // onMethodChange: (method: 'visa' | 'mastercard' | 'applepay') => void;
}

const PaymentMethodSelector: React.FC<PaymentMethodSelectorProps> = ({
  // selectedMethod,
  // onMethodChange
}) => {
  const paymentMethods = [
    {
      id: 'visa' as const,
      name: 'Visa',
      icon: <FaCreditCard className="w-6 h-6" />,
      bgColor: 'bg-blue-50',
      borderColor: 'border-gray-200',
      selectedBorderColor: 'border-yellow',
      textColor: 'text-gray-600',
      selectedTextColor: 'text-yellow'
    },
    {
      id: 'mastercard' as const,
      name: 'MasterCard',
      icon: <FaCreditCard className="w-6 h-6" />,
      bgColor: 'bg-red-50',
      borderColor: 'border-gray-200',
      selectedBorderColor: 'border-yellow',
      textColor: 'text-gray-600',
      selectedTextColor: 'text-yellow'
    },
    {
      id: 'applepay' as const,
      name: 'Apple Pay',
      icon: <FaApple className="w-6 h-6" />,
      bgColor: 'bg-gray-50',
      borderColor: 'border-gray-200',
      selectedBorderColor: 'border-yellow',
      textColor: 'text-gray-600',
      selectedTextColor: 'text-yellow'
    }
  ];

  return (
    <div className="grid grid-cols-3 gap-4">
      {paymentMethods.map((method) => (
        <button
          key={method.id}
          type="button"
          // onClick={() => onMethodChange(method.id)}
          className={`
            relative p-4 rounded-lg border-2 transition-all duration-200 cursor-pointer
            hover:scale-105 focus:outline-none
            ${method.bgColor}
            ${selectedMethod === method.id 
              ? `${method.selectedBorderColor}` 
              : method.borderColor
            }
          `}
        >
          {/* Selection indicator */}
          {selectedMethod === method.id && (
            <div className="absolute -top-2 -right-2 w-6 h-6 bg-yellow-500 rounded-full flex items-center justify-center">
              <svg className="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 20 20">
                <path fillRule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clipRule="evenodd" />
              </svg>
            </div>
          )}
          
          <div className="flex flex-col items-center space-y-2">
            <div className={`
              ${method.textColor} transition-colors duration-200
              ${selectedMethod === method.id ? method.selectedTextColor : method.textColor}
            `}>
              {method.icon}
            </div>
            <span className={`
              ${method.textColor} transition-colors duration-200
              ${selectedMethod === method.id ? method.selectedTextColor : method.textColor}
            `}>
              {method.name}
            </span>
          </div>
        </button>
      ))}
    </div>
  );
};

export default PaymentMethodSelector;
