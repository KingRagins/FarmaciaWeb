$(document).ready(function () {
  $("#codigo_pedido").on("keyup", function () {
    let codigo = $(this).val().trim();
    if (codigo.length >= 5) {
      $.ajax({
        url: "controladores/buscar_pedido.php",
        type: "POST",
        data: { codigo_pedido: codigo },
        dataType: "json",
        success: function (data) {
          if (data.success) {
            mostrarPedidoEncontrado(data.pedido);
          } else {
            Swal.fire({
              icon: "error",
              title: "No encontrado",
              text:
                data.message ||
                "El código no corresponde a un pedido apartado.",
              timer: 2000,
              showConfirmButton: false,
            });
          }
        },
        error: function () {
          Swal.fire({
            icon: "error",
            title: "Error",
            text: "No se pudo conectar.",
          });
        },
      });
    }
  });
});

function mostrarPedidoEncontrado(pedido) {
  Swal.fire({
    icon: "success",
    title: `Pedido Encontrado: ${pedido.codigo_pedido}`,
    html: `
      <div style="text-align: left; font-size: 15px;">
        <p><strong>Cliente:</strong> ${pedido.nombre_cliente}</p>
        <p><strong>Correo:</strong> ${pedido.correo}</p>
        <p><strong>Fecha:</strong> ${pedido.fecha_apartado}</p>
        <hr>
        <p><strong>Productos:</strong></p>
        <ul style="padding-left: 20px; margin: 0;">
          ${pedido.productos
            .map(
              (p) =>
                `<li><strong>${p.nombre}</strong> × ${
                  p.dp_cantidad
                } = Bs.${parseFloat(p.subtotal).toFixed(2)}</li>`
            )
            .join("")}
        </ul>
        <hr>
        <p style="font-size: 18px; color: #28a745;">
          <strong>Total: Bs.${parseFloat(pedido.total).toFixed(2)}</strong>
        </p>
      </div>
    `,
    width: "600px",
    confirmButtonText: "Aceptar",
  }).then((result) => {
    if (result.isConfirmed) abrirModalPago(pedido);
  });
}

