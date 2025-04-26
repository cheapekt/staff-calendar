<?php
/**
 * Plantilla para mostrar la página de configuración del plugin WP Time Clock
 *
 * @since      1.1.0
 */

// Prevenir acceso directo
if (!defined('ABSPATH')) {
    exit;
}

// Inicializar gestor de fichajes
$clock_manager = new WP_Time_Clock_Manager();

// Obtener configuraciones actuales con valores predeterminados
$settings = [
    'general' => [
        'working_hours_per_day' => $clock_manager->get_setting('working_hours_per_day', '8'),
        'allow_manual_entry' => $clock_manager->get_setting('allow_manual_entry', 'yes'),
        'require_approval' => $clock_manager->get_setting('require_approval', 'no'),
        'workday_start' => $clock_manager->get_setting('workday_start', '09:00:00'),
        'workday_end' => $clock_manager->get_setting('workday_end', '18:00:00'),
    ],
    'location' => [
        'geolocation_enabled' => $clock_manager->get_setting('geolocation_enabled', 'yes'),
    ],
    'appearance' => [
        'clock_button_style' => $clock_manager->get_setting('clock_button_style', 'default'),
        'display_clock_time' => $clock_manager->get_setting('display_clock_time', 'yes'),
        'allow_clock_note' => $clock_manager->get_setting('allow_clock_note', 'yes'),
    ],
    'notifications' => [
        'notification_emails' => $clock_manager->get_setting('notification_emails', get_option('admin_email')),
    ],
    'advanced' => [
        'enable_breaks' => $clock_manager->get_setting('enable_breaks', 'no'),
        'auto_clock_out' => $clock_manager->get_setting('auto_clock_out', 'no'),
        'auto_clock_out_time' => $clock_manager->get_setting('auto_clock_out_time', '23:59:59'),
        'weekend_days' => $clock_manager->get_setting('weekend_days', '0,6'),
    ]
];

// Procesar actualización de configuraciones
if (isset($_POST['wp_time_clock_settings']) && check_admin_referer('wp_time_clock_settings_nonce')) {
    $updated_settings = [];
    
    // Definir reglas de sanitización
    $sanitization_rules = [
        'working_hours_per_day' => function($value) {
            return floatval(max(0, min(24, $value)));
        },
        'allow_manual_entry' => function($value) {
            return $value === 'yes' ? 'yes' : 'no';
        },
        'require_approval' => function($value) {
            return $value === 'yes' ? 'yes' : 'no';
        },
        'workday_start' => function($value) {
            return preg_match('/^([01]\d|2[0-3]):([0-5]\d)$/', $value) ? $value : '09:00';
        },
        'workday_end' => function($value) {
            return preg_match('/^([01]\d|2[0-3]):([0-5]\d)$/', $value) ? $value : '18:00';
        },
        'geolocation_enabled' => function($value) {
            return $value === 'yes' ? 'yes' : 'no';
        },
        'clock_button_style' => function($value) {
            $allowed_styles = ['default', 'modern', 'minimal'];
            return in_array($value, $allowed_styles) ? $value : 'default';
        },
        'display_clock_time' => function($value) {
            return $value === 'yes' ? 'yes' : 'no';
        },
        'allow_clock_note' => function($value) {
            return $value === 'yes' ? 'yes' : 'no';
        },
        'notification_emails' => function($value) {
            return is_email($value) ? $value : get_option('admin_email');
        },
        'enable_breaks' => function($value) {
            return $value === 'yes' ? 'yes' : 'no';
        },
        'auto_clock_out' => function($value) {
            return $value === 'yes' ? 'yes' : 'no';
        },
        'auto_clock_out_time' => function($value) {
            return preg_match('/^([01]\d|2[0-3]):([0-5]\d):([0-5]\d)$/', $value) ? $value : '23:59:59';
        },
        'weekend_days' => function($value) {
            if (!is_array($value)) return '0,6';
            
            $valid_days = array_filter($value, function($day) {
                return is_numeric($day) && $day >= 0 && $day <= 6;
            });
            
            return empty($valid_days) ? '0,6' : implode(',', $valid_days);
        }
    ];
    
    // Procesar cada configuración
    $updated_count = 0;
    foreach ($sanitization_rules as $key => $sanitizer) {
        $raw_value = $_POST[$key] ?? '';
        $sanitized_value = $sanitizer($raw_value);
        
        if ($clock_manager->save_setting($key, $sanitized_value)) {
            $updated_settings[$key] = $sanitized_value;
            $updated_count++;
            
            // Actualizar valor local
            foreach ($settings as &$section) {
                if (isset($section[$key])) {
                    $section[$key] = $sanitized_value;
                    break;
                }
            }
        }
    }
    
    // Mostrar mensaje de éxito
    if ($updated_count > 0) {
        add_settings_error(
            'wp_time_clock_settings', 
            'settings_updated', 
            sprintf(__('Configuración actualizada correctamente. %d valores modificados.', 'wp-time-clock'), $updated_count), 
            'success'
        );
    }
}

