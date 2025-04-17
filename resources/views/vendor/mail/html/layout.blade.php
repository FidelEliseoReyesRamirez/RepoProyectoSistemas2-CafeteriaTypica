<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cafetería Típica - Notificación</title>
</head>
<body style="
    margin: 0;
    padding: 0;
    width: 100%;
    font-family: 'Instrument Sans', serif;
    background-color: hsl(30, 60%, 95%);
    color: hsl(32, 25%, 20%);
    background-image: url('https://d1bvpoagx8hqbg.cloudfront.net/originals/experiencia-la-paz-bolivia-wara-c1c24d25cdb9259f80ae30ae679fee58.jpg');
    background-size: cover;
    background-position: center;
    background-repeat: no-repeat;
">

    <table width="100%" cellpadding="0" cellspacing="0">
        <tr>
            <td align="center" style="padding: 40px 16px;">
                <table cellpadding="0" cellspacing="0" role="presentation" style="
                    max-width: 480px;
                    width: 100%;
                    background-color: hsla(30, 60%, 98%, 0.94);
                    border-radius: 16px;
                    padding: 40px 32px;
                    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.15);
                    backdrop-filter: blur(6px);
                ">
                    {{-- Header --}}
                    {{ $header ?? '' }}

                    {{-- Email Body --}}
                    <tr>
                        <td>
                            {{ $slot }}
                        </td>
                    </tr>

                    {{-- Subcopy --}}
                    @if (! empty($subcopy ?? ''))
                        <tr>
                            <td style="padding-top: 24px;">
                                {{ $subcopy }}
                            </td>
                        </tr>
                    @endif

                    {{-- Footer --}}
                    <tr>
                        <td style="padding-top: 40px;">
                            {{ $footer ?? '' }}
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>
</body>
</html>
