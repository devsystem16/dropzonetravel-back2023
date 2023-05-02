<?php

namespace App\Http\Controllers;

use App\Models\Abonos;
use App\Models\DetallesReservas;
use App\Models\ProgramacionFechas;
use App\Models\Reportes;
use Illuminate\Http\Request;
use App\Models\Reservas;
use App\Models\Tours;
use Illuminate\Support\Carbon;

class ReportesController extends Controller
{

    public function ReportePasajeros($programacionFechaID)
    {


        $ProgramacionFechas =   ProgramacionFechas::where('id', $programacionFechaID)->first();

        foreach ($ProgramacionFechas->CostoTour as $programacion) {
            $programacion->TipoAcompañante;
        }
        $ProgramacionFechas->Tour;
        $reservas = Reservas::where('programacion_fecha_id',  $ProgramacionFechas["id"])->get();



        // foreach ($reservas->Abonos as $abonos) {
        //     $abonos->TipoTransaccion;
        //     $abonos->Banco;
        // }


        foreach ($reservas as $reserva) {
            $reserva->ClienteTitular;
            $reserva->LugarSalidaTour->LugarSalida;


            foreach ($reserva->Abonos as $abonos) {
                $abonos->Banco;
                $abonos->TipoTransaccion;
            }

            foreach ($reserva->DetallesReservas as $detalles) {

                $detalles->CostoTour->TipoAcompañante;
                optional($detalles->LugarSalidaTour)->LugarSalida;
            }


            foreach ($reserva->HabitacionesReservas as $HabitacionesReservas) {
                $HabitacionesReservas->Habitacion;
            }




            // $reserva->CostoTour->TipoAcompañante;
        }

        foreach ($reserva->DetallesReservas as $reserva) {
        }

        return  ["reservas" => $reservas, "costosTour" => $ProgramacionFechas];
    }


    public function generarVoucher($reserva_id)
    {
        $reserva = Reservas::where('id', $reserva_id)->first();
        $reserva->LugarSalidaTour->LugarSalida;

        $reserva->tour = $reserva->ProgramacionFecha->Tour;
        // $reserva->DetallesReservas = $reserva->DetallesReservas;


        foreach ($reserva->DetallesReservas as $detalles) {
            $detalles->Cliente;
            $detalles->CostoTour->TipoAcompañante;
            optional($detalles->LugarSalidaTour)->LugarSalida;
        }

        foreach ($reserva->Abonos as $abonos) {
            $abonos->TipoTransaccion;
            $abonos->Banco;
        }

        foreach ($reserva->HabitacionesReservas as $HabitacionesReservas) {
            $HabitacionesReservas->Habitacion;
        }


        return $reserva;
    }

    public function listaReservaTitularesTour($programacionFechaId)
    {
        $programacionFechaID = ProgramacionFechas::find($programacionFechaId);
        $programacionFechaID->Tour;

        $informacionTour =  [
            "titulo" => $programacionFechaID["tour"]["titulo"],
            "duracion" => $programacionFechaID["tour"]["duracion"],
            "fecha_salida" => $programacionFechaID["fecha"]

        ];


        $lugarSalidaTour = Reservas::select(
            'reservas.id',

            'reservas.total',
            'reservas.observaciones',
            'reservas.comisionAgencia',
            'reservas.descuento',
            'reservas.lugar_salida_tours_id',
            'reservas.costoAdicional',
            'reservas.costoAdicionalMotivo',
            'reservas.observaciones',

            'programacion_fechas.tour_id',
            'clientes.nombres',
            'clientes.apellidos',
            'clientes.documento',
            'clientes.telefono1',
            'clientes.telefono2',
            'clientes.nacionalidad_id',
            'nacionalidads.codigoPais'

            // 'abonos.valor',
            // 'abonos.fecha',
        )
            ->join('clientes', 'clientes.id', 'reservas.cliente_id')
            ->leftJoin('nacionalidads', 'nacionalidads.id', 'clientes.nacionalidad_id')
            ->join('programacion_fechas', 'programacion_fechas.id', 'reservas.programacion_fecha_id')
            ->where('reservas.programacion_fecha_id', $programacionFechaId)->get();


        foreach ($lugarSalidaTour as $reservas) {
            $abonos =  Abonos::where("reserva_id", "=",  $reservas["id"])->get();
            $reservas->abonos =  $abonos;
            $reservas->totalDescuento  = $reservas["descuento"] + $reservas["comisionAgencia"];
        }

        foreach ($lugarSalidaTour as $reservas) {

            $acompañantes = DetallesReservas::select(
                'detalles_reservas.id',
                'detalles_reservas.precio',
                'detalles_reservas.observaciones',
                'detalles_reservas.tipo_cliente',
                'clientes.documento',
                'clientes.nombres',
                'clientes.apellidos',
                'tipo_acompanantes.descripcion as categoria',
                'nacionalidads.codigoPais'

            )
                ->join('clientes', 'clientes.id', 'detalles_reservas.cliente_id')
                ->leftJoin('nacionalidads', 'nacionalidads.id', 'clientes.nacionalidad_id')
                ->join('costo_tours', 'costo_tours.id', 'detalles_reservas.costo_tour_id')
                ->join('tipo_acompanantes', 'tipo_acompanantes.id', 'costo_tours.tipo_acompanante_id')

                ->where('detalles_reservas.reserva_id',  $reservas["id"])

                ->get();

            $precioTotal = 0;


            foreach ($acompañantes as $precios) {
                $precioTotal += $precios["precio"];
            }

            $reservas->totalCalculado = $precioTotal;
            $reservas->acompañantes = $acompañantes;
        }



        return  ["informacionTour" =>    $informacionTour,  "listadoClientes" => $lugarSalidaTour];
    }

