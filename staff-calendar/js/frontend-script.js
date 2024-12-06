jQuery(document).ready(function ($) {
  let currentMonth = new Date().getMonth() + 1;
  let currentYear = new Date().getFullYear();
  let isLoading = false;

  function showMessage(message, type = "info") {
    const messageContainer = $("#calendar-messages");
    messageContainer
      .html(
        `<div class="status-message message-${type}">
                    <span class="message-text">${message}</span>
                    <button class="close-message">&times;</button>
                </div>`
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
    const headerRow = $(".calendar-table thead tr");
    headerRow.find("th:not(:first)").remove();

    for (let day = 1; day <= daysInMonth; day++) {
      const date = new Date(currentYear, currentMonth - 1, day);
      const isWeekend = date.getDay() === 0 || date.getDay() === 6;
      headerRow.append(`<th class="${isWeekend ? "weekend" : ""}">${day}</th>`);
    }

    // Actualizar celdas de usuarios
    $(".calendar-table tbody tr").each(function () {
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
    $(".calendar-table tbody tr").each(function () {
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
        const vehicle = dayData ? dayData.vehicle : "";
        const modificationCount = dayData
          ? parseInt(dayData.modification_count)
          : 0;
        const cell = $(this).find(`td[data-day="${day}"]`);

        let cellContent = "";
        if (destination) {
          cellContent += `<div class="cell-destination">${destination}</div>`;
        }
        if (vehicle) {
          // Primero cargar los datos del vehículo
          $.ajax({
            url: staffCalendarConfig.ajax_url,
            type: "GET",
            async: false, // Importante para mantener el orden
            data: {
              action: "get_vehicles",
              nonce: staffCalendarConfig.nonce,
            },
            success: function (response) {
              if (response.success) {
                const vehicleData = response.data.find(
                  (v) => v.name === vehicle
                );
                if (vehicleData) {
                  const plate = vehicleData.plate
                    ? ` (${vehicleData.plate})`
                    : "";
                  cellContent += `<div class="cell-vehicle">${vehicle}${plate}</div>`;
                } else {
                  cellContent += `<div class="cell-vehicle">${vehicle}</div>`;
                }
              }
            },
          });
        }

        cell
          .empty()
          .addClass("destination-cell")
          .attr({
            "data-destination": destination,
            "data-vehicle": vehicle,
            "data-modification-count": modificationCount,
            "data-user-id": userId,
          })
          .toggleClass("has-destination", !!(destination || vehicle))
          .toggleClass("has-modifications", modificationCount > 0)
          .toggleClass("weekend", isWeekend)
          .html(cellContent);
      }
    });
  }

  function loadVehiclesForSelect() {
    $.ajax({
      url: staffCalendarConfig.ajax_url,
      type: "GET",
      data: {
        action: "get_vehicles",
        nonce: staffCalendarConfig.nonce,
      },
      success: function (response) {
        if (response.success) {
          const select = $("#modal-vehicle");
          select.empty();
          select.append('<option value="">Seleccionar vehículo</option>');

          response.data.forEach(function (vehicle) {
            if (vehicle.status === "active") {
              select.append(
                `<option value="${vehicle.name}">${vehicle.name} (${
                  vehicle.plate || "Sin matrícula"
                })</option>`
              );
            }
          });
        }
      },
    });
  }

  function openModal(cell) {
    const userId = cell.data("user-id");
    const row = cell.closest("tr");
    const userName = row.find(".user-name").text();
    const day = cell.data("day");
    const date = new Date(currentYear, currentMonth - 1, day);

    const formattedDisplayDate = date.toLocaleDateString("es-ES", {
      weekday: "long",
      year: "numeric",
      month: "long",
      day: "numeric",
    });

    const formattedInputDate = `${currentYear}-${String(currentMonth).padStart(
      2,
      "0"
    )}-${String(day).padStart(2, "0")}`;

    const destination = cell.attr("data-destination") || "";
    const vehicle = cell.attr("data-vehicle") || "";
    const modificationCount =
      parseInt(cell.attr("data-modification-count")) || 0;

    $("#destination-modal .modal-user").text(userName);
    $("#destination-modal .modal-date").text(formattedDisplayDate);

    // Manejo del indicador de modificaciones
    if (modificationCount > 0) {
      const modificationText = `Este registro ha sido modificado ${modificationCount} ${
        modificationCount === 1 ? "vez" : "veces"
      }`;
      $(".modification-indicator")
        .show()
        .find(".message-text")
        .text(modificationText);
    } else {
      $(".modification-indicator").hide();
    }

    if (staffCalendarConfig.isAdmin) {
      loadVehiclesForSelect();
      $("#modal-destination").val(destination);
      $("#modal-vehicle").val(vehicle);
      $("#modal-start-date").val(formattedInputDate);
      $("#modal-end-date").val(formattedInputDate);
      $("#modal-destination").data("user-id", userId);
    } else {
      $(".modal-destination-text").text(destination || "Sin destino asignado");
      $(".modal-vehicle-text").text(vehicle || "Sin vehículo asignado");
    }

    $("#destination-modal").fadeIn(200);
  }

  // Manejadores de eventos del calendario
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

  $(document).on("click", ".calendar-table .destination-cell", function () {
    openModal($(this));
  });

  $(".modal-close, .modal-cancel").click(function () {
    $(this).closest(".destination-modal, .vehicle-modal").fadeOut(200);
  });

  $(window).click(function (e) {
    if ($(e.target).is(".destination-modal, .vehicle-modal")) {
      $(e.target).fadeOut(200);
    }
  });

  // Gestión de vehículos
  function loadVehicles() {
    $.ajax({
      url: staffCalendarConfig.ajax_url,
      type: "GET",
      data: {
        action: "get_vehicles",
        nonce: staffCalendarConfig.nonce,
      },
      success: function (response) {
        if (response.success) {
          updateVehiclesList(response.data);
        }
      },
    });
  }

  function updateVehiclesList(vehicles) {
    const tbody = $("#vehicles-list-body");
    tbody.empty();

    vehicles.forEach(function (vehicle) {
      const statusText = {
        active: "Activo",
        maintenance: "En Mantenimiento",
        inactive: "Inactivo",
      }[vehicle.status];

      const statusClass = {
        active: "status-active",
        maintenance: "status-maintenance",
        inactive: "status-inactive",
      }[vehicle.status];

      const row = `
                <tr>
                    <td>${vehicle.name}</td>
                    <td>${vehicle.plate || "-"}</td>
                    <td><span class="status-badge ${statusClass}">${statusText}</span></td>
                    <td>
                        <button class="button edit-vehicle" data-id="${
                          vehicle.id
                        }" 
                            data-name="${vehicle.name}" 
                            data-plate="${vehicle.plate || ""}" 
                            data-status="${vehicle.status}">
                            Editar
                        </button>
                        <button class="button delete-vehicle" data-id="${
                          vehicle.id
                        }">
                            Eliminar
                        </button>
                    </td>
                </tr>
            `;
      tbody.append(row);
    });
  }

  // Manejo del formulario de nuevo vehículo
  $("#new-vehicle-form").on("submit", function (e) {
    e.preventDefault();

    const name = $("#vehicle-name").val();
    const plate = $("#vehicle-plate").val();
    const status = $("#vehicle-status").val();

    $.ajax({
      url: staffCalendarConfig.ajax_url,
      type: "POST",
      data: {
        action: "add_vehicle",
        nonce: staffCalendarConfig.nonce,
        name: name,
        plate: plate,
        status: status,
      },
      success: function (response) {
        if (response.success) {
          $("#new-vehicle-form")[0].reset();
          loadVehicles();
          showMessage("Vehículo añadido correctamente", "success");
        } else {
          showMessage("Error al añadir el vehículo", "error");
        }
      },
    });
  });

  // Manejo de la edición de vehículo
  $(document).on("click", ".edit-vehicle", function () {
    const button = $(this);
    const id = button.data("id");
    const name = button.data("name");
    const plate = button.data("plate");
    const status = button.data("status");

    $("#edit-vehicle-id").val(id);
    $("#edit-vehicle-name").val(name);
    $("#edit-vehicle-plate").val(plate);
    $("#edit-vehicle-status").val(status);

    $("#edit-vehicle-modal").fadeIn(200);
  });

  $("#edit-vehicle-form").on("submit", function (e) {
    e.preventDefault();

    const id = $("#edit-vehicle-id").val();
    const name = $("#edit-vehicle-name").val();
    const plate = $("#edit-vehicle-plate").val();
    const status = $("#edit-vehicle-status").val();

    $.ajax({
      url: staffCalendarConfig.ajax_url,
      type: "POST",
      data: {
        action: "edit_vehicle",
        nonce: staffCalendarConfig.nonce,
        id: id,
        name: name,
        plate: plate,
        status: status,
      },
      success: function (response) {
        if (response.success) {
          $("#edit-vehicle-modal").fadeOut(200);
          loadVehicles();
          showMessage("Vehículo actualizado correctamente", "success");
        } else {
          showMessage("Error al actualizar el vehículo", "error");
        }
      },
    });
  });

  // Manejo de la eliminación de vehículo
  $(document).on("click", ".delete-vehicle", function () {
    if (!confirm("¿Estás seguro de que quieres eliminar este vehículo?")) {
      return;
    }

    const id = $(this).data("id");

    $.ajax({
      url: staffCalendarConfig.ajax_url,
      type: "POST",
      data: {
        action: "delete_vehicle",
        nonce: staffCalendarConfig.nonce,
        id: id,
      },
      success: function (response) {
        if (response.success) {
          loadVehicles();
          showMessage("Vehículo eliminado correctamente", "success");
        } else {
          showMessage("Error al eliminar el vehículo", "error");
        }
      },
    });
  });

  if (staffCalendarConfig.isAdmin) {
    $(".modal-save").click(function () {
      const userId = $("#modal-destination").data("user-id");
      const startDate = $("#modal-start-date").val();
      const endDate = $("#modal-end-date").val();
      const destination = $("#modal-destination").val();
      const vehicle = $("#modal-vehicle").val();

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
          vehicle: vehicle,
        },
        success: function (response) {
          if (response.success) {
            loadCalendarData();
            $("#destination-modal").fadeOut(200);
            showMessage("Datos actualizados correctamente", "info");
          } else {
            showMessage("Error al guardar los datos", "error");
          }
        },
        error: function (xhr, status, error) {
          console.error("Error en la petición:", { xhr, status, error });
          showMessage("Error de conexión", "error");
        },
      });
    });
  }

  $(document).on("click", ".close-message", function () {
    $(this).closest(".status-message").parent().fadeOut();
  });

  // Inicializar el calendario si está presente
  if ($(".staff-calendar-frontend").length) {
    updateCalendar();
  }

  // Inicializar la lista de vehículos si está presente
  if ($(".staff-vehicles-manager").length) {
    loadVehicles();
  }

  // Manejadores de eventos compartidos
  $(document).on("click", ".close-message", function () {
    $(this).closest(".status-message").parent().fadeOut();
  });

  $(window).click(function (e) {
    if ($(e.target).is(".vehicle-modal, .destination-modal")) {
      $(".vehicle-modal, .destination-modal").fadeOut(200);
    }
  });

  $(document).keydown(function (e) {
    if (e.key === "Escape") {
      $(".vehicle-modal, .destination-modal").fadeOut(200);
    }
  });
});
