# Proyecto WordPress + Next.js

## Estructura
- `/frontend` → Aplicación Next.js
- `/backend` → WordPress completo + base de datos (`database.sql`)

## Configuración del Frontend
1. Copia `https://testpage.local/` a `.env.local`
2. Configura la URL de WordPress local:


NEXT_PUBLIC_WORDPRESS_URL=http://localhost:10000

GRAPHQL_URL=http://localhost:10000/graphql


NEXT_PUBLIC_WORDPRESS_URL=http://localhost:10000

GRAPHQL_URL=http://localhost:10000/graphql

3. Instala dependencias:

cd frontend
npm install

4. Ejecuta:

npm run dev

5. Abre en el navegador: [http://localhost:3000](http://localhost:3000)

## Configuración del Backend
1. Descomprime la carpeta `backend` en tu servidor local.
2. Carga la base de datos `database.sql` en tu MySQL/LocalWP.
3. Ajusta `wp-config.php` si es necesario.
4. Accede a WordPress: `http://localhost:10000/wp-admin/`
