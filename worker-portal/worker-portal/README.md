# Estructura del Plugin Portal del Trabajador

## Descripción
Plugin modular para WordPress que implementa un portal personalizado para empleados con diversas funcionalidades: gestión de documentos personales, registro de gastos, hojas de trabajo diarias y sistema de incentivos.

## Estructura de Archivos

```
worker-portal/
│
├── worker-portal.php                   # Archivo principal del plugin
├── README.md                           # Documentación
├── uninstall.php                       # Script de desinstalación
│
├── includes/                           # Núcleo del plugin
│   ├── class-worker-portal.php         # Clase principal
│   ├── class-activator.php             # Clase de activación
│   ├── class-deactivator.php           # Clase de desactivación
│   ├── class-i18n.php                  # Internacionalización
│   ├── class-loader.php                # Cargador de hooks
│   ├── class-module-manager.php        # Gestor de módulos
│   └── class-database.php              # Gestión de base de datos
│
├── modules/                            # Módulos funcionales
│   ├── documents/                      # Módulo de Documentos
│   │   ├── class-documents.php
│   │   ├── templates/
│   │   └── js/
│   │
│   ├── expenses/                       # Módulo de Gastos
│   │   ├── class-expenses.php
│   │   ├── templates/
│   │   └── js/
│   │
│   ├── worksheets/                     # Módulo de Hojas de Trabajo
│   │   ├── class-worksheets.php
│   │   ├── templates/
│   │   └── js/
│   │
│   └── incentives/                     # Módulo de Incentivos
│       ├── class-incentives.php
│       ├── templates/
│       └── js/
│
├── admin/                              # Administración
│   ├── class-admin.php                 # Clase principal de admin
│   ├── js/
│   ├── css/
│   └── partials/                       # Plantillas de administración
│       ├── dashboard.php
│       ├── documents-settings.php
│       ├── expenses-settings.php
│       ├── worksheets-settings.php
│       └── incentives-settings.php
│
├── public/                             # Frontend
│   ├── class-public.php                # Clase principal de frontend
│   ├── js/
│   ├── css/
│   └── partials/                       # Plantillas de frontend
│       ├── portal-page.php
│       ├── documents-view.php
│       ├── expenses-view.php
│       ├── worksheets-view.php
│       └── incentives-view.php
│
└── languages/                          # Archivos de traducción
```

## Módulos Principales

### 1. Documentos (Mis Documentos)
- Repositorio de documentos personales para cada empleado
- Subida y descarga de archivos PDF (nóminas, contratos, etc.)
- Sistema de categorización de documentos
- Notificaciones de nuevos documentos

### 2. Gastos (Mis Gastos)
- Registro de gastos por parte del empleado
- Captura de imágenes de tickets/facturas
- Sistema de aprobación por parte de supervisores
- Categorización: kilometraje, dietas, desplazamiento, otros
- Informes y exportación de datos

### 3. Hojas de Trabajo
- Registro diario de actividades y tareas realizadas
- Asignación a proyectos/obras específicas
- Registro de materiales utilizados y horas dedicadas
- Sistema de validación por encargados o supervisores
- Informes y estadísticas de productividad

### 4. Incentivos (Mi Hucha)
- Cálculo automático de incentivos basados en productividad
- Visualización de métricas de rendimiento
- Historial de incentivos obtenidos
- Integración con sistema de nóminas

## Base de Datos

### Tablas principales

1. **wp_worker_documents**
   - id (bigint)
   - user_id (bigint)
   - title (varchar)
   - description (text)
   - file_path (varchar)
   - category (varchar)
   - upload_date (datetime)
   - status (varchar)

2. **wp_worker_expenses**
   - id (bigint)
   - user_id (bigint)
   - report_date (datetime)
   - expense_date (date)
   - expense_type (varchar)
   - description (text)
   - amount (decimal)
   - has_receipt (tinyint)
   - receipt_path (varchar)
   - status (varchar)
   - approved_by (bigint)
   - approved_date (datetime)

3. **wp_worker_worksheets**
   - id (bigint)
   - user_id (bigint)
   - work_date (date)
   - project_id (bigint)
   - difficulty (varchar)
   - system_type (varchar)
   - unit_type (varchar)
   - quantity (decimal)
   - hours (decimal)
   - notes (text)
   - status (varchar)
   - validated_by (bigint)
   - validated_date (datetime)

4. **wp_worker_incentives**
   - id (bigint)
   - user_id (bigint)
   - worksheet_id (bigint)
   - calculation_date (datetime)
   - description (text)
   - amount (decimal)
   - status (varchar)
   - approved_by (bigint)
   - approved_date (datetime)

5. **wp_worker_projects**
   - id (bigint)
   - name (varchar)
   - description (text)
   - location (varchar)
   - start_date (date)
   - end_date (date)
   - status (varchar)

## Integración con Plugins Existentes

- **Staff Calendar**: Compartir información de ubicación y asignación a proyectos
- **WP Time Clock**: Validación cruzada de horas trabajadas y fichajes

## Roles y Permisos

1. **Empleado**
   - Ver sus propios documentos, gastos, hojas de trabajo e incentivos
   - Registrar nuevos gastos y hojas de trabajo
   - Descargar documentos personales

2. **Supervisor/Encargado**
   - Aprobar/denegar gastos y hojas de trabajo
   - Ver informes de su equipo
   - Subir documentos para su equipo

3. **Administrador**
   - Gestión completa del sistema
   - Configuración de parámetros
   - Informes globales

## Shortcodes

- `[worker_portal]` - Portal completo
- `[worker_documents]` - Sección de documentos
- `[worker_expenses]` - Sección de gastos
- `[worker_worksheets]` - Sección de hojas de trabajo
- `[worker_incentives]` - Sección de incentivos
