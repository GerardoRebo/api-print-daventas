<table width="100%" cellspacing="0" cellpadding="0" style="font-family: Arial, sans-serif; padding: 20px;">
    <tr>
        <td>
            <h2 style="color: #333; margin-bottom: 10px;">
                Tu factura ya está disponible
            </h2>

            <p style="color: #555;">
                @if ($sender)
                    Hola, este es un correo automático enviado por {{ $sender }}.
                @else
                    Hola, este es un correo automático enviado por {{ config('app.name') }}.
                @endif
                Tus archivos estarán disponibles durante los próximos <strong>{{ $daysValid }} días</strong>.
            </p>

            <p style="margin-top: 20px;">
                <a href="{{ $pdfUrl }}"
                    style="display:inline-block; background:#4CAF50; color:white; padding:10px 16px; border-radius:6px; text-decoration:none;">
                    Descargar PDF
                </a>
            </p>

            <p>
                <a href="{{ $xmlUrl }}"
                    style="display:inline-block; background:#2196F3; color:white; padding:10px 16px; border-radius:6px; text-decoration:none;">
                    Descargar XML
                </a>
            </p>

            <p style="color:#888; font-size:12px; margin-top:30px;">
                Si tienes dudas o este correo no era para ti, por favor ignóralo.
            </p>

        </td>
    </tr>
</table>
