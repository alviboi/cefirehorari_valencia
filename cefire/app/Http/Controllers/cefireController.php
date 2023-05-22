<?php

namespace App\Http\Controllers;

use App\Models\cefire;
use DateTime;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\ControlController;
use App\Events\AfegitCefire;
use App\Events\BorratCefire;


class cefireController extends Controller
{

    protected $aparell;

    /**
     * __construct
     *
     * Extraguem la informació de configuració de l'aplicació
     *
     * aparell = 1 fitxa per temps i l'aparell funciona
     * aparell = 0 el fitxatge es per dia
     *
     * @param  mixed $Control_data
     * @return void
     */
    public function __construct(ControlController $Control_data)
    {
        $this->aparell = $Control_data->index()->aparell;
    }
    /**
     * Mostra un llistat de tot el recurs
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
        $ret = cefire::get();
        return $ret->toArray();

    }

    /**
     * Extrau totes les dades de fitxar del cefire amb el nom
     *
     * @return \Illuminate\Http\Response
     */
    public function get_data_index($any, $mes)
    {
        $ret = array();
        $els = cefire::whereMonth('data', '=', date($mes))->whereYear('data', '=', date($any))->get();
        foreach ($els as $el) {
            $item=array("id"=>$el->id, "name"=>$el->user['name'], "data"=>$el->data, "inici"=>$el->inici, "fi"=>$el->fi);
            array_push($ret, $item);
        }
        return $ret;
    }

    /**
     * Guarda l'elememt creat.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
        $data_hui = date('Y-m-d');
        $en4dies = date('Y-m-d', strtotime($data_hui. ' + 4 days'));
        if ($this->aparell == 1){
            



            if ($request->data != $data_hui){
                return response("Està habilitat el fitxatge per dies. Has de fitxar cada dia", 403);
                //abort(403, 'Està habilitat el fitxatge per dies. Has de fitxar cada dia');
            } else {
                $hora = date('H:i:s');
                if ($request->id == 0){
                    $c = date("14:00:00");
                    $d = date("16:00:00");
                    if ($hora>$c && $hora<$d){
                        return response("No pots fitxar la entrada entre les 14:00 i les 16:00", 403);

                        //abort(403,"No pots fitxar la entrada entre les 14:00 i les 16:00");
                    }
                    $dat = new cefire();
                    $dat->data = $request->data;
                    $dat->inici = $hora;
                    $dat->fi = "00:00:00";
                    $dat->user_id = auth()->id();
                    $dat->save();

                    $broad= array("data" => $dat->data,"nom" => auth()->user()->name,"id" => $dat->id, "inici" => $dat->inici, "fi" => $dat->fi);
                    $ret = array("id" => $dat->id,"inici" => $dat->inici,"fi" => $dat->fi);
                    // return cefire::where('data','=',$request->data)->where('user_id','=',auth()->id())->get();
                } else {
                    $cef=cefire::where('id','=',$request->id)->first();
                    // $conta=cefire::where('data','=',$data_hui)->count();
                    // // if (auth()->user()->cefire()->where('data','=',$data_hui)->count()>0 && getWeekday($data_hui)==5){
                    // //     $cef->fi = $hora;
                    // // } else 
                    $a = date($cef->fi);
                    $b = date("14:45:00");
                    $c = date($cef->inici);
                    $d = date("14:00:00");

                    //abort(413, "Es: ".($a>$b));
                    //if ((auth()->user()->diaguardia == $this->getWeekday($data_hui)) && ($a->diffInMinutes($b)>0) && $cef->fi=="00:00:00") {
                    // if (($a->diffInMinutes($b)>0) && ($c->diffInMinutes($a)>0)){

                    // } else 
                    if ($a>$b && $c<$d) {
                        $cef->fi = "14:45:00";
                    } else {
                        $cef->fi = $hora;
                    }
                    
                    //$cef->fi = $hora;
                    
                    $cef->save();
                    $broad = array("data" => $cef->data, "nom" => auth()->user()->name, "id" => $cef->id, "inici" => $cef->inici, "fi" => $cef->fi);
                    $ret = array("id"=>$cef->id,"inici" => $cef->inici,"fi" => $cef->fi);
                    // return cefire::where('data','=',$request->data)->where('user_id','=',auth()->id())->get();
                }
                broadcast(new AfegitCefire($broad))->toOthers();
                return $ret;

            }
        } else {
            $hi_ha=cefire::where('user_id','=',auth()->id())->where('data','=',$request->data)->where('inici','=',$request->inici)->first();
            if ($hi_ha) {
                //abort(403, "Ja has fitxat el dia de hui");
                return response("Ja has fitxat", 403);
            } elseif ($request->data > $en4dies) {
                return response("Has de planificar la teua estància al CEFIRE cada setmana", 403);
            }else {
                $dat = new cefire();
                $dat->data = $request->data;
                $dat->inici = $request->inici;
                $dat->fi = $request->fi;
                $dat->user_id = auth()->id();
                $dat->save();
                $broad = array("data" => $dat->data, "nom" => auth()->user()->name, "id" => $dat->id, "inici" => $dat->inici, "fi" => $dat->fi);
                $ret = array("id" => $dat->id,"inici" => $dat->inici,"fi" => $dat->fi);
                broadcast(new AfegitCefire($broad))->toOthers();
                return $ret;

                // return cefire::where('data','=',$request->data)->where('user_id','=',auth()->id())->get();

            }
        }
    }
    


    /**
     * Mostra l'element del curs
     *
     * @param  \App\Models\cefire  $cefire
     * @return \Illuminate\Http\Response
     */
    public function show(cefire $cefire)
    {
        //
    }


