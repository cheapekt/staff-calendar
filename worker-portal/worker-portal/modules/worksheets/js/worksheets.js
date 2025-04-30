/**
 * JavaScript para el módulo de hojas de trabajo del Portal del Trabajador
 */
(function ($) {
  "use strict";

  // Objeto principal para el módulo de hojas de trabajo
  const WorkerPortalWorksheets = {
    // Inicialización
    init: function () {
      console.log("Inicializando módulo de hojas de trabajo...");
      this.setupFormToggle();
      this.setupFormSubmission();
      this.setupWorksheetActions();
      this.setupFilters();
      this.setupPagination();
      this.setupExport();
      this.setupDetails();
    },

    // Configurar el toggle del formulario
    setupFormToggle: function () {
      console.log("Configurando toggle de formulario");
      $("#new-worksheet-button").on("click", function () {
        $(".worker-portal-worksheets-form-container").slideToggle();
        $(this).toggleClass("active");

        if ($(this).hasClass("active")) {
          $(this).html('<i class="dashicons dashicons-minus"></i> Cancelar');
        } else {
          $(this).html(
            '<i class="dashicons dashicons-plus-alt"></i> Nueva Hoja de Trabajo'
          );
        }
      });
    },

    // Configurar el envío del formulario
    setupFormSubmission: function () {
      console.log("Configurando envío de formulario");
      $("#worker-portal-worksheet-form").on("submit", function (e) {
        e.preventDefault();
        console.log("Formulario enviado");

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
        formData.append("nonce", workerPortalWorksheets.nonce);
        formData.append("action", "submit_worksheet");

        // Deshabilitar el botón de envío y mostrar indicador de carga
        const submitButton = $(form).find("button[type=submit]");
        submitButton
          .prop("disabled", true)
          .html(
            '<i class="dashicons dashicons-update-alt spinning"></i> Enviando...'
          );

        // Enviar los datos mediante AJAX
        $.ajax({
          url: workerPortalWorksheets.ajax_url,
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
            alert(workerPortalWorksheets.i18n.error);
          },
          complete: function () {
            // Restaurar el botón de envío
            submitButton.prop("disabled", false).html("Enviar Hoja de Trabajo");
          },
        });
      });

      // También configurar el formulario directo usado en algunas plantillas
      $("#worksheet-form-direct").on("submit", function (e) {
        e.preventDefault();
        console.log("Formulario directo enviado");

        // Validar campos obligatorios
        const workDate = $("#work-date-direct").val();
        const projectId = $("#project-id-direct").val();
        const systemType = $("#system-type-direct").val();
        const unitType = $("#unit-type-direct").val();
        const quantity = $("#quantity-direct").val();
        const hours = $("#hours-direct").val();

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

        const formData = new FormData(this);
        formData.append("nonce", workerPortalWorksheets.nonce);

        $.ajax({
          url: workerPortalWorksheets.ajax_url,
          type: "POST",
          data: formData,
          processData: false,
          contentType: false,
          success: function (response) {
            if (response.success) {
              alert("Hoja de trabajo registrada correctamente");
              $("#worksheet-form-direct")[0].reset();
              window.location.reload();
            } else {
              alert(response.data || "Error al registrar la hoja de trabajo");
            }
          },
          error: function () {
            alert("Error de comunicación. Por favor, inténtalo de nuevo.");
          },
        });
      });
    },

    // Configurar acciones sobre las hojas de trabajo
    setupWorksheetActions: function () {
      console.log("Configurando acciones de hojas de trabajo");
      // Eliminar hoja de trabajo
      $(document).on("click", ".worker-portal-delete-worksheet", function () {
        console.log("Clic en eliminar hoja de trabajo");
        const worksheetId = $(this).data("worksheet-id");

        if (confirm(workerPortalWorksheets.i18n.confirm_delete)) {
          $.ajax({
            url: workerPortalWorksheets.ajax_url,
            type: "POST",
            data: {
              action: "delete_worksheet",
              nonce: workerPortalWorksheets.nonce,
              worksheet_id: worksheetId,
            },
            success: function (response) {
              if (response.success) {
                // Eliminar la fila de la tabla o recargar la página
                $(`tr[data-worksheet-id="${worksheetId}"]`).fadeOut(
                  function () {
                    $(this).remove();

                    // Si no quedan hojas, mostrar mensaje
                    if (
                      $(".worker-portal-worksheets-table tbody tr").length === 0
                    ) {
                      $(".worker-portal-table-responsive").html(
                        '<p class="worker-portal-no-data">No hay hojas de trabajo registradas.</p>'
                      );
                    }
                  }
                );
              } else {
                alert(response.data);
              }
            },
            error: function () {
              alert(workerPortalWorksheets.i18n.error);
            },
          });
        }
      });

      // Ver detalles de hoja de trabajo
      $(document).on("click", ".worker-portal-view-worksheet", function () {
        console.log("Clic en ver detalles de hoja de trabajo");
        const worksheetId = $(this).data("worksheet-id");
        WorkerPortalWorksheets.loadWorksheetDetails(worksheetId);
      });
    },

    // Cargar detalles de hoja de trabajo
    loadWorksheetDetails: function (worksheetId) {
      console.log("Cargando detalles de hoja de trabajo:", worksheetId);
      $.ajax({
        url: workerPortalWorksheets.ajax_url,
        type: "POST",
        data: {
          action: "get_worksheet_details",
          nonce: workerPortalWorksheets.nonce,
          worksheet_id: worksheetId,
        },
        beforeSend: function () {
          $("#worksheet-details-content").html(
            '<div class="worker-portal-loading">' +
              '<div class="worker-portal-spinner"></div>' +
              "<p>Cargando detalles...</p>" +
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
              "Error al cargar los detalles. Por favor, inténtalo de nuevo." +
              "</div>"
          );
        },
      });
    },

    // Configurar filtros de búsqueda
    setupFilters: function () {
      console.log("Configurando filtros");
      // Enviar formulario de filtros
      $("#worksheets-filter-form").on("submit", function (e) {
        e.preventDefault();
        console.log("Aplicando filtros");
        WorkerPortalWorksheets.loadFilteredWorksheets(1);
      });

      // Limpiar filtros
      $("#clear-filters").on("click", function () {
        console.log("Limpiando filtros");
        $("#worksheets-filter-form")[0].reset();
        WorkerPortalWorksheets.loadFilteredWorksheets(1);
      });
    },

    // Cargar hojas filtradas mediante AJAX
    loadFilteredWorksheets: function (page) {
      console.log("Cargando hojas filtradas, página:", page);
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
      formData.append("nonce", workerPortalWorksheets.nonce);
      formData.append("page", page);

      // Realizar petición AJAX
      $.ajax({
        url: workerPortalWorksheets.ajax_url,
        type: "POST",
        data: formData,
        processData: false,
        contentType: false,
        success: function (response) {
          if (response.success) {
            $("#worksheets-list-content").html(response.data);
          } else {
            $("#worksheets-list-content").html(
              '<p class="worker-portal-no-data">' + response.data + "</p>"
            );
          }
        },
        error: function () {
          $("#worksheets-list-content").html(
            '<p class="worker-portal-no-data">' +
              workerPortalWorksheets.i18n.error +
              "</p>"
          );
        },
      });
    },

    // Configurar paginación
    setupPagination: function () {
      console.log("Configurando paginación");
      // Delegación de eventos para los botones de paginación
      $(document).on(
        "click",
        ".worker-portal-pagination-links a",
        function (e) {
          e.preventDefault();
          const page = $(this).data("page");
          console.log("Cambiando a página:", page);
          WorkerPortalWorksheets.loadFilteredWorksheets(page);
        }
      );
    },

    // Configurar exportación de hojas de trabajo
    setupExport: function () {
      console.log("Configurando exportación");
      $("#export-worksheets-button").on("click", function () {
        console.log("Exportando hojas de trabajo");
        // Obtener datos del formulario de filtros
        const formData = new FormData($("#worksheets-filter-form")[0]);
        formData.append("action", "export_worksheets");
        formData.append("nonce", workerPortalWorksheets.nonce);

        // Deshabilitar botón y mostrar indicador de carga
        const $button = $(this);
        $button
          .prop("disabled", true)
          .html(
            '<i class="dashicons dashicons-update-alt spinning"></i> Exportando...'
          );

        // Realizar petición AJAX
        $.ajax({
          url: workerPortalWorksheets.ajax_url,
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
            alert(workerPortalWorksheets.i18n.error);
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

    // Configurar modal de detalles
    setupDetails: function () {
      console.log("Configurando modales");
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
    console.log("DOM listo, inicializando módulo de hojas de trabajo");
    WorkerPortalWorksheets.init();

    // Si estamos en la página de hojas de trabajo, cargar los datos iniciales
    if ($("#worksheets-list-content").length > 0) {
      WorkerPortalWorksheets.loadFilteredWorksheets(1);
    }
  });
})(jQuery);
