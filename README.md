# Sistema de Postulación de Iniciativas CT-USM
El proyecto consiste en el desarrollo de un sistema web para gestionar postulaciones de iniciativas, permitiendo registrar información, administrar postulaciones, asignar evaluadores y realizar evaluaciones mediante una base de datos relacional.

El sistema fue desarrollado como un proyecto académico, utilizando PHP, MySQL, HTML y CSS.



## Funcionalidades implementadas
El sistema contempla tres roles de usuario:

### 1. Postulante
- Registrar nuevas postulaciones.
- Consultar las postulaciones realizadas.
- Editar y eliminar postulaciones según su estado.
- Enviar postulaciones para su evaluación.

### 2. Evaluador
- Consultar las postulaciones asignadas.
- Registrar evaluaciones y comentarios.
- Actualizar el estado de las postulaciones evaluadas.

### 3. Administrador
- Registrar y gestionar usuarios evaluadores.
- Administrar la asignación de evaluadores a las postulaciones.



## Funcionalidades generales
- Inicio y cierre de sesión.
- Control de acceso según el rol del usuario.
- Listado general de postulaciones.
- Búsqueda avanzada mediante distintos filtros.
- Consulta de información almacenada en la base de datos.



## Tecnologías usadas
- PHP: desarrollo de la lógica del sistema y procesamiento de solicitudes.
- MySQL: almacenamiento y gestión de la información.
- HTML: estructura de las interfaces web.
- CSS: diseño y presentación de las interfaces.
- SQL: consultas, relaciones, funciones, procedimientos almacenados, vistas y triggers.
- XAMPP: entorno local de desarrollo y ejecución.



## Estructura del repositorio
```text
CT-USM-Postulaciones/
├── BD/
│   ├── Base de datos normalizada.sql
│   ├── Function.sql
│   ├── Poblamiento.sql
│   ├── Procedimiento de almacenado.sql
│   ├── Trigger.sql
│   └── View.sql
│
├── PHP/
│   ├── css/
│   │   ├── busqueda_avanzada.css
│   │   ├── crear_postulacion.css
│   │   ├── dashboard.css
│   │   ├── evaluar_postulaciones.css
│   │   ├── gestionar_evaluadores.css
│   │   ├── listado_general.css
│   │   ├── login.css
│   │   └── mis_postulaciones.css
│   │
│   ├── busqueda_avanzada.php
│   ├── conexion.php
│   ├── crear_postulacion.php
│   ├── dashboard.php
│   ├── enviar_postulacion.php
│   ├── evaluar_postulaciones.php
│   ├── gestionar_evaluadores.php
│   ├── listado_general.php
│   ├── login.php
│   ├── mis_postulaciones.php
│   └── validar_login.php
│
├── .gitignore
└── README.md
```



## Base de datos
La base de datos utilizada por el sistema se denomina `ct_usm_postulaciones`.

Su diseño contempla tablas relacionadas para gestionar usuarios, empresas, postulaciones, evaluaciones, documentos, candidatos, participaciones y etapas de cronograma.

Los archivos SQL se encuentran en la carpeta `BD/`:
- `Base de datos normalizada.sql`: creación de la base de datos y sus tablas.
- `Poblamiento.sql`: incorporación de los datos ficticios utilizados en las pruebas.
- `Function.sql`: función para calcular la duración total de las etapas de una postulación.
- `Procedimiento de almacenado.sql`: procedimiento para registrar documentos asociados a postulaciones.
- `Trigger.sql`: validación de plazos positivos en las etapas del cronograma.
- `View.sql`: vista que reúne información de las postulaciones y sus relaciones.



## Instalación y ejecución
### Requisitos
Para ejecutar el sistema localmente se requiere:

- XAMPP con Apache y MySQL.
- PHP con extensión MySQLi.
- Navegador web.
- phpMyAdmin u otro cliente compatible con MySQL.


### Configuración
#### 1. Preparar el entorno
Instalar XAMPP y colocar la carpeta del proyecto dentro del directorio `htdocs/`.

#### 2. Iniciar los servicios
Iniciar Apache y MySQL desde el panel de control de XAMPP.

#### 3. Crear y poblar la base de datos
Importar los archivos SQL de `BD/` en el siguiente orden:

1. `Base de datos normalizada.sql`
2. `Poblamiento.sql`
3. `Function.sql`
4. `Procedimiento de almacenado.sql`
5. `Trigger.sql`
6. `View.sql`

#### 4. Configurar la conexión
Revisar el archivo `PHP/conexion.php` y ajustar los parámetros de conexión según la configuración local de MySQL.

#### 5. Acceder al sistema
Con los servicios iniciados y la base de datos configurada, acceder desde el navegador a:

`http://localhost/CT-USM-Postulaciones/PHP/login.php`

Los usuarios de demostración se definen en el archivo `BD/Poblamiento.sql`.



## Contexto académico
Proyecto desarrollado en equipo durante el primer semestre de 2026 para la asignatura Bases de Datos, correspondiente a la carrera Ingeniería Civil Informática de la Universidad Técnica Federico Santa María (USM).

El sistema corresponde a un prototipo académico elaborado con fines educativos. No está preparado para su utilización en un entorno de producción.