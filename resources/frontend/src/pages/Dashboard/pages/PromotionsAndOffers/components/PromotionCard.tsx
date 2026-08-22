import { FaClock } from "react-icons/fa";
import { LuClock } from "react-icons/lu";

interface PromotionCardProps {
    title: string;
    discount: number;
    originalPrice: number;
    daysLeft: number;
    imageUrl: string;
    badgeText: string;
    onViewDetails?: () => void;
}

export const PromotionCard = (props: PromotionCardProps) => {
    const { title, discount, originalPrice, daysLeft, imageUrl, badgeText, onViewDetails } = props;
    return (
      <div className="bg-white rounded-lg border border-gray-200 overflow-hidden hover:shadow-md transition-shadow duration-200">
        {/* Image Container */}
        <div className="relative p-4">
          <div className="relative">
            <img 
              src={imageUrl} 
              alt={title}
              className="w-full h-32 object-cover rounded-lg"
            />
            {/* Badge */}
            <div className="absolute top-2 left-2 bg-slate-900 text-white px-2 py-1 rounded text-xs font-medium">
              {badgeText}
            </div>
            {/* Status */}
            <div className="absolute top-2 right-2 bg-[#26C5A8] text-white px-2 py-1 rounded text-xs font-medium">
              Active
            </div>
          </div>
        </div>
        
        {/* Content */}
        <div className="p-4">
          <h3 className="text-sm font-medium text-gray-900 mb-2 line-clamp-2">
            {title}
          </h3>
          
          {/* Price Info */}
          <div className="mb-3 flex justify-between items-center">
            <div className="text-[#B12C2E] font-semibold text-sm mb-1">
              Earn {discount} per sale
            </div>
            <div className="flex items-center text-xs text-gray-500">
              <LuClock className="mr-1" size={15}/>
              <span>{daysLeft} days left</span>
            </div>
          </div>
          
          {/* Action Buttons */}
          <div className="gap-2 grid grid-cols-2">
            <button className="bg-[#B12C2E] hover:bg-[#972426] text-white px-3 py-2 rounded text-xs font-medium transition-colors duration-200 cursor-pointer">
              Share Offer
            </button>
            <button
              onClick={() => onViewDetails && onViewDetails()}
              className="px-3 py-2 border border-gray-300 hover:border-gray-400 text-gray-700 rounded text-xs font-medium transition-colors duration-200 cursor-pointer"
            >
              View Details
            </button>
          </div>
        </div>
      </div>
    );
};
