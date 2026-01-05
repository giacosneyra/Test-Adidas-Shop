# Proyecto Headless WordPress con Next.js
<img width="2560" height="2884" alt="My-Store-01-04-2026_11_13_PM" src="https://github.com/user-attachments/assets/904587be-5075-4df7-ab3f-4172940a0573" />
<img width="2560" height="1801" alt="My-Store-01-04-2026_11_13_PM (1)" src="https://github.com/user-attachments/assets/5b3465ae-e97d-4c86-aacf-40cffa57d208" />
<img width="2560" height="1271" alt="My-Store-01-04-2026_11_13_PM (2)" src="https://github.com/user-attachments/assets/1f64a8e4-a2fb-48c6-91ce-38738d15e381" />
<img width="2560" height="2730" alt="My-Store-01-04-2026_11_14_PM" src="https://github.com/user-attachments/assets/37b03650-7d6f-4e98-b4e6-7d9c102038e0" />
<img width="2560" height="1271" alt="My-Store-01-04-2026_11_14_PM (1)" src="https://github.com/user-attachments/assets/dc04d862-8344-44bd-80dd-b8383a3c4483" />

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
