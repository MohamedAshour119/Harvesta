@php
  $isRtl = ($locale ?? app()->getLocale()) === 'ar';
  $assetBaseUrl = env('MAIL_ASSET_URL') ?: (config('app.asset_url') ?: config('app.url'));
  $defaultLogo = rtrim($assetBaseUrl, '/') . '/' . ($isRtl ? 'logo-ar.png' : 'logo.png');
  $logoUrl = $isRtl 
    ? (env('MAIL_LOGO_AR_URL') ?: $defaultLogo) 
    : (env('MAIL_LOGO_EN_URL') ?: $defaultLogo);
@endphp
<!DOCTYPE html>
<html lang="{{ $isRtl ? 'ar' : 'en' }}" dir="{{ $isRtl ? 'rtl' : 'ltr' }}">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta http-equiv="X-UA-Compatible" content="ie=edge">
  <title>{{ $isRtl ? 'إعادة تعيين كلمة المرور - هارفستا' : 'Reset Password - Harvesta' }}</title>
  <style>
    @import url('https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800&display=swap');
    body, table, td, a { -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%; }
    table, td { mso-table-lspace: 0pt; mso-table-rspace: 0pt; }
    img { -ms-interpolation-mode: bicubic; border: 0; outline: none; text-decoration: none; }
    body {
      margin: 0;
      padding: 0;
      width: 100% !important;
      height: 100% !important;
      background-color: #F1F5F9;
      font-family: 'Cairo', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Arial, sans-serif;
    }
    @media only screen and (max-width: 620px) {
      .email-container {
        width: 100% !important;
        padding: 12px !important;
      }
      .content-card {
        padding: 24px 18px !important;
      }
      .cta-button {
        display: block !important;
        width: 100% !important;
        box-sizing: border-box !important;
        text-align: center !important;
      }
    }
  </style>
