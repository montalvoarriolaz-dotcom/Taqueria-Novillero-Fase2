<?php
session_start();

// Validar seguridad de administrador
if (!isset($_SESSION['usuario'])) {
    header("Location: login.php");
    exit();
}

$servidor   = "localhost";
$usuario_db = "root";
$pass_db    = "";
$base_datos = "taqueria_novillero";

$conexion = new mysqli($servidor, $usuario_db, $pass_db, $base_datos);

if ($conexion->connect_error) {
    die("Error de conexión: " . $conexion->connect_error);
}

// Valores por defecto para los filtros (Día actual)
$filtro_tipo = isset($_POST['filtro_tipo']) ? $_POST['filtro_tipo'] : 'dia';
$fecha_busqueda = isset($_POST['fecha_busqueda']) ? $_POST['fecha_busqueda'] : date('Y-m-d');
$mes_busqueda = isset($_POST['mes_busqueda']) ? $_POST['mes_busqueda'] : date('Y-m');

// Construir la consulta SQL según el filtro seleccionado
if ($filtro_tipo == 'dia') {
    $titulo_reporte = "Reporte del Día: " . date('d/m/Y', strtotime($fecha_busqueda));
    $sql = "SELECT id_venta, id_pedido, total, cobrado_por, fecha_hora_cobro 
            FROM historial_ventas 
            WHERE DATE(fecha_hora_cobro) = '$fecha_busqueda' 
            ORDER BY fecha_hora_cobro DESC";
} else {
    $titulo_reporte = "Reporte del Mes: " . date('m/Y', strtotime($mes_busqueda . "-01"));
    $sql = "SELECT id_venta, id_pedido, total, cobrado_por, fecha_hora_cobro 
            FROM historial_ventas 
            WHERE DATE_FORMAT(fecha_hora_cobro, '%Y-%m') = '$mes_busqueda' 
            ORDER BY fecha_hora_cobro DESC";
}

$resultado = $conexion->query($sql);

// Calcular la suma total de las ganancias del periodo
$ganancias_totales = 0;
$ventas = [];

if ($resultado && $resultado->num_rows > 0) {
    while ($fila = $resultado->fetch_assoc()) {
        $ganancias_totales += $fila['total'];
        $ventas[] = $fila;
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reporte de Ventas - Taquería Novillero</title>
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
            max-width: 800px;
            background-color: #ffffff;
            padding: 25px;
            border-radius: 20px; /* Esquinas redondeadas rústicas */
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            border: 2px solid #d32f2f; /* Borde rojo del administrador */
        }

        h2 {
            color: #3d2514;
            text-transform: uppercase;
            letter-spacing: 1px;
            border-bottom: 3px double #d32f2f; /* Línea roja doble */
            padding-bottom: 10px;
            margin-top: 0;
            margin-bottom: 5px;
        }

        .sub-titulo {
            margin-top: 5px;
            margin-bottom: 25px;
            font-size: 14px;
            font-style: italic;
            color: #b71c1c;
            font-weight: bold;
        }

        /* Formulario de Filtros Estilizado */
        .filtro-box {
            background-color: #fcf8f2;
            padding: 20px;
            border-radius: 15px; /* Redondeado rústico */
            border: 1px dashed #3d2514;
            margin-bottom: 25px;
        }

        .filtro-opcion {
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            font-size: 14px;
            font-weight: bold;
        }

        .filtro-opcion input[type="radio"] {
            margin-right: 10px;
            accent-color: #d32f2f;
        }

        .filtro-opcion input[type="date"], 
        .filtro-opcion input[type="month"] {
            font-family: inherit;
            padding: 4px 8px;
            border: 1px solid #3d2514;
            border-radius: 8px; /* Inputs redondeados */
            margin-left: 10px;
            background-color: #ffffff;
            color: #2c1d11;
        }

        .btn-reporte {
            display: block;
            margin-top: 15px;
            padding: 10px 20px;
            font-weight: bold;
            font-family: inherit;
            background-color: #d32f2f; /* Rojo Quemado */
            color: #ffffff;
            border: none;
            cursor: pointer;
            text-transform: uppercase;
            font-size: 13px;
            border-radius: 10px; /* Redondeado */
            transition: background 0.2s;
            box-shadow: 0 3px 0 #991b1b;
        }

        .btn-reporte:hover {
            background-color: #b71c1c;
        }

        .btn-reporte:active {
            box-shadow: none;
            transform: translateY(3px);
        }

        h3 {
            color: #3d2514;
            text-transform: uppercase;
            font-size: 16px;
            margin-top: 20px;
            margin-bottom: 15px;
            border-left: 5px solid #d32f2f;
            padding-left: 10px;
        }

        /* Tarjeta Resumen de Caja Chica/Ganancias */
        .ganancias-box {
            background-color: #edf7ed;
            border-left: 6px solid #4caf50;
            padding: 15px;
            margin-bottom: 25px;
            border-radius: 12px; /* Redondeado */
            max-width: 320px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }

        .ganancias-lbl {
            font-size: 12px;
            color: #1e4620;
            font-weight: bold;
            letter-spacing: 0.5px;
        }

        .ganancias-val {
            font-size: 26px;
            color: #1e4620;
            font-weight: bold;
            display: block;
            margin-top: 5px;
        }

        /* Alerta sin ventas */
        .alerta-vacia {
            background-color: #fdf2f2;
            border-left: 6px solid #d32f2f;
            padding: 15px;
            border-radius: 12px;
            margin-bottom: 25px;
        }

        .alerta-vacia p {
            color: #b71c1c;
            font-weight: bold;
            margin: 0;
            font-size: 14px;
        }

        /* Tabla de Reportes */
        table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            margin-bottom: 25px;
            border-radius: 15px; /* Tabla redondeada */
            overflow: hidden;
            border: 1px solid #3d2514;
        }

        th {
            background-color: #2c1d11; /* Negro Carbón */
            color: #ffffff;
            padding: 12px;
            text-transform: uppercase;
            font-size: 12px;
            letter-spacing: 1px;
        }

        td {
            padding: 12px;
            background-color: #ffffff;
            border-bottom: 1px solid #f4ece1;
            color: #2c1d11;
            font-size: 14px;
        }

        tr:last-child td {
            border-bottom: none;
        }

        .btn-volver {
            display: block;
            width: 220px;
            margin: 10px auto 0 auto;
            padding: 12px;
            background-color: #2c1d11;
            color: #ffffff;
            text-decoration: none;
            text-align: center;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 13px;
            border-radius: 12px;
            transition: background 0.2s;
            box-shadow: 0 4px 0 #1c1007;
        }

        .btn-volver:hover {
            background-color: #d32f2f;
            box-shadow: 0 4px 0 #991b1b;
        }

        .btn-volver:active {
            box-shadow: none;
            transform: translateY(4px);
        }
    </style>
