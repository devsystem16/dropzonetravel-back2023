<?php

namespace App\Http\Controllers;

use App\Models\Imagenes;
use Illuminate\Http\Request;
use Aws\S3\S3Client;
use Exception;
use Illuminate\Support\Facades\DB;

use function PHPUnit\Framework\isNull;

class ImagenesController extends Controller
{

    public function deleteImagenS3($imagenId)
    {
        try {

            $s3 = new S3Client([

                'version' => 'latest',
                'region'  =>  'us-east-2',
                'credentials' => [
                    'key'    => 'AKIA54YCI7NLFUZ4PIXP',
                    'secret' =>  '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',
                ],
            ]);
            // Obtener imagen principal
            $imagen = Imagenes::where('id', $imagenId)->first();
            $path =   str_replace("https://zonetravel.s3.us-east-2.amazonaws.com/", '',  $imagen->paths3);


            $result =   $s3->deleteObject([
                'Bucket' => 'zonetravel',
                'Key' =>   $path  //'uploads/collages/tour/41/miniatura/1.jpg',
            ]);
            $imagen->delete();




            return response()->json(["codigo"  => 200,  "objetoDeleted" =>   $imagen], 200);
        } catch (Exception $e) {
            DB::rollback();
            return response()->json(["codigo"  => 400,  "objetoDeleted" =>   null], 201);
        }
    }


    public function eliminarPortada($tour_id)
    {
        $imagen = Imagenes::where('tour_id', $tour_id)
            ->where('orden', '1')
            ->first();
        if ($imagen) {
            $this->deleteImagenS3($imagen->id);
            $imagen->delete();
        } else {
            // manejar el caso en que no se encontró la imagen
        }
    }
    public function uploadImageAwsS3($tour_id, Request $request)
    {




        try {
            $toReturn = [];
            DB::beginTransaction();
            $contador = 1;


            $s3 = new S3Client([

                'version' => 'latest',
                'region'  =>  'us-east-2',
                'credentials' => [
                    'key'    => 'AKIA54YCI7NLFUZ4PIXP',
                    'secret' =>  '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',
                ],
            ]);


            // Validar y Guardar imagen de Portada.
            if ($request->hasFile('mainImage')) {

                $this->eliminarPortada($tour_id);


                $mainImage = $request->file('mainImage');


                // Carga la imagen principal
                $mainImageName = 'main-' . time() . '.' . $mainImage->getClientOriginalExtension();
                $s3->putObject([
                    'Bucket' => 'zonetravel',
                    'Key' =>  'uploads/collages/tour/' . $tour_id . '/'  . 'portada/'  . $mainImageName,
                    'Body' => file_get_contents($mainImage),
                    'ACL' => 'public-read',
                ]);



                $imagenPrincial =  Imagenes::create([
                    'tour_id' =>  $tour_id,
                    "paths3" => 'https://zonetravel.s3.us-east-2.amazonaws.com/uploads/collages/tour/' . $tour_id . '/'  . 'portada/'  . $mainImageName,
                    'orden' =>  $contador,
                    'estado' =>  1
                ]);


                // $response = ["portada" =>  'https://zonetravel.s3.us-east-2.amazonaws.com/uploads/collages/tour/' . $tour_id . '/'  . 'portada/'  . $mainImageName];
                array_push($toReturn,     $imagenPrincial);
            }

            // Validar y Guardar imagenes miniaturas.
            if ($request->hasFile('thumbnailImages')) {


                $thumbnailImages = $request->file('thumbnailImages');
                // Carga las imágenes miniaturas
                $thumbnailImageNames = [];
                foreach ($thumbnailImages as  $thumbnailImage) {
                    $contador =  $contador + 1;
                    $thumbnailImageName = strval($contador) . 'thumbnail-' .  '-' . time() . '.' . $thumbnailImage->getClientOriginalExtension();
                    $s3->putObject([
                        'Bucket' => 'zonetravel',
                        'Key' =>  'uploads/collages/tour/' . $tour_id . '/'  . 'miniatura/'  . $thumbnailImageName,
                        'Body' => file_get_contents($thumbnailImage),
                        'ACL' => 'public-read',
                    ]);
                    $miniaturas =  Imagenes::create([
                        'tour_id' =>  $tour_id,
                        "paths3" =>  'https://zonetravel.s3.us-east-2.amazonaws.com/uploads/collages/tour/' . $tour_id . '/'  . 'miniatura/'  . $thumbnailImageName,
                        'orden' =>  $contador,
                        'estado' =>  1
                    ]);
                    array_push($toReturn,     $miniaturas);
                    $thumbnailImageNames[] = $thumbnailImageName;
                }
            }

            DB::commit();
            return response()->json($toReturn, 200);
        } catch (Exception $e) {
            DB::rollback();
            return response()->json($e->getMessage(), 400);
        }

        // return response()->json([
        //     'mainImage' => $s3->getObjectUrl('zonetravel', $mainImageName),
        //     'thumbnailImages' => array_map(function ($thumbnailImageName) use ($s3) {
        //         return $s3->getObjectUrl('zonetravel', $thumbnailImageName);
        //     }, $thumbnailImageNames),
        // ]);
    }
    public function loadImageTour($tourId, Request $request)
    {

        $mainImage = $request->input('mainImage');
        $thumbnailImages = $request->input('thumbnailImages');

        // TODO: guardar los datos del collage en la base de datos

        return response()->json(['message' => 'Collage guardado con éxito']);


        return $tourId;
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
     * @param  \App\Models\Imagenes  $imagenes
     * @return \Illuminate\Http\Response
     */
    public function show(Imagenes $imagenes)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Imagenes  $imagenes
     * @return \Illuminate\Http\Response
     */
    public function edit(Imagenes $imagenes)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Imagenes  $imagenes
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Imagenes $imagenes)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Imagenes  $imagenes
     * @return \Illuminate\Http\Response
     */
    public function destroy(Imagenes $imagenes)
    {
        //
    }
}
