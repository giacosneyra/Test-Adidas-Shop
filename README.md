# Proyecto Headless WordPress con Next.js
https://drive.google.com/file/d/1oF_ZzAORZL1HrdbLDXUvte5Pt4lhdacJ/view?usp=sharing
Este proyecto utiliza **WordPress como backend** y **Next.js como frontend** en un enfoque Headless, usando **GraphQL** para consumir datos. Está diseñado para ser desplegado en un entorno local con **LocalWP** y se puede migrar fácilmente a producción.

# Video de Presentacion
[https://drive.google.com/drive/folders/1p9Fg-64Fmk5iHSOvFg77IFRXBeh7GjJV?usp=drive_link](https://drive.google.com/drive/folders/1p9Fg-64Fmk5iHSOvFg77IFRXBeh7GjJV?usp=drive_link)
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

2. Configurar frontend (Next.js)

Abrir terminal en la carpeta /frontend.

Instalar dependencias:

npm install


Levantar servidor de desarrollo:

npm run dev


Para producción:

npm run build
npm start


Configurar URL de GraphQL en .env.local:

NEXT_PUBLIC_GRAPHQL_URL=http://localhost:10000/graphql


Ajusta el puerto según el que LocalWP asigne a tu sitio.

Uso

Acceder al frontend en http://localhost:3000.

Editar contenido desde WordPress (backend).

Rutas dinámicas de productos o posts se manejan con [slug] en Next.js.

Componentes reutilizables se encuentran en /components.

Exportar/Importar Base de Datos

Para exportar desde LocalWP:

Abrir AdminNeo o phpMyAdmin.

Seleccionar la base de datos de tu sitio.

Hacer clic en Export → Formato SQL → Descargar.

Para importar:

Abrir AdminNeo o phpMyAdmin en el nuevo entorno.

Crear base de datos vacía o seleccionar existente.

Hacer clic en Import y subir el archivo .sql.

💡 Tip: Copia también la carpeta wp-content para mover temas, plugins y uploads.

Contribuciones

Crear nuevas ramas para cada funcionalidad.

Hacer pull request al branch principal.

Mantener código limpio, modular y documentado.

Contacto

Correo: gianfraoficial@gmail.com
