interface ProgressBarProps {
    current: number;
    total: number;
    className?: string;
}

export const ProgressBar = ({ current, total, className = "" }: ProgressBarProps) => {
    const percentage = Math.min((current / total) * 100, 100);
    
    return (
      <div className={`w-full bg-gray-200 rounded-full h-2 ${className}`}>
        <div 
          className="bg-[#B12C2E] h-2 rounded-full transition-all duration-500 ease-out"
          style={{ width: `${percentage}%` }}
        />
      </div>
    );
};
