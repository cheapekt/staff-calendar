/**
 * JavaScript para el panel de administración del Portal del Trabajador
 */
(function ($) {
  "use strict";

  // Objeto principal para la administración del portal
  const WorkerPortalAdmin = {
    // Inicialización
    init: function () {
      this.setupExpensesModule();
    },

    // Configurar funcionalidades del módulo de gastos
    setupExpensesModule: function () {
      // Solo ejecutar si estamos en la página de gastos
      if (
        !$(".worker-portal-admin").length ||
        !$("body").hasClass("toplevel_page_worker-portal-expenses")
      ) {
        return;
      }

      this.setupExpenseModals();
      this.setupExpenseActions();
      this.setupBulkActions();
      this.setupExportExpenses();
      this.setupExpenseTypeControls();
    },

    // Configurar modales
    setupExpenseModals: function () {
      // Cerrar modal haciendo clic en el botón de cierre
      $(".worker-portal-modal-close").on("click", function () {
        $(this).closest(".worker-portal-modal").fadeOut();
      });

      // Cerrar modal haciendo clic fuera del contenido
      $(window).on("click", function (e) {
        if ($(e.target).hasClass("worker-portal-modal")) {
          $(".worker-portal-modal").fadeOut();
        }
      });

      // Ver recibo en modal
      $(document).on("click", ".view-receipt", function (e) {
        e.preventDefault();

        const receiptUrl = $(this).attr("href");
        let contentHtml = "";

        // Determinar tipo de contenido por extensión
        if (receiptUrl.toLowerCase().endsWith(".pdf")) {
          contentHtml = `<iframe src="${receiptUrl}" style="width:100%; height:500px; border:none;"></iframe>`;
        } else {
          contentHtml = `<img src="${receiptUrl}" style="max-width:100%; max-height:500px;">`;
        }

        // Mostrar en modal
        $("#receipt-modal-content").html(contentHtml);
        $("#receipt-modal").fadeIn();
      });

      // Ver detalles de gasto
      $(document).on("click", ".view-expense-details", function () {
        const expenseId = $(this).data("expense-id");

        // Cargar detalles mediante AJAX
        $.ajax({
          url: ajaxurl,
          type: "POST",
          data: {
            action: "get_expense_details",
            nonce: $("#_wpnonce").val(),
            expense_id: expenseId,
          },
          beforeSend: function () {
            $("#expense-details-content").html(
              '<div class="worker-portal-loader"><div class="worker-portal-loader-spinner"></div></div>'
            );
            $("#expense-details-modal").fadeIn();
          },
          success: function (response) {
            if (response.success) {
              $("#expense-details-content").html(response.data);
            } else {
              $("#expense-details-content").html(
                '<div class="notice notice-error"><p>' +
                  response.data +
                  "</p></div>"
              );
            }
          },
          error: function () {
            $("#expense-details-content").html(
              '<div class="notice notice-error"><p>Ha ocurrido un error al cargar los detalles.</p></div>'
            );
          },
        });
      });
    },

    // Configurar acciones sobre gastos
    setupExpenseActions: function () {
      // Aprobar un gasto
      $(document).on("click", ".approve-expense", function () {
        const expenseId = $(this).data("expense-id");

        // Confirmar acción
        if (!confirm("¿Estás seguro de aprobar este gasto?")) {
          return;
        }

        // Enviar solicitud AJAX
        $.ajax({
          url: ajaxurl,
          type: "POST",
          data: {
            action: "approve_expense",
            nonce: $("#_wpnonce").val(),
            expense_id: expenseId,
          },
          beforeSend: function () {
            $(this).prop("disabled", true).text("Procesando...");
          }.bind(this),
          success: function (response) {
            if (response.success) {
              alert(response.data);
              window.location.reload();
            } else {
              alert(response.data);
              $(this)
                .prop("disabled", false)
                .html('<span class="dashicons dashicons-yes"></span> Aprobar');
            }
          }.bind(this),
          error: function () {
            alert("Ha ocurrido un error. Por favor, inténtalo de nuevo.");
            $(this)
              .prop("disabled", false)
              .html('<span class="dashicons dashicons-yes"></span> Aprobar');
          }.bind(this),
        });
      });

      // Rechazar un gasto
      $(document).on("click", ".reject-expense", function () {
        const expenseId = $(this).data("expense-id");

        // Confirmar acción
        if (!confirm("¿Estás seguro de denegar este gasto?")) {
          return;
        }

        // Enviar solicitud AJAX
        $.ajax({
          url: ajaxurl,
          type: "POST",
          data: {
            action: "reject_expense",
            nonce: $("#_wpnonce").val(),
            expense_id: expenseId,
          },
          beforeSend: function () {
            $(this).prop("disabled", true).text("Procesando...");
          }.bind(this),
          success: function (response) {
            if (response.success) {
              alert(response.data);
              window.location.reload();
            } else {
              alert(response.data);
              $(this)
                .prop("disabled", false)
                .html('<span class="dashicons dashicons-no"></span> Denegar');
            }
          }.bind(this),
          error: function () {
            alert("Ha ocurrido un error. Por favor, inténtalo de nuevo.");
            $(this)
              .prop("disabled", false)
              .html('<span class="dashicons dashicons-no"></span> Denegar');
          }.bind(this),
        });
      });
    },

    // Configurar acciones masivas
    setupBulkActions: function () {
      // Seleccionar/deseleccionar todos los gastos
      $("#select-all-expenses").on("click", function () {
        $(".expense-checkbox").prop("checked", $(this).prop("checked"));
        WorkerPortalAdmin.checkBulkSelection();
      });

      // Comprobar selección para habilitar/deshabilitar botón de acción masiva
      $(".expense-checkbox").on("change", function () {
        WorkerPortalAdmin.checkBulkSelection();
      });

      // Inicialmente desactivar botón de acción masiva
      $("#apply-bulk-action").prop("disabled", true);

      // Enviar formulario de acciones masivas
      $("#bulk-approve-form").on("submit", function (e) {
        e.preventDefault();

        const action = $("#bulk-action").val();
        if (!action) {
          alert("Por favor, selecciona una acción.");
          return;
        }

        const checkedExpenses = $(".expense-checkbox:checked");
        if (checkedExpenses.length === 0) {
          alert("Por favor, selecciona al menos un gasto.");
          return;
        }

        // Confirmar acción
        if (!confirm("¿Estás seguro? Esta acción no se puede deshacer.")) {
          return;
        }

        // Recoger IDs de gastos seleccionados
        const expenseIds = [];
        checkedExpenses.each(function () {
          expenseIds.push($(this).val());
        });

        // Enviar solicitud AJAX
        $.ajax({
          url: ajaxurl,
          type: "POST",
          data: {
            action: "bulk_expense_action",
            nonce: $("#_wpnonce").val(),
            bulk_action: action,
            expense_ids: expenseIds,
          },
          beforeSend: function () {
            $("#apply-bulk-action")
              .prop("disabled", true)
              .text("Procesando...");
          },
          success: function (response) {
            if (response.success) {
              alert(response.data.message);
              window.location.reload();
            } else {
              alert(response.data);
              $("#apply-bulk-action").prop("disabled", false).text("Aplicar");
            }
          },
          error: function () {
            alert("Ha ocurrido un error. Por favor, inténtalo de nuevo.");
            $("#apply-bulk-action").prop("disabled", false).text("Aplicar");
          },
        });
      });
    },

    // Comprobar selección para acciones masivas
    checkBulkSelection: function () {
      if ($(".expense-checkbox:checked").length > 0) {
        $("#apply-bulk-action").prop("disabled", false);
      } else {
        $("#apply-bulk-action").prop("disabled", true);
      }
    },

    // Configurar exportación de gastos
    setupExportExpenses: function () {
      $("#export-expenses-button").on("click", function () {
        // Obtener parámetros de filtrado de la URL actual
        const urlParams = new URLSearchParams(window.location.search);
        const user_id = urlParams.get("user_id") || "";
        const expense_type = urlParams.get("expense_type") || "";
        const status = urlParams.get("status") || "";
        const date_from = urlParams.get("date_from") || "";
        const date_to = urlParams.get("date_to") || "";

        // Mostrar indicador de carga
        $(this)
          .prop("disabled", true)
          .html(
            '<span class="dashicons dashicons-update-alt spinning"></span> Exportando...'
          );

        // Realizar petición AJAX
        $.ajax({
          url: ajaxurl,
          type: "POST",
          data: {
            action: "admin_export_expenses",
            nonce: $("#_wpnonce").val(),
            user_id: user_id,
            expense_type: expense_type,
            status: status,
            date_from: date_from,
            date_to: date_to,
          },
          success: function (response) {
            if (response.success) {
              // Crear enlace para descargar
              const link = document.createElement("a");
              link.href = response.data.file_url;
              link.download = response.data.filename;
              link.style.display = "none";
              document.body.appendChild(link);
              link.click();
              document.body.removeChild(link);

              alert("El archivo ha sido generado correctamente.");
            } else {
              alert(response.data);
            }
          },
          error: function () {
            alert("Ha ocurrido un error durante la exportación.");
          },
          complete: function () {
            // Restaurar botón
            $("#export-expenses-button")
              .prop("disabled", false)
              .html(
                '<span class="dashicons dashicons-download"></span> Exportar a Excel'
              );
          },
        });
      });
    },

    // Configurar controles para tipos de gastos
    setupExpenseTypeControls: function () {
      // Añadir tipo de gasto
      $("#add-expense-type").on("click", function () {
        const newRow = `
                    <tr class="expense-type-row">
                        <td>
                            <input type="text" name="worker_portal_expense_types[keys][]" required>
                        </td>
                        <td>
                            <input type="text" name="worker_portal_expense_types[labels][]" required>
                        </td>
                        <td>
                            <button type="button" class="button button-small remove-expense-type">
                                <span class="dashicons dashicons-trash"></span>
                            </button>
                        </td>
                    </tr>
                `;

        $("#expense-types-list").append(newRow);
      });

      // Eliminar tipo de gasto
      $(document).on("click", ".remove-expense-type", function () {
        // Si solo queda un tipo, mostrar mensaje
        if ($(".expense-type-row").length <= 1) {
          alert("Debe existir al menos un tipo de gasto.");
          return;
        }

        $(this).closest("tr").remove();
      });
    },
  };

  // Inicializar cuando el DOM esté listo
  $(function () {
    WorkerPortalAdmin.init();
  });
})(jQuery);
