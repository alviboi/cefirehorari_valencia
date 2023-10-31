<?php

namespace App\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Jobs\SendPasswordMail;
use DateTime;


use DB;

/**
 * UserController Class
 *
 * @version 1.0
 *
 * Per a totes aquelles connexions realitzades a la taula User
 *
 */

class UserController extends Controller
{


    /**
     * @return la vista en blade de home
     */
    public function home()
    {
        $conta = User::find(auth()->id())->notificacions()->count();
        return view('home', ['conta' => $conta]);
    }




    /**
     * contar: Conta tots els elements que té un assessor
     *
     * @param  mixed $desde inici del di
     * @param  mixed $fins fi del dia
     * @return array de totes les dades contades
     */
    public function contar($desde, $fins)
    {

        $labels = ['cefire', 'permis', 'compensa', 'curs'];

        $cefire = user::find(auth()->id())->cefire()->whereBetween('data', [$desde, $fins])->get();
        $permis = user::find(auth()->id())->permis()->whereBetween('data', [$desde, $fins])->get();
        $compensa = user::find(auth()->id())->compensa()->whereBetween('data', [$desde, $fins])->get();
        $curs = user::find(auth()->id())->curs()->whereBetween('data', [$desde, $fins])->get();

        $total_cef = 0;
        foreach ($cefire as $cef) {
            $cef->inici = new Carbon($cef->inici);
            $duration = $cef->inici->diffInMinutes($cef->fi);
            $total_cef = $total_cef + $duration;
        }

        $total_per = 0;
        foreach ($permis as $perm) {
            $in = Carbon::parse($perm->inici);
            $fi = Carbon::parse($perm->fi);
            $duration = $in->diffInMinutes($fi);
            $total_per = $total_per + $duration;
        }

        $total_comp = 0;
        foreach ($compensa as $comp) {
            $in = Carbon::parse($comp->inici);
            $fi = Carbon::parse($comp->fi);
            $duration = $in->diffInMinutes($fi);
            $total_comp = $total_comp + $duration;
        }

        $total_curs = 0;
        foreach ($curs as $cu) {
            $in = Carbon::parse($cu->inici);
            $fi = Carbon::parse($cu->fi);
            $duration = $in->diffInMinutes($fi);
            $total_curs = $total_curs + $duration;
        }
        $datos = [round($total_cef / 60, 2), round($total_per / 60, 2), round($total_comp / 60, 2), round($total_curs / 60, 2)];

        $ret = array('labels' => $labels, 'datos' => $datos);
        return ($ret);
    }

    public function logat()
    {
        return user::find(auth()->id())->name;
    }

    public function logat_id()
    {
        return user::find(auth()->id())->id;
    }

    /**
     * Guarda l'elememt creat.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response missatge de tasca feta
     */
    public function store(Request $request)
    {
        $input = $request->all();
        $message = "";
        for ($i = 0; $i < count($input); $i++) {
            $user_comp = User::where('email', '=', $input[$i]['email'])->first();
            if ($user_comp != null) {
                $message = $message . " " . $user_comp->email;
            } else {
                $dat = new User();
                $dat->name = $input[$i]['name'];
                $dat->email = $input[$i]['email'];
                $dat->Perfil = 0;
                $passwd = Str::random(5);
                $dat->password = Hash::make($passwd);
                $dat->save();
                $emailJob = (new SendPasswordMail($input[$i]['email'], $passwd))->delay(Carbon::now()->addSeconds(120));
                dispatch($emailJob);
            }
        }
        if ($message == "") {
            return "Tots els usuaris s'han creat";
        } else {
            return "Els usuaris amb mail: " . $message . ", ja estan creats, per tant no s'han tornat a crear";
        }
    }

