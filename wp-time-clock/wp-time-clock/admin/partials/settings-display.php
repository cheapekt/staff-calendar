<?php
/**
 * Plantilla para mostrar la página de configuración
 *
 * @since      1.0.0
 */

// Si se accede directamente, salir
if (!defined('ABSPATH')) {
    exit;
}

// Inicializar gestor de fichajes
$clock_manager = new WP_Time_Clock_Manager();

// Obtener configuraciones actuales
$settings = array(
    'working_hours_per_day' => $clock_manager->get_setting('working_hours_per_day', '8'),
    'allow_manual_entry' => $clock_manager->get_setting('allow_manual_entry', 'yes'),
    'require_approval' => $clock_manager->get_setting('require_approval', 'no'),
    'geolocation_enabled' => $clock_manager->get_setting('geolocation_enabled', 'yes'),
    'clock_button_style' => $clock_manager->get_setting('clock_button_style', 'default'),
    'notification_emails' => $clock_manager->get_setting('notification_emails', get_option('admin_email')),
    'allow_clock_note' => $clock_manager->get_setting('allow_clock_note', 'yes'),
    'display_clock_time' => $clock_manager->get_setting('display_clock_time', 'yes'),
    'enable_breaks' => $clock_manager->get_setting('enable_breaks', 'no'),
    'auto_clock_out' => $clock_manager->get_setting('auto_clock_out', 'no'),
    'auto_clock_out_time' => $clock_manager->get_setting('auto_clock_out_time', '23:59:59'),
    'weekend_days' => $clock_manager->get_setting('weekend_days', '0,6'), // 0 (domingo) y 6 (sábado)
    'workday_start' => $clock_manager->get_setting('workday_start', '09:00:00'),
    'workday_end' => $clock_manager->get_setting('workday_end', '18:00:00')
);

// Si se ha enviado el formulario, procesar y guardar cambios
if (isset($_POST['wp_time_clock_settings']) && check_admin_referer('wp_time_clock_settings_nonce')) {
    
    // Obtener valores
    $new_settings = array(
        'working_hours_per_day' => sanitize_text_field($_POST['working_hours_per_day']),
        'allow_manual_entry' => isset($_POST['allow_manual_entry']) ? 'yes' : 'no',
        'require_approval' => isset($_POST['require_approval']) ? 'yes' : 'no',
        'geolocation_enabled' => isset($_POST['geolocation_enabled']) ? 'yes' : 'no',
        'clock_button_style' => sanitize_text_field($_POST['clock_button_style']),
        'notification_emails' => sanitize_email($_POST['notification_emails']),
        'allow_clock_note' => isset($_POST['allow_clock_note']) ? 'yes' : 'no',
        'display_clock_time' => isset($_POST['display_clock_time']) ? 'yes' : 'no',
        'enable_breaks' => isset($_POST['enable_breaks']) ? 'yes' : 'no',
        'auto_clock_out' => isset($_POST['auto_clock_out']) ? 'yes' : 'no',
        'auto_clock_out_time' => sanitize_text_field($_POST['auto_clock_out_time']),
        'weekend_days' => isset($_POST['weekend_days']) ? implode(',', $_POST['weekend_days']) : '',
        'workday_start' => sanitize_text_field($_POST['workday_start']),
        'workday_end' => sanitize_text_field($_POST['workday_end'])
    );
    
    // Guardar cada configuración
    $updated = 0;
    foreach ($new_settings as $key => $value) {
        if ($clock_manager->save_setting($key, $value)) {
            $updated++;
        }
    }
    
    // Actualizar settings para mostrar los valores guardados
    $settings = $new_settings;
    
    // Mensaje de éxito
    if ($updated > 0) {
        echo '<div class="notice notice-success is-dismissible"><p>' . 
            sprintf(__('Configuración guardada correctamente. %d valores actualizados.', 'wp-time-clock'), $updated) . 
            '</p></div>';
    }
}

