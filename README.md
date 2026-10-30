# Sistema de gestión académica

Aplicación web desarrollada con Laravel para administrar información académica e institucional. Incluye módulos para usuarios y roles, personal, carreras, materias, gestiones académicas, grupos, actividades, postulaciones, inscripciones y pagos.

Esta guía explica cómo preparar el entorno local en Windows, instalar las dependencias, configurar la base de datos, cargar datos de demostración y ejecutar el sistema.

## Requisitos

Instala estas herramientas antes de comenzar:

| Herramienta | Requisito | Para qué se utiliza |
|---|---|---|
| Git | Versión reciente | Descargar el proyecto y administrar cambios. |
| PHP | 8.2 o superior | Ejecutar Laravel. |
| Extensiones de PHP | `pdo_mysql`, `mbstring`, `openssl`, `fileinfo`, `xml`, `dom`, `gd` y `zip`, como mínimo | Conexión a MySQL, manejo de archivos, imágenes, PDF y hojas de cálculo. |
| Composer | Composer 2 | Instalar dependencias de PHP. |
| MySQL | MySQL 8 recomendado | Guardar los datos del sistema. |
| Node.js | 22.12 o superior (incluye npm) | Instalar y compilar los recursos del frontend con Vite 7. |

Puedes instalar PHP y MySQL por separado o utilizar un entorno local como **Laragon** o **XAMPP**, siempre que la versión de PHP sea 8.2 o superior y tenga habilitadas las extensiones requeridas. Instala Composer y Node.js por separado si tu entorno no los incluye. Para ejecutar las pruebas automatizadas también se necesita habilitar `pdo_sqlite`, ya que la configuración de pruebas utiliza SQLite en memoria.

**Opcionales:** Visual Studio Code para editar el proyecto y MySQL Workbench para administrar la base de datos gráficamente.

Comprueba que Windows reconoce los comandos desde PowerShell:

```powershell
php -v
composer --version
node --version
npm --version
git --version
```

Si un comando no se reconoce, revisa la instalación y que su carpeta esté agregada a `PATH`. Para verificar las extensiones de PHP, ejecuta `php -m`; también puedes ejecutar `composer check-platform-reqs` después de instalar las dependencias.

## Descargar el proyecto

Si todavía no tienes una copia local, abre PowerShell en la carpeta donde quieras guardarlo y ejecuta:

```powershell
git clone <URL_DEL_REPOSITORIO>
cd <NOMBRE_DE_LA_CARPETA>
```

Reemplaza `<URL_DEL_REPOSITORIO>` con la dirección que te comparta tu compañero. Si ya tienes el proyecto, abre PowerShell en su carpeta y continúa con el siguiente paso.

## Instalar dependencias y configurar `.env`

Desde la carpeta raíz del proyecto:

```powershell
composer install
npm install

if (-not (Test-Path .env)) {
    Copy-Item .env.example .env
}

php artisan key:generate
```

`composer install` instala las bibliotecas PHP indicadas por `composer.lock`, y `npm install` instala las herramientas JavaScript indicadas por `package-lock.json`. El archivo `.env` contiene la configuración local y no se debe compartir ni subir al repositorio.

## Configurar MySQL

Inicia el servicio MySQL y crea una base vacía llamada `itsa`. Por ejemplo, desde el cliente de MySQL:

