export interface ProductVariant {
  id: number;
  name: string;
  price: number;
  image: string;
  availability: string;
}

export interface ProductDetails {
  id: number;
  name: string;
  description: string;
  price: number;
  originalPrice?: number;
  rating: number;
  reviewCount: number;
  images: string[];
  variants: ProductVariant[];
  category: string;
  inStock: boolean;
  tags: string[];
}