// Array con los días de la semana
$weekdays = array(
    0 => __('Domingo', 'wp-time-clock'),
    1 => __('Lunes', 'wp-time-clock'),
    2 => __('Martes', 'wp-time-clock'),
    3 => __('Miércoles', 'wp-time-clock'),
    4 => __('Jueves', 'wp-time-clock'),
    5 => __('Viernes', 'wp-time-clock'),
    6 => __('Sábado', 'wp-time-clock')
);

// Convertir weekend_days a array
$weekend_days = explode(',', $settings['weekend_days']);

?>

<div class="wrap wp-time-clock-admin">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <!-- Pestaña de navegación -->
    <div class="wp-time-clock-settings-tabs">
        <a href="#general" class="wp-time-clock-tab active" data-tab="general"><?php _e('General', 'wp-time-clock'); ?></a>
        <a href="#appearance" class="wp-time-clock-tab" data-tab="appearance"><?php _e('Apariencia', 'wp-time-clock'); ?></a>
        <a href="#notifications" class="wp-time-clock-tab" data-tab="notifications"><?php _e('Notificaciones', 'wp-time-clock'); ?></a>
        <a href="#advanced" class="wp-time-clock-tab" data-tab="advanced"><?php _e('Avanzado', 'wp-time-clock'); ?></a>
        <a href="#shortcodes" class="wp-time-clock-tab" data-tab="shortcodes"><?php _e('Shortcodes', 'wp-time-clock'); ?></a>
    </div>
    
    <!-- Formulario de configuración -->
    <form method="post" action="" class="wp-time-clock-settings-form">
        <?php wp_nonce_field('wp_time_clock_settings_nonce'); ?>
        <input type="hidden" name="wp_time_clock_settings" value="1">
        
        <!-- Pestaña: General -->
        <div id="general" class="wp-time-clock-tab-content active">
            <div class="wp-time-clock-settings-section">
                <h3><?php _e('Configuración General', 'wp-time-clock'); ?></h3>
                
                <div class="wp-time-clock-field">
                    <label for="working_hours_per_day"><?php _e('Horas de trabajo por día:', 'wp-time-clock'); ?></label>
                    <input type="number" id="working_hours_per_day" name="working_hours_per_day" 
                           value="<?php echo esc_attr($settings['working_hours_per_day']); ?>"
                           min="1" max="24" step="0.5">
                    <p class="description"><?php _e('Número de horas laborables estándar por día.', 'wp-time-clock'); ?></p>
                </div>
                
                <div class="wp-time-clock-field">
                    <label><?php _e('Días laborables:', 'wp-time-clock'); ?></label>
                    <fieldset>
                        <?php foreach ($weekdays as $key => $day): ?>
                        <label>
                            <input type="checkbox" name="weekday_<?php echo $key; ?>" 
                                   <?php checked(!in_array($key, $weekend_days)); ?>>
                            <?php echo esc_html($day); ?>
                        </label><br>
                        <?php endforeach; ?>
                    </fieldset>
                    <p class="description"><?php _e('Marca los días que se consideran laborables.', 'wp-time-clock'); ?></p>
                </div>
                
                <div class="wp-time-clock-field">
                    <label for="workday_start"><?php _e('Hora inicio jornada:', 'wp-time-clock'); ?></label>
                    <input type="time" id="workday_start" name="workday_start" 
                           value="<?php echo esc_attr($settings['workday_start']); ?>">
                    <p class="description"><?php _e('Hora de inicio de la jornada laboral estándar.', 'wp-time-clock'); ?></p>
                </div>
                
                <div class="wp-time-clock-field">
                    <label for="workday_end"><?php _e('Hora fin jornada:', 'wp-time-clock'); ?></label>
                    <input type="time" id="workday_end" name="workday_end" 
                           value="<?php echo esc_attr($settings['workday_end']); ?>">
                    <p class="description"><?php _e('Hora de finalización de la jornada laboral estándar.', 'wp-time-clock'); ?></p>
                </div>
                
                <div class="wp-time-clock-field">
                    <label for="allow_manual_entry">
                        <input type="checkbox" id="allow_manual_entry" name="allow_manual_entry" 
                               <?php checked($settings['allow_manual_entry'], 'yes'); ?>>
                        <?php _e('Permitir entrada manual', 'wp-time-clock'); ?>
                    </label>
                    <p class="description"><?php _e('Si está activado, los administradores pueden editar y añadir entradas manualmente.', 'wp-time-clock'); ?></p>
                </div>
                
                <div class="wp-time-clock-field">
                    <label for="require_approval">
                        <input type="checkbox" id="require_approval" name="require_approval" 
                               <?php checked($settings['require_approval'], 'yes'); ?>>
                        <?php _e('Requerir aprobación', 'wp-time-clock'); ?>
                    </label>
                    <p class="description"><?php _e('Si está activado, las modificaciones en las entradas requerirán aprobación de un administrador.', 'wp-time-clock'); ?></p>
                </div>
            </div>
            
            <div class="wp-time-clock-settings-section">
                <h3><?php _e('Ubicación', 'wp-time-clock'); ?></h3>
                
                <div class="wp-time-clock-field">
                    <label for="geolocation_enabled">
                        <input type="checkbox" id="geolocation_enabled" name="geolocation_enabled" 
                               <?php checked($settings['geolocation_enabled'], 'yes'); ?>>
                        <?php _e('Habilitar geolocalización', 'wp-time-clock'); ?>
                    </label>
                    <p class="description"><?php _e('Si está activado, se registrará la ubicación del usuario al fichar.', 'wp-time-clock'); ?></p>
                </div>
            </div>
        </div>
        
        <!-- Pestaña: Apariencia -->
        <div id="appearance" class="wp-time-clock-tab-content">
            <div class="wp-time-clock-settings-section">
                <h3><?php _e('Estilo del Botón', 'wp-time-clock'); ?></h3>
                
                <div class="wp-time-clock-field">
                    <label for="clock_button_style"><?php _e('Estilo del botón de fichaje:', 'wp-time-clock'); ?></label>
                    <select id="clock_button_style" name="clock_button_style">
                        <option value="default" <?php selected($settings['clock_button_style'], 'default'); ?>><?php _e('Predeterminado', 'wp-time-clock'); ?></option>
                        <option value="modern" <?php selected($settings['clock_button_style'], 'modern'); ?>><?php _e('Moderno', 'wp-time-clock'); ?></option>
                        <option value="minimal" <?php selected($settings['clock_button_style'], 'minimal'); ?>><?php _e('Minimalista', 'wp-time-clock'); ?></option>
                    </select>
                    <p class="description"><?php _e('Selecciona el estilo visual para el botón de fichaje.', 'wp-time-clock'); ?></p>
                </div>
                
                <div class="wp-time-clock-field">
                    <label for="display_clock_time">
                        <input type="checkbox" id="display_clock_time" name="display_clock_time" 
                               <?php checked($settings['display_clock_time'], 'yes'); ?>>
                        <?php _e('Mostrar reloj en tiempo real', 'wp-time-clock'); ?>
                    </label>
                    <p class="description"><?php _e('Si está activado, se mostrará un reloj en tiempo real junto al botón de fichaje.', 'wp-time-clock'); ?></p>
                </div>
                
                <div class="wp-time-clock-field">
                    <label for="allow_clock_note">
                        <input type="checkbox" id="allow_clock_note" name="allow_clock_note" 
                               <?php checked($settings['allow_clock_note'], 'yes'); ?>>
                        <?php _e('Permitir notas en fichajes', 'wp-time-clock'); ?>
                    </label>
                    <p class="description"><?php _e('Si está activado, los usuarios podrán añadir notas al fichar entrada o salida.', 'wp-time-clock'); ?></p>
                </div>
            </div>
            
            <div class="wp-time-clock-settings-section">
                <h3><?php _e('Vista Previa', 'wp-time-clock'); ?></h3>
                
                <div class="wp-time-clock-preview">
                    <p><?php _e('Vista previa del botón de fichaje con el estilo seleccionado:', 'wp-time-clock'); ?></p>
                    
                    <div class="wp-time-clock-preview-button" data-style="default">
                        <!-- Vista previa se generará con JavaScript -->
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Pestaña: Notificaciones -->
        <div id="notifications" class="wp-time-clock-tab-content">
            <div class="wp-time-clock-settings-section">
                <h3><?php _e('Configuración de Notificaciones', 'wp-time-clock'); ?></h3>
                
                <div class="wp-time-clock-field">
                    <label for="notification_emails"><?php _e('Correos para notificaciones:', 'wp-time-clock'); ?></label>
                    <input type="email" id="notification_emails" name="notification_emails" 
                           value="<?php echo esc_attr($settings['notification_emails']); ?>"
                           class="regular-text">
                    <p class="description"><?php _e('Dirección de correo electrónico para recibir notificaciones de fichajes.', 'wp-time-clock'); ?></p>
                </div>
                
                <div class="wp-time-clock-field">
                    <label>
                        <input type="checkbox" name="notify_on_clock_in" <?php checked(true); ?> disabled>
                        <?php _e('Notificar al administrador cuando un usuario ficha entrada', 'wp-time-clock'); ?>
                    </label>
                    <p class="description"><?php _e('Característica disponible en la versión Pro.', 'wp-time-clock'); ?></p>
                </div>
                
                <div class="wp-time-clock-field">
                    <label>
                        <input type="checkbox" name="notify_on_clock_out" <?php checked(true); ?> disabled>
                        <?php _e('Notificar al administrador cuando un usuario ficha salida', 'wp-time-clock'); ?>
                    </label>
                    <p class="description"><?php _e('Característica disponible en la versión Pro.', 'wp-time-clock'); ?></p>
                </div>
                
                <div class="wp-time-clock-field">
                    <label>
                        <input type="checkbox" name="notify_on_edit" <?php checked(true); ?> disabled>
                        <?php _e('Notificar al usuario cuando se edita uno de sus fichajes', 'wp-time-clock'); ?>
                    </label>
                    <p class="description"><?php _e('Característica disponible en la versión Pro.', 'wp-time-clock'); ?></p>
                </div>
            </div>
        </div>
        
        <!-- Pestaña: Avanzado -->
        <div id="advanced" class="wp-time-clock-tab-content">
            <div class="wp-time-clock-settings-section">
                <h3><?php _e('Configuración Avanzada', 'wp-time-clock'); ?></h3>
                
                <div class="wp-time-clock-field">
                    <label for="enable_breaks">
                        <input type="checkbox" id="enable_breaks" name="enable_breaks" 
                               <?php checked($settings['enable_breaks'], 'yes'); ?>>
                        <?php _e('Habilitar gestión de pausas', 'wp-time-clock'); ?>
                    </label>
                    <p class="description"><?php _e('Si está activado, los usuarios podrán registrar pausas durante su jornada.', 'wp-time-clock'); ?></p>
                </div>
                
                <div class="wp-time-clock-field">
                    <label for="auto_clock_out">
                        <input type="checkbox" id="auto_clock_out" name="auto_clock_out" 
                               <?php checked($settings['auto_clock_out'], 'yes'); ?>>
                        <?php _e('Registro automático de salida', 'wp-time-clock'); ?>
                    </label>
                    <p class="description"><?php _e('Si está activado, se registrará automáticamente la salida de los usuarios que no lo hayan hecho.', 'wp-time-clock'); ?></p>
                </div>
                
                <div class="wp-time-clock-field wp-time-clock-auto-clock-out-time" <?php echo $settings['auto_clock_out'] !== 'yes' ? 'style="display:none;"' : ''; ?>>
                    <label for="auto_clock_out_time"><?php _e('Hora para registro automático:', 'wp-time-clock'); ?></label>
                    <input type="time" id="auto_clock_out_time" name="auto_clock_out_time" 
                           value="<?php echo esc_attr($settings['auto_clock_out_time']); ?>">
                    <p class="description"><?php _e('Hora a la que se registrará automáticamente la salida de los usuarios que no lo hayan hecho.', 'wp-time-clock'); ?></p>
                </div>
                
                <div class="wp-time-clock-field">
                    <label><?php _e('Fin de semana:', 'wp-time-clock'); ?></label>
                    <fieldset>
                        <?php foreach ($weekdays as $key => $day): ?>
                        <label>
                            <input type="checkbox" name="weekend_days[]" value="<?php echo $key; ?>" 
                                   <?php checked(in_array($key, $weekend_days)); ?>>
                            <?php echo esc_html($day); ?>
                        </label><br>
                        <?php endforeach; ?>
                    </fieldset>
                    <p class="description"><?php _e('Selecciona los días que se consideran fin de semana.', 'wp-time-clock'); ?></p>
                </div>
            </div>
            
            <div class="wp-time-clock-settings-section">
                <h3><?php _e('Herramientas', 'wp-time-clock'); ?></h3>
                
                <div class="wp-time-clock-field">
                    <button type="button" class="button wp-time-clock-export-settings">
                        <?php _e('Exportar configuración', 'wp-time-clock'); ?>
                    </button>
                    <p class="description"><?php _e('Exporta toda la configuración actual a un archivo JSON.', 'wp-time-clock'); ?></p>
                </div>
                
                <div class="wp-time-clock-field">
                    <label for="import_settings"><?php _e('Importar configuración:', 'wp-time-clock'); ?></label>
                    <input type="file" id="import_settings" name="import_settings" accept=".json">
                    <button type="button" class="button wp-time-clock-import-settings">
                        <?php _e('Importar', 'wp-time-clock'); ?>
                    </button>
                    <p class="description"><?php _e('Importa la configuración desde un archivo JSON.', 'wp-time-clock'); ?></p>
                </div>
                
                <div class="wp-time-clock-field">
                    <button type="button" class="button wp-time-clock-reset-settings">
                        <?php _e('Restablecer configuración predeterminada', 'wp-time-clock'); ?>
                    </button>
                    <p class="description"><?php _e('Restablece toda la configuración a sus valores predeterminados.', 'wp-time-clock'); ?></p>
                </div>
            </div>
        </div>
        
        <!-- Pestaña: Shortcodes -->
        <div id="shortcodes" class="wp-time-clock-tab-content">
            <div class="wp-time-clock-settings-section">
                <h3><?php _e('Shortcodes Disponibles', 'wp-time-clock'); ?></h3>
                
                <div class="wp-time-clock-shortcode-info">
                    <h4><?php _e('Botón de Fichaje', 'wp-time-clock'); ?></h4>
                    <code>[wp_time_clock]</code>
                    <p class="description"><?php _e('Muestra el botón de fichaje para que los usuarios registren su entrada y salida.', 'wp-time-clock'); ?></p>
                    
                    <h5><?php _e('Parámetros opcionales:', 'wp-time-clock'); ?></h5>
                    <ul>
                        <li><code>text_in="Fichar Entrada"</code> - <?php _e('Texto para el botón de entrada', 'wp-time-clock'); ?></li>
                        <li><code>text_out="Fichar Salida"</code> - <?php _e('Texto para el botón de salida', 'wp-time-clock'); ?></li>
                        <li><code>show_time="yes"</code> - <?php _e('Mostrar reloj (yes/no)', 'wp-time-clock'); ?></li>
                        <li><code>show_status="yes"</code> - <?php _e('Mostrar estado actual (yes/no)', 'wp-time-clock'); ?></li>
                        <li><code>theme="default"</code> - <?php _e('Tema visual (default/modern/minimal)', 'wp-time-clock'); ?></li>
                    </ul>
                    
                    <h4><?php _e('Historial de Fichajes', 'wp-time-clock'); ?></h4>
                    <code>[wp_time_clock_history]</code>
                    <p class="description"><?php _e('Muestra el historial de fichajes del usuario actual.', 'wp-time-clock'); ?></p>
                    
                    <h5><?php _e('Parámetros opcionales:', 'wp-time-clock'); ?></h5>
                    <ul>
                        <li><code>days="30"</code> - <?php _e('Número de días a mostrar', 'wp-time-clock'); ?></li>
                        <li><code>show_notes="yes"</code> - <?php _e('Mostrar notas de fichaje (yes/no)', 'wp-time-clock'); ?></li>
                        <li><code>show_times="yes"</code> - <?php _e('Mostrar horas exactas (yes/no)', 'wp-time-clock'); ?></li>
                    </ul>
                </div>
                
                <div class="wp-time-clock-shortcode-examples">
                    <h4><?php _e('Ejemplos:', 'wp-time-clock'); ?></h4>
                    
                    <div class="wp-time-clock-example">
                        <h5><?php _e('Botón de fichaje con tema moderno:', 'wp-time-clock'); ?></h5>
                        <code>[wp_time_clock theme="modern" text_in="Comenzar Trabajo" text_out="Terminar Trabajo"]</code>
                    </div>
                    
                    <div class="wp-time-clock-example">
                        <h5><?php _e('Historial de los últimos 7 días:', 'wp-time-clock'); ?></h5>
                        <code>[wp_time_clock_history days="7" show_notes="no"]</code>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Botón para guardar -->
        <p class="submit">
            <input type="submit" name="submit" id="submit" class="button button-primary" value="<?php _e('Guardar Cambios', 'wp-time-clock'); ?>">
        </p>
    </form>
