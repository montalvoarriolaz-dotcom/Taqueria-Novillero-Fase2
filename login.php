<?php
session_start();
$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $conexion = new mysqli("localhost", "root", "", "taqueria_novillero");

    // Limpiar variables por seguridad básica
    $user = $conexion->real_escape_string($_POST['usuario']);
    $pass = $conexion->real_escape_string($_POST['contrasena']);

    $sql = "SELECT usuario, rol FROM usuarios WHERE usuario = '$user' AND contrasena = '$pass'";
    $resultado = $conexion->query($sql);

    if ($resultado && $resultado->num_rows > 0) {
        $fila = $resultado->fetch_assoc();
        $_SESSION['usuario'] = $fila['usuario'];
        $_SESSION['rol'] = $fila['rol']; // Guardamos el rol por si acaso

        // Redirección dependiendo del rol
        if ($fila['rol'] == 'administrador') {
            header("Location: panel_admin.php");
            exit();
        } else {
            header("Location: panel_trabajador.php");
            exit();
        }
    } else {
        $error = "Usuario o contraseña incorrectos.";
    }
    $conexion->close();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Taquería Novillero - Login</title>
    <style>
        body {
            background-color: #f4ece1; /* Fondo color hueso/arena */
            font-family: 'Courier New', Courier, monospace; /* Estilo clásico/rústico */
            color: #2c1d11; /* Café muy oscuro para textos */
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }

        .login-box {
            background-color: #3d2514; /* Café cuero/madera */
            padding: 30px;
            border-radius: 20px; /* Bordes bien redondeados estilo letrero rústico */
            box-shadow: 0px 6px 15px rgba(0, 0, 0, 0.4);
            width: 100%;
            max-width: 350px;
            text-align: center;
            border: 3px double #d32f2f; /* Doble borde rojo tradicional */
        }

        h2 {
            color: #ffffff;
            margin-bottom: 5px;
            text-transform: uppercase;
            letter-spacing: 2px;
            font-size: 24px;
        }

        .subtitle {
            color: #ffc107; /* Dorado/Amarillo maíz para el subtítulo */
            font-size: 12px;
            margin-bottom: 25px;
            text-transform: uppercase;
            font-weight: bold;
        }

        label {
            color: #f4ece1;
            display: block;
            text-align: left;
            margin-bottom: 5px;
            font-weight: bold;
            font-size: 14px;
        }

        input[type="text"], input[type="password"] {
            width: 100%;
            padding: 10px;
            margin-bottom: 20px;
            border: 1px solid #2c1d11;
            background-color: #f4ece1;
            color: #2c1d11;
            font-family: inherit;
            font-size: 16px;
            box-sizing: border-box;
            border-radius: 10px; /* Campos de texto con puntas redondeadas */
        }

        input[type="text"]:focus, input[type="password"]:focus {
            outline: none;
            border: 2px solid #d32f2f; /* Brillo rojo al enfocar */
        }

        button {
            width: 100%;
            padding: 12px;
            background-color: #d32f2f; /* Rojo vivo/antiguo */
            color: #ffffff;
            border: none;
            font-weight: bold;
            font-size: 16px;
            text-transform: uppercase;
            cursor: pointer;
            letter-spacing: 1px;
            border-radius: 12px; /* Botón con puntas redondeadas */
            transition: background 0.3s ease, transform 0.1s ease;
            box-shadow: 0 4px 0 #991b1b;
        }

        button:hover {
            background-color: #b71c1c; /* Rojo más oscuro al pasar el mouse */
        }

        button:active {
            box-shadow: none;
            transform: translateY(4px); /* Efecto de presionado suave */
        }

        .error-msg {
            background-color: #fdf2f2;
            color: #b71c1c;
            padding: 10px;
            border-left: 5px solid #d32f2f;
            font-size: 13px;
            text-align: left;
            margin-bottom: 20px;
            font-weight: bold;
            border-top-right-radius: 8px;
            border-bottom-right-radius: 8px;
        }
    </style>
</head>
<body>

    <div class="login-box">
        <h2>TAQUERÍA NOVILLERO</h2>
        <div class="subtitle">SISTEMA DE CONTROL</div>

        <!-- Mensaje de error si fallan las credenciales -->
        <?php if(!empty($error)): ?>
            <div class="error-msg"><?php echo $error; ?></div>
        <?php endif; ?>

        <form action="login.php" method="POST">
            <label>USUARIO:</label>
            <input type="text" name="usuario" autocomplete="off" required>

            <label>CONTRASEÑA:</label>
            <input type="password" name="contrasena" required>

            <button type="submit">Ingresar al Rancho</button>
        </form>
    </div>

</body>
</html>