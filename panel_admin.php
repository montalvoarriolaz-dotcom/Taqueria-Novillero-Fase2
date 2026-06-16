<?php
session_start();

// Validar que el administrador haya iniciado sesión
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
    <title>Panel Administrador - Taquería Novillero</title>
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
            max-width: 750px;
            background-color: #ffffff;
            padding: 30px;
            border-radius: 20px; /* Sin puntas, esquinas redondeadas rústicas */
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            border: 2px solid #3d2514; /* Borde café madera */
        }

        .header-admin {
            text-align: center;
            border-bottom: 3px double #d32f2f; /* Línea roja doble */
            padding-bottom: 15px;
            margin-bottom: 25px;
        }

        h1 {
            color: #3d2514;
            text-transform: uppercase;
            margin: 0;
            font-size: 24px;
            letter-spacing: 1px;
        }

        .bienvenida {
            margin-top: 10px;
            font-size: 16px;
            font-weight: bold;
            color: #d32f2f; /* Rojo quemado */
            text-transform: uppercase;
        }

        h3 {
            color: #3d2514;
            text-transform: uppercase;
            font-size: 15px;
            margin-top: 0;
            margin-bottom: 15px;
            border-left: 5px solid #3d2514;
            padding-left: 10px;
        }

        .seccion-panel {
            background-color: #fcf8f2;
            padding: 20px;
            border-radius: 15px; /* Redondeado interno */
            border: 1px dashed #3d2514;
            margin-bottom: 25px;
        }

        /* Estilo de lista de enlaces como botones limpios */
        .menu-links {
            list-style: none;
            padding: 0;
            margin: 0;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 12px;
        }

        .menu-links li {
            margin: 0;
        }

        .menu-links a {
            display: block;
            background-color: #ffffff;
            color: #2c1d11;
            text-decoration: none;
            font-weight: bold;
            padding: 12px;
            border: 2px solid #3d2514;
            border-radius: 10px; /* Redondeado */
            text-align: center;
            text-transform: uppercase;
            font-size: 13px;
            transition: all 0.2s ease;
            box-shadow: 0 3px 0 #3d2514;
        }

        .menu-links a:hover {
            background-color: #3d2514;
            color: #ffffff;
            transform: translateY(-2px);
            box-shadow: 0 5px 0 #1c1007;
        }

        .menu-links a:active {
            transform: translateY(3px);
            box-shadow: none;
        }

        /* Variación de color para destacar herramientas de dueño */
        .admin-box a {
            border-color: #d32f2f;
            box-shadow: 0 3px 0 #d32f2f;
            color: #d32f2f;
        }

        .admin-box a:hover {
            background-color: #d32f2f;
            color: #ffffff;
            box-shadow: 0 5px 0 #991b1b;
        }

        .btn-salir {
            display: block;
            width: 200px;
            margin: 10px auto 0 auto;
            padding: 10px;
            background-color: #2c1d11; /* Negro carbón */
            color: #ffffff;
            text-decoration: none;
            text-align: center;
            font-weight: bold;
            text-transform: uppercase;
            border-radius: 10px;
            font-size: 13px;
            transition: background 0.2s;
        }

        .btn-salir:hover {
            background-color: #d32f2f;
        }
    </style>
</head>
<body>

    <div class="container">
        <div class="header-admin">
            <h1>Panel de Administración</h1>
            <div class="bienvenida">Bienvenido, Jefe: <?php echo htmlspecialchars($_SESSION['usuario']); ?></div>
        </div>

        <!-- SECCIÓN 1: OPERACIÓN -->
        <div class="seccion-panel">
            <h3>Acciones de Operación (Funciones de Empleado)</h3>
            <ul class="menu-links">
                <li><a href="hacer_pedidoA.php">Tomar nueva orden</a></li>
                <li><a href="historial_pedidosA.php">Pedidos Activos</a></li>
                <li><a href="historial_ventasA.php">Ver Historial de Ventas</a></li>
		<li><a href="cocinaA.php" class="btn">Cocina</a></li>
            </ul>
        </div>

        <!-- SECCIÓN 2: HERRAMIENTAS EXCLUSIVAS -->
        <div class="seccion-panel admin-box">
            <h3>Herramientas de Administrador (Exclusivo)</h3>
            <ul class="menu-links">
                <li><a href="reporte_ventas.php">Reporte de Ventas Totales</a></li>
                <li><a href="cambiar_precios.php">Modificar Menú</a></li>
                <li><a href="gestionar_usuarios.php">Gestionar Usuarios</a></li>
                <li><a href="gestionar_inventario.php">Gestionar Inventario</a></li>
            </ul>
        </div>

        <a href="login.php" class="btn-salir">Cerrar Sesión / Salir</a>
    </div>

</body>
</html>