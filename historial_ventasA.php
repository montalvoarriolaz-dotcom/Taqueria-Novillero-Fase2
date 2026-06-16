<?php

session_start();



// Validar que el usuario inició sesión

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



$notificacion = "";



// ACCIÓN: ELIMINAR UN REGISTRO PERMANENTEMENTE DEL HISTORIAL

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['eliminar_registro'])) {

    $id_venta_borrar = intval($_POST['id_venta_id']);

    

    $sql_delete = "DELETE FROM historial_ventas WHERE id_venta = $id_venta_borrar";

    if ($conexion->query($sql_delete)) {

        $notificacion = "🗑️ El registro de venta #$id_venta_borrar ha sido eliminado físicamente del historial.";

    } else {

        $notificacion = "❌ Error al intentar borrar el registro del historial.";

    }

}



// CONSULTA DIRECTA Y TOTAL A TU TABLA DE HISTORIAL

$sql = "SELECT id_venta, id_pedido, total, cobrado_por, fecha_hora_cobro, estado, productos_vendidos 

        FROM historial_ventas 

        ORDER BY fecha_hora_cobro DESC";

$resultado = $conexion->query($sql);

?>



<!DOCTYPE html>

<html lang="es">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Historial de Ventas - Taquería Novillero</title>

    <style>

        body { 

            background-color: #f4ece1; 

            font-family: 'Courier New', Courier, monospace; 

            color: #2c1d11; 

            padding: 20px; 

            margin: 0; 

            display: flex; 

            flex-direction: column; 

            align-items: center; 

        }

        .container { 

            width: 100%; 

            max-width: 1100px; 

            background-color: #ffffff; 

            padding: 25px; 

            border-radius: 20px; 

            box-shadow: 0 4px 12px rgba(0,0,0,0.1); 

            border: 2px solid #d32f2f; 

        }

        h2 { 

            color: #3d2514; 

            text-transform: uppercase; 

            text-align: center; 

            border-bottom: 3px double #d32f2f; 

            padding-bottom: 10px; 

            margin: 0 0 5px 0; 

        }

        .sub-titulo {

            text-align: center;

            margin-top: 5px;

            margin-bottom: 25px;

            font-size: 14px;

            font-style: italic;

            color: #b71c1c;

            font-weight: bold;

            text-transform: uppercase;

        }

        .alerta { 

            background-color: #fdf2f2; 

            color: #b71c1c; 

            padding: 12px; 

            border-radius: 12px; 

            font-weight: bold; 

            margin-bottom: 20px; 

            border-left: 6px solid #d32f2f; 

            text-align: center;

        }

        table { 

            width: 100%; 

            border-collapse: separate; 

            border-spacing: 0; 

            border: 1px solid #3d2514; 

            border-radius: 15px; 

            overflow: hidden; 

        }

        th { 

            background-color: #d32f2f; 

            color: #ffffff; 

            padding: 12px; 

            text-transform: uppercase; 

            font-size: 11px; 

            letter-spacing: 0.5px;

        }

        td { 

            padding: 12px; 

            background-color: #ffffff; 

            border-bottom: 1px solid #f4ece1; 

            font-size: 13px; 

            vertical-align: middle; 

            color: #2c1d11;

        }

        tr:last-child td { border-bottom: none; }

        

        /* Modificación visual para renglones cancelados */

        .cancelado-row td { 

            background-color: #fdf2f2 !important; 

            color: #7f2323; 

        }

        

        /* Badges de Estado */

        .badge { 

            padding: 5px 10px; 

            border-radius: 6px; 

            font-size: 11px; 

            font-weight: bold; 

            text-transform: uppercase;

            display: inline-block;

        }

        .badge-cobrado { background-color: #e8f5e9; color: #2e7d32; border: 1px solid #a5d6a7; }

        .badge-cancelado { background-color: #ffebee; color: #c62828; border: 1px solid #ef9a9a; }

        

        /* Botones */

        .acciones-wrapper { display: flex; flex-direction: column; gap: 6px; align-items: center; }

        .btn-ticket { width: 100px; padding: 7px 0; background-color: #2c1d11; color: #ffffff; font-weight: bold; font-size: 11px; text-transform: uppercase; border-radius: 8px; cursor: pointer; border: none; text-align: center; }

        .btn-ticket:hover { background-color: #d32f2f; }

        .btn-borrar { width: 100px; padding: 7px 0; background-color: #c62828; color: #ffffff; font-weight: bold; font-size: 11px; text-transform: uppercase; border-radius: 8px; cursor: pointer; border: none; text-align: center; }

        .btn-borrar:hover { background-color: #b71c1c; }

        

        .btn-regresar { display: block; width: 240px; margin: 25px auto 0 auto; padding: 12px; background-color: #3d2514; color: #ffffff; text-decoration: none; text-align: center; font-weight: bold; border-radius: 12px; text-transform: uppercase; box-shadow: 0 4px 0 #1c1007; }

        .btn-regresar:hover { background-color: #d32f2f; box-shadow: 0 4px 0 #991b1b; }

    </style>

</head>

<body>



<div class="container">

    <h2>Historial de Ventas</h2>

    <p class="sub-titulo">Taquería Novillero — Registro de Auditoría</p>



    <?php if (!empty($notificacion)): ?>

        <div class="alerta"><?php echo $notificacion; ?></div>

    <?php endif; ?>



    <table>

        <thead>

            <tr>

                <th style="width: 90px; text-align: center;">ID Venta</th>

                <th style="width: 80px; text-align: center;">Pedido</th>

                <th style="width: 170px; text-align: left;">Fecha y Hora</th>

                <th style="width: 130px; text-align: left;">Quién Vendió</th>

                <th style="text-align: left;">Qué Vendió (Productos)</th>

                <th style="width: 110px; text-align: center;">Estado</th>

                <th style="width: 100px; text-align: right;">Total</th>

                <th style="width: 130px; text-align: center;">Acciones</th>

            </tr>

        </thead>

        <tbody>

            <?php if ($resultado && $resultado->num_rows > 0): ?>

                <?php while ($v = $resultado->fetch_assoc()): ?>

                    <?php 

                    $esCancelado = ($v['estado'] === 'CANCELADO'); 

                    // Reemplazamos los saltos de línea (\n) por br para que se vean ordenados en la tabla

                    $productosCelda = nl2br(htmlspecialchars($v['productos_vendidos']));

                    ?>

                    <tr class="<?php echo $esCancelado ? 'cancelado-row' : ''; ?>">

                        <td style="text-align: center; font-weight: bold;">#<?php echo $v['id_venta']; ?></td>

                        <td style="text-align: center;">#<?php echo $v['id_pedido']; ?></td>

                        <td><?php echo $v['fecha_hora_cobro']; ?></td>

                        <td><strong><?php echo htmlspecialchars($v['cobrado_por']); ?></strong></td>

                        <td style="line-height: 1.5; font-size: 12px; font-weight: bold;"><?php echo $productosCelda; ?></td>

                        <td style="text-align: center;">

                            <span class="badge <?php echo $esCancelado ? 'badge-cancelado' : 'badge-cobrado'; ?>">

                                <?php echo ($v['estado'] === 'COBRADO' || $v['estado'] === 'PAGADO') ? '✔ COBRADO' : '❌ CANCELADO'; ?>

                            </span>

                        </td>

                        <td style="text-align: right; font-weight: bold; color: #b71c1c; font-size: 14px;">

                            $<?php echo number_format($v['total'], 2); ?>

                        </td>

                        <td style="text-align: center;">

                            <div class="acciones-wrapper">

                                <button type="button" class="btn-ticket" onclick="generarTicketPopup('<?php echo $v['id_venta']; ?>', '<?php echo $v['id_pedido']; ?>', '<?php echo $v['fecha_hora_cobro']; ?>', '<?php echo htmlspecialchars($v['cobrado_por']); ?>', '<?php echo $v['total']; ?>', '<?php echo rawurlencode($v['productos_vendidos']); ?>', '<?php echo $v['estado']; ?>')">

                                    Ticket 🖨️

                                </button>

                                

                                <form action="historial_ventasA.php" method="POST" onsubmit="return confirm('¿Estás completamente seguro de borrar el registro físico de la venta #<?php echo $v['id_venta']; ?>?\nEsta acción no se puede revertir.');" style="margin:0;">

                                    <input type="hidden" name="id_venta_id" value="<?php echo $v['id_venta']; ?>">

                                    <button type="submit" name="eliminar_registro" class="btn-borrar">Borrar</button>

                                </form>

                            </div>

                        </td>

                    </tr>

                <?php endwhile; ?>

            <?php else: ?>

                <tr>

                    <td colspan="8" style="text-align: center; font-style: italic; padding: 25px; color: #777;">No existen registros de ventas o cancelaciones en el historial.</td>

                </tr>

            <?php endif; ?>

        </tbody>

    </table>



    <a href="panel_admin.php" class="btn-regresar">← Volver al Panel Admin</a>

</div>



<script>

function generarTicketPopup(idVenta, idPedido, fecha, atendio, totalStr, rawData, estado) {

    let total = parseFloat(totalStr);

    let subtotal = total / 1.16;

    let iva = total - subtotal;

    

    let propina10 = total * 0.10;

    let propina15 = total * 0.15;



    // Convertir saltos de línea de la BD en estructura HTML para el ticket plano

    let textoProductos = decodeURIComponent(rawData);

    let renglones = textoProductos.split('\n');

    let tablaProductosHtml = '';



    renglones.forEach(function(linea) {

        if (linea.trim() !== '') {

            tablaProductosHtml += `

                <tr>

                    <td style="padding: 4px 0; font-size: 13px;">${linea}</td>

                    <td style="text-align: right; font-size: 12px; color:#444;">Incluido</td>

                </tr>

            `;

        }

    });



    // Centrado físico y dimensiones estilo comanda (80mm o similar estándar)

    let ancho = 400; let alto = 660;

    let x = (screen.width - ancho) / 2;

    let y = (screen.height - alto) / 2;

    let configuracion = `width=${ancho},height=${alto},top=${y},left=${x},toolbar=no,menubar=no,scrollbars=yes`;

    

    let ticketWin = window.open('', '_blank', configuracion);



    let estructuraTicket = `

        <!DOCTYPE html>

        <html>

        <head>

            <meta charset="UTF-8">

            <title>Ticket_Venta_${idVenta}</title>

            <style>

                body { font-family: 'Courier New', Courier, monospace; font-size: 13px; margin: 15px; color: #000; background-color: #fff; }

                .center { text-align: center; }

                .titulo-negocio { font-size: 18px; font-weight: bold; text-transform: uppercase; }

                .linea-puntos { border-top: 1px dashed #000; margin: 8px 0; }

                .status-alerta { border: 2px solid #000; padding: 5px; display: inline-block; font-weight: bold; margin: 8px 0; font-size: 12px; }

                table { width: 100%; border-collapse: collapse; }

                .totales-tabla td { padding: 3px 0; }

                .box-sugerencias { font-size: 11px; background:#f5f5f5; padding: 5px; border:1px dotted #000; margin-top: 12px; line-height: 1.4; }

                .btn-impresion { display: block; width: 100%; background: #000; color: #fff; border: none; padding: 10px; margin-top: 20px; font-weight: bold; cursor: pointer; font-family: inherit; text-transform: uppercase; font-size: 12px; }

                @media print { .btn-impresion { display: none; } }

            </style>

        </head>

        <body>

            <div class="center">

                <div class="titulo-negocio">TAQUERÍA NOVILLERO</div>

                <div>¡Los mejores tacos al carbón!</div>

                <div class="linea-puntos"></div>

                <div><strong>COMPROBANTE ORIGINAL</strong></div>

                <div class="status-alerta">${(estado === 'CANCELADO') ? '❌ VENTA CANCELADA / SIN COBRO' : '✔ CUENTA COBRADA / PAGADA'}</div>

                <div style="margin-top: 4px;">Folio Venta: #${idVenta} | Pedido Ref: #${idPedido}</div>

                <div>Fecha de Mov.: ${fecha}</div>

                <div>Atendido por: ${atendio}</div>

            </div>



            <div class="linea-puntos"></div>



            <table>

                <thead>

                    <tr>

                        <th style="text-align: left; border-bottom: 1px solid #000; font-size: 12px;">Cant. y Platillo</th>

                        <th style="text-align: right; border-bottom: 1px solid #000; font-size: 12px; width: 80px;">Precio</th>

                    </tr>

                </thead>

                <tbody>

                    ${tablaProductosHtml}

                </tbody>

            </table>



            <div class="linea-puntos"></div>



            <table class="totales-tabla">

                <tr><td>Subtotal:</td><td style="text-align: right;">$${subtotal.toFixed(2)}</td></tr>

                <tr><td>IVA (16% Incluido):</td><td style="text-align: right;">$${iva.toFixed(2)}</td></tr>

                <tr style="font-weight: bold; font-size: 14px;"><td>TOTAL COBRADO:</td><td style="text-align: right;">$${total.toFixed(2)}</td></tr>

            </table>



            <div class="linea-puntos"></div>



            <div class="box-sugerencias">

                <div style="font-weight:bold; text-align:center; margin-bottom: 2px;">Propina Sugerida voluntaria:</div>

                • Sugerido (10%): $${propina10.toFixed(2)} | Total estimado: $${(total + propina10).toFixed(2)}<br>

                • Excelente (15%): $${propina15.toFixed(2)} | Total estimado: $${(total + propina15).toFixed(2)}

            </div>



            <p class="center" style="font-weight: bold; margin-top: 15px; font-size: 12px;">

                ¡Muchas gracias por su preferencia!<br>Vuelva pronto.

            </p>



            <button class="btn-impresion" onclick="window.print()">Imprimir Comanda</button>

        </body>

        </html>

    `;



    ticketWin.document.write(estructuraTicket);

    ticketWin.document.close();

}

</script>



</body>

</html>

<?php $conexion->close(); ?>