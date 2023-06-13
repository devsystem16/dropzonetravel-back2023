<?php

use App\Http\Controllers\AbonosController;
use App\Http\Controllers\AcompaniantesController;
use App\Http\Controllers\BancosController;
use App\Http\Controllers\ClientesController;
use App\Http\Controllers\CostoTourController;
use App\Http\Controllers\DetallesReservasController;
use App\Http\Controllers\HabitacionesController;
use App\Http\Controllers\ImagenesController;
use App\Http\Controllers\LugaresSalidasController;
use App\Http\Controllers\LugarSalidaTourController;
use App\Http\Controllers\NacionalidadController;
use App\Http\Controllers\ReportesController;
use App\Http\Controllers\ReservasController;
use App\Http\Controllers\ServiciosController;
use App\Http\Controllers\TipoAcompanantesController;
use App\Http\Controllers\TipoTransaccionesController;
use App\Http\Controllers\ToursController;
use App\Http\Controllers\UsuariosController;
use App\Models\TipoTransacciones;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::get('/servicios/listado/{limit}',  [ServiciosController::class, 'listado']);
Route::resource('tipoacompanante', TipoAcompanantesController::class);


Route::resource('lugaressalidas', LugaresSalidasController::class);

Route::get('/lugar-salida-tour/obtener/{tour_id}',  [LugarSalidaTourController::class, 'obtenerLugarSalidaTour']);

Route::post('/lugar-salida-tour/eliminar',  [LugarSalidaTourController::class, 'eliminarLugarSalida']);
Route::post('/lugar-salida-tour/eliminar-forzado',  [LugarSalidaTourController::class, 'eliminarLugarSalidaForzado']);


Route::resource('tour', ToursController::class);
Route::post('/tour/listado/tabla/',  [ToursController::class, 'listado']);
Route::post('/tour/listado/tabla/actuales',  [ToursController::class, 'listadoActuales']);
Route::post('/tour/eliminar/{id}',  [ToursController::class, 'eliminar']);

Route::post('/tour/aws/s3/upload/image',  [ToursController::class, 'uploadImage']);

Route::post('/tour/experiencias/imagenes',  [ToursController::class, 'obtenerImagenesExperiencias']);
Route::get('/tour/experiency/getimages/{idTour}',  [ToursController::class, 'getImages']);



Route::post('/tour/add-fecha-salida/id/{idTour}',  [ToursController::class, 'añadirFecha']);
Route::post('/tour/actualizar/{id}',  [ToursController::class, 'actualizarTour']);

Route::post('/tour/actualizar-precio/{programacionFechaId}',  [ToursController::class, 'actualizarPrecioTour']);



Route::resource('cliente', ClientesController::class);
Route::get('/cliente/find/{idCliente}',  [ClientesController::class, 'find']);
Route::get('/cliente/count/obtener',  [ClientesController::class, 'conteoClientes']);


Route::get('/acompaniante/find/{documento}',  [AcompaniantesController::class, 'find']);

Route::get('costo-tour/obtener-precios/{idProgramacionFecha}', [CostoTourController::class, 'obtenerPrecios']);


// Route::resource('habitacion/{idProgramacionFecha}', [HabitacionesController::class, 'obtenerPrecios']);
Route::resource('habitacion', HabitacionesController::class);



Route::resource('bancos', BancosController::class);

Route::resource('reserva', ReservasController::class);


Route::resource('abono', AbonosController::class);
Route::post('/abono/eliminar/{id}',  [AbonosController::class, 'EliminarAbono']);

Route::post('/detalle-reserva/eliminar/{id}',  [DetallesReservasController::class, 'EliminarDetalle']);




Route::get('/reporte/titulares/{programacionFechaId}',  [ReportesController::class, 'listaTitularesTour']);

Route::get('/reserva/listado/titulares/{programacionFechaId}',  [ReportesController::class, 'listaReservaTitularesTour']);
Route::get('/reserva/voucher/generar/{reserva_id}',  [ReportesController::class, 'generarVoucher']);

Route::get('/reserva/acompaniante/obtener/{reserva_id}',  [ReservasController::class, 'obtenerAcompañante']);
Route::get('/reserva/habitaciones/obtener/{reserva_id}',  [ReservasController::class, 'obtenerHabitaciones']);
Route::get('/reserva/lugar-salida/obtener/{reserva_id}',  [ReservasController::class, 'obtenerLugarSalida']);
Route::get('/reserva/precios/obtener/{reserva_id}',  [ReservasController::class, 'obtenerPrecios']);
Route::post('/reserva/procesos/eliminar/{reserva_id}',  [ReservasController::class, 'eliminarReserva']);


Route::post('/reserva/editar/{reserva_id}',  [ReservasController::class, 'editarReserva']);



Route::get('/reporte/mensual/{programacionFechaId}/{año}',  [ReportesController::class, 'reporteMensual']);
Route::get('/reporte/anual/{anio}',  [ReportesController::class, 'reporteAnual']);
Route::get('/reporte/mensual/pasajeros/codigo-fecha/{programacionFechaID}',  [ReportesController::class, 'ReportePasajeros']);



Route::get('/tipo-transacciones/list-select',  [TipoTransaccionesController::class, 'listSelect']);



Route::post('/usuario/login',  [UsuariosController::class, 'login']);



Route::post('/image/upload/tour/{tourId}',  [ImagenesController::class, 'uploadImageAwsS3']);
Route::post('/image/delete/{imagenId}',  [ImagenesController::class, 'deleteImagenS3']);



Route::resource('nacionalidads', NacionalidadController::class);


// Route::get('/nacionalidads/obtener/',  [NacionalidadController::class, 'obtenerAll']);
