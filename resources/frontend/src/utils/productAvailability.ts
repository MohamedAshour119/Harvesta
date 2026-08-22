import type { IProductVariant } from '../types';

export const isVariantAvailable = (variant?: IProductVariant | null) => {
    if (!variant) {
        return false;
    }

    const manualAvailability = variant.stock_availability?.toLowerCase();

    if (manualAvailability === 'in-stock') {
        return true;
    }

    if (manualAvailability === 'out-of-stock') {
        return false;
    }

    return (variant.total_quantity ?? 0) > 0;
};