// Array de días de la semana
$weekdays = [
    0 => __('Domingo', 'wp-time-clock'),
    1 => __('Lunes', 'wp-time-clock'),
    2 => __('Martes', 'wp-time-clock'),
    3 => __('Miércoles', 'wp-time-clock'),
    4 => __('Jueves', 'wp-time-clock'),
    5 => __('Viernes', 'wp-time-clock'),
    6 => __('Sábado', 'wp-time-clock')
];

// Convertir días de fin de semana a array
$weekend_days = explode(',', $settings['advanced']['weekend_days']);
?>

<div class="wrap wp-time-clock-admin">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <?php 
    // Mostrar mensajes de error/éxito
    settings_errors('wp_time_clock_settings'); 
    ?>
    
    <form method="post" action="" class="wp-time-clock-settings-form">
        <?php 
        wp_nonce_field('wp_time_clock_settings_nonce'); 
        ?>
        <input type="hidden" name="wp_time_clock_settings" value="1">
        
        <!-- Navegación de pestañas -->
        <div class="wp-time-clock-settings-tabs">
            <a href="#general" class="wp-time-clock-tab active" data-tab="general">
                <span class="dashicons dashicons-admin-generic"></span>
                <?php _e('General', 'wp-time-clock'); ?>
            </a>
            <a href="#appearance" class="wp-time-clock-tab" data-tab="appearance">
                <span class="dashicons dashicons-admin-appearance"></span>
                <?php _e('Apariencia', 'wp-time-clock'); ?>
            </a>
            <a href="#location" class="wp-time-clock-tab" data-tab="location">
                <span class="dashicons dashicons-location"></span>
                <?php _e('Ubicación', 'wp-time-clock'); ?>
            </a>
            <a href="#notifications" class="wp-time-clock-tab" data-tab="notifications">
                <span class="dashicons dashicons-email-alt"></span>
                <?php _e('Notificaciones', 'wp-time-clock'); ?>
            </a>
            <a href="#advanced" class="wp-time-clock-tab" data-tab="advanced">
                <span class="dashicons dashicons-admin-tools"></span>
                <?php _e('Avanzado', 'wp-time-clock'); ?>
            </a>
            <a href="#shortcodes" class="wp-time-clock-tab" data-tab="shortcodes">
                <span class="dashicons dashicons-shortcode"></span>
                <?php _e('Shortcodes', 'wp-time-clock'); ?>
            </a>
        </div>

        <!-- Contenido de las pestañas -->
        <div id="general" class="wp-time-clock-tab-content active">
            <!-- Contenido de la pestaña General -->
            <div class="wp-time-clock-settings-section">
                <h3><?php _e('Configuración General', 'wp-time-clock'); ?></h3>
                
                <div class="wp-time-clock-field">
                    <label for="working_hours_per_day"><?php _e('Horas de trabajo por día', 'wp-time-clock'); ?></label>
                    <input type="number" id="working_hours_per_day" name="working_hours_per_day" 
                           value="<?php echo esc_attr($settings['general']['working_hours_per_day']); ?>" 
                           min="1" max="24" step="0.5">
                    <p class="description"><?php _e('Número estándar de horas laborables por día.', 'wp-time-clock'); ?></p>
                </div>

                <!-- Resto de campos de la pestaña General -->
                <!-- ... (similar a tu implementación anterior) ... -->
            </div>
        </div>

        <!-- Las demás pestañas seguirán un patrón similar -->
        
        <!-- Botón de guardar -->
        <div class="wp-time-clock-submit-container">
            <?php submit_button(__('Guardar Cambios', 'wp-time-clock'), 'primary large'); ?>
        </div>
    </form>
</div>

<script>
jQuery(document).ready(function($) {
    // Manejar cambios de pestañas
    $('.wp-time-clock-tab').on('click', function(e) {
        e.preventDefault();
        const tabId = $(this).data('tab');
        
        // Actualizar pestañas
        $('.wp-time-clock-tab').removeClass('active');
        $(this).addClass('active');
        
        // Actualizar contenido
        $('.wp-time-clock-tab-content').removeClass('active');
        $(`#${tabId}`).addClass('active');
        
        // Actualizar URL
        window.history.replaceState(null, '', `#${tabId}`);
    });

    // Restaurar pestaña desde hash de URL
    const hash = window.location.hash.substr(1);
    if (hash && $(`#${hash}`).length) {
        $(`.wp-time-clock-tab[data-tab="${hash}"]`).click();
    }

    // Manejar campos dependientes
    $('#auto_clock_out').on('change', function() {
        $('.wp-time-clock-auto-clock-out-time').toggle($(this).is(':checked'));
    });

    // Vista previa del botón
    function updateButtonPreview() {
        const style = $('#clock_button_style').val();
        const showTime = $('#display_clock_time').is(':checked');
        
        const previewHtml = `
            <div class="wp-time-clock-container wp-time-clock-container-${style}" data-status="clocked_out">
                ${showTime ? '<div class="wp-time-clock-time">12:34:56</div>' : ''}
                <button class="wp-time-clock-button wp-time-clock-button-${style} wp-time-clock-button-in">
                    Fichar Entrada
                </button>
            </div>
        `;
        
        $('.wp-time-clock-preview-button').html(previewHtml);
    }

    // Actualizar vista previa
    $('#clock_button_style, #display_clock_time').on('change', updateButtonPreview);
    updateButtonPreview();
});
</script>