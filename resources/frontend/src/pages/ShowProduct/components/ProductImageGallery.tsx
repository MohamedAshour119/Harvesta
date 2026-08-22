import React from 'react';

interface ProductImageGalleryProps {
  images: string[];
  productName: string;
  selectedImage: number;
  onImageSelect: (index: number) => void;
}

export const ProductImageGallery: React.FC<ProductImageGalleryProps> = ({
  images,
  productName,
  selectedImage,
  onImageSelect
}) => {
  const currentImage = images[selectedImage] || images[0];
  
  return (
    <div className="space-y-4">
      {/* Main Image */}
      <div className="aspect-square bg-gray-50 dark:bg-[#151D2C] border border-gray-100 dark:border-slate-800 rounded-2xl overflow-hidden p-4 flex items-center justify-center shadow-sm">
        <img 
          src={currentImage} 
          alt={productName}
          className="w-full h-full object-contain transition-transform duration-300 hover:scale-105"
        />
      </div>
      
      {/* Thumbnail Images */}
      {images.length > 1 && (
        <div className="flex space-x-2 overflow-x-auto flex-wrap gap-2">
          {images.map((image, index) => (
            <button
              key={index}
              onClick={() => onImageSelect(index)}
              className={`flex-shrink-0 w-16 h-16 rounded-xl cursor-pointer overflow-hidden border-2 p-1 bg-white dark:bg-[#151D2C] transition-all duration-200 ${
                selectedImage === index 
                  ? 'border-[#B12C2E] dark:border-[#E03E41] shadow-md' 
                  : 'border-gray-200 dark:border-slate-700 hover:border-gray-300 dark:hover:border-slate-600'
              }`}
            >
              <img 
                src={image} 
                alt={`${productName} ${index + 1}`}
                className="w-full h-full object-contain"
              />
            </button>
          ))}
        </div>
      )}
    </div>
  );
};
