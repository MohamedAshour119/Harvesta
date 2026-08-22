
export function calculateTotalWithDiscount(
    subtotal: number,
    value: number
) {
    const cut_price = (subtotal * value) / 100;
    return subtotal - cut_price;
}
