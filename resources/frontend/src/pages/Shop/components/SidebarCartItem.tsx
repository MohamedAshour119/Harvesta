import React from 'react';
import { FaRegTrashAlt, FaMinus } from 'react-icons/fa';
import i18n from '../../../i18n';

interface SidebarCartItemProps {
  id: number;
  name: string;
  image: string;
  price: number;
  quantity?: number;
  variantName?: string;
  variantValue?: string;
  onIncreaseQuantity: (id: number) => void;
  onDecreaseQuantity: (id: number) => void;
}

const SidebarCartItem: React.FC<SidebarCartItemProps> = ({
  id,
  name,
  image,
  price,
  quantity,
  variantName,
  variantValue,
  onIncreaseQuantity,
  onDecreaseQuantity,
}) => {
  const isRTL = i18n.language === 'ar';

  return (
    <div className="flex items-center bg-white rounded-xl border border-gray-100 mb-3 px-3 py-2 shadow-sm">
        <div className="flex items-center border border-gray-200 p-1 rounded-md">
            {/* Image */}
            <img
                src={image}
                alt={name}
                className="w-14 h-fit max-h-[56px] object-contain rounded-md bg-white"
            />
        </div>

      {/* Details */}
      <div className="flex-1 min-w-0 ms-3 flex flex-col gap-y-2">
        <div className="font-medium text-gray-800 text-sm truncate flex flex-col gap-y-1">
          {name}
          {variantName && variantValue && (
            <span className="text-xs text-gray-500">
              {variantName}: {variantValue}
            </span>
          )}
        </div>
        <div className="font-semibold text-xs mt-1 flex items-end justify-between">
            <span className="flex items-center">
              <span className="text-sm font-bold mx-1">EGP</span> {price?.toFixed(2)}
            </span> 

            {/* Price and Quantity Controls */}
            <div className="flex justify-between gap-y-3">
                <div className="flex items-center">
                <button 
                    className={`w-6 h-6 flex items-center justify-center bg-yellow text-white ${isRTL ? 'rounded-r' : 'rounded-l'} cursor-pointer`}
                    onClick={() => onDecreaseQuantity(id)}
                >
                    {quantity === 1 ? <FaRegTrashAlt size={14} /> : <FaMinus size={12}/> }
                </button>   
                <span className="w-6 h-6 flex items-center justify-center bg-amber-400 text-white font-medium">
                    {quantity}
                </span>
                <button 
                    className={`w-6 h-6 flex items-center justify-center bg-yellow text-white ${isRTL ? 'rounded-l' : 'rounded-r'} cursor-pointer`}
                    onClick={() => onIncreaseQuantity(id)}
                >
                    +
                </button>
                </div>
            </div>  
        </div>
      </div>

    </div>
  );
};

export default SidebarCartItem;
