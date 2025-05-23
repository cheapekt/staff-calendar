<?php
/**
 * Sincronización entre el calendario (wp_staff_calendar) y los proyectos (wp_worker_projects)
 * 
 * Este archivo debe ser guardado en:
 * worker-portal/worker-portal/includes/integrations/calendario-sync.php
 */

// Si este archivo es llamado directamente, abortar
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Clase que maneja la sincronización entre el calendario y los proyectos
 */
class Worker_Portal_Calendar_Sync {

    /**
     * Constructor
     */
    public function __construct() {
        // Inicializar la sincronización
        $this->init();
    }

    /**
     * Inicializar la clase
     */
    public function init() {
        // Agregar un cron job para sincronizar los destinos con los proyectos
        add_action('init', array($this, 'setup_sync_cron'));
        
        // Hook para la sincronización manual y programada
        add_action('wp_ajax_sync_calendar_projects', array($this, 'ajax_sync_calendar_projects'));
        add_action('worker_calendar_projects_sync', array($this, 'sync_calendar_projects'));
        
        // Añadir script para el manejo de destinos en el frontend
        add_action('wp_footer', array($this, 'add_calendar_integration_script'));
    }

    /**
     * Configurar cron job para sincronización
     */
    public function setup_sync_cron() {
        // Programar la sincronización si no está ya programada
        if (!wp_next_scheduled('worker_calendar_projects_sync')) {
            wp_schedule_event(time(), 'hourly', 'worker_calendar_projects_sync');
        }
    }

    /**
     * Sincronizar proyectos desde el calendario
     */
    public function sync_calendar_projects() {
        global $wpdb;
        
        // Tablas que usaremos
        $calendar_table = $wpdb->prefix . 'staff_calendar';
        $projects_table = $wpdb->prefix . 'worker_projects';
        
        // Verificar que ambas tablas existen
        if ($wpdb->get_var("SHOW TABLES LIKE '$calendar_table'") != $calendar_table ||
            $wpdb->get_var("SHOW TABLES LIKE '$projects_table'") != $projects_table) {
            return false;
        }
        
        // Obtener todos los destinos únicos del calendario
        $calendar_destinations = $wpdb->get_col("SELECT DISTINCT destination FROM $calendar_table WHERE destination != ''");
        
        if (empty($calendar_destinations)) {
            return false;
        }
        
        // Obtener todos los proyectos existentes - SOLO COMPARAR POR NOMBRE
        $existing_projects = $wpdb->get_results("SELECT id, name FROM $projects_table", ARRAY_A);
        $existing_names = array_column($existing_projects, 'name');
        
        $synced_count = 0;
        
        // Procesar cada destino
        foreach ($calendar_destinations as $destination) {
            // Solo verificar si ya existe un proyecto con este NOMBRE
            if (in_array($destination, $existing_names)) {
                continue; // Ya existe un proyecto con este nombre, saltar
            }
            
            // Crear un nuevo proyecto basado en el destino
            $result = $wpdb->insert(
                $projects_table,
                array(
                    'name' => $destination,
                    'description' => 'Creado automáticamente desde el calendario',
                    'location' => $destination,
                    'start_date' => date('Y-m-d'),
                    'end_date' => date('Y-m-d', strtotime('+365 days')),
                    'status' => 'active'
                )
            );
            
            if ($result) {
                $synced_count++;
                error_log("Worker Portal: Proyecto '$destination' creado desde calendario");
            }
        }
        
        return $synced_count;
    }

