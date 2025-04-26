<?php
/**
 * Gestiona la funcionalidad principal de fichajes
 *
 * @since      1.0.0
 */
class WP_Time_Clock_Manager {

    /**
     * Tabla de entradas de fichaje
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $table_entries    Nombre de la tabla de entradas
     */
    private $table_entries;

    /**
     * Tabla de configuraciones
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $table_settings    Nombre de la tabla de configuraciones
     */
    private $table_settings;

    /**
     * Constructor
     *
     * @since    1.0.0
     */
    public function __construct() {
        global $wpdb;
        $this->table_entries = $wpdb->prefix . 'time_clock_entries';
        $this->table_settings = $wpdb->prefix . 'time_clock_settings';
    }

    /**
     * Registra una entrada (clock-in) para un usuario
     *
     * @since    1.0.0
     * @param    int       $user_id          ID del usuario, o null para el usuario actual
     * @param    string    $location         Información de ubicación (JSON)
     * @param    string    $note             Nota opcional del usuario
     * @param    string    $custom_time      Hora personalizada (formato Y-m-d H:i:s)
     * @return   array                       Resultado de la operación
     */
    public function clock_in($user_id = null, $location = '', $note = '', $custom_time = '') {
        // Si no se especifica usuario, usar el actual
        if (null === $user_id) {
            $user_id = get_current_user_id();
        }
        
        // Verificar si el usuario ya tiene una entrada activa
        $active_entry = $this->get_active_entry($user_id);
        if ($active_entry) {
            return array(
                'success' => false,
                'message' => __('Ya tienes una entrada activa. Debes registrar la salida primero.', 'wp-time-clock')
            );
        }
        
        global $wpdb;
        
        // Determinar fecha/hora
        $clock_time = empty($custom_time) ? current_time('mysql') : $custom_time;
        
        // Insertar en la base de datos
        $result = $wpdb->insert(
            $this->table_entries,
            array(
                'user_id' => $user_id,
                'clock_in' => $clock_time,
                'clock_in_location' => $location,
                'clock_in_note' => $note,
                'status' => 'active',
                'created_at' => current_time('mysql')
            ),
            array('%d', '%s', '%s', '%s', '%s', '%s')
        );
        
        if (false === $result) {
            return array(
                'success' => false,
                'message' => __('Error al registrar la entrada: ', 'wp-time-clock') . $wpdb->last_error
            );
        }
        
        // Disparar acción para plugins/temas
        do_action('wp_time_clock_after_clock_in', $user_id, $wpdb->insert_id, $clock_time);
        
        // Éxito
        return array(
            'success' => true,
            'message' => __('Entrada registrada correctamente', 'wp-time-clock'),
            'entry_id' => $wpdb->insert_id,
            'clock_time' => $clock_time
        );
    }

    /**
     * Registra una salida (clock-out) para un usuario
     *
     * @since    1.0.0
     * @param    int       $user_id          ID del usuario, o null para el usuario actual
     * @param    string    $location         Información de ubicación (JSON)
     * @param    string    $note             Nota opcional del usuario
     * @param    string    $custom_time      Hora personalizada (formato Y-m-d H:i:s)
     * @return   array                       Resultado de la operación
     */
    public function clock_out($user_id = null, $location = '', $note = '', $custom_time = '') {
        // Si no se especifica usuario, usar el actual
        if (null === $user_id) {
            $user_id = get_current_user_id();
        }
        
        // Verificar si el usuario tiene una entrada activa
        $active_entry = $this->get_active_entry($user_id);
        if (!$active_entry) {
            return array(
                'success' => false,
                'message' => __('No tienes una entrada activa. Debes registrar la entrada primero.', 'wp-time-clock')
            );
        }
        
        global $wpdb;
        
        // Determinar fecha/hora
        $clock_time = empty($custom_time) ? current_time('mysql') : $custom_time;
        
        // Actualizar el registro
        $result = $wpdb->update(
            $this->table_entries,
            array(
                'clock_out' => $clock_time,
                'clock_out_location' => $location,
                'clock_out_note' => $note
            ),
            array('id' => $active_entry->id),
            array('%s', '%s', '%s'),
            array('%d')
        );
        
        if (false === $result) {
            return array(
                'success' => false,
                'message' => __('Error al registrar la salida: ', 'wp-time-clock') . $wpdb->last_error
            );
        }
        
        // Disparar acción para plugins/temas
        do_action('wp_time_clock_after_clock_out', $user_id, $active_entry->id, $clock_time);
        
        // Calcular horas trabajadas
        $time_worked = $this->calculate_time_worked($active_entry->clock_in, $clock_time);
        
        // Éxito
        return array(
            'success' => true,
            'message' => __('Salida registrada correctamente', 'wp-time-clock'),
            'entry_id' => $active_entry->id,
            'clock_time' => $clock_time,
            'time_worked' => $time_worked
        );
    }

