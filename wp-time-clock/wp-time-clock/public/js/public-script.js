/**
 * JavaScript para la funcionalidad frontend del plugin
 * 
 * Maneja las interacciones del usuario con el botón de fichaje,
 * la geolocalización y actualización en tiempo real.
 */
(function($) {
    'use strict';

    // Variables globales
    let clockTimer = null;
    let elapsedTimer = null;
    let isProcessing = false;
    
    // Al cargar el documento
    $(document).ready(function() {
        initClockButton();
        updateCurrentTime();
        updateElapsedTime();
    });

    /**
     * Inicializa el botón de fichaje
     */
    function initClockButton() {
        // Click en el botón principal de fichaje
        $('.wp-time-clock-button').on('click', function(e) {
            e.preventDefault();
            
            // Evitar doble clic
            if (isProcessing) return;
            
            const $button = $(this);
            const action = $button.data('action');
            const $container = $button.closest('.wp-time-clock-container');
            
            // Verificar si se requiere nota
            const allowNote = true; // Esto podría venir de las configuraciones
            
            if (allowNote) {
                // Mostrar el contenedor de nota
                $container.find('.wp-time-clock-button').hide();
                $container.find('.wp-time-clock-note-container').show();
                
                // Enfocar el textarea
                $container.find('.wp-time-clock-note').focus();
            } else {
                // Procesar directamente sin nota
                processClockAction(action, '', $container);
            }
        });
        
        // Click en "Guardar" de la nota
        $('.wp-time-clock-submit').on('click', function(e) {
            e.preventDefault();
            
            const $container = $(this).closest('.wp-time-clock-container');
            const action = $container.find('.wp-time-clock-button').data('action');
            const note = $container.find('.wp-time-clock-note').val();
            
            processClockAction(action, note, $container);
        });
        
        // Click en "Cancelar" de la nota
        $('.wp-time-clock-cancel').on('click', function(e) {
            e.preventDefault();
            
            const $container = $(this).closest('.wp-time-clock-container');
            
            // Ocultar el contenedor de nota y mostrar el botón
            $container.find('.wp-time-clock-note-container').hide();
            $container.find('.wp-time-clock-button').show();
            
            // Limpiar el textarea
            $container.find('.wp-time-clock-note').val('');
        });
    }
    
    /**
     * Procesa la acción de fichaje (entrada o salida)
     */
    function processClockAction(action, note, $container) {
        if (isProcessing) return;
        isProcessing = true;
        
        // Mensaje de carga
        showMessage($container, wpTimeClock.i18n.loading, 'info');
        
        // Si la geolocalización está activada
        if (wpTimeClock.geolocation_enabled && navigator.geolocation) {
            showMessage($container, wpTimeClock.i18n.location_wait, 'info');
            
            navigator.geolocation.getCurrentPosition(
                // Éxito
                function(position) {
                    const locationData = {
                        latitude: position.coords.latitude,
                        longitude: position.coords.longitude,
                        accuracy: position.coords.accuracy
                    };
                    
                    // Convertir a JSON
                    const locationJson = JSON.stringify(locationData);
                    
                    // Enviar solicitud con ubicación
                    sendClockRequest(action, note, locationJson, $container);
                },
                // Error
                function(error) {
                    console.error('Error de geolocalización:', error);
                    
                    // Preguntar si desea continuar sin ubicación
                    if (confirm(wpTimeClock.i18n.location_error + ' ¿Deseas continuar sin registrar tu ubicación?')) {
                        sendClockRequest(action, note, '', $container);
                    } else {
                        isProcessing = false;
                        $container.find('.wp-time-clock-note-container').hide();
                        $container.find('.wp-time-clock-button').show();
                        showMessage($container, '', '');
                    }
                },
                // Opciones
                {
                    enableHighAccuracy: true,
                    timeout: 10000,
                    maximumAge: 0
                }
            );
        } else {
            // Sin geolocalización
            sendClockRequest(action, note, '', $container);
        }
    }
    
    /**
     * Envía la solicitud de fichaje al servidor
     */
    function sendClockRequest(action, note, location, $container) {
        const endpoint = (action === 'clock_in') ? 'clock-in' : 'clock-out';
        
        $.ajax({
            url: wpTimeClock.rest_url + '/' + endpoint,
            method: 'POST',
            beforeSend: function(xhr) {
                xhr.setRequestHeader('X-WP-Nonce', wpTimeClock.nonce);
            },
            data: {
                note: note,
                location: location
            },
            success: function(response) {
                isProcessing = false;
                
                if (response.success) {
                    // Actualizar UI
                    updateUIAfterClock(action, response, $container);
                    showMessage($container, response.message, 'success');
                    
                    // Limpiar el textarea y ocultar el contenedor de notas
                    $container.find('.wp-time-clock-note').val('');
                    $container.find('.wp-time-clock-note-container').hide();
                    $container.find('.wp-time-clock-button').show();
                } else {
                    showMessage($container, response.message || wpTimeClock.i18n.error, 'error');
                    $container.find('.wp-time-clock-note-container').hide();
                    $container.find('.wp-time-clock-button').show();
                }
            },
            error: function(xhr, status, error) {
                isProcessing = false;
                console.error('Error en la solicitud:', error);
                
                let errorMessage = wpTimeClock.i18n.error;
                
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMessage = xhr.responseJSON.message;
                }
                
                showMessage($container, errorMessage, 'error');
                $container.find('.wp-time-clock-note-container').hide();
                $container.find('.wp-time-clock-button').show();
            }
        });
    }
    
    /**
     * Actualiza la interfaz después de un fichaje exitoso
     */
    function updateUIAfterClock(action, response, $container) {
        const $button = $container.find('.wp-time-clock-button');
        const $status = $container.find('.wp-time-clock-status-value');
        const $elapsed = $container.find('.wp-time-clock-elapsed');
        
        if (action === 'clock_in') {
            // Cambiar botón a "Fichar Salida"
            $button.text($button.text().replace(/Entrada/i, 'Salida'));
            $button.data('action', 'clock_out');
            $button.removeClass('wp-time-clock-button-in').addClass('wp-time-clock-button-out');
            
            // Actualizar estado
            if ($status.length) {
                $status.text(wpTimeClock.i18n.working);
            }
            
            // Mostrar tiempo transcurrido
            if ($elapsed.length) {
                $elapsed.show();
                $elapsed.find('.wp-time-clock-elapsed-value')
                    .data('since', response.clock_time)
                    .data('seconds', 0)
                    .text('00:00:00');
                
                // Iniciar temporizador
                startElapsedTimer();
            }
            
            // Actualizar el atributo data-status del contenedor
            $container.attr('data-status', 'clocked_in');
        } else {
            // Cambiar botón a "Fichar Entrada"
            $button.text($button.text().replace(/Salida/i, 'Entrada'));
            $button.data('action', 'clock_in');
            $button.removeClass('wp-time-clock-button-out').addClass('wp-time-clock-button-in');
            
            // Actualizar estado
            if ($status.length) {
                $status.text(wpTimeClock.i18n.not_working);
            }
            
            // Ocultar tiempo transcurrido
            if ($elapsed.length) {
                $elapsed.hide();
                
                // Detener temporizador
                stopElapsedTimer();
            }
            
            // Actualizar el atributo data-status del contenedor
            $container.attr('data-status', 'clocked_out');
            
            // Si hay un mensaje de tiempo trabajado, mostrarlo
            if (response.time_worked && response.time_worked.formatted) {
                showMessage($container, 'Has trabajado ' + response.time_worked.formatted, 'info', 5000);
            }
        }
    }
    
    /**
     * Muestra un mensaje en el contenedor
     */
    function showMessage($container, message, type = 'info', duration = 3000) {
        const $message = $container.find('.wp-time-clock-message');
        
        // Limpiar cualquier clase de tipo anterior
        $message.removeClass('wp-time-clock-message-error wp-time-clock-message-success wp-time-clock-message-info');
        
        if (message === '') {
            $message.html('').hide();
            return;
        }
        
        // Aplicar la clase de tipo
        $message.addClass('wp-time-clock-message-' + type);
        
        // Mostrar el mensaje
        $message.html(message).fadeIn();
        
        // Auto-ocultar después de la duración (si no es 0)
        if (duration > 0) {
            setTimeout(function() {
                $message.fadeOut();
            }, duration);
        }
    }
    
    /**
     * Actualiza el reloj en tiempo real
     */
    function updateCurrentTime() {
        const $clock = $('#wp-time-clock-current-time');
        
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
     * Actualiza el tiempo transcurrido desde el fichaje
     */
    function updateElapsedTime() {
        const $elapsed = $('.wp-time-clock-elapsed-value');
        
        if ($elapsed.length) {
            const update = function() {
                $elapsed.each(function() {
                    const $this = $(this);
                    
                    // Si tenemos la fecha de inicio
                    if ($this.data('since')) {
                        let seconds = parseInt($this.data('seconds')) || 0;
                        seconds++;
                        
                        // Formatear tiempo
                        const hours = Math.floor(seconds / 3600);
                        const minutes = Math.floor((seconds % 3600) / 60);
                        const secs = seconds % 60;
                        
                        const formatted = 
                            String(hours).padStart(2, '0') + ':' +
                            String(minutes).padStart(2, '0') + ':' +
                            String(secs).padStart(2, '0');
                        
                        $this.text(formatted);
                        $this.data('seconds', seconds);
                    }
                });
            };
            
            // Iniciar temporizador si hay elementos activos
            startElapsedTimer();
        }
    }
    
    /**
     * Inicia el temporizador para tiempo transcurrido
     */
    function startElapsedTimer() {
        if (!elapsedTimer) {
            elapsedTimer = setInterval(function() {
                const $elapsed = $('.wp-time-clock-elapsed-value');
                
                if ($elapsed.length) {
                    $elapsed.each(function() {
                        const $this = $(this);
                        
                        // Si tenemos la fecha de inicio
                        if ($this.data('since')) {
                            let seconds = parseInt($this.data('seconds')) || 0;
                            seconds++;
                            
                            // Formatear tiempo
                            const hours = Math.floor(seconds / 3600);
                            const minutes = Math.floor((seconds % 3600) / 60);
                            const secs = seconds % 60;
                            
                            const formatted = 
                                String(hours).padStart(2, '0') + ':' +
                                String(minutes).padStart(2, '0') + ':' +
                                String(secs).padStart(2, '0');
                            
                            $this.text(formatted);
                            $this.data('seconds', seconds);
                        }
                    });
                } else {
                    // Si no hay elementos, detener el temporizador
                    stopElapsedTimer();
                }
            }, 1000);
        }
    }
    
    /**
     * Detiene el temporizador para tiempo transcurrido
     */
    function stopElapsedTimer() {
        if (elapsedTimer) {
            clearInterval(elapsedTimer);
            elapsedTimer = null;
        }
    }
    
    // Limpiar temporizadores al descargar la página
    $(window).on('beforeunload', function() {
        if (clockTimer) clearInterval(clockTimer);
        if (elapsedTimer) clearInterval(elapsedTimer);
    });

})(jQuery);
