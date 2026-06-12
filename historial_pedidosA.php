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

$mensaje = "";
$tipo_mensaje = "";

// ACCIÓN DE COBRAR: Mover de pedidos_activos a historial_ventas y actualizar estado
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['cobrar_pedido'])) {
    $id_pedido_cobrar = intval($_POST['id_pedido_cobrar']);
    $total_pedido = floatval($_POST['total_pedido']);
    $cobrado_por = $_SESSION['usuario']; // El admin que inició sesión

    // 1. Iniciar transacción para asegurar que no se pierdan datos en el brinco
    $conexion->begin_transaction();

    try {
        // 2. Insertar en el historial de ventas
        $sql_historial = "INSERT INTO historial_ventas (id_pedido, total, cobrado_por, fecha_hora_cobro) 
                          VALUES ($id_pedido_cobrar, $total_pedido, '$cobrado_por', NOW())";
        $conexion->query($sql_historial);

        // 3. Actualizar el estado del pedido a 'cobrado' para que desaparezca de los pendientes
        $sql_actualizar = "UPDATE pedidos_activos SET estado = 'cobrado' WHERE id_pedido = $id_pedido_cobrar";
        $conexion->query($sql_actualizar);

        // Confirmar cambios
        $conexion->commit();
        $mensaje = "¡Pedido #$id_pedido_cobrar cobrado con éxito! Ya fue enviado al historial de ventas.";
        $tipo_mensaje = "exito";
    } catch (Exception $e) {
        // Deshacer si algo falla
        $conexion->rollback();
        $mensaje = "Error al procesar el cobro: " . $e->getMessage();
        $tipo_mensaje = "error";
    }
}

// Consulta: Trae pedidos con estado 'pendiente', su mesa y concatena el desglose
$sql = "SELECT 
            pa.id_pedido, 
            pa.mesa, 
            pa.total, 
            pa.estado, 
            pa.fecha_hora AS fecha_creacion,
            GROUP_CONCAT(CONCAT(dp.cantidad, 'x ', prod.nombre) SEPARATOR '<br>') AS productos_detalle
        FROM pedidos_activos pa
        INNER JOIN detalle_pedidos dp ON pa.id_pedido = dp.id_pedido
        INNER JOIN productos prod ON dp.id_producto = prod.id
        WHERE pa.estado = 'pendiente'
        GROUP BY pa.id_pedido
        ORDER BY pa.id_pedido DESC";

$resultado = $conexion->query($sql);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Historial de Pedidos Activos - Taquería Novillero</title>
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
            max-width: 950px; 
            background-color: #ffffff;
            padding: 25px;
            border-radius: 20px; /* Esquinas redondeadas */
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            border: 2px solid #3d2514; /* Borde café madera */
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

        p {
            margin-top: 5px;
            margin-bottom: 25px;
            font-size: 14px;
            font-style: italic;
            color: #5c4033;
        }

        .mensaje-alerta {
            padding: 12px;
            border-radius: 12px;
            font-weight: bold;
            margin-bottom: 20px;
            font-size: 14px;
        }

        .mensaje-exito {
            background-color: #edf7ed;
            color: #1e4620;
            border-left: 6px solid #4caf50;
        }

        .mensaje-error {
            background-color: #fdf2f2;
            color: #b71c1c;
            border-left: 6px solid #d32f2f;
        }

        table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            margin-bottom: 20px;
            border-radius: 15px; /* Tabla redondeada */
            overflow: hidden;
            border: 1px solid #3d2514;
        }

        th {
            background-color: #2c1d11; /* Negro carbón */
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
            vertical-align: middle;
        }

        tr:last-child td {
            border-bottom: none;
        }

        .badge-pendiente {
            background-color: #fdf2f2;
            color: #d32f2f;
            padding: 5px 10px;
            border-radius: 8px;
            font-weight: bold;
            font-size: 12px;
            letter-spacing: 1px;
            display: inline-block;
            border: 1px solid #f5b7b7;
        }

        .badge-mesa {
            background-color: #d32f2f;
            color: #ffffff;
            padding: 4px 8px;
            border-radius: 6px;
            font-weight: bold;
            font-size: 13px;
            display: inline-block;
        }

        .btn-cobrar {
            background-color: #4caf50; /* Verde Éxito */
            color: white;
            border: none;
            padding: 8px 14px;
            text-transform: uppercase;
            font-weight: bold;
            font-size: 12px;
            font-family: inherit;
            border-radius: 8px;
            cursor: pointer;
            transition: background 0.2s;
        }

        .btn-cobrar:hover {
            background-color: #388e3c;
        }

        .btn-volver {
            display: block;
            width: 220px;
            margin: 25px auto 0 auto;
            padding: 12px;
            background-color: #3d2514; /* Café Madera */
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
        <h2>Pedidos Pendientes por Cobrar</h2>
        <p>Monitoreo de cuentas activas en las mesas y barra (Vista Administrador).</p>

        <?php if (!empty($mensaje)): ?>
            <div class="mensaje-alerta <?php echo ($tipo_mensaje == 'error') ? 'mensaje-error' : 'mensaje-exito'; ?>">
                <?php echo $mensaje; ?>
            </div>
        <?php endif; ?>

        <table>
            <thead>
                <tr>
                    <th style="text-align: center; width: 90px;">ID Pedido</th>
                    <th style="text-align: center; width: 100px;">Mesa</th>
                    <th style="text-align: left; width: 170px;">Fecha / Hora Registro</th>
                    <th style="text-align: left;">Productos Ordenados</th>
                    <th style="text-align: right; width: 120px;">Total Cuenta</th>
                    <th style="text-align: center; width: 110px;">Estado</th>
                    <th style="text-align: center; width: 110px;">Acción</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($resultado && $resultado->num_rows > 0): ?>
                    <?php while ($fila = $resultado->fetch_assoc()): ?>
                        <tr>
                            <td style="text-align: center;"><strong>#<?php echo $fila['id_pedido']; ?></strong></td>
                            
                            <td style="text-align: center;">
                                <span class="badge-mesa">MESA <?php echo $fila['mesa']; ?></span>
                            </td>
                            
                            <td><?php echo $fila['fecha_creacion']; ?></td>
                            
                            <td style="line-height: 1.4; font-size: 14px; color: #5c4033;">
                                <?php echo $fila['productos_detalle']; ?>
                            </td>
                            
                            <td style="text-align: right; font-weight: bold; color: #b71c1c;">
                                $<?php echo number_format($fila['total'], 2); ?>
                            </td>
                            
                            <td style="text-align: center;">
                                <span class="badge-pendiente">PENDIENTE</span>
                            </td>

                            <td style="text-align: center;">
                                <form action="historial_pedidosA.php" method="POST" onsubmit="return confirm('¿Confirmar el cobro total de la MESA <?php echo $fila['mesa']; ?> por $<?php echo number_format($fila['total'], 2); ?>?');" style="margin:0;">
                                    <input type="hidden" name="id_pedido_cobrar" value="<?php echo $fila['id_pedido']; ?>">
                                    <input type="hidden" name="total_pedido" value="<?php echo $fila['total']; ?>">
                                    <button type="submit" name="cobrar_pedido" class="btn-cobrar">Cobrar</button>
                                </form>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" style="text-align: center; font-style: italic; padding: 20px;">
                            No hay pedidos pendientes en este momento. ¡Todo cobrado!
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>

        <a href="panel_admin.php" class="btn-volver">← Volver al Panel Admin</a>
    </div>

</body>
</html>
<?php $conexion->close(); ?>