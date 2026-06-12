<?php
session_start();

// Validar que haya un usuario en sesión (para saber quién cobra)
$usuario_empleado = isset($_SESSION['usuario']) ? $_SESSION['usuario'] : 'Empleado General';

$servidor   = "localhost";
$usuario_db = "root";
$pass_db    = "";
$base_datos = "taqueria_novillero";

$conexion = new mysqli($servidor, $usuario_db, $pass_db, $base_datos);

if ($conexion->connect_error) {
    die("Error de conexión: " . $conexion->connect_error);
}

$mensaje = "";
$pedido_encontrado = false;
$id_pedido = "";
$total_pedido = 0;
$productos_pedido = [];

// FLUJO 1: BUSCAR EL PEDIDO
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['buscar_pedido'])) {
    $id_pedido = intval($_POST['id_pedido']);
    
    // Verificar que el pedido exista y esté pendiente de pago
    $sql_pedido = "SELECT total, estado FROM pedidos_activos WHERE id_pedido = $id_pedido";
    $res_pedido = $conexion->query($sql_pedido);
    
    if ($res_pedido && $res_pedido->num_rows > 0) {
        $pedido = $res_pedido->fetch_assoc();
        
        if ($pedido['estado'] == 'pagado') {
            $mensaje = "Error: El pedido #$id_pedido ya fue cobrado anteriormente.";
        } else {
            $pedido_encontrado = true;
            $total_pedido = $pedido['total'];
            
            // Buscar los productos vinculados a este pedido
            $sql_detalles = "SELECT p.nombre, d.cantidad, p.precio 
                             FROM detalle_pedidos d 
                             INNER JOIN productos p ON d.id_producto = p.id 
                             WHERE d.id_pedido = $id_pedido";
            $res_detalles = $conexion->query($sql_detalles);
            
            while ($fila = $res_detalles->fetch_assoc()) {
                $productos_pedido[] = $fila;
            }
        }
    } else {
        $mensaje = "Error: No se encontró ningún pedido activo con el ID #$id_pedido.";
    }
}

