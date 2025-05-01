/**
 * JavaScript para el módulo de hojas de trabajo del Portal del Trabajador
 */
(function ($) {
  "use strict";

  // Objeto principal para el módulo de hojas de trabajo
  const WorkerPortalWorksheets = {
    /**
     * Inicialización
     */
    init: function () {
      // Configuración básica
      this.setupFormToggle();
      this.setupFormSubmission();
      this.setupWorksheetActions();
      this.setupFilters();
      this.setupPagination();
      this.setupExport();
      this.setupModals();
    },

    /**
     * Configurar el toggle del formulario
     */
    setupFormToggle: function () {
      $("#new-worksheet-button").on("click", function () {
        $(".worker-portal-worksheets-form-container").slideToggle();
        $(this).toggleClass("active");

        if ($(this).hasClass("active")) {
          $(this).html('<i class="dashicons dashicons-minus"></i> Cancelar');
        } else {
          $(this).html(
            '<i class="dashicons dashicons-plus-alt"></i> NUEVA HOJA DE TRABAJO'
          );
        }
      });
    },

    /**
     * Configurar el envío del formulario
     */
    setupFormSubmission: function () {
      $("#worker-portal-worksheet-form").on("submit", function (e) {
        e.preventDefault();

        const form = this;
        const formData = new FormData(form);

        // Validar campos obligatorios
        const workDate = formData.get("work_date");
        const projectId = formData.get("project_id");
        const systemType = formData.get("system_type");
        const unitType = formData.get("unit_type");
        const quantity = formData.get("quantity");
        const hours = formData.get("hours");

        if (
          !workDate ||
          !projectId ||
          !systemType ||
          !unitType ||
          !quantity ||
          quantity <= 0 ||
          !hours ||
          hours <= 0
        ) {
          alert(
            "Por favor, completa todos los campos obligatorios correctamente."
          );
          return;
        }

        // Añadir nonce para seguridad
        formData.append("action", "submit_worksheet");
        formData.append("nonce", window.workerPortalWorksheets.nonce);

        // Deshabilitar el botón de envío y mostrar indicador de carga
        const submitButton = $(form).find("button[type=submit]");
        submitButton
          .prop("disabled", true)
          .html(
            '<i class="dashicons dashicons-update-alt spinning"></i> Enviando...'
          );

        // Enviar los datos mediante AJAX
        $.ajax({
          url: window.workerPortalWorksheets.ajax_url,
          type: "POST",
          data: formData,
          processData: false,
          contentType: false,
          success: function (response) {
            if (response.success) {
              // Mostrar mensaje de éxito
              alert(response.data.message);

              // Limpiar formulario
              form.reset();

              // Recargar la página para mostrar la nueva hoja
              window.location.reload();
            } else {
              // Mostrar mensaje de error
              alert(response.data);
            }
          },
          error: function () {
            alert("Ha ocurrido un error. Por favor, inténtalo de nuevo.");
          },
          complete: function () {
            // Restaurar el botón de envío
            submitButton.prop("disabled", false).html("Enviar Hoja de Trabajo");
          },
        });
      });
    },

    /**
     * Configurar acciones sobre las hojas de trabajo (ver, eliminar)
     */
    setupWorksheetActions: function () {
      // Eliminar hoja de trabajo
      $(document).on("click", ".worker-portal-delete-worksheet", function () {
        const worksheetId = $(this).data("worksheet-id");
        WorkerPortalWorksheets.deleteWorksheet(worksheetId);
      });

      // Ver detalles de hoja de trabajo
      $(document).on("click", ".worker-portal-view-worksheet", function () {
        const worksheetId = $(this).data("worksheet-id");
        WorkerPortalWorksheets.loadWorksheetDetails(worksheetId);
      });
    },

    /**
     * Carga detalles de una hoja de trabajo en el modal
     * @param {number} worksheetId - ID de la hoja a cargar
     */
    loadWorksheetDetails: function (worksheetId) {
      $.ajax({
        url: window.workerPortalWorksheets.ajax_url,
        type: "POST",
        data: {
          action: "get_worksheet_details",
          nonce: window.workerPortalWorksheets.nonce,
          worksheet_id: worksheetId,
        },
        beforeSend: function () {
          $("#worksheet-details-content").html(
            '<div class="worker-portal-loading">' +
              '<div class="worker-portal-spinner"></div>' +
              "<p>" +
              window.workerPortalWorksheets.i18n.loading +
              "</p>" +
              "</div>"
          );
          $("#worksheet-details-modal").fadeIn();
        },
        success: function (response) {
          if (response.success) {
            $("#worksheet-details-content").html(response.data);
          } else {
            $("#worksheet-details-content").html(
              '<div class="worker-portal-error">' + response.data + "</div>"
            );
          }
        },
        error: function () {
          $("#worksheet-details-content").html(
            '<div class="worker-portal-error">' +
              window.workerPortalWorksheets.i18n.error_load +
              "</div>"
          );
        },
      });
    },

    /**
     * Elimina una hoja de trabajo
     * @param {number} worksheetId - ID de la hoja a eliminar
     */
    deleteWorksheet: function (worksheetId) {
      if (!confirm(window.workerPortalWorksheets.i18n.confirm_delete)) {
        return;
      }

      $.ajax({
        url: window.workerPortalWorksheets.ajax_url,
        type: "POST",
        data: {
          action: "delete_worksheet",
          nonce: window.workerPortalWorksheets.nonce,
          worksheet_id: worksheetId,
        },
        success: function (response) {
          if (response.success) {
            // Eliminar la fila de la tabla o recargar la página
            $(`tr[data-worksheet-id="${worksheetId}"]`).fadeOut(function () {
              $(this).remove();

              // Si no quedan hojas, mostrar mensaje
              if ($(".worker-portal-worksheets-table tbody tr").length === 0) {
                $(".worker-portal-table-responsive").html(
                  '<p class="worker-portal-no-data">No hay hojas de trabajo registradas.</p>'
                );
              }
            });
          } else {
            alert(
              response.data || window.workerPortalWorksheets.i18n.error_delete
            );
          }
        },
        error: function () {
          alert("Ha ocurrido un error. Por favor, inténtalo de nuevo.");
        },
      });
    },

    /**
     * Configurar filtros de búsqueda
     */
    setupFilters: function () {
      // Enviar formulario de filtros
      $("#worksheets-filter-form").on("submit", function (e) {
        e.preventDefault();
        WorkerPortalWorksheets.loadFilteredWorksheets(1);
      });

      // Limpiar filtros
      $("#clear-filters").on("click", function () {
        $("#worksheets-filter-form")[0].reset();
        WorkerPortalWorksheets.loadFilteredWorksheets(1);
      });
    },

    /**
     * Carga hojas filtradas mediante AJAX
     * @param {number} page - Número de página para la paginación
     */
    loadFilteredWorksheets: function (page) {
      // Mostrar indicador de carga
      $("#worksheets-list-content").html(
        '<div class="worker-portal-loading">' +
          '<div class="worker-portal-spinner"></div>' +
          "<p>Cargando hojas de trabajo...</p>" +
          "</div>"
      );

      // Obtener datos del formulario
      const formData = new FormData($("#worksheets-filter-form")[0]);
      formData.append("action", "filter_worksheets");
      formData.append("nonce", window.workerPortalWorksheets.nonce);
      formData.append("page", page);

      // Realizar petición AJAX
      $.ajax({
        url: window.workerPortalWorksheets.ajax_url,
        type: "POST",
        data: formData,
        processData: false,
        contentType: false,
        success: function (response) {
          if (response.success) {
            $("#worksheets-list-content").html(response.data);
            // No necesitamos reinicializar los eventos ya que usamos delegación
          } else {
            $("#worksheets-list-content").html(
              '<p class="worker-portal-no-data">' + response.data + "</p>"
            );
          }
        },
        error: function () {
          $("#worksheets-list-content").html(
            '<p class="worker-portal-no-data">' +
              "Ha ocurrido un error. Por favor, inténtalo de nuevo." +
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
          WorkerPortalWorksheets.loadFilteredWorksheets(page);
        }
      );
    },

    /**
     * Configurar exportación de hojas de trabajo
     */
    setupExport: function () {
      $("#export-worksheets-button").on("click", function () {
        // Obtener datos del formulario de filtros
        const formData = new FormData($("#worksheets-filter-form")[0]);
        formData.append("action", "export_worksheets");
        formData.append("nonce", window.workerPortalWorksheets.nonce);

        // Deshabilitar botón y mostrar indicador de carga
        const $button = $(this);
        $button
          .prop("disabled", true)
          .html(
            '<i class="dashicons dashicons-update-alt spinning"></i> Exportando...'
          );

        // Realizar petición AJAX
        $.ajax({
          url: window.workerPortalWorksheets.ajax_url,
          type: "POST",
          data: formData,
          processData: false,
          contentType: false,
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
            } else {
              alert(response.data);
            }
          },
          error: function () {
            alert("Ha ocurrido un error. Por favor, inténtalo de nuevo.");
          },
          complete: function () {
            // Restaurar botón
            $button
              .prop("disabled", false)
              .html(
                '<i class="dashicons dashicons-download"></i> Exportar a Excel'
              );
          },
        });
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
    if (typeof window.workerPortalWorksheets === "undefined") {
      console.error(
        "Error: No se encontraron las variables necesarias para el módulo de hojas de trabajo"
      );
      return;
    }

    WorkerPortalWorksheets.init();
  });
})(jQuery);
