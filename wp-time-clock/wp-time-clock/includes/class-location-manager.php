<?php
/**
 * Gestiona las ubicaciones de los fichajes
 *
 * @since      1.0.0
 */
class WP_Time_Clock_Location_Manager {

    /**
     * Constructor
     *
     * @since    1.0.0
     */
    public function __construct() {
        // Inicialización
    }

    /**
     * Verifica si la geolocalización está habilitada
     *
     * @since    1.0.0
     * @return   bool    Verdadero si la geolocalización está habilitada
     */
    public function is_geolocation_enabled() {
        $clock_manager = new WP_Time_Clock_Manager();
        return $clock_manager->get_setting('geolocation_enabled', 'yes') === 'yes';
    }

    /**
     * Obtiene la dirección a partir de coordenadas
     *
     * @since    1.0.0
     * @param    float    $latitude     Latitud
     * @param    float    $longitude    Longitud
     * @return   string                 Dirección obtenida o cadena vacía si no se pudo obtener
     */
    public function get_address_from_coordinates($latitude, $longitude) {
        // Esta función utilizaría un servicio de geocodificación inversa
        // Por ahora, simplemente devolvemos las coordenadas formateadas
        if (!$latitude || !$longitude) {
            return '';
        }
        
        return sprintf('Lat: %s, Lng: %s', $latitude, $longitude);
        
        // Para implementar con un servicio real:
        /*
        $url = sprintf(
            'https://maps.googleapis.com/maps/api/geocode/json?latlng=%s,%s&key=%s',
            $latitude,
            $longitude,
            $api_key // Obtener de configuración
        );
        
        $response = wp_remote_get($url);
        
        if (is_wp_error($response)) {
            return '';
        }
        
        $data = json_decode(wp_remote_retrieve_body($response), true);
        
        if ($data['status'] !== 'OK') {
            return '';
        }
        
        return $data['results'][0]['formatted_address'] ?? '';
        */
    }

    /**
     * Analiza los datos de ubicación JSON
     *
     * @since    1.0.0
     * @param    string    $location_json    Datos de ubicación en formato JSON
     * @return   array                       Array con los datos de ubicación
     */
    public function parse_location_data($location_json) {
        if (empty($location_json)) {
            return array();
        }
        
        $location_data = json_decode($location_json, true);
        
        if (!is_array($location_data)) {
            return array();
        }
        
        return $location_data;
    }

    /**
     * Formatea la ubicación para mostrarla
     *
     * @since    1.0.0
     * @param    string    $location_json    Datos de ubicación en formato JSON
     * @return   string                      Ubicación formateada
     */
    public function format_location($location_json) {
        $location_data = $this->parse_location_data($location_json);
        
        if (empty($location_data)) {
            return __('Ubicación no disponible', 'wp-time-clock');
        }
        
        if (isset($location_data['latitude']) && isset($location_data['longitude'])) {
            // Si tenemos coordenadas, intentar obtener dirección
            $address = $this->get_address_from_coordinates(
                $location_data['latitude'],
                $location_data['longitude']
            );
            
            if (!empty($address)) {
                return $address;
            }
            
            // Si no se pudo obtener la dirección, mostrar coordenadas
            return sprintf(
                __('Lat: %s, Lng: %s', 'wp-time-clock'),
                $location_data['latitude'],
                $location_data['longitude']
            );
        }
        
        if (isset($location_data['ip'])) {
            // Si solo tenemos IP
            return sprintf(__('IP: %s', 'wp-time-clock'), $location_data['ip']);
        }
        
        return __('Ubicación no disponible', 'wp-time-clock');
    }

    /**
     * Obtiene la IP del usuario
     *
     * @since    1.0.0
     * @return   string    Dirección IP
     */
    public function get_user_ip() {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        }
        
        return sanitize_text_field($ip);
    }

    /**
     * Verifica si una ubicación está dentro de un radio permitido
     *
     * @since    1.0.0
     * @param    float    $latitude          Latitud del usuario
     * @param    float    $longitude         Longitud del usuario
     * @param    float    $center_latitude   Latitud del centro del radio
     * @param    float    $center_longitude  Longitud del centro del radio
     * @param    float    $radius_km         Radio en kilómetros
     * @return   bool                        Verdadero si está dentro del radio
     */
    public function is_within_radius($latitude, $longitude, $center_latitude, $center_longitude, $radius_km) {
        // Fórmula de Haversine para calcular distancia entre dos puntos en la Tierra
        $earth_radius_km = 6371; // Radio de la Tierra en km
        
        $dLat = deg2rad($center_latitude - $latitude);
        $dLon = deg2rad($center_longitude - $longitude);
        
        $a = sin($dLat/2) * sin($dLat/2) +
            cos(deg2rad($latitude)) * cos(deg2rad($center_latitude)) * 
            sin($dLon/2) * sin($dLon/2);
            
        $c = 2 * atan2(sqrt($a), sqrt(1-$a));
        $distance = $earth_radius_km * $c;
        
        return $distance <= $radius_km;
    }
}
