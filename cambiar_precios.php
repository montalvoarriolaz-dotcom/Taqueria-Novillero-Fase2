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

// 1. PROCESAR: AGREGAR NUEVO PRODUCTO
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['accion_agregar'])) {
    $nombre_nuevo = $conexion->real_escape_string($_POST['nuevo_nombre']);
    $categoria_nueva = $conexion->real_escape_string($_POST['nuevo_categoria']);
    $precio_nuevo = floatval($_POST['nuevo_precio']);

    if (!empty($nombre_nuevo) && !empty($categoria_nueva) && $precio_nuevo > 0) {
        $sql_insert = "INSERT INTO productos (nombre, categoria, precio) VALUES ('$nombre_nuevo', '$categoria_nueva', $precio_nuevo)";
        if ($conexion->query($sql_insert)) {
            $mensaje = "¡Producto agregado con éxito al menú!";
        } else {
            $mensaje = "Error al agregar el producto: " . $conexion->error;
        }
    } else {
        $mensaje = "Por favor, rellene todos los campos correctamente.";
    }
}

// 2. PROCESAR: ELIMINAR PRODUCTO
if (isset($_GET['eliminar'])) {
    $id_eliminar = intval($_GET['eliminar']);
    $sql_delete = "DELETE FROM productos WHERE id = $id_eliminar";
    if ($conexion->query($sql_delete)) {
        $mensaje = "¡Producto eliminado correctamente del menú!";
    } else {
        $mensaje = "Error al eliminar el producto.";
    }
}

