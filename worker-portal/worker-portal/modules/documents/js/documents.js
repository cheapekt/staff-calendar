/**
 * JavaScript para el módulo de documentos del Portal del Trabajador
 */
(function ($) {
  "use strict";

  // Objeto principal para el módulo de documentos
  const WorkerPortalDocuments = {
    /**
     * Inicialización
     */
    init: function () {
      this.setupFilters();
      this.setupPagination();
      this.setupDocumentActions();
      this.setupModals();
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
    },

    /**
     * Carga documentos filtrados mediante AJAX
     * @param {number} page - Número de página para la paginación
     */
    loadFilteredDocuments: function (page) {
      // Mostrar indicador de carga
      $("#documents-list-content").html(
        '<div class="worker-portal-loading">' +
          '<div class="worker-portal-spinner"></div>' +
          "<p>Cargando documentos...</p>" +
          "</div>"
      );

      // Obtener datos del formulario
      const formData = new FormData($("#documents-filter-form")[0]);
      formData.append("action", "filter_documents");
      formData.append("nonce", workerPortalDocuments.nonce);
      formData.append("page", page);
      formData.append("per_page", 10); // Documentos por página

      // Realizar petición AJAX
      $.ajax({
        url: workerPortalDocuments.ajax_url,
        type: "POST",
        data: formData,
        processData: false,
        contentType: false,
        success: function (response) {
          if (response.success) {
            $("#documents-list-content").html(response.data.html);
          } else {
            $("#documents-list-content").html(
              '<p class="worker-portal-no-data">' + response.data + "</p>"
            );
          }
        },
        error: function () {
          $("#documents-list-content").html(
            '<p class="worker-portal-no-data">' +
              workerPortalDocuments.i18n.error +
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
          WorkerPortalDocuments.loadFilteredDocuments(page);
        }
      );
    },

    /**
     * Configurar acciones sobre documentos (descargar, ver)
     */
    setupDocumentActions: function () {
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
          nonce: workerPortalDocuments.nonce,
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
            alert(response.data);
          }
        },
        error: function () {
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
          nonce: workerPortalDocuments.nonce,
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
          $("#document-view-modal").fadeIn();
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
                nonce: workerPortalDocuments.nonce,
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
        error: function () {
          $("#document-modal-content").html(
            '<div class="worker-portal-error">' +
              workerPortalDocuments.i18n.error +
              "</div>"
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
    if (typeof workerPortalDocuments === "undefined") {
      console.error(
        "Error: No se encontraron las variables necesarias para el módulo de documentos"
      );
      return;
    }

    WorkerPortalDocuments.init();
  });
})(jQuery);
