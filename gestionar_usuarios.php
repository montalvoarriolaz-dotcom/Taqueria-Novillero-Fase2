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

// 1. FLUJO PARA REGISTRAR UN NUEVO USUARIO
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['agregar_usuario'])) {
    $nuevo_user = trim($_POST['usuario']);
    $nueva_pass = trim($_POST['contrasena']);
    $nuevo_rol  = $_POST['rol'];

    if (!empty($nuevo_user) && !empty($nueva_pass)) {
        // Verificar primero si el usuario ya existe para no duplicarlo
        $buscar = $conexion->query("SELECT id FROM usuarios WHERE usuario = '$nuevo_user'");
        if ($buscar && $buscar->num_rows > 0) {
            $mensaje = "Error: El nombre de usuario '$nuevo_user' ya está en uso.";
            $tipo_mensaje = "error";
        } else {
            // Insertar el nuevo usuario
            $sql_insert = "INSERT INTO usuarios (usuario, contrasena, rol) VALUES ('$nuevo_user', '$nueva_pass', '$nuevo_rol')";
            if ($conexion->query($sql_insert)) {
                $mensaje = "¡Usuario '$nuevo_user' registrado con éxito!";
                $tipo_mensaje = "exito";
            }
        }
    } else {
        $mensaje = "Error: Todos los campos son obligatorios.";
        $tipo_mensaje = "error";
    }
}

// 2. FLUJO PARA ELIMINAR UN USUARIO
if (isset($_GET['eliminar'])) {
    $id_eliminar = intval($_GET['eliminar']);
    
    // CANDADO DE SEGURIDAD EXCLUSIVO: Evitar borrar al Dueño Absoluto (ID 7)
    if ($id_eliminar === 7) {
        $mensaje = "Error: El perfil del Dueño Absoluto está blindado y no puede ser eliminado.";
        $tipo_mensaje = "error";
    }
    // Evitar que el administrador se elimine a sí mismo por accidente
    elseif (isset($_SESSION['id_usuario']) && $id_eliminar == $_SESSION['id_usuario']) {
        $mensaje = "Error: No puedes eliminar tu propia cuenta en sesión activa.";
        $tipo_mensaje = "error";
    } else {
        $conexion->query("DELETE FROM usuarios WHERE id = $id_eliminar");
        $mensaje = "Usuario eliminado correctamente.";
        $tipo_mensaje = "exito";
    }
}

// Consultar la lista completa de usuarios para la tabla
$resultado_usuarios = $conexion->query("SELECT id, usuario, contrasena, rol FROM usuarios ORDER BY rol, usuario");
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Personal - Taquería Novillero</title>
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
        .form-registro {
            background-color: #fcf8f2;
            padding: 20px;
            border-radius: 15px;
            border: 1px dashed #3d2514;
            max-width: 450px;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            font-weight: bold;
            font-size: 13px;
            margin-bottom: 5px;
            color: #3d2514;
            text-transform: uppercase;
        }

        .form-group input[type="text"], 
        .form-group select {
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

        .btn-agregar {
            display: inline-block;
            padding: 10px 20px;
            font-weight: bold;
            font-family: inherit;
            background-color: #4caf50; /* Verde Éxito rústico */
            color: #ffffff;
            border: none;
            cursor: pointer;
            text-transform: uppercase;
            font-size: 12px;
            border-radius: 10px;
            transition: background 0.2s;
            box-shadow: 0 3px 0 #2e7d32;
        }

        .btn-agregar:hover {
            background-color: #43a047;
        }

        .btn-agregar:active {
            box-shadow: none;
            transform: translateY(3px);
        }

        /* Tabla de Personal */
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

        .rol-tag {
            font-weight: bold;
            font-size: 11px;
            padding: 3px 8px;
            border-radius: 6px;
            text-transform: uppercase;
        }
        .rol-admin {
            background-color: #fde8e8;
            color: #c81e1e;
            border: 1px solid #f8b4b4;
        }
        .rol-trabajador {
            background-color: #e1effe;
            color: #1e429f;
            border: 1px solid #b3d1ff;
        }

        .btn-eliminar {
            display: inline-block;
            padding: 5px 10px;
            background-color: #d32f2f;
            color: #ffffff;
            text-decoration: none;
            font-weight: bold;
            font-size: 11px;
            text-transform: uppercase;
            border-radius: 6px;
            transition: background 0.2s;
            border: 1px solid #b71c1c;
        }

        .btn-eliminar:hover {
            background-color: #b71c1c;
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
        <h2>Panel de Administración: Gestión de Personal</h2>
        <p class="sub-titulo">CONTROL DE CUENTAS DE ACCESO AL SISTEMA</p>

        <?php if (!empty($mensaje)): ?>
            <div class="mensaje-alerta <?php echo ($tipo_mensaje == 'error') ? 'mensaje-error' : 'mensaje-exito'; ?>">
                <?php echo $mensaje; ?>
            </div>
        <?php endif; ?>

        <h3>Registrar Nuevo Personal</h3>
        <form action="gestionar_usuarios.php" method="POST" class="form-registro">
            <div class="form-group">
                <label>Usuario (Nombre de acceso):</label>
                <input type="text" name="usuario" required autocomplete="off">
            </div>

            <div class="form-group">
                <label>Contraseña:</label>
                <input type="text" name="contrasena" required autocomplete="off">
            </div>

            <div class="form-group">
                <label>Rol del Usuario:</label>
                <select name="rol">
                    <option value="trabajador">Trabajador (Cocina / Caja)</option>
                    <option value="administrador">Administrador (Acceso Total)</option>
                </select>
            </div>

            <button type="submit" name="agregar_usuario" class="btn-agregar">Agregar Usuario</button>
        </form>

        <hr style="border: 0; border-top: 1px dashed #3d2514; margin: 30px 0;">

        <h3>Usuarios Activos</h3>
        <table>
            <thead>
                <tr>
                    <th style="text-align: left;">Usuario</th>
                    <th style="text-align: left;">Contraseña en Claro</th>
                    <th style="text-align: center; width: 150px;">Rol asignado</th>
                    <th style="text-align: center; width: 110px;">Acción</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($resultado_usuarios && $resultado_usuarios->num_rows > 0): ?>
                    <?php while ($user = $resultado_usuarios->fetch_assoc()): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($user['usuario']); ?></strong></td>
                            <td style="font-family: monospace; color: #5c4033;"><?php echo htmlspecialchars($user['contrasena']); ?></td>
                            <td style="text-align: center;">
                                <?php if ($user['rol'] == 'administrador'): ?>
                                    <span class="rol-tag rol-admin">Admin</span>
                                <?php else: ?>
                                    <span class="rol-tag rol-trabajador">Trabajador</span>
                                <?php endif; ?>
                            </td>
                            <td style="text-align: center;">
                                <?php if ($user['id'] == 7): ?>
                                    <span style="color: #d32f2f; font-weight: bold; font-size: 12px; text-transform: uppercase;">🔒 Dueño</span>
                                <?php else: ?>
                                    <a href="gestionar_usuarios.php?eliminar=<?php echo $user['id']; ?>" 
                                       onclick="return confirm('¿Seguro que quieres eliminar permanentemente al usuario «<?php echo htmlspecialchars($user['usuario']); ?>»?');" 
                                       class="btn-eliminar">
                                        Eliminar
                                    </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="4" style="text-align: center; font-style: italic; padding: 20px;">No hay usuarios registrados.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>

        <a href="panel_admin.php" class="btn-volver">← Volver al Panel Admin</a>
    </div>

</body>
</html>
<?php $conexion->close(); ?>