    /**
     * get_usuaris_ldap funció per a demanar tots els usuaris LDAP
     *
     * @param  mixed $request es demana la ip i la contrasenya de netadmin per a poder connectar-se a un LliureX
     * @return text Si ha estat satisfactòria
     */
    public function get_usuaris_ldap(Request $request)
    {

        //Cal identificar-se amb l'usuari netadmin
        //uid=alfredo@alfredo.es,ou=Teachers,ou=People,dc=ma5,dc=lliurex,dc=net
        $pwd = $request->contrasenya;
        $conn = ldap_connect($request->ip, '389');
        $bindDn = "uid=netadmin,ou=Admins,ou=People,dc=ma5,dc=lliurex,dc=net";
        ldap_set_option($conn, LDAP_OPT_PROTOCOL_VERSION, 3);

        $ldaptree = "ou=Teachers,ou=People,dc=ma5,dc=lliurex,dc=net";


        if ($conn) {

            $ldapbind = ldap_bind($conn, $bindDn, $pwd) or die("Ha hagut un error: " . ldap_error($conn));

            if ($ldapbind) {

                $result = ldap_search($conn, $ldaptree, "(cn=*)") or die("Ha hagut un error: " . ldap_error($conn));
                $data = ldap_get_entries($conn, $result);

                $usuaris = array();
                for ($i = 0; $i < $data["count"]; $i++) {
                    $el = ['email' => $data[$i]["uid"][0], 'name' => $data[$i]["description"][0]];
                    array_push($usuaris, $el);
                }
                return $usuaris;
                ldap_close($conn);
            } else {
                ldap_close($conn);
                return "El servidor no està ben configurat. Revisa la configuració";
            }


        }
    }

    /**
     * Mostra la informació d'un usuari
     *
     * @return informació
     */
    public function get_user()
    {
        //
        return User::find(auth()->id());
    }
    /**
     * Mostra un llistat de tot el recurs
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
        return User::orderBy('name', 'ASC')->get();
    }

    /**
     * destroy
     *
     * Elimina el recurs
     *
     * @param  mixed $user
     * @return void
     */
    public function destroy($user)
    {
        //
        User::find($user)->delete();
    }


    /**
     * update
     *
     * Actualitza el recurs
     *
     * @param  mixed $request
     * @return estat de la petició
     */
    public function update(Request $request)
    {
        //
        if ($request->id == auth()->id() || auth()->user()->Perfil == 1) {
            $user = User::find($request->id);
            $user->name = $request->nom;
            $user->email = $request->mail;
            $user->Perfil = $request->perfil;
            $user->rfid = $request->rfid;
            if ($user->contrasenya != "") {
                $user->password = $request->contrasenya;
            }
            $user->save();
        } else {
            abort(403, "No tens permís per a realitzar aquesta acció");
        }


    }


    /**
     * dia_cefire
     *
     * Torna la informació sol·licitada d'un moment concret del dia del recurs
     *
     * @param  mixed $dia
     * @param  mixed $mati
     * @return array $ret2 amb tota la informació
     */
    public function dia_cefire($dia, $mati)
    {
        //
        if ($mati == 'm')
            $control = '<';
        else
            $control = '>';
        $cefire = User::find(auth()->id())->cefire()->where('data', '=', $dia)->where('fi', $control, '15:00:00')->get();
        $ret2 = array();
        foreach ($cefire as $value) {
            $ret1 = array("id" => $value->id, "user_id" => $value->user_id, "inici" => $value->inici->format('H:i:s'), "fi" => $value->fi->format('H:i:s'));
            array_push($ret2, $ret1);

        }

        return $ret2;

    }
    /**
     * dia_guardia
     *
     * Torna la informació sol·licitada d'un moment concret del dia del recurs
     *
     * @param  mixed $dia
     * @param  mixed $mati
     * @return array $ret2 amb tota la informació
     */
    public function dia_guardia($dia, $mati)
    {
        //
        if ($mati == 'm')
            $control = '<';
        else
            $control = '>';
        $cefire = User::find(auth()->id())->guardia()->where('data', '=', $dia)->where('fi', $control, '15:00:00')->get();
        return $cefire;
    }
    /**
     * dia_incidencies
     *
     * Torna la informació sol·licitada d'un moment concret del dia del recurs
     *
     * @param  mixed $dia
     * @param  mixed $mati
     * @return array $ret2 amb tota la informació
     */
    public function dia_incidencies($dia, $mati)
    {
        //
        if ($mati == 'm')
            $control = '<';
        else
            $control = '>';
        $incidencies = User::find(auth()->id())->incidencies()->where('data', '=', $dia)->where('fi', $control, '15:00:00')->get();
        return $incidencies;
    }
    /**
     * dia_curs
     *
     * Torna la informació sol·licitada d'un moment concret del dia del recurs
     *
     * @param  mixed $dia
     * @param  mixed $mati
     * @return array $ret2 amb tota la informació
     */
    public function dia_curs($dia, $mati)
    {
        //
        if ($mati == 'm')
            $control = '<';
        else
            $control = '>';
        $cefire = User::find(auth()->id())->curs()->where('data', '=', $dia)->where('fi', $control, '15:00:00')->get();
        return $cefire;
    }
    /**
     * dia_compensa
     *
     * Torna la informació sol·licitada d'un moment concret del dia del recurs
     *
     * @param  mixed $dia
     * @param  mixed $mati
     * @return array $ret2 amb tota la informació
     */
    public function dia_compensa($dia, $mati)
    {
        //
        if ($mati == 'm')
            $control = '<';
        else
            $control = '>';
        $cefire = User::find(auth()->id())->compensa()->where('data', '=', $dia)->where('fi', $control, '15:00:00')->get();
        return $cefire;
    }
    /**
     * dia_visita
     *
     * Torna la informació sol·licitada d'un moment concret del dia del recurs
     *
     * @param  mixed $dia
     * @param  mixed $mati
     * @return array $ret2 amb tota la informació
     */
    public function dia_visita($dia, $mati)
    {
        //
        if ($mati == 'm')
            $control = '<';
        else
            $control = '>';
        $cefire = User::find(auth()->id())->visita()->where('data', '=', $dia)->where('fi', $control, '15:00:00')->get();
        return $cefire;
    }
    /**
     * dia_permis
     *
     * Torna la informació sol·licitada d'un moment concret del dia del recurs
     *
     * @param  mixed $dia
     * @param  mixed $mati
     * @return array $ret2 amb tota la informació
     */
    public function dia_permis($dia, $mati)
    {
        //
        if ($mati == 'm')
            $control = '<';
        else
            $control = '>';
        $cefire = User::find(auth()->id())->permis()->where('data', '=', $dia)->where('fi', $control, '15:00:00')->get();
        return $cefire;
    }