    /**
     * Endpoint AJAX para sincronización manual
     */
    public function ajax_sync_calendar_projects() {
        // Verificar si es una petición AJAX
        if (!wp_doing_ajax()) {
            return;
        }
        
        // Verificar nonce y permisos
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'calendar_sync_nonce') || 
            !current_user_can('manage_options')) {
            wp_send_json_error('No tienes permisos para realizar esta acción');
        }
        
        // Ejecutar sincronización
        $synced = $this->sync_calendar_projects();
        
        if ($synced !== false) {
            wp_send_json_success(array(
                'message' => sprintf(_n(
                    'Se ha sincronizado %d proyecto desde el calendario',
                    'Se han sincronizado %d proyectos desde el calendario',
                    $synced,
                    'worker-portal'
                ), $synced),
                'count' => $synced
            ));
        } else {
            wp_send_json_error('Error al sincronizar. Verifica que las tablas existen.');
        }
    }

    /**
     * Función para forzar sincronización inmediata cuando se crea/modifica un destino
     */
    public function sync_specific_destination($destination) {
        if (empty($destination)) {
            return false;
        }
        
        global $wpdb;
        $projects_table = $wpdb->prefix . 'worker_projects';
        
        // Verificar si ya existe
        $existing = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT id FROM $projects_table WHERE name = %s",
                $destination
            )
        );
        
        if ($existing) {
            return false; // Ya existe
        }
        
        // Crear el proyecto
        $result = $wpdb->insert(
            $projects_table,
            array(
                'name' => $destination,
                'description' => 'Creado automáticamente desde el calendario',
                'location' => $destination,
                'start_date' => date('Y-m-d'),
                'end_date' => date('Y-m-d', strtotime('+365 days')),
                'status' => 'active'
            )
        );
        
        return $result !== false;
    }

    /**
     * Añadir script para la integración automática
     */
    public function add_calendar_integration_script() {
        // Solo añadir en las páginas relevantes
        if (!is_page('portal-del-trabajador') && !has_shortcode(get_the_content(), 'worker_portal')) {
            return;
        }
        
        ?>
        <script type="text/javascript">
        (function($) {
            'use strict';
            
            $(function() {
                // Si estamos en la página de hojas de trabajo
                if ($('#worker-portal-worksheet-form').length > 0) {
                    // Procesar la integración con el calendario
                    processCalendarDestination();
                }
            });
            
            function processCalendarDestination() {
                // Verificar si hay un destino guardado en localStorage
                var lastDestination = localStorage.getItem('ultimo_destino_laboral');
                if (!lastDestination) return;
                
                // Buscar en las opciones del select si hay algún proyecto que coincida
                var found = false;
                var $projectSelect = $('#project-id');
                
                $projectSelect.find('option').each(function() {
                    var projectName = $(this).text().toLowerCase();
                    var destination = lastDestination.toLowerCase();
                    
                    // Buscar coincidencia exacta o parcial
                    if (projectName === destination || 
                        projectName.indexOf(destination) !== -1 || 
                        destination.indexOf(projectName) !== -1) {
                        
                        $projectSelect.val($(this).val());
                        found = true;
                        return false; // Salir del bucle
                    }
                });
                
                // Si se encontró una coincidencia, dar feedback visual
                if (found) {
                    $projectSelect.css('background-color', '#ffffcc').delay(1500).queue(function(next) {
                        $(this).css('background-color', '');
                        next();
                    });
                }
            }
            
            // Si hay un formulario de calendario, capturar el destino
            $(document).on('submit', 'form:contains("Destino")', function() {
                var $form = $(this);
                var destination = '';
                
                // Intentar encontrar el campo de destino por diferentes criterios
                $form.find('input[type="text"], textarea').each(function() {
                    var $field = $(this);
                    var $container = $field.closest('div, label');
                    
                    if ($container.text().indexOf('Destino') > -1 && $field.val()) {
                        destination = $field.val();
                        return false; // Salir del bucle
                    }
                });
                
                if (destination) {
                    // Guardar el destino en localStorage
                    localStorage.setItem('ultimo_destino_laboral', destination);
                }
            });
            
        })(jQuery);
        </script>
        <?php
    }
}

// Inicializar la sincronización
$calendar_sync = new Worker_Portal_Calendar_Sync();