</div>

<script>
jQuery(document).ready(function($) {
    // Manejar pestañas de configuración
    $('.wp-time-clock-tab').on('click', function(e) {
        e.preventDefault();
        
        var tabId = $(this).data('tab');
        
        // Actualizar pestañas
        $('.wp-time-clock-tab').removeClass('active');
        $(this).addClass('active');
        
        // Actualizar contenido
        $('.wp-time-clock-tab-content').removeClass('active');
        $('#' + tabId).addClass('active');
        
        // Actualizar URL con hash
        window.location.hash = tabId;
    });
    
    // Verificar hash en la URL
    var hash = window.location.hash.substr(1);
    if (hash && $('#' + hash).length) {
        $('.wp-time-clock-tab[data-tab="' + hash + '"]').click();
    }
    
    // Mostrar/ocultar hora de registro automático
    $('#auto_clock_out').on('change', function() {
        if ($(this).is(':checked')) {
            $('.wp-time-clock-auto-clock-out-time').show();
        } else {
            $('.wp-time-clock-auto-clock-out-time').hide();
        }
    });
    
    // Vista previa del botón
    function updateButtonPreview() {
        var style = $('#clock_button_style').val();
        var showTime = $('#display_clock_time').is(':checked');
        var allowNote = $('#allow_clock_note').is(':checked');
        
        // Simular HTML del botón
        var previewHtml = `
            <div class="wp-time-clock-container wp-time-clock-container-${style}" data-status="clocked_out">
                ${showTime ? '<div class="wp-time-clock-time">12:34:56</div>' : ''}
                <button class="wp-time-clock-button wp-time-clock-button-${style} wp-time-clock-button-in">
                    ${style === 'modern' ? '<span class="dashicons dashicons-clock"></span> ' : ''}
                    Fichar Entrada
                </button>
                <div class="wp-time-clock-message"></div>
            </div>
        `;
        
        $('.wp-time-clock-preview-button').html(previewHtml);
    }
    
    // Actualizar vista previa cuando cambian las opciones
    $('#clock_button_style, #display_clock_time, #allow_clock_note').on('change', updateButtonPreview);
    
    // Inicializar vista previa
    updateButtonPreview();
    
    // Exportar configuración
    $('.wp-time-clock-export-settings').on('click', function() {
        alert('<?php _e('Función de exportación disponible en la versión Pro.', 'wp-time-clock'); ?>');
    });
    
    // Importar configuración
    $('.wp-time-clock-import-settings').on('click', function() {
        alert('<?php _e('Función de importación disponible en la versión Pro.', 'wp-time-clock'); ?>');
    });
    
    // Restablecer configuración
    $('.wp-time-clock-reset-settings').on('click', function() {
        if (confirm('<?php _e('¿Estás seguro de que deseas restablecer toda la configuración a los valores predeterminados? Esta acción no se puede deshacer.', 'wp-time-clock'); ?>')) {
            alert('<?php _e('Función de restablecimiento disponible en la versión Pro.', 'wp-time-clock'); ?>');
        }
    });
});
</script>