    public function dia_tot($dia, $mati)
    {
        //
        $cefire = array();
        $ret2 = array();
        $ret3 = array();
        if ($mati == 'm')
            $control = '<=';
        else
            $control = '>';
        $cefire['permis'] = User::find(auth()->id())->permis()->where('data', '=', $dia)->where('fi', $control, '15:00:00')->get();
        $cefire['visita'] = User::find(auth()->id())->visita()->where('data', '=', $dia)->where('fi', $control, '15:00:00')->get();
        $cefire['compensa'] = User::find(auth()->id())->compensa()->where('data', '=', $dia)->where('fi', $control, '15:00:00')->get();
        $cefire['curs'] = User::find(auth()->id())->curs()->where('data', '=', $dia)->where('fi', $control, '15:00:00')->get();
        $cefire['guardia'] = User::find(auth()->id())->guardia()->where('data', '=', $dia)->where('fi', $control, '15:00:00')->get();
        $cefire['incidencies'] = User::find(auth()->id())->incidencies()->where('data', '=', $dia)->where('fi', $control, '15:00:00')->get();

        $ret2 = User::find(auth()->id())->cefire()->where('data', '=', $dia)->where('fi', $control, '15:00:00')->get();

        foreach ($ret2 as $value) {
            $ret1 = array("id" => $value->id, "user_id" => $value->user_id, "inici" => $value->inici, "fi" => $value->fi);
            array_push($ret3, $ret1);
        }
        $cefire['cefire'] = $ret3;

        $vac = new VacancesOficialsController();

        $cefire['vac_oficials'] = $vac->es_vacances($dia);
        return $cefire;
    }


    /**
     * get_cefire
     *
     * Torna tots el fitxatges fets durant un temps determinat per $any $mes de l'assesor amb el codi $num
     *
     * @param  mixed $request
     * @param  mixed $num
     * @param  mixed $any
     * @param  mixed $mes
     * @return array $ret2 amb les dades sol·licitades
     */
    public function get_cefire(Request $request, $num, $any, $mes)
    {
        //
        $cefire = User::find($num)->cefire()->whereMonth('data', '=', date($mes))->whereYear('data', '=', date($any))->get();
        $ret2 = array();
        foreach ($cefire as $value) {
            # code...
            $ret1 = array("data" => $value->data, "id" => $value->id, "user_id" => $value->user_id, "inici" => $value->inici, "fi" => $value->fi);
            array_push($ret2, $ret1);
        }
        return $ret2;
    }
    /**
     * get_guardia
     *
     * Torna totes les guàrdies durant un temps determinat per $any $mes de l'assesor amb el codi $num
     *
     * @param  mixed $request
     * @param  mixed $num
     * @param  mixed $any
     * @param  mixed $mes
     * @return array $ret2 amb les dades sol·licitades
     */

