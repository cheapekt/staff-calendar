<?php
/**
 * Plugin Name: Portal del Trabajador
 * Description: Sistema modular de portal para empleados con gestión de documentos, gastos, hojas de trabajo e incentivos.
 * Version: 1.0.0
 * Author: Desarrollador
 * Text Domain: worker-portal
 * Domain Path: /languages
 */

// Si este archivo es llamado directamente, abortar.
if (!defined('ABSPATH')) {
    exit;
}

// Definir constantes del plugin
define('WORKER_PORTAL_VERSION', '1.0.0');
define('WORKER_PORTAL_PATH', plugin_dir_path(__FILE__));
define('WORKER_PORTAL_URL', plugin_dir_url(__FILE__));
define('WORKER_PORTAL_BASENAME', plugin_basename(__FILE__));

/**
 * Código que se ejecuta durante la activación del plugin.
 */
function activate_worker_portal() {
    // Cargar clase de activación
    require_once WORKER_PORTAL_PATH . 'includes/class-activator.php';
    Worker_Portal_Activator::activate();
}

/**
 * Código que se ejecuta durante la desactivación del plugin.
 */
function deactivate_worker_portal() {
    // Cargar clase de desactivación
    require_once WORKER_PORTAL_PATH . 'includes/class-deactivator.php';
    Worker_Portal_Deactivator::deactivate();
}

register_activation_hook(__FILE__, 'activate_worker_portal');
register_deactivation_hook(__FILE__, 'deactivate_worker_portal');

/**
 * La clase principal del plugin.
 */
require_once WORKER_PORTAL_PATH . 'includes/class-worker-portal.php';

/**
 * Comienza la ejecución del plugin.
 */
function run_worker_portal() {
    // Crear una instancia de la clase principal
    $plugin = new Worker_Portal();
    // Ejecutar el plugin
    $plugin->run();
}

// Ejecutar el plugin
run_worker_portal();