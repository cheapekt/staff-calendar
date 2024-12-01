# Staff Calendar Plugin para WordPress

## Descripción
Staff Calendar es un plugin de WordPress que permite gestionar un calendario laboral para todos los usuarios registrados en el sitio. El plugin proporciona dos niveles de acceso: administrador y suscriptor, permitiendo a los administradores gestionar los destinos de trabajo de los usuarios mientras que los suscriptores pueden visualizar la información.

## Características Principales

### Para Administradores
- Panel de administración dedicado
- Edición de destinos de trabajo para cada usuario
- Vista de calendario mensual completa
- Guardado automático de cambios
- Navegación intuitiva entre meses
- Gestión de múltiples usuarios simultáneamente

### Para Usuarios (Subscribers)
- Vista de calendario de solo lectura
- Visualización de destinos propios y de compañeros
- Navegación mes a mes
- Interfaz responsive

## Requisitos Técnicos
- WordPress 5.0 o superior
- PHP 7.4 o superior
- MySQL 5.6 o superior
- JavaScript habilitado en el navegador

## Instalación

1. Descarga el plugin y descomprime el archivo en la carpeta `/wp-content/plugins/`
2. Activa el plugin desde el panel de administración de WordPress
3. El plugin creará automáticamente la tabla necesaria en la base de datos

### Instalación Manual
```bash
# Navega a la carpeta de plugins de WordPress
cd wp-content/plugins

# Crea la carpeta del plugin
mkdir staff-calendar

# Copia los archivos del plugin a la carpeta
cp -r /ruta/a/los/archivos/* staff-calendar/
```

## Uso

### Para Administradores

1. Accede al panel de administración de WordPress
2. Encuentra "Staff Calendar" en el menú lateral
3. Para editar destinos:
   - Navega al mes deseado usando las flechas
   - Haz clic en cualquier celda para editar el destino
   - Los cambios se guardan automáticamente

### Para Usuarios

1. El calendario se puede visualizar en cualquier página donde se haya insertado el shortcode
2. Uso del shortcode:
```
[staff_calendar]
```

## Estructura de Archivos
```
staff-calendar/
├── staff-calendar.php
├── README.md
├── templates/
│   ├── admin-view.php
│   └── frontend-view.php
├── js/
│   ├── admin-script.js
│   └── frontend-script.js
├── css/
│   ├── admin-style.css
│   └── frontend-style.css
```

## Personalización

### Estilos CSS
Los estilos pueden ser personalizados modificando los archivos CSS en la carpeta `css/`:
- `admin-style.css` para el panel de administración
- `frontend-style.css` para la vista de usuario

### Filtros y Acciones
El plugin proporciona varios filtros y acciones para extender su funcionalidad:

```php
// Ejemplo de filtro para modificar los destinos disponibles
add_filter('staff_calendar_destinations', function($destinations) {
    return array_merge($destinations, ['Nueva Ubicación']);
});
```

## Seguridad

- Validación de nonce en todas las peticiones AJAX
- Sanitización de datos de entrada
- Verificación de permisos de usuario
- Escape de datos en la salida
- Consultas preparadas para la base de datos

## Solución de Problemas

### Problemas Comunes

1. El calendario no se muestra
   - Verifica que el shortcode está correctamente insertado
   - Comprueba que JavaScript está habilitado en el navegador

2. Los cambios no se guardan
   - Verifica los permisos de usuario
   - Comprueba los logs de error de PHP
   - Asegúrate de que las peticiones AJAX funcionan correctamente

3. Problemas de visualización
   - Actualiza los archivos CSS
   - Verifica conflictos con el tema actual
   - Comprueba la consola del navegador para errores JavaScript

## Actualización
1. Desactiva el plugin
2. Reemplaza los archivos del plugin con la nueva versión
3. Activa el plugin
4. Verifica que la base de datos se ha actualizado correctamente

## Base de Datos
El plugin crea una tabla personalizada:
```sql
wp_staff_calendar
- id (mediumint)
- user_id (bigint)
- work_date (date)
- destination (varchar)
```

## Desarrolladores

### Hooks Disponibles
```php
// Antes de actualizar un destino
do_action('before_update_staff_destination', $user_id, $work_date, $destination);

// Después de actualizar un destino
do_action('after_update_staff_destination', $user_id, $work_date, $destination);
```

### Filtros Disponibles
```php
// Modificar los datos del calendario antes de mostrarlos
apply_filters('staff_calendar_data', $calendar_data);

// Personalizar los permisos de usuario
apply_filters('staff_calendar_user_can_edit', $can_edit, $user_id);
```

## Contribución
Las contribuciones son bienvenidas. Por favor:
1. Crea un fork del repositorio
2. Crea una rama para tu característica
3. Envía un pull request

## Licencia
Este plugin está licenciado bajo GPL v2 o posterior.

## Soporte
Para soporte técnico o preguntas:
1. Revisa la documentación
2. Abre un issue en el repositorio
3. Contacta con el equipo de desarrollo

## Créditos
Desarrollado por [Tu Nombre/Empresa]
Versión 1.0