// 3. PROCESAR: MODIFICAR/EDITAR PRODUCTOS EXISTENTES
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['productos_edit'])) {
    $productos_edit = $_POST['productos_edit']; 
    $cambios_realizados = 0;

    foreach ($productos_edit as $id_prod => $datos) {
        $id_prod = intval($id_prod);
        $nombre_mod = $conexion->real_escape_string($datos['nombre']);
        $categoria_mod = $conexion->real_escape_string($datos['categoria']);
        $precio_mod = floatval($datos['precio']);
        
        if (!empty($nombre_mod) && !empty($categoria_mod) && $precio_mod > 0) {
            $sql_update = "UPDATE productos SET nombre = '$nombre_mod', categoria = '$categoria_mod', precio = $precio_mod WHERE id = $id_prod";
            if ($conexion->query($sql_update)) {
                $cambios_realizados++;
            }
        }
    }

    if ($cambios_realizados > 0) {
        $mensaje = "¡Menú y precios actualizados con éxito!";
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
    <title>Gestionar Menú - Taquería Novillero</title>
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
            max-width: 900px;
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

        /* Alerta de Éxito / Estado */
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

        /* Formulario Agregar Producto */
        .form-agregar {
            background-color: #fcf8f2;
            border: 1px dashed #3d2514;
            padding: 15px;
            border-radius: 12px;
            margin-bottom: 25px;
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            align-items: center;
        }

        .form-agregar h3 {
            margin: 0;
            width: 100%;
            font-size: 14px;
            text-transform: uppercase;
            color: #3d2514;
        }

        .input-inline {
            font-family: inherit;
            padding: 6px;
            border: 1px solid #3d2514;
            border-radius: 8px;
            color: #2c1d11;
            font-size: 13px;
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

        /* Inputs editables dentro de la tabla */
        .input-tabla {
            font-family: inherit;
            padding: 5px;
            border: 1px solid #3d2514;
            border-radius: 6px;
            color: #2c1d11;
            font-weight: bold;
            background-color: #fcf8f2;
            font-size: 13px;
            box-sizing: border-box;
            width: 100%;
        }

        .input-precio {
            text-align: right;
            width: 80px;
        }

        .input-tabla:focus {
            background-color: #ffffff;
            outline: 2px solid #d32f2f;
        }

        /* Botón Eliminar */
        .btn-eliminar {
            background-color: #b71c1c;
            color: white;
            border: none;
            padding: 5px 10px;
            border-radius: 6px;
            cursor: pointer;
            font-family: inherit;
            font-weight: bold;
            text-decoration: none;
            font-size: 12px;
            box-shadow: 0 2px 0 #7f1212;
        }

        .btn-eliminar:hover {
            background-color: #d32f2f;
        }

        .btn-eliminar:active {
            box-shadow: none;
            transform: translateY(2px);
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
        <h2>Panel de Administración: Gestionar Menú</h2>
        <p class="sub-titulo">ALTA, BAJA Y MODIFICACIÓN DE PLATILLOS E INSUMOS</p>

        <!-- Mensajes de respuesta -->
        <?php if (!empty($mensaje)): ?>
            <div class="mensaje-alerta">
                ⚠️ <?php echo $mensaje; ?>
            </div>
        <?php endif; ?>

        <!-- SECCIÓN 1: FORMULARIO NUEVO PARA AGREGAR PRODUCTO -->
        <form action="cambiar_precios.php" method="POST" class="form-agregar">
            <h3>+ AGREGAR NUEVO PRODUCTO AL MENÚ</h3>
            <input type="text" name="nuevo_nombre" placeholder="Nombre del platillo/bebida" class="input-inline" style="flex: 2;" required>
            
            <select name="nuevo_categoria" class="input-inline" style="flex: 1;" required>
                <option value="" disabled selected>Categoría...</option>
                <option value="tacos">TACOS</option>
                <option value="especialidades">ESPECIALIDADES</option>
                <option value="bebidas">BEBIDAS</option>
                <option value="postres">POSTRES</option>
            </select>

            <input type="number" name="nuevo_precio" placeholder="Precio ($)" step="0.01" min="0.01" class="input-inline" style="width: 100px;" required>
            
            <button type="submit" name="accion_agregar" class="btn-guardar" style="padding: 6px 15px; box-shadow: 0 2px 0 #991b1b; font-size: 11px;">Añadir</button>
        </form>

        <!-- SECCIÓN 2 y 3: TABLA PARA EDITAR Y ELIMINAR -->
        <form action="cambiar_precios.php" method="POST">
            <table>
                <thead>
                    <tr>
                        <th style="text-align: center; width: 150px;">Categoría</th>
                        <th style="text-align: left;">Nombre del Producto</th>
                        <th style="text-align: center; width: 130px;">Precio ($)</th>
                        <th style="text-align: center; width: 100px;">Acción</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($resultado && $resultado->num_rows > 0): ?>
                        <?php while ($row = $resultado->fetch_assoc()): ?>
                            <tr>
                                <!-- Categoría Editable -->
                                <td style="text-align: center;">
                                    <input type="text" 
                                           name="productos_edit[<?php echo $row['id']; ?>][categoria]" 
                                           value="<?php echo htmlspecialchars($row['categoria']); ?>" 
                                           class="input-tabla" 
                                           style="text-align: center; text-transform: uppercase;" required>
                                </td>
                                
                                <!-- Nombre Editable -->
                                <td>
                                    <input type="text" 
                                           name="productos_edit[<?php echo $row['id']; ?>][nombre]" 
                                           value="<?php echo htmlspecialchars($row['nombre']); ?>" 
                                           class="input-tabla" required>
                                </td>
                                
                                <!-- Precio Editable -->
                                <td style="text-align: center;">
                                    <span style="color: #3d2514; font-weight: bold; margin-right: 2px;">$</span>
                                    <input type="number" 
                                           name="productos_edit[<?php echo $row['id']; ?>][precio]" 
                                           step="0.01" 
                                           min="0.01" 
                                           value="<?php echo $row['precio']; ?>" 
                                           class="input-tabla input-precio" required>
                                </td>
                                
                                <!-- Botón de Eliminación Directa -->
                                <td style="text-align: center;">
                                    <a href="cambiar_precios.php?eliminar=<?php echo $row['id']; ?>" 
                                       class="btn-eliminar" 
                                       onclick="return confirm('¿Seguro que deseas eliminar este producto del menú?');">
                                       Eliminar
                                    </a>
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