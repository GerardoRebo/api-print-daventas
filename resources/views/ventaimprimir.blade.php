<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Document</title>
</head>

<body onload="imprimir();">
    <br>
    <br>
    .
    <br>

    Cajero: {{ $ventaticket->user->name }}
    <br>
    Sucursal: {{ $ventaticket->almacen->name }}
    <br>
    Dirección: {{ $ventaticket->almacen->direccion }}
    <br>
    RFC: {{ $ventaticket->almacen->rfc }}
    <br>
    Teléfono: {{ $ventaticket->almacen->telefono}}
    <br>
    Ticket #: {{ $ventaticket->consecutivo}}
    
    <br>
    @isset($ventaticket->pago_en_hora)
    Fecha: {{ $ventaticket->pago_en_hora }}
    @else
    Fecha: {{ getMysqlTimestamp($ventaticket->user->configuration?->time_zone) }}
    @endisset

    <br>
    @if ($ventaticket->esta_cancelado)
    Ticket Cancelado
    @endif
    <br>
    ===================
    <br>

    @foreach ($ventaticket->ventaticket_articulos as $item)
    - {{ $item->product->name }}
    <br>
    {{ $item->cantidad . ' x $' . $item->precio_usado . ' = ' . '$' . $item->precio_final }}
    @if ($item->fue_devuelto)
    <br>
    Devolución -{{ $item->cantidad_devuelta }}
    @endif
    <br>
    <br>
    @endforeach
    ===================
    <br>
    <p style="font-weight: bold ">
        Total: ${{ $ventaticket->total - $ventaticket->total_devuelto }}
    </p>

    Pagó con: ${{ $ventaticket->pago_con }}
    <br>
    Su cambio: ${{ $ventaticket->total - $ventaticket->pago_con }}
    <br>

    Gracias por tu compra

    <br>
    <br>
    <br>
    <br>

    .
    <script LANGUAGE="JavaScript">
        function imprimir() {
            window.print();

        }
    </script>
</body>

</html>