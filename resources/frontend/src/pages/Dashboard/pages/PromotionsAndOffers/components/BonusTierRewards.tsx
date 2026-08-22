import { BonusTier } from "./BonusTier";

interface BonusTierRewardsProps {
    tiers: any[];
    currentSales?: number;
}

export const BonusTierRewards = ({ tiers, currentSales = 15 }: BonusTierRewardsProps) => {
    return (
      <div className="bg-white rounded-lg border border-gray-200 p-6">
        <h2 className="text-lg font-semibold text-gray-900 mb-6">Bonus Tier Rewards</h2>
        
        <div className="relative">
          {/* Progress Line */}
          <div className="absolute top-2 left-0 right-0 flex items-center">
            <div className="flex-1 h-1 bg-gray-200">
              <div 
                className="h-1 bg-[#B12C2E] transition-all duration-700 ease-out"
                style={{ width: `${Math.min((currentSales / tiers[tiers.length - 1].sales) * 100, 100)}%` }}
              />
            </div>
          </div>
          
          {/* Tier Points */}
          <div className="flex justify-between relative z-10">
            {tiers.map((tier, index) => (
              <BonusTier
                key={index}
                progress={Math.min((currentSales / tier.sales) * 100, 100)}
                sales={tier.sales}
                reward={tier.reward}
                isActive={currentSales >= tier.sales && (index === tiers.length - 1 || currentSales < tiers[index + 1].sales)}
                isCompleted={currentSales >= tier.sales}
              />
            ))}
          </div>
        </div>
        
        {/* Progress Text */}
        <div className="mt-4 text-center text-sm text-gray-600">
          <span className="font-medium">{currentSales}</span> of {tiers[tiers.length - 1].sales} sales completed
        </div>
      </div>
    );
  };
