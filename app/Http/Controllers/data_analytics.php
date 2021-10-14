<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use \DB;

use Event;

class data_analytics extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;
    public function list(Request $request)
    {
        date_default_timezone_set('Asia/Taipei');

        $data = $request->all();

        if (!$data) {
            return response()->json(['status' => 400, 'msg' => "沒有傳送任何資料", 'request' => $request, 'request_data' => $data, 'request_data_content' => $request->getContent()]);
        }
        $type = $data["type"];
        if(isset($data["advanced"]))
            $advanced = $data["advanced"];
        else
            $advanced = [];
        $start_time = $data["start_time"];
        $end_time = $data["end_time"];
        $count_time = $data["time"]; //min, hour, day
        if (!$start_time) $start_time = '2021-04-01 00:00:00';
        if (!$end_time) $end_time = '2021-05-01 00:00:00';
        // dd($advanced);
        $tmp_arr = array();
        $where = [];
        $where_column_arr=[];
        $select_arr = [];
        $table = '';
        $groupby = '';
        // $where_column_arr = [[$tableArr[0].'.time', $tableArr[1].'.time']];
        // $select_arr = [$tableArr[0].'.time as time', $tableArr[0].'.value as '.$tableArr[0], $tableArr[1].'.value as '.$tableArr[1]];
        // $table = $tableArr[0]. ','. $tableArr[1];
        if (count($type) < 1) {
            return response()->json(['status' => 400, 'msg' => "請選擇適用的sensor"]);
        }

        if (count($advanced) > 0) {
            for ($i=0; $i < count($advanced); $i++) {
                if (in_array($advanced[$i]['sensor'],$type)) {
                    array_push($where, [$advanced[$i]['sensor'].'.value',$advanced[$i]['operation'], $advanced[$i]['value']]);
                }
            }
        }

        if ($count_time == 'min') {
            $groupby = $type[0]. '.time';
            for ($i=0; $i < count($type); $i++) {
                if ($i == 0) {
                    $table = $type[$i];
                    array_push($select_arr, $type[$i].'.time as time');
                    if (count($type) == 1) {
                        array_push($where_column_arr, [$type[$i].'.time', $type[$i].'.time']);
                    }
                }else {
                    $table = $table. ', '. $type[$i];
                    array_push($where_column_arr, [$type[($i-1)].'.time', $type[$i].'.time']);
                }
                array_push($select_arr, DB::raw('round(AVG('. $type[$i].'.value), 2) as '.$type[$i]));
            }

        }elseif ($count_time == 'hour') {
            $groupby = DB::raw('date_format('. $type[0]. '.time, "%Y-%m-%d %H")');
            for ($i=0; $i < count($type); $i++) {
                if ($i == 0) {
                    $table = $type[$i];
                    array_push($select_arr, DB::raw('date_format('. $type[$i].'.time, "%Y-%m-%d %H") as time'));
                    if (count($type) == 1) {
                        array_push($where_column_arr, [$type[$i].'.time', $type[$i].'.time']);
                    }
                }else {
                    $table = $table. ', '. $type[$i];
                    array_push($where_column_arr, [$type[($i-1)].'.time', $type[$i].'.time']);
                }
                array_push($select_arr, DB::raw('round(AVG('. $type[$i].'.value), 2) as '.$type[$i]));
            }

        }elseif ($count_time == 'day') {
            $groupby = DB::raw('DATE('. $type[0]. '.time)');
            for ($i=0; $i < count($type); $i++) {
                if ($i == 0) {
                    $table = $type[$i];
                    array_push($select_arr, DB::raw('DATE('. $type[$i].'.time) as time'));
                    if (count($type) == 1) {
                        array_push($where_column_arr, [$type[$i].'.time', $type[$i].'.time']);
                    }
                }else {
                    $table = $table. ', '. $type[$i];
                    array_push($where_column_arr, [$type[($i-1)].'.time', $type[$i].'.time']);
                }
                array_push($select_arr, DB::raw('round(AVG('. $type[$i].'.value), 2) as '.$type[$i]));
            }

        }


        DB::enableQueryLog();
        // $data = DB::select('select * from atp');
        Event::listen('illuminate.query', function($query, $params, $time, $conn)
        {
            dd(array($query, $params, $time, $conn));
        });

        $query = DB::table(DB::raw($table))
        ->whereBetween($type[0].'.time' ,[$start_time, $end_time])
        ->where($where)
        ->whereColumn($where_column_arr)
        ->select($select_arr)
        ->groupBy($groupby)
        ->orderBy($groupby)
        ->get();

        $log = DB::getQueryLog();
        // dd($log[0]['bindings']);
        // $log[1]['bindings'][0] = '`'. str_replace('.','`.`',$log[0]['bindings'][1]) . '`';
        // $data = DB::select($log[0]['query'], [$log[0]['bindings'][0], $log[0]['bindings'][1]]);
        // dd(DB::getQueryLog());
        // echo $data;
        if (!$query) {
            return response()->json(['status' => 200, 'msg' => "查無紀錄", 'log' => $log, 'datas' => $query]);
        }
        return response()->json(['status' => 200, 'msg' => "success", 'log' => $log, 'datas' => $query]);
    }

    public function chart(Request $request)
    {
        date_default_timezone_set('Asia/Taipei');

        $data = $request->all();

        if (!$data) {
            return response()->json(['status' => 400, 'msg' => "沒有傳送任何資料", 'request' => $request, 'request_data' => $data, 'request_data_content' => $request->getContent()]);
        }
        $type = $data["type"];
        if(isset($data["advanced"]))
            $advanced = $data["advanced"];
        else
            $advanced = [];
        $start_time = $data["start_time"];
        $end_time = $data["end_time"];
        $count_time = $data["time"]; //min, hour, day
        if (!$start_time) $start_time = '2021-04-01 00:00:00';
        if (!$end_time) $end_time = '2021-05-01 00:00:00';

        $tmp_arr = array();
        $where = [];
        $where_column_arr=[];
        $select_arr = [];
        $select_concat_arr = [];
        $table = '';
        $groupby = '';
        // $where_column_arr = [[$tableArr[0].'.time', $tableArr[1].'.time']];
        // $select_arr = [$tableArr[0].'.time as time', $tableArr[0].'.value as '.$tableArr[0], $tableArr[1].'.value as '.$tableArr[1]];
        // $table = $tableArr[0]. ','. $tableArr[1];
        if (count($type) < 1) {
            return response()->json(['status' => 400, 'msg' => "請選擇適用的sensor"]);
        }

        if (count($advanced) > 0) {
            for ($i=0; $i < count($advanced); $i++) {
                if (in_array($advanced[$i]['sensor'],$type)) {
                    array_push($where, [$advanced[$i]['sensor'].'.value',$advanced[$i]['operation'], $advanced[$i]['value']]);
                }
            }
        }

        if ($count_time == 'min') {
            $groupby = $type[0]. '.time';
            for ($i=0; $i < count($type); $i++) {
                if ($i == 0) {
                    $table = $type[$i];
                    array_push($select_arr, $type[$i].'.time as time');
                    if (count($type) == 1) {
                        array_push($where_column_arr, [$type[$i].'.time', $type[$i].'.time']);
                    }
                    array_push($select_concat_arr,DB::raw("concat('[',GROUP_CONCAT(CONCAT(CHAR(34),time,CHAR(34))),']') as time"));
                }else {
                    $table = $table. ', '. $type[$i];
                    array_push($where_column_arr, [$type[($i-1)].'.time', $type[$i].'.time']);
                }
                array_push($select_arr, DB::raw('round(AVG('. $type[$i].'.value), 2) as '.$type[$i]));
                array_push($select_concat_arr,DB::raw("concat('[',GROUP_CONCAT(". $type[$i]. "),']') as ". $type[$i]));
            }

        }elseif ($count_time == 'hour') {
            $groupby = DB::raw('date_format('. $type[0]. '.time, "%Y-%m-%d %H")');
            for ($i=0; $i < count($type); $i++) {
                if ($i == 0) {
                    $table = $type[$i];
                    array_push($select_arr, DB::raw('date_format('. $type[$i].'.time, "%Y-%m-%d %H") as time'));
                    if (count($type) == 1) {
                        array_push($where_column_arr, [$type[$i].'.time', $type[$i].'.time']);
                    }
                    array_push($select_concat_arr,DB::raw("concat('[',GROUP_CONCAT(CONCAT(CHAR(34),time,CHAR(34))),']') as time"));
                }else {
                    $table = $table. ', '. $type[$i];
                    array_push($where_column_arr, [$type[($i-1)].'.time', $type[$i].'.time']);
                }
                array_push($select_arr, DB::raw('round(AVG('. $type[$i].'.value), 2) as '.$type[$i]));
                array_push($select_concat_arr,DB::raw("concat('[',GROUP_CONCAT(". $type[$i]. "),']') as ". $type[$i]));
            }

        }elseif ($count_time == 'day') {
            $groupby = DB::raw('DATE('. $type[0]. '.time)');
            for ($i=0; $i < count($type); $i++) {
                if ($i == 0) {
                    $table = $type[$i];
                    array_push($select_arr, DB::raw('DATE('. $type[$i].'.time) as time'));
                    if (count($type) == 1) {
                        array_push($where_column_arr, [$type[$i].'.time', $type[$i].'.time']);
                    }
                    array_push($select_concat_arr,DB::raw("concat('[',GROUP_CONCAT(CONCAT(CHAR(34),time,CHAR(34))),']') as time"));
                }else {
                    $table = $table. ', '. $type[$i];
                    array_push($where_column_arr, [$type[($i-1)].'.time', $type[$i].'.time']);
                }
                array_push($select_arr, DB::raw('round(AVG('. $type[$i].'.value), 2) as '.$type[$i]));
                array_push($select_concat_arr,DB::raw("concat('[',GROUP_CONCAT(". $type[$i]. "),']') as ". $type[$i]));
            }

        }


        DB::enableQueryLog();
        // $data = DB::select('select * from atp');
        Event::listen('illuminate.query', function($query, $params, $time, $conn)
        {
            dd(array($query, $params, $time, $conn));
        });

        $query = DB::table(DB::raw($table))
        ->whereBetween($type[0].'.time' ,[$start_time, $end_time])
        ->whereColumn($where_column_arr)
        ->where($where)
        ->select($select_arr)
        ->groupBy($groupby)
        ->orderBy($groupby);


        $sql = $query->toSql();
        $getBindings = $query->getBindings();

        // $getBindings = [$start_time,$end_time];

        // $sql = str_replace('?', '%s', $query->toSql());
        // $sql = sprintf($sql, ...$getBindings);

        $query2 = DB::table(DB::raw('('.$sql.') as tb'))
        ->select($select_concat_arr)->toSql();

        $query3 = DB::select($query2, $getBindings);
        $log = DB::getQueryLog();
        if (!$query3) {
            return response()->json(['status' => 200, 'msg' => "查無紀錄", 'log' => $log, 'datas' => $query3]);
        }
        if ($query3[0]->time) $query3[0]->time = json_decode($query3[0]->time, true);
        for ($i=0; $i < count($type); $i++) {
            $string = (string)($type[$i]);
            if ($string == 'atp') $query3[0]->atp = json_decode($query3[0]->atp, true);
            if ($string == 'ec') $query3[0]->ec = json_decode($query3[0]->ec, true);
            if ($string == 'humidity') $query3[0]->humidity = json_decode($query3[0]->humidity, true);
            if ($string == 'luminance') $query3[0]->luminance = json_decode($query3[0]->luminance, true);
            if ($string == 'ph') $query3[0]->ph = json_decode($query3[0]->ph, true);
            if ($string == 'soil_humid') $query3[0]->soil_humid = json_decode($query3[0]->soil_humid, true);
            if ($string == 'soil_temp') $query3[0]->soil_temp = json_decode($query3[0]->soil_temp, true);
            if ($string == 'temp') $query3[0]->temp = json_decode($query3[0]->temp, true);
            if ($string == 'uv') $query3[0]->uv = json_decode($query3[0]->uv, true);
        }


        // dd($log[0]['bindings']);
        // $log[1]['bindings'][0] = '`'. str_replace('.','`.`',$log[0]['bindings'][1]) . '`';
        // $data = DB::select($log[0]['query'], [$log[0]['bindings'][0], $log[0]['bindings'][1]]);
        // dd(DB::getQueryLog());
        // echo $data;
        return response()->json(['status' => 200, 'msg' => "success", 'log' => $log, 'datas' => $query3]);
    }

    public function corr_sql($count_time = 'min', $column1 = '', $column2 = '', $start_time = '2021-04-01 00:00:00', $end_time = '2021-05-01 00:00:00')
    {
        $tmp_arr = array();
        $where_column_arr=[];
        $select_arr = [];
        $select_concat_arr = [];
        $table = $column1. ', '. $column2;
        $groupby = '';
        array_push($select_arr,DB::raw('@a1:=AVG('. $column1. '.value)'));
        array_push($select_arr,DB::raw('@b1:=AVG('. $column2. '.value)'));
        array_push($select_arr,DB::raw('@c1:=(stddev_samp('.$column1.'.value) * stddev_samp('. $column2. '.value))'));

        array_push($where_column_arr, [$column1. '.time', $column2. '.time']);

        $query = DB::table(DB::raw($table))
        ->whereColumn($where_column_arr)
        ->whereBetween($column1. '.time' ,[$start_time, $end_time])
        ->select($select_arr);


        $sql = $query->toSql();
        $getBindings = $query->getBindings();

        // dd($sql,$getBindings);

        // $getBindings = [$start_time,$end_time];
        // $sql = str_replace('?', '%s', $query->toSql());
        // $sql = sprintf($sql, ...$getBindings);

        $query2 = DB::table(DB::raw($table .', ('. $sql. ') as tb'))
        ->select(DB::raw('round((sum( ( '. $column1. '.value - @a1 ) * ('. $column2. '.value - @b1) ) / ((count('. $column1. '.value) -1) * @c1)), 4) as p'))
        ->whereColumn($where_column_arr)
        ->whereBetween($column1. '.time' ,[$start_time, $end_time]);
        $sql2 = $query2->toSql();
        $getBindings2 = $query2->getBindings();
        $bindings = array_merge($getBindings, $getBindings2);
        // dd($sql2,$bindings);
        // dd($query2,$getBindings);
        $query3 = DB::select($sql2, $bindings);
        // dd($query3[0]->p);
        return $query3[0]->p;
    }

    public function correlation(Request $request)
    {
        date_default_timezone_set('Asia/Taipei');
        // $sensor_arr = ['atp', 'ec', 'humidity', 'luminance', 'ph', 'soil_humid', 'soil_temp', 'temp', 'uv'];
        $sensor_arr = ['luminance', 'temp', 'humidity', 'soil_temp', 'soil_humid', 'ec', 'ph', 'atp', 'uv'];
        $data = $request->all();

        if (!$data) {
            return response()->json(['status' => 400, 'msg' => "沒有傳送任何資料", 'request' => $request, 'request_data' => $data, 'request_data_content' => $request->getContent()]);
        }
        $start_time = $data["start_time"];
        $end_time = $data["end_time"];
        $count_time = $data["time"]; //min, hour, day
        if (!$start_time) $start_time = '2021-04-01 00:00:00';
        if (!$end_time) $end_time = '2021-05-01 00:00:00';
        if ($count_time != 'min') {
            return response()->json(['status' => 400, 'msg' => "目前僅可查詢分的相似值"]);
        }
        $corr = array();
        $tmp_arr = array();
        for ($i=0; $i < count($sensor_arr); $i++) {
            $tmp_arr = array();
            for ($j=0; $j < count($sensor_arr); $j++) {
                if ($j == 0) {
                    $tmp_arr = array_merge($tmp_arr, ["header" => $sensor_arr[$i]]);
                }
                if ($i > $j) {
                    $ans = (double)($corr[$j][$sensor_arr[$i]]);
                    $tmp_arr = array_merge($tmp_arr, [$sensor_arr[$j] => $ans ]);
                }else if($sensor_arr[$i] == $sensor_arr[$j]){
                    $tmp_arr = array_merge($tmp_arr, [$sensor_arr[$j] => '-']);
                }else{
                    $ans = $this->corr_sql($count_time, $sensor_arr[$i], $sensor_arr[$j], $start_time, $end_time);
                    $tmp_arr = array_merge($tmp_arr, [$sensor_arr[$j] => $ans]);
                }
            }
            array_push($corr, $tmp_arr);
        }

        // dd($corr);
        if (!$corr) {
            return response()->json(['status' => 200, 'msg' => "查無紀錄", 'datas' => $corr]);
        }
        return response()->json(['status' => 200, 'msg' => "success", 'datas' => $corr]);
    }
}
