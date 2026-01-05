# Proyecto Headless WordPress con Next.js

Este proyecto utiliza **WordPress como backend** y **Next.js como frontend** en un enfoque Headless, usando **GraphQL** para consumir datos. Está diseñado para ser desplegado en un entorno local con **LocalWP** y se puede migrar fácilmente a producción.

---

## Tecnologías utilizadas
- **Frontend:** Next.js, React
- **Backend:** WordPress
- **Base de datos:** MySQL (LocalWP)
- **API:** GraphQL (WPGraphQL)
- **Gestión de dependencias:** npm

---

## Estructura del proyecto
- `/frontend` → Código de Next.js (React)  
- `/backend` → Instalación de WordPress  
- `/database` → Copia de la base de datos en `.sql`  
- `/components` → Componentes React reutilizables (Navbar, Footer, etc)  
- `/pages` → Páginas de Next.js  
- `/styles` → Archivos CSS / Tailwind  

---

## Instalación y configuración

### 1. Configurar backend (WordPress)
- Abrir LocalWP y crear un nuevo sitio o importar tu instalación existente.
- Importar la base de datos:
  1. Abrir **AdminNeo** o **phpMyAdmin** desde LocalWP.
  2. Seleccionar la base de datos de tu sitio.
  3. Hacer clic en **Import** y subir el archivo `.sql`.
- Configurar `wp-config.php` con tus credenciales locales de MySQL:
  ```php
  define('DB_NAME', 'nombre_de_tu_base_de_datos');
  define('DB_USER', 'usuario_de_bd');
  define('DB_PASSWORD', 'contraseña');
  define('DB_HOST', 'localhost');

Accesos WordPress Site:
User:GianfrancoTest
Password: GianfrancoTest2026
