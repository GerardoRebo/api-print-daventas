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

    Cajero: {{ $ticket->user->name }}
    <br>
    Sucursal: {{ $ticket->ventaticket->almacen->name }}
    <br>
    
    Teléfono: {{ $ticket->ventaticket->almacen->telefono}}
    
    <br>
    @isset($ticket->devuelto_en)
    Fecha: {{ $ticket->devuelto_en }}
    @endisset
    <p style="font-weight:bold">Devolución!!!</p>
    <br>
    ===================
    <br>

    @foreach ($ticket->devoluciones_articulos as $item)
    - {{ $item->product->name }}
    <br>
    {{ $item->cantidad_devuelta .' -- $' . $item->dinero_devuelto }}
    <br>
    @endforeach
    ===================
    <br>
    <p style="font-weight: bold ">
        Total: ${{ $ticket->total_devuelto }}
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