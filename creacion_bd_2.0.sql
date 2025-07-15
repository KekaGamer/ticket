--
-- Base de datos: `cma110690_masercom_tickets`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `adjuntos`
--

CREATE TABLE `adjuntos` (
  `id` int(11) NOT NULL,
  `id_respuesta` int(11) DEFAULT NULL,
  `id_ticket` int(11) DEFAULT NULL,
  `nombre_archivo` varchar(255) NOT NULL,
  `ruta_archivo` varchar(255) NOT NULL,
  `tipo_archivo` varchar(50) DEFAULT NULL,
  `tamanio` int(11) DEFAULT NULL,
  `fecha_creacion` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `areas`
--

CREATE TABLE `areas` (
  `id` int(11) NOT NULL,
  `id_unidad` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `estado` tinyint(1) DEFAULT 1,
  `fecha_creacion` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Volcado de datos para la tabla `areas`
--

INSERT INTO `areas` (`id`, `id_unidad`, `nombre`, `descripcion`, `estado`, `fecha_creacion`) VALUES
(1, 1, 'MYSQL', '', 1, '2025-07-11 18:20:29'),
(2, 1, 'SQLSERVER', '', 1, '2025-07-11 18:20:35'),
(3, 2, 'GESTION USUARIOS AD', '', 1, '2025-07-11 18:20:50'),
(4, 7, 'DESARROLLO WEB', '', 1, '2025-07-11 18:21:03'),
(5, 7, 'DESARROLLO APP', '', 1, '2025-07-11 18:21:12'),
(6, 5, 'CABLEADO ESTRUCTURADO', '', 1, '2025-07-11 18:21:37'),
(7, 6, 'REDES AP POINT', '', 1, '2025-07-11 18:21:51'),
(8, 3, 'SOPORTE GERENCIA', '', 1, '2025-07-11 18:22:07'),
(9, 4, 'SOPORTE TI', '', 1, '2025-07-11 18:22:19');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `categorias_tickets`
--

CREATE TABLE `categorias_tickets` (
  `id` int(11) NOT NULL,
  `parent_id` int(11) DEFAULT NULL,
  `nombre` varchar(100) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `plantilla` text DEFAULT NULL,
  `icono_fa` varchar(50) DEFAULT 'fas fa-concierge-bell',
  `estado` tinyint(1) DEFAULT 1,
  `fecha_creacion` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Volcado de datos para la tabla `categorias_tickets`
--

INSERT INTO `categorias_tickets` (`id`, `parent_id`, `nombre`, `descripcion`, `plantilla`, `icono_fa`, `estado`, `fecha_creacion`) VALUES
(3, 6, 'Crear Usuario de BD', '', '[separador: Detalles del Nuevo Usuario]\r\n[texto: Nombre de la Base de Datos a la que tendrá acceso]\r\n[texto: Nombre del nuevo usuario (ej: usr_reportes)]\r\n[texto: Contraseña para el nuevo usuario]\r\n[opciones: Nivel de Permisos|Solo Lectura (SELECT)|Lectura y Escritura (SELECT, INSERT, UPDATE)]\r\n[checkbox: Se tiene autorizacion VB Jefatura que sera enviado al correo.]', '', 1, '2025-07-13 21:14:33'),
(4, 6, 'Modificar Usuario de BD', '', '[separador: Identificación del Usuario]\r\n[texto: Nombre del usuario a modificar]\r\n[texto: Base de Datos asociada]\r\n[area: Describa los cambios a realizar (ej: \"Resetear contraseña\", \"Añadir permiso de DELETE\")]\r\n[checkbox: Se tiene autorizacion VB Jefatura que sera enviado al correo.]', '', 1, '2025-07-13 22:40:37'),
(5, 6, 'Eliminar Usuario de BD', '', '[separador: Identificación del Usuario]\r\n[texto: Nombre del usuario a eliminar]\r\n[texto: Base de Datos asociada]\r\n[area: Motivo de la eliminación]\r\n[checkbox: Confirmo que se ha realizado un respaldo de la BD importantes del servidor correspondiente.]\r\n[checkbox: Se tiene autorizacion VB Jefatura que sera enviado al correo.]\r\n[checkbox: Entiendo que esta acción es permanente y no se puede deshacer.]', '', 1, '2025-07-13 22:41:17'),
(6, NULL, 'Gestión de Bases de Datos (MySQL - SQLServer)', 'Solicitudes para crear, modificar o eliminar bases de datos y usuarios.', '', 'fas fa-database', 1, '2025-07-13 22:44:39'),
(7, 6, 'Solicitud Respaldo BD', '', '[separador: Solicitud de Backup BD]\r\n[texto: Nombre de la Base de Datos para el respaldo]\r\n[opciones: Formato del Backup|Archivo .sql (estándar)|Archivo .zip (comprimido)]\r\n[fecha: Fecha para la cual necesita el respaldo]', '', 1, '2025-07-13 22:55:15'),
(8, 6, 'Crear Base de Datos', '', '[separador: Detalles de la Nueva Base de Datos]\r\n[texto: Nombre de la Base de Datos (sin espacios ni caracteres especiales)]\r\n[texto: Nombre del Usuario principal para esta BD]\r\n[opciones: Entorno|Producción|Desarrollo|Pruebas (QA)]\r\n[area: Propósito o aplicación que usará esta BD]\r\n[checkbox: Se tiene autorizacion VB Jefatura que sera enviado al correo.]', '', 1, '2025-07-13 22:56:51'),
(9, 6, 'Eliminar Base de Datos', '', '[separador: ¡ADVERTENCIA! - ACCIÓN IRREVERSIBLE]\r\n[texto: Nombre exacto de la Base de Datos a eliminar]\r\n[texto: Por seguridad, vuelva a escribir el nombre de la BD para confirmar]\r\n[area: Motivo de la eliminación]\r\n[checkbox: Confirmo que tengo un respaldo y entiendo que se borrará toda la información permanentemente.]\r\n[checkbox: Confirmo que se ha realizado un respaldo de la BD importantes del servidor correspondiente.]\r\n[checkbox: Se tiene autorizacion VB Jefatura que sera enviado al correo.]\r\n', '', 1, '2025-07-13 22:58:41'),
(10, NULL, 'Gestión de Usuarios (Servidores UNIX/Linux)', 'Solicitudes para crear, modificar o eliminar usuarios en servidores con sistema operativo Linux.', '', 'fas fa-user-shield', 1, '2025-07-13 23:18:01'),
(11, 10, 'Crear Usuario UNIX', '', '[separador: Información del Servidor]\r\n[texto: Nombre del Servidor o Dirección IP]\r\n[opciones: Entorno|Producción|Desarrollo|Pruebas (QA)]\r\n\r\n[separador: Detalles del Nuevo Usuario]\r\n[texto: Nombre de usuario (login, ej: jperez)]\r\n[texto: Nombre Completo (ej: Juan Pérez)]\r\n[texto: Contraseña Temporal (se solicitará cambio en el primer login)]\r\n\r\n[separador: Permisos y Accesos]\r\n[checkbox: ¿Necesita acceso SSH?]\r\n[checkbox: ¿Necesita acceso SFTP/FTP?]\r\n[area: Grupos a los que debe pertenecer (ej: www-data, developers)]\r\n[texto: Directorio personal (Home) (ej: /home/jperez)]', '', 1, '2025-07-13 23:18:37'),
(12, 10, 'Modificar Usuario UNIX', '', '[separador: Identificación del Usuario y Servidor]\r\n[texto: Nombre del Servidor o Dirección IP donde reside el usuario]\r\n[texto: Nombre de usuario (login) a modificar]\r\n\r\n[separador: Modificaciones Requeridas]\r\n[area: Describa detalladamente los cambios (ej: \"Añadir al grupo \'developers\'\", \"Resetear contraseña\", \"Cambiar directorio personal\")]', '', 1, '2025-07-13 23:19:04'),
(13, 10, 'Eliminar Usuario UNIX', '', '[separador: ¡ADVERTENCIA! Esta acción es irreversible]\r\n[area: La eliminación de un usuario borrará su directorio personal y todos sus archivos de forma permanente.]\r\n\r\n[separador: Identificación del Usuario y Servidor]\r\n[texto: Nombre del Servidor o Dirección IP]\r\n[texto: Nombre de usuario (login) a eliminar]\r\n\r\n[separador: Confirmación]\r\n[checkbox: Confirmo que se ha realizado un respaldo de los datos importantes de este usuario.]\r\n[checkbox: Entiendo que esta acción es permanente y no se puede deshacer.]', '', 1, '2025-07-13 23:19:19'),
(14, NULL, 'Infraestructura de Servidores', 'Solicitudes para la instalación física o virtual de servidores y sus sistemas operativos.', '', 'fas fa-server', 1, '2025-07-13 23:19:37'),
(15, 14, 'Instalación de Servidor (Hardware)', '', '[separador: Tipo de Servidor]\r\n[opciones: Formato del Servidor|Rack|Torre|Virtual (VM)]\r\n[opciones: Entorno de Uso|Producción|Desarrollo|Pruebas (QA)]\r\n\r\n[separador: Especificaciones Técnicas Requeridas]\r\n[texto: Cantidad de vCPU o Cores Físicos]\r\n[texto: Cantidad de Memoria RAM (ej: 32 GB)]\r\n[texto: Almacenamiento Principal (ej: 500 GB NVMe SSD)]\r\n[texto: Almacenamiento Secundario (si aplica)]\r\n\r\n[separador: Red e Información Adicional]\r\n[texto: Ubicación Física (Rack y U) o Hypervisor (si es VM)]\r\n[area: Comentarios adicionales sobre la configuración]', '', 1, '2025-07-13 23:20:03'),
(16, 14, 'Instalación de Sistema Operativo', '', '[separador: Servidor Destino]\r\n[texto: Nombre o IP del Servidor donde se instalará el S.O.]\r\n\r\n[separador: Selección del Sistema Operativo]\r\n[opciones: Familia de S.O.|Microsoft Windows Server|Ubuntu Server|Debian Server]\r\n\r\n[opciones: Versión de Windows Server|Windows Server 2025|Windows Server 2022|Windows Server 2019]\r\n[opciones: Versión de Ubuntu Server|Ubuntu 24.04 LTS (Noble Numbat)|Ubuntu 22.04 LTS (Jammy Jellyfish)|Ubuntu 20.04 LTS (Focal Fossa)]\r\n[opciones: Versión de Debian Server|Debian 12 (Bookworm)|Debian 11 (Bullseye)]\r\n\r\n[separador: Configuración Inicial]\r\n[texto: Nombre de Host para el servidor (ej: SRV-WEB-01)]\r\n[texto: Contraseña para la cuenta de Administrador/root]', '', 1, '2025-07-13 23:20:20'),
(17, NULL, 'Instalación de Servicios de Servidor', 'Instalación y configuración de software de servidor como paneles de control, correo, web, etc.', '', 'fas fa-cogs', 1, '2025-07-13 23:20:42'),
(18, 17, 'Instalación de cPanel/WHM', '', '[separador: Servidor Destino]\r\n[texto: Nombre o IP del Servidor donde se instalará cPanel]\r\n[texto: Licencia de cPanel (si ya la posee)]\r\n[area: Comentarios o configuraciones específicas requeridas]', '', 1, '2025-07-13 23:21:02'),
(19, 17, 'Instalación de Servidor de Correo', '', '[separador: Servidor Destino]\r\n[texto: Nombre o IP del Servidor]\r\n[opciones: Software de Correo|Postfix con Dovecot|Microsoft Exchange|Otro (especificar)]\r\n[texto: Si es \"Otro\", especifique cuál]\r\n[texto: Dominio principal para el correo (ej: masercom.cl)]', '', 1, '2025-07-13 23:21:25'),
(20, 17, 'Instalación de Servidor Web', '', '[separador: Servidor Destino]\r\n[texto: Nombre o IP del Servidor]\r\n[opciones: Software de Servidor Web|Apache|Nginx|LiteSpeed]\r\n[texto: Dominio principal que alojará (ej: masercom-qa.cl)]\r\n[checkbox: ¿Requiere configuración de certificado SSL (HTTPS)?]', '', 1, '2025-07-13 23:21:42'),
(21, 17, 'Configuración de Firewall', '', '[separador: Servidor o Equipo a Proteger]\r\n[texto: Nombre o IP del equipo]\r\n[opciones: Tipo de Firewall|Software (UFW, iptables)|Hardware (Fortinet, Cisco)]\r\n[area: Describa las reglas requeridas (ej: \"Permitir tráfico entrante en el puerto 443 desde cualquier IP\", \"Bloquear acceso al puerto 3306 excepto desde la IP 192.168.1.100\")]', '', 1, '2025-07-13 23:22:00'),
(22, NULL, 'Software de Desarrollo', 'Instalación y configuración de herramientas y entornos de desarrollo.', '', 'fas fa-code', 1, '2025-07-13 23:22:34'),
(23, 22, 'Instalación de Node.js', '', '[texto: Nombre o IP del equipo destino]\r\n[opciones: Versión de Node.js|Última versión LTS|Versión específica (indicar en comentarios)]\r\n[area: Si necesita una versión específica, por favor indíquela aquí]', '', 1, '2025-07-13 23:22:56'),
(24, 22, 'Instalación de Visual Studio Code', '', '[texto: Nombre del equipo o computador donde se instalará]\r\n[area: ¿Necesita alguna extensión específica preinstalada? (ej: \"Docker\", \"Python\", \"Prettier\")]', '', 1, '2025-07-13 23:23:16'),
(25, 22, 'Instalación de XAMPP / AppServ', '', '[texto: Nombre del equipo o computador donde se instalará]\r\n[opciones: Paquete de Instalación|XAMPP|AppServ]\r\n[checkbox: ¿Necesita que se configuren hosts virtuales adicionales?]', '', 1, '2025-07-13 23:23:35'),
(26, NULL, 'Infraestructura de Red', 'Solicitudes relacionadas con conectividad, cableado y permisos de red.', '', 'fas fa-network-wired', 1, '2025-07-13 23:24:01'),
(27, 26, 'Revisión/Habilitación de Punto de Red', '', '[texto: Número de oficina o ubicación del punto de red]\r\n[texto: Código del punto de red (impreso en la placa de pared)]\r\n[area: Describa el problema o la solicitud (ej: \"No tengo conexión\", \"Necesito que se active este punto\")]', '', 1, '2025-07-13 23:24:18'),
(28, 26, 'Permisos de Firewall / VPN / Proxy', '', '[opciones: Tipo de Permiso|Firewall|VPN|Proxy]\r\n[texto: IP de Origen (su equipo)]\r\n[texto: IP/URL de Destino a la que necesita acceder]\r\n[texto: Puerto(s) requeridos (ej: 80, 443, 3306)]\r\n[area: Justificación del acceso]', '', 1, '2025-07-13 23:24:34'),
(29, NULL, 'Soporte de Cámaras de Seguridad (CCTV)', 'Solicitudes para cotización, instalación o mantenimiento de equipos de CCTV.', '', 'fas fa-video', 1, '2025-07-13 23:24:57'),
(30, 29, 'Cotización de Nuevo Sistema CCTV', '', '[texto: Cantidad de cámaras requeridas]\r\n[opciones: Tipo de Cámara|Domo|Bala (Bullet)|PTZ (Móvil)]\r\n[area: Describa las áreas que necesitan cobertura]', '', 1, '2025-07-13 23:25:18'),
(31, 29, 'Mantenimiento o Cambio de Equipo', '', '[opciones: Tipo de Solicitud|Cambio de Cámara Defectuosa|Cambio de DVR/XVR|Revisión de Cableado]\r\n[texto: Código o ubicación de la cámara/equipo con problemas]\r\n[area: Describa la falla que presenta el equipo]', '', 1, '2025-07-13 23:25:39'),
(32, NULL, 'Gestión de Active Directory', 'Solicitudes para crear, modificar o gestionar cuentas de usuario en el dominio de la empresa.', '', 'fas fa-address-book', 1, '2025-07-13 23:26:09'),
(33, 32, 'Crear Usuario en Active Directory', '', '[separador: Datos del Nuevo Colaborador]\r\n[texto: Nombre Completo]\r\n[texto: Rut o DNI]\r\n[texto: Cargo que desempeñará]\r\n[texto: Departamento o Área]\r\n[texto: Manager o Jefatura Directa]\r\n\r\n[separador: Detalles de la Cuenta]\r\n[texto: Nombre de usuario sugerido (ej: jperez)]\r\n[opciones: Oficina o Sucursal|Casa Matriz|Sucursal Norte|Sucursal Sur]\r\n[area: Observaciones (ej: \"Usuario temporal hasta fin de mes\", \"Necesita acceso a las mismas carpetas que el usuario \'antonio.r\'\")]', '', 1, '2025-07-13 23:26:25'),
(34, 32, 'Asignar Permisos a Usuario', '', '[separador: Identificación del Usuario]\r\n[texto: Nombre de usuario (login) al que se le asignarán permisos]\r\n\r\n[separador: Permisos Requeridos]\r\n[area: Describa detalladamente los permisos necesarios (ej: \"Acceso de escritura a la carpeta compartida \'Contabilidad\'\", \"Permisos para instalar software\")]\r\n[texto: Nombre de otro usuario como referencia (ej: \"Asignar los mismos permisos que \'mlopez\'\")]', '', 1, '2025-07-13 23:26:48'),
(35, 32, 'Eliminar Usuario de Active Directory', '', '[separador: ¡ADVERTENCIA! Esta acción es definitiva]\r\n[area: Se eliminará la cuenta del usuario y sus accesos. La cuenta de correo y los archivos en OneDrive/Google Drive deben gestionarse por separado.]\r\n\r\n[separador: Identificación del Usuario a Eliminar]\r\n[texto: Nombre de usuario (login) a eliminar]\r\n[texto: Nombre Completo del usuario desvinculado]\r\n[fecha: Fecha de término de contrato]\r\n[checkbox: Confirmo que los datos importantes de este usuario han sido respaldados.]', '', 1, '2025-07-13 23:27:03'),
(36, 32, 'Desactivar Usuario (Temporal)', '', '[separador: Identificación del Usuario]\r\n[texto: Nombre de usuario (login) a desactivar]\r\n\r\n[separador: Detalles de la Desactivación]\r\n[area: Motivo de la desactivación temporal (ej: \"Licencia médica prolongada\", \"Vacaciones\")]\r\n[fecha: Fecha de inicio de la desactivación]\r\n[fecha: Fecha estimada de reactivación (si se conoce)]', '', 1, '2025-07-13 23:27:18'),
(37, 32, 'Asignar Grupos a Usuario', '', '[separador: Identificación del Usuario]\r\n[texto: Nombre de usuario (login) a modificar]\r\n\r\n[separador: Grupos de Seguridad]\r\n[area: Liste los nombres de los grupos de seguridad a los que se debe añadir o quitar al usuario (ej: \"Añadir a \'GG_Ventas\'\", \"Quitar de \'GG_Proyectos_Antiguos\'\")]', '', 1, '2025-07-13 23:27:33'),
(38, NULL, 'Soporte Técnico a Usuarios (Windows)', 'Asistencia para problemas, mantenimiento o formateo de equipos con sistema operativo Windows.', '', 'fas fa-laptop-medical', 1, '2025-07-13 23:27:52'),
(39, 38, 'Formateo de Equipo', '', '[separador: ¡ADVERTENCIA! Se borrarán todos los datos]\r\n[area: El formateo eliminará todos los archivos y programas del equipo. Es responsabilidad del usuario respaldar su información importante antes de entregar el equipo.]\r\n\r\n[separador: Identificación del Equipo]\r\n[texto: Nombre del Equipo o número de inventario (si lo conoce)]\r\n[texto: Nombre del usuario principal del equipo]\r\n\r\n[separador: Confirmación de Respaldo]\r\n[checkbox: Confirmo que he respaldado todos mis archivos importantes y autorizo el formateo del equipo.]\r\n[area: Liste el software específico que necesita que sea reinstalado (además de Office y las herramientas estándar)]\r\n', '', 1, '2025-07-13 23:28:09'),
(40, 38, 'Mantención de Equipo', '', '[separador: Identificación del Equipo]\r\n[texto: Nombre del Equipo o número de inventario]\r\n[texto: Nombre del usuario principal]\r\n\r\n[separador: Tipo de Mantención Requerida]\r\n[opciones: Tipo de Mantención|Limpieza física (polvo, ventiladores)|Optimización de sistema (software)|Ambas]\r\n[area: Describa cualquier problema específico que justifique la mantención (ej: \"El equipo se sobrecalienta mucho\", \"Tarda demasiado en encender\")]', '', 1, '2025-07-13 23:28:25'),
(41, 38, 'Revisión Aplicativo Office', '', '[separador: Identificación del Problema]\r\n[opciones: Aplicación de Office con problemas|Microsoft Word|Microsoft Excel|Microsoft Outlook|PowerPoint|Otro]\r\n[texto: Si es \"Otro\", por favor especifique cuál]\r\n[area: Describa detalladamente el error o problema que está experimentando (si hay un mensaje de error, por favor cópielo textualmente aquí)]\r\n[checkbox: ¿El problema ocurre con un archivo específico o con todos?]', '', 1, '2025-07-13 23:28:38'),
(42, 38, 'Revisión Lentitud de Equipo', '', '[separador: Descripción del Problema]\r\n[area: Por favor, describa en qué momentos nota la lentitud (ej: \"Al encender el equipo\", \"Al abrir muchas pestañas en Chrome\", \"Cuando uso Excel con archivos grandes\")]\r\n\r\n[separador: Verificaciones Realizadas]\r\n[checkbox: Ya he reiniciado el equipo y el problema persiste.]\r\n[texto: ¿Desde cuándo nota este problema de lentitud?]', '', 1, '2025-07-13 23:28:54'),
(43, 6, 'Modificar Base de Datos', '', '[separador: Detalles de la Modificación]\r\n[texto: Nombre de la Base de Datos a modificar]\r\n[area: Describa los cambios requeridos (ej: cambiar cotejamiento a utf8mb4_general_ci, optimizar tablas)]', '', 1, '2025-07-13 23:40:27');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `empresas`
--

CREATE TABLE `empresas` (
  `id` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `direccion` text DEFAULT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `estado` tinyint(1) DEFAULT 1,
  `fecha_creacion` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Volcado de datos para la tabla `empresas`
--

INSERT INTO `empresas` (`id`, `nombre`, `direccion`, `telefono`, `estado`, `fecha_creacion`) VALUES
(1, 'MASERCOM', 'FIGUERAS 8118, OFICINA 433, RENCA, RM', '+56228645254', 1, '2025-07-11 18:17:32');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `gerencias`
--

CREATE TABLE `gerencias` (
  `id` int(11) NOT NULL,
  `id_empresa` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `estado` tinyint(1) DEFAULT 1,
  `fecha_creacion` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Volcado de datos para la tabla `gerencias`
--

INSERT INTO `gerencias` (`id`, `id_empresa`, `nombre`, `descripcion`, `estado`, `fecha_creacion`) VALUES
(1, 1, 'GERENCIA INFORMATICA', '', 1, '2025-07-11 18:17:54'),
(2, 1, 'GERENCIA GESTION IDENTIDADES', '', 1, '2025-07-11 18:18:12'),
(3, 1, 'GERENCIA REDES Y COMUNICACIONES', '', 1, '2025-07-11 18:18:24'),
(4, 1, 'GERENCIA GESTION DE DATOS', '', 1, '2025-07-11 18:18:39');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `logs`
--

CREATE TABLE `logs` (
  `id` int(11) NOT NULL,
  `id_usuario` int(11) DEFAULT NULL,
  `accion` varchar(255) NOT NULL,
  `detalles` text DEFAULT NULL,
  `ip` varchar(45) DEFAULT NULL,
  `fecha_creacion` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `respuestas_tickets`
--

CREATE TABLE `respuestas_tickets` (
  `id` int(11) NOT NULL,
  `id_ticket` int(11) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `mensaje` text NOT NULL,
  `adjuntos` text DEFAULT NULL,
  `fecha_creacion` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tickets`
--

CREATE TABLE `tickets` (
  `id` int(11) NOT NULL,
  `id_cliente` int(11) NOT NULL,
  `id_tecnico` int(11) DEFAULT NULL,
  `id_categoria` int(11) DEFAULT NULL,
  `id_empresa` int(11) DEFAULT NULL,
  `id_area` int(11) DEFAULT NULL,
  `titulo` varchar(255) NOT NULL,
  `descripcion` text NOT NULL,
  `prioridad` enum('baja','media','alta','critica') DEFAULT 'media',
  `estado` enum('abierto','pendiente','cerrado','reabierto') DEFAULT 'abierto',
  `fecha_creacion` datetime DEFAULT current_timestamp(),
  `fecha_actualizacion` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  `fecha_cierre` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Volcado de datos para la tabla `tickets`
--

INSERT INTO `tickets` (`id`, `id_cliente`, `id_tecnico`, `id_categoria`, `id_empresa`, `id_area`, `titulo`, `descripcion`, `prioridad`, `estado`, `fecha_creacion`, `fecha_actualizacion`, `fecha_cierre`) VALUES
(1, 3, NULL, 33, NULL, NULL, 'Prueba', '**Detalles de la Solicitud:**\r\n\r\n** Nombre Completo:**\r\nRodrigo Gonzalez Cerpa\r\n\r\n** Rut o DNI:**\r\n178779206\r\n\r\n** Cargo que desempeñará:**\r\nGerente\r\n\r\n** Departamento o Área:**\r\nGerente\r\n\r\n** Manager o Jefatura Directa:**\r\nRodrigo\r\n\r\n** Nombre de usuario sugerido (ej: jperez):**\r\nexrgcer\r\n\r\n**Oficina o Sucursal:**\r\nCasa Matriz\r\n\r\n** Observaciones (ej: :**\r\nTodos los permisos', 'media', 'abierto', '2025-07-14 11:16:11', NULL, NULL),
(2, 3, NULL, 8, NULL, NULL, 'Prueba', '**Detalles de la Solicitud:**\r\n\r\n** Nombre de la Base de Datos (sin espacios ni caracteres especiales):**\r\nprueba\r\n\r\n** Nombre del Usuario principal para esta BD:**\r\nprueba\r\n\r\n**Entorno:**\r\nPruebas (QA)\r\n\r\n** Propósito o aplicación que usará esta BD:**\r\nprueba\r\n\r\n[x]  Se tiene autorizacion VB Jefatura que sera enviado al correo.', 'media', 'abierto', '2025-07-14 11:16:40', NULL, NULL),
(3, 3, NULL, 37, NULL, NULL, 'Modificar Perfil de Usuario en AD', '**Detalles de la Solicitud:**\r\n\r\n** Nombre de usuario (login) a modificar:**\r\nexrgcer\r\n\r\n** Liste los nombres de los grupos de seguridad a los que se debe añadir o quitar al usuario (ej: :**\r\nAñadir al grupo Gerencia/informatica', 'media', 'abierto', '2025-07-14 16:05:45', NULL, NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `unidades`
--

CREATE TABLE `unidades` (
  `id` int(11) NOT NULL,
  `id_gerencia` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `estado` tinyint(1) DEFAULT 1,
  `fecha_creacion` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Volcado de datos para la tabla `unidades`
--

INSERT INTO `unidades` (`id`, `id_gerencia`, `nombre`, `descripcion`, `estado`, `fecha_creacion`) VALUES
(1, 4, 'BASE DE DATOS', '', 1, '2025-07-11 18:18:55'),
(2, 2, 'SOPORTE NIVEL 3', '', 1, '2025-07-11 18:19:06'),
(3, 1, 'SOPORTE NIVEL 2 VIP', '', 1, '2025-07-11 18:19:18'),
(4, 1, 'SOPORTE TECNICO NIVEL 1', '', 1, '2025-07-11 18:19:30'),
(5, 3, 'REDES INFRAESTRUCTURA', '', 1, '2025-07-11 18:19:49'),
(6, 3, 'REDES LOGICO', '', 1, '2025-07-11 18:20:02'),
(7, 1, 'DESARROLLO API', '', 1, '2025-07-11 18:20:13');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL,
  `id_empresa` int(11) DEFAULT NULL,
  `id_area` int(11) DEFAULT NULL,
  `nombre` varchar(100) NOT NULL,
  `apellido` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `rol` enum('admin','tecnico','cliente') NOT NULL,
  `foto_perfil` varchar(255) DEFAULT NULL,
  `estado` tinyint(1) DEFAULT 1,
  `ultimo_login` datetime DEFAULT NULL,
  `fecha_creacion` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Volcado de datos para la tabla `usuarios`
--

INSERT INTO `usuarios` (`id`, `id_empresa`, `id_area`, `nombre`, `apellido`, `email`, `password`, `rol`, `foto_perfil`, `estado`, `ultimo_login`, `fecha_creacion`) VALUES
(1, NULL, NULL, 'Admin', 'Sistema', 'admin@masercom.cl', '$2y$12$LVVey6nFHjCTQiB2x6FP3Ohr3mxl7l/BBRinK8Crnvi8LizkYzPz.', 'admin', 'user_1_1752448932.png', 1, '2025-07-14 23:11:11', '2025-07-11 14:03:47'),
(2, 1, 9, 'Rodrigo', 'Gonzalez Cerpa', 'soporte@masercom.cl', '$2y$12$JHj.WvEtLLyWMwu5vpZ8iOFWBh1TsyNii56zK.TozCJlM7ao2nc3S', 'tecnico', NULL, 1, '2025-07-13 21:49:50', '2025-07-11 18:23:00'),
(3, NULL, NULL, 'Victor', 'Bonta', 'victorbonta670@gmail.com', '$2y$12$ZYPkFKV6lboVokWwbKfkr.eHulZdc2w8IHnMS85cBejYGJ0N9c5A6', 'cliente', NULL, 1, '2025-07-14 22:35:18', '2025-07-13 21:33:56');

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `adjuntos`
--
ALTER TABLE `adjuntos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_respuesta` (`id_respuesta`),
  ADD KEY `id_ticket` (`id_ticket`);

--
-- Indices de la tabla `areas`
--
ALTER TABLE `areas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_unidad` (`id_unidad`);

--
-- Indices de la tabla `categorias_tickets`
--
ALTER TABLE `categorias_tickets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `parent_id` (`parent_id`);

--
-- Indices de la tabla `empresas`
--
ALTER TABLE `empresas`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `gerencias`
--
ALTER TABLE `gerencias`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_empresa` (`id_empresa`);

--
-- Indices de la tabla `logs`
--
ALTER TABLE `logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_usuario` (`id_usuario`);

--
-- Indices de la tabla `respuestas_tickets`
--
ALTER TABLE `respuestas_tickets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_ticket` (`id_ticket`),
  ADD KEY `id_usuario` (`id_usuario`);

--
-- Indices de la tabla `tickets`
--
ALTER TABLE `tickets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_cliente` (`id_cliente`),
  ADD KEY `id_tecnico` (`id_tecnico`),
  ADD KEY `id_categoria` (`id_categoria`),
  ADD KEY `id_empresa` (`id_empresa`),
  ADD KEY `id_area` (`id_area`);

--
-- Indices de la tabla `unidades`
--
ALTER TABLE `unidades`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_gerencia` (`id_gerencia`);

--
-- Indices de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `id_empresa` (`id_empresa`),
  ADD KEY `id_area` (`id_area`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `adjuntos`
--
ALTER TABLE `adjuntos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `areas`
--
ALTER TABLE `areas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT de la tabla `categorias_tickets`
--
ALTER TABLE `categorias_tickets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=44;

--
-- AUTO_INCREMENT de la tabla `empresas`
--
ALTER TABLE `empresas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `gerencias`
--
ALTER TABLE `gerencias`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `logs`
--
ALTER TABLE `logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `respuestas_tickets`
--
ALTER TABLE `respuestas_tickets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `tickets`
--
ALTER TABLE `tickets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `unidades`
--
ALTER TABLE `unidades`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `adjuntos`
--
ALTER TABLE `adjuntos`
  ADD CONSTRAINT `adjuntos_ibfk_1` FOREIGN KEY (`id_respuesta`) REFERENCES `respuestas_tickets` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `adjuntos_ibfk_2` FOREIGN KEY (`id_ticket`) REFERENCES `tickets` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `areas`
--
ALTER TABLE `areas`
  ADD CONSTRAINT `areas_ibfk_1` FOREIGN KEY (`id_unidad`) REFERENCES `unidades` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `categorias_tickets`
--
ALTER TABLE `categorias_tickets`
  ADD CONSTRAINT `fk_categorias_parent` FOREIGN KEY (`parent_id`) REFERENCES `categorias_tickets` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Filtros para la tabla `gerencias`
--
ALTER TABLE `gerencias`
  ADD CONSTRAINT `gerencias_ibfk_1` FOREIGN KEY (`id_empresa`) REFERENCES `empresas` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `logs`
--
ALTER TABLE `logs`
  ADD CONSTRAINT `logs_ibfk_1` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL;

--
-- Filtros para la tabla `respuestas_tickets`
--
ALTER TABLE `respuestas_tickets`
  ADD CONSTRAINT `respuestas_tickets_ibfk_1` FOREIGN KEY (`id_ticket`) REFERENCES `tickets` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `respuestas_tickets_ibfk_2` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `tickets`
--
ALTER TABLE `tickets`
  ADD CONSTRAINT `tickets_ibfk_1` FOREIGN KEY (`id_cliente`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `tickets_ibfk_2` FOREIGN KEY (`id_tecnico`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `tickets_ibfk_3` FOREIGN KEY (`id_categoria`) REFERENCES `categorias_tickets` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `tickets_ibfk_4` FOREIGN KEY (`id_empresa`) REFERENCES `empresas` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `tickets_ibfk_5` FOREIGN KEY (`id_area`) REFERENCES `areas` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `unidades`
--
ALTER TABLE `unidades`
  ADD CONSTRAINT `unidades_ibfk_1` FOREIGN KEY (`id_gerencia`) REFERENCES `gerencias` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD CONSTRAINT `usuarios_ibfk_1` FOREIGN KEY (`id_empresa`) REFERENCES `empresas` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `usuarios_ibfk_2` FOREIGN KEY (`id_area`) REFERENCES `areas` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;