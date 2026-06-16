<?php
session_start();
if (!isset($_SESSION['usuario'])) {
    header("Location: login.php");
    exit();
}

$conexion = new mysqli("localhost", "root", "", "taqueria_novillero");
if ($conexion->connect_error) { 
    die("Error de conexión: " . $conexion->connect_error); 
}

$mensaje = "";
$tipo_mensaje = "";

// ACCIÓN: COBRAR PEDIDO
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['cobrar_pedido'])) {
    $id_pedido = intval($_POST['id_pedido_accion']); 
    $total = floatval($_POST['total_pedido']);
    $usuario = $_SESSION['usuario'];

    $conexion->begin_transaction();
    try {
        // 1. EXTRAER PRODUCTOS ACTIVOS DE FORMA LIMPIA ANTES DE BORRARLOS
        $productos_texto = "";
        $sql_desglose = "SELECT dp.cantidad, prod.nombre 
                         FROM detalle_pedidos dp 
                         INNER JOIN productos prod ON dp.id_producto = prod.id 
                         WHERE dp.id_pedido = $id_pedido";
        $res_desglose = $conexion->query($sql_desglose);
        
        if ($res_desglose && $res_desglose->num_rows > 0) {
            $lineas = [];
            while ($item = $res_desglose->fetch_assoc()) {
                $lineas[] = $item['cantidad'] . "x " . $item['nombre'];
            }
            $productos_texto = implode("\n", $lineas); // Separación por saltos de línea estáticos
        } else {
            $productos_texto = "Sin productos desglosados";
        }

        // 2. INSERTAR EN EL HISTORIAL INCLUYENDO LOS PRODUCTOS Y EL ESTADO 'COBRADO'
        $stmt = $conexion->prepare("INSERT INTO historial_ventas (id_pedido, total, cobrado_por, fecha_hora_cobro, estado, productos_vendidos) VALUES (?, ?, ?, NOW(), 'COBRADO', ?)");
        $stmt->bind_param("idss", $id_pedido, $total, $usuario, $productos_texto);
        $stmt->execute();

        // 3. LIMPIAR EL FLUJO ACTIVO
        $conexion->query("DELETE FROM detalle_pedidos WHERE id_pedido = $id_pedido");
        $conexion->query("DELETE FROM pedidos_activos WHERE id_pedido = $id_pedido");

        $conexion->commit();
        $mensaje = "💰 Pedido #$id_pedido cobrado con éxito.";
        $tipo_mensaje = "exito";
    } catch (Exception $e) {
        $conexion->rollback();
        $mensaje = "Error al procesar cobro: " . $e->getMessage();
        $tipo_mensaje = "error";
    }
}

// ACCIÓN: CANCELAR PEDIDO
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['cancelar_pedido'])) {
    $id_pedido = intval($_POST['id_pedido_accion']); 
    $total = floatval($_POST['total_pedido']);
    $usuario = $_SESSION['usuario'];

    $conexion->begin_transaction();
    try {
        // 1. EXTRAER PRODUCTOS ACTIVOS DE FORMA LIMPIA ANTES DE BORRARLOS
        $productos_texto = "";
        $sql_desglose = "SELECT dp.cantidad, prod.nombre 
                         FROM detalle_pedidos dp 
                         INNER JOIN productos prod ON dp.id_producto = prod.id 
                         WHERE dp.id_pedido = $id_pedido";
        $res_desglose = $conexion->query($sql_desglose);
        
        if ($res_desglose && $res_desglose->num_rows > 0) {
            $lineas = [];
            while ($item = $res_desglose->fetch_assoc()) {
                $lineas[] = $item['cantidad'] . "x " . $item['nombre'];
            }
            $productos_texto = implode("\n", $lineas);
        } else {
            $productos_texto = "Sin productos desglosados";
        }

        // 2. INSERTAR EN EL HISTORIAL INCLUYENDO LOS PRODUCTOS Y EL ESTADO 'CANCELADO'
        $stmt = $conexion->prepare("INSERT INTO historial_ventas (id_pedido, total, cobrado_por, fecha_hora_cobro, estado, productos_vendidos) VALUES (?, ?, ?, NOW(), 'CANCELADO', ?)");
        $stmt->bind_param("idss", $id_pedido, $total, $usuario, $productos_texto);
        $stmt->execute();

        // 3. LIMPIAR EL FLUJO ACTIVO
        $conexion->query("DELETE FROM detalle_pedidos WHERE id_pedido = $id_pedido");
        $conexion->query("DELETE FROM pedidos_activos WHERE id_pedido = $id_pedido");

        $conexion->commit();
        $mensaje = "❌ Pedido #$id_pedido cancelado.";
        $tipo_mensaje = "error";
    } catch (Exception $e) {
        $conexion->rollback();
        $mensaje = "Error al cancelar pedido: " . $e->getMessage();
        $tipo_mensaje = "error";
    }
}

// CONSULTA GENERAL: Monitoreo en tiempo real de pedidos activos
$sql = "SELECT pa.id_pedido, pa.mesa, pa.total, pa.estado,
        GROUP_CONCAT(CONCAT(dp.cantidad, 'x ', prod.nombre) SEPARATOR '<br>') AS productos_detalle
        FROM pedidos_activos pa
        INNER JOIN detalle_pedidos dp ON pa.id_pedido = dp.id_pedido
        INNER JOIN productos prod ON dp.id_producto = prod.id
        GROUP BY pa.id_pedido, pa.mesa, pa.total, pa.estado
        ORDER BY pa.id_pedido DESC";

