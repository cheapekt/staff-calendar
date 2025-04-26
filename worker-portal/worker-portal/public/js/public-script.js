jQuery(document).ready(function ($) {
  // Navegación entre secciones del portal
  $(".worker-portal-navigation a").on("click", function (e) {
    e.preventDefault();

    // Ocultar todas las secciones
    $(".worker-portal-section").hide();

    // Remover clase activa de todos los botones
    $(".worker-portal-navigation a").removeClass("active");

    // Mostrar sección seleccionada
    const section = $(this).data("section");
    $(`#${section}-section`).show();

    // Cargar contenido dinámicamente si es necesario
    loadSectionContent(section);

    // Marcar botón como activo
    $(this).addClass("active");
  });

  // Función para cargar contenido de secciones
  function loadSectionContent(section) {
    const sectionElement = $(`#${section}-section`);

    // Si la sección está vacía, cargar contenido
    if (sectionElement.html().trim() === "") {
      $.ajax({
        url: worker_portal_params.ajax_url,
        type: "POST",
        data: {
          action: "load_portal_section",
          section: section,
          nonce: worker_portal_params.nonce,
        },
        success: function (response) {
          if (response.success) {
            sectionElement.html(response.data);
          } else {
            sectionElement.html("<p>Error al cargar el contenido.</p>");
          }
        },
        error: function () {
          sectionElement.html("<p>Error al cargar el contenido.</p>");
        },
      });
    }
  }

  // Mostrar sección de gastos por defecto
  $('.worker-portal-navigation a[data-section="expenses"]').click();
});