```sql
CREATE DATABASE itsa CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

Abre el archivo `.env` y confirma que la configuración coincida con tu instalación:

```dotenv
APP_NAME="Sistema de gestion academica"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://127.0.0.1:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=itsa
DB_USERNAME=root
DB_PASSWORD=
```

El `.env.example` usa `root` sin contraseña. Si MySQL está configurado con otro usuario, contraseña, puerto o nombre de base de datos, modifica esos valores. `APP_DEBUG=true` es apropiado únicamente para desarrollo local.

## Crear las tablas y cargar datos

Para instalar el esquema y los datos de demostración:

```powershell
php artisan migrate --seed
```

Si las tablas ya existen y solo quieres volver a ejecutar los datos iniciales:

```powershell
php artisan db:seed
```

El seeder principal agrega registros de demostración, como carreras, materias, un administrador y postulaciones de prueba. **No contiene la base real de tu compañero**. Si necesitas continuar con datos reales, pídele un respaldo de la base de datos y las instrucciones para restaurarlo.

> **Atención:** `php artisan migrate:fresh --seed` elimina todas las tablas y sus datos antes de crearlas nuevamente. Úsalo solo en una base local descartable, nunca en una base con información que quieras conservar.

## Ejecutar la aplicación

En una terminal, desde la raíz del proyecto:

```powershell
composer run dev
```

Este comando inicia el servidor local de Laravel, Vite y los procesos de desarrollo configurados por el proyecto. Deja la terminal abierta y visita:

```text
http://127.0.0.1:8000
```

Detén los procesos con `Ctrl+C`.

Para compilar los recursos del frontend sin iniciar el modo de desarrollo:

```powershell
npm run build
```

Si prefieres iniciar los procesos por separado, usa terminales distintas:

```powershell
php artisan serve
```

```powershell
npm run dev
```

## Acceso de demostración

Después de ejecutar `php artisan migrate --seed`, el seeder incluye esta cuenta local:

| Usuario | Contraseña | Rol |
|---|---|---|
| `20260001` | `adminroot` | Administrador |

Son credenciales públicas de demostración definidas en el código. Úsalas únicamente en desarrollo y cámbialas antes de cualquier despliegue o uso con información real. El formulario de acceso solicita el usuario y la contraseña.

## Comandos útiles

Ejecuta estos comandos desde la carpeta raíz:

| Comando | Función |
|---|---|
| `php artisan migrate:status` | Ver el estado de las migraciones. |
| `php artisan migrate` | Aplicar migraciones pendientes sin borrar los datos existentes. |
| `php artisan db:seed` | Ejecutar el seeder principal. |
| `php artisan route:list` | Mostrar las rutas registradas. |
| `php artisan optimize:clear` | Limpiar cachés de configuración, rutas y vistas. |
| `php artisan test` | Ejecutar las pruebas automatizadas. |
| `npm run dev` | Ejecutar Vite en modo desarrollo. |
| `npm run build` | Compilar los recursos frontend. |

Para servir archivos cargados mediante el disco público de Laravel, crea el enlace de almacenamiento una sola vez:

```powershell
php artisan storage:link
```

## Solución de problemas

- **`php`, `composer`, `node` o `npm` no se reconoce:** instala la herramienta correspondiente y reinicia PowerShell para actualizar `PATH`.
- **Composer indica que falta una extensión PHP:** habilita la extensión indicada en la configuración de PHP usada por la terminal, reinicia PowerShell y comprueba con `php -m`.
- **Error de conexión a MySQL:** confirma que el servicio esté iniciado, que la base `itsa` exista y que `DB_HOST`, `DB_PORT`, `DB_USERNAME` y `DB_PASSWORD` en `.env` sean correctos.
- **Cambiaste `.env` y Laravel sigue usando valores anteriores:** ejecuta `php artisan optimize:clear`.
- **El puerto 8000 está ocupado:** inicia Laravel con `php artisan serve --port=8001` y abre `http://127.0.0.1:8001`.
- **No aparecen los estilos o scripts durante el desarrollo:** confirma que `npm install` terminó y que `composer run dev` o `npm run dev` sigue activo.

## Notas para el equipo

- No compartas ni subas `.env`, contraseñas o respaldos con datos personales.
- Usa datos ficticios en las instalaciones locales de prueba.
- Antes de actualizar dependencias, revisa los cambios en `composer.json`/`composer.lock` y `package.json`/`package-lock.json`.
- Para saber qué cambió en el proyecto o qué configuración usa una instalación existente, consulta a quien te entregó el repositorio.
