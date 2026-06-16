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

// ACCIÓN: El cocinero surte el pedido (Pasa de 'en cocina' a 'Servido')
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['surtir_pedido'])) {
    $id_pedido = intval($_POST['id_pedido']);
    
    // Cambiamos el estado a 'Servido' para que desaparezca de la cocina
    $sql_actualizar = "UPDATE pedidos_activos SET estado = 'Servido' WHERE id_pedido = $id_pedido";
    if ($conexion->query($sql_actualizar)) {
        $mensaje = "Pedido #$id_pedido enviado a mesa.";
    }
}

// CONSULTA: Solo traer pedidos cuyo estado visual sea 'en cocina'
$sql = "SELECT pa.id_pedido, pa.mesa, 
        GROUP_CONCAT(CONCAT(dp.cantidad, 'x ', prod.nombre) SEPARATOR '<br>') AS productos_detalle
        FROM pedidos_activos pa
        INNER JOIN detalle_pedidos dp ON pa.id_pedido = dp.id_pedido
        INNER JOIN productos prod ON dp.id_producto = prod.id
        WHERE pa.estado = 'en cocina'
        GROUP BY pa.id_pedido, pa.mesa
        ORDER BY pa.id_pedido ASC";

$resultado = $conexion->query($sql);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cocina (Admin) - Taquería Novillero</title>
    <style>
        body { 
            background-color: #2c1d11; 
            font-family: 'Courier New', Courier, monospace; 
            color: #ffffff; 
            padding: 20px; 
            margin: 0;
        }
        .header { 
            text-align: center; 
            border-bottom: 2px solid #d32f2f; 
            padding-bottom: 10px; 
            margin-bottom: 20px; 
        }
        .grid-pedidos { 
            display: grid; 
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); 
            gap: 20px; 
        }
        .card { 
            background-color: #ffffff; 
            color: #2c1d11; 
            padding: 20px; 
            border-radius: 15px; 
            border-left: 8px solid #d32f2f; 
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        }
        .mesa-num { 
            font-size: 24px; 
            font-weight: bold; 
            color: #d32f2f; 
        }
        .productos { 
            font-size: 18px; 
            margin: 15px 0; 
            line-height: 1.6; 
        }
        .btn-surtido { 
            width: 100%; 
            background-color: #4caf50; 
            color: white; 
            border: none; 
            padding: 12px; 
            font-size: 16px; 
            font-weight: bold; 
            border-radius: 8px; 
            cursor: pointer; 
            transition: background 0.2s;
        }
        .btn-surtido:hover {
            background-color: #43a047;
        }
        .btn-volver { 
            display: block; 
            width: 250px; 
            margin: 30px auto; 
            text-align: center; 
            color: white; 
            text-decoration: none; 
            background: #3d2514; 
            padding: 10px; 
            border-radius: 10px; 
            font-weight: bold;
        }
        .btn-volver:hover {
            background-color: #d32f2f;
        }
    </style>
</head>
<body>

    <div class="header">
        <h1>👨‍🍳 PEDIDOS EN COCINA (ADMIN)</h1>
    </div>

    <div class="grid-pedidos">
        <?php if ($resultado && $resultado->num_rows > 0): ?>
            <?php while ($pedido = $resultado->fetch_assoc()): ?>
                <div class="card">
                    <span class="mesa-num">MESA <?php echo $pedido['mesa']; ?></span>
                    <div class="productos"><?php echo $pedido['productos_detalle']; ?></div>
                    
                    <form method="POST" action="cocinaA.php">
                        <input type="hidden" name="id_pedido" value="<?php echo $pedido['id_pedido']; ?>">
                        <button type="submit" name="surtir_pedido" class="btn-surtido">SURTIDO ✓</button>
                    </form>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p style="text-align: center; width: 100%; color: #a1887f; font-size: 18px; font-style: italic;">¡Sin pendientes! Todo el carbón está libre.</p>
        <?php endif; ?>
    </div>

    <a href="panel_admin.php" class="btn-volver">← Volver al Panel Admin</a>

    <script>setTimeout(function(){ location.reload(); }, 5000);</script>
</body>
</html>
<?php $conexion->close(); ?>