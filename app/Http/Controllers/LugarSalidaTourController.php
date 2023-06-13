<?php

namespace App\Http\Controllers;

use App\Models\Clientes;
use App\Models\LugaresSalidas;
use App\Models\LugarSalidaTour;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Exception;

class LugarSalidaTourController extends Controller
{


    public function eliminarLugarSalidaForzado(Request $request)
    {
        $lugarSalidaTourId =    $request->lugarSalidaTourId;
        $lugarSalidaTour = LugarSalidaTour::find($lugarSalidaTourId); //230); //
        $lugarSalidaTour->delete();
        return response()->json(["permiteEliminar" => true, "Titulares" => [],   "Acompañantes" =>   [], "Message" => "Lugar de Salida Eliminado del Tour."], 200);
    }

    public function eliminarLugarSalida(Request $request)
    {

        $lugarSalidaTourId =    $request->lugarSalidaTourId;
        $lugarSalidaTour = LugarSalidaTour::find($lugarSalidaTourId); //230); //


        if (!$lugarSalidaTour) {
            // El LugarSalidaTour no existe
            return;
        }

        $reservasCount = $lugarSalidaTour->reservas()->count();
        $detallesReservasCount = $lugarSalidaTour->detallesReservas()->count();

        if ($reservasCount === 0 && $detallesReservasCount === 0) {
            // El LugarSalidaTour ha sido eliminado exitosamente
            $lugarSalidaTour->delete();
            return response()->json(["permiteEliminar" => true, "Titulares" => [],   "Acompañantes" =>   [], "Message" => "Lugar de Salida Eliminado del Tour."], 200);
        } else {

            $Titulares = Clientes::whereHas('reservas', function ($query) use ($lugarSalidaTourId) {
                $query->where('lugar_salida_tours_id', $lugarSalidaTourId);
            })->get();

            $Acompañantes = Clientes::whereHas('detallesReservas', function ($query) use ($lugarSalidaTourId) {
                $query->where('lugar_salida_tours_id', $lugarSalidaTourId);
            })->get();

            return response()->json(["permiteEliminar" => false, "Titulares" => $Titulares,   "Acompañantes" =>   $Acompañantes, "Message" =>  "Existen clientes registrados para este lugar de salida."], 200);
            // No se puede eliminar el LugarSalidaTour porque tiene reservas o detalles de reservas asociados
        }
    }

    public function obtenerLugarSalidaTour($tour_id)
    {
        try {

            $lugaresSalidaTour = LugarSalidaTour::select(
                'lugar_salida_tours.id',
                'lugar_salida_tours.lugar_salida_id',
                'lugar_salida_tours.tour_id',
                'lugar_salida_tours.hora',
                'lugar_salida_tours.siguienteDia',
                'lugar_salida_tours.estado',
                'lugares_salidas.descripcion',

            )
                ->join('lugares_salidas', 'lugares_salidas.id', 'lugar_salida_tours.lugar_salida_id')
                ->where('lugar_salida_tours.tour_id', $tour_id)
                ->orderBy('lugar_salida_tours.hora', 'asc')
                ->get();

            return  $lugaresSalidaTour;
        } catch (Exception $e) {
            return response()->json(["errorMessage" => $e->getMessage()], 400);
        }
    }


    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\LugarSalidaTour  $lugarSalidaTour
     * @return \Illuminate\Http\Response
     */
    public function show(LugarSalidaTour $lugarSalidaTour)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\LugarSalidaTour  $lugarSalidaTour
     * @return \Illuminate\Http\Response
     */
    public function edit(LugarSalidaTour $lugarSalidaTour)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\LugarSalidaTour  $lugarSalidaTour
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, LugarSalidaTour $lugarSalidaTour)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\LugarSalidaTour  $lugarSalidaTour
     * @return \Illuminate\Http\Response
     */
    public function destroy(LugarSalidaTour $lugarSalidaTour)
    {
        //
    }
}
