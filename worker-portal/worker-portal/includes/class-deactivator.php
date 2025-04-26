<?php
/**
 * Se ejecuta durante la desactivación del plugin
 *
 * @since      1.0.0
 */
class Worker_Portal_Deactivator {

    /**
     * Método principal de desactivación
     *
     * @since    1.0.0
     */
    public static function deactivate() {
        // Limpiar la caché de reescritura
        flush_rewrite_rules();
        
        // Opcional: Eliminar configuraciones temporales
        // delete_option('worker_portal_version');
        
        // Opcional: Desactivar permisos especiales
        // $admin_role = get_role('administrator');
        // if ($admin_role) {
        //     // Remover capacidades específicas del plugin si es necesario
        //     $admin_role->remove_cap('wp_worker_view_own_documents');
        // }
    }
}