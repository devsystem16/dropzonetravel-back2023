<?php

namespace App\Http\Controllers;

use App\Models\CostoTour;
use App\Models\Imagenes;
use App\Models\LugaresSalidas;
use App\Models\LugarSalidaTour;
use App\Models\ProgramacionFechas;
use App\Models\Reservas;
use Illuminate\Support\Facades\DB;
use App\Models\Tours;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Aws\S3\S3Client;


class ToursController extends Controller
{



    public static    function existeEnArray($lista, $dato)
    {
        if (in_array($dato, $lista)) {
            return true;
        } else {
            return false;
        };
    }


    public function actualizarPrecioTour($programacionFechaId,  Request $request)
    {
        try {
            $exitenReserva = false;
            $cantidadReserva = 0;
            $arrayIDTipoAcompañantes = [];


            $reservas = Reservas::select("*")->where("programacion_fecha_id", "=", $programacionFechaId)->get();
            if (is_array($reservas) || is_object($reservas)) {
                foreach ($reservas as $reserv) {
                    $cantidadReserva++;
                    $exitenReserva = true;
                }
            }
            if ($exitenReserva) {
                //    return response()->json(["Message" => "En está fecha ya existen ($cantidadReserva) Reservas. NO se permite modificar los precios"], 209);
                // Validacion de que solo se actualicen precios que no esten configurados.
                foreach ($reservas as $reserva) {
                    foreach ($reserva->DetallesReservas as $detalle) {
                        $detalle->CostoTour->TipoAcompañante;
                        $codigo = $detalle->CostoTour->TipoAcompañante["descripcion"];
                        if (!$this->existeEnArray($arrayIDTipoAcompañantes,  $codigo)) {
                            array_push($arrayIDTipoAcompañantes,   $codigo);
                        }
                    }
                }
                // Fin  Validacion de que solo se actualicen precios que no esten configurados.
            }


            $data = $request->json()->all();
            $programacionFecha = ProgramacionFechas::select("*")->where("id", "=", $programacionFechaId)->first();

            $precios = $data["precios"];

            // Quitar del listado los precios, que ya tengan personas con reservas.
            foreach ($arrayIDTipoAcompañantes as $tiposDelete) {
                unset($precios[$tiposDelete]);
            }

            foreach ($precios as $precio) {

                if ($precio["type"] == "new") {
                    $costoTour =  CostoTour::create([
                        'programacion_fecha_id' =>  $programacionFecha->id,
                        "tipo_acompanante_id" => $precio["id"],
                        'aplicapago' => ($precio["valor"] == 0) ? false : true,
                        'precio' =>  $precio["valor"],
                        'estado' =>  1
                    ]);
                } else {
                    // En caso de actualizar.
                    $costo_tour = CostoTour::select("*")->where("id", "=", $precio["id"])->first();
                    $costo_tour->precio =  $precio["valor"];
                    $costo_tour->aplicapago =  ($precio["valor"] == 0) ? false : true;
                    $costo_tour->save();
                }
            }

            DB::commit();
            return response()->json(["Message" => "Datos Actualizados Correctamente"], 200);
        } catch (Exception $e) {

            DB::rollBack();
            return response()->json(["Message" => $e->getMessage()], 209);
        }
    }
    public function actualizarTour($idTour,  Request $request)
    {

        try {
            // $data = $request->json()->all();
            $data = get_object_vars(json_decode($request->input('data')));




            $tour = Tours::select("*")->where("id", "=", $idTour)->first();

            $url =  $this->uploadImageAwsS3($request,       $tour);


            $lugaresSalidas = $data["lugaresSalidas"];

            foreach ($lugaresSalidas as $lugar) {
                if ($lugar->new == true) {
                }
            }



            $tour->titulo = $data["titulo"];
            $tour->duracion = $data["duracion"];
            $tour->detalles = $data["detalles"];
            $tour->imagen =     $url; //$data["imagen"];
            $tour->incluye = $data["incluye"];
            $tour->noIncluye = $data["noIncluye"];
            $tour->informacionAdicional = $data["informacionAdicional"];
            $tour->save();

            foreach ($lugaresSalidas as $lugar) {
                $NewlugarSalida = null;
                if ($lugar->new == true) {
                    $NewlugarSalida =   LugaresSalidas::create([
                        "descripcion" => $lugar->descripcion,
                        "estado" => true
                    ]);
                } else {
                    $NewlugarSalida = $lugar;
                }
                LugarSalidaTour::create([
                    "lugar_salida_id" => $NewlugarSalida->id,
                    "tour_id" =>  $tour->id,
                    "hora" => $lugar->hora,
                    "siguienteDia" =>  $lugar->siguienteDia,
                    "estado" => true
                ]);
            }

            // return  $tour;

            DB::commit();
            return response()->json(["Message" => "Datos Actualizados Correctamente"], 200);
        } catch (Exception $e) {

            DB::rollBack();
            return response()->json(["errorMessage" => $e->getMessage()], 209);
        }
    }
    public function añadirFecha($idTour,  Request $request)
    {
        try {
            $data = $request->json()->all();
            $programacionFechas = $data["fechas"];
            $precios = $data["precios"];

            $tour =  Tours::where("id", "=",   $idTour)->first();
            $tour->updated_at =  Carbon::now();
            $tour->save();

            foreach ($programacionFechas as $programacion) {
                $nuevaProgramacionFecha =  ProgramacionFechas::create(
                    [
                        'fecha' => $programacion["fecha"],
                        "observacion" =>  "",
                        'estado' => true,
                        'tour_id' =>  $idTour
                    ]
                );

                foreach ($precios as $precio) {
                    $costoTour =  CostoTour::create([
                        'programacion_fecha_id' =>  $nuevaProgramacionFecha->id,
                        "tipo_acompanante_id" => $precio["id"],
                        'aplicapago' => ($precio["valor"] == 0) ? false : true,
                        'precio' =>  $precio["valor"],
                        'estado' =>  1
                    ]);
                }
            }




            DB::commit();
            return response()->json(["Message" => "Fechas & Costos Guardados Correctamente"], 200);
        } catch (Exception $e) {

            DB::rollBack();
            return response()->json(["errorMessage" => $e->getMessage()], 209);
        }
    }


    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return "OK";
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



