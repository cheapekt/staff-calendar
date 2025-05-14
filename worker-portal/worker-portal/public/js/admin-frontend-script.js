/**
 * JavaScript corregido para el panel de administración en el frontend
 */
(function ($) {
  "use strict";

  // Objeto principal para el panel de administración en frontend
  const WorkerPortalAdminFrontend = {
    // Inicialización
    init: function () {
      this.setupNavigation();
      this.setupExpensesModule();
      this.setupWorksheetModule();
      this.setupModals();
      this.setupTimeclockModule();
    },

    // Configurar navegación entre pestañas
    setupNavigation: function () {
      // Navegación entre pestañas principales
      $(".worker-portal-tab-link").on("click", function (e) {
        e.preventDefault();

        // Ocultar todas las pestañas
        $(".worker-portal-tab-content").removeClass("active");

        // Remover clase activa de todos los enlaces
        $(".worker-portal-tab-link").removeClass("active");

        // Mostrar pestaña seleccionada
        const tab = $(this).data("tab");
        $("#tab-" + tab).addClass("active");

        // Activar enlace
        $(this).addClass("active");

        // Cargar contenido dinámico si es necesario
        if (tab === "pending-expenses") {
          WorkerPortalAdminFrontend.loadPendingExpenses();
        } else if (tab === "worksheets") {
          WorkerPortalAdminFrontend.loadWorksheets();
        }
        if (tab === "incentives") {
          // Initialize incentives module if it exists
          if (
            typeof WorkerPortalIncentives !== "undefined" &&
            $("#incentives-list-container").length
          ) {
            WorkerPortalIncentives.loadAdminIncentives();
          }
        }
      });

      // Navegación entre sub-pestañas
      $(".worker-portal-subtab-link").on("click", function (e) {
        e.preventDefault();

        // Ocultar todas las sub-pestañas
        $(".worker-portal-subtab-content").removeClass("active");

        // Remover clase activa de todos los enlaces
        $(".worker-portal-subtab-link").removeClass("active");

        // Mostrar sub-pestaña seleccionada
        const subtab = $(this).data("subtab");
        $("#subtab-" + subtab).addClass("active");

        // Activar enlace
        $(this).addClass("active");
      });

      // Navegación desde enlaces de estadísticas
      $(".worker-portal-admin-stat-action").on("click", function (e) {
        e.preventDefault();

        const tab = $(this).data("tab");
        $('.worker-portal-tab-link[data-tab="' + tab + '"]').click();
      });

      // Enlaces para tabs desde botones
      $(document).on("click", ".tab-nav-link", function (e) {
        e.preventDefault();

        const tab = $(this).data("tab");
        $('.worker-portal-tab-link[data-tab="' + tab + '"]').click();
      });
    },

    setupTimeclockModule: function () {
      // Filtros para fichajes
      $("#admin-timeclock-filter-form").on("submit", function (e) {
        e.preventDefault();
        WorkerPortalAdminFrontend.loadTimeclockEntries();
      });

      // Limpiar filtros de fichajes
      $("#clear-filters-timeclock").on("click", function () {
        $("#admin-timeclock-filter-form")[0].reset();
        WorkerPortalAdminFrontend.loadTimeclockEntries();
      });

      // Acciones para fichajes
      $(document).on("click", ".edit-entry", function () {
        const entryId = $(this).data("entry-id");
        WorkerPortalAdminFrontend.editTimeclockEntry(entryId);
      });

      $(document).on("click", ".register-exit", function () {
        const entryId = $(this).data("entry-id");
        WorkerPortalAdminFrontend.registerExitForEntry(entryId);
      });
    },

    // Cargar entradas de fichaje
    loadTimeclockEntries: function () {
      console.log("Cargando fichajes...");

      // Mostrar indicador de carga
      $("#timeclock-entries-container").html(
        '<div class="worker-portal-loading">' +
          '<div class="worker-portal-spinner"></div>' +
          "<p>Cargando fichajes...</p>" +
          "</div>"
      );

      // Obtener datos del formulario
      var formData = new FormData($("#admin-timeclock-filter-form")[0]);
      formData.append("action", "admin_load_timeclock_entries");
      formData.append("nonce", $("#admin_nonce").val());

      // Realizar petición AJAX
      $.ajax({
        url: ajaxurl,
        type: "POST",
        data: formData,
        processData: false,
        contentType: false,
        success: function (response) {
          if (response.success) {
            $("#timeclock-entries-container").html(response.data);
          } else {
            $("#timeclock-entries-container").html(
              '<div class="worker-portal-error">' +
                (response.data || "Error al cargar fichajes") +
                "</div>"
            );
          }
        },
        error: function () {
          $("#timeclock-entries-container").html(
            '<div class="worker-portal-error">' +
              "Error de comunicación con el servidor. Por favor, inténtalo de nuevo." +
              "</div>"
          );
        },
      });
    },

    // Editar entrada de fichaje
    editTimeclockEntry: function (entryId) {
      $.ajax({
        url: ajaxurl,
        type: "POST",
        data: {
          action: "admin_get_timeclock_entry",
          entry_id: entryId,
          nonce: $("#admin_nonce").val(),
        },
        beforeSend: function () {
          $("#timeclock-entry-details-content").html(
            '<div class="worker-portal-loading">' +
              '<div class="worker-portal-spinner"></div>' +
              "<p>Cargando detalles...</p>" +
              "</div>"
          );

          $("#timeclock-entry-details-modal").fadeIn(200);
        },
        success: function (response) {
          if (response.success) {
            $("#timeclock-entry-details-content").html(response.data);
          } else {
            $("#timeclock-entry-details-content").html(
              '<div class="worker-portal-error">' +
                (response.data || "Error al cargar detalles") +
                "</div>"
            );
          }
        },
        error: function () {
          $("#timeclock-entry-details-content").html(
            '<div class="worker-portal-error">' +
              "Error de comunicación con el servidor. Por favor, inténtalo de nuevo." +
              "</div>"
          );
        },
      });
    },

    // Registrar salida para una entrada
    registerExitForEntry: function (entryId) {
      if (
        !confirm(
          "¿Estás seguro de que deseas registrar la salida para este fichaje?"
        )
      ) {
        return;
      }

      $.ajax({
        url: ajaxurl,
        type: "POST",
        data: {
          action: "admin_register_exit",
          entry_id: entryId,
          nonce: $("#admin_nonce").val(),
        },
        success: function (response) {
          if (response.success) {
            alert(response.data.message);
            WorkerPortalAdminFrontend.loadTimeclockEntries();
          } else {
            alert(response.data || "Error al registrar la salida");
          }
        },
        error: function () {
          alert(
            "Error de comunicación con el servidor. Por favor, inténtalo de nuevo."
          );
        },
      });
    },

    // Configurar funcionalidades del módulo de gastos
    setupExpensesModule: function () {
      // Filtros para gastos pendientes
      $("#admin-expenses-filter-form").on("submit", function (e) {
        e.preventDefault();
        WorkerPortalAdminFrontend.loadPendingExpenses();
      });

      // Limpiar filtros
      $("#clear-filters-expenses").on("click", function () {
        $("#admin-expenses-filter-form")[0].reset();
        WorkerPortalAdminFrontend.loadPendingExpenses();
      });

      // Acciones para gastos (delegación de eventos)
      $(document).on("click", ".approve-expense", function () {
        const expenseId = $(this).data("expense-id");
        WorkerPortalAdminFrontend.approveExpense(expenseId);
      });

      $(document).on("click", ".reject-expense", function () {
        const expenseId = $(this).data("expense-id");
        WorkerPortalAdminFrontend.rejectExpense(expenseId);
      });

      $(document).on("click", ".view-expense", function () {
        const expenseId = $(this).data("expense-id");
        WorkerPortalAdminFrontend.viewExpenseDetails(expenseId);
      });

      // Seleccionar/deseleccionar todos los gastos
      $(document).on("click", "#select-all-expenses", function () {
        $(".expense-checkbox").prop("checked", $(this).prop("checked"));
        WorkerPortalAdminFrontend.checkBulkSelection();
      });

      // Verificar cambios en los checkboxes
      $(document).on("change", ".expense-checkbox", function () {
        WorkerPortalAdminFrontend.checkBulkSelection();
      });

      // Acciones masivas
      $("#bulk-approve-form").on("submit", function (e) {
        e.preventDefault();
        WorkerPortalAdminFrontend.processBulkAction();
      });
    },

    // Configurar funcionalidades del módulo de hojas de trabajo
    setupWorksheetModule: function () {
      // Filtros para hojas de trabajo
      $("#admin-worksheets-filter-form").on("submit", function (e) {
        e.preventDefault();
        WorkerPortalAdminFrontend.loadWorksheets();
      });

      // Limpiar filtros de hojas de trabajo
      $("#clear-filters-ws").on("click", function () {
        $("#admin-worksheets-filter-form")[0].reset();
        WorkerPortalAdminFrontend.loadWorksheets();
      });

      // Exportar hojas de trabajo
      $("#export-worksheets-button").on("click", function () {
        WorkerPortalAdminFrontend.exportWorksheets();
      });

      // Acciones para hojas de trabajo (delegación de eventos)
      // CORRECCIÓN: Delegación de eventos para compatibilidad con contenido cargado dinámicamente
      $(document).on("click", ".validate-worksheet", function () {
        const worksheetId = $(this).data("worksheet-id");
        WorkerPortalAdminFrontend.validateWorksheet(worksheetId);
      });

      $(document).on("click", ".view-worksheet", function () {
        const worksheetId = $(this).data("worksheet-id");
        WorkerPortalAdminFrontend.viewWorksheetDetails(worksheetId);
      });
    },

    // Configurar modales
    setupModals: function () {
      // Cerrar modales
      $(".worker-portal-modal-close").on("click", function () {
        $(this).closest(".worker-portal-modal").fadeOut();
      });

      // Cerrar haciendo clic fuera o con ESC
      $(window).on("click", function (e) {
        if ($(e.target).hasClass("worker-portal-modal")) {
          $(".worker-portal-modal").fadeOut();
        }
      });

      $(document).on("keydown", function (e) {
        if (e.key === "Escape" && $(".worker-portal-modal:visible").length) {
          $(".worker-portal-modal").fadeOut();
        }
      });

      // Visualizar recibo
      $(document).on("click", ".view-receipt", function (e) {
        e.preventDefault();
        const receiptUrl = $(this).attr("href");

        let contentHtml = "";

        // Detectar tipo de archivo
        if (receiptUrl.toLowerCase().endsWith(".pdf")) {
          contentHtml = `<iframe src="${receiptUrl}" style="width:100%; height:500px; border:none;"></iframe>`;
        } else {
          contentHtml = `<img src="${receiptUrl}" style="max-width:100%; max-height:500px;">`;
        }

        $("#receipt-modal-content").html(contentHtml);
        $("#receipt-modal").fadeIn();
      });
    },

    // Cargar gastos pendientes
    loadPendingExpenses: function () {
      console.log("Cargando gastos pendientes...");

      // Obtener valores de filtros
      const formData = new FormData($("#admin-expenses-filter-form")[0]);
      formData.append("action", "admin_load_pending_expenses");
      formData.append("nonce", $("#admin_nonce").val());

      // Mostrar indicador de carga
      $("#pending-expenses-list-container").html(
        '<div class="worker-portal-loading">' +
          '<div class="worker-portal-spinner"></div>' +
          "<p>Cargando gastos...</p>" +
          "</div>"
      );

      // Realizar petición AJAX
      $.ajax({
        url: ajaxurl,
        type: "POST",
        data: formData,
        processData: false,
        contentType: false,
        success: function (response) {
          console.log("Respuesta de gastos recibida", response);
          if (response.success) {
            $("#pending-expenses-list-container").html(response.data);
          } else {
            $("#pending-expenses-list-container").html(
              '<div class="worker-portal-error">' +
                (response.data || "Error desconocido al cargar los gastos") +
                "</div>"
            );
          }
        },
        error: function (xhr, status, error) {
          console.error("Error AJAX:", xhr, status, error);
          $("#pending-expenses-list-container").html(
            '<div class="worker-portal-error">' +
              "Error al cargar los gastos. Por favor, inténtalo de nuevo." +
              "</div>"
          );
        },
      });
    },

    // Cargar hojas de trabajo
    loadWorksheets: function () {
      console.log("Cargando hojas de trabajo...");
      // Mostrar indicador de carga
      $("#worksheets-list-container").html(
        '<div class="worker-portal-loading">' +
          '<div class="worker-portal-spinner"></div>' +
          "<p>Cargando hojas de trabajo...</p>" +
          "</div>"
      );
      // Crear objeto FormData para enviar los filtros
      const formData = new FormData();
      formData.append("action", "admin_load_worksheets");
      formData.append("nonce", $("#admin_nonce").val());
      // Añadir filtros si existen
      if ($("#filter-worker-ws").length) {
        formData.append("user_id", $("#filter-worker-ws").val() || "");
      }
      if ($("#filter-project").length) {
        formData.append("project_id", $("#filter-project").val() || "");
      }
      if ($("#filter-date-from-ws").length) {
        formData.append("date_from", $("#filter-date-from-ws").val() || "");
      }
      if ($("#filter-date-to-ws").length) {
        formData.append("date_to", $("#filter-date-to-ws").val() || "");
      }
      // Realizar petición AJAX
      $.ajax({
        url: ajaxurl,
        type: "POST",
        data: formData,
        processData: false,
        contentType: false,
        success: function (response) {
          console.log("Respuesta de hojas de trabajo:", response);
          if (response.success) {
            // Insertar el HTML recibido
            $("#worksheets-list-container").html(
              response.data.html ||
                "<p>No hay hojas de trabajo para mostrar</p>"
            );

            // MEJORAR APARIENCIA DE LA TABLA:

            // 1. Asegurarse de que la tabla tiene la clase correcta
            $("#worksheets-list-container table").addClass(
              "worker-portal-table"
            );

            // 2. Convertir todas las instancias de "Pendiente" a badges
            $("#worksheets-list-container td").each(function () {
              const text = $(this).text().trim();
              if (text === "Pendiente") {
                $(this).html(
                  '<span class="worker-portal-badge worker-portal-badge-warning">Pendiente</span>'
                );
              } else if (text === "Validada") {
                $(this).html(
                  '<span class="worker-portal-badge worker-portal-badge-success">Validada</span>'
                );
              }
            });

            // 3. Mejorar todos los botones existentes
            $("#worksheets-list-container button").each(function () {
              const buttonText = $(this).text().trim();

              // Limpiar clases existentes
              $(this).removeClass("validate-worksheet view-worksheet");

              // Mantener atributos data importantes
              const worksheetId =
                $(this).data("worksheet-id") || $(this).data("id");

              if (
                buttonText === "Validar" ||
                $(this).find(".dashicons-yes").length
              ) {
                $(this).addClass(
                  "worker-portal-button worker-portal-button-small worker-portal-button-primary validate-worksheet"
                );
                if (worksheetId) $(this).attr("data-worksheet-id", worksheetId);
              } else if (
                buttonText === "Detalles" ||
                $(this).find(".dashicons-visibility").length
              ) {
                $(this).addClass(
                  "worker-portal-button worker-portal-button-small worker-portal-button-secondary view-worksheet"
                );
                if (worksheetId) $(this).attr("data-worksheet-id", worksheetId);
              }
            });

            // 4. Mejorar apariencia de enlaces si existen
            $("#worksheets-list-container a").each(function () {
              const linkText = $(this).text().trim();

              if (linkText === "Validar") {
                $(this).addClass(
                  "worker-portal-button worker-portal-button-small worker-portal-button-primary"
                );
              } else if (linkText === "Detalles") {
                $(this).addClass(
                  "worker-portal-button worker-portal-button-small worker-portal-button-secondary"
                );
              }
            });

            // 5. Añadir clase al botón de exportar si existe
            $("button:contains('Exportar a Excel')").addClass(
              "worker-portal-button worker-portal-button-secondary"
            );

            // MANTENER FUNCIONALIDAD EXISTENTE:

            // Asegurar que los botones tengan el atributo correcto
            $(".validate-worksheet, .view-worksheet").each(function () {
              if ($(this).data("id") && !$(this).data("worksheet-id")) {
                $(this).attr("data-worksheet-id", $(this).data("id"));
              }
            });
          } else {
            $("#worksheets-list-container").html(
              '<div class="worker-portal-error">' +
                (response.data ||
                  "Error desconocido al cargar las hojas de trabajo") +
                "</div>"
            );
          }
        },
        error: function (xhr, status, error) {
          console.error("Error AJAX:", xhr, status, error);
          $("#worksheets-list-container").html(
            '<div class="worker-portal-error">' +
              "Error al cargar las hojas de trabajo. Por favor, inténtalo de nuevo." +
              "</div>"
          );
        },
      });
    },

    // Validar una hoja de trabajo
    validateWorksheet: function (worksheetId) {
      if (!confirm("¿Estás seguro de validar esta hoja de trabajo?")) {
        return;
      }

      $.ajax({
        url: ajaxurl,
        type: "POST",
        data: {
          action: "admin_validate_worksheet",
          nonce: $("#admin_nonce").val(),
          worksheet_id: worksheetId,
        },
        success: function (response) {
          if (response.success) {
            alert(response.data.message);
            // Cerrar modal si está abierto
            $("#worksheet-details-modal").fadeOut();
            // Recargar las hojas
            WorkerPortalAdminFrontend.loadWorksheets();
          } else {
            alert(response.data || "Error al validar la hoja de trabajo");
          }
        },
        error: function () {
          alert("Ha ocurrido un error. Por favor, inténtalo de nuevo.");
        },
      });
    },

    // Ver detalles de una hoja de trabajo (CORREGIDO)
    viewWorksheetDetails: function (worksheetId) {
      console.log("Mostrando detalles de la hoja: " + worksheetId);

      // Verificar la estructura del DOM
      if ($("#worksheet-details-modal").length === 0) {
        console.error("Modal no encontrado en el DOM");
        // Opcionalmente, crear el modal dinámicamente si no existe
        $("body").append(`
            <div id="worksheet-details-modal" class="worker-portal-modal">
                <div class="worker-portal-modal-content">
                    <div class="worker-portal-modal-header">
                        <h3>Detalles de la Hoja de Trabajo</h3>
                        <button type="button" class="worker-portal-modal-close">&times;</button>
                    </div>
                    <div class="worker-portal-modal-body">
                        <div id="worksheet-details-content"></div>
                    </div>
                </div>
            </div>
        `);

        // Reinicializar los eventos de cierre del modal
        $(".worker-portal-modal-close").on("click", function () {
          $(this).closest(".worker-portal-modal").fadeOut();
        });
      }

      $.ajax({
        url: ajaxurl,
        type: "POST",
        data: {
          action: "admin_get_worksheet_details",
          nonce: $("#admin_nonce").val(),
          worksheet_id: worksheetId,
        },
        beforeSend: function () {
          $("#worksheet-details-content").html(
            '<div class="worker-portal-loading">' +
              '<div class="worker-portal-spinner"></div>' +
              "<p>Cargando detalles...</p>" +
              "</div>"
          );

          // Forzar visibilidad del modal usando CSS inline
          $("#worksheet-details-modal").css({
            display: "block",
            visibility: "visible",
            opacity: "1",
            "z-index": "9999",
          });
        },
        success: function (response) {
          console.log("Respuesta de detalles:", response);
          if (response.success) {
            // Insertar directamente el HTML recibido
            $("#worksheet-details-content").html(response.data);

            // Asegurar que el botón de validar tenga el atributo correcto
            $("#worksheet-details-content .validate-worksheet").each(
              function () {
                if ($(this).data("id") && !$(this).data("worksheet-id")) {
                  $(this).attr("data-worksheet-id", $(this).data("id"));
                }
              }
            );

            // Forzar visibilidad del modal nuevamente para asegurar
            $("#worksheet-details-modal").css({
              display: "block",
              visibility: "visible",
              opacity: "1",
              "z-index": "9999",
            });
          } else {
            $("#worksheet-details-content").html(
              '<div class="worker-portal-error">' +
                (response.data || "Error al cargar los detalles") +
                "</div>"
            );
          }
        },
        error: function (xhr, status, error) {
          console.error("Error al cargar detalles:", xhr, status, error);
          $("#worksheet-details-content").html(
            '<div class="worker-portal-error">' +
              "Error al cargar los detalles. Por favor, inténtalo de nuevo." +
              "</div>"
          );
        },
      });
    },

    // Exportar hojas de trabajo (CORREGIDO)
    exportWorksheets: function () {
      const formData = new FormData();
      formData.append("action", "admin_export_worksheets");
      formData.append("nonce", $("#admin_nonce").val());

      // Añadir filtros si existen
      if ($("#filter-worker-ws").length) {
        formData.append("user_id", $("#filter-worker-ws").val() || "");
      }

      if ($("#filter-project").length) {
        formData.append("project_id", $("#filter-project").val() || "");
      }

      if ($("#filter-date-from-ws").length) {
        formData.append("date_from", $("#filter-date-from-ws").val() || "");
      }

      if ($("#filter-date-to-ws").length) {
        formData.append("date_to", $("#filter-date-to-ws").val() || "");
      }

      // Deshabilitar botón y mostrar indicador
      $("#export-worksheets-button")
        .prop("disabled", true)
        .html(
          '<i class="dashicons dashicons-update-alt spinning"></i> Exportando...'
        );

      $.ajax({
        url: ajaxurl,
        type: "POST",
        data: formData,
        processData: false,
        contentType: false,
        success: function (response) {
          console.log("Respuesta de exportación:", response);
          if (response.success && response.data.file_url) {
            // Crear enlace de descarga
            const link = document.createElement("a");
            link.href = response.data.file_url;
            link.download = response.data.filename || "hojas-trabajo.csv";
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
          } else {
            alert(response.data || "Error al exportar las hojas de trabajo.");
          }
        },
        error: function () {
          alert(
            "Ha ocurrido un error durante la exportación. Por favor, inténtalo de nuevo."
          );
        },
        complete: function () {
          // Restaurar botón
          $("#export-worksheets-button")
            .prop("disabled", false)
            .html(
              '<i class="dashicons dashicons-download"></i> Exportar a Excel'
            );
        },
      });
    },

    // Aprobar un gasto individual
    approveExpense: function (expenseId) {
      if (!confirm("¿Estás seguro de aprobar este gasto?")) {
        return;
      }

      $.ajax({
        url: ajaxurl,
        type: "POST",
        data: {
          action: "admin_approve_expense",
          expense_id: expenseId,
          nonce: $("#admin_nonce").val(),
        },
        success: function (response) {
          if (response.success) {
            alert(response.data.message);

            // Cerrar modal si está abierto
            $("#expense-details-modal").fadeOut();

            // Recargar datos
            if ($("#tab-pending-expenses").hasClass("active")) {
              WorkerPortalAdminFrontend.loadPendingExpenses();
            } else {
              $('.worker-portal-tab-link[data-tab="dashboard"]').click();
            }
          } else {
            alert(response.data);
          }
        },
        error: function () {
          alert("Ha ocurrido un error. Por favor, inténtalo de nuevo.");
        },
      });
    },

    // Rechazar un gasto individual
    rejectExpense: function (expenseId) {
      if (!confirm("¿Estás seguro de denegar este gasto?")) {
        return;
      }

      $.ajax({
        url: ajaxurl,
        type: "POST",
        data: {
          action: "admin_reject_expense",
          expense_id: expenseId,
          nonce: $("#admin_nonce").val(),
        },
        success: function (response) {
          if (response.success) {
            alert(response.data.message);

            // Cerrar modal si está abierto
            $("#expense-details-modal").fadeOut();

            // Recargar datos
            if ($("#tab-pending-expenses").hasClass("active")) {
              WorkerPortalAdminFrontend.loadPendingExpenses();
            } else {
              $('.worker-portal-tab-link[data-tab="dashboard"]').click();
            }
          } else {
            alert(response.data);
          }
        },
        error: function () {
          alert("Ha ocurrido un error. Por favor, inténtalo de nuevo.");
        },
      });
    },

    // Ver detalles de un gasto
    viewExpenseDetails: function (expenseId) {
      $.ajax({
        url: ajaxurl,
        type: "POST",
        data: {
          action: "admin_get_expense_details",
          expense_id: expenseId,
          nonce: $("#admin_nonce").val(),
        },
        beforeSend: function () {
          $("#expense-details-content").html(
            '<div class="worker-portal-loading">' +
              '<div class="worker-portal-spinner"></div>' +
              "<p>Cargando detalles...</p>" +
              "</div>"
          );
          $("#expense-details-modal").fadeIn();
        },
        success: function (response) {
          if (response.success) {
            $("#expense-details-content").html(response.data);
          } else {
            $("#expense-details-content").html(
              '<div class="worker-portal-error">' + response.data + "</div>"
            );
          }
        },
        error: function () {
          $("#expense-details-content").html(
            '<div class="worker-portal-error">' +
              "Error al cargar los detalles. Por favor, inténtalo de nuevo." +
              "</div>"
          );
        },
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

    // Procesar acción masiva
    processBulkAction: function () {
      const action = $("#bulk-action").val();
      if (!action) {
        alert("Por favor, selecciona una acción.");
        return;
      }

      const checked = $(".expense-checkbox:checked");
      if (checked.length === 0) {
        alert("Por favor, selecciona al menos un gasto.");
        return;
      }

      if (!confirm("¿Estás seguro? Esta acción no se puede deshacer.")) {
        return;
      }

      // Recoger IDs seleccionados
      const ids = [];
      checked.each(function () {
        ids.push($(this).val());
      });

      // Realizar acción masiva
      $.ajax({
        url: ajaxurl,
        type: "POST",
        data: {
          action: "admin_bulk_expense_action",
          bulk_action: action,
          expense_ids: ids,
          nonce: $("#admin_nonce").val(),
        },
        beforeSend: function () {
          $("#apply-bulk-action").prop("disabled", true).text("Procesando...");
        },
        success: function (response) {
          if (response.success) {
            alert(response.data.message);
            WorkerPortalAdminFrontend.loadPendingExpenses();
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
    },
  };

  $(document).on("click", "#register-all-exits", function () {
    if (
      !confirm(
        "¿Estás seguro de registrar la salida para TODOS los fichajes activos? Esta acción no se puede deshacer."
      )
    ) {
      return;
    }

    $.ajax({
      url: ajaxurl,
      type: "POST",
      data: {
        action: "admin_register_all_exits",
        nonce: $("#admin_nonce").val(),
      },
      success: function (response) {
        if (response.success) {
          alert(response.data.message);
          WorkerPortalAdminFrontend.loadTimeclockEntries();
        } else {
          alert(response.data || "Error al registrar las salidas");
        }
      },
      error: function () {
        alert(
          "Error de comunicación con el servidor. Por favor, inténtalo de nuevo."
        );
      },
    });
  });

  // Manejar acción de exportar datos
  $(document).on("click", "#export-timeclock-data", function () {
    // Recoger los filtros actuales
    var user_id = $("#filter-worker-timeclock").val() || "";
    var date_from = $("#filter-date-from-timeclock").val() || "";
    var date_to = $("#filter-date-to-timeclock").val() || "";

    // Desactivar botón durante la exportación
    $(this)
      .prop("disabled", true)
      .html(
        '<i class="dashicons dashicons-update-alt spinning"></i> Exportando...'
      );

    // Realizar petición
    $.ajax({
      url: ajaxurl,
      type: "POST",
      data: {
        action: "admin_export_timeclock_data",
        nonce: $("#admin_nonce").val(),
        user_id: user_id,
        date_from: date_from,
        date_to: date_to,
      },
      success: function (response) {
        if (response.success) {
          // Crear enlace para descargar
          var link = document.createElement("a");
          link.href = response.data.file_url;
          link.download = response.data.filename;
          document.body.appendChild(link);
          link.click();
          document.body.removeChild(link);
        } else {
          alert(response.data || "Error al exportar los datos");
        }
      },
      error: function () {
        alert(
          "Error de comunicación con el servidor. Por favor, inténtalo de nuevo."
        );
      },
      complete: function () {
        // Restaurar botón
        $("#export-timeclock-data")
          .prop("disabled", false)
          .html(
            '<i class="dashicons dashicons-download"></i> Exportar a Excel'
          );
      },
    });
  });

  // Inicializar cuando el DOM esté listo
  $(function () {
    WorkerPortalAdminFrontend.init();

    // Cargar contenido inicial de hojas de trabajo si estamos en esa pestaña
    if ($("#tab-worksheets").hasClass("active")) {
      WorkerPortalAdminFrontend.loadWorksheets();
    }
  });
})(jQuery);
