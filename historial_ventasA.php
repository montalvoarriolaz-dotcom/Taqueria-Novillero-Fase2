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

// ACCIÓN EXCLUSIVA DE ADMIN: BORRAR REGISTRO DEL HISTORIAL
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['borrar_venta'])) {
    $id_venta_borrar = intval($_POST['id_venta_borrar']);
    
    // Eliminar el registro del historial de ventas
    $sql_borrar = "DELETE FROM historial_ventas WHERE id_venta = $id_venta_borrar";
    if ($conexion->query($sql_borrar)) {
        $mensaje = "¡Registro de venta #$id_venta_borrar eliminado correctamente del historial!";
    } else {
        $mensaje = "Error al intentar eliminar el registro.";
    }
}

// Consulta para traer las ventas cobradas con el desglose de sus productos
$sql = "SELECT h.id_venta, h.id_pedido, h.total, h.cobrado_por, h.fecha_hora_cobro,
               GROUP_CONCAT(CONCAT(d.cantidad, 'x ', p.nombre) SEPARATOR '<br>') AS productos_desglosados,
               GROUP_CONCAT(CONCAT(d.cantidad, '|||', p.nombre, '|||', p.precio) SEPARATOR '###') AS ticket_data
        FROM historial_ventas h
        INNER JOIN detalle_pedidos d ON h.id_pedido = d.id_pedido
        INNER JOIN productos p ON d.id_producto = p.id
        GROUP BY h.id_venta
        ORDER BY h.fecha_hora_cobro DESC";

