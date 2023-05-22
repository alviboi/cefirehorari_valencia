<?php

namespace App\Http\Controllers;
use App\Models\VacancesOficials;
use Illuminate\Http\Request;

class VacancesOficialsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
                //$data_hui = date('Y-m-d');
                $any = date('Y');
                $mes = date('m');
                //abort(403,$mes);
                // $data_1_set = date();
                // $data_31_agost = date();
        
                if ($mes >= 9) {
                    $data_1_set = date($any."-09-01");
                    $data_31_agost = date(($any+1)."-08-31");    
                } else {
                    $data_1_set = date(($any-1)."-09-01");
                    $data_31_agost = date(($any)."-08-31");
                }
                return VacancesOficials::whereBetween('data', [$data_1_set, $data_31_agost])->get();
            }
        
            public function es_vacances (String $dia) {
                $vac = VacancesOficials::where('data', "=", $dia)->get();
                if (!$vac->isEmpty()){
                    return 1;
                } else {
                    return 0;
                }
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
        VacancesOficials::truncate();
        foreach ($request->toArray() as $key => $value) {
            # code...
            $vac = VacancesOficials::firstOrCreate(["data" => $value['id']]);
            if (!$vac){
                abort(403,"Ha hagut un error");
            }
        }
        return "Sembla que tot ha anat correctament";
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
    
    
    
    public function getWorkingDays($startDate, $endDate)
    {
        $vacancesoficials = VacancesOficials::get('data')->toArray();
        $vacaofcarr = array();
        foreach ($vacancesoficials as $key => $value) {
            # code...
            array_push($vacaofcarr,$value['data']);
        }
        // do strtotime calculations just once
        $endDatea = strtotime($endDate);
        $startDatea = strtotime($startDate);

        //$days = ($endDate)->diff($startDate);
        //The total number of days between the two dates. We compute the no. of seconds and divide it to 60*60*24
        //We add one to inlude both dates in the interval.
        $days = ($endDatea - $startDatea) / 86400 + 1;

        $tots_els_dies = array();

        for ($i=0; $i < $days; $i++) { 
            # code...
            $dia = date('Y-m-d', strtotime($startDate. ' + '.$i.' days'));
            if (!$this->isWeekend($dia)){
                if (!in_array($dia, $vacaofcarr)){
                    array_push($tots_els_dies,$dia);
                }
            }       
        }
        return $tots_els_dies;
    }

    function isWeekend($date) {
        return (date('N', strtotime($date)) >= 6);
    }


}
