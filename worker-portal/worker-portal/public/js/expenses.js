(function ($) {
  "use strict";

  // Objeto principal para el módulo de gastos en frontend
  const WorkerPortalPublicExpenses = {
    // Inicialización
    init: function () {
      this.setupFormToggle();
      this.setupReceiptUpload();
      this.setupFormSubmission();
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
  };

  // Inicializar cuando el DOM esté listo
  $(function () {
    WorkerPortalPublicExpenses.init();
  });
})(jQuery);
