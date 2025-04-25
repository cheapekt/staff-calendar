<?php
/**
 * Se activa durante la desactivación del plugin
 *
 * @since      1.0.0
 */
class WP_Time_Clock_Deactivator {

    /**
     * Método ejecutado durante la desactivación del plugin
     *
     * Limpia datos temporales y caché, pero mantiene las tablas de la base de datos
     * para preservar los registros de fichajes.
     *
     * @since    1.0.0
     */
    public static function deactivate() {
        // Eliminar roles y capacidades temporales si es necesario
        self::remove_temp_capabilities();
        
        // Limpiar caché de reescritura
        flush_rewrite_rules();
    }
    
    /**
     * Elimina capacidades temporales que ya no son necesarias
     * pero mantiene las principales para preservar los permisos
     *
     * @since    1.0.0
     */
    private static function remove_temp_capabilities() {
        // Por ahora, no eliminamos capacidades para mantener la configuración
        // del usuario. Si hay capacidades temporales específicas para eliminar,
        // se añadirían aquí.
    }
}
