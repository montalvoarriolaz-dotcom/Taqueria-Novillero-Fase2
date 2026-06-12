<?php
session_start();

// Validar seguridad de administrador
if (!isset($_SESSION['usuario'])) {
    header("Location: login.php");
    exit();
}

// Configuración de la base de datos
$servidor   = "localhost";
$usuario_db = "root";
$pass_db    = "";
$base_datos = "taqueria_novillero";

$conexion = new mysqli($servidor, $usuario_db, $pass_db, $base_datos);

if ($conexion->connect_error) {
    die("Error de conexión: " . $conexion->connect_error);
}

$mensaje = "";

// Procesar la actualización de precios
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['precios'])) {
    $precios = $_POST['precios']; // Array con los nuevos precios [id => nuevo_precio]
    
    $cambios_realizados = 0;

    foreach ($precios as $id_prod => $precio_nuevo) {
        // Limpiar el valor para que sea un número decimal válido
        $precio_nuevo = floatval($precio_nuevo);
        
        if ($precio_nuevo > 0) {
            // Actualizar el precio en la tabla 'productos'
            $sql_update = "UPDATE productos SET precio = $precio_nuevo WHERE id = " . intval($id_prod);
            if ($conexion->query($sql_update)) {
                $cambios_realizados++;
            }
        }
    }

    if ($cambios_realizados > 0) {
        $mensaje = "¡Precios actualizados con éxito en el menú!";
    }
}

// Consultar los productos actuales para mostrarlos en la lista
$sql_productos = "SELECT id, nombre, categoria, precio FROM productos ORDER BY categoria, nombre";
$resultado = $conexion->query($sql_productos);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modificar Precios - Taquería Novillero</title>
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

        /* Alerta de Éxito */
        .mensaje-alerta {
            background-color: #edf7ed;
            color: #1e4620;
            padding: 12px;
            border-radius: 12px;
            font-weight: bold;
            margin-bottom: 25px;
            border-left: 6px solid #4caf50;
            font-size: 14px;
        }

        /* Tabla Estilizada */
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

        /* Input de precio */
        .input-precio {
            font-family: inherit;
            padding: 6px;
            border: 1px solid #3d2514;
            border-radius: 8px; /* Redondeado */
            text-align: right;
            font-weight: bold;
            color: #2c1d11;
            background-color: #fcf8f2;
            width: 90px;
            box-sizing: border-box;
            font-size: 14px;
        }

        .input-precio:focus {
            background-color: #ffffff;
            outline: 2px solid #d32f2f;
        }

        .acciones-box {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin-top: 25px;
        }

        .btn-guardar {
            padding: 12px 24px;
            font-weight: bold;
            font-family: inherit;
            background-color: #d32f2f; /* Rojo Quemado */
            color: #ffffff;
            border: none;
            cursor: pointer;
            text-transform: uppercase;
            font-size: 13px;
            border-radius: 12px;
            transition: background 0.2s;
            box-shadow: 0 4px 0 #991b1b;
        }

        .btn-guardar:hover {
            background-color: #b71c1c;
        }

        .btn-guardar:active {
            box-shadow: none;
            transform: translateY(4px);
        }

        .btn-volver {
            display: block;
            width: 220px;
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
            background-color: #3d2514;
            box-shadow: 0 4px 0 #1c1007;
        }

        .btn-volver:active {
            box-shadow: none;
            transform: translateY(4px);
        }
    </style>
</head>
<body>

    <div class="container">
        <h2>Panel de Administración: Modificar Precios</h2>
        <p class="sub-titulo">AJUSTE DE TARIFAS Y COSTOS DEL MENÚ GENERAL</p>

        <!-- Mensajes de respuesta -->
        <?php if (!empty($mensaje)): ?>
            <div class="mensaje-alerta">
                ⚠️ <?php echo $mensaje; ?>
            </div>
        <?php endif; ?>

        <form action="cambiar_precios.php" method="POST">
            <table>
                <thead>
                    <tr>
                        <th style="text-align: center; width: 140px;">Categoría</th>
                        <th style="text-align: left;">Nombre del Producto</th>
                        <th style="text-align: right; width: 150px;">Precio Actual</th>
                        <th style="text-align: center; width: 160px;">Nuevo Precio ($)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($resultado && $resultado->num_rows > 0): ?>
                        <?php while ($row = $resultado->fetch_assoc()): ?>
                            <tr>
                                <td style="text-align: center;">
                                    <span class="cat-tag"><?php echo strtoupper($row['categoria']); ?></span>
                                </td>
                                <td><strong><?php echo htmlspecialchars($row['nombre']); ?></strong></td>
                                <td style="text-align: right; font-weight: bold; color: #5c4033;">
                                    $<?php echo number_format($row['precio'], 2); ?>
                                </td>
                                <td style="text-align: center;">
                                    <!-- Mandamos el precio actual como valor por defecto para que sea más fácil editar -->
                                    <span style="color: #3d2514; font-weight: bold; margin-right: 4px;">$</span>
                                    <input type="number" 
                                           name="precios[<?php echo $row['id']; ?>]" 
                                           step="0.01" 
                                           min="0.01" 
                                           value="<?php echo $row['precio']; ?>" 
                                           class="input-precio" 
                                           required>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" style="text-align: center; font-style: italic; padding: 20px;">No hay productos registrados en el menú.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>

            <div class="acciones-box">
                <a href="panel_admin.php" class="btn-volver">← Cancelar y Volver</a>
                <button type="submit" class="btn-guardar">Guardar Todos los Cambios</button>
            </div>
        </form>
    </div>

</body>
</html>
<?php $conexion->close(); ?>