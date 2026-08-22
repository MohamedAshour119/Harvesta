import React from 'react';
import { useLocation } from 'react-router-dom';

interface MaxWidthWrapperProps {
  children: React.ReactNode;
  className?: string;
}

const MaxWidthWrapper: React.FC<MaxWidthWrapperProps> = ({ 
  children, 
  className = '' 
}) => {
  const pathname = useLocation().pathname;
  return (
    <div className={`w-full mx-auto max-w-[1242px] ${pathname === '/show-results' ? 'px-0' : 'px-4'} ${className}`}>
      {children}
    </div>
  );
};

export default MaxWidthWrapper;
