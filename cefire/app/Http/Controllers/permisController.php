<?php

namespace App\Http\Controllers;
use Validator;
use App\Models\permis;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class permisController extends Controller
{
    /**
     * Mostra un llistat de tot el recurs
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
        return permis::get();
    }
    /**
     * Extrau totes les dades del permis entre dades determinades
     *
     * @return \Illuminate\Http\Response
     */
    public function get_data_index($any, $mes)
    {
        $ret = array();
        $els = permis::whereMonth('data', '=', date($mes))->whereYear('data', '=', date($any))->get();
        foreach ($els as $el) {
            $item=array("id"=>$el->id, "name"=>$el->user['name'], "data"=>$el->data, "inici"=>$el->inici, "fi"=>$el->fi, "motiu"=>$el->motiu);
            array_push($ret, $item);
        }
        return $ret;
    }

    /**
     * Guarda l'element creat.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //

        if (strtotime($request->inici) > strtotime($request->fi)){
            abort(413,"Mmmm, estàs fent coses molt rares.");
        }

        $inici_m = strtotime("07:59:00");
        $fi_m = strtotime("15:00:00");
        $inici_v = strtotime("15:00:00");
        $fi_v = strtotime("21:00:00");
        $inici = strtotime($request->inici.":00");
        $fi = strtotime($request->fi.":00");
        //return ($inici > $inici_m && $fi > $fi_m)?"SI":"NO";
        if (($inici > $inici_m && $fi > $fi_m && $inici<$fi_m) || $inici < $inici_m || ($inici > $fi_m && $inici<$inici_v) || ($fi > $fi_m && $fi < $inici_v) || $fi>$fi_v){
            abort(413,"El que estàs tractant de fer és il·legal.");
        }

        $dat = new permis();
        $dat->data = $request->data;
        $dat->inici = $request->inici;
        $dat->fi = $request->fi;
        $dat->user_id = auth()->id();
        $dat->motiu = $request->motiu;
        $dat->arxiu= $request->arxiu;
        $dat->save();
        return $dat->toArray();
    }

    /**
     * Mostra l'element del curs entre dos dades donades pel request
     *
     * @param  \App\Models\permis  $permis
     * @return \Illuminate\Http\Response
     */
    public function permis_desde(Request $request)
    {
        //
        $per = permis::where('user_id','=',$request->id)->whereBetween('data', [$request->desde, $request->fins])->get();
        if ($per->isEmpty()){
            abort(403,"No hi ha resultats");
        } else {
            $ret = array();
            foreach ($per as $el) {
                $item=array("id"=>$el->id, "name"=>$el->user['name'], "data"=>$el->data, "inici"=>$el->inici, "fi"=>$el->fi, "motiu"=>$el->motiu, "arxiu"=>$el->arxiu);
                array_push($ret, $item);
            }
            return $ret;
        }

    }
    /**
     * Busca tots els permisos del sistema que no tinguen l'arxiu pujat
     *
     * @param  \App\Models\permis  $permis
     * @return \Illuminate\Http\Response
     */
    public function permis_sense_arxiu(Request $request)
    {
        //
        $per = permis::where('arxiu','=',null)->get();
        if ($per->isEmpty()){
            abort(403,"No hi ha resultats");
        } else {
            $ret = array();
            foreach ($per as $el) {
                $item=array("id"=>$el->id, "name"=>$el->user['name'], "data"=>$el->data, "inici"=>$el->inici, "fi"=>$el->fi, "motiu"=>$el->motiu, "arxiu"=>$el->arxiu);
                array_push($ret, $item);
            }
            return $ret;
        }

    }
    /**
     * Elimina l'element  del recurs de la base de dades
     *
     * @param  \App\Models\permis  $permis
     * @return \Illuminate\Http\Response
     */
    public function destroy($permis)
    {
        //
        permis::find($permis)->delete();
    }
    /**
     * Puja l'arxiu del permís
     *
     * @param  \App\Models\permis  $permis
     * @return \Illuminate\Http\Response
     */
    public function upload(Request $request)
    {
        //
        $validator = Validator::make($request->all(), [
            'arxiu' => 'required|mimes:pdf|max:10024'
        ]);

        if ($validator->fails()) {
            abort(404,"L'arxiu no és pdf");
        } else {
            $arxiu=$request->file('arxiu')->store('arxius');
            return $arxiu;
        }
    }
    /**
     * Descarrega l'arxiu demanat
     *
     * @param  \App\Models\permis  $permis
     * @return \Illuminate\Http\Response
     */
    public function download(Request $request)
    {
        $arx = response()->download(storage_path("app/".$request->arxiu), 'permis.pdf', [], 'inline');
        return $arx;
    }

    public function permisllarg(Request $request){
        $vac = new VacancesOficialsController();
        $dates_laborals = $vac->getWorkingDays($request->dia_inici,$request->dia_fi);
        
        foreach ($dates_laborals as $key => $value) {
            # code...
            $exist = permis::where('user_id','=',auth()->id())->where('data','=',$value)->count();
            if ($exist>0){
                abort(413,"En la data ".$value." ja tens un permís. Revisa les dates.");
            }
        }
        foreach ($dates_laborals as $key => $value) {
            # code...
            $dat = new permis();
            $dat->data = $value;
            $dat->inici = "09:00:00";
            $dat->fi = "14:00:00";
            $dat->user_id = auth()->id();
            $dat->motiu = $request->motiu;
            $dat->arxiu= $request->arxiu;
            $dat->save();
            if ($this->getWeekday($value) == auth()->user()->dia_guardia){
                $dat2 = new permis();
                $dat2->data = $value;
                $dat2->inici = "16:00:00";
                $dat2->fi = "20:00:00";
                $dat2->user_id = auth()->id();
                $dat2->motiu = $request->motiu;
                $dat2->arxiu= $request->arxiu;
                $dat2->save();
            }
        }
        return 1;
    }

    public function getWeekday($date)
    {
        return date('w', strtotime($date));
    }

}
