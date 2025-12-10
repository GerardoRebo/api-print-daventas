<table width="100%" cellspacing="0" cellpadding="0" style="font-family: Arial, sans-serif; padding: 24px; background:#fcfdff;">
    <tr>
        <td>

            <!-- Título -->
            <h2 style="color: #102540; margin-bottom: 6px; font-size: 22px;">
                Tu factura ya está disponible
            </h2>

            <!-- Introducción -->
            <p style="color: #333; font-size: 15px; line-height: 1.5;">
                @if ($senderName)
                    Hola, este es un correo automático enviado por <strong>{{ $senderName }}</strong>.
                @else
                    Hola, este es un correo automático enviado por <strong>{{ config('app.name') }}</strong>.
                @endif
                Tus archivos estarán disponibles durante los próximos <strong>{{ $daysValid }} días</strong>.
            </p>

            <!-- Botón PDF -->
            <p style="margin-top: 24px;">
                <a href="{{ $pdfUrl }}"
                    style="display:inline-block; background:#3084f2; color:white; padding:12px 18px; border-radius:8px; text-decoration:none; font-weight:bold;">
                    Descargar PDF
                </a>
            </p>

            <!-- Botón XML -->
            <p>
                <a href="{{ $xmlUrl }}"
                    style="display:inline-block; background:#102540; color:white; padding:12px 18px; border-radius:8px; text-decoration:none; font-weight:bold;">
                    Descargar XML
                </a>
            </p>

            <!-- Línea gris separadora -->
            <hr style="border:none; border-top:1px solid #cacbcc; margin:30px 0;">

            <!-- Bloque promocional suave -->
            <table width="100%" cellpadding="0" cellspacing="0" style="background:#F2F2F2; padding:18px; border-radius:10px;">
                <tr>
                    <td>
                        <h3 style="margin:0 0 8px 0; color:#102540; font-size:18px;">
                            Daventas: tu sistema de punto de venta y facturación
                        </h3>

                        <p style="color:#333; font-size:14px; line-height:1.5; margin:0;">
                            ¿Tienes un negocio? Administra tus ventas, inventarios y facturación
                            con una plataforma moderna, rápida y fácil de usar.
                        </p>

                        <p style="margin-top:12px;">
                            <a href="https://daventas.com"
                                style="background:#f2b90f; color:#102540; padding:10px 16px; border-radius:8px; 
                                       text-decoration:none; font-weight:bold;">
                                Conocer más
                            </a>
                        </p>
                    </td>
                </tr>
            </table>

            <!-- Pie de página -->
            <p style="color:#888; font-size:12px; margin-top:30px; line-height:1.4;">
                Si tienes dudas o este correo no era para ti, por favor ignóralo.
            </p>

        </td>
    </tr>
</table>
