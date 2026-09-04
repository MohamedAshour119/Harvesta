# Password Reset Throttling, Dual Rate Limiting & Countdown UI - Task Tracker

| # | Task | Phase | Status |
|---|------|-------|--------|
| 1 | Configure dynamic password reset throttle via `AUTH_PASSWORD_RESET_THROTTLE` in `config/auth.php` and `.env` | Backend | ✅ done |
| 2 | Implement Layer 2 per-IP rate limiter `forgot-password` (5 req/min) in `AppServiceProvider` & attach to `api.php` | Backend | ✅ done |
| 3 | Brand reset password email template with official SVG vector logo and ngrok public tunnel | Backend/Blade | ✅ done |
| 4 | Add localization keys (`resendCooldown`, `resendIn`, `resendButton`, `didNotReceive`) to `ar/auth.json` and `en/auth.json` | Frontend/i18n | ✅ done |
| 5 | Implement visual countdown timer (`FaClock`, 60s) and resend action with Dual Theme support in `LoginPrompt.tsx` | Frontend/UI | ✅ done |
| 6 | Verify TypeScript compilation (`tsc --noEmit`) and zero-hang execution | Verification | ✅ done |
| 7 | Generate Mandatory Theoretical & In-Depth Technical Report (`.md`) | Documentation | ✅ done |
| 8 | Refine email layout: move logo inside card, center gradient & heading, and remove redundant lock icon | Backend/Blade | ✅ done |
| 9 | Migrate frontend Header and FooterSection logos from SVG to PNG | Frontend | ✅ done |
| 10 | User Verification Checkpoint (`ask_question`) & Atomic Git Commit/Push | Git & Release | ✅ done |