    /**
     * Elimina l'element  del recurs de la base de dades
     *
     * @param  \App\Models\cefire  $cefire
     * @return \Illuminate\Http\Response
     */
    public function destroy(cefire $cefire)
    {
        //
        broadcast(new BorratCefire($cefire->toArray()))->toOthers();

        $cefire->delete();
    }


    /**
     * contar_cefires
     *
     * Calcula el temps que s'ha realitzat al Cefire entre $desde fins a $fins, seccionat per dies. Aquesta funció torna les dades per a poder generar la gràfica de
     * estadística de l'aplicació.
     *
     * @param  mixed $desde
     * @param  mixed $fins
     * @return void
     */
    public function contar_cefires($desde,$fins)
    {
        //
        $cefire=cefire::where('user_id','=',auth()->id())->whereBetween('data', [$desde, $fins])->orderBy('data','ASC')->get();
        $total=0;
        $labels=array();
        $data=array();
        $data_ant=new DateTime();
        $comp = new DateTime("00:00:00");
        foreach($cefire as $cef){
            $cef->inici = new Carbon($cef->inici);
            $cef->fi = new Carbon($cef->fi);
            if($cef->fi == $comp) {

            } else {
                if ($cef->data == $data_ant) {
                    $duration = $cef->inici->diffInMinutes($cef->fi);
                    $valor = array_pop($data);
                    array_push($data,($valor+$duration));
                } else {
                    $duration = $cef->inici->diffInMinutes($cef->fi);
                    array_push($labels,$cef->data);
                    array_push($data,$duration);
                }
                $total=$total+$duration;
                $data_ant=$cef->data;
            }

        }
        $ret=array('labels' => $labels, 'data' => $data, 'total' => $total);
        return ($ret);
    }

    public function ultims_dies_estadistica() {


        $data_hui = date('Y-m-d');
        $any = date('Y');
        // $data_15_oct = date($any."-10-15");
        // $data_15_mai = date($any."-05-15");
        // if ($data_hui >= $data_15_oct || $data_hui <= $data_15_mai) {
        //     $interval_comp = 27900/60;       
        // } else {
        //     $interval_comp = 26100/60;
        // }

        // if(auth()->user()->reduccio){
        //     $interval_comp = $interval_comp - 60;
        // }
        

        $cefires = cefire::where('user_id','=',auth()->id())->orderBy('id', 'desc')->take(5)->get();
        $ret =array();
        $end_time   =   strtotime("15:30:00");
        foreach ($cefires as $key => $el) {
            # code...
            $st_time    =   strtotime($el->inici);
           
            if($st_time > $end_time) {
                $interval_comp = 240;
            } else {
                $interval_comp = 300;
            }
            $a = strtotime($el->fi);
            $b = strtotime($el->inici);
            $dat = date_create($el->data);
            $interval = round(($a - $b)/60,0,PHP_ROUND_HALF_DOWN);
            $item=array("id"=>$el->id, "name"=>$el->user['name'], "data"=>date_format($dat,"d-m-Y"), "inici"=>$el->inici, "fi"=>$el->fi, "diferencia"=>$interval, "compara"=>$interval_comp);
            array_push($ret, $item);
        }
        return $ret;
    }

    public function getWeekday($date) {
        return date('w', strtotime($date));
    }

    public function cefire_fitxa_oblit(Request $request) {
        $dat = new cefire();
        $dat->data = $request->data;
        $dat->inici = $request->inici;
        $dat->fi = $request->fi;
        $dat->user_id = $request->id;
        $ok = $dat->save();
        $duration = $dat->inici->diffInMinutes($dat->fi);
        $data_hui_mes = date('m');
        $data_hui_a = explode('-',$dat->data);
        $data_hui = $data_hui_a[1];
        if ($data_hui_mes != $data_hui) {
            $deute = new DeutesmesController();
            $deute->afegix_a_mes_anterior($dat->user_id,$duration);
        } 
        if ($ok) {
            return "Fitxat correctament";
        } else {
            abort(403, "Alguna cosa ha anat malament");
        }
    }

    public function usuaris_oblit_fitxatge() {
        $data_hui = date('Y-m-d');
        $cefires = cefire::where("fi","=","00:00:00")->where('data','<',$data_hui)->get();
        $dias=array("Diumenge","Dilluns","Dimarts","Dimecres","Dijous","Divendres","Dissabte");
        $ret = array();
        foreach ($cefires as $el) {
            $da=date("d-m-Y", strtotime($el->data));
            $da2=$dias[date("w", strtotime($el->data))];
            
            $item=array("id"=>$el->id, "name"=>$el->user['name'], "data"=>$da2.", ".$da, "inici"=>$el->inici, "fi"=>$el->inici);
            array_push($ret, $item);
        }
        return $ret;

    }
}
