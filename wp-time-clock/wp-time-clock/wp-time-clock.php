<?php
/**
 * Plugin Name: WP Time Clock
 * Description: Sistema profesional de fichajes para WordPress
 * Version: 1.0.0
 * Author: Tu Nombre
 * Text Domain: wp-time-clock
 * Domain Path: /languages
 */

// Si este archivo es llamado directamente, abortar.
if (!defined('ABSPATH')) {
    exit;
}

// Definir constantes del plugin
define('WP_TIME_CLOCK_VERSION', '1.0.0');
define('WP_TIME_CLOCK_PATH', plugin_dir_path(__FILE__));
define('WP_TIME_CLOCK_URL', plugin_dir_url(__FILE__));
define('WP_TIME_CLOCK_BASENAME', plugin_basename(__FILE__));

/**
 * Código que se ejecuta durante la activación del plugin.
 */
function activate_wp_time_clock() {
    require_once WP_TIME_CLOCK_PATH . 'includes/class-activator.php';
    WP_Time_Clock_Activator::activate();
}

/**
 * Código que se ejecuta durante la desactivación del plugin.
 */
function deactivate_wp_time_clock() {
    require_once WP_TIME_CLOCK_PATH . 'includes/class-deactivator.php';
    WP_Time_Clock_Deactivator::deactivate();
}

register_activation_hook(__FILE__, 'activate_wp_time_clock');
register_deactivation_hook(__FILE__, 'deactivate_wp_time_clock');

/**
 * El núcleo de la clase del plugin.
 */
require_once WP_TIME_CLOCK_PATH . 'includes/class-wp-time-clock.php';

/**
 * Comienza la ejecución del plugin.
 */
function run_wp_time_clock() {
    $plugin = new WP_Time_Clock();
    $plugin->run();
}
run_wp_time_clock();

/**
 * Funciones de utilidad para integración con otros plugins
 */

/**
 * Obtiene el estado actual de fichaje de un usuario
 * 
 * @param int|null $user_id ID del usuario o null para el usuario actual
 * @return array Información del estado del usuario
 */
function wp_time_clock_get_user_status($user_id = null) {
    $clock_manager = WP_Time_Clock::get_instance()->get_clock_manager();
    return $clock_manager->get_user_status($user_id);
}

/**
 * Obtiene los registros de fichaje de un usuario en un período
 * 
 * @param int $user_id ID del usuario
 * @param string $start_date Fecha de inicio (YYYY-MM-DD)
 * @param string $end_date Fecha de fin (YYYY-MM-DD)
 * @return array Registros de fichaje
 */
function wp_time_clock_get_user_entries($user_id, $start_date, $end_date) {
    $clock_manager = WP_Time_Clock::get_instance()->get_clock_manager();
    return $clock_manager->get_user_entries($user_id, $start_date, $end_date);
}

/**
 * Renderiza un botón de fichaje con atributos personalizados
 * 
 * @param array $atts Atributos para personalizar el botón
 * @return string HTML del botón
 */
function wp_time_clock_render_button($atts = []) {
    $clock_manager = WP_Time_Clock::get_instance()->get_clock_manager();
    return $clock_manager->render_button($atts);
}

/**
 * Shortcode para mostrar el botón de fichaje
 * 
 * @param array $atts Atributos del shortcode
 * @return string HTML renderizado
 */
function wp_time_clock_shortcode($atts) {
    $atts = shortcode_atts(array(
        'text_in' => 'Fichar Entrada',
        'text_out' => 'Fichar Salida',
        'show_time' => 'yes',
        'show_status' => 'yes',
        'theme' => 'default'
    ), $atts, 'wp_time_clock');
    
    return wp_time_clock_render_button($atts);
}
add_shortcode('wp_time_clock', 'wp_time_clock_shortcode');
