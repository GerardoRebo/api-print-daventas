<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Verificacion Exitosa Daventas</title>

    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700&display=swap" rel="stylesheet">
    <!-- Styles -->
    <style>
        body {
            font-family: 'Nunito', sans-serif;
            background-color: gainsboro;
            display: flex;
            flex-direction: row;
            justify-content: center;
            align-items: baseline;
            height: 100vh;
            width: 100vw;
            box-sizing: content-box;
            margin: 0;

        }

        .fram {}

        .card {
            background-color: rgb(247, 247, 247);
            border-radius: 30px;
            box-shadow: 20px;
            margin-top: 40px;
            margin: 2em 1em;
            padding: 2em 2em 2em 2em;
        }

        .header {
            font-size: 40px;
            font-weight: bolder;
        }

        .body {
            padding: 1em;
            font-size: 30px;
        }

        .gray-color {
            color: #111827;
        }

        .orange-color {
            color: #ea580c;
        }
    </style>
    <script defer>
        setTimeout(function() {
            window.close();
        }, 20000); // 20000 milliseconds = 20 seconds 
    </script>
</head>

<body class="">
    <div class="frame">
        <div class="card">
            <div class="header orange-color">
                La verificación de tu email ha sido exitosa
            </div>
            <hr>
            <div class="body gray-color">
                <div class="">
                    Ya puedes cerrar esta ventana de forma segura, y regresar a la aplicacion.
                </div>
                <div style="margin-top: 2em; font-size:20px">
                    Esta ventana se cerrara automaticamente en 20 segundos...
                </div>
            </div>
        </div>
    </div>
</body>

</html>