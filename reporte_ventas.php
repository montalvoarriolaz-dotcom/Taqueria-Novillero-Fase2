<?php
session_start();

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

// 1. OBTENER LISTA DE TRABAJADORES
$lista_empleados = [];
$sql_usuarios = "SELECT usuario FROM usuarios ORDER BY usuario ASC";
$res_usuarios = $conexion->query($sql_usuarios);

if ($res_usuarios && $res_usuarios->num_rows > 0) {
    while ($u = $res_usuarios->fetch_assoc()) {
        $lista_empleados[] = $u['usuario'];
    }
}

// Valores de filtros
$filtro_tipo = isset($_POST['filtro_tipo']) ? $_POST['filtro_tipo'] : 'dia';
$fecha_busqueda = isset($_POST['fecha_busqueda']) ? $_POST['fecha_busqueda'] : date('Y-m-d');
$mes_busqueda = isset($_POST['mes_busqueda']) ? $_POST['mes_busqueda'] : date('Y-m');
$empleado_filtro = isset($_POST['empleado_filtro']) ? $_POST['empleado_filtro'] : 'todos';

// Condiciones Base
$condicion_fecha_ventas = ($filtro_tipo == 'dia') ? "DATE(fecha_hora_cobro) = '$fecha_busqueda'" : "DATE_FORMAT(fecha_hora_cobro, '%Y-%m') = '$mes_busqueda'";
$condicion_empleado = ($empleado_filtro !== 'todos') ? " AND cobrado_por = '" . $conexion->real_escape_string($empleado_filtro) . "'" : "";

// Consulta de Ventas Principal
$titulo_reporte = ($filtro_tipo == 'dia') ? "Reporte del Día: " . date('d/m/Y', strtotime($fecha_busqueda)) : "Reporte del Mes: " . date('m/Y', strtotime($mes_busqueda . "-01"));

$sql = "SELECT id_venta, id_pedido, total, cobrado_por, fecha_hora_cobro 
        FROM historial_ventas 
        WHERE $condicion_fecha_ventas $condicion_empleado 
        ORDER BY fecha_hora_cobro DESC";

$resultado = $conexion->query($sql);

$ganancias_totales = 0;
$ventas = [];
if ($resultado && $resultado->num_rows > 0) {
    while ($fila = $resultado->fetch_assoc()) {
        $ganancias_totales += $fila['total'];
        $ventas[] = $fila;
    }
}

// 2. CÁLCULO DEL TOP 10 DE PRODUCTOS MÁS VENDIDOS RELACIONAL
$top_productos = [];

$condicion_fecha_productos = ($filtro_tipo == 'dia') ? "DATE(hv.fecha_hora_cobro) = '$fecha_busqueda'" : "DATE_FORMAT(hv.fecha_hora_cobro, '%Y-%m') = '$mes_busqueda'";
$condicion_empleado_prod = ($empleado_filtro !== 'todos') ? " AND hv.cobrado_por = '" . $conexion->real_escape_string($empleado_filtro) . "'" : "";

$sql_top = "SELECT p.nombre AS nombre_producto, SUM(dp.cantidad) AS total_vendido 
            FROM detalle_pedidos dp
            INNER JOIN productos p ON dp.id_producto = p.id
            INNER JOIN historial_ventas hv ON dp.id_pedido = hv.id_pedido
            WHERE $condicion_fecha_productos $condicion_empleado_prod
            GROUP BY dp.id_producto, p.nombre
            ORDER BY total_vendido DESC 
            LIMIT 10";

$res_top = $conexion->query($sql_top);