    public function get_guardia(Request $request, $num, $any, $mes)
    {
        //
        return User::find($num)->guardia()->whereMonth('data', '=', date($mes))->whereYear('data', '=', date($any))->get();
    }

    /**
     * get_curs
     *
     * Torna tots els curs fets per un assessor durant un temps determinat per $any $mes de l'assesor amb el codi $num
     *
     * @param  mixed $request
     * @param  mixed $num
     * @param  mixed $any
     * @param  mixed $mes
     * @return array $ret2 amb les dades sol·licitades
     */

    public function get_curs(Request $request, $num, $any, $mes)
    {
        //
        return User::find($num)->curs()->whereMonth('data', '=', date($mes))->whereYear('data', '=', date($any))->get();
    }

    /**
     * get_compensa
     *visitaemps determinat per $any $mes de l'assesor amb el codi $num
     *
     * @param  mixed $request
     * @param  mixed $num
     * @param  mixed $any
     * @param  mixed $mes
     * @return array $ret2 amb les dades sol·licitades
     */

    public function get_compensa(Request $request, $num, $any, $mes)
    {
        //
        return User::find($num)->compensa()->whereMonth('data', '=', date($mes))->whereYear('data', '=', date($any))->get();
    }

    /**
     * get_visita
     *
     * Torna totes les visites durant un temps determinat per $any $mes de l'assesor amb el codi $num
     *
     * @param  mixed $request
     * @param  mixed $num
     * @param  mixed $any
     * @param  mixed $mes
     * @return array $ret2 amb les dades sol·licitades
     */

    public function get_visita(Request $request, $num, $any, $mes)
    {
        //
        return User::find($num)->visita()->whereMonth('data', '=', date($mes))->whereYear('data', '=', date($any))->get();
    }

    /**
     * get_permis
     *
     * Torna tots els permisos durant un temps determinat per $any $mes de l'assesor amb el codi $num
     *
     * @param  mixed $request
     * @param  mixed $num
     * @param  mixed $any
     * @param  mixed $mes
     * @return array $ret2 amb les dades sol·licitades
     */

    public function get_permis(Request $request, $num, $any, $mes)
    {
        //
        return User::find($num)->permis()->whereMonth('data', '=', date($mes))->whereYear('data', '=', date($any))->get();
    }

    // public function __invoke(Request $request)
    // {
    //     //
    // }


    /**
     * get_allguardia
     *
     * Torna totes les dades d'un assessor en concret
     *
     * @param  mixed $de
     * @param  mixed $fins
     * @return array $ret
     */
    public function get_all($de, $fins)
    {
        //
        $cefire = User::find(auth()->id())->cefire()->where('data', '>', $de)->where('data', '<', $fins)->get();
        $compensa = User::find(auth()->id())->compensa()->where('data', '>', $de)->where('data', '<', $fins)->get();
        $curs = User::find(auth()->id())->curs()->where('data', '>', $de)->where('data', '<', $fins)->get();
        $guardia = User::find(auth()->id())->guardia()->where('data', '>', $de)->where('data', '<', $fins)->get();
        $permis = User::find(auth()->id())->permis()->where('data', '>', $de)->where('data', '<', $fins)->get();
        $visita = User::find(auth()->id())->visita()->where('data', '>', $de)->where('data', '<', $fins)->get();
        $ret = [
            'cefire' => $cefire,
            'compensa' => $compensa,
            'curs' => $curs,
            'guardia' => $guardia,
            'permis' => $permis,
            'visita' => $visita
        ];
        return $ret;



    }

    public function getWeekday($date)
    {
        return date('w', strtotime($date));
    }