$resultado = $conexion->query($sql);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Historial de Ventas (Admin) - Taquería Novillero</title>
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
            max-width: 1000px;
            background-color: #ffffff;
            padding: 25px;
            border-radius: 20px; /* Esquinas redondeadas */
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            border: 2px solid #d32f2f; /* Borde rojo quemado para entorno Admin */
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

        .mensaje-alerta {
            background-color: #edf7ed;
            color: #1e4620;
            padding: 12px;
            border-radius: 12px;
            font-weight: bold;
            margin-bottom: 20px;
            border-left: 6px solid #4caf50;
            font-size: 14px;
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
            background-color: #d32f2f; /* Encabezado Rojo Administrativo */
            color: #ffffff;
            padding: 12px;
            text-transform: uppercase;
            font-size: 11px;
            letter-spacing: 1px;
        }

        td {
            padding: 12px;
            background-color: #ffffff;
            border-bottom: 1px solid #f4ece1;
            color: #2c1d11;
            font-size: 13px;
            vertical-align: middle;
        }

        tr:last-child td {
            border-bottom: none;
        }

        .acciones-box {
            display: flex;
            flex-direction: column;
            gap: 6px;
            align-items: center;
        }

        .btn-ticket {
            display: block;
            width: 90px;
            padding: 6px 0;
            background-color: #2c1d11; /* Negro carbón */
            color: #ffffff;
            text-decoration: none;
            font-weight: bold;
            font-size: 11px;
            text-transform: uppercase;
            border-radius: 8px;
            transition: background 0.2s;
            text-align: center;
            cursor: pointer;
        }

        .btn-ticket:hover {
            background-color: #3d2514;
        }

        .btn-borrar {
            display: block;
            width: 90px;
            padding: 6px 0;
            background-color: #d32f2f; /* Rojo */
            color: #ffffff;
            border: none;
            font-family: inherit;
            font-weight: bold;
            font-size: 11px;
            text-transform: uppercase;
            border-radius: 8px;
            cursor: pointer;
            transition: background 0.2s;
            text-align: center;
        }

        .btn-borrar:hover {
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
        <h2>Historial de Ventas Cobradas</h2>
        <p class="sub-titulo">MÓDULO DE EDICIÓN Y CONSULTA EXCLUSIVO ADMINISTRADOR</p>

        <?php if (!empty($mensaje)): ?>
            <div class="mensaje-alerta"><?php echo $mensaje; ?></div>
        <?php endif; ?>

        <table>
            <thead>
                <tr>
                    <th style="text-align: center; width: 80px;">ID Venta</th>
                    <th style="text-align: center; width: 80px;">ID Pedido</th>
                    <th style="text-align: left; width: 160px;">Fecha y Hora</th>
                    <th style="text-align: left; width: 130px;">Cobrado Por</th>
                    <th style="text-align: left;">Productos Vendidos</th>
                    <th style="text-align: right; width: 100px;">Total</th>
                    <th style="text-align: center; width: 120px;">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($resultado && $resultado->num_rows > 0): ?>
                    <?php while ($venta = $resultado->fetch_assoc()): ?>
                        <tr>
                            <td style="text-align: center;">#<?php echo $venta['id_venta']; ?></td>
                            <td style="text-align: center;">#<?php echo $venta['id_pedido']; ?></td>
                            <td><?php echo $venta['fecha_hora_cobro']; ?></td>
                            <td><strong><?php echo htmlspecialchars($venta['cobrado_por']); ?></strong></td>
                            <td style="line-height: 1.4;"><?php echo $venta['productos_desglosados']; ?></td>
                            <td style="text-align: right; font-weight: bold; color: #b71c1c;">$<?php echo number_format($venta['total'], 2); ?></td>
                            <td style="text-align: center;">
                                <div class="acciones-box">
                                    <button type="button" class="btn-ticket" onclick="abrirTicketPopUp('<?php echo $venta['id_venta']; ?>', '<?php echo $venta['id_pedido']; ?>', '<?php echo $venta['fecha_hora_cobro']; ?>', '<?php echo htmlspecialchars($venta['cobrado_por']); ?>', '<?php echo $venta['total']; ?>', '<?php echo rawurlencode($venta['ticket_data']); ?>')">
                                        Ticket
                                    </button>
                                    
                                    <form action="historial_ventasA.php" method="POST" onsubmit="return confirm('¿Está seguro de eliminar permanentemente la venta #<?php echo $venta['id_venta']; ?> del historial? Esta acción no se puede deshacer.');" style="margin:0;">
                                        <input type="hidden" name="id_venta_borrar" value="<?php echo $venta['id_venta']; ?>">
                                        <button type="submit" name="borrar_venta" class="btn-borrar">Borrar</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" style="text-align: center; font-style: italic; padding: 20px;">No hay registros de ventas guardados.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>

        <a href="panel_admin.php" class="btn-volver">← Volver al Panel Admin</a>
    </div>

    <script>
    function abrirTicketPopUp(idVenta, idPedido, fecha, atendio, totalStr, rawData) {
        let total = parseFloat(totalStr);
        let subtotal = total / 1.16;
        let iva = total - subtotal;
        
        let propina10 = total * 0.10;
        let propina15 = total * 0.15;

        // Procesar los productos desglosados
        let dataDecodificada = decodeURIComponent(rawData);
        let items = dataDecodificada.split('###');
        let filasProductosHtml = '';

        items.forEach(function(item) {
            let partes = item.split('|||');
            if (partes.length === 3) {
                let cant = partes[0];
                let nombre = partes[1];
                let precioUnitario = parseFloat(partes[2]);
                let importe = cant * precioUnitario;
                
                filasProductosHtml += `
                    <tr>
                        <td>${cant} x ${nombre}</td>
                        <td style="text-align: right;">$${importe.toFixed(2)}</td>
                    </tr>
                `;
            }
        });

        // Configuración de la ventana pop-up (350px de ancho simulando ticketera térmica)
        let ancho = 380;
        let alto = 600;
        let izquierda = (screen.width - ancho) / 2;
        let arriba = (screen.height - alto) / 2;
        
        let opciones = `width=${ancho},height=${alto},top=${arriba},left=${izquierda},toolbar=no,location=no,status=no,menubar=no,scrollbars=yes`;
        let ventanaTicket = window.open('', '_blank', opciones);

        // Contenido HTML y Estilos rústicos del Ticket de la Taquería
        let contenidoTicket = `
            <!DOCTYPE html>
            <html>
            <head>
                <meta charset="UTF-8">
                <title>Ticket Venta #${idVenta}</title>
                <style>
                    body {
                        font-family: 'Courier New', Courier, monospace;
                        font-size: 13px;
                        color: #000;
                        margin: 10px;
                        padding: 0;
                        background-color: #fff;
                    }
                    .text-center { text-align: center; }
                    .titulo { font-size: 16px; font-weight: bold; margin-bottom: 2px; text-transform: uppercase; }
                    .separador { border-top: 1px dashed #000; margin: 10px 0; }
                    table { width: 100%; border-collapse: collapse; }
                    th { border-bottom: 1px solid #000; text-align: left; }
                    .totales-table td { padding: 2px 0; }
                    .propina-box { font-size: 11px; font-style: italic; background-color: #f9f9f9; padding: 5px; border: 1px dotted #000; }
                    .btn-print {
                        display: block; width: 100%; max-width: 150px; margin: 20px auto; padding: 8px;
                        background-color: #000; color: #fff; border: none; text-align: center;
                        font-weight: bold; cursor: pointer; font-family: inherit;
                    }
                    @media print {
                        .btn-print { display: none; }
                    }
                </style>
            </head>
            <body>
                <div class="text-center">
                    <div class="titulo">TAQUERÍA NOVILLERO</div>
                    <div>¡Los mejores tacos al carbón!</div>
                    <div>Venta: #${idVenta} | Pedido: #${idPedido}</div>
                    <div>Fecha: ${fecha}</div>
                    <div>Atendió: ${atendio}</div>
                </div>

                <div class="separador"></div>

                <table>
                    <thead>
                        <tr>
                            <th>Descripción</th>
                            <th style="text-align: right; width: 80px;">Importe</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${filasProductosHtml}
                    </tbody>
                </table>

                <div class="separador"></div>

                <table class="totales-table">
                    <tr>
                        <td>Subtotal (Sin IVA):</td>
                        <td style="text-align: right;">$${subtotal.toFixed(2)}</td>
                    </tr>
                    <tr>
                        <td>IVA (16% Incluido):</td>
                        <td style="text-align: right;">$${iva.toFixed(2)}</td>
                    </tr>
                    <tr style="font-weight: bold; font-size: 14px;">
                        <td>TOTAL NETO:</td>
                        <td style="text-align: right;">$${total.toFixed(2)}</td>
                    </tr>
                </table>

                <div class="separador"></div>

                <div class="propina-box">
                    <div style="font-weight: bold; margin-bottom: 4px; text-align: center;">Sugerencia de Propinas:</div>
                    <div>• 10% Recomendado: $${propina10.toFixed(2)} (Total: $${(total + propina10).toFixed(2)})</div>
                    <div>• 15% Excelente servicio: $${propina15.toFixed(2)} (Total: $${(total + propina15).toFixed(2)})</div>
                </div>

                <div class="text-center" style="margin-top: 20px; font-weight: bold;">
                    ¡Gracias por su preferencia!
                </div>

                <button class="btn-print" onclick="window.print()">Imprimir Ticket</button>
            </body>
            </html>
        `;

        ventanaTicket.document.write(contenidoTicket);
        ventanaTicket.document.close();
    }
    </script>

</body>
</html>
<?php $conexion->close(); ?>