    public function listaTitularesTour($programacionFechaId)
    {


        $programacionFechaID = ProgramacionFechas::find($programacionFechaId);
        $programacionFechaID->Tour;

        $informacionTour =  [
            "titulo" => $programacionFechaID["tour"]["titulo"],
            "duracion" => $programacionFechaID["tour"]["duracion"],
            "fecha_salida" => $programacionFechaID["fecha"]

        ];


        // $lugarSalidaTour = Reservas::select(
        //     'reservas.id',
        //     'reservas.total',
        //     'reservas.observaciones',
        //     'clientes.nombres',
        //     'clientes.apellidos',
        //     'clientes.documento',
        //     'clientes.telefono1',
        //     'clientes.telefono2'
        // )
        //     ->join('clientes', 'clientes.id', 'reservas.cliente_id')
        //     ->where('reservas.programacion_fecha_id', $programacionFechaId)
        //     ->get();


        $acompañantes = DetallesReservas::select(
            'detalles_reservas.id',
            'detalles_reservas.reserva_id',
            'detalles_reservas.costo_tour_id',
            'clientes.nombres',
            'clientes.apellidos',
            'clientes.documento',
            'clientes.telefono1',
            'clientes.telefono2',
            'nacionalidads.descripcion as nacionalidad'
        )
            ->join('clientes', 'clientes.id', 'detalles_reservas.cliente_id')
            ->leftJoin('nacionalidads', 'nacionalidads.id', 'clientes.nacionalidad_id')
            ->join('reservas', 'reservas.id', 'detalles_reservas.reserva_id')
            ->where('reservas.programacion_fecha_id',  $programacionFechaId)
            ->where('reservas.deleted_at',   null)
            // // ->orderBy('clientes.nombres', 'asc')
            ->orderBy('reservas.created_at', 'asc')
            ->get();


        return  ["informacionTour" =>    $informacionTour,  "listadoClientes" => $acompañantes];
    }


    public function reporteAnual($año)
    {
        $fecha = Carbon::now()->startOfMonth();


        $ProgramacionFechas =   ProgramacionFechas::whereRaw('year(fecha) =' . $año)
            // ->whereRaw('month(fecha) = ' . $mes)
            ->orderBy('fecha', 'asc')
            ->get();



        foreach ($ProgramacionFechas as $programacion_fecha) {
            $programacion_fecha->Tour;
        }


        // Obtener las reservas
        foreach ($ProgramacionFechas as $programacion_fecha) {
            $reservas =   Reservas::where('programacion_fecha_id', $programacion_fecha["id"])->get();
            $programacion_fecha->reservas = $reservas;
        }

        // Recorrer las reservas.
        foreach ($ProgramacionFechas as $programacion_fecha) {
            $reservas = $programacion_fecha["reservas"];


            foreach ($reservas as $reserva) {
                // $DetallesReservas =   DetallesReservas::where('reserva_id', $reserva["id"])->get();

                $DetallesReservas =    DetallesReservas::select(
                    'detalles_reservas.id as IDDetalleReserva',
                    'detalles_reservas.precio',
                    'detalles_reservas.observaciones',
                    'detalles_reservas.tipo_cliente',
                    'clientes.nombres',
                    'clientes.apellidos'
                )
                    ->join('clientes', 'detalles_reservas.cliente_id', 'clientes.id')
                    ->where('detalles_reservas.reserva_id', $reserva["id"])->get();

                $reserva->DetallesReservas = $DetallesReservas;
            }
        }



        return $ProgramacionFechas;
    }