// FLUJO 2: FINALIZAR EL COBRO
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['finalizar_cobro'])) {
    $id_pedido = intval($_POST['id_pedido_cobrar']);
    $total_cobrar = floatval($_POST['total_cobrar']);
    
    // 1. Cambiar el estado del pedido a 'pagado'
    $conexion->query("UPDATE pedidos_activos SET estado = 'pagado' WHERE id_pedido = $id_pedido");
    
    // 2. Registrar en el historial de ventas
    $conexion->query("INSERT INTO historial_ventas (id_pedido, total, cobrado_por) 
                      VALUES ($id_pedido, $total_cobrar, '$usuario_empleado')");
    
    $mensaje = "¡Cobro del pedido #$id_pedido registrado con éxito en el historial!";
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Módulo de Cobro - Taquería Novillero</title>
    <style>
        body {
            background-color: #f4ece1; /* Fondo color hueso/arena */
            font-family: 'Courier New', Courier, monospace;
            color: #2c1d11; /* Café muy oscuro para textos */
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
            padding: 25px;
            border-radius: 20px; /* Puntas redondeadas */
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            border: 2px solid #3d2514; /* Borde café madera */
        }

        h2 {
            color: #3d2514;
            text-transform: uppercase;
            letter-spacing: 1px;
            border-bottom: 3px double #d32f2f; /* Línea roja doble estilo rancho */
            padding-bottom: 10px;
            margin-top: 0;
        }

        h3 {
            color: #3d2514;
            margin-top: 25px;
            text-transform: uppercase;
        }

        .mensaje-alerta {
            background-color: #e3f2fd;
            color: #0d47a1;
            padding: 12px;
            border-radius: 12px;
            font-weight: bold;
            margin-bottom: 20px;
            border-left: 6px solid #2196f3;
        }

        /* Si el mensaje contiene la palabra Error cambia a estilo rojo */
        <?php if (strpos($mensaje, 'Error') !== false): ?>
        .mensaje-alerta {
            background-color: #fdf2f2;
            color: #b71c1c;
            border-left: 6px solid #d32f2f;
        }
        <?php endif; ?>

        .form-busqueda {
            background-color: #fcf8f2;
            padding: 15px;
            border-radius: 15px;
            border: 1px dashed #3d2514;
            display: flex;
            align-items: center;
            gap: 15px;
            flex-wrap: wrap;
        }

        label {
            font-weight: bold;
            text-transform: uppercase;
            font-size: 14px;
        }

        input[type="number"] {
            padding: 8px 12px;
            border: 2px solid #3d2514;
            background-color: #ffffff;
            color: #2c1d11;
            font-family: inherit;
            font-size: 16px;
            border-radius: 10px; /* Redondeado */
            width: 90px;
            font-weight: bold;
            text-align: center;
        }

        input[type="number"]:focus {
            outline: none;
            border-color: #d32f2f;
        }

        .btn-cafe {
            padding: 10px 20px;
            background-color: #3d2514; /* Café Oscuro */
            color: #ffffff;
            border: none;
            font-weight: bold;
            text-transform: uppercase;
            cursor: pointer;
            border-radius: 12px; /* Redondeado */
            transition: background 0.2s;
            box-shadow: 0 3px 0 #1c1007;
        }

        .btn-cafe:hover {
            background-color: #2c1d11;
        }

        .btn-cafe:active {
            box-shadow: none;
            transform: translateY(3px);
        }

        .btn-rojo {
            padding: 12px 28px;
            background-color: #d32f2f; /* Rojo Quemado */
            color: #ffffff;
            border: none;
            font-weight: bold;
            font-size: 16px;
            text-transform: uppercase;
            cursor: pointer;
            border-radius: 12px; /* Redondeado */
            transition: background 0.2s;
            box-shadow: 0 4px 0 #991b1b;
        }

        .btn-rojo:hover {
            background-color: #b71c1c;
        }

        .btn-rojo:active {
            box-shadow: none;
            transform: translateY(4px);
        }

        table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            margin-top: 15px;
            border-radius: 15px; /* Redondeado de tabla */
            overflow: hidden;
            border: 1px solid #3d2514;
        }

        th {
            background-color: #2c1d11; /* Negro/Café carbón */
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
        }

        tr:last-child td {
            border-bottom: none;
        }

        .total-row {
            background-color: #fcf8f2;
            font-size: 16px;
        }

        .enlace-volver {
            display: inline-block;
            margin-top: 25px;
            color: #3d2514;
            font-weight: bold;
            text-decoration: none;
            border-bottom: 2px dashed #3d2514;
            padding-bottom: 2px;
        }

        .enlace-volver:hover {
            color: #d32f2f;
            border-color: #d32f2f;
        }
    </style>
</head>
<body>

    <div class="container">
        <h2>Cobrar Pedido Activo</h2>
        
        <?php if (!empty($mensaje)): ?>
            <div class="mensaje-alerta"><?php echo $mensaje; ?></div>
        <?php endif; ?>

        <form action="cobrar_pedido.php" method="POST" class="form-busqueda">
            <label>ID del Pedido:</label>
            <input type="number" name="id_pedido" value="<?php echo htmlspecialchars($id_pedido); ?>" required min="1">
            <button type="submit" name="buscar_pedido" class="btn-cafe">Buscar Pedido</button>
        </form>

        <?php if ($pedido_encontrado): ?>
            <h3>Detalles de la Orden #<?php echo $id_pedido; ?></h3>
            
            <table>
                <thead>
                    <tr>
                        <th>Producto</th>
                        <th style="text-align: center;">Cantidad</th>
                        <th style="text-align: right;">Precio</th>
                        <th style="text-align: right;">Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($productos_pedido as $prod): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($prod['nombre']); ?></strong></td>
                            <td style="text-align: center;"><?php echo $prod['cantidad']; ?></td>
                            <td style="text-align: right;">$<?php echo number_format($prod['precio'], 2); ?></td>
                            <td style="text-align: right; font-weight: bold;">$<?php echo number_format($prod['precio'] * $prod['cantidad'], 2); ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <tr class="total-row">
                        <td colspan="3" style="text-align: right; font-weight: bold; text-transform: uppercase;">Total a Cobrar:</td>
                        <td style="text-align: right; font-weight: bold; color: #d32f2f; font-size: 18px;">$<?php echo number_format($total_pedido, 2); ?></td>
                    </tr>
                </tbody>
            </table>

            <br>

            <form action="cobrar_pedido.php" method="POST" style="text-align: right; margin-top: 15px;">
                <input type="hidden" name="id_pedido_cobrar" value="<?php echo $id_pedido; ?>">
                <input type="hidden" name="total_cobrar" value="<?php echo $total_pedido; ?>">
                
                <button type="submit" name="finalizar_cobro" class="btn-rojo">
                    Finalizar Cobro
                </button>
            </form>
        <?php endif; ?>

        <div style="text-align: center;">
            <a href="panel_trabajador.php" class="enlace-volver">← Volver al Panel Operativo</a>
        </div>
    </div>

</body>
</html>
<?php $conexion->close(); ?>