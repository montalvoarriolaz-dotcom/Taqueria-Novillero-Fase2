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

// Procesar el ajuste de inventario
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['ajustar_stock'])) {
    $id_prod = intval($_POST['id_producto']);
    $cantidad = intval($_POST['cantidad']);
    $accion = $_POST['accion']; // 'sumar' o 'restar'

    if ($cantidad > 0) {
        // Consultar existencias actuales del producto seleccionado
        $res_prod = $conexion->query("SELECT nombre, existencias FROM productos WHERE id = $id_prod");
        if ($res_prod && $res_prod->num_rows > 0) {
            $prod = $res_prod->fetch_assoc();
            $stock_actual = $prod['existencias'];
            $nombre_prod = $prod['nombre'];

            if ($accion == 'sumar') {
                // Sumar al inventario
                $conexion->query("UPDATE productos SET existencias = existencias + $cantidad WHERE id = $id_prod");
                $mensaje = "Se agregaron $cantidad unidades a '$nombre_prod' con éxito.";
                $tipo_mensaje = "exito";
            } elseif ($accion == 'restar') {
                // Validar que no se intente quitar más de lo disponible
                if ($cantidad > $stock_actual) {
                    $mensaje = "Error: No puedes quitar $cantidad unidades de '$nombre_prod'. Solo quedan $stock_actual disponibles.";
                    $tipo_mensaje = "error";
                } else {
                    // Restar del inventario
                    $conexion->query("UPDATE productos SET existencias = existencias - $cantidad WHERE id = $id_prod");
                    $mensaje = "Se retiraron $cantidad unidades de '$nombre_prod' con éxito.";
                    $tipo_mensaje = "exito";
                }
            }
        }
    } else {
        $mensaje = "Error: Por favor ingresa una cantidad mayor a 0.";
        $tipo_mensaje = "error";
    }
}

