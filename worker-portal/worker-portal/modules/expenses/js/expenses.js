/**
 * JavaScript para el módulo de gastos del Portal del Trabajador
 */
(function ($) {
  "use strict";

  // Objeto principal para el módulo de gastos
  const WorkerPortalExpenses = {
    // Inicialización
    init: function () {
      this.setupFormToggle();
      this.setupReceiptUpload();
      this.setupCameraModal();
      this.setupFormSubmission();
      this.setupExpenseActions();
    },

    // Configurar el toggle del formulario
    setupFormToggle: function () {
      $("#new-expense-button").on("click", function () {
        $(".worker-portal-expenses-form-container").slideToggle();
        $(this).toggleClass("active");

        if ($(this).hasClass("active")) {
          $(this).html('<i class="dashicons dashicons-minus"></i> Cancelar');
        } else {
          $(this).html(
            '<i class="dashicons dashicons-plus-alt"></i> Nuevo Gasto'
          );
        }
      });
    },

    // Configurar la subida de recibos
    setupReceiptUpload: function () {
      // Mostrar/ocultar el campo de subida de recibo
      $("#expense-has-receipt").on("change", function () {
        if ($(this).is(":checked")) {
          $("#receipt-upload-container").slideDown();
        } else {
          $("#receipt-upload-container").slideUp();
          $("#expense-receipt").val("");
          $("#receipt-preview").empty();
        }
      });

      // Previsualizar el recibo seleccionado
      $("#expense-receipt").on("change", function () {
        const file = this.files[0];
        if (file) {
          const reader = new FileReader();

          reader.onload = function (e) {
            const preview = $("#receipt-preview");
            preview.empty();

            if (file.type.match("image.*")) {
              $("<img>", {
                src: e.target.result,
                class: "worker-portal-receipt-image",
              }).appendTo(preview);
            } else {
              $("<div>", {
                class: "worker-portal-receipt-file",
                text: file.name,
              }).appendTo(preview);
            }
          };

          reader.readAsDataURL(file);
        }
      });
    },

    // Configurar el modal de la cámara
    setupCameraModal: function () {
      let stream = null;

      // Abrir modal de cámara
      $("#take-photo").on("click", function () {
        $("#camera-modal").fadeIn();
        startCamera();
      });

      // Cerrar modal de cámara
      $(".worker-portal-modal-close").on("click", function () {
        $("#camera-modal").fadeOut();
        stopCamera();
      });

      // Iniciar la cámara
      function startCamera() {
        const video = document.getElementById("camera-preview");

        if (navigator.mediaDevices && navigator.mediaDevices.getUserMedia) {
          navigator.mediaDevices
            .getUserMedia({ video: { facingMode: "environment" } })
            .then(function (s) {
              stream = s;
              video.srcObject = stream;
              $("#capture-photo").show();
              $("#retry-photo, #accept-photo").hide();
            })
            .catch(function (error) {
              console.error("Error al acceder a la cámara:", error);
              alert("No se pudo acceder a la cámara.");
              $("#camera-modal").fadeOut();
            });
        } else {
          alert("Tu dispositivo no soporta el acceso a la cámara.");
          $("#camera-modal").fadeOut();
        }
      }

      // Detener la cámara
      function stopCamera() {
        if (stream) {
          stream.getTracks().forEach((track) => track.stop());
          stream = null;
        }
      }

      // Capturar foto
      $("#capture-photo").on("click", function () {
        const video = document.getElementById("camera-preview");
        const canvas = document.getElementById("camera-capture");
        const context = canvas.getContext("2d");

        // Establecer las dimensiones del canvas iguales al video
        canvas.width = video.videoWidth;
        canvas.height = video.videoHeight;

        // Dibujar el fotograma actual del video en el canvas
        context.drawImage(video, 0, 0, canvas.width, canvas.height);

        // Mostrar botones de aceptar/reintentar
        $("#capture-photo").hide();
        $("#retry-photo, #accept-photo").show();
      });

      // Reintentar captura
      $("#retry-photo").on("click", function () {
        $("#capture-photo").show();
        $("#retry-photo, #accept-photo").hide();
      });

      // Aceptar foto
      $("#accept-photo").on("click", function () {
        const canvas = document.getElementById("camera-capture");

        // Convertir el canvas a un blob
        canvas.toBlob(
          function (blob) {
            // Crear un archivo a partir del blob
            const file = new File(
              [blob],
              "receipt-" + new Date().getTime() + ".jpg",
              { type: "image/jpeg" }
            );

            // Crear un objeto de transferencia de archivos
            const dataTransfer = new DataTransfer();
            dataTransfer.items.add(file);

            // Asignar el archivo al input de archivo
            document.getElementById("expense-receipt").files =
              dataTransfer.files;

            // Disparar el evento change para actualizar la previsualización
            $("#expense-receipt").trigger("change");

            // Cerrar el modal
            $("#camera-modal").fadeOut();
            stopCamera();
          },
          "image/jpeg",
          0.9
        );
      });

      // Cerrar el modal haciendo clic fuera o con ESC
      $(window).on("click", function (e) {
        if ($(e.target).is("#camera-modal")) {
          $("#camera-modal").fadeOut();
          stopCamera();
        }
      });

      $(document).on("keydown", function (e) {
        if (e.key === "Escape" && $("#camera-modal").is(":visible")) {
          $("#camera-modal").fadeOut();
          stopCamera();
        }
      });
    },

    // Configurar el envío del formulario
    setupFormSubmission: function () {
      $("#worker-portal-expense-form").on("submit", function (e) {
        e.preventDefault();

        const form = this;
        const formData = new FormData(form);

        // Validar campos obligatorios
        const expenseDate = formData.get("expense_date");
        const expenseType = formData.get("expense_type");
        const description = formData.get("description");
        const amount = formData.get("amount");

        if (
          !expenseDate ||
          !expenseType ||
          !description ||
          !amount ||
          amount <= 0
        ) {
          alert(
            "Por favor, completa todos los campos obligatorios correctamente."
          );
          return;
        }

        // Validar archivo si se ha seleccionado adjuntar recibo
        const hasReceipt = formData.get("has_receipt") === "yes";
        const receipt = document.getElementById("expense-receipt").files[0];

        if (hasReceipt && !receipt) {
          alert(
            "Has indicado que tienes un justificante, pero no has seleccionado ningún archivo."
          );
          return;
        }

        // Añadir nonce para seguridad
        formData.append("nonce", workerPortalExpenses.nonce);
        formData.append("action", "submit_expense");

        // Deshabilitar el botón de envío y mostrar indicador de carga
        const submitButton = $(form).find("button[type=submit]");
        submitButton
          .prop("disabled", true)
          .html(
            '<i class="dashicons dashicons-update-alt spinning"></i> Enviando...'
          );

        // Enviar los datos mediante AJAX
        $.ajax({
          url: workerPortalExpenses.ajax_url,
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
              $("#receipt-preview").empty();
              $("#receipt-upload-container").slideUp();

              // Recargar la página para mostrar el nuevo gasto
              window.location.reload();
            } else {
              // Mostrar mensaje de error
              alert(response.data);
            }
          },
          error: function () {
            alert(workerPortalExpenses.i18n.error);
          },
          complete: function () {
            // Restaurar el botón de envío
            submitButton.prop("disabled", false).html("Enviar Gasto");
          },
        });
      });
    },

    // Configurar acciones sobre los gastos
    setupExpenseActions: function () {
      // Eliminar gasto
      $(document).on("click", ".worker-portal-delete-expense", function () {
        if (confirm(workerPortalExpenses.i18n.confirm_delete)) {
          const expenseId = $(this).data("expense-id");

          $.ajax({
            url: workerPortalExpenses.ajax_url,
            type: "POST",
            data: {
              action: "delete_expense",
              nonce: workerPortalExpenses.nonce,
              expense_id: expenseId,
            },
            success: function (response) {
              if (response.success) {
                // Eliminar la fila de la tabla
                $(`tr[data-expense-id="${expenseId}"]`).fadeOut(function () {
                  $(this).remove();

                  // Si no quedan gastos, mostrar mensaje
                  if (
                    $(".worker-portal-expenses-table tbody tr").length === 0
                  ) {
                    $(".worker-portal-table-responsive").html(
                      '<p class="worker-portal-no-data">No hay gastos registrados.</p>'
                    );
                  }
                });
              } else {
                alert(response.data);
              }
            },
            error: function () {
              alert(workerPortalExpenses.i18n.error);
            },
          });
        }
      });

      // Ver recibo (maximizar en ventana modal)
      $(document).on("click", ".worker-portal-view-receipt", function (e) {
        e.preventDefault();

        const receiptUrl = $(this).attr("href");

        // Crear un modal para mostrar el recibo
        const modal = $(`
                    <div class="worker-portal-modal">
                        <div class="worker-portal-modal-content" style="max-width: 80%; margin: 5% auto;">
                            <div class="worker-portal-modal-header">
                                <h3>Justificante</h3>
                                <button type="button" class="worker-portal-modal-close">&times;</button>
                            </div>
                            <div class="worker-portal-modal-body" style="text-align: center;">
                                <img src="${receiptUrl}" style="max-width: 100%; max-height: 80vh;">
                            </div>
                        </div>
                    </div>
                `);

        // Añadir el modal al body y mostrarlo
        $("body").append(modal);
        modal.fadeIn();

        // Evento para cerrar el modal
        modal.find(".worker-portal-modal-close").on("click", function () {
          modal.fadeOut(function () {
            modal.remove();
          });
        });

        // Cerrar haciendo clic fuera o con ESC
        modal.on("click", function (e) {
          if ($(e.target).is(modal)) {
            modal.fadeOut(function () {
              modal.remove();
            });
          }
        });

        $(document).on("keydown.receipt-modal", function (e) {
          if (e.key === "Escape") {
            modal.fadeOut(function () {
              modal.remove();
              $(document).off("keydown.receipt-modal");
            });
          }
        });
      });
    },
  };

  // Inicializar cuando el DOM esté listo
  $(function () {
    WorkerPortalExpenses.init();
  });
})(jQuery);