$resultado = $conexion->query($sql);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Caja (Trabajador) - Taquería Novillero</title>
    <style>
        body { 
            background-color: #f4ece1; 
            font-family: 'Courier New', Courier, monospace; 
            color: #2c1d11; 
            padding: 20px; 
            display: flex; 
            flex-direction: column; 
            align-items: center; 
        }
        .container { 
            width: 100%; 
            max-width: 950px; 
            background-color: #ffffff; 
            padding: 25px; 
            border-radius: 20px; 
            border: 2px solid #3d2514; 
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        h2 { 
            border-bottom: 3px double #d32f2f; 
            padding-bottom: 10px; 
            text-transform: uppercase; 
            text-align: center; 
            margin-top: 0;
        }
        .alerta { 
            padding: 12px; 
            margin-bottom: 20px; 
            font-weight: bold; 
            border-radius: 8px; 
            text-align: center; 
        }
        .exito { 
            background-color: #edf7ed; 
            color: #1e4620; 
            border-left: 6px solid #4caf50; 
        }
        .error { 
            background-color: #fdf2f2; 
            color: #b71c1c; 
            border-left: 6px solid #d32f2f; 
        }
        table { 
            width: 100%; 
            border-collapse: collapse; 
            margin-top: 10px; 
        }
        th { 
            background-color: #2c1d11; 
            color: white; 
            padding: 12px; 
            text-transform: uppercase; 
            font-size: 12px;
        }
        td { 
            padding: 12px; 
            border-bottom: 1px solid #f4ece1; 
            text-align: center; 
            font-size: 14px;
        }
        .badge-cocina { 
            background-color: #fff3cd; 
            color: #856404; 
            padding: 5px 10px; 
            border-radius: 5px; 
            font-weight: bold; 
            font-size: 11px; 
            display: inline-block;
        }
        .badge-servido { 
            background-color: #edf7ed; 
            color: #1e4620; 
            padding: 5px 10px; 
            border-radius: 5px; 
            font-weight: bold; 
            font-size: 11px; 
            display: inline-block;
        }
        .btn-cobrar { 
            background-color: #4caf50; 
            color: white; 
            border: none; 
            padding: 8px 14px; 
            font-weight: bold; 
            border-radius: 5px; 
            cursor: pointer; 
            font-family: inherit;
        }
        .btn-cancelar { 
            background-color: #d32f2f; 
            color: white; 
            border: none; 
            padding: 8px 14px; 
            font-weight: bold; 
            border-radius: 5px; 
            cursor: pointer; 
            font-family: inherit;
        }
        .btn-cobrar:hover { background-color: #43a047; }
        .btn-cancelar:hover { background-color: #b71c1c; }
        .btn-volver { 
            display: block; 
            width: 280px; 
            margin: 20px auto 0 auto; 
            text-align: center; 
            color: white; 
            text-decoration: none; 
            background: #3d2514; 
            padding: 10px; 
            border-radius: 10px; 
            font-weight: bold;
            text-transform: uppercase;
        }
        .btn-volver:hover { background-color: #d32f2f; }
    </style>
</head>
<body>

    <div class="container">
        <h2>Monitoreo de Caja (Trabajador)</h2>

        <?php if (!empty($mensaje)): ?>
            <div class="alerta <?php echo ($tipo_mensaje == 'exito') ? 'exito' : 'error'; ?>">
                <?php echo $mensaje; ?>
            </div>
        <?php endif; ?>

        <table>
            <thead>
                <tr>
                    <th>Pedido</th>
                    <th>Mesa</th>
                    <th>Productos</th>
                    <th>Total</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($resultado && $resultado->num_rows > 0): ?>
                    <?php while ($fila = $resultado->fetch_assoc()): ?>
                        <tr>
                            <td><strong>#<?php echo $fila['id_pedido']; ?></strong></td>
                            <td><span style="background:#d32f2f; color:white; padding:4px 8px; border-radius:4px; font-weight: bold;">MESA <?php echo $fila['mesa']; ?></span></td>
                            <td style="text-align: left; line-height: 1.4;"><?php echo $fila['productos_detalle']; ?></td>
                            <td style="font-weight: bold; color: #b71c1c;">$<?php echo number_format($fila['total'], 2); ?></td>
                            <td>
                                <?php if ($fila['estado'] == 'en cocina'): ?>
                                    <span class="badge-cocina">⏳ EN COCINA</span>
                                <?php else: ?>
                                    <span class="badge-servido">🍳 SERVIDO</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div style="display: flex; gap: 8px; justify-content: center;">
                                    <form method="POST" action="" onsubmit="return confirm('¿Confirmar cobro del pedido #<?php echo $fila['id_pedido']; ?>?');" style="margin:0;">
                                        <input type="hidden" name="id_pedido_accion" value="<?php echo $fila['id_pedido']; ?>">
                                        <input type="hidden" name="total_pedido" value="<?php echo $fila['total']; ?>">
                                        <button type="submit" name="cobrar_pedido" class="btn-cobrar">Cobrar</button>
                                    </form>

                                    <form method="POST" action="" onsubmit="return confirm('¿Está seguro de cancelar el pedido #<?php echo $fila['id_pedido']; ?>?');" style="margin:0;">
                                        <input type="hidden" name="id_pedido_accion" value="<?php echo $fila['id_pedido']; ?>">
                                        <input type="hidden" name="total_pedido" value="<?php echo $fila['total']; ?>">
                                        <button type="submit" name="cancelar_pedido" class="btn-cancelar">Cancelar</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" style="font-style: italic; color: #777; padding: 20px;">No hay pedidos activos en este momento.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>

        <a href="panel_trabajador.php" class="btn-volver">← Volver al Panel Trabajador</a>
    </div>

    <script>setTimeout(function(){ location.reload(); }, 5000);</script>
</body>
</html>
<?php $conexion->close(); ?>