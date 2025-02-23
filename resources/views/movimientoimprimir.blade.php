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
    Cajero: {{ $movimiento->user->name }}
    <br>
    Sucursal: {{ $movimiento->almacen_origen->name }}
    <br>
    
    
    Teléfono: {{ $movimiento->almacen_origen->telefono}}
    
    <br>
    @isset($movimiento->enviada_en)
    Fecha: {{ $movimiento->enviada_en }}
    @else
    Fecha: {{ getMysqlTimestamp($movimiento->user->configuration?->time_zone) }}
    @endisset

    <br>
    @if ($movimiento->estado == "C")
    Ticket Cancelado
    @endif
    <br>
    ===================
    <br>

    @foreach ($movimiento->articulos_ocs as $item)
    - {{ $item->product->name }}
    <br>
    {{ $item->cantidad_ordenada . ' x $' . $item->costo_al_ordenar . ' = ' . '$' . $item->total_al_ordenar }}
    <br>
    <br>
    @endforeach
    ===================
    <br>
    <p style="font-weight: bold ">
        Total: ${{ $movimiento->total_enviado }}
    </p>
    <br>


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