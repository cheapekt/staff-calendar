/**
 * JavaScript corregido para el módulo de trabajadores del Portal del Trabajador
 *
 * Maneja todas las interacciones del usuario en el perfil de trabajador
 * y en la gestión de trabajadores desde el panel de administración.
 */
(function ($) {
  "use strict";

  // Objeto principal para el módulo de trabajadores
  const WorkerPortalWorkers = {
    // Inicialización
    init: function () {
      this.setupProfileFunctions();
      this.setupAdminFunctions();
      this.setupModals();
    },

    // Funciones para el perfil de trabajador
    setupProfileFunctions: function () {
      // Abrir modal para editar perfil
      $("#edit-profile-button").on("click", function () {
        $("#edit-profile-modal").fadeIn();
      });

      // Abrir modal para cambiar contraseña
      $("#change-password-button").on("click", function () {
        $("#change-password-modal").fadeIn();
      });

      // Enviar formulario de edición de perfil
      $("#edit-profile-form").on("submit", function (e) {
        e.preventDefault();
        WorkerPortalWorkers.updateWorkerProfile(this);
      });

      // Enviar formulario de cambio de contraseña
      $("#change-password-form").on("submit", function (e) {
        e.preventDefault();
        WorkerPortalWorkers.updateWorkerPassword(this);
      });

      // Medidor de fortaleza de contraseña
      $("#new-password").on("keyup", function () {
        WorkerPortalWorkers.checkPasswordStrength(
          $(this),
          $(".password-strength-meter")
        );
      });

      // Navegación a otras secciones
      $(".section-link").on("click", function (e) {
        e.preventDefault();
        const section = $(this).data("section");
        $(
          '.worker-portal-navigation a[data-section="' + section + '"]'
        ).click();
      });
    },

    // Funciones para la administración de trabajadores
    setupAdminFunctions: function () {
      // Solo inicializar si estamos en la página de administración
      if ($(".worker-portal-admin-workers").length === 0) {
        return;
      }

      // Navegación entre pestañas
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

      // Filtrar trabajadores
      $("#workers-filter-form").on("submit", function (e) {
        e.preventDefault();
        WorkerPortalWorkers.filterWorkers();
      });

      // Limpiar filtros
      $("#clear-workers-filters").on("click", function () {
        $("#workers-filter-form")[0].reset();
        WorkerPortalWorkers.filterWorkers();
      });

      // Añadir nuevo trabajador
      $("#add-worker-form").on("submit", function (e) {
        e.preventDefault();
        WorkerPortalWorkers.addNewWorker(this);
      });

      // Guardar configuración
      $("#worker-settings-form").on("submit", function (e) {
        e.preventDefault();
        WorkerPortalWorkers.saveWorkerSettings(this);
      });

      // Exportar trabajadores
      $("#export-workers-button").on("click", function () {
        WorkerPortalWorkers.exportWorkers();
      });

      // Ver detalles de trabajador (delegación de eventos)
      $(document).on("click", ".view-worker", function () {
        const userId = $(this).data("user-id");
        WorkerPortalWorkers.viewWorkerDetails(userId);
      });

      // Editar trabajador (delegación de eventos)
      $(document).on("click", ".edit-worker", function () {
        const userId = $(this).data("user-id");
        WorkerPortalWorkers.editWorker(userId);
      });

      // Activar/Desactivar trabajador (delegación de eventos)
      $(document).on("click", ".activate-worker", function () {
        const userId = $(this).data("user-id");
        WorkerPortalWorkers.changeWorkerStatus(userId, "activate");
      });

      $(document).on("click", ".deactivate-worker", function () {
        const userId = $(this).data("user-id");
        WorkerPortalWorkers.changeWorkerStatus(userId, "deactivate");
      });

      // Restablecer contraseña (delegación de eventos)
      $(document).on("click", ".reset-password", function () {
        const userId = $(this).data("user-id");
        WorkerPortalWorkers.resetPassword(userId);
      });

      // Ver actividad (delegación de eventos)
      $(document).on("click", ".view-activity", function () {
        const type = $(this).data("type");
        const id = $(this).data("id");
        WorkerPortalWorkers.viewActivity(type, id);
      });

      // Accesos rápidos (delegación de eventos)
      $(document).on("click", ".view-worker-expenses", function () {
        const userId = $(this).data("user-id");
        WorkerPortalWorkers.viewWorkerExpenses(userId);
      });

      $(document).on("click", ".view-worker-worksheets", function () {
        const userId = $(this).data("user-id");
        WorkerPortalWorkers.viewWorkerWorksheets(userId);
      });

      $(document).on("click", ".view-worker-documents", function () {
        const userId = $(this).data("user-id");
        WorkerPortalWorkers.viewWorkerDocuments(userId);
      });

      $(document).on("click", ".view-worker-incentives", function () {
        const userId = $(this).data("user-id");
        WorkerPortalWorkers.viewWorkerIncentives(userId);
      });

      // Medidor de fortaleza de contraseña
      $("#worker-password, #edit-password").on("keyup", function () {
        WorkerPortalWorkers.checkPasswordStrength(
          $(this),
          $(this).siblings(".password-strength-meter")
        );
      });

      // Mostrar/ocultar campos de contraseña en edición
      $(document).on("change", "#edit-reset-password", function () {
        if ($(this).is(":checked")) {
          $("#reset-password-container").slideDown();
        } else {
          $("#reset-password-container").slideUp();
        }
      });

      // Gestión de categorías
      $("#add-category").on("click", function () {
        WorkerPortalWorkers.addCategory();
      });

      $(document).on("click", ".remove-category", function () {
        WorkerPortalWorkers.removeCategory(this);
      });
    },

    // Configurar modales
    setupModals: function () {
      // Cerrar modales
      $(".worker-portal-modal-close").on("click", function () {
        $(this).closest(".worker-portal-modal").fadeOut();
      });

      // Cerrar modales al hacer clic fuera
      $(window).on("click", function (e) {
        if ($(e.target).hasClass("worker-portal-modal")) {
          $(".worker-portal-modal").fadeOut();
        }
      });

      // Cerrar modales con Escape
      $(document).on("keydown", function (e) {
        if (e.key === "Escape") {
          $(".worker-portal-modal").fadeOut();
        }
      });
    },

    // Funciones para el perfil de trabajador
    updateWorkerProfile: function (form) {
      const formData = new FormData(form);

      $.ajax({
        url: worker_portal_params.ajax_url, // CORREGIDO: Usar worker_portal_params
        type: "POST",
        data: formData,
        processData: false,
        contentType: false,
        beforeSend: function () {
          $(form)
            .find('button[type="submit"]')
            .prop("disabled", true)
            .html(
              '<i class="dashicons dashicons-update-alt spinning"></i> Guardando...'
            );
        },
        success: function (response) {
          if (response.success) {
            alert(response.data.message);
            $("#edit-profile-modal").fadeOut();
            // Recargar página para mostrar cambios
            location.reload();
          } else {
            alert(response.data);
          }
        },
        error: function () {
          alert("Ha ocurrido un error. Por favor, inténtalo de nuevo.");
        },
        complete: function () {
          $(form)
            .find('button[type="submit"]')
            .prop("disabled", false)
            .html('<i class="dashicons dashicons-yes"></i> Guardar Cambios');
        },
      });
    },

    updateWorkerPassword: function (form) {
      // Validar que las contraseñas coinciden
      const newPassword = $("#new-password").val();
      const confirmPassword = $("#confirm-password").val();

      if (newPassword !== confirmPassword) {
        alert("Las contraseñas no coinciden.");
        return;
      }

      const formData = new FormData(form);

      $.ajax({
        url: worker_portal_params.ajax_url, // CORREGIDO: Usar worker_portal_params
        type: "POST",
        data: formData,
        processData: false,
        contentType: false,
        beforeSend: function () {
          $(form)
            .find('button[type="submit"]')
            .prop("disabled", true)
            .html(
              '<i class="dashicons dashicons-update-alt spinning"></i> Cambiando...'
            );
        },
        success: function (response) {
          if (response.success) {
            alert(response.data.message);
            $("#change-password-modal").fadeOut();
            form.reset();
          } else {
            alert(response.data);
          }
        },
        error: function () {
          alert("Ha ocurrido un error. Por favor, inténtalo de nuevo.");
        },
        complete: function () {
          $(form)
            .find('button[type="submit"]')
            .prop("disabled", false)
            .html('<i class="dashicons dashicons-yes"></i> Cambiar Contraseña');
        },
      });
    },

    // Funciones para la administración de trabajadores
    filterWorkers: function () {
      const search = $("#filter-worker-name").val().toLowerCase();
      const role = $("#filter-worker-role").val();
      const status = $("#filter-worker-status").val();

      $("#workers-table tbody tr").each(function () {
        const $row = $(this);
        let showRow = true;

        // Filtrar por nombre/email
        if (search) {
          const text = $row.text().toLowerCase();
          if (text.indexOf(search) === -1) {
            showRow = false;
          }
        }

        // Filtrar por rol
        if (role && showRow) {
          let workerRole = "";
          if (role === "supervisor") {
            workerRole = "Supervisor";
          } else {
            workerRole = "Trabajador";
          }

          if ($row.find("td:nth-child(3)").text() !== workerRole) {
            showRow = false;
          }
        }

        // Filtrar por estado
        if (status && showRow) {
          const isActive = !$row.hasClass("worker-inactive");
          if (
            (status === "active" && !isActive) ||
            (status === "inactive" && isActive)
          ) {
            showRow = false;
          }
        }

        // Mostrar u ocultar fila
        $row.toggle(showRow);
      });

      // Mostrar mensaje si no hay resultados
      const visibleRows = $("#workers-table tbody tr:visible").length;
      if (visibleRows === 0) {
        if ($("#no-results-row").length === 0) {
          $("#workers-table tbody").append(
            '<tr id="no-results-row"><td colspan="8" class="worker-portal-no-data">' +
              "No se encontraron trabajadores con los criterios seleccionados." +
              "</td></tr>"
          );
        }
      } else {
        $("#no-results-row").remove();
      }
    },

    addNewWorker: function (form) {
      // Validar contraseñas
      const password = $("#worker-password").val();
      const confirmPassword = $("#worker-confirm-password").val();

      if (password !== confirmPassword) {
        alert("Las contraseñas no coinciden.");
        return;
      }

      // Validar fortaleza de contraseña
      if ($("#enforce-strong-passwords").is(":checked")) {
        const strongRegex = new RegExp(
          "^(?=.*[a-z])(?=.*[A-Z])(?=.*[0-9])(?=.*[!@#$%^&*])(?=.{8,})"
        );
        if (!strongRegex.test(password)) {
          alert(
            "La contraseña debe tener al menos 8 caracteres e incluir mayúsculas, minúsculas, números y símbolos."
          );
          return;
        }
      }

      const formData = new FormData(form);

      $.ajax({
        url: worker_portal_params.ajax_url, // CORREGIDO: Usar worker_portal_params
        type: "POST",
        data: formData,
        processData: false,
        contentType: false,
        beforeSend: function () {
          $(form)
            .find('button[type="submit"]')
            .prop("disabled", true)
            .html(
              '<i class="dashicons dashicons-update-alt spinning"></i> Guardando...'
            );
        },
        success: function (response) {
          if (response.success) {
            alert(response.data.message);
            form.reset();

            // Recargar la página para mostrar el nuevo trabajador
            location.reload();
          } else {
            alert(response.data);
          }
        },
        error: function () {
          alert("Ha ocurrido un error. Por favor, inténtalo de nuevo.");
        },
        complete: function () {
          $(form)
            .find('button[type="submit"]')
            .prop("disabled", false)
            .html(
              '<i class="dashicons dashicons-plus-alt"></i> Añadir Trabajador'
            );
        },
      });
    },

    saveWorkerSettings: function (form) {
      const formData = new FormData(form);

      $.ajax({
        url: worker_portal_params.ajax_url, // CORREGIDO: Usar worker_portal_params
        type: "POST",
        data: formData,
        processData: false,
        contentType: false,
        beforeSend: function () {
          $(form)
            .find('button[type="submit"]')
            .prop("disabled", true)
            .html(
              '<i class="dashicons dashicons-update-alt spinning"></i> Guardando...'
            );
        },
        success: function (response) {
          if (response.success) {
            alert(response.data.message);
          } else {
            alert(response.data);
          }
        },
        error: function () {
          alert("Ha ocurrido un error. Por favor, inténtalo de nuevo.");
        },
        complete: function () {
          $(form)
            .find('button[type="submit"]')
            .prop("disabled", false)
            .html(
              '<i class="dashicons dashicons-yes"></i> Guardar Configuración'
            );
        },
      });
    },

    exportWorkers: function () {
      // Mostrar indicador de carga
      $("#export-workers-button")
        .prop("disabled", true)
        .html(
          '<i class="dashicons dashicons-update-alt spinning"></i> Exportando...'
        );

      $.ajax({
        url: worker_portal_params.ajax_url, // CORREGIDO: Usar worker_portal_params
        type: "POST",
        data: {
          action: "export_workers",
          nonce: $('#worker-settings-form input[name="nonce"]').val(),
          search: $("#filter-worker-name").val(),
          role: $("#filter-worker-role").val(),
          status: $("#filter-worker-status").val(),
        },
        success: function (response) {
          if (response.success) {
            // Crear enlace para descargar
            const link = document.createElement("a");
            link.href = response.data.file_url;
            link.download = response.data.filename;
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
          } else {
            alert(response.data || "Error al exportar trabajadores");
          }
        },
        error: function () {
          alert("Ha ocurrido un error. Por favor, inténtalo de nuevo.");
        },
        complete: function () {
          // Restaurar botón
          $("#export-workers-button")
            .prop("disabled", false)
            .html(
              '<i class="dashicons dashicons-download"></i> Exportar a Excel'
            );
        },
      });
    },

    viewWorkerDetails: function (userId) {
      // Depurar información sobre nonces disponibles
      console.log("Valores de nonce disponibles:");
      console.log("admin_nonce:", $("#admin_nonce").val());
      console.log(
        "worker_settings_form nonce:",
        $('#worker-settings-form input[name="nonce"]').val()
      );
      console.log("worker_portal_ajax_nonce:", worker_portal_params.nonce);

      // Vamos a intentar encontrar cualquier nonce disponible
      var nonce =
        $("#admin_nonce").val() ||
        $('#worker-settings-form input[name="nonce"]').val() ||
        $('input[name="nonce"]').val() ||
        worker_portal_params.nonce;

      console.log("Nonce que se usará:", nonce);
      console.log("URL AJAX:", worker_portal_params.ajax_url);

      $.ajax({
        url: worker_portal_params.ajax_url,
        type: "POST",
        data: {
          action: "get_worker_details",
          user_id: userId,
          nonce: nonce,
        },
        beforeSend: function () {
          $("#worker-details-content").html(
            '<div class="worker-portal-loading">' +
              '<div class="worker-portal-spinner"></div>' +
              "<p>Cargando detalles...</p>" +
              "</div>"
          );
          $("#worker-details-modal").fadeIn();
        },
        success: function (response) {
          console.log("Respuesta de AJAX:", response);
          if (response.success) {
            $("#worker-details-content").html(response.data.html);
          } else {
            $("#worker-details-content").html(
              '<div class="worker-portal-error">' +
                (response.data || "Error al cargar detalles del trabajador") +
                "</div>"
            );
          }
        },
        error: function (xhr, status, error) {
          console.error("Error AJAX:", xhr.responseText);
          console.error("Status:", status);
          console.error("Error:", error);
          $("#worker-details-content").html(
            '<div class="worker-portal-error">' +
              "Ha ocurrido un error. Por favor, inténtalo de nuevo.<br>" +
              "Detalles del error: " +
              error +
              "</div>"
          );
        },
      });
    },

    editWorker: function (userId) {
      $.ajax({
        url: worker_portal_params.ajax_url, // CORREGIDO: Usar worker_portal_params
        type: "POST",
        data: {
          action: "get_worker_edit_form",
          user_id: userId,
          nonce:
            $("#admin_nonce").val() ||
            $('#worker-settings-form input[name="nonce"]').val(),
        },
        beforeSend: function () {
          $("#edit-worker-content").html(
            '<div class="worker-portal-loading">' +
              '<div class="worker-portal-spinner"></div>' +
              "<p>Cargando formulario...</p>" +
              "</div>"
          );
          $("#edit-worker-modal").fadeIn();
        },
        success: function (response) {
          if (response.success) {
            $("#edit-worker-content").html(response.data.html);

            // Inicializar el formulario de edición
            WorkerPortalWorkers.initEditWorkerForm();
          } else {
            $("#edit-worker-content").html(
              '<div class="worker-portal-error">' +
                (response.data || "Error al cargar formulario de edición") +
                "</div>"
            );
          }
        },
        error: function () {
          $("#edit-worker-content").html(
            '<div class="worker-portal-error">' +
              "Ha ocurrido un error. Por favor, inténtalo de nuevo." +
              "</div>"
          );
        },
      });
    },

    initEditWorkerForm: function () {
      $("#edit-worker-form").on("submit", function (e) {
        e.preventDefault();

        // Validar contraseñas si se ha marcado "Restablecer contraseña"
        if ($("#edit-reset-password").is(":checked")) {
          const password = $("#edit-password").val();
          const confirmPassword = $("#edit-confirm-password").val();

          if (password !== confirmPassword) {
            alert("Las contraseñas no coinciden.");
            return;
          }

          // Validar fortaleza de contraseña si está habilitado
          if ($("#enforce-strong-passwords").is(":checked")) {
            const strongRegex = new RegExp(
              "^(?=.*[a-z])(?=.*[A-Z])(?=.*[0-9])(?=.*[!@#$%^&*])(?=.{8,})"
            );
            if (!strongRegex.test(password)) {
              alert(
                "La contraseña debe tener al menos 8 caracteres e incluir mayúsculas, minúsculas, números y símbolos."
              );
              return;
            }
          }
        }

        const formData = new FormData(this);

        $.ajax({
          url: worker_portal_params.ajax_url, // CORREGIDO: Usar worker_portal_params
          type: "POST",
          data: formData,
          processData: false,
          contentType: false,
          beforeSend: function () {
            $('#edit-worker-form button[type="submit"]')
              .prop("disabled", true)
              .html(
                '<i class="dashicons dashicons-update-alt spinning"></i> Guardando...'
              );
          },
          success: function (response) {
            if (response.success) {
              alert(response.data.message);
              $("#edit-worker-modal").fadeOut();

              // Recargar la página para mostrar los cambios
              location.reload();
            } else {
              alert(response.data);
            }
          },
          error: function () {
            alert("Ha ocurrido un error. Por favor, inténtalo de nuevo.");
          },
          complete: function () {
            $('#edit-worker-form button[type="submit"]')
              .prop("disabled", false)
              .html('<i class="dashicons dashicons-yes"></i> Guardar Cambios');
          },
        });
      });
    },

    changeWorkerStatus: function (userId, action) {
      const confirmMsg =
        action === "activate"
          ? "¿Estás seguro de que deseas activar a este trabajador?"
          : "¿Estás seguro de que deseas desactivar a este trabajador?";

      if (!confirm(confirmMsg)) {
        return;
      }

      $.ajax({
        url: worker_portal_params.ajax_url, // CORREGIDO: Usar worker_portal_params
        type: "POST",
        data: {
          action: "change_worker_status",
          user_id: userId,
          status: action,
          nonce:
            $("#admin_nonce").val() ||
            $('#worker-settings-form input[name="nonce"]').val(),
        },
        success: function (response) {
          if (response.success) {
            alert(response.data.message);

            // Recargar la página para mostrar los cambios
            location.reload();
          } else {
            alert(response.data);
          }
        },
        error: function () {
          alert("Ha ocurrido un error. Por favor, inténtalo de nuevo.");
        },
      });
    },

    resetPassword: function (userId) {
      if (
        !confirm(
          "¿Estás seguro de que deseas restablecer la contraseña de este trabajador?"
        )
      ) {
        return;
      }

      // Solicitar nueva contraseña
      const newPassword = prompt("Introduce la nueva contraseña:");
      if (!newPassword) {
        return; // El usuario ha cancelado
      }

      // Validar fortaleza de contraseña si está habilitado
      if ($("#enforce-strong-passwords").is(":checked")) {
        const strongRegex = new RegExp(
          "^(?=.*[a-z])(?=.*[A-Z])(?=.*[0-9])(?=.*[!@#$%^&*])(?=.{8,})"
        );
        if (!strongRegex.test(newPassword)) {
          alert(
            "La contraseña debe tener al menos 8 caracteres e incluir mayúsculas, minúsculas, números y símbolos."
          );
          return;
        }
      }

      $.ajax({
        url: worker_portal_params.ajax_url, // CORREGIDO: Usar worker_portal_params
        type: "POST",
        data: {
          action: "reset_worker_password",
          user_id: userId,
          password: newPassword,
          nonce:
            $("#admin_nonce").val() ||
            $('#worker-settings-form input[name="nonce"]').val(),
        },
        success: function (response) {
          if (response.success) {
            alert(response.data.message);
          } else {
            alert(response.data);
          }
        },
        error: function () {
          alert("Ha ocurrido un error. Por favor, inténtalo de nuevo.");
        },
      });
    },

    viewActivity: function (type, id) {
      // Redirigir a la página correspondiente según el tipo de actividad
      switch (type) {
        case "expense":
          $('.worker-portal-tab-link[data-tab="pending-expenses"]').click();
          // TODO: Implementar vista detallada del gasto específico
          break;

        case "worksheet":
          $('.worker-portal-tab-link[data-tab="worksheets"]').click();
          // TODO: Implementar vista detallada de la hoja de trabajo específica
          break;

        case "incentive":
          $('.worker-portal-tab-link[data-tab="incentives"]').click();
          // TODO: Implementar vista detallada del incentivo específico
          break;
      }
    },

    viewWorkerExpenses: function (userId) {
      $('.worker-portal-tab-link[data-tab="pending-expenses"]').click();

      // Establecer filtro de usuario
      $("#filter-worker").val(userId).trigger("change");
      $("#admin-expenses-filter-form").submit();
    },

    viewWorkerWorksheets: function (userId) {
      $('.worker-portal-tab-link[data-tab="worksheets"]').click();

      // Establecer filtro de usuario
      $("#filter-worker-ws").val(userId).trigger("change");
      $("#admin-worksheets-filter-form").submit();
    },

    viewWorkerDocuments: function (userId) {
      $('.worker-portal-tab-link[data-tab="documents"]').click();

      // Asegurarse de que estamos en la subpestaña de lista
      $('.worker-portal-subtab-link[data-subtab="doc-list"]').click();

      // Establecer filtro de usuario
      $("#filter-worker-doc").val(userId).trigger("change");
      $("#admin-documents-filter-form").submit();
    },

    viewWorkerIncentives: function (userId) {
      $('.worker-portal-tab-link[data-tab="incentives"]').click();

      // Establecer filtro de usuario
      $("#filter-worker-inc").val(userId).trigger("change");
      $("#admin-incentives-filter-form").submit();
    },

    checkPasswordStrength: function (passwordField, strengthMeter) {
      const password = passwordField.val();
      let strength = 0;

      // Si la contraseña es mayor a 6 caracteres, sumar puntos
      if (password.length >= 6) strength += 1;

      // Si la contraseña tiene letras minúsculas y mayúsculas, sumar puntos
      if (password.match(/([a-z].*[A-Z])|([A-Z].*[a-z])/)) strength += 1;

      // Si la contraseña tiene números, sumar puntos
      if (password.match(/([0-9])/)) strength += 1;

      // Si la contraseña tiene caracteres especiales, sumar puntos
      if (password.match(/([!,%,&,@,#,$,^,*,?,_,~])/)) strength += 1;

      // Mostrar el indicador de fuerza
      if (strength < 2) {
        strengthMeter.html("Débil").css("color", "red");
      } else if (strength === 2) {
        strengthMeter.html("Regular").css("color", "orange");
      } else if (strength === 3) {
        strengthMeter.html("Buena").css("color", "yellowgreen");
      } else {
        strengthMeter.html("Fuerte").css("color", "green");
      }
    },

    addCategory: function () {
      const newRow =
        '<tr class="category-row">' +
        "<td>" +
        '<input type="text" name="categories[keys][]" required>' +
        "</td>" +
        "<td>" +
        '<input type="text" name="categories[labels][]" required>' +
        "</td>" +
        "<td>" +
        '<button type="button" class="worker-portal-button worker-portal-button-small worker-portal-button-outline remove-category">' +
        '<i class="dashicons dashicons-trash"></i>' +
        "</button>" +
        "</td>" +
        "</tr>";

      $("#categories-list").append(newRow);
    },

    removeCategory: function (button) {
      // Si solo queda una categoría, mostrar mensaje
      if ($(".category-row").length <= 1) {
        alert("Debe existir al menos una categoría.");
        return;
      }

      $(button).closest("tr").remove();
    },
  };

  // Inicializar cuando el DOM esté listo
  $(function () {
    WorkerPortalWorkers.init();
  });
})(jQuery);