if ($res_top && $res_top->num_rows > 0) {
    while ($prod = $res_top->fetch_assoc()) {
        $top_productos[] = $prod;
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reportes de Ventas - Taquería Novillero</title>
    <style>
        body { background-color: #f4ece1; font-family: 'Courier New', Courier, monospace; color: #2c1d11; margin: 0; padding: 20px; display: flex; flex-direction: column; align-items: center; }
        .container { width: 100%; max-width: 900px; background-color: #ffffff; padding: 25px; border-radius: 20px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); border: 2px solid #d32f2f; }
        h2 { color: #3d2514; text-transform: uppercase; border-bottom: 3px double #d32f2f; padding-bottom: 10px; margin: 0 0 5px 0; }
        .sub-titulo { margin-bottom: 25px; font-size: 14px; font-style: italic; color: #b71c1c; font-weight: bold; }
        
        .filtro-bloque { background-color: #fcf8f2; border: 1px dashed #3d2514; padding: 20px; border-radius: 12px; margin-bottom: 25px; }
        .filtro-grid { display: flex; flex-direction: column; gap: 15px; }
        .filtro-linea { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; font-size: 14px; font-weight: bold; }
        .input-control { font-family: inherit; padding: 6px 10px; border: 1px solid #3d2514; border-radius: 6px; background-color: #ffffff; color: #2c1d11; }

        .btn-enviar-reporte { background-color: #d32f2f; color: #ffffff; padding: 12px 24px; border: none; border-radius: 10px; cursor: pointer; font-weight: bold; text-transform: uppercase; font-family: inherit; font-size: 14px; box-shadow: 0 4px 0 #991b1b; margin-top: 5px; align-self: flex-start; }
        .btn-enviar-reporte:hover { background-color: #b71c1c; }
        .btn-enviar-reporte:active { box-shadow: none; transform: translateY(4px); }
        
        .dashboard-row { display: flex; gap: 20px; margin-bottom: 25px; flex-wrap: wrap; }
        .box-info { flex: 1; min-width: 250px; padding: 15px; border-radius: 12px; border: 1px solid #3d2514; background-color: #fcf8f2; }
        .ganancias-val { font-size: 28px; font-weight: bold; color: #1e4620; display: block; margin-top: 5px; }
        
        table { width: 100%; border-collapse: separate; border-spacing: 0; border: 1px solid #3d2514; border-radius: 15px; overflow: hidden; margin-bottom: 25px; }
        th { background-color: #2c1d11; color: #ffffff; padding: 12px; font-size: 12px; text-transform: uppercase; }
        td { padding: 12px; border-bottom: 1px solid #f4ece1; font-size: 13px; }
        tr:last-child td { border-bottom: none; }
        
        .btn-volver { display: block; width: 220px; margin: 10px auto 0 auto; padding: 12px; background-color: #2c1d11; color: #ffffff; text-decoration: none; text-align: center; font-weight: bold; text-transform: uppercase; font-size: 13px; border-radius: 12px; box-shadow: 0 4px 0 #1c1007; }
        .btn-volver:hover { background-color: #d32f2f; box-shadow: 0 4px 0 #991b1b; }
        .btn-volver:active { box-shadow: none; transform: translateY(4px); }
    </style>
</head>
<body>

<div class="container">
    <h2>Reportes y Ganancias</h2>
    <p class="sub-titulo">AUDITORÍA DE CAJA - CONFIGURACIÓN DE FILTROS</p>

    <form action="reporte_ventas.php" method="POST" class="filtro-bloque">
        <div class="filtro-grid">
            <div class="filtro-linea">
                <input type="radio" name="filtro_tipo" value="dia" <?php echo ($filtro_tipo == 'dia') ? 'checked' : ''; ?> style="accent-color: #d32f2f;"> 
                <span>Por Fecha:</span>
                <input type="date" name="fecha_busqueda" value="<?php echo $fecha_busqueda; ?>" class="input-control">
            </div>

            <div class="filtro-linea">
                <input type="radio" name="filtro_tipo" value="mes" <?php echo ($filtro_tipo == 'mes') ? 'checked' : ''; ?> style="accent-color: #d32f2f;"> 
                <span>Por Mes Completo:</span>
                <input type="month" name="mes_busqueda" value="<?php echo $mes_busqueda; ?>" class="input-control">
            </div>

            <div class="filtro-linea" style="margin-top: 5px; border-top: 1px dashed #c4bcae; padding-top: 10px;">
                <span>Filtrar por Empleado:</span>
                <select name="empleado_filtro" class="input-control" style="min-width: 200px;">
                    <option value="todos" <?php echo ($empleado_filtro == 'todos') ? 'selected' : ''; ?>>-- TODOS LOS EMPLEADOS --</option>
                    <?php foreach ($lista_empleados as $emp): ?>
                        <option value="<?php echo htmlspecialchars($emp); ?>" <?php echo ($empleado_filtro == $emp) ? 'selected' : ''; ?>>
                            <?php echo strtoupper(htmlspecialchars($emp)); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <button type="submit" class="btn-enviar-reporte">📊 Generar Reporte</button>
        </div>
    </form>

    <h3 style="text-transform: uppercase; font-size: 14px; border-left: 4px solid #d32f2f; padding-left: 8px; margin-top: 30px;">
        <?php echo $titulo_reporte; ?>
    </h3>

    <div class="dashboard-row">
        <div class="box-info" style="border-left: 5px solid #4caf50;">
            <span style="font-size: 11px; font-weight: bold; color: #1e4620;">TOTAL EN CAJA</span>
            <span class="ganancias-val">$<?php echo number_format($ganancias_totales, 2); ?></span>
            <span style="font-size: 11px; color: #b71c1c; font-weight: bold;">
                Empleado: <?php echo strtoupper(htmlspecialchars($empleado_filtro)); ?>
            </span>
        </div>
        
        <div class="box-info" style="border-left: 5px solid #d32f2f;">
            <span style="font-size: 11px; font-weight: bold; color: #3d2514;">🔥 TOP 10 MÁS VENDIDOS</span>
            <ol style="font-size: 12px; margin: 8px 0 0 0; padding-left: 20px; line-height: 1.5; color: #2c1d11;">
                <?php if (!empty($top_productos)): ?>
                    <?php foreach ($top_productos as $p): ?>
                        <li><strong><?php echo htmlspecialchars($p['nombre_producto']); ?></strong> (<?php echo $p['total_vendido']; ?> un.)</li>
                    <?php endforeach; ?>
                <?php else: ?>
                    <li style="list-style: none; margin-left: -20px; font-style: italic; color: #7a6b5c;">
                        No hay productos vendidos en este periodo.
                    </li>
                <?php endif; ?>
            </ol>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th style="text-align: center; width: 110px;">Venta ID</th>
                <th style="text-align: center; width: 110px;">Pedido ID</th>
                <th>Hora de Cobro</th>
                <th>Atendió</th>
                <th style="text-align: right; width: 140px;">Total</th>
            </tr>
        </thead>
        <tbody>
            <?php if(!empty($ventas)): ?>
                <?php foreach ($ventas as $v): ?>
                    <tr>
                        <td style="text-align: center; font-weight: bold; color: #b71c1c;">#<?php echo $v['id_venta']; ?></td>
                        <td style="text-align: center;">#<?php echo $v['id_pedido']; ?></td>
                        <td><?php echo date('H:i:s', strtotime($v['fecha_hora_cobro'])); ?> hrs</td>
                        <td><span style="background: #f4ece1; padding: 2px 6px; border-radius: 4px; font-size: 12px; border: 1px solid #3d2514;"><?php echo strtoupper(htmlspecialchars($v['cobrado_por'])); ?></span></td>
                        <td style="text-align: right; font-weight: bold; color: #1e4620;">$<?php echo number_format($v['total'], 2); ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="5" style="text-align: center; font-style: italic; padding: 20px; color: #7a6b5c;">
                        No se encontraron registros de cobro con los filtros actuales.
                    </td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <a href="panel_admin.php" class="btn-volver">← Volver al Panel</a>
</div>

</body>
</html>
<?php $conexion->close(); ?>