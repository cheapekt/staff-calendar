jQuery(document).ready(function ($) {
  let currentMonth = new Date().getMonth() + 1;
  let currentYear = new Date().getFullYear();
  let isLoading = false;

  function showMessage(message, type = "info") {
    const messageContainer = $("#calendar-messages");
    messageContainer
      .html(
        `
            <div class="status-message message-${type}">
                <span class="message-text">${message}</span>
                <button class="close-message">&times;</button>
            </div>
        `
      )
      .show();

    setTimeout(() => messageContainer.fadeOut(), 3000);
  }

  function updateCalendar() {
    if (isLoading) return;
    isLoading = true;

    const daysInMonth = new Date(currentYear, currentMonth, 0).getDate();

    // Actualizar encabezado del mes
    $(".current-month").text(
      new Date(currentYear, currentMonth - 1).toLocaleDateString("es-ES", {
        month: "long",
        year: "numeric",
      })
    );

    // Actualizar encabezados de días
    const headerRow = $("thead tr");
    headerRow.find("th:not(:first)").remove();

    for (let day = 1; day <= daysInMonth; day++) {
      const date = new Date(currentYear, currentMonth - 1, day);
      const isWeekend = date.getDay() === 0 || date.getDay() === 6;
      headerRow.append(`<th class="${isWeekend ? "weekend" : ""}">${day}</th>`);
    }

    // Actualizar celdas de usuarios
    $("tbody tr").each(function () {
      $(this).find("td:not(:first)").remove();
      for (let day = 1; day <= daysInMonth; day++) {
        $(this).append(`<td class="destination-cell" data-day="${day}"></td>`);
      }
    });

    loadCalendarData();
  }

  function loadCalendarData() {
    $(".calendar-loading").show();

    $.ajax({
      url: staffCalendarConfig.ajax_url,
      type: "GET",
      data: {
        action: "get_calendar_data",
        nonce: staffCalendarConfig.nonce,
        month: currentMonth,
        year: currentYear,
      },
      success: function (response) {
        if (response.success) {
          updateCalendarCells(response.data);
        } else {
          showMessage(staffCalendarConfig.translations.error, "error");
        }
      },
      error: function (xhr, status, error) {
        console.error("Error al cargar datos:", { xhr, status, error });
        showMessage("Error al cargar los datos del calendario", "error");
      },
      complete: function () {
        isLoading = false;
        $(".calendar-loading").hide();
      },
    });
  }

  function updateCalendarCells(data) {
    $("tbody tr").each(function () {
      const userId = $(this).data("user-id");

      for (
        let day = 1;
        day <= new Date(currentYear, currentMonth, 0).getDate();
        day++
      ) {
        const date = new Date(currentYear, currentMonth - 1, day);
        const isWeekend = date.getDay() === 0 || date.getDay() === 6;
        const formattedDate = `${currentYear}-${String(currentMonth).padStart(
          2,
          "0"
        )}-${String(day).padStart(2, "0")}`;

        const dayData = data.find(
          (entry) =>
            entry.user_id == userId && entry.work_date === formattedDate
        );

        const destination = dayData ? dayData.destination : "";
        const cell = $(this).find(`td[data-day="${day}"]`);

        cell
          .empty()
          .addClass("destination-cell")
          .attr("data-destination", destination)
          .toggleClass("has-destination", !!destination)
          .toggleClass("weekend", isWeekend)
          .text(destination || "");
      }
    });
  }

  $(".prev-month").click(function () {
    if (currentMonth === 1) {
      currentMonth = 12;
      currentYear--;
    } else {
      currentMonth--;
    }
    updateCalendar();
  });

  $(".next-month").click(function () {
    if (currentMonth === 12) {
      currentMonth = 1;
      currentYear++;
    } else {
      currentMonth++;
    }
    updateCalendar();
  });

  $(document).on("click", ".close-message", function () {
    $(this).closest(".status-message").parent().fadeOut();
  });

  function openModal(cell) {
    const row = cell.closest("tr");
    const userId = row.data("user-id");
    const userName = row.find(".user-name").text();
    const day = cell.data("day");
    const date = new Date(currentYear, currentMonth - 1, day);

    // Formato para mostrar
    const formattedDisplayDate = date.toLocaleDateString("es-ES", {
      weekday: "long",
      year: "numeric",
      month: "long",
      day: "numeric",
    });

    // Formato para el input date
    const formattedInputDate = `${currentYear}-${String(currentMonth).padStart(
      2,
      "0"
    )}-${String(day).padStart(2, "0")}`;

    const destination = cell.attr("data-destination") || "";

    $("#destination-modal .modal-user").text(userName);
    $("#destination-modal .modal-date").text(formattedDisplayDate);

    if (staffCalendarConfig.isAdmin) {
      $("#modal-destination").val(destination);
      $("#modal-start-date").val(formattedInputDate);
      $("#modal-end-date").val(formattedInputDate);

      $("#modal-destination")
        .data("user-id", userId)
        .data("date", formattedInputDate);
    } else {
      $(".modal-destination-text").text(destination || "Sin destino asignado");
    }

    $("#destination-modal").fadeIn(200);
  }

  // Event handlers para el modal
  $(document).on("click", ".destination-cell", function () {
    openModal($(this));
  });

  $(".modal-close, .modal-cancel").click(function () {
    $("#destination-modal").fadeOut(200);
  });

  // Cerrar modal al hacer clic fuera
  $(window).click(function (e) {
    if ($(e.target).is(".destination-modal")) {
      $("#destination-modal").fadeOut(200);
    }
  });

  if (staffCalendarConfig.isAdmin) {
    $(".modal-save").click(function () {
      const input = $("#modal-destination");
      const userId = input.data("user-id");
      const startDate = $("#modal-start-date").val();
      const endDate = $("#modal-end-date").val();
      const destination = input.val();

      $.ajax({
        url: staffCalendarConfig.ajax_url,
        type: "POST",
        data: {
          action: "update_staff_destination_range",
          nonce: staffCalendarConfig.nonce,
          user_id: userId,
          start_date: startDate,
          end_date: endDate,
          destination: destination,
        },
        success: function (response) {
          if (response.success) {
            loadCalendarData();
            $("#destination-modal").fadeOut(200);
            showMessage("Destinos actualizados correctamente", "info");
          } else {
            showMessage("Error al guardar los destinos", "error");
          }
        },
        error: function (xhr, status, error) {
          console.error("Error en la petición:", { xhr, status, error });
          showMessage("Error de conexión", "error");
        },
      });
    });
  }

  updateCalendar();
});
