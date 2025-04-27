/**
 * JavaScript para el panel de administración en el frontend
 */
(function ($) {
  "use strict";

  // Objeto principal para el panel de administración en frontend
  const WorkerPortalAdminFrontend = {
    // Inicialización
    init: function () {
      this.setupNavigation();
      this.setupExpensesModule();
      this.setupModals();
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
        if (
          tab === "pending-expenses" &&
          $("#pending-expenses-list-container .worker-portal-admin-table")
            .length === 0
        ) {
          WorkerPortalAdminFrontend.loadPendingExpenses();
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

    // Configurar funcionalidades del módulo de gastos
    setupExpensesModule: function () {
      // Filtros para gastos pendientes
      $("#admin-expenses-filter-form").on("submit", function (e) {
        e.preventDefault();
        WorkerPortalAdminFrontend.loadPendingExpenses();
      });

      // Limpiar filtros
      $("#clear-filters").on("click", function () {
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
      // Obtener valores de filtros
      const formData = new FormData($("#admin-expenses-filter-form")[0]);
      formData.append("action", "admin_load_pending_expenses");
      formData.append("nonce", worker_portal_params.nonce);

      // Mostrar indicador de carga
      $("#pending-expenses-list-container").html(
        '<div class="worker-portal-loading">' +
          '<div class="worker-portal-spinner"></div>' +
          "<p>Cargando gastos...</p>" +
          "</div>"
      );

      // Realizar petición AJAX
      $.ajax({
        url: worker_portal_params.ajax_url,
        type: "POST",
        data: formData,
        processData: false,
        contentType: false,
        success: function (response) {
          if (response.success) {
            $("#pending-expenses-list-container").html(response.data);

            // Inicializar estado del botón de acción masiva
            WorkerPortalAdminFrontend.checkBulkSelection();
          } else {
            $("#pending-expenses-list-container").html(
              '<div class="worker-portal-error">' + response.data + "</div>"
            );
          }
        },
        error: function () {
          $("#pending-expenses-list-container").html(
            '<div class="worker-portal-error">' +
              "Error al cargar los gastos. Por favor, inténtalo de nuevo." +
              "</div>"
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
        url: worker_portal_params.ajax_url,
        type: "POST",
        data: {
          action: "admin_approve_expense",
          expense_id: expenseId,
          nonce: worker_portal_params.nonce,
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
        url: worker_portal_params.ajax_url,
        type: "POST",
        data: {
          action: "admin_reject_expense",
          expense_id: expenseId,
          nonce: worker_portal_params.nonce,
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
        url: worker_portal_params.ajax_url,
        type: "POST",
        data: {
          action: "admin_get_expense_details",
          expense_id: expenseId,
          nonce: worker_portal_params.nonce,
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
        url: worker_portal_params.ajax_url,
        type: "POST",
        data: {
          action: "admin_bulk_expense_action",
          bulk_action: action,
          expense_ids: ids,
          nonce: worker_portal_params.nonce,
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

  // Inicializar cuando el DOM esté listo
  $(function () {
    WorkerPortalAdminFrontend.init();
  });
})(jQuery);