</head>
<body style="margin: 0; padding: 0; background-color: #F1F5F9; font-family: 'Cairo', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Arial, sans-serif; color: #1E293B; direction: {{ $isRtl ? 'rtl' : 'ltr' }};">
  <table border="0" cellpadding="0" cellspacing="0" width="100%" style="background-color: #F1F5F9; padding: 40px 10px;">
    <tr>
      <td align="center">
        <!-- Main Email Container -->
        <table border="0" cellpadding="0" cellspacing="0" width="100%" class="email-container" style="max-width: 580px; margin: 0 auto;">
          
          <!-- Main White Card -->
          <tr>
            <td>
              <table border="0" cellpadding="0" cellspacing="0" width="100%" class="content-card" style="background-color: #FFFFFF; border-radius: 20px; box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05), 0 8px 10px -6px rgba(0, 0, 0, 0.03); border: 1px solid #E2E8F0; overflow: hidden; padding: 40px 32px;">
                
                <!-- Brand Logo Inside Card -->
                <tr>
                  <td align="center" style="padding-bottom: 18px;">
                    <a href="{{ config('app.url') }}" target="_blank" style="text-decoration: none; display: inline-block;">
                      <img src="{{ $logoUrl }}" alt="Harvesta" height="48" style="display: block; height: 48px; width: auto; max-width: 220px; border: 0; outline: none; margin: 0 auto;" />
                    </a>
                  </td>
                </tr>

                <!-- Accent Top Gradient Line (Centered) -->
                <tr>
                  <td align="center" style="padding-bottom: 24px;">
                    <div style="height: 4px; width: 64px; background: linear-gradient(90deg, #B12C2E 0%, #E03E41 50%, #F59E0B 100%); border-radius: 4px; margin: 0 auto;"></div>
                  </td>
                </tr>

                <!-- Heading (Centered) -->
                <tr>
                  <td align="center" style="padding-bottom: 24px;">
                    <h1 style="margin: 0; font-size: 22px; font-weight: 800; color: #0F172A; text-align: center; line-height: 1.3;">
                      {{ $isRtl ? 'طلب إعادة تعيين كلمة المرور' : 'Password Reset Request' }}
                    </h1>
                  </td>
                </tr>

                <!-- Greeting & Body Text -->
                <tr>
                  <td align="{{ $isRtl ? 'right' : 'left' }}" style="padding-bottom: 28px; font-size: 15px; line-height: 1.8; color: #334155;">
                    <p style="margin: 0 0 14px 0; font-weight: 700; color: #1E293B;">
                      {{ $isRtl ? 'مرحباً' : 'Hello' }}{{ !empty($user->full_name) ? ' ' . $user->full_name : '' }} 👋
                    </p>
                    <p style="margin: 0;">
                      {{ $isRtl 
                        ? 'تلقينا طلباً لإعادة تعيين كلمة المرور الخاصة بحسابك على منصة هارفستا. لإنشاء كلمة مرور جديدة وتأمين دخولك، يرجى النقر على الزر أدناه:' 
                        : 'We received a request to reset your password for your Harvesta account. To choose a new password and secure your access, please click the button below:' 
                      }}
                    </p>
                  </td>
                </tr>

                <!-- Primary CTA Button -->
                <tr>
                  <td align="center" style="padding-bottom: 32px;">
                    <table border="0" cellpadding="0" cellspacing="0" style="margin: 0 auto;">
                      <tr>
                        <td align="center" style="border-radius: 12px; background: linear-gradient(135deg, #B12C2E 0%, #902123 100%);">
                          <a href="{{ $url }}" target="_blank" class="cta-button" style="display: inline-block; padding: 14px 38px; font-family: 'Cairo', Arial, sans-serif; font-size: 15px; font-weight: 700; color: #FFFFFF !important; text-decoration: none; border-radius: 12px; letter-spacing: 0.2px;">
                            {{ $isRtl ? 'إعادة تعيين كلمة المرور' : 'Reset Password' }}
                          </a>
                        </td>
                      </tr>
                    </table>
                  </td>
                </tr>

                <!-- Expiry & Security Notice Box -->
                <tr>
                  <td style="padding-bottom: 28px;">
                    <table border="0" cellpadding="0" cellspacing="0" width="100%" style="background-color: #FFFBEB; border-radius: 12px; border: 1px solid #FDE68A; padding: 16px;">
                      <tr>
                        <td align="{{ $isRtl ? 'right' : 'left' }}" style="font-size: 13px; line-height: 1.6; color: #92400E;">
                          <div style="font-weight: 700; margin-bottom: 4px; display: flex; align-items: center; gap: 6px;">
                            <span>⚠️</span>
                            <span>{{ $isRtl ? 'معلومات أمنية هامة:' : 'Important Security Notice:' }}</span>
                          </div>
                          <ul style="margin: 6px 0 0 0; padding-{{ $isRtl ? 'right' : 'left' }}: 18px;">
                            <li style="margin-bottom: 4px;">
                              {{ $isRtl 
                                ? 'هذا الرابط متاح للاستخدام لمرة واحدة فقط وينتهي بعد ' . ($count ?? 60) . ' دقيقة.' 
                                : 'This password reset link is single-use and will expire in ' . ($count ?? 60) . ' minutes.' 
                              }}
                            </li>
                            <li>
                              {{ $isRtl 
                                ? 'إذا لم تقم بطلب إعادة التعيين بنفسك، فلا داعي للقلق؛ حسابك في أمان تام ويمكنك تجاهل هذه الرسالة.' 
                                : 'If you did not request a password reset, no further action is required; your account is secure.' 
                              }}
                            </li>
                          </ul>
                        </td>
                      </tr>
                    </table>
                  </td>
                </tr>

                <!-- Raw URL Fallback -->
                <tr>
                  <td align="{{ $isRtl ? 'right' : 'left' }}" style="padding-top: 20px; border-top: 1px solid #F1F5F9; font-size: 12px; line-height: 1.6; color: #64748B;">
                    <p style="margin: 0 0 8px 0;">
                      {{ $isRtl 
                        ? 'إذا واجهتك أي مشكلة في النقر على الزر أعلاه، يمكنك نسخ ولصق الرابط التالي مباشرة في متصفحك:' 
                        : 'If you are having trouble clicking the "Reset Password" button, copy and paste the URL below into your web browser:' 
                      }}
                    </p>
                    <div style="word-break: break-all; font-family: monospace; font-size: 11px; color: #B12C2E; background-color: #F8FAFC; border: 1px solid #E2E8F0; padding: 10px 12px; border-radius: 8px; direction: ltr; text-align: left;">
                      {{ $url }}
                    </div>
                  </td>
                </tr>

              </table>
            </td>
          </tr>

          <!-- Footer Area -->
          <tr>
            <td align="center" style="padding-top: 28px; font-size: 12px; line-height: 1.7; color: #94A3B8;">
              <p style="margin: 0 0 6px 0; font-weight: 600; color: #64748B;">
                {{ $isRtl ? 'منصة هارفستا - شريكك الموثوق في التجارة الزراعية' : 'Harvesta Platform - Your Trusted Partner in Agricultural Commerce' }}
              </p>
              <p style="margin: 0;">
                © {{ date('Y') }} Harvesta. {{ $isRtl ? 'جميع الحقوق محفوظة.' : 'All rights reserved.' }}
              </p>
            </td>
          </tr>

        </table>
      </td>
    </tr>
  </table>
</body>
</html>
