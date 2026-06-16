<?php
session_start();

// Validar que el trabajador haya iniciado sesión
if (!isset($_SESSION['usuario'])) {
    header("Location: login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel Trabajador - Taquería Novillero</title>
    <style>
        body {
            background-color: #f4ece1; /* Fondo color hueso/arena */
            font-family: 'Courier New', Courier, monospace;
            color: #2c1d11; /* Café muy oscuro */
            margin: 0;
            padding: 20px;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .container {
            width: 100%;
            max-width: 700px;
            background-color: #ffffff;
            padding: 30px;
            border-radius: 20px; /* Esquinas redondeadas estilo rústico */
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            border: 2px solid #3d2514; /* Borde café madera */
        }

        .header-trabajador {
            text-align: center;
            border-bottom: 3px double #d32f2f; /* Línea roja doble tradicional */
            padding-bottom: 15px;
            margin-bottom: 25px;
        }

        h1 {
            color: #3d2514;
            text-transform: uppercase;
            margin: 0;
            font-size: 22px;
            letter-spacing: 1px;
        }

        .empleado-info {
            margin-top: 10px;
            font-size: 15px;
            font-weight: bold;
            color: #3d2514;
            text-transform: uppercase;
        }

        .empleado-info span {
            color: #d32f2f; /* Nombre en rojo quemado */
        }

        h3 {
            color: #3d2514;
            text-transform: uppercase;
            font-size: 15px;
            margin-top: 0;
            margin-bottom: 20px;
            border-left: 5px solid #d32f2f;
            padding-left: 10px;
        }

        .seccion-operacion {
            background-color: #fcf8f2;
            padding: 20px;
            border-radius: 15px; /* Redondeado interno */
            border: 1px dashed #3d2514;
            margin-bottom: 25px;
        }

        /* Botones grandes de cuadrícula para agilizar el toque */
        .menu-operativo {
            list-style: none;
            padding: 0;
            margin: 0;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 15px;
        }

        .menu-operativo li {
            margin: 0;
        }

        .menu-operativo a {
            display: block;
            background-color: #3d2514; /* Botones café cuero/madera */
            color: #ffffff;
            text-decoration: none;
            font-weight: bold;
            padding: 16px;
            border: 2px solid #2c1d11;
            border-radius: 12px; /* Puntas bien redondeadas */
            text-align: center;
            text-transform: uppercase;
            font-size: 13px;
            letter-spacing: 0.5px;
            transition: all 0.2s ease;
            box-shadow: 0 4px 0 #1c1007;
        }

        .menu-operativo a:hover {
            background-color: #d32f2f; /* Cambio a rojo al pasar el cursor */
            border-color: #991b1b;
            transform: translateY(-2px);
            box-shadow: 0 6px 0 #991b1b;
        }

        .menu-operativo a:active {
            transform: translateY(4px);
            box-shadow: none;
        }

        .btn-salir {
            display: block;
            width: 180px;
            margin: 10px auto 0 auto;
            padding: 10px;
            background-color: #2c1d11; /* Negro carbón */
            color: #ffffff;
            text-decoration: none;
            text-align: center;
            font-weight: bold;
            text-transform: uppercase;
            border-radius: 10px;
            font-size: 12px;
            transition: background 0.2s;
        }

        .btn-salir:hover {
            background-color: #b71c1c;
        }
    </style>
</head>
<body>

    <div class="container">
        <div class="header-trabajador">
            <h1>PANEL DE TRABAJADOR (COCINA / CAJA)</h1>
            <div class="empleado-info">Turno Activo: <span><?php echo htmlspecialchars($_SESSION['usuario']); ?></span></div>
        </div>

        <div class="seccion-operacion">
            <h3>Acciones de Operación en Turno</h3>
            <ul class="menu-operativo">
                <li><a href="hacer_pedido.php">Tomar nueva orden de tacos</a></li>
                <li><a href="historial_pedidos.php">Ver Historial de Pedidos</a></li>
                <li><a href="historial_ventas.php">Consultar Historial Ventas</a></li>
                <li><a href="cocina.php" class="btn">Cocina</a></li>
            </ul>
        </div>

        <a href="login.php" class="btn-salir">Cerrar Sesión / Salir</a>
    </div>

</body>
</html>