    public function get_statistics()
    {

        $vacances_con = new VacancesOficialsController();
        //VacancesController
        $dia_guardia_user = auth()->user()->diaguardia;
        $year = date('Y');
        $mes = date('m');
        $inici = date($year . "-" . $mes . "-1");
        $fi = date($year . "-" . $mes . "-t");

        $dates = $vacances_con->getWorkingDays($inici, $fi);

        //$dates = cefire::select('data')->distinct()->whereBetween('data',[$inici,$fi])->orderBy('data', 'ASC')->get();

        $total_dies = count($dates);
        $usuari = user::where("id", "=", auth()->id())->first();
        //TODO: Agafa vesprada de guardia
        $data_hui = date('Y-m-d');
        $any = date('Y');
        $data_15_oct = date($any . "-10-15");
        $data_15_mai = date($any . "-05-15");


        $total_mes = 0;
        foreach ($dates as $key => $value) {
            $dia_setmana = $this->getWeekday($value);
            # code...
            if ($dia_setmana == $dia_guardia_user) {
                $total_mes += 540;
            } else {
                if (($usuari->guardia()->where('data','=',$value)->count()>0) && ($this->getWeekday($value)==5)){
                    $total_mes += 360;
                } else {
                    $total_mes += 300;
                }
            }


        }



        $este = $this->agafa_dades_suma($usuari, $mes, $any, $inici, $fi, $total_mes, $total_dies);
        unset($este["Nom"]);



        return $este;

    }

    function agafa_dades_suma($value, $mes, $any, $inici, $fi, $total_mes, $total_dies)
    {
        $este = array();

        $este['Nom'] = $value->name;
        $este['fitxatge'] = intval($value->cefire()->select(DB::raw('SUM(TIME_TO_SEC(TIMEDIFF(fi,inici))/60) as total'))->whereBetween('data', [$inici, $fi])->where('fi', '!=', '00:00:00')->first()['total']);

        $este['permís'] = intval($value->permis()->select(DB::raw('SUM(TIME_TO_SEC(TIMEDIFF(fi,inici))/60) as total'))->whereBetween('data', [$inici, $fi])->first()['total']);
        $este['compensa'] = intval($value->compensa()->select(DB::raw('SUM(TIME_TO_SEC(TIMEDIFF(fi,inici))/60) as total'))->whereBetween('data', [$inici, $fi])->first()['total']);
        $este['curs'] = intval($value->curs()->select(DB::raw('SUM(TIME_TO_SEC(TIMEDIFF(fi,inici))/60) as total'))->whereBetween('data', [$inici, $fi])->first()['total']);
        $este['visita'] = intval($value->visita()->select(DB::raw('SUM(TIME_TO_SEC(TIMEDIFF(fi,inici))/60) as total'))->whereBetween('data', [$inici, $fi])->first()['total']);

        $curs_extra = intval($value->curs()->select(DB::raw('SUM(TIME_TO_SEC(TIMEDIFF(fi,inici))/60) as total'))->whereRaw('weekday(data) = 5 and year(data) = ? and month(data)=?',[$any,$mes])->first()['total']);
        $visita_extra = intval($value->visita()->select(DB::raw('SUM(TIME_TO_SEC(TIMEDIFF(fi,inici))/60) as total'))->whereRaw('weekday(data) = 5 and year(data) = ? and month(data)=?',[$any,$mes])->first()['total']);
        $curs_extra2 = intval($value->curs()->select(DB::raw('SUM(TIME_TO_SEC(TIMEDIFF(fi,inici))/60) as total'))->whereRaw('weekday(data) = 4 and year(data) = ? and month(data)=? and inici>="15:00:00"',[$any,$mes])->first()['total']);
        $visita_extra2 = intval($value->visita()->select(DB::raw('SUM(TIME_TO_SEC(TIMEDIFF(fi,inici))/60) as total'))->whereRaw('weekday(data) = 4 and year(data) = ? and month(data)=? and inici>="15:00:00"',[$any,$mes])->first()['total']);



        $este['curs']=$este['curs']+$curs_extra+$curs_extra2;
        $este['visita']=$este['visita']+$visita_extra+$visita_extra2;

        $deutesmes = $value->deutesmes()->first();
        if ($deutesmes) {
            $a = $deutesmes->minuts;
            $este['borsa d\'hores'] = $a;
        } else {
            $este['borsa d\'hores'] = 0;
        }


        $este['total'] = $este['fitxatge'] + $este['permís'] + $este['compensa'] + $este['visita']/*Es suma perquè les està gaudint d'un excés que ha fet altre mes*/+ $este['curs'];


        $este['diferència'] = ($este['total']) - $total_mes; //El total dels dies del mes multiplicat per 60
        $este['id'] = $value->id;
        return $este;
    }