    public function uploadImage(Request $request)
    {
        $file = $request->file('image');
        $fileName = $file->getClientOriginalName();
        $s3 = new S3Client([
            'version' => 'latest',
            'region'  =>  'us-east-2',
            'credentials' => [
                'key'    => 'AKIA54YCI7NLFUZ4PIXP',
                'secret' =>  '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',
            ],
        ]);

        $result = $s3->putObject([
            'Bucket' =>  'zonetravel',
            'Key'    => 'uploads/tours/' . $fileName,
            'Body'   => fopen($file, 'r'),
            'ACL'    => 'public-read',

        ]);


        $url =  "https://zonetravel.s3.us-east-2.amazonaws.com/uploads/tours/" . $fileName;
        return  $url;



        return response()->json([
            'message' => 'Archivo subido correctamente',
            'url' => $url
        ]);


        return;
    }



    public function deleteImagenS3($tourId)
    {
        try {
            // Obtener imagen principal
            $tour = Tours::where('id', $tourId)->first();
            if ($tour->imagen == null ||   $tour->imagen ==  "")
                return false;


            $s3 = new S3Client([

                'version' => 'latest',
                'region'  =>  'us-east-2',
                'credentials' => [
                    'key'    => 'AKIA54YCI7NLFUZ4PIXP',
                    'secret' =>  'mmO1fliU2sKa63DUYgiFNbcdFfg4KprhyxqDUgy0',
                ],
            ]);


            $path =   str_replace("https://zonetravel.s3.us-east-2.amazonaws.com/", '',  $tour->imagen);


            $result =   $s3->deleteObject([
                'Bucket' => 'zonetravel',
                'Key' =>   $path  //'uploads/collages/tour/41/miniatura/1.jpg',
            ]);


            return  true;
        } catch (Exception $e) {
            DB::rollback();
            return false;
        }
    }



