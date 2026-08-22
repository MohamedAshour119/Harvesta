interface BonusTierProps {
    sales: number;
    reward: number;
    isActive: boolean;
    isCompleted: boolean;
    progress: number;
}

export const BonusTier = ({ sales, reward, isActive, isCompleted, progress }: BonusTierProps) => {
    return (
      <div className="flex flex-col items-center relative">
        {/* Circle Indicator */}
        <div className={`w-5 h-5 rounded-full border-2 transition-colors duration-200 ${
          isCompleted 
            ? 'bg-[#B12C2E] border-white' 
            : isActive 
              ? 'bg-white border-4 border-red-700' 
              : 'bg-gray-300 border-gray-300'
        }`} />
        
        {/* Sales Number */}
        <div className="mt-2 text-sm font-medium text-gray-900">
          {sales} sales
        </div>
        
        {/* Reward */}
        <div className={`text-sm font-semibold ${
          isCompleted || isActive ? 'text-[#26C5A8]' : 'text-gray-500'
        }`}>
          {reward}
        </div>
      </div>
    );
};
