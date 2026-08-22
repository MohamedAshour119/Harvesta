import { PromotionCard } from "./PromotionCard";

interface FeaturedPromotionsProps {
    promotions: any[];
    onViewDetails?: (promotion: any) => void;
}

export const FeaturedPromotions = ({ promotions, onViewDetails }: FeaturedPromotionsProps) => {
    return (
      <div className="mb-8 p-6">
        <h2 className="text-lg font-semibold text-gray-900 mb-4">Featured Promotions</h2>
        <div className="grid min-[530px]:!grid-cols-2 md:grid-cols-3 widescreen:grid-cols-4 min-[1505px]:!grid-cols-5 gap-4">
          {promotions.map((promotion, index) => (
            <PromotionCard
              key={index}
              title={promotion.title}
              discount={promotion.discount}
              originalPrice={promotion.originalPrice}
              daysLeft={promotion.daysLeft}
              imageUrl={"https://cdn.pixabay.com/photo/2014/06/03/19/38/board-361516_960_720.jpg"}
              badgeText={promotion.badgeText}
              onViewDetails={() => onViewDetails?.(promotion)}
            />
          ))}
        </div>
      </div>
    );
  };