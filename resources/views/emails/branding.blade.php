<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
</head>
<body style="margin: 0; padding: 0; background-color: #f4f4f4; font-family: sans-serif;">
    <table width="100%" border="0" cellspacing="0" cellpadding="0">
        <tr>
            <td align="center" style="padding: 20px;">
                <table width="600" border="0" cellspacing="0" cellpadding="0" style="background-color: #ffffff; border-radius: 10px; overflow: hidden;">
                    <tr>
                        <td bgcolor="#2563eb" style="padding: 30px; text-align: center;">
                            <h1 style="color: #ffffff; margin: 0; font-size: 24px;">NAMA BRAND ANDA</h1>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 40px; color: #333333; line-height: 1.6;">
                            {!! nl2br(e($bodyContent)) !!}
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 20px; background-color: #f9fafb; text-align: center; font-size: 12px; color: #6b7280;">
                            &copy; {{ date('Y') }} Nama Brand Anda. All rights reserved.
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>