function abrirModalPago(pedido) {
  const bancos = [
    "Banco de Venezuela (BDV)",
    "Banco Nacional de Crédito (BNC)",
    "BBVA Provincial",
    "Banco Mercantil",
    "Banesco",
    "Banco Bicentenario",
    "Banco del Tesoro",
    "Banco Exterior",
    "Banco Caroní",
    "Banco de la Mujer",
    "Banco de las Fuerzas Armadas (BFA)",
    "Banco Industrial de Venezuela (BIV)",
    "Banco Internacional de Desarrollo (BID)",
    "Banco Occidental de Descuento (BOD)",
    "Banco Popular Dominicano (BPD)",
    "Banco Provincial de Valencia (BPV)",
    "Banco Federal",
    "Banco Libertador",
    "Banco Plaza",
    "Banco Sofitasa",
    "Banco Unión",
    "Banco Venezolano de Crédito (BVC)",
    "Banco Visión",
    "Banco Way Carboní",
    "100% Banco",
  ];
  const selectBancos = bancos
    .map((b) => `<option value="${b}">${b}</option>`)
    .join("");

  // OBTENER TIPO DE CAMBIO EN TIEMPO REAL
  let tipoCambio = 50.0;
  $.ajax({
    url: "controladores/obtener_tipo_cambio.php",
    type: "GET",
    async: false,
    dataType: "json",
    success: function (data) {
      if (data.success) {
        tipoCambio = parseFloat(data.tipo_cambio);
      }
    },
    error: function () {
      console.warn(
        "No se pudo obtener el tipo de cambio. Usando valor por defecto: 50.00"
      );
    },
  });

  const modalHTML = `
    <div class="modal fade" id="modalPago" tabindex="-1">
      <div class="modal-dialog modal-lg">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">Registrar Pago - ${
              pedido.codigo_pedido
            }</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <p><strong>Total a pagar:</strong> Bs. ${parseFloat(
              pedido.total
            ).toLocaleString("es-VE", { minimumFractionDigits: 2 })}</p>

            <div class="mb-3">
              <label>Método de pago:</label>
              <select class="form-control" id="metodoPago">
                <option value="bolivares">Bolívares</option>
                <option value="divisas">Divisas (Dólares)</option>
                <option value="tarjeta">Tarjeta</option>
                <option value="pago_movil">Pago Móvil</option>
              </select>
            </div>

            <div id="divisaSection">
              <div class="mb-3">
                <label id="labelMonto">Monto recibido (USD):</label>
                <input type="number" step="0.01" min="0" class="form-control" id="montoRecibido" placeholder="0.00" inputmode="decimal">
              </div>
              <div class="alert" id="vueltoInfo" style="display:none; padding: 8px; margin-top: 10px;">
                <strong id="vueltoTexto"></strong>
              </div>
            </div>

            <div id="tarjetaSection" style="display:none;">
              <div class="mb-3">
                <label>Banco:</label>
                <select class="form-control" id="bancoTarjeta">${selectBancos}</select>
              </div>
            </div>

            <div id="movilSection" style="display:none;">
              <div class="mb-3">
                <label>Banco:</label>
                <select class="form-control" id="bancoMovil">${selectBancos}</select>
              </div>
              <div class="mb-3">
                <label>Referencia (4 dígitos):</label>
                <input type="text" class="form-control" id="referenciaMovil" maxlength="4" placeholder="Ej: 1234">
              </div>
            </div>

            <input type="hidden" id="vueltoUsd" value="0">
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
            <button type="button" id="btnConfirmarPago" class="btn btn-primary" disabled>Confirmar Pago</button>
          </div>
        </div>
      </div>
    </div>`;

  $("body").append(modalHTML);
  $("#modalPago").modal("show");

  // Cambiar etiqueta y mostrar/ocultar secciones
  $("#metodoPago").change(function () {
    const metodo = $(this).val();
    $("#divisaSection, #tarjetaSection, #movilSection").hide();
    $("#btnConfirmarPago").prop("disabled", true);
    $("#vueltoInfo").hide();

    if (metodo === "divisas" || metodo === "bolivares") {
      $("#divisaSection").show();
      $("#labelMonto").text(
        metodo === "divisas" ? "Monto recibido (USD):" : "Monto recibido (BS):"
      );
      calcularVuelto();
    } else if (metodo === "tarjeta") {
      $("#tarjetaSection").show();
      $("#btnConfirmarPago").prop("disabled", $("#bancoTarjeta").val() === "");
    } else if (metodo === "pago_movil") {
      $("#movilSection").show();
      validarPagoMovil();
    }
  });

  // Cálculo en tiempo real
  $("#montoRecibido").on("input", calcularVuelto);

  function calcularVuelto() {
    const totalBs = parseFloat(pedido.total);
    const montoRecibido = parseFloat($("#montoRecibido").val()) || 0;
    const metodo = $("#metodoPago").val();
    const totalUsd = totalBs / tipoCambio;

    let mensaje = "";
    let color = "";
    let habilitarBoton = false;
    let vueltoUsd = 0;

    if (metodo === "divisas") {
      // PAGO EN DÓLARES
      if (montoRecibido >= totalUsd) {
        vueltoUsd = montoRecibido - totalUsd;
        mensaje = `Vuelto: $${vueltoUsd.toFixed(2)} USD`;
        color = "alert-success";
        habilitarBoton = true;
      } else if (montoRecibido > 0) {
        const faltante = totalUsd - montoRecibido;
        mensaje = `Faltan $${faltante.toFixed(2)} USD`;
        color = "alert-danger";
      } else {
        $("#vueltoInfo").hide();
      }
    } else {
      // PAGO EN BOLÍVARES
      if (montoRecibido >= totalBs) {
        const vueltoBs = montoRecibido - totalBs;
        mensaje = `Vuelto: Bs. ${vueltoBs.toLocaleString("es-VE", {
          minimumFractionDigits: 2,
        })}`;
        color = "alert-success";
        habilitarBoton = true;
        // Guardamos vuelto en USD para el backend (opcional)
        vueltoUsd = vueltoBs / tipoCambio;
      } else if (montoRecibido > 0) {
        const faltanteBs = totalBs - montoRecibido;
        const faltanteUsd = faltanteBs / tipoCambio;
        mensaje = `Faltan $${faltanteUsd.toFixed(2)} USD`;
        color = "alert-danger";
      } else {
        $("#vueltoInfo").hide();
      }
    }

    if (mensaje) {
      $("#vueltoTexto").text(mensaje);
      $("#vueltoInfo")
        .removeClass("alert-success alert-danger")
        .addClass(color)
        .show();
    }

    $("#btnConfirmarPago").prop("disabled", !habilitarBoton);
    $("#vueltoUsd").val(vueltoUsd.toFixed(2)); // Para el backend
  }

  function validarPagoMovil() {
    const banco = $("#bancoMovil").val();
    const ref = $("#referenciaMovil").val();
    const habilitar = banco && ref.length === 4;
    $("#btnConfirmarPago").prop("disabled", !habilitar);
  }

  $("#bancoMovil, #referenciaMovil").on("change input", validarPagoMovil);

  // Confirmar pago
  $("#btnConfirmarPago").on("click", function () {
    const metodo = $("#metodoPago").val();
    let datosPago = {
      codigo_pedido: pedido.codigo_pedido,
      metodo_pago: metodo,
      vuelto_usd: $("#vueltoUsd").val(),
    };

    if (metodo === "divisas" || metodo === "bolivares") {
      datosPago.monto_recibido = parseFloat($("#montoRecibido").val());
    } else if (metodo === "tarjeta") {
      datosPago.banco = $("#bancoTarjeta").val();
    } else if (metodo === "pago_movil") {
      datosPago.banco = $("#bancoMovil").val();
      datosPago.referencia_pago = $("#referenciaMovil").val();
    }

    $.ajax({
      url: "controladores/registrar_pago.php",
      type: "POST",
      data: datosPago,
      dataType: "json",
      success: function (data) {
        if (data.success) {
          Swal.fire("Éxito!", data.message, "success");
          $("#modalPago").modal("hide");
          $("#codigo_pedido").val("");
        } else {
          Swal.fire("Error", data.message, "error");
        }
      },
      error: function () {
        Swal.fire("Error", "No se pudo registrar el pago.", "error");
      },
    });
  });

  // Limpiar al cerrar
  $("#modalPago").on("hidden.bs.modal", function () {
    $(this).remove();
  });
}
