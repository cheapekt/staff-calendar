/**
 * JavaScript para el panel de administración de WP Time Clock
 *
 * Maneja las interacciones del usuario con el panel de administración,
 * incluyendo la edición de entradas, la visualización de datos y más.
 */
(function($) {
    'use strict';

    // Variables globales
    let clockTimer = null;
    let elapsedTimers = [];
    
    /**
     * Inicializa el panel de administración
     */
    function initAdmin() {
        // Inicializar reloj
        updateAdminClock();
        
        // Actualizar tiempos transcurridos
        updateElapsedTimes();
        
        // Manejar modal de edición
        handleEditModal();
        
        // Manejar registro de salida
        handleClockOut();

        // Manejadores de pestañas para settings
        handleSettingsTabs();
    }
    
    /**
     * Actualiza el reloj del panel de administración
     */
    function updateAdminClock() {
        const $clock = $('#wp-time-clock-admin-time');
        
        if ($clock.length) {
            const update = function() {
                const now = new Date();
                const hours = String(now.getHours()).padStart(2, '0');
                const minutes = String(now.getMinutes()).padStart(2, '0');
                const seconds = String(now.getSeconds()).padStart(2, '0');
                
                $clock.text(hours + ':' + minutes + ':' + seconds);
            };
            
            // Actualizar inmediatamente
            update();
            
            // Actualizar cada segundo
            clockTimer = setInterval(update, 1000);
        }
    }
    
    /**
     * Actualiza los tiempos transcurridos en las celdas
     */
    function updateElapsedTimes() {
        const $cells = $('.wp-time-clock-elapsed-cell');
        
        if ($cells.length > 0) {
            const updateCells = function() {
                $cells.each(function() {
                    const $cell = $(this);
                    let seconds = parseInt($cell.attr('data-seconds')) + 1;
                    
                    // Actualizar atributo
                    $cell.attr('data-seconds', seconds);
                    
                    // Actualizar texto
                    $cell.text(formatTime(seconds));
                });
            };
            
            // Crear temporizador
            const timerId = setInterval(updateCells, 1000);
            elapsedTimers.push(timerId);
        }
    }
    
    /**
     * Maneja el modal de edición
     */
    function handleEditModal() {
        // Abrir modal
        $('.wp-time-clock-edit-entry').on('click', function() {
            const entryId = $(this).data('entry-id');
            const userId = $(this).data('user-id');
            const userName = $(this).data('user-name') || obtainUserName(userId);
            
            // Cargar datos en el modal
            $('#wp-time-clock-entry-id').val(entryId);
            $('#wp-time-clock-user-id').val(userId);
            $('#wp-time-clock-user-name').val(userName);
            
            // En una implementación real, cargaríamos más datos mediante AJAX
            loadEntryData(entryId);
            
            // Mostrar modal
            $('#wp-time-clock-edit-modal').fadeIn(200);
        });
        
        // Cerrar modal
        $('.wp-time-clock-modal-close, .wp-time-clock-modal-cancel').on('click', function() {
            $('#wp-time-clock-edit-modal').fadeOut(200);
        });
        
        // Al hacer clic fuera del modal, cerrarlo
        $(window).on('click', function(e) {
            if ($(e.target).is('.wp-time-clock-modal')) {
                $('.wp-time-clock-modal').fadeOut(200);
            }
        });
        
        // Manejar envío del formulario
        $('#wp-time-clock-edit-form').on('submit', function(e) {
            e.preventDefault();
            
            const entryId = $('#wp-time-clock-entry-id').val();
            const userId = $('#wp-time-clock-user-id').val();
            const clockIn = $('#wp-time-clock-clock-in').val();
            const clockOut = $('#wp-time-clock-clock-out').val();
            const status = $('#wp-time-clock-status').val();
            const note = $('#wp-time-clock-note').val();
            
            // En una implementación real, enviaríamos estos datos mediante AJAX
            saveEntryData(entryId, {
                user_id: userId,
                clock_in: clockIn,
                clock_out: clockOut,
                status: status,
                note: note
            });
            
            // Cerrar modal
            $('#wp-time-clock-edit-modal').fadeOut(200);
            
            // Mostrar mensaje
            showMessage(wpTimeClockAdmin.i18n.success, 'success');
        });
    }
    
    /**
     * Maneja el registro de salida
     */
    function handleClockOut() {
        $('.wp-time-clock-register-exit').on('click', function() {
            if (confirm(wpTimeClockAdmin.i18n.confirm_clockout)) {
                const entryId = $(this).data('entry-id');
                const userId = $(this).data('user-id');
                
                // En una implementación real, enviaríamos estos datos mediante AJAX
                registerClockOut(entryId, userId);
                
                // Mostrar mensaje
                showMessage('Salida registrada correctamente', 'success');
                
                // Recargar página después de un breve retraso
                setTimeout(function() {
                    location.reload();
                }, 1500);
            }
        });
    }
    
    /**
     * Maneja las pestañas de configuración
     */
    function handleSettingsTabs() {
        $('.wp-time-clock-tab').on('click', function(e) {
            e.preventDefault();
            
            const tab = $(this).data('tab');
            
            // Actualizar pestañas
            $('.wp-time-clock-tab').removeClass('active');
            $(this).addClass('active');
            
            // Actualizar contenido
            $('.wp-time-clock-tab-content').removeClass('active');
            $('#' + tab).addClass('active');
        });
    }
    
    /**
     * Carga los datos de una entrada mediante AJAX
     */
    function loadEntryData(entryId) {
        // En una implementación real, se cargarían los datos mediante AJAX
        // Por ahora, simulamos la carga con datos ficticios
        
        // Simular carga
        showMessage(wpTimeClockAdmin.i18n.loading, 'info');
        
        // Simular retraso de red
        setTimeout(function() {
            // Datos de ejemplo
            const now = new Date();
            const clockIn = now.toISOString().slice(0, 16); // Formato YYYY-MM-DDTHH:MM
            
            // Simular que ya existe una hora de salida
            const clockOut = new Date(now.getTime() + 8 * 60 * 60 * 1000).toISOString().slice(0, 16);
            
            // Establecer valores en el formulario
            $('#wp-time-clock-clock-in').val(clockIn);
            $('#wp-time-clock-clock-out').val(clockOut);
            $('#wp-time-clock-status').val('active');
            $('#wp-time-clock-note').val('');
            
            // Ocultar mensaje
            hideMessage();
        }, 500);
    }
    
    /**
     * Guarda los datos de una entrada mediante AJAX
     */
    function saveEntryData(entryId, data) {
        // En una implementación real, se enviarían los datos mediante AJAX
        console.log('Guardando datos para la entrada ' + entryId, data);
        
        // Simulación de guardado exitoso
        showMessage('Entrada actualizada correctamente', 'success');
        
        // Recargar página después de un breve retraso
        setTimeout(function() {
            location.reload();
        }, 1500);
    }
    
    /**
     * Registra la salida para una entrada mediante AJAX
     */
    function registerClockOut(entryId, userId) {
        // En una implementación real, se enviarían los datos mediante AJAX
        console.log('Registrando salida para la entrada ' + entryId + ' del usuario ' + userId);
        
        // API REST para utilizar en implementación real
        /*
        $.ajax({
            url: wpTimeClockAdmin.rest_url + '/clock-out',
            method: 'POST',
            beforeSend: function(xhr) {
                xhr.setRequestHeader('X-WP-Nonce', wpTimeClockAdmin.rest_nonce);
            },
            data: {
                entry_id: entryId,
                user_id: userId
            },
            success: function(response) {
                if (response.success) {
                    showMessage(response.message, 'success');
                    
                    // Recargar
                    setTimeout(function() {
                        location.reload();
                    }, 1500);
                } else {
                    showMessage(response.message, 'error');
                }
            },
            error: function() {
                showMessage('Error al registrar la salida', 'error');
            }
        });
        */
    }
    
    /**
     * Obtiene el nombre de un usuario mediante el ID
     */
    function obtainUserName(userId) {
        // En una implementación real, se obtendría del DOM o mediante AJAX
        return 'Usuario ' + userId;
    }
    
    /**
     * Muestra un mensaje al usuario
     */
    function showMessage(message, type = 'info') {
        const $container = $('.wrap.wp-time-clock-admin');
        
        // Si ya existe un mensaje, eliminarlo
        $('.wp-time-clock-message').remove();
        
        // Crear mensaje
        const $message = $('<div class="wp-time-clock-message wp-time-clock-message-' + type + '">' + message + '</div>');
        
        // Insertar al inicio del contenedor
        $container.prepend($message);
        
        // Hacer scroll para mostrar el mensaje
        $('html, body').animate({
            scrollTop: $message.offset().top - 50
        }, 300);
    }
    
    /**
     * Oculta los mensajes
     */
    function hideMessage() {
        $('.wp-time-clock-message').fadeOut(200, function() {
            $(this).remove();
        });
    }
    
    /**
     * Formatea un tiempo en segundos a formato HH:MM:SS
     */
    function formatTime(seconds) {
        const hours = Math.floor(seconds / 3600);
        const minutes = Math.floor((seconds % 3600) / 60);
        const secs = seconds % 60;
        
        return String(hours).padStart(2, '0') + ':' + 
               String(minutes).padStart(2, '0') + ':' + 
               String(secs).padStart(2, '0');
    }
    
    // Inicialización cuando el documento está listo
    $(document).ready(function() {
        initAdmin();
    });
    
    // Limpiar temporizadores al descargar la página
    $(window).on('beforeunload', function() {
        if (clockTimer) {
            clearInterval(clockTimer);
        }
        
        elapsedTimers.forEach(function(timerId) {
            clearInterval(timerId);
        });
    });

})(jQuery);
