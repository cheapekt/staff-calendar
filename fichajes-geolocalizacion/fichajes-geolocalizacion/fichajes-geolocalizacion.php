<?php
/**
 * Plugin Name: Control de Geolocalización para Fichajes
 * Description: Permite habilitar o deshabilitar la geolocalización en el plugin WP Time Clock.
 * Version: 1.0
 * Author: Tu Nombre
 */

// Prevenir acceso directo
if (!defined('ABSPATH')) {
    exit;
}

// Clase principal del plugin
class Fichajes_Geolocalizacion {
    
    // Constructor
    public function __construct() {
        // Añadir menú independiente
        add_action('admin_menu', array($this, 'add_menu_page'));
        
        // Registrar estilos
        add_action('admin_enqueue_scripts', array($this, 'enqueue_styles'));
    }
    
    // Añadir página de menú
    public function add_menu_page() {
        add_menu_page(
            'Control de Geolocalización', 
            'Geolocalización', 
            'manage_options', 
            'fichajes-geolocalizacion', 
            array($this, 'render_settings_page'),
            'dashicons-location-alt',
            59 // Posición en el menú
        );
    }
    
    // Estilos para la página de administración
    public function enqueue_styles($hook) {
        if ($hook != 'toplevel_page_fichajes-geolocalizacion') {
            return;
        }
        
        wp_enqueue_style('wp-admin'); // Usar estilos de admin de WordPress
    }
    
    // Renderizar página de configuración
    public function render_settings_page() {
        // Verificar permisos
        if (!current_user_can('manage_options')) {
            wp_die('No tienes permisos suficientes para acceder a esta página.');
        }
        
        // Procesar el formulario si se envió
        if (isset($_POST['fichajes_geo_submit']) && check_admin_referer('fichajes_geo_nonce')) {
            $this->update_geolocation_setting(isset($_POST['geolocation_enabled']) ? 'yes' : 'no');
        }
        
        // Obtener configuración actual
        $geo_enabled = $this->get_geolocation_setting();
        
        // Mostrar la página
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            
            <?php if (isset($_POST['fichajes_geo_submit'])): ?>
                <div class="notice notice-success is-dismissible">
                    <p>La configuración de geolocalización se ha actualizado correctamente.</p>
                </div>
            <?php endif; ?>
            
            <div class="card">
                <h2>Configuración de Geolocalización</h2>
                <p>Esta opción controla si el plugin de fichajes solicitará la ubicación de los usuarios cuando fichen entrada o salida.</p>
                
                <form method="post" action="">
                    <?php wp_nonce_field('fichajes_geo_nonce'); ?>
                    
                    <table class="form-table" role="presentation">
                        <tr>
                            <th scope="row">Estado de geolocalización</th>
                            <td>
                                <fieldset>
                                    <legend class="screen-reader-text">
                                        <span>Estado de geolocalización</span>
                                    </legend>
                                    <label for="geolocation_enabled">
                                        <input name="geolocation_enabled" type="checkbox" id="geolocation_enabled" value="1" <?php checked($geo_enabled, true); ?>>
                                        Habilitar geolocalización
                                    </label>
                                </fieldset>
                            </td>
                        </tr>
                    </table>
                    
                    <p class="submit">
                        <input type="submit" name="fichajes_geo_submit" id="submit" class="button button-primary" value="Guardar Cambios">
                    </p>
                </form>
            </div>
            
            <div class="card" style="margin-top: 20px;">
                <h2>Información sobre la Geolocalización</h2>
                <p>Cuando la geolocalización está activada, ocurre lo siguiente:</p>
                
                <ul style="list-style-type: disc; padding-left: 20px; margin-left: 10px;">
                    <li>El navegador del usuario solicitará permiso para acceder a su ubicación</li>
                    <li>La latitud y longitud se registrarán junto con cada fichaje</li>
                    <li>Esta información se almacena en la base de datos</li>
                </ul>
                
                <p><strong>Nota:</strong> Actualmente, esta información no se muestra en la interfaz del plugin de fichajes, pero se guarda en la base de datos para posibles usos futuros.</p>
            </div>
            
            <?php $this->display_database_info(); ?>
        </div>
        <?php
    }
    
    // Mostrar información de la base de datos (para diagnóstico)
    private function display_database_info() {
        global $wpdb;
        $table_settings = $wpdb->prefix . 'time_clock_settings';
        
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_settings'") == $table_settings;
        
        if (!$table_exists) {
            ?>
            <div class="card" style="margin-top: 20px; border-left: 4px solid #dc3232;">
                <h2>Estado de la Base de Datos</h2>
                <p>La tabla de configuraciones del plugin de fichajes no existe (<code><?php echo $table_settings; ?></code>).</p>
                <p>Esto puede indicar que el plugin WP Time Clock no está instalado correctamente o que la tabla no se creó durante la activación.</p>
            </div>
            <?php
            return;
        }
        
        $option_exists = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_settings WHERE option_name = %s",
            'geolocation_enabled'
        ));
        
        ?>
        <div class="card" style="margin-top: 20px; border-left: 4px solid #46b450;">
            <h2>Estado de la Base de Datos</h2>
            <p>Tabla de configuraciones: <code><?php echo $table_settings; ?></code> (<?php echo $table_exists ? 'Existe' : 'No existe'; ?>)</p>
            <p>Opción de geolocalización: <?php echo $option_exists ? 'Encontrada' : 'No encontrada (se creará al guardar)'; ?></p>
            <p>Valor actual: <?php echo $this->get_geolocation_setting() ? 'Habilitada' : 'Deshabilitada'; ?></p>
        </div>
        <?php
    }
    
    // Obtener la configuración actual
    private function get_geolocation_setting() {
        global $wpdb;
        $table_settings = $wpdb->prefix . 'time_clock_settings';
        
        $value = $wpdb->get_var($wpdb->prepare(
            "SELECT option_value FROM $table_settings WHERE option_name = %s LIMIT 1",
            'geolocation_enabled'
        ));
        
        // Si no existe, asumimos que está habilitado (comportamiento predeterminado del plugin)
        return ($value === null) ? true : ($value === 'yes');
    }
    
    // Actualizar la configuración
    private function update_geolocation_setting($new_value) {
        global $wpdb;
        $table_settings = $wpdb->prefix . 'time_clock_settings';
        
        // Verificar si la tabla existe
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_settings'") == $table_settings;
        if (!$table_exists) {
            return false;
        }
        
        // Verificar si la opción existe
        $option_exists = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_settings WHERE option_name = %s",
            'geolocation_enabled'
        ));
        
        if ($option_exists > 0) {
            // Actualizar
            return $wpdb->update(
                $table_settings,
                array('option_value' => $new_value),
                array('option_name' => 'geolocation_enabled'),
                array('%s'),
                array('%s')
            );
        } else {
            // Insertar
            return $wpdb->insert(
                $table_settings,
                array(
                    'option_name' => 'geolocation_enabled',
                    'option_value' => $new_value
                ),
                array('%s', '%s')
            );
        }
    }
}

// Inicializar el plugin
$fichajes_geolocalizacion = new Fichajes_Geolocalizacion();
