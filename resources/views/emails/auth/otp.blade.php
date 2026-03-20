<!DOCTYPE html>
<html lang="{{ $locale }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('auth.mail.otp_subject') }}</title>
<style>
    body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; background-color: #f4f7f6; margin: 0; padding: 0; }
    .wrapper { width: 100%; table-layout: fixed; background-color: #f4f7f6; padding-bottom: 40px; }
    .main { background-color: #ffffff; margin: 0 auto; width: 100%; max-width: 600px; border-spacing: 0; color: #4a5568; border-radius: 8px; overflow: hidden; margin-top: 40px; }
    .header { background-color: #2d3748; padding: 20px; text-align: center; color: #ffffff; font-size: 20px; font-weight: bold; }
    .content { padding: 30px; line-height: 1.6; }
    .otp-container { background-color: #edf2f7; border: 2px dashed #cbd5e0; margin: 20px 0; padding: 20px; text-align: center; }
    .otp-code { font-size: 36px; font-weight: 800; letter-spacing: 10px; color: #2b6cb0; margin: 0; }
    .footer { text-align: center; font-size: 12px; color: #a0aec0; padding: 20px; }
</style>
</head>
<body>
<center class="wrapper">
    <table class="main" width="100%">
        <tr>
            <td class="header">
                {{ config('app.name') }}
            </td>
        </tr>
        <tr>
            <td class="content">
                <p style="font-size: 16px;">{{ __('auth.mail.otp.greeting') }}</p>
                <p>{{ __('auth.mail.otp.otp_message') }}</p>

                <div class="otp-container">
                    <h1 class="otp-code">{{ $otp }}</h1>
                </div>

                <p>{{ __('auth.mail.otp.warning') }}</p>
                <p>{{ __('auth.mail.otp.thanks') }}<br><strong>{{ config('app.name') }} Team</strong></p>
            </td>
        </tr>
    </table>

    <table width="100%">
        <tr>
            <td class="footer">
                &copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.
            </td>
        </tr>
    </table>
</center>
</body>
</html>
