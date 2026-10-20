# Plataforma de nodos

Plataforma web que recibe, guarda y muestra los datos de nodos IoT modulares
(ESP32 / LilyGO con sondas por RS485) instalados en distintos sitios: Guatemala (lago Atitlán y PTAR),
laboratorio de Lima y laboratorio de Madrid.

- **Modular:** cada sensor llega como `<fuente>_<magnitud>` (`OD_OD_medida`, `pH_pH`...). Una clave nueva
  se da de alta sola como parámetro pendiente: no hay que tocar ni el firmware ni las tablas.
- **Contrato estable con los nodos:** ver [docs/contrato_api.md](docs/contrato_api.md).
- **Horas en UTC** en la base de datos; la web las muestra en la hora de cada sitio.

## Tecnología

Laravel 13 (PHP 8.3+) con vistas Blade. Base de datos: SQLite en desarrollo y MySQL en el servidor
(las migraciones valen para MySQL, PostgreSQL o SQLite).

## Dónde está cada cosa

| Carpeta / archivo | Qué hay |
| --- | --- |
| `database/migrations/` | Las tablas: proyectos, sitios, nodos, sondas, parametros, rangos, ciclos, lecturas, incidencias |
| `app/Models/` | Un modelo por tabla y sus relaciones |
| `app/Services/ProcesadorTramas.php` | La recepción de una trama: validar, evitar duplicados, guardar ciclo y lecturas |
| `app/Http/Controllers/Api/MedidasController.php` | La API que llaman los nodos |
| `app/Http/Controllers/PanelController.php` | El panel de inicio: una tarjeta por nodo, agrupadas por sitio |
| `app/Http/Controllers/NodoController.php` | La vista de cada nodo: medidores y gráficas de 6 h, 24 h o 7 días |
| `app/Http/Controllers/TramasController.php` | La página de comprobación `/tramas` |
| `app/Services/Rangos.php` | Qué rango aplica a cada valor (el del sitio o el de su tipo) |
| `routes/api.php`, `routes/web.php` | Qué dirección abre qué controlador |
| `resources/views/` | Las páginas (Blade) y el componente `x-barra-valor` |
| `public/js/graficas.js` | Medidores y gráficas (ECharts) |
| `database/seeders/DemoSeeder.php` | Datos de demostración: 2 nodos con 24 h de tramas |
| `config/plataforma.php` | Ajustes propios (desfase por defecto, zona horaria, límites) |
| `tests/Feature/RecepcionTramasTest.php` | Pruebas automáticas del contrato con los nodos |

## Puesta en marcha en local

```bash
composer install
cp .env.example .env        # solo la primera vez
php artisan key:generate    # solo la primera vez
php artisan migrate
php artisan db:seed --class=DemoSeeder   # opcional: datos de demostración
php artisan serve           # http://127.0.0.1:8000
php artisan test            # pruebas automáticas
```