</head>
<body>

    <div class="container">
        <h2>Panel de Administración: Reportes y Ganancias</h2>
        <p class="sub-titulo">CORTES DE CAJA E INGRESOS DEL NEGOCIO</p>

        <form action="reporte_ventas.php" method="POST" class="filtro-box">
            <span style="font-size: 13px; text-transform: uppercase; font-weight: bold; display: block; margin-bottom: 15px; color: #3d2514;">
                Selecciona el tipo de reporte:
            </span>
            
            <div class="filtro-opcion">
                <input type="radio" name="filtro_tipo" value="dia" <?php echo ($filtro_tipo == 'dia') ? 'checked' : ''; ?>> 
                Por Fecha Específica: 
                <input type="date" name="fecha_busqueda" value="<?php echo $fecha_busqueda; ?>">
            </div>

            <div class="filtro-opcion">
                <input type="radio" name="filtro_tipo" value="mes" <?php echo ($filtro_tipo == 'mes') ? 'checked' : ''; ?>> 
                Por Mes Completo: 
                <input type="month" name="mes_busqueda" value="<?php echo $mes_busqueda; ?>">
            </div>

            <button type="submit" class="btn-reporte">Generar Reporte</button>
        </form>

        <h3><?php echo $titulo_reporte; ?></h3>

        <div class="ganancias-box">
            <span class="ganancias-lbl">GANANCIAS TOTALES DEL PERIODO:</span>
            <span class="ganancias-val">$<?php echo number_format($ganancias_totales, 2); ?></span>
        </div>

        <?php if (empty($ventas)): ?>
            <div class="alerta-vacia">
                <p>⚠️ Atención: No se registraron ventas ni movimientos de ganancias en el periodo seleccionado.</p>
            </div>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th style="text-align: center; width: 100px;">ID Venta</th>
                        <th style="text-align: center; width: 100px;">ID Pedido</th>
                        <th style="text-align: left; width: 150px;">Hora de Cobro</th>
                        <th style="text-align: left;">Cobrado Por</th>
                        <th style="text-align: right; width: 150px;">Monto Cobrado</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($ventas as $v): ?>
                        <tr>
                            <td style="text-align: center;">#<?php echo $v['id_venta']; ?></td>
                            <td style="text-align: center;">#<?php echo $v['id_pedido']; ?></td>
                            <td><?php echo date('H:i:s', strtotime($v['fecha_hora_cobro'])); ?> hrs</td>
                            <td><strong><?php echo htmlspecialchars($v['cobrado_por']); ?></strong></td>
                            <td style="text-align: right; font-weight: bold; color: #1e4620;">
                                $<?php echo number_format($v['total'], 2); ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>

        <a href="panel_admin.php" class="btn-volver">← Volver al Panel</a>
    </div>

</body>
</html>
<?php $conexion->close(); ?>