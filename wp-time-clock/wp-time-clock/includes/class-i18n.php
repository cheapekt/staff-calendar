<?php
/**
 * Define la funcionalidad de internacionalización
 *
 * @since      1.0.0
 */
class WP_Time_Clock_i18n {

    /**
     * Carga el dominio de texto del plugin para la traducción
     *
     * @since    1.0.0
     */
    public function load_plugin_textdomain() {
        load_plugin_textdomain(
            'wp-time-clock',
            false,
            dirname(dirname(plugin_basename(__FILE__))) . '/languages/'
        );
    }
}