    public function tots_els_dies_mes($any, $mes)
    {

        $vacances = new VacancesOficialsController();
        //VacancesController


        $inici = date($any . "-" .$mes . "-01");
        $fi = date("Y-m-t", strtotime($inici));

        $dates = $vacances->getWorkingDays($inici, $fi);

        //$dates = cefire::select('data')->distinct()->whereBetween('data',[$inici,$fi])->orderBy('data', 'ASC')->get();

        if (count($dates) == 0) {
            abort(403, "No hi ha cap resultat");
        }
        $total_dies = count($dates);
        $usuaris = User::orderBy('name', 'ASC')->get();


        $este = array();
        $a = array();

        $any = date('Y');        
        $vacances = new VacancesOficialsController();



        foreach ($usuaris as $key => $value) {
            # code...
            // Anar agafant-ho tot per dia
            $total_mes = 0;
            $dia_guardia_user = $value->diaguardia;
            foreach ($dates as $key2 => $value2) {
                # code...
                $dia_setmana = $this->getWeekday($value2);
                if ($dia_setmana == $dia_guardia_user) {
                    $total_mes += 540;
                } else {        
                    if (($value->guardia()->where('data','=',$value2)->count()>0) && ($this->getWeekday($value2)==5)){
                        $total_mes += 360;
                    } else {
                        $total_mes += 300;
                    }
                }
            }



            $este = $this->agafa_dades_suma($value, $mes, $any, $inici, $fi, $total_mes, $total_dies);
            array_push($a, $este);
        }


        return $a;

    }

    function detecta_solapaments(Request $valors) {
        $usuari = User::where('id',"=",$valors->id)->first();
        $vacances = new VacancesOficialsController();
        $inici = date($valors->any . "-" . $valors->mes . "-01");
        $fi = date("Y-m-t", strtotime($inici));
        $dates = $vacances->getWorkingDays($inici, $fi);
        $este = array();
        $num = array();
        $compara = array();
        $sobrelapat = array();
        foreach ($dates as $key => $value) {
            # code...
            $num['fitxatge'] = $usuari->cefire()->where('data','=',$value)->count();
            $num['permís'] = $usuari->permis()->where('data','=',$value)->count();
            $num['compensa'] = $usuari->compensa()->where('data','=',$value)->count();
            $num['curs'] = $usuari->curs()->where('data','=',$value)->count();
            $num['visita'] = $usuari->visita()->where('data','=',$value)->count();
            if (($num['fitxatge'] + $num['permís'] + $num['compensa'] + $num['curs'] + $num['visita']) >= 2){
                //return ($num['fitxatge'] + $num['permís'] + $num['compensa'] + $num['curs'] + $num['visita']);
                $compara = [];
                $este = [];
                $este['visita'] = $usuari->visita()->where('data','=',$value)->get();
                if (!$este['visita']->isEmpty()){
                    foreach ($este['visita'] as $key1 => $value1) {
                        # code...
                        array_push($compara,$value1->toArray());
                    }
                }

                $este['fitxatge'] = $usuari->cefire()->where('data','=',$value)->get();
                if (!$este['fitxatge']->isEmpty()){
                    foreach ($este['fitxatge'] as $key2 => $value2) {
                        # code...
                        array_push($compara,$value2->toArray());
                    }
                }

                $este['permís'] = $usuari->permis()->where('data','=',$value)->get();
                if (!$este['permís']->isEmpty()){
                    foreach ($este['permís'] as $key3 => $value3) {
                        # code...
                        array_push($compara,$value3->toArray());
                    }
                }

                $este['compensa'] = $usuari->compensa()->where('data','=',$value)->get();
                if (!$este['compensa']->isEmpty()){
                    foreach ($este['compensa'] as $key4 => $value4) {
                        # code...
                        array_push($compara,$value4->toArray());
                    }
                }

                $este['curs'] = $usuari->curs()->where('data','=',$value)->get();
                if (!$este['curs']->isEmpty()){
                    foreach ($este['curs'] as $key => $value) {
                        # code...
                        array_push($compara,$value->toArray());
                    }
                }

                //return $compara;
                $sobre = $this->checkOverlapInDateRanges($compara);
                if (sizeof($sobre)>0)
                array_push($sobrelapat,$sobre);
                
                
            }

            
    
        }
        return $sobrelapat;

    }