    /**
     * Obtiene la entrada activa de un usuario (si existe)
     *
     * @since    1.0.0
     * @param    int       $user_id    ID del usuario
     * @return   object|null           Objeto con la entrada o null si no hay ninguna activa
     */
    public function get_active_entry($user_id) {
        global $wpdb;
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->table_entries} 
            WHERE user_id = %d 
            AND clock_out IS NULL 
            ORDER BY clock_in DESC 
            LIMIT 1",
            $user_id
        ));
    }

    /**
     * Calcula el tiempo trabajado entre dos fechas
     *
     * @since    1.0.0
     * @param    string    $start    Fecha/hora de inicio (formato MySQL)
     * @param    string    $end      Fecha/hora de fin (formato MySQL)
     * @return   array               Array con horas, minutos, segundos y total en segundos
     */
    public function calculate_time_worked($start, $end) {
        $start_time = strtotime($start);
        $end_time = strtotime($end);
        $diff = $end_time - $start_time;
        
        return array(
            'hours' => floor($diff / 3600),
            'minutes' => floor(($diff % 3600) / 60),
            'seconds' => $diff % 60,
            'total_seconds' => $diff,
            'formatted' => $this->format_time_worked($diff)
        );
    }
    
    /**
     * Formatea un tiempo en segundos a formato legible
     *
     * @since    1.0.0
     * @param    int       $seconds    Tiempo en segundos
     * @return   string                Tiempo formateado (HH:MM:SS)
     */
    public function format_time_worked($seconds) {
        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);
        $seconds = $seconds % 60;
        
        return sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds);
    }

    /**
     * Obtiene el estado actual de fichaje de un usuario
     *
     * @since    1.0.0
     * @param    int       $user_id    ID del usuario, o null para el usuario actual
     * @return   array                 Estado del usuario
     */
    public function get_user_status($user_id = null) {
        if (null === $user_id) {
            $user_id = get_current_user_id();
        }
        
        // Si el usuario no está logueado
        if (!$user_id) {
            return array(
                'status' => 'not_logged_in',
                'message' => __('Usuario no identificado', 'wp-time-clock')
            );
        }
        
        $active_entry = $this->get_active_entry($user_id);
        
        if ($active_entry) {
            $start_time = strtotime($active_entry->clock_in);
            $current_time = time();
            $elapsed = $current_time - $start_time;
            
            return array(
                'status' => 'clocked_in',
                'since' => $active_entry->clock_in,
                'elapsed' => $this->format_time_worked($elapsed),
                'elapsed_seconds' => $elapsed,
                'entry_id' => $active_entry->id,
                'message' => __('Trabajando', 'wp-time-clock')
            );
        } else {
            // Obtener la última entrada del usuario
            $last_entry = $this->get_last_completed_entry($user_id);
            
            if ($last_entry) {
                return array(
                    'status' => 'clocked_out',
                    'last_activity' => $last_entry->clock_out,
                    'last_entry_id' => $last_entry->id,
                    'message' => __('No registrado', 'wp-time-clock')
                );
            } else {
                return array(
                    'status' => 'never_clocked',
                    'message' => __('Nunca ha fichado', 'wp-time-clock')
                );
            }
        }
    }

    /**
     * Obtiene la última entrada completada de un usuario
     *
     * @since    1.0.0
     * @param    int       $user_id    ID del usuario
     * @return   object|null           Objeto con la entrada o null si no hay ninguna
     */
    public function get_last_completed_entry($user_id) {
        global $wpdb;
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->table_entries} 
            WHERE user_id = %d 
            AND clock_out IS NOT NULL 
            ORDER BY clock_out DESC 
            LIMIT 1",
            $user_id
        ));
    }

    /**
     * Obtiene las entradas de fichaje de un usuario en un período
     *
     * @since    1.0.0
     * @param    int       $user_id      ID del usuario
     * @param    string    $start_date   Fecha de inicio (formato Y-m-d)
     * @param    string    $end_date     Fecha fin (formato Y-m-d)
     * @param    string    $status       Estado de las entradas ('all', 'active', 'edited', etc.)
     * @return   array                   Array de entradas
     */
    public function get_user_entries($user_id, $start_date, $end_date, $status = 'all') {
        global $wpdb;
        
        $where = "user_id = %d AND DATE(clock_in) BETWEEN %s AND %s";
        $params = array($user_id, $start_date, $end_date);
        
        if ('all' !== $status) {
            $where .= " AND status = %s";
            $params[] = $status;
        }
        
        $entries = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$this->table_entries} 
                WHERE {$where} 
                ORDER BY clock_in DESC",
                $params
            )
        );
        
        // Calcular tiempo trabajado para cada entrada
        foreach ($entries as &$entry) {
            if ($entry->clock_out) {
                $entry->time_worked = $this->calculate_time_worked($entry->clock_in, $entry->clock_out);
            } else {
                $entry->time_worked = null;
            }
            
            // Añadir datos del usuario editor
            if ($entry->edited_by) {
                $editor = get_userdata($entry->edited_by);
                $entry->editor_name = $editor ? $editor->display_name : __('Usuario desconocido', 'wp-time-clock');
            }
        }
        
        return $entries;
    }

    /**
     * Edita una entrada de fichaje existente
     *
     * @since    1.0.0
     * @param    int       $entry_id      ID de la entrada
     * @param    array     $data          Datos a actualizar
     * @param    int       $editor_id     ID del usuario que edita
     * @return   array                    Resultado de la operación
     */
    public function edit_entry($entry_id, $data, $editor_id = null) {
        global $wpdb;
        
        // Comprobar permisos
        if (!current_user_can('time_clock_edit_entries') && !current_user_can('administrator')) {
            return array(
                'success' => false,
                'message' => __('No tienes permiso para editar entradas', 'wp-time-clock')
            );
        }
        
        // Si no se especifica editor, usar el usuario actual
        if (null === $editor_id) {
            $editor_id = get_current_user_id();
        }
        
        // Verificar que la entrada existe
        $entry = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->table_entries} WHERE id = %d",
            $entry_id
        ));
        
        if (!$entry) {
            return array(
                'success' => false,
                'message' => __('La entrada especificada no existe', 'wp-time-clock')
            );
        }
        
        // Preparar datos a actualizar
        $update_data = array();
        $update_format = array();
        
        if (isset($data['clock_in'])) {
            $update_data['clock_in'] = $data['clock_in'];
            $update_format[] = '%s';
        }
        
        if (isset($data['clock_out'])) {
            $update_data['clock_out'] = $data['clock_out'];
            $update_format[] = '%s';
        }
        
        if (isset($data['clock_in_note'])) {
            $update_data['clock_in_note'] = $data['clock_in_note'];
            $update_format[] = '%s';
        }
        
        if (isset($data['clock_out_note'])) {
            $update_data['clock_out_note'] = $data['clock_out_note'];
            $update_format[] = '%s';
        }
        
        if (isset($data['status'])) {
            $update_data['status'] = $data['status'];
            $update_format[] = '%s';
        }
        
        // Marcar como editado
        $update_data['status'] = isset($data['status']) ? $data['status'] : 'edited';
        $update_data['edited_by'] = $editor_id;
        $update_data['edited_at'] = current_time('mysql');
        $update_format[] = '%s';
        $update_format[] = '%d';
        $update_format[] = '%s';
        
        // Actualizar
        $result = $wpdb->update(
            $this->table_entries,
            $update_data,
            array('id' => $entry_id),
            $update_format,
            array('%d')
        );
        
        if (false === $result) {
            return array(
                'success' => false,
                'message' => __('Error al actualizar la entrada: ', 'wp-time-clock') . $wpdb->last_error
            );
        }
        
        // Disparar acción
        do_action('wp_time_clock_after_edit_entry', $entry_id, $update_data, $entry);
        
        return array(
            'success' => true,
            'message' => __('Entrada actualizada correctamente', 'wp-time-clock'),
            'entry_id' => $entry_id
        );
    }

    /**
     * Obtiene una configuración del plugin
     *
     * @since    1.0.0
     * @param    string    $option    Nombre de la opción
     * @param    mixed     $default   Valor por defecto si no existe
     * @return   mixed                Valor de la opción
     */
    public function get_setting($option, $default = '') {
        global $wpdb;
        
        $value = $wpdb->get_var($wpdb->prepare(
            "SELECT option_value FROM {$this->table_settings} WHERE option_name = %s LIMIT 1",
            $option
        ));
        
        return (null !== $value) ? $value : $default;
    }

    /**
     * Guarda una configuración del plugin
     *
     * @since    1.0.0
     * @param    string    $option    Nombre de la opción
     * @param    mixed     $value     Valor a guardar
     * @return   bool                 Resultado de la operación
     */
    public function save_setting($option, $value) {
        global $wpdb;
        
        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->table_settings} WHERE option_name = %s",
            $option
        ));
        
        if ($exists) {
            return $wpdb->update(
                $this->table_settings,
                array('option_value' => $value),
                array('option_name' => $option),
                array('%s'),
                array('%s')
            );
        } else {
            return $wpdb->insert(
                $this->table_settings,
                array(
                    'option_name' => $option,
                    'option_value' => $value
                ),
                array('%s', '%s')
            );
        }
    }