    public function uploadImageAwsS3(Request $request, $tour = null)
    {
        $file = null;
        $fileName = null;
        $url = "";

        if ($tour != null)
            $url = $tour->imagen;

        if ($request->hasFile('image')) {
            $file = $request->file('image');

            if ($tour != null)
                $this->deleteImagenS3($tour->id,);

            // Verificamos si hubo errores de subida
            if ($file->getError() !== UPLOAD_ERR_OK) {
                // return response()->json(['message' => 'Error al subir la imagen.'], 422);
                $url = "";
            }

            $fileName = $file->getClientOriginalName();

            // SUBIMOS LA IMAGEN.
            $s3 = new S3Client([
                'version' => 'latest',
                'region'  =>  'us-east-2',
                'credentials' => [
                    'key'    => 'AKIA54YCI7NLFUZ4PIXP',
                    'secret' =>  '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',
                ],
            ]);

            $result = $s3->putObject([
                'Bucket' =>  'zonetravel',
                'Key'    => 'uploads/tours/' . $fileName,
                'Body'   => fopen($file, 'r'),
                'ACL'    => 'public-read',

            ]);


            $url =  "https://zonetravel.s3.us-east-2.amazonaws.com/uploads/tours/" . $fileName;
        }

        return $url;
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        try {

            $data = get_object_vars(json_decode($request->input('data')));
            $url =  $this->uploadImageAwsS3($request, null);

            $toReturn = [];
            DB::beginTransaction();
            $programacionFechas = $data["programacionFechas"];
            $lugaresSalidas = $data["lugaresSalidas"];





            // $tour =  Tours::create($request->json()->all());
            $tour =  Tours::create([
                "titulo" =>  $data["titulo"],
                "duracion" =>  $data["duracion"],
                "detalles" => $data["detalles"],
                "imagen" =>  $url,
                "incluye" => $data["incluye"],
                "noIncluye" => $data["noIncluye"],
                "informacionAdicional" => $data["informacionAdicional"],
                "estado" => true,
            ]);


            array_push($toReturn, $tour);




            foreach ($lugaresSalidas as $lugar) {
                $NewlugarSalida = null;
                if ($lugar->new == true) {
                    $NewlugarSalida =   LugaresSalidas::create([
                        "descripcion" => $lugar->descripcion,
                        "estado" => true
                    ]);
                } else {
                    $NewlugarSalida = $lugar;
                }
                LugarSalidaTour::create([
                    "lugar_salida_id" => $NewlugarSalida->id,
                    "tour_id" =>  $tour->id,
                    "hora" => $lugar->hora,
                    "siguienteDia" => $lugar->siguienteDia,
                    "estado" => true
                ]);
            }



            foreach ($programacionFechas as $programacion) {
                $nuevaProgramacionFecha =  ProgramacionFechas::create(
                    [
                        'fecha' => $programacion->fecha,
                        "observacion" => $programacion->observacion,
                        'estado' => true,
                        'tour_id' =>   $tour->id
                    ]
                );
                array_push($toReturn, $nuevaProgramacionFecha);

                $precios = $programacion->precios;
                foreach ($precios as $precio) {
                    $costoTour =  CostoTour::create([
                        'programacion_fecha_id' =>  $nuevaProgramacionFecha->id,
                        "tipo_acompanante_id" => $precio->id,
                        'aplicapago' => ($precio->valor == 0) ? false : true,
                        'precio' =>  $precio->valor,
                        'estado' =>  1
                    ]);
                    array_push($toReturn, $costoTour);
                }
            }


            DB::commit();
            return response()->json($toReturn, 200);
        } catch (Exception $e) {
            return response()->json($e->getMessage(), 400);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Tours  $tours
     * @return \Illuminate\Http\Response
     */
    public function show(Tours $tours)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Tours  $tours
     * @return \Illuminate\Http\Response
     */
    public function edit(Tours $tours)
    {
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Tours  $tours
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Tours $tours)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Tours  $tours
     * @return \Illuminate\Http\Response
     */
    public function destroy(Tours $tours)
    {
    }

    public function listadoActuales(Request $request)
    {
        $data = $request->json()->all();
        $reporte = [];
        $tours = Tours::select(
            'tours.id',
            'tours.titulo',
            'tours.duracion',
            'tours.detalles',
            'tours.imagen',
            'tours.incluye',
            'tours.noIncluye',
            'tours.informacionAdicional',
            'tours.estado',
        )
            ->orderBy('tours.updated_at', 'desc')
            ->get()
            ->map(function ($tou) {

                $tou->incluye = preg_replace("/[\r\n|\n|\r]+/",  "<br />", $tou->incluye);
                $tou->noIncluye = preg_replace("/[\r\n|\n|\r]+/",  "<br />", $tou->noIncluye);
                $tou->informacionAdicional = preg_replace("/[\r\n|\n|\r]+/",  "<br />", $tou->informacionAdicional);

                return $tou;
            });

        foreach ($tours as $tour) {
            $lugarSalidaTour = LugarSalidaTour::select(
                'lugar_salida_tours.id',
                'lugares_salidas.id as lugares_salidas_id ',
                'lugares_salidas.descripcion',
                'lugar_salida_tours.hora',
                'lugar_salida_tours.estado',
                'lugar_salida_tours.siguienteDia'

            )
                ->join('lugares_salidas', 'lugares_salidas.id', 'lugar_salida_tours.lugar_salida_id')
                ->where('lugar_salida_tours.tour_id', $tour->id)->get();


            // $fechaActual = Carbon::now()->toDateString();;
            // return        $fechaActual->format('Y-m-d');

            $currentDate = Carbon::now();
            $currentDate = $currentDate->format('Y-m-d');


            $programacionFechas = [];
            if ($data["mostrarFechasOld"]) {
                $programacionFechas = ProgramacionFechas::select(
                    'programacion_fechas.id',
                    'programacion_fechas.fecha',
                    'programacion_fechas.observacion',
                    'programacion_fechas.estado'
                )
                    ->where('programacion_fechas.tour_id', $tour->id)
                    // ->where('programacion_fechas.fecha', '>=', $currentDate)
                    ->get();
            } else {

                $programacionFechas = ProgramacionFechas::select(
                    'programacion_fechas.id',
                    'programacion_fechas.fecha',
                    'programacion_fechas.observacion',
                    'programacion_fechas.estado'
                )
                    ->where('programacion_fechas.tour_id', $tour->id)
                    ->where('programacion_fechas.fecha', '>=', $currentDate)
                    ->get();
            }






            foreach ($programacionFechas as $programacion) {
                $costoTour = CostoTour::select(
                    'costo_tours.id',
                    'costo_tours.programacion_fecha_id',
                    'costo_tours.tipo_acompanante_id',
                    'tipo_acompanantes.descripcion',
                    'costo_tours.aplicapago',
                    'costo_tours.precio',
                    'costo_tours.estado'
                )
                    ->join('tipo_acompanantes', 'tipo_acompanantes.id', 'costo_tours.tipo_acompanante_id')
                    ->where('costo_tours.programacion_fecha_id', $programacion->id)->get();

                $programacion->precios  = $costoTour;
            }


            if (count($programacionFechas) > 0) {
                $tour->lugaresSalidas =  $lugarSalidaTour;
                $tour->programacionFechas =  $programacionFechas;
                array_push(
                    $reporte,
                    $tour
                );
            }
        }




        return  $reporte;
    }
    public function listado(Request $request)
    {
        $data = $request->json()->all();
        $reporte = [];
        $tours = Tours::select(
            'tours.id',
            'tours.titulo',
            'tours.duracion',
            'tours.detalles',
            'tours.imagen',
            'tours.incluye',
            'tours.noIncluye',
            'tours.informacionAdicional',
            'tours.estado',
        )
            ->orderBy('tours.updated_at', 'desc')
            ->get()
            ->map(function ($tou) {

                $tou->incluye = preg_replace("/[\r\n|\n|\r]+/",  "<br />", $tou->incluye);
                $tou->noIncluye = preg_replace("/[\r\n|\n|\r]+/",  "<br />", $tou->noIncluye);
                $tou->informacionAdicional = preg_replace("/[\r\n|\n|\r]+/",  "<br />", $tou->informacionAdicional);

                return $tou;
            });

        foreach ($tours as $tour) {
            $lugarSalidaTour = LugarSalidaTour::select(
                'lugar_salida_tours.id',
                'lugares_salidas.id as lugares_salidas_id ',
                'lugares_salidas.descripcion',
                'lugar_salida_tours.hora',
                'lugar_salida_tours.estado',
                'lugar_salida_tours.siguienteDia'

            )
                ->join('lugares_salidas', 'lugares_salidas.id', 'lugar_salida_tours.lugar_salida_id')
                ->where('lugar_salida_tours.tour_id', $tour->id)->get();


            // $fechaActual = Carbon::now()->toDateString();;
            // return        $fechaActual->format('Y-m-d');

            $currentDate = Carbon::now();
            $currentDate = $currentDate->format('Y-m-d');


            $programacionFechas = [];
            if ($data["mostrarFechasOld"]) {
                $programacionFechas = ProgramacionFechas::select(
                    'programacion_fechas.id',
                    'programacion_fechas.fecha',
                    'programacion_fechas.observacion',
                    'programacion_fechas.estado'
                )
                    ->where('programacion_fechas.tour_id', $tour->id)
                    // ->where('programacion_fechas.fecha', '>=', $currentDate)
                    ->get();
            } else {

                $programacionFechas = ProgramacionFechas::select(
                    'programacion_fechas.id',
                    'programacion_fechas.fecha',
                    'programacion_fechas.observacion',
                    'programacion_fechas.estado'
                )
                    ->where('programacion_fechas.tour_id', $tour->id)
                    ->where('programacion_fechas.fecha', '>=', $currentDate)
                    ->get();
            }






            foreach ($programacionFechas as $programacion) {
                $costoTour = CostoTour::select(
                    'costo_tours.id',
                    'costo_tours.programacion_fecha_id',
                    'costo_tours.tipo_acompanante_id',
                    'tipo_acompanantes.descripcion',
                    'costo_tours.aplicapago',
                    'costo_tours.precio',
                    'costo_tours.estado'
                )
                    ->join('tipo_acompanantes', 'tipo_acompanantes.id', 'costo_tours.tipo_acompanante_id')
                    ->where('costo_tours.programacion_fecha_id', $programacion->id)->get();

                $programacion->precios  = $costoTour;
            }



            $tour->lugaresSalidas =  $lugarSalidaTour;
            $tour->programacionFechas =  $programacionFechas;
            array_push(
                $reporte,
                $tour
            );
        }




        return  $reporte;
    }

    public function eliminar($id)
    {
        try {
            $toReturn = [];
            $existenReservas = false;
            $tour = Tours::select("*")->where("id", "=", $id)->first();
            $programacionFechas =  $tour->programacionFechas;

            $currentDate = Carbon::now();
            $currentDate = $currentDate->format('Y-m-d');

            foreach ($programacionFechas as $fechas) {

                if ($fechas["fecha"] >=   $currentDate) {

                    $reservas =  Reservas::where("programacion_fecha_id", "=",  $fechas["id"])->get();

                    $cantidadReservas = 0;
                    $fechasR = $fechas["fecha"];
                    $tieneReservas = false;
                    foreach ($reservas as $reserva) {

                        $cantidadReservas++;
                        $existenReservas = true;
                        $tieneReservas = true;
                    }
                    if ($tieneReservas) {
                        $response = [
                            "fechas" =>  $fechasR,
                            "cantidad"   => $cantidadReservas
                        ];
                        array_push($toReturn,  $response);
                    }
                }
            }


            $response = null;

            if (!$existenReservas) {
                $tour->delete();
                $response = ["existe_reserva" => $existenReservas, "reservas" =>  $toReturn, "Message" => "Tour Eliminado Correctamente"];
            } else {
                $response = ["existe_reserva" => $existenReservas, "reservas" =>  $toReturn, "Message" => "El Tour tiene registrado reservas. No se permite eliminar."];
            }

            return response()->json($response, 200);
        } catch (Exception $e) {
            return response()->json(["existe_reserva" => false, "reservas" => [], "Message" => "Error con el Tour proporcionado."], 400);
        }
    }

    public function obtenerImagenesExperiencias(Request $request)
    {
        $toursConImagenes = Tours::whereHas('imagenes', function ($query) {
            $query->whereNotNull('paths3');
        })->with(['imagenes' => function ($query) {
            $query->orderBy('id', 'asc');
        }])->get();


        return   $toursConImagenes;
    }

    public function getImages($idTour)
    {

        // Obtener imagen principal
        $imagenPrincipal = Imagenes::where('tour_id', $idTour)->where('orden', 1)->first();

        // Obtener miniaturas
        $miniaturas = Imagenes::where('tour_id', $idTour)->where('orden', '>', 1)->get();

        $imagenes = [
            'principal' => $imagenPrincipal,
            'miniaturas' => $miniaturas
        ];
        // // Validar si hay imágenes
        // if ($imagenPrincipal && $miniaturas->count() > 0) {
        //     // Convertir las imágenes a un array
        //     $imagenes = [
        //         'principal' => $imagenPrincipal,
        //         'miniaturas' => $miniaturas
        //     ];
        // } else {
        //     $imagenes = []; // No hay imágenes
        // }

        return $imagenes;
    }
}
