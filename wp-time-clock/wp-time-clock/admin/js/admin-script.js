/**
 * JavaScript para el panel de administración de WP Time Clock
 */
(function ($) {
  "use strict";

  // Namespace para manejar funciones comunes
  const AdminUtils = {
    /**
     * Mostrar mensaje de notificación
     * @param {string} message - Mensaje a mostrar
     * @param {string} type - Tipo de mensaje (success, error, warning, info)
     * @param {number} [duration=3000] - Duración del mensaje
     */
    showMessage: function (message, type = "info", duration = 3000) {
      const $messageContainer = $(".wrap.wp-time-clock-admin");
      const $message = $(`
                <div class="notice notice-${
                  type === "success"
                    ? "success"
                    : type === "error"
                    ? "error"
                    : "info"
                } is-dismissible">
                    <p>${message}</p>
                    <button type="button" class="notice-dismiss"></button>
                </div>
            `).hide();

      $messageContainer.prepend($message);
      $message.slideDown();

      // Auto dismiss
      setTimeout(() => {
        $message.slideUp(300, function () {
          $(this).remove();
        });
      }, duration);
    },

    /**
     * Formatear fecha/hora para input datetime-local
     * @param {string} dateString - Fecha en formato ISO o MySQL
     * @returns {string} Fecha formateada para input datetime-local
     */
    formatDateTimeForInput: function (dateString) {
      const date = new Date(dateString);
      return date.toISOString().slice(0, 16); // YYYY-MM-DDTHH:mm
    },

    /**
     * Hacer una petición AJAX genérica
     * @param {string} url - URL del endpoint
     * @param {string} method - Método HTTP
     * @param {Object} [data={}] - Datos a enviar
     * @returns {Promise} Promesa de la solicitud AJAX
     */
    ajaxRequest: function (url, method = "GET", data = {}) {
      return $.ajax({
        url: url,
        method: method,
        beforeSend: function (xhr) {
          xhr.setRequestHeader("X-WP-Nonce", wpTimeClockAdmin.rest_nonce);
        },
        data: data,
      });
    },
  };

  // Componentes de administración
  const AdminComponents = {
    /**
     * Inicializar componentes del dashboard
     */
    initDashboard: function () {
      this.initCharts();
      this.updateElapsedTimes();
    },

    /**
     * Inicializar gráficos del dashboard
     */
    initCharts: function () {
      // Ejemplo de gráfico de barras para actividad
      const $chart = $("#wp-time-clock-activity-chart");
      if ($chart.length && window.Chart) {
        new Chart($chart[0].getContext("2d"), {
          type: "bar",
          data: {
            labels: window.wpTimeClockChartData.labels,
            datasets: [
              {
                label: "Fichajes por día",
                data: window.wpTimeClockChartData.data,
                backgroundColor: "rgba(54, 162, 235, 0.5)",
                borderColor: "rgba(54, 162, 235, 1)",
                borderWidth: 1,
              },
            ],
          },
          options: {
            responsive: true,
            scales: {
              y: {
                beginAtZero: true,
                precision: 0,
              },
            },
          },
        });
      }
    },

    /**
     * Actualizar tiempos transcurridos en vivo
     */
    updateElapsedTimes: function () {
      function formatTime(seconds) {
        const hours = Math.floor(seconds / 3600);
        const minutes = Math.floor((seconds % 3600) / 60);
        const secs = seconds % 60;

        return `${String(hours).padStart(2, "0")}:${String(minutes).padStart(
          2,
          "0"
        )}:${String(secs).padStart(2, "0")}`;
      }

      const $elapsedCells = $(".wp-time-clock-elapsed-cell");

      function updateElapsed() {
        $elapsedCells.each(function () {
          const $cell = $(this);
          let currentSeconds = parseInt($cell.data("seconds")) + 1;

          $cell.text(formatTime(currentSeconds));
          $cell.data("seconds", currentSeconds);
        });
      }

      // Actualizar cada segundo
      if ($elapsedCells.length) {
        setInterval(updateElapsed, 1000);
      }
    },
  };

  // Manejadores principales
  const AdminHandlers = {
    /**
     * Inicializar manejadores de administración
     */
    init: function () {
      this.entryEditing();
      this.reportExport();
      this.settingsManagement();
      this.modalHandling();
    },

    /**
     * Manejar edición de entradas
     */
    entryEditing: function () {
      // Abrir modal de edición
      $(".wp-time-clock-edit-entry").on("click", function () {
        const entryId = $(this).data("entry-id");
        const userId = $(this).data("user-id");

        AdminUtils.ajaxRequest(
          `${wpTimeClockAdmin.rest_url}/edit-entry/${entryId}`
        )
          .done(function (response) {
            if (response.success) {
              const entry = response.data;
              $("#wp-time-clock-entry-id").val(entryId);
              $("#wp-time-clock-user-id").val(userId);
              $("#wp-time-clock-user-name").val(entry.user_name);
              $("#wp-time-clock-clock-in").val(
                AdminUtils.formatDateTimeForInput(entry.clock_in)
              );
              $("#wp-time-clock-clock-out").val(
                entry.clock_out
                  ? AdminUtils.formatDateTimeForInput(entry.clock_out)
                  : ""
              );
              $("#wp-time-clock-status").val(entry.status);
              $("#wp-time-clock-note").val(entry.clock_in_note || "");

              $("#wp-time-clock-edit-modal").fadeIn(200);
            } else {
              AdminUtils.showMessage(response.message, "error");
            }
          })
          .fail(function () {
            AdminUtils.showMessage("Error cargando la entrada", "error");
          });
      });

      // Guardar cambios en entrada
      $("#wp-time-clock-edit-form").on("submit", function (e) {
        e.preventDefault();
        const entryId = $("#wp-time-clock-entry-id").val();
        const data = {
          clock_in: $("#wp-time-clock-clock-in").val(),
          clock_out: $("#wp-time-clock-clock-out").val(),
          status: $("#wp-time-clock-status").val(),
          clock_in_note: $("#wp-time-clock-note").val(),
        };

        AdminUtils.ajaxRequest(
          `${wpTimeClockAdmin.rest_url}/edit-entry/${entryId}`,
          "POST",
          data
        )
          .done(function (response) {
            if (response.success) {
              AdminUtils.showMessage("Entrada actualizada", "success");
              $("#wp-time-clock-edit-modal").fadeOut(200);
              location.reload();
            } else {
              AdminUtils.showMessage(response.message, "error");
            }
          })
          .fail(function () {
            AdminUtils.showMessage("Error guardando cambios", "error");
          });
      });
    },

    /**
     * Manejar exportación de informes
     */
    reportExport: function () {
      $(".wp-time-clock-export-button button").on("click", function () {
        const exportType = $(this).attr("name");
        const form = $(this).closest("form");

        form.append(
          `<input type="hidden" name="export" value="${exportType}">`
        );
        form.submit();
      });
    },

    /**
     * Manejar configuraciones
     */
    settingsManagement: function () {
      // Exportar configuración
      $(".wp-time-clock-export-settings").on("click", function () {
        AdminUtils.ajaxRequest(`${wpTimeClockAdmin.rest_url}/settings`)
          .done(function (response) {
            if (response.success) {
              const blob = new Blob([JSON.stringify(response.data, null, 2)], {
                type: "application/json",
              });
              const url = URL.createObjectURL(blob);
              const a = document.createElement("a");
              a.href = url;
              a.download = `wp-time-clock-settings-${new Date()
                .toISOString()
                .slice(0, 10)}.json`;
              a.click();
            } else {
              AdminUtils.showMessage("Error exportando configuración", "error");
            }
          })
          .fail(function () {
            AdminUtils.showMessage(
              "No se pudo exportar la configuración",
              "error"
            );
          });
      });

      // Importar configuración
      $(".wp-time-clock-import-settings").on("click", function () {
        const fileInput = $("#import_settings");
        const file = fileInput[0].files[0];

        if (!file) {
          AdminUtils.showMessage(
            "Selecciona un archivo para importar",
            "error"
          );
          return;
        }

        const reader = new FileReader();
        reader.onload = function (e) {
          try {
            const settings = JSON.parse(e.target.result);

            AdminUtils.ajaxRequest(
              `${wpTimeClockAdmin.rest_url}/settings`,
              "POST",
              settings
            )
              .done(function (response) {
                if (response.success) {
                  AdminUtils.showMessage(
                    `Importación exitosa: ${response.updated_count} configuraciones actualizadas`,
                    "success"
                  );
                  location.reload();
                } else {
                  AdminUtils.showMessage(
                    "Error importando configuración",
                    "error"
                  );
                }
              })
              .fail(function () {
                AdminUtils.showMessage(
                  "No se pudo importar la configuración",
                  "error"
                );
              });
          } catch (err) {
            AdminUtils.showMessage(
              "Archivo de configuración inválido",
              "error"
            );
          }
        };
        reader.readAsText(file);
      });

      // Restablecer configuraciones
      $(".wp-time-clock-reset-settings").on("click", function () {
        if (
          confirm(
            "¿Seguro que deseas restablecer todas las configuraciones a sus valores predeterminados?"
          )
        ) {
          AdminUtils.ajaxRequest(
            `${wpTimeClockAdmin.rest_url}/reset-settings`,
            "POST"
          )
            .done(function (response) {
              if (response.success) {
                AdminUtils.showMessage(
                  "Configuraciones restablecidas correctamente",
                  "success"
                );
                location.reload();
              } else {
                AdminUtils.showMessage(
                  "Error restableciendo configuraciones",
                  "error"
                );
              }
            })
            .fail(function () {
              AdminUtils.showMessage(
                "No se pudieron restablecer las configuraciones",
                "error"
              );
            });
        }
      });
    },

    /**
     * Manejar modales y popups
     */
    modalHandling: function () {
      // Cerrar modal con botón de cierre
      $(".wp-time-clock-modal-close").on("click", function () {
        $(this).closest(".wp-time-clock-modal").fadeOut(200);
      });

      // Cerrar modal con botón de cancelar
      $(".wp-time-clock-modal-cancel").on("click", function () {
        $(this).closest(".wp-time-clock-modal").fadeOut(200);
      });

      // Cerrar modal al hacer clic fuera
      $(window).on("click", function (e) {
        if ($(e.target).hasClass("wp-time-clock-modal")) {
          $(".wp-time-clock-modal").fadeOut(200);
        }
      });
    },
  };

  // Inicialización al cargar el documento
  $(document).ready(function () {
    // Inicializar componentes principales
    AdminComponents.initDashboard();

    // Inicializar manejadores
    AdminHandlers.init();

    // Manejar pestañas de configuración
    $(".wp-time-clock-tab").on("click", function (e) {
      e.preventDefault();
      const tabId = $(this).data("tab");

      // Actualizar pestañas
      $(".wp-time-clock-tab").removeClass("active");
      $(this).addClass("active");

      // Actualizar contenido
      $(".wp-time-clock-tab-content").removeClass("active");
      $(`#${tabId}`).addClass("active");

      // Actualizar URL con hash
      window.history.replaceState(null, "", `#${tabId}`);
    });

    // Verificar hash en la URL al cargar
    const hash = window.location.hash.substr(1);
    if (hash && $(`#${hash}`).length) {
      $(`.wp-time-clock-tab[data-tab="${hash}"]`).click();
    }

    // Manejar mostrar/ocultar campos dependientes
    $("#auto_clock_out").on("change", function () {
      $(".wp-time-clock-auto-clock-out-time").toggle($(this).is(":checked"));
    });
  });

  // Limpiar temporizadores al descargar la página
  $(window).on("beforeunload", function () {
    // Limpiar cualquier temporizador activo
    const timers = window.wpTimeClockTimers || [];
    timers.forEach(clearInterval);
  });
})(jQuery);

// Objeto global para manejar temporizadores
window.wpTimeClockTimers = [];