/**
 * Renderiza el botón de fichaje
 *
 * @since    1.0.0
 * @param    array     $atts     Atributos para personalizar el botón
 * @return   string              HTML del botón
 */
public function render_button($atts = []) {
    // Si el usuario no está logueado
    if (!is_user_logged_in()) {
        return sprintf(
            '<div class="wp-time-clock-message">%s</div>',
            __('Debes iniciar sesión para usar el sistema de fichajes', 'wp-time-clock')
        );
    }
    
    // Obtener el estado del usuario
    $user_status = $this->get_user_status();
    $is_clocked_in = ($user_status['status'] === 'clocked_in');
    
    // Asignar clases CSS según el tema
    $theme = isset($atts['theme']) ? $atts['theme'] : 'default';
    $button_class = 'wp-time-clock-button';
    $container_class = 'wp-time-clock-container';
    
    if ($theme === 'modern') {
        $button_class .= ' wp-time-clock-button-modern';
        $container_class .= ' wp-time-clock-container-modern';
    } elseif ($theme === 'minimal') {
        $button_class .= ' wp-time-clock-button-minimal';
        $container_class .= ' wp-time-clock-container-minimal';
    }
    
    $button_class .= $is_clocked_in ? ' wp-time-clock-button-out' : ' wp-time-clock-button-in';
    
    // Textos del botón
    $text_in = isset($atts['text_in']) ? $atts['text_in'] : __('Fichar Entrada', 'wp-time-clock');
    $text_out = isset($atts['text_out']) ? $atts['text_out'] : __('Fichar Salida', 'wp-time-clock');
    $button_text = $is_clocked_in ? $text_out : $text_in;
    
    // Opción de mostrar reloj
    $show_time = isset($atts['show_time']) && $atts['show_time'] === 'yes';
    
    // Opción de mostrar estado
    $show_status = isset($atts['show_status']) && $atts['show_status'] === 'yes';
    
    // Iniciar buffer de salida
    ob_start();
    
    // HTML del componente
    ?>
    <div class="<?php echo esc_attr($container_class); ?>" data-status="<?php echo esc_attr($user_status['status']); ?>">
        
        <?php if ($show_time): ?>
        <div class="wp-time-clock-time" id="wp-time-clock-current-time">
            <?php echo esc_html(current_time('H:i:s')); ?>
        </div>
        <?php endif; ?>
        
        <?php if ($show_status): ?>
        <div class="wp-time-clock-status">
            <span class="wp-time-clock-status-label"><?php _e('Estado:', 'wp-time-clock'); ?></span>
            <span class="wp-time-clock-status-value"><?php echo esc_html($user_status['message']); ?></span>
            
            <?php if ($is_clocked_in): ?>
            <div class="wp-time-clock-elapsed">
                <span class="wp-time-clock-elapsed-label"><?php _e('Tiempo transcurrido:', 'wp-time-clock'); ?></span>
                <span class="wp-time-clock-elapsed-value" 
                     data-since="<?php echo esc_attr($user_status['since']); ?>"
                     data-seconds="<?php echo esc_attr($user_status['elapsed_seconds']); ?>">
                    <?php echo esc_html($user_status['elapsed']); ?>
                </span>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>
        
        <!-- Campo de nota siempre visible -->
        <div class="wp-time-clock-note-container">
            <textarea class="wp-time-clock-note" 
                     placeholder="<?php echo $is_clocked_in ? 
                                     esc_attr__('Nota de salida (opcional)', 'wp-time-clock') : 
                                     esc_attr__('Nota de entrada (opcional)', 'wp-time-clock'); ?>"></textarea>
        </div>
        
        <button class="<?php echo esc_attr($button_class); ?>"
                data-action="<?php echo $is_clocked_in ? 'clock_out' : 'clock_in'; ?>"
                data-nonce="<?php echo wp_create_nonce('wp_time_clock_nonce'); ?>">
            <?php echo esc_html($button_text); ?>
        </button>
        
        <div class="wp-time-clock-message"></div>
    </div>
    <?php
    
    return ob_get_clean();
}
    

}