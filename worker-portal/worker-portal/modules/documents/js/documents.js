/**
 * JavaScript mejorado para el módulo de documentos del Portal del Trabajador
 */
(function ($) {
  "use strict";

  // Objeto principal para el módulo de documentos
  const WorkerPortalDocuments = {
    /**
     * Inicialización
     */
    init: function () {
      // Registrar eventos de depuración si está habilitado
      if (workerPortalDocuments.debug) {
        console.log("Inicializando módulo de documentos...");
        console.log("Nonce disponible:", workerPortalDocuments.nonce);
        console.log("Nonce común:", workerPortalDocuments.common_nonce);
        console.log("Es admin:", workerPortalDocuments.is_admin);
      }

      this.setupFilters();
      this.setupPagination();
      this.setupDocumentActions();
      this.setupModals();
      this.setupFormSubmission();

      // Cargar documentos iniciales si estamos en la vista de lista
      if ($("#documents-list-content").length > 0) {
        this.loadFilteredDocuments(1);
      }

      // Manejar eventos de cambio de pestaña en el panel de administración
      this.setupTabEvents();
    },

    /**
     * Configurar eventos de pestaña
     */
    setupTabEvents: function () {
      // Si estamos en el panel de administración
      if ($(".worker-portal-tab-link").length > 0) {
        console.log("Configurando eventos de pestaña para documentos");

        // Cuando se hace clic en la pestaña de documentos
        $('.worker-portal-tab-link[data-tab="documents"]').on(
          "click",
          function () {
            console.log("Se ha hecho clic en la pestaña de documentos");

            // Cargamos documentos si estamos en la subpestaña de lista
            if ($("#subtab-doc-list").hasClass("active")) {
              console.log("Subpestaña de lista activa, cargando documentos...");
              WorkerPortalDocuments.loadFilteredDocuments(1);
            }
          }
        );

        // Cuando se hace clic en la subpestaña de lista de documentos
        $('.worker-portal-subtab-link[data-subtab="doc-list"]').on(
          "click",
          function () {
            console.log(
              "Se ha hecho clic en la subpestaña de lista de documentos"
            );
            WorkerPortalDocuments.loadFilteredDocuments(1);
          }
        );
      }
    },

    /**
     * Configurar filtros de búsqueda
     */
    setupFilters: function () {
      // Enviar formulario de filtros
      $("#documents-filter-form").on("submit", function (e) {
        e.preventDefault();
        WorkerPortalDocuments.loadFilteredDocuments(1);
      });

      // Limpiar filtros
      $("#clear-filters").on("click", function () {
        $("#documents-filter-form")[0].reset();
        WorkerPortalDocuments.loadFilteredDocuments(1);
      });

      // Para administradores: filtros en panel de admin
      $("#admin-documents-filter-form").on("submit", function (e) {
        e.preventDefault();
        WorkerPortalDocuments.loadFilteredDocuments(1);
      });

      $("#clear-filters-doc").on("click", function () {
        $("#admin-documents-filter-form")[0].reset();
        WorkerPortalDocuments.loadFilteredDocuments(1);
      });
    },

    /**
     * Carga documentos filtrados mediante AJAX
     * @param {number} page - Número de página para la paginación
     */
    loadFilteredDocuments: function (page) {
      console.log("Cargando documentos filtrados...");

      // Determinar el contenedor de documentos según la vista
      let container;
      if ($("#documents-list-content").length > 0) {
        container = $("#documents-list-content");
      } else if ($("#documents-list-container").length > 0) {
        container = $("#documents-list-container");
      } else {
        console.error(
          "No se encontró un contenedor válido para los documentos"
        );
        return;
      }

      // Mostrar indicador de carga
      container.html(
        '<div class="worker-portal-loading">' +
          '<div class="worker-portal-spinner"></div>' +
          "<p>" +
          workerPortalDocuments.i18n.loading +
          "</p>" +
          "</div>"
      );

      // Determinar el formulario a usar
      let formSelector;
      if ($("#documents-filter-form").length > 0) {
        formSelector = "#documents-filter-form";
      } else if ($("#admin-documents-filter-form").length > 0) {
        formSelector = "#admin-documents-filter-form";
      } else {
        console.log(
          "No se encontró un formulario de filtros, usando parámetros por defecto"
        );
      }

      // Crear objeto FormData con los datos del formulario o vacío si no hay formulario
      let formData;
      if (formSelector) {
        formData = new FormData($(formSelector)[0]);
      } else {
        formData = new FormData();
      }

      // Añadir parámetros AJAX
      formData.append("action", "filter_documents");

      // Determinar qué nonce usar (intentar con múltiples opciones)
      let nonce = this.getNonce();
      formData.append("nonce", nonce);

      // Añadir parámetros de paginación
      formData.append("page", page);
      formData.append("per_page", 10);

      // Realizar petición AJAX
      $.ajax({
        url: workerPortalDocuments.ajax_url,
        type: "POST",
        data: formData,
        processData: false,
        contentType: false,
        success: function (response) {
          console.log("Respuesta AJAX recibida:", response);

          if (response.success) {
            container.html(response.data.html);
            WorkerPortalDocuments.setupDocumentActions();
          } else {
            container.html(
              '<p class="worker-portal-no-data">' +
                (response.data || workerPortalDocuments.i18n.error) +
                "</p>"
            );
          }
        },
        error: function (xhr, status, error) {
          console.error("Error AJAX:", xhr.responseText);
          console.error("Estado:", status);
          console.error("Error:", error);

          container.html(
            '<p class="worker-portal-no-data">' +
              workerPortalDocuments.i18n.error +
              "</p>"
          );
        },
      });
    },

    /**
     * Obtiene el nonce más apropiado para solicitudes AJAX
     * Prueba varias opciones para mayor compatibilidad
     */
    getNonce: function () {
      let nonce;

      // Intentar obtener nonce del objeto de datos documento
      if (workerPortalDocuments.nonce) {
        nonce = workerPortalDocuments.nonce;
        console.log("Usando nonce de workerPortalDocuments:", nonce);
      }
      // Intentar con nonce común
      else if (workerPortalDocuments.common_nonce) {
        nonce = workerPortalDocuments.common_nonce;
        console.log("Usando nonce común:", nonce);
      }
      // Intentar con atributo data
      else if ($("#documents-list-container").data("nonce")) {
        nonce = $("#documents-list-container").data("nonce");
        console.log("Usando nonce de data-attribute:", nonce);
      }
      // Intentar con campos ocultos
      else if ($("#admin_nonce").length > 0) {
        nonce = $("#admin_nonce").val();
        console.log("Usando nonce de campo oculto admin_nonce:", nonce);
      } else if ($("#worker_portal_nonce").length > 0) {
        nonce = $("#worker_portal_nonce").val();
        console.log("Usando nonce de campo oculto worker_portal_nonce:", nonce);
      } else {
        console.warn(
          "No se encontró ningún nonce válido. Las solicitudes AJAX podrían fallar."
        );
        nonce = "";
      }

      return nonce;
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
          WorkerPortalDocuments.loadFilteredDocuments(page);

          // Scroll hacia arriba para mejor usabilidad
          $("html, body").animate(
            {
              scrollTop: $("#documents-list-content").offset().top - 50,
            },
            500
          );
        }
      );
    },

    /**
     * Configurar acciones sobre documentos (descargar, ver, etc.)
     */
    setupDocumentActions: function () {
      console.log("Configurando acciones de documentos");

      // Descargar documento
      $(document).on("click", ".worker-portal-download-document", function (e) {
        e.preventDefault();
        const documentId = $(this).data("document-id");
        WorkerPortalDocuments.downloadDocument(documentId);
      });

      // Ver documento
      $(document).on("click", ".worker-portal-view-document", function (e) {
        e.preventDefault();
        const documentId = $(this).data("document-id");
        WorkerPortalDocuments.viewDocument(documentId);
      });

      // Solo para administradores
      if (workerPortalDocuments.is_admin === "true") {
        // Ver detalles
        $(document).on(
          "click",
          ".worker-portal-document-details",
          function (e) {
            e.preventDefault();
            const documentId = $(this).data("document-id");
            WorkerPortalDocuments.viewDocumentDetails(documentId);
          }
        );

        // Eliminar documento
        $(document).on("click", ".worker-portal-delete-document", function (e) {
          e.preventDefault();
          const documentId = $(this).data("document-id");
          if (confirm(workerPortalDocuments.i18n.confirm_delete)) {
            WorkerPortalDocuments.deleteDocument(documentId);
          }
        });
      }
    },

    /**
     * Descarga un documento
     * @param {number} documentId - ID del documento a descargar
     */
    downloadDocument: function (documentId) {
      $.ajax({
        url: workerPortalDocuments.ajax_url,
        type: "POST",
        data: {
          action: "download_document",
          nonce: this.getNonce(),
          document_id: documentId,
        },
        success: function (response) {
          if (response.success) {
            // Crear enlace de descarga
            const link = document.createElement("a");
            link.href = response.data.download_url;
            link.download = response.data.filename;
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
          } else {
            alert(response.data || workerPortalDocuments.i18n.error);
          }
        },
        error: function (xhr, status, error) {
          console.error("Error al descargar documento:", error);
          alert(workerPortalDocuments.i18n.error);
        },
      });
    },

    /**
     * Muestra un documento en el modal
     * @param {number} documentId - ID del documento a mostrar
     */
    viewDocument: function (documentId) {
      $.ajax({
        url: workerPortalDocuments.ajax_url,
        type: "POST",
        data: {
          action: "download_document",
          nonce: this.getNonce(),
          document_id: documentId,
        },
        beforeSend: function () {
          $("#document-modal-content").html(
            '<div class="worker-portal-loading">' +
              '<div class="worker-portal-spinner"></div>' +
              "<p>" +
              workerPortalDocuments.i18n.loading +
              "</p>" +
              "</div>"
          );
          $("#document-view-modal").fadeIn(200);
        },
        success: function (response) {
          if (response.success) {
            // Mostrar PDF en iframe
            const html = `<iframe src="${response.data.download_url}" style="width:100%; height:500px; border:none;"></iframe>`;
            $("#document-modal-content").html(html);

            // Obtener título del documento
            $.ajax({
              url: workerPortalDocuments.ajax_url,
              type: "POST",
              data: {
                action: "get_document_details",
                nonce: WorkerPortalDocuments.getNonce(),
                document_id: documentId,
              },
              success: function (detailsResponse) {
                if (detailsResponse.success) {
                  $("#document-modal-title").text(detailsResponse.data.title);
                }
              },
            });
          } else {
            $("#document-modal-content").html(
              '<div class="worker-portal-error">' + response.data + "</div>"
            );
          }
        },
        error: function (xhr, status, error) {
          console.error("Error al mostrar documento:", error);
          $("#document-modal-content").html(
            '<div class="worker-portal-error">' +
              workerPortalDocuments.i18n.error +
              "</div>"
          );
        },
      });
    },

    /**
     * Ver detalles de un documento (solo para administradores)
     * @param {number} documentId - ID del documento
     */
    viewDocumentDetails: function (documentId) {
      $.ajax({
        url: workerPortalDocuments.ajax_url,
        type: "POST",
        data: {
          action: "admin_get_document_details",
          nonce: this.getNonce(),
          document_id: documentId,
        },
        beforeSend: function () {
          $("#document-details-content").html(
            '<div class="worker-portal-loading">' +
              '<div class="worker-portal-spinner"></div>' +
              "<p>" +
              workerPortalDocuments.i18n.loading +
              "</p>" +
              "</div>"
          );
          $("#document-details-modal").fadeIn(200);
        },
        success: function (response) {
          if (response.success) {
            const document = response.data;

            let html =
              '<table class="worker-portal-details-table">' +
              "<tr>" +
              "<th>ID:</th>" +
              "<td>" +
              document.id +
              "</td>" +
              "</tr>" +
              "<tr>" +
              "<th>Título:</th>" +
              "<td>" +
              document.title +
              "</td>" +
              "</tr>" +
              "<tr>" +
              "<th>Categoría:</th>" +
              "<td>" +
              document.category_name +
              "</td>" +
              "</tr>" +
              "<tr>" +
              "<th>Descripción:</th>" +
              "<td>" +
              (document.description || "Sin descripción") +
              "</td>" +
              "</tr>" +
              "<tr>" +
              "<th>Usuario:</th>" +
              "<td>" +
              document.user_name +
              "</td>" +
              "</tr>" +
              "<tr>" +
              "<th>Fecha de subida:</th>" +
              "<td>" +
              document.upload_date +
              "</td>" +
              "</tr>" +
              "<tr>" +
              "<th>Archivo:</th>" +
              "<td>" +
              '<a href="' +
              document.download_url +
              '" target="_blank" class="worker-portal-button worker-portal-button-small worker-portal-button-outline">' +
              '<i class="dashicons dashicons-visibility"></i> Ver documento' +
              "</a>" +
              "</td>" +
              "</tr>" +
              "</table>" +
              '<div class="worker-portal-document-actions" style="margin-top: 20px;">' +
              '<button type="button" class="worker-portal-button worker-portal-button-danger worker-portal-delete-document" data-document-id="' +
              document.id +
              '">' +
              '<i class="dashicons dashicons-trash"></i> Eliminar documento' +
              "</button>" +
              "</div>";

            $("#document-details-content").html(html);

            // Reinicializar evento para el botón de eliminar
            $("#document-details-modal .worker-portal-delete-document").on(
              "click",
              function () {
                const docId = $(this).data("document-id");
                if (confirm(workerPortalDocuments.i18n.confirm_delete)) {
                  WorkerPortalDocuments.deleteDocument(docId);
                }
              }
            );
          } else {
            $("#document-details-content").html(
              '<div class="worker-portal-error">' + response.data + "</div>"
            );
          }
        },
        error: function (xhr, status, error) {
          console.error("Error al cargar detalles:", error);
          $("#document-details-content").html(
            '<div class="worker-portal-error">' +
              workerPortalDocuments.i18n.error +
              "</div>"
          );
        },
      });
    },

    /**
     * Eliminar un documento (solo para administradores)
     * @param {number} documentId - ID del documento a eliminar
     */
    deleteDocument: function (documentId) {
      $.ajax({
        url: workerPortalDocuments.ajax_url,
        type: "POST",
        data: {
          action: "admin_delete_document",
          nonce: this.getNonce(),
          document_id: documentId,
        },
        success: function (response) {
          if (response.success) {
            alert(response.data);

            // Cerrar modal si está abierto
            $("#document-details-modal").fadeOut(200);

            // Recargar la lista de documentos
            WorkerPortalDocuments.loadFilteredDocuments(1);
          } else {
            alert(response.data || workerPortalDocuments.i18n.error);
          }
        },
        error: function (xhr, status, error) {
          console.error("Error al eliminar documento:", error);
          alert(workerPortalDocuments.i18n.error);
        },
      });
    },

    /**
     * Configurar modales
     */
    setupModals: function () {
      // Cerrar modal con botón
      $(".worker-portal-modal-close").on("click", function () {
        $(this).closest(".worker-portal-modal").fadeOut(200);
      });

      // Cerrar haciendo clic fuera o con ESC
      $(window).on("click", function (e) {
        if ($(e.target).hasClass("worker-portal-modal")) {
          $(".worker-portal-modal").fadeOut(200);
        }
      });

      $(document).on("keydown", function (e) {
        if (e.key === "Escape" && $(".worker-portal-modal:visible").length) {
          $(".worker-portal-modal").fadeOut(200);
        }
      });
    },

    /**
     * Configurar el envío del formulario de subida de documentos
     */
    setupFormSubmission: function () {
      // Comprobar si el formulario existe
      if ($("#upload-document-form").length > 0) {
        console.log("Configurando formulario de subida de documentos");

        $("#upload-document-form").on("submit", function (e) {
          e.preventDefault();
          console.log("Enviando formulario de subida de documentos");

          var formData = new FormData(this);
          formData.append("action", "admin_upload_document");
          formData.append("nonce", WorkerPortalDocuments.getNonce());

          // Verificar que se ha seleccionado un archivo
          if (!$("#document-file")[0].files.length) {
            alert("Por favor, selecciona un archivo PDF.");
            return;
          }

          // Verificar que se ha seleccionado al menos un usuario
          var users = $("#document-users").val();
          if (!users || users.length === 0) {
            alert("Por favor, selecciona al menos un destinatario.");
            return;
          }

          $.ajax({
            url: workerPortalDocuments.ajax_url,
            type: "POST",
            data: formData,
            contentType: false,
            processData: false,
            beforeSend: function () {
              $(this)
                .find("button[type=submit]")
                .prop("disabled", true)
                .html(
                  '<i class="dashicons dashicons-update-alt spinning"></i> Subiendo...'
                );
            }.bind(this),
            success: function (response) {
              console.log("Respuesta de subida:", response);
              if (response.success) {
                alert(
                  response.data.message || "Documento subido correctamente"
                );
                this.reset();

                // Cambiar a la pestaña de lista de documentos
                if (
                  $('.worker-portal-subtab-link[data-subtab="doc-list"]').length
                ) {
                  $(
                    '.worker-portal-subtab-link[data-subtab="doc-list"]'
                  ).click();
                } else {
                  // Recargar la página
                  window.location.reload();
                }
              } else {
                alert(response.data || "Error al subir el documento");
              }
            }.bind(this),
            error: function (xhr, status, error) {
              console.error("Error AJAX al subir documento:", error);
              alert(
                "Ha ocurrido un error al subir el documento. Por favor, inténtalo de nuevo."
              );
            },
            complete: function () {
              $(this)
                .find("button[type=submit]")
                .prop("disabled", false)
                .html(
                  '<i class="dashicons dashicons-upload"></i> Subir Documento'
                );
            }.bind(this),
          });
        });
      }

      // Configurar formulario de configuración de documentos
      if ($("#document-settings-form").length > 0) {
        console.log("Configurando formulario de configuración de documentos");

        // Añadir categoría
        $("#add-category").on("click", function () {
          var newRow =
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
        });

        // Eliminar categoría
        $(document).on("click", ".remove-category", function () {
          // Si solo queda una categoría, mostrar mensaje
          if ($(".category-row").length <= 1) {
            alert("Debe existir al menos una categoría.");
            return;
          }

          $(this).closest("tr").remove();
        });

        // Enviar formulario de configuración
        $("#document-settings-form").on("submit", function (e) {
          e.preventDefault();

          var formData = new FormData(this);
          formData.append("action", "admin_save_document_settings");
          formData.append("nonce", WorkerPortalDocuments.getNonce());

          $.ajax({
            url: workerPortalDocuments.ajax_url,
            type: "POST",
            data: formData,
            contentType: false,
            processData: false,
            beforeSend: function () {
              $(this)
                .find("button[type=submit]")
                .prop("disabled", true)
                .html("Guardando...");
            }.bind(this),
            success: function (response) {
              if (response.success) {
                alert(response.data.message);
              } else {
                alert(response.data || "Error al guardar la configuración");
              }
            },
            error: function (xhr, status, error) {
              console.error("Error al guardar configuración:", error);
              alert(
                "Ha ocurrido un error al guardar la configuración. Por favor, inténtalo de nuevo."
              );
            },
            complete: function () {
              $(this)
                .find("button[type=submit]")
                .prop("disabled", false)
                .html("Guardar Cambios");
            }.bind(this),
          });
        });
      }
    },
  };

  // Inicializar cuando el DOM esté listo
  $(function () {
    // Verificar si existe el objeto con las variables necesarias
    if (typeof workerPortalDocuments === "undefined") {
      console.error(
        "Error: No se encontraron las variables necesarias para el módulo de documentos"
      );
      return;
    }

    WorkerPortalDocuments.init();
  });
})(jQuery);