// Consultar la lista de productos actualizada para rellenar la tabla e insumos del formulario
$resultado_productos = $conexion->query("SELECT id, nombre, categoria, existencias FROM productos ORDER BY categoria, nombre");
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Inventario - Taquería Novillero</title>
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

        h3 {
            color: #3d2514;
            text-transform: uppercase;
            font-size: 15px;
            margin-top: 25px;
            margin-bottom: 15px;
            border-left: 5px solid #3d2514;
            padding-left: 8px;
        }

        /* Alertas de Éxito o Error */
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

        /* Formulario Estilizado */
        .form-ajuste {
            background-color: #fcf8f2;
            padding: 20px;
            border-radius: 15px;
            border: 1px dashed #3d2514;
        }

        .form-group {
            margin-bottom: 18px;
        }

        .form-group label {
            display: block;
            font-weight: bold;
            font-size: 13px;
            margin-bottom: 6px;
            color: #3d2514;
            text-transform: uppercase;
        }

        .form-group select,
        .form-group input[type="number"] {
            width: 100%;
            font-family: inherit;
            padding: 8px;
            border: 1px solid #3d2514;
            border-radius: 8px; /* Inputs redondeados */
            box-sizing: border-box;
            background-color: #ffffff;
            color: #2c1d11;
            font-size: 14px;
        }

        .form-group input[type="number"] {
            width: 120px;
            text-align: center;
        }

        .radio-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
            margin-top: 5px;
        }

        .radio-option {
            display: flex;
            align-items: center;
            font-size: 14px;
            font-weight: bold;
            cursor: pointer;
        }

        .radio-option input[type="radio"] {
            margin-right: 10px;
            accent-color: #d32f2f;
        }

        .btn-aplicar {
            display: inline-block;
            padding: 10px 20px;
            font-weight: bold;
            font-family: inherit;
            background-color: #2c1d11; /* Negro Carbón */
            color: #ffffff;
            border: none;
            cursor: pointer;
            text-transform: uppercase;
            font-size: 12px;
            border-radius: 10px;
            transition: background 0.2s;
            box-shadow: 0 3px 0 #1c1007;
        }

        .btn-aplicar:hover {
            background-color: #d32f2f;
            box-shadow: 0 3px 0 #991b1b;
        }

        .btn-aplicar:active {
            box-shadow: none;
            transform: translateY(3px);
        }

        /* Tabla de Inventario */
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
            vertical-align: middle;
        }

        tr:last-child td {
            border-bottom: none;
        }

        .cat-tag {
            font-weight: bold;
            font-size: 10px;
            padding: 2px 6px;
            background-color: #f4ece1;
            border: 1px solid #3d2514;
            border-radius: 4px;
            color: #5c4033;
        }

        .stock-critico {
            background-color: #fde8e8 !md;
            color: #d32f2f !important;
            font-weight: bold;
        }

        .btn-volver {
            display: block;
            width: 220px;
            margin: 25px auto 0 auto;
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
        <h2>Panel de Administración: Control de Inventario</h2>
        <p class="sub-titulo">MOVIMIENTOS DE ALMACÉN, ENTRADAS Y CONTROL DE MERMAS</p>

        <!-- Mensajes de respuesta -->
        <?php if (!empty($mensaje)): ?>
            <div class="mensaje-alerta <?php echo ($tipo_mensaje == 'error') ? 'mensaje-error' : 'mensaje-exito'; ?>">
                <?php echo $mensaje; ?>
            </div>
        <?php endif; ?>

        <!-- Formulario para Ajustar Stock -->
        <h3>Realizar Ajuste Manual</h3>
        <form action="gestionar_inventario.php" method="POST" class="form-ajuste">
            <div class="form-group">
                <label>Selecciona el Producto / Insumo:</label>
                <select name="id_producto" required>
                    <?php 
                    // Reiniciamos el puntero del resultado para usarlo en el select
                    $resultado_productos->data_seek(0);
                    while ($row = $resultado_productos->fetch_assoc()): 
                    ?>
                        <option value="<?php echo $row['id']; ?>">
                            [<?php echo strtoupper($row['categoria']); ?>] <?php echo htmlspecialchars($row['nombre']); ?> (Stock actual: <?php echo $row['existencias']; ?>)
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>

            <div class="form-group">
                <label>Acción a realizar:</label>
                <div class="radio-group">
                    <label class="radio-option">
                        <input type="radio" name="accion" value="sumar" checked>
                        ➕ Agregar existencias (Entradas / Abastecimiento)
                    </label>
                    <label class="radio-option">
                        <input type="radio" name="accion" value="restar">
                        ⚠️ Quitar existencias (Mermas / Insumo dañado o vendido)
                    </label>
                </div>
            </div>

            <div class="form-group">
                <label>Cantidad a ajustar:</label>
                <input type="number" name="cantidad" min="1" value="1" required>
            </div>

            <button type="submit" name="ajustar_stock" class="btn-aplicar">Aplicar Ajuste en Almacén</button>
        </form>

        <hr style="border: 0; border-top: 1px dashed #3d2514; margin: 30px 0;">

        <!-- Tabla con las Existencias Actuales -->
        <h3>Existencias en Almacén</h3>
        <table>
            <thead>
                <tr>
                    <th style="text-align: center; width: 130px;">Categoría</th>
                    <th style="text-align: left;">Nombre del Producto</th>
                    <th style="text-align: center; width: 220px;">Existencias Actuales</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                // Volvemos a colocar el puntero al inicio para pintar la tabla
                $resultado_productos->data_seek(0);
                if ($resultado_productos && $resultado_productos->num_rows > 0): 
                ?>
                    <?php while ($prod = $resultado_productos->fetch_assoc()): 
                        $es_bajo = ($prod['existencias'] < 15);
                    ?>
                        <tr>
                            <td style="text-align: center;">
                                <span class="cat-tag"><?php echo strtoupper($prod['categoria']); ?></span>
                            </td>
                            <td><strong><?php echo htmlspecialchars($prod['nombre']); ?></strong></td>
                            <td style="text-align: center; font-size: 15px;" class="<?php echo $es_bajo ? 'stock-critico' : ''; ?>">
                                <?php echo $prod['existencias']; ?> unidades
                                <?php if ($es_bajo): ?>
                                    <span style="display:block; font-size: 11px; letter-spacing: 0.5px; font-weight: bold; text-transform: uppercase; margin-top: 2px;">⚠️ ¡STOCK BAJO!</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="3" style="text-align: center; font-style: italic; padding: 20px;">No hay productos registrados en el inventario.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>

        <a href="panel_admin.php" class="btn-volver">← Volver al Panel Admin</a>
    </div>

</body>
</html>
<?php $conexion->close(); ?>