    public function reporteMensual($mes, $año)
    {
        $fecha = Carbon::now()->startOfMonth();
        $ProgramacionFechas =   ProgramacionFechas::whereRaw('year(fecha) =' . $año)
            ->whereRaw('month(fecha) = ' . $mes)
            ->orderBy('fecha', 'asc')
            ->get();



        foreach ($ProgramacionFechas as $programacion_fecha) {
            $programacion_fecha->Tour;
        }


        // Obtener las reservas
        foreach ($ProgramacionFechas as $programacion_fecha) {
            $reservas =   Reservas::where('programacion_fecha_id', $programacion_fecha["id"])->get();
            $programacion_fecha->reservas = $reservas;
        }

        // Recorrer las reservas.
        foreach ($ProgramacionFechas as $programacion_fecha) {
            $reservas = $programacion_fecha["reservas"];


            foreach ($reservas as $reserva) {
                $DetallesReservas =   DetallesReservas::where('reserva_id', $reserva["id"])->get();
                $reserva->DetallesReservas = $DetallesReservas;
            }
        }



        return $ProgramacionFechas;
    }

    public function reporteMensual2($programacionFechaId)
    {

        $tours = Tours::all();

        //Obtener la programacion de fechas de los tours.
        foreach ($tours as $tour) {
            $programacionFechas =   ProgramacionFechas::where('tour_id', $tour->id)->get();
            $tour->programacionFechas =    $programacionFechas;
        }


        // Recorrer la programacion Fechas de los tours para ver las reservas.
        foreach ($tours as $tour) {
            $programacion_fechas = $tour["programacionFechas"];

            foreach ($programacion_fechas as $programacion_fecha) {
                $reservas =   Reservas::where('programacion_fecha_id', $programacion_fecha["id"])->get();
                $programacion_fecha->reservas = $reservas;
            }
        }



        // Recorrer la programacion Fechas de los tours para ver las reservas.
        foreach ($tours as $tour) {
            $programacion_fechas = $tour["programacionFechas"];

            foreach ($programacion_fechas as $programacion_fecha) {
                $reservas = $programacion_fecha["reservas"];

                foreach ($reservas as $reserva) {
                    $detalles_reservas =   DetallesReservas::where('reserva_id', $reserva["id"])->get();
                    $reserva->detalles_reservas = $detalles_reservas;
                }
            }
        }

        return   $tours;
        // return Tours::select(
        //     'tours.id',
        //     'tours.titulo',
        //     'programacion_fechas.fecha'
        // )
        //     ->join('programacion_fechas', 'programacion_fechas.tour_id', 'tours.id')
        //     ->get();



        $programacionFechaID = ProgramacionFechas::find($programacionFechaId);
        $programacionFechaID->Tour;

        $informacionTour =  [
            "titulo" => $programacionFechaID["tour"]["titulo"],
            "duracion" => $programacionFechaID["tour"]["duracion"],
            "fecha_salida" => $programacionFechaID["fecha"]

        ];


        $lugarSalidaTour = Reservas::select(
            'reservas.id',
            'reservas.total',
            'reservas.observaciones',
            'clientes.nombres',
            'clientes.apellidos',
            'clientes.documento',
            'clientes.telefono1',
            'clientes.telefono2'
        )
            ->join('clientes', 'clientes.id', 'reservas.cliente_id')
            ->where('reservas.programacion_fecha_id', $programacionFechaId)->get();
        return  ["informacionTour" =>    $informacionTour,  "listadoClientes" => $lugarSalidaTour];
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
     * @param  \App\Models\Reportes  $reportes
     * @return \Illuminate\Http\Response
     */
    public function show(Reportes $reportes)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Reportes  $reportes
     * @return \Illuminate\Http\Response
     */
    public function edit(Reportes $reportes)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Reportes  $reportes
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Reportes $reportes)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Reportes  $reportes
     * @return \Illuminate\Http\Response
     */
    public function destroy(Reportes $reportes)
    {
        //
    }
}