    function checkOverlapInDateRanges($ranges) {
    
        $overlapp = [];
        
        for($i = 0; $i < count($ranges); $i++){
            
            for($j= ($i + 1); $j < count($ranges); $j++){
    
                $start_a = strtotime($ranges[$i]['inici']);
                $end_a = strtotime($ranges[$i]['fi']);
    
                $start_b = strtotime($ranges[$j]['inici']);
                $end_b = strtotime($ranges[$j]['fi']);
    
                if( $start_b <= $end_a && $end_b >= $start_a ) {
                    $m1 = ($end_a -  $start_a)/60;
                    $m2 = ($end_b - $start_b)/60;

                    array_push($overlapp, ["Dia: ". $ranges[$i]['data'] ." de ".$ranges[$i]['inici'] ." - " .$ranges[$i]['fi'] ." es solapa amb " .$ranges[$j]['inici'] ." - " .$ranges[$j]['fi'],($m1>$m2)? $m2:$m1]);
                    break;
                }
                
            }
            
        }
        
        return $overlapp;
        
    }

    function detecta_solapaments_tots(Request $request) {
        
        $usuaris = User::all();
        $ret = array();
        
        foreach ($usuaris as $key => $value) {
            # code...
            $pet = new Request();
            $pet->id = $value->id;
            $pet->mes = $request->mes;
            $pet->any = $request->any;
            $aux = $this->detecta_solapaments($pet);
            if (sizeof($aux)>0){
                array_push($ret,$value->id);
            }
            
        }
        return $ret;
    }
    



function calcula_deutes_mes_usuari($user_id, $fi_opt = null)
    {
        //$any, $mes
        $vacances = new VacancesOficialsController();
        //VacancesController
        $any = date("Y", strtotime("-1 months"));
        $mes = date("m", strtotime("-1 months"));


        $inici = date($any . "-" . $mes . "-01");
        if ($fi_opt === null) {
            $fi = date("Y-m-t", strtotime($inici));
        } else {
            $fi = date($fi_opt);
        }

        $dates = $vacances->getWorkingDays($inici, $fi);

        //$dates = cefire::select('data')->distinct()->whereBetween('data',[$inici,$fi])->orderBy('data', 'ASC')->get();

        if (count($dates) == 0) {
            abort(403, "No hi ha cap resultat");
        }
        $total_dies = count($dates);
        $usuari = User::where('id', "=", $user_id)->first();


        $este = array();
        //$a = array();

        //$data_hui = date('Y-m-d');
        $any = date('Y');

        $total_mes = 0;
        $dia_guardia_user = $usuari->diaguardia;

        
        //El que deuria de fer
        foreach ($dates as $key => $value) {
            $dia_setmana = $this->getWeekday($value);
            # code...
            if ($dia_setmana == $dia_guardia_user) {
                $total_mes += 540;
            } else {
                if (($usuari->guardia()->where('data','=',$value)->count()>0) && ($this->getWeekday($value)==5)){
                    $total_mes += 300; //Seria 360 però com no fitxem per temps, cal deixar-ho en 300
                } else {
                    $total_mes += 300;
                }
            }

        }

        $este = $this->agafa_dades_suma($usuari, $mes, $any, $inici, $fi, $total_mes, $total_dies);
        //$este['diferència'] = 100;

        $pet = new Request();
        $pet->id = $user_id;
        $pet->mes = $mes;
        $pet->any = $any;
        $aux = $this->detecta_solapaments($pet);
        $solapats = 0;
        foreach ($aux as $key => $value) {
            # code...
            foreach ($value as $key2 => $value2) {
                # code...
                $solapats += $value2[1];
            }
            
        }

        $este['solapats'] = $solapats;

        return $este;

    }

    public function avisdiasetmana(Request $request){
        $user = auth()->user();
        $user->diaguardia = $request->diasetmana;
        $user->save();
        return "Dia guardat";
    }

}