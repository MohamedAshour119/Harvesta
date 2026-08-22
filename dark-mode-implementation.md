# Dark Mode Implementation Plan

## Goal
Add a responsive, premium Dark Mode across the entire Cairo3A platform with smooth toggle transitions and persistent preferences.

## Tasks
- [ ] Task 1: Create ThemeContext.tsx and ThemeToggle.tsx component -> Verify: toggle updates html class and persists in localStorage
- [ ] Task 2: Configure Tailwind v4 @custom-variant dark and dark theme tokens in App.css -> Verify: .dark selector applies custom variables and base styling
- [ ] Task 3: Integrate ThemeToggle and dark classes into Header.tsx, HeaderDropdown.tsx, and FooterSection.tsx -> Verify: navbar and footer adapt to dark mode seamlessly
- [ ] Task 4: Apply dark styles to Product & Category Cards (ProductCard.tsx, CategoryCard.tsx, FavoriteItem.tsx, Placeholders) -> Verify: cards display with dark backgrounds, legible text, and clear borders
- [ ] Task 5: Apply dark styles to HomePage.tsx and all product slider carousels -> Verify: home page sections, swipers, and banners render cleanly in dark mode
- [ ] Task 6: Apply dark styles to Products.tsx, filter sidebars, search results, and ShowProduct.tsx details page -> Verify: catalog, search, and product details are fully styled in dark mode
- [ ] Task 7: Apply dark styles to CartPage.tsx, CheckoutPage.tsx, and Auth pages (Login, SignUp, ForgotPassword, ResetPassword) -> Verify: cart tables, checkout radio options, and auth cards look premium in dark mode
- [ ] Task 8: Apply dark styles to Dashboard, Profile, ChatWidget (AI Chatbot), and Modals/Popups -> Verify: dashboard tables, profile settings, and chatbot interface switch properly to dark theme
- [ ] Task 9: Run npm run build and test full platform UI -> Verify: Vite build succeeds with zero errors and real-time hot reload works

## Done When
- [ ] Platform supports one-click toggle between Dark and Light mode across all pages
- [ ] User choice is saved in localStorage and respected across reloads
- [ ] All components (Header, Footer, Cards, Home, Products, ShowProduct, Cart, Checkout, Auth, Dashboard, Chatbot, Modals) have high-contrast, premium styling
- [ ] npm run build passes with zero compilation errors

## Notes
- Palette: Deep Slate (#0F172A), Elevated Surfaces (#1E293B), Borders (#334155), Accents (#B12C2E / #E03E41 and #3B82F6).
- Full RTL and LTR support.
