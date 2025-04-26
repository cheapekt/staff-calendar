<?php
/**
 * Gestiona la internacionalización del plugin
 *
 * @since      1.0.0
 */
class Worker_Portal_i18n {

    /**
     * Carga el dominio de texto del plugin para la traducción
     *
     * @since    1.0.0
     */
    public function load_plugin_textdomain() {
        // Cargar traducciones desde el directorio de idiomas del plugin
        load_plugin_textdomain(
            'worker-portal',
            false,
            dirname(plugin_basename(WORKER_PORTAL_PATH . 'worker-portal.php')) . '/languages/'
        );
    }
}