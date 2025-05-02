/**
 * JavaScript para el módulo de incentivos del Portal del Trabajador
 */
(function ($) {
  "use strict";

  // Objeto principal para el módulo de incentivos
  const WorkerPortalIncentives = {
    /**
     * Inicialización
     */
    init: function () {
      this.setupFilters();
      this.setupPagination();
      this.setupAdminActions();
      this.setupModals();
    },

    /**
     * Configurar filtros de búsqueda
     */
    setupFilters: function () {
      // Enviar formulario de filtros
      $("#incentives-filter-form").on("submit", function (e) {
        e.preventDefault();
        WorkerPortalIncentives.loadFilteredIncentives(1);
      });

      // Limpiar filtros
      $("#clear-filters").on("click", function () {
        $("#incentives-filter-form")[0].reset();
        WorkerPortalIncentives.loadFilteredIncentives(1);
      });
    },

    /**
     * Carga incentivos filtrados mediante AJAX
     * @param {number} page - Número de página para la paginación
     */
    loadFilteredIncentives: function (page) {
      // Mostrar indicador de carga
      $("#incentives-list-content").html(
        '<div class="worker-portal-loading">' +
          '<div class="worker-portal-spinner"></div>' +
          "<p>Cargando incentivos...</p>" +
          "</div>"
      );

      // Obtener datos del formulario
      const formData = new FormData($("#incentives-filter-form")[0]);
      formData.append("action", "filter_incentives");
      formData.append("nonce", workerPortalIncentives.nonce);
      formData.append("page", page);
      formData.append("per_page", 10); // Incentivos por página

      // Realizar petición AJAX
      $.ajax({
        url: workerPortalIncentives.ajax_url,
        type: "POST",
        data: formData,
        processData: false,
        contentType: false,
        success: function (response) {
          if (response.success) {
            $("#incentives-list-content").html(response.data.html);
          } else {
            $("#incentives-list-content").html(
              '<p class="worker-portal-no-data">' + response.data + "</p>"
            );
          }
        },
        error: function () {
          $("#incentives-list-content").html(
            '<p class="worker-portal-no-data">' +
              workerPortalIncentives.i18n.error +
              "</p>"
          );
        },
      });
    },

    /**
     * Configurar paginación
     */
    setupPagination: function () {
      // Delegación de eventos para los botones de paginación
      $(document).on(
        "click",
        ".worker-portal-pagination-links a",
        function (e) {
          e.preventDefault();
          const page = $(this).data("page");
          WorkerPortalIncentives.loadFilteredIncentives(page);
        }
      );
    },

    /**
     * Configurar acciones para administradores
     */
    setupAdminActions: function () {
      // Añadir incentivo
      $("#add-incentive-form").on("submit", function (e) {
        e.preventDefault();
        WorkerPortalIncentives.submitIncentive($(this));
      });

      // Aprobar incentivo
      $(document).on("click", ".approve-incentive", function () {
        const incentiveId = $(this).data("incentive-id");
        WorkerPortalIncentives.approveIncentive(incentiveId);
      });

      // Rechazar incentivo
      $(document).on("click", ".reject-incentive", function () {
        const incentiveId = $(this).data("incentive-id");
        WorkerPortalIncentives.rejectIncentive(incentiveId);
      });

      // Ver detalles incentivo
      $(document).on("click", ".view-incentive", function () {
        const incentiveId = $(this).data("incentive-id");
        WorkerPortalIncentives.viewIncentiveDetails(incentiveId);
      });

      // Eliminar incentivo
      $(document).on("click", ".delete-incentive", function () {
        const incentiveId = $(this).data("incentive-id");
        WorkerPortalIncentives.deleteIncentive(incentiveId);
      });

      // Calcular incentivo desde hoja de trabajo
      $("#calculate-incentive-button").on("click", function () {
        const worksheetId = $(this).data("worksheet-id");
        WorkerPortalIncentives.calculateWorksheetIncentive(worksheetId);
      });
    },

    /**
     * Envía formulario de incentivo
     * @param {jQuery} form - Formulario de incentivo
     */
    submitIncentive: function (form) {
      // Validar formulario
      const userId = form.find("#incentive-user-id").val();
      const description = form.find("#incentive-description").val();
      const amount = parseFloat(form.find("#incentive-amount").val());

      if (!userId || userId <= 0) {
        alert("Por favor, selecciona un usuario");
        return;
      }

      if (!description) {
        alert("Por favor, introduce una descripción");
        return;
      }

      if (isNaN(amount) || amount <= 0) {
        alert("Por favor, introduce un importe válido mayor que cero");
        return;
      }

      // Obtener datos del formulario
      const formData = new FormData(form[0]);
      formData.append("action", "admin_add_incentive");
      formData.append("nonce", $("#admin_nonce").val());

      // Enviar datos
      $.ajax({
        url: workerPortalIncentives.ajax_url,
        type: "POST",
        data: formData,
        processData: false,
        contentType: false,
        beforeSend: function () {
          form
            .find("button[type=submit]")
            .prop("disabled", true)
            .html(
              '<i class="dashicons dashicons-update-alt spinning"></i> Guardando...'
            );
        },
        success: function (response) {
          if (response.success) {
            alert(response.data.message);
            form[0].reset();
            // Recargar la lista de incentivos si está visible
            if ($("#incentives-list-container").length) {
              WorkerPortalIncentives.loadAdminIncentives();
            }
          } else {
            alert(response.data);
          }
        },
        error: function () {
          alert(workerPortalIncentives.i18n.error);
        },
        complete: function () {
          form
            .find("button[type=submit]")
            .prop("disabled", false)
            .html('<i class="dashicons dashicons-plus"></i> Añadir Incentivo');
        },
      });
    },

    /**
     * Aprueba un incentivo
     * @param {number} incentiveId - ID del incentivo
     */
    approveIncentive: function (incentiveId) {
      if (!confirm("¿Estás seguro de aprobar este incentivo?")) {
        return;
      }

      $.ajax({
        url: workerPortalIncentives.ajax_url,
        type: "POST",
        data: {
          action: "admin_approve_incentive",
          incentive_id: incentiveId,
          nonce: $("#admin_nonce").val(),
        },
        success: function (response) {
          if (response.success) {
            alert(response.data.message);
            // Cerrar modal si está abierto
            $("#incentive-details-modal").fadeOut();
            // Recargar lista de incentivos
            if ($("#incentives-list-container").length) {
              WorkerPortalIncentives.loadAdminIncentives();
            } else {
              // Reload dashboard
              $('.worker-portal-tab-link[data-tab="dashboard"]').click();
            }
          } else {
            alert(response.data);
          }
        },
        error: function () {
          alert(workerPortalIncentives.i18n.error);
        },
      });
    },

    /**
     * Rechaza un incentivo
     * @param {number} incentiveId - ID del incentivo
     */
    rejectIncentive: function (incentiveId) {
      if (!confirm("¿Estás seguro de rechazar este incentivo?")) {
        return;
      }

      $.ajax({
        url: workerPortalIncentives.ajax_url,
        type: "POST",
        data: {
          action: "admin_reject_incentive",
          incentive_id: incentiveId,
          nonce: $("#admin_nonce").val(),
        },
        success: function (response) {
          if (response.success) {
            alert(response.data.message);
            // Cerrar modal si está abierto
            $("#incentive-details-modal").fadeOut();
            // Recargar lista de incentivos
            if ($("#incentives-list-container").length) {
              WorkerPortalIncentives.loadAdminIncentives();
            } else {
              // Reload dashboard
              $('.worker-portal-tab-link[data-tab="dashboard"]').click();
            }
          } else {
            alert(response.data);
          }
        },
        error: function () {
          alert(workerPortalIncentives.i18n.error);
        },
      });
    },

    /**
     * Elimina un incentivo
     * @param {number} incentiveId - ID del incentivo
     */
    deleteIncentive: function (incentiveId) {
      if (
        !confirm(
          "¿Estás seguro de eliminar este incentivo? Esta acción no se puede deshacer."
        )
      ) {
        return;
      }

      $.ajax({
        url: workerPortalIncentives.ajax_url,
        type: "POST",
        data: {
          action: "admin_delete_incentive",
          incentive_id: incentiveId,
          nonce: $("#admin_nonce").val(),
        },
        success: function (response) {
          if (response.success) {
            alert(response.data.message);
            // Cerrar modal si está abierto
            $("#incentive-details-modal").fadeOut();
            // Recargar lista de incentivos
            if ($("#incentives-list-container").length) {
              WorkerPortalIncentives.loadAdminIncentives();
            } else {
              // Reload dashboard
              $('.worker-portal-tab-link[data-tab="dashboard"]').click();
            }
          } else {
            alert(response.data);
          }
        },
        error: function () {
          alert(workerPortalIncentives.i18n.error);
        },
      });
    },

    /**
     * Muestra detalles de un incentivo
     * @param {number} incentiveId - ID del incentivo
     */
    viewIncentiveDetails: function (incentiveId) {
      $.ajax({
        url: workerPortalIncentives.ajax_url,
        type: "POST",
        data: {
          action: "admin_get_incentive_details",
          incentive_id: incentiveId,
          nonce: $("#admin_nonce").val(),
        },
        beforeSend: function () {
          $("#incentive-details-content").html(
            '<div class="worker-portal-loading">' +
              '<div class="worker-portal-spinner"></div>' +
              "<p>" +
              workerPortalIncentives.i18n.loading +
              "</p>" +
              "</div>"
          );
          $("#incentive-details-modal").fadeIn();
        },
        success: function (response) {
          if (response.success) {
            // Generar HTML para los detalles
            var incentive = response.data;
            var html = '<table class="worker-portal-details-table">';

            html += "<tr><th>ID:</th><td>" + incentive.id + "</td></tr>";
            html +=
              "<tr><th>Trabajador:</th><td>" +
              incentive.user_name +
              "</td></tr>";

            if (incentive.project_name) {
              html +=
                "<tr><th>Proyecto:</th><td>" +
                incentive.project_name +
                "</td></tr>";
            }

            if (incentive.work_date) {
              html +=
                "<tr><th>Fecha de trabajo:</th><td>" +
                incentive.work_date +
                "</td></tr>";
            }

            html +=
              "<tr><th>Tipo de incentivo:</th><td>" +
              incentive.incentive_type_name +
              "</td></tr>";
            html +=
              "<tr><th>Descripción:</th><td>" +
              incentive.description +
              "</td></tr>";
            html +=
              "<tr><th>Importe:</th><td>" +
              incentive.amount.toFixed(2).replace(".", ",") +
              " €</td></tr>";
            html +=
              "<tr><th>Fecha de cálculo:</th><td>" +
              incentive.calculation_date +
              "</td></tr>";

            var status_class = "";
            var status_text = "";

            switch (incentive.status) {
              case "pending":
                status_class = "worker-portal-badge-warning";
                status_text = "Pendiente";
                break;
              case "approved":
                status_class = "worker-portal-badge-success";
                status_text = "Aprobado";
                break;
              case "rejected":
                status_class = "worker-portal-badge-danger";
                status_text = "Rechazado";
                break;
            }

            html +=
              '<tr><th>Estado:</th><td><span class="worker-portal-badge ' +
              status_class +
              '">' +
              status_text +
              "</span></td></tr>";

            if (incentive.approved_by) {
              html +=
                "<tr><th>Procesado por:</th><td>" +
                incentive.approver_name +
                "</td></tr>";
              html +=
                "<tr><th>Fecha de procesamiento:</th><td>" +
                incentive.approved_date +
                "</td></tr>";
            }

            html += "</table>";

            // Añadir botones de acción para incentivos pendientes
            if (incentive.status === "pending") {
              html += '<div class="worker-portal-incentive-actions">';
              html +=
                '<button type="button" class="worker-portal-button worker-portal-button-primary approve-incentive" data-incentive-id="' +
                incentive.id +
                '">';
              html +=
                '<i class="dashicons dashicons-yes"></i> Aprobar</button>';
              html +=
                '<button type="button" class="worker-portal-button worker-portal-button-danger reject-incentive" data-incentive-id="' +
                incentive.id +
                '">';
              html +=
                '<i class="dashicons dashicons-no"></i> Rechazar</button>';
              html += "</div>";
            }

            $("#incentive-details-content").html(html);
          } else {
            $("#incentive-details-content").html(
              '<div class="worker-portal-error">' + response.data + "</div>"
            );
          }
        },
        error: function () {
          $("#incentive-details-content").html(
            '<div class="worker-portal-error">' +
              workerPortalIncentives.i18n.error +
              "</div>"
          );
        },
      });
    },

    /**
     * Carga incentivos para administrador
     */
    loadAdminIncentives: function () {
      // Mostrar indicador de carga
      $("#incentives-list-container").html(
        '<div class="worker-portal-loading">' +
          '<div class="worker-portal-spinner"></div>' +
          "<p>Cargando incentivos...</p>" +
          "</div>"
      );

      // Obtener datos de filtros si existen
      var formData = new FormData();
      formData.append("action", "admin_load_incentives");
      formData.append("nonce", $("#admin_nonce").val());

      if ($("#filter-worker-inc").length) {
        formData.append("user_id", $("#filter-worker-inc").val() || "");
      }

      if ($("#filter-status-inc").length) {
        formData.append("status", $("#filter-status-inc").val() || "");
      }

      if ($("#filter-date-from-inc").length) {
        formData.append("date_from", $("#filter-date-from-inc").val() || "");
      }

      if ($("#filter-date-to-inc").length) {
        formData.append("date_to", $("#filter-date-to-inc").val() || "");
      }

      // Realizar petición AJAX
      $.ajax({
        url: workerPortalIncentives.ajax_url,
        type: "POST",
        data: formData,
        processData: false,
        contentType: false,
        success: function (response) {
          if (response.success) {
            $("#incentives-list-container").html(response.data.html);
          } else {
            $("#incentives-list-container").html(
              '<div class="worker-portal-error">' + response.data + "</div>"
            );
          }
        },
        error: function () {
          $("#incentives-list-container").html(
            '<div class="worker-portal-error">' +
              workerPortalIncentives.i18n.error +
              "</div>"
          );
        },
      });
    },

    /**
     * Calcula incentivo a partir de una hoja de trabajo
     * @param {number} worksheetId - ID de la hoja de trabajo
     */
    calculateWorksheetIncentive: function (worksheetId) {
      $.ajax({
        url: workerPortalIncentives.ajax_url,
        type: "POST",
        data: {
          action: "admin_calculate_worksheet_incentive",
          worksheet_id: worksheetId,
          nonce: $("#admin_nonce").val(),
        },
        beforeSend: function () {
          $("#calculate-incentive-button")
            .prop("disabled", true)
            .html(
              '<i class="dashicons dashicons-update-alt spinning"></i> Calculando...'
            );
        },
        success: function (response) {
          if (response.success) {
            // Rellenar el formulario con los datos calculados
            $("#incentive-user-id").val(response.data.user_id);
            $("#incentive-worksheet-id").val(response.data.worksheet_id);
            $("#incentive-description").val(response.data.description);
            $("#incentive-amount").val(response.data.amount);
            $("#incentive-type").val(response.data.incentive_type);

            // Si estamos en un modal, mostrar formulario
            if ($("#worksheet-incentive-form").length) {
              $("#worksheet-incentive-form").slideDown();
            }
          } else {
            alert(response.data);
          }
        },
        error: function () {
          alert(workerPortalIncentives.i18n.error);
        },
        complete: function () {
          $("#calculate-incentive-button")
            .prop("disabled", false)
            .html(
              '<i class="dashicons dashicons-calculator"></i> Calcular Incentivo'
            );
        },
      });
    },

    /**
     * Configurar modales
     */
    setupModals: function () {
      // Cerrar modal
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
    },
  };

  // Inicializar cuando el DOM esté listo
  $(function () {
    // Verificar si existe el objeto con las variables necesarias
    if (typeof workerPortalIncentives === "undefined") {
      console.error(
        "Error: No se encontraron las variables necesarias para el módulo de incentivos"
      );
      return;
    }

    WorkerPortalIncentives.init();

    // Si estamos en la sección de administración, cargar incentivos
    if ($("#incentives-list-container").length) {
      WorkerPortalIncentives.loadAdminIncentives();
    }
  });
})(jQuery);
