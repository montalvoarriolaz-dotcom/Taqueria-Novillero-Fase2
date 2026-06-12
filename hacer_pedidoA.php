<?php
session_start();
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

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['cantidades'])) {
    $cantidades = $_POST['cantidades']; 
    $mesa = isset($_POST['mesa']) ? intval($_POST['mesa']) : 0; // Capturar el número de mesa
    
    $error_stock = false;
    $productos_a_pedir = [];
    $total_pedido = 0;

    foreach ($cantidades as $id_prod => $cant) {
        $cant = intval($cant);
        if ($cant > 0) {
            $res_prod = $conexion->query("SELECT nombre, precio, existencias FROM productos WHERE id = $id_prod");
            if ($res_prod && $res_prod->num_rows > 0) {
                $prod = $res_prod->fetch_assoc();
                
                if ($cant > $prod['existencias']) {
                    $mensaje = "Error: No hay existencias suficientes de '" . $prod['nombre'] . "'. Disponibles: " . $prod['existencias'];
                    $tipo_mensaje = "error";
                    $error_stock = true;
                    break; 
                }
                
                $total_pedido += ($prod['precio'] * $cant);
                $productos_a_pedir[] = [
                    'id' => $id_prod,
                    'cantidad' => $cant
                ];
            }
        }
    }

    if (!$error_stock && !empty($productos_a_pedir)) {
        // Se añade el campo mesa en el INSERT
        $conexion->query("INSERT INTO pedidos_activos (total, mesa) VALUES ($total_pedido, $mesa)");
        $id_nuevo_pedido = $conexion->insert_id; 

        foreach ($productos_a_pedir as $item) {
            $id_p = $item['id'];
            $cant_p = $item['cantidad'];

            $conexion->query("INSERT INTO detalle_pedidos (id_pedido, id_producto, cantidad) VALUES ($id_nuevo_pedido, $id_p, $cant_p)");
            $conexion->query("UPDATE productos SET existencias = existencias - $cant_p WHERE id = $id_p");
        }

        $mensaje = "¡Pedido #" . $id_nuevo_pedido . " registrado con éxito! Total: $" . number_format($total_pedido, 2);
        $tipo_mensaje = "exito";
    } elseif (!$error_stock && empty($productos_a_pedir)) {
        $mensaje = "Por favor, ingresa una cantidad en al menos un producto.";
        $tipo_mensaje = "error";
    }
}

$productos_menu = $conexion->query("SELECT id, nombre, precio FROM productos");
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nueva Orden - Taquería Novillero</title>
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
            border-radius: 20px; /* Esquinas redondeadas */
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
            margin-bottom: 20px;
        }

        .mensaje-alerta {
            padding: 12px;
            border-radius: 12px; /* Redondeado */
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

        /* Estilo para la sección de mesa añadida */
        .seccion-mesa {
            background-color: #fcf8f2;
            padding: 15px;
            border-radius: 12px;
            border: 1px dashed #3d2514;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .seccion-mesa label {
            font-weight: bold;
            text-transform: uppercase;
            font-size: 14px;
        }

        table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            margin-bottom: 20px;
            border-radius: 15px; /* Redondeado de tabla */
            overflow: hidden;
            border: 1px solid #3d2514;
        }

        th {
            background-color: #2c1d11; /* Negro/Café carbón */
            color: #ffffff;
            padding: 12px;
            text-transform: uppercase;
            font-size: 13px;
            letter-spacing: 1px;
        }

        td {
            padding: 12px;
            background-color: #ffffff;
            border-bottom: 1px solid #f4ece1;
            color: #2c1d11;
            font-size: 15px;
        }

        tr:last-child td {
            border-bottom: none;
        }

        input[type="number"] {
            padding: 6px 10px;
            border: 2px solid #3d2514;
            background-color: #ffffff;
            color: #2c1d11;
            font-family: inherit;
            font-size: 15px;
            border-radius: 8px; /* Cantidades redondeadas */
            width: 65px;
            font-weight: bold;
            text-align: center;
        }

        input[type="number"]:focus {
            outline: none;
            border-color: #d32f2f;
        }

        .btn-rojo {
            width: 100%;
            padding: 12px;
            background-color: #d32f2f; /* Rojo Quemado */
            color: #ffffff;
            border: none;
            font-weight: bold;
            font-size: 16px;
            text-transform: uppercase;
            cursor: pointer;
            border-radius: 12px; /* Redondeado */
            transition: background 0.2s, transform 0.1s;
            box-shadow: 0 4px 0 #991b1b;
            letter-spacing: 1px;
        }

        .btn-rojo:hover {
            background-color: #b71c1c;
        }

        .btn-rojo:active {
            box-shadow: none;
            transform: translateY(4px);
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
        <h2>Registrar Nuevo Pedido</h2>

        <?php if (!empty($mensaje)): ?>
            <div class="mensaje-alerta <?php echo ($tipo_mensaje == 'error') ? 'mensaje-error' : 'mensaje-exito'; ?>">
                <?php echo $mensaje; ?>
            </div>
        <?php endif; ?>

        <form action="hacer_pedidoA.php" method="POST">
            <div class="seccion-mesa">
                <label for="mesa">Asignar Número de Mesa:</label>
                <input type="number" id="mesa" name="mesa" min="1" max="99" value="1" required style="width: 80px;">
            </div>

            <table>
                <thead>
                    <tr>
                        <th style="text-align: left;">Producto / Platillo</th>
                        <th style="text-align: right; width: 120px;">Precio</th>
                        <th style="text-align: center; width: 160px;">Cantidad a ordenar</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($productos_menu && $productos_menu->num_rows > 0): ?>
                        <?php while ($row = $productos_menu->fetch_assoc()): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($row['nombre']); ?></strong></td>
                                <td style="text-align: right; font-weight: bold;">$<?php echo number_format($row['precio'], 2); ?></td>
                                <td style="text-align: center;">
                                    <input type="number" name="cantidades[<?php echo $row['id']; ?>]" min="0" value="0">
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="3" style="text-align: center; font-style: italic;">No hay productos registrados en el menú.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>

            <button type="submit" class="btn-rojo">Guardar Pedido de la Mesa</button>
        </form>

        <div style="text-align: center;">
            <a href="panel_admin.php" class="enlace-volver">← Volver al Panel Operativo</a>
        </div>
    </div>

</body>
</html>
<?php $conexion->close(); ?>