import React, { useState, useEffect } from 'react';
import { FaArrowLeft, FaHome, FaSearch } from 'react-icons/fa';
import { FiZap } from 'react-icons/fi';
import { HiSparkles } from 'react-icons/hi';

export default function NotFoundResults() {
    const [mousePosition, setMousePosition] = useState({ x: 0, y: 0 });
    const [isHovered, setIsHovered] = useState(false);
    const [glitchActive, setGlitchActive] = useState(false);

    useEffect(() => {
        const handleMouseMove = (e: any) => {
            setMousePosition({ x: e.clientX, y: e.clientY });
        };

        const glitchInterval = setInterval(() => {
            setGlitchActive(true);
            setTimeout(() => setGlitchActive(false), 150);
        }, 3000);

        window.addEventListener('mousemove', handleMouseMove);
        return () => {
            window.removeEventListener('mousemove', handleMouseMove);
            clearInterval(glitchInterval);
        };
    }, []);

    const floatingElements = Array.from({ length: 20 }, (_, i) => (
        <div
            key={i}
            className="absolute w-2 h-2 bg-gradient-to-r from-pink-400 to-yellow-400 rounded-full opacity-40 animate-pulse"
            style={{
                left: `${Math.random() * 100}%`,
                top: `${Math.random() * 100}%`,
                animationDelay: `${Math.random() * 2}s`,
                animationDuration: `${2 + Math.random() * 2}s`
            }}
        />
    ));

    return (
        <div className="min-h-screen bg-gradient-to-br from-gray-50 via-white to-gray-100 flex items-center justify-center relative overflow-hidden">
            {/* Animated background elements */}
            <div className="absolute inset-0 opacity-30">
                {floatingElements}
            </div>
            
            {/* Dynamic gradient orb following mouse */}
            <div 
                className="absolute w-96 h-96 bg-gradient-to-r from-pink-500/20 to-yellow-500/20 rounded-full blur-3xl transition-all duration-300 ease-out pointer-events-none"
                style={{
                    left: mousePosition.x - 192,
                    top: mousePosition.y - 192,
                    transform: `scale(${isHovered ? 1.2 : 1})`
                }}
            />

            {/* Main content */}
            <div className="relative z-10 text-center px-6 max-w-2xl">
                {/* 404 with glitch effect */}
                <div className="relative mb-8">
                    <h1 className={`text-8xl md:text-9xl font-black bg-gradient-to-r from-pink-500 via-yellow-500 to-pink-500 bg-clip-text text-transparent ${glitchActive ? 'animate-pulse' : ''}`}>
                        404
                    </h1>
                    {glitchActive && (
                        <h1 className="absolute inset-0 text-8xl md:text-9xl font-black text-pink-500 opacity-50 animate-ping">
                            404
                        </h1>
                    )}
                    <div className="absolute -top-4 -right-4 animate-bounce">
                        <HiSparkles className="w-8 h-8 text-yellow-400" />
                    </div>
                </div>

                {/* Main message */}
                <div className="mb-8 space-y-4">
                    <h2 className="text-3xl md:text-4xl font-bold text-gray-800 mb-4">
                        Oops! Page Not Found
                    </h2>
                    <p className="text-lg text-gray-600 leading-relaxed">
                        Looks like you've ventured into the digital void. The page you're looking for has either been moved, deleted, or is hiding in another dimension.
                    </p>
                </div>

                {/* Search bar */}
                <div className="mb-8">
                    <div className="relative max-w-md mx-auto">
                        <input
                            type="text"
                            placeholder="Search for what you need..."
                            className="w-full px-6 py-4 bg-white border border-gray-300 rounded-2xl text-gray-800 placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-pink-500 focus:border-transparent transition-all duration-300 shadow-lg"
                            onFocus={() => setIsHovered(true)}
                            onBlur={() => setIsHovered(false)}
                        />
                        <FaSearch className="absolute right-4 top-1/2 transform -translate-y-1/2 text-gray-500 w-5 h-5" />
                    </div>
                </div>

                {/* Action buttons */}
                <div className="flex flex-col sm:flex-row gap-4 justify-center items-center">
                    <button className="group relative px-8 py-4 bg-gradient-to-r from-pink-500 to-yellow-500 hover:from-pink-600 hover:to-yellow-600 text-white font-semibold rounded-2xl transition-all duration-300 transform hover:scale-105 shadow-lg hover:shadow-pink-500/25 flex items-center gap-3">
                        <FaHome className="w-5 h-5" />
                        <span>Go Home</span>
                        <div className="absolute inset-0 bg-gradient-to-r from-pink-400 to-yellow-400 rounded-2xl blur opacity-0 group-hover:opacity-30 transition-opacity duration-300 -z-10" />
                    </button>
                    
                    <button className="group px-8 py-4 bg-white border border-gray-300 hover:bg-gray-50 text-gray-800 font-semibold rounded-2xl transition-all duration-300 transform hover:scale-105 flex items-center gap-3 shadow-lg">
                        <FaArrowLeft className="w-5 h-5" />
                        <span>Go Back</span>
                    </button>
                </div>

                {/* Fun fact */}
                <div className="mt-12 p-6 bg-white/90 backdrop-blur-sm rounded-2xl border border-gray-200 shadow-lg">
                    <div className="flex items-center justify-center gap-2 mb-3">
                        <FiZap className="w-5 h-5 text-yellow-500" />
                        <span className="text-sm font-semibold text-pink-600">DID YOU KNOW?</span>
                    </div>
                    <p className="text-gray-600 text-sm">
                        The first 404 error occurred at CERN in 1992. The room where the web servers were located was literally room 404!
                    </p>
                </div>
            </div>

            {/* Decorative elements */}
            <div className="absolute top-10 left-10 w-20 h-20 bg-gradient-to-r from-pink-500/20 to-yellow-500/20 rounded-full blur-xl animate-pulse" />
            <div className="absolute bottom-10 right-10 w-32 h-32 bg-gradient-to-r from-yellow-500/20 to-pink-500/20 rounded-full blur-xl animate-pulse" style={{ animationDelay: '1s' }} />
            <div className="absolute top-1/2 left-0 w-1 h-32 bg-gradient-to-b from-transparent via-pink-500 to-transparent opacity-20" />
            <div className="absolute top-1/2 right-0 w-1 h-32 bg-gradient-to-b from-transparent via-yellow-500 to-transparent opacity-20" />
        </div>
    );
}