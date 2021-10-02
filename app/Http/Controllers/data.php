<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use \DB;

use Event;

class data extends BaseController
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
        $start_time = $data["start_time"];
        $end_time = $data["end_time"];
        $count_time = $data["time"]; //min, hour, day
        if (!$start_time) $start_time = '2021-04-01 00:00:00';
        if (!$end_time) $end_time = '2021-05-01 00:00:00';

        $tmp_arr = array();
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

        if ($count_time == 'min') {
            if (count($type) == 1) {
                $table = $type[0];
                $groupby = $type[0]. '.time';
                array_push($where_column_arr, [$type[0].'.time', $type[0].'.time']);
                array_push($select_arr, $type[0].'.time as time');
                array_push($select_arr, DB::raw('round('. $type[0].'.value, 2) as '.$type[0]));
            }else if(count($type) > 1) {
                $groupby = $type[0]. '.time';
                for ($i=0; $i < count($type); $i++) {
                    if ($i == 0) {
                        $table = $type[$i];
                        array_push($select_arr, $type[$i].'.time as time');
                        array_push($select_arr, DB::raw('round('. $type[$i].'.value, 2) as '.$type[$i]));
                    }else {
                        $table = $table. ', '. $type[$i];
                        array_push($select_arr, DB::raw('round('. $type[$i].'.value, 2) as '.$type[$i]));
                        array_push($where_column_arr, [$type[($i-1)].'.time', $type[$i].'.time']);
                    }
                }
            }
        }elseif ($count_time == 'hour') {
            if (count($type) == 1) {
                $table = $type[0];
                $groupby = DB::raw('date_format('. $type[0]. '.time, "%Y-%m-%d %H")');
                array_push($where_column_arr, [$type[0].'.time', $type[0].'.time']);
                array_push($select_arr, DB::raw('date_format('. $type[0].'.time, "%Y-%m-%d %H") as time'));
                array_push($select_arr, DB::raw('round(AVG('. $type[0].'.value), 2) as '.$type[0]));
            }else if(count($type) > 1) {
                $groupby = DB::raw('date_format('. $type[0]. '.time, "%Y-%m-%d %H")');
                for ($i=0; $i < count($type); $i++) {
                    if ($i == 0) {
                        $table = $type[$i];
                        array_push($select_arr, DB::raw('date_format('. $type[$i].'.time, "%Y-%m-%d %H") as time'));
                        array_push($select_arr, DB::raw('round(AVG('. $type[$i].'.value), 2) as '.$type[$i]));
                    }else {
                        $table = $table. ', '. $type[$i];
                        array_push($select_arr, DB::raw('round(AVG('. $type[$i].'.value), 2) as '.$type[$i]));
                        array_push($where_column_arr, [$type[($i-1)].'.time', $type[$i].'.time']);
                    }
                }
            }
        }elseif ($count_time == 'day') {
            if (count($type) == 1) {
                $table = $type[0];
                $groupby = DB::raw('DATE('. $type[0]. '.time)');
                array_push($where_column_arr, [$type[0].'.time', $type[0].'.time']);
                array_push($select_arr, DB::raw('DATE('. $type[0].'.time) as time'));
                array_push($select_arr, DB::raw('round(AVG('. $type[0].'.value), 2) as '.$type[0]));
            }else if(count($type) > 1) {
                $groupby = DB::raw('DATE('. $type[0]. '.time)');
                for ($i=0; $i < count($type); $i++) {
                    if ($i == 0) {
                        $table = $type[$i];
                        array_push($select_arr, DB::raw('DATE('. $type[$i].'.time) as time'));
                        array_push($select_arr, DB::raw('round(AVG('. $type[$i].'.value), 2) as '.$type[$i]));
                    }else {
                        $table = $table. ', '. $type[$i];
                        array_push($select_arr, DB::raw('round(AVG('. $type[$i].'.value), 2) as '.$type[$i]));
                        array_push($where_column_arr, [$type[($i-1)].'.time', $type[$i].'.time']);
                    }
                }
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
        ->select($select_arr)
        ->groupBy($groupby)
        ->get();

        $log = DB::getQueryLog();
        // dd($log[0]['bindings']);
        // $log[1]['bindings'][0] = '`'. str_replace('.','`.`',$log[0]['bindings'][1]) . '`';
        // $data = DB::select($log[0]['query'], [$log[0]['bindings'][0], $log[0]['bindings'][1]]);
        // dd(DB::getQueryLog());
        // echo $data;
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
        $start_time = $data["start_time"];
        $end_time = $data["end_time"];
        $count_time = $data["time"]; //min, hour, day
        if (!$start_time) $start_time = '2021-04-01 00:00:00';
        if (!$end_time) $end_time = '2021-05-01 00:00:00';

        $tmp_arr = array();
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

        if ($count_time == 'min') {
            if (count($type) == 1) {
                $table = $type[0];
                $groupby = $type[0]. '.time';

                array_push($where_column_arr, [$type[0].'.time', $type[0].'.time']);
                array_push($select_arr, $type[0].'.time as time');
                array_push($select_arr, DB::raw('round('. $type[0].'.value, 2) as '.$type[0]));

                array_push($select_concat_arr,DB::raw("concat('[',GROUP_CONCAT(time),']') as time"));
                array_push($select_concat_arr,DB::raw("concat('[',GROUP_CONCAT(". $type[0]. "),']') as ". $type[0]));
            }else if(count($type) > 1) {
                $groupby = $type[0]. '.time';
                for ($i=0; $i < count($type); $i++) {
                    if ($i == 0) {
                        $table = $type[$i];
                        array_push($select_arr, $type[$i].'.time as time');
                        array_push($select_arr, DB::raw('round('. $type[$i].'.value, 2) as '.$type[$i]));

                        array_push($select_concat_arr,DB::raw("concat('[',GROUP_CONCAT(time),']') as time"));
                        array_push($select_concat_arr,DB::raw("concat('[',GROUP_CONCAT(". $type[$i]. "),']') as ". $type[$i]));
                    }else {
                        $table = $table. ', '. $type[$i];
                        array_push($select_arr, DB::raw('round('. $type[$i].'.value, 2) as '.$type[$i]));
                        array_push($where_column_arr, [$type[($i-1)].'.time', $type[$i].'.time']);

                        array_push($select_concat_arr,DB::raw("concat('[',GROUP_CONCAT(". $type[$i]. "),']') as ". $type[$i]));
                    }
                }
            }
        }elseif ($count_time == 'hour') {
            if (count($type) == 1) {
                $table = $type[0];
                $groupby = DB::raw('date_format('. $type[0]. '.time, "%Y-%m-%d %H")');
                array_push($where_column_arr, [$type[0].'.time', $type[0].'.time']);
                array_push($select_arr, DB::raw('date_format('. $type[0].'.time, "%Y-%m-%d %H") as time'));
                array_push($select_arr, DB::raw('round(AVG('. $type[0].'.value), 2) as '.$type[0]));

                array_push($select_concat_arr,DB::raw("concat('[',GROUP_CONCAT(time),']') as time"));
                array_push($select_concat_arr,DB::raw("concat('[',GROUP_CONCAT(". $type[0]. "),']') as ". $type[0]));
            }else if(count($type) > 1) {
                $groupby = DB::raw('date_format('. $type[0]. '.time, "%Y-%m-%d %H")');
                for ($i=0; $i < count($type); $i++) {
                    if ($i == 0) {
                        $table = $type[$i];
                        array_push($select_arr, DB::raw('date_format('. $type[$i].'.time, "%Y-%m-%d %H") as time'));
                        array_push($select_arr, DB::raw('round(AVG('. $type[$i].'.value), 2) as '.$type[$i]));

                        array_push($select_concat_arr,DB::raw("concat('[',GROUP_CONCAT(time),']') as time"));
                        array_push($select_concat_arr,DB::raw("concat('[',GROUP_CONCAT(". $type[$i]. "),']') as ". $type[$i]));
                    }else {
                        $table = $table. ', '. $type[$i];
                        array_push($select_arr, DB::raw('round(AVG('. $type[$i].'.value), 2) as '.$type[$i]));
                        array_push($where_column_arr, [$type[($i-1)].'.time', $type[$i].'.time']);

                        array_push($select_concat_arr,DB::raw("concat('[',GROUP_CONCAT(". $type[$i]. "),']') as ". $type[$i]));
                    }
                }
            }
        }elseif ($count_time == 'day') {
            if (count($type) == 1) {
                $table = $type[0];
                $groupby = DB::raw('DATE('. $type[0]. '.time)');
                array_push($where_column_arr, [$type[0].'.time', $type[0].'.time']);
                array_push($select_arr, DB::raw('DATE('. $type[0].'.time) as time'));
                array_push($select_arr, DB::raw('round(AVG('. $type[0].'.value), 2) as '.$type[0]));

                array_push($select_concat_arr,DB::raw("concat('[',GROUP_CONCAT(time),']') as time"));
                array_push($select_concat_arr,DB::raw("concat('[',GROUP_CONCAT(". $type[0]. "),']') as ". $type[0]));
            }else if(count($type) > 1) {
                $groupby = DB::raw('DATE('. $type[0]. '.time)');
                for ($i=0; $i < count($type); $i++) {
                    if ($i == 0) {
                        $table = $type[$i];
                        array_push($select_arr, DB::raw('DATE('. $type[$i].'.time) as time'));
                        array_push($select_arr, DB::raw('round(AVG('. $type[$i].'.value), 2) as '.$type[$i]));

                        array_push($select_concat_arr,DB::raw("concat('[',GROUP_CONCAT(time),']') as time"));
                        array_push($select_concat_arr,DB::raw("concat('[',GROUP_CONCAT(". $type[$i]. "),']') as ". $type[$i]));
                    }else {
                        $table = $table. ', '. $type[$i];
                        array_push($select_arr, DB::raw('round(AVG('. $type[$i].'.value), 2) as '.$type[$i]));
                        array_push($where_column_arr, [$type[($i-1)].'.time', $type[$i].'.time']);

                        array_push($select_concat_arr,DB::raw("concat('[',GROUP_CONCAT(". $type[$i]. "),']') as ". $type[$i]));
                    }
                }
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
        ->select($select_arr)
        ->groupBy($groupby);

        $sql = $query->toSql();
        $getBindings = $query->getBindings();

        // $getBindings = [$start_time,$end_time];

        // $sql = str_replace('?', '%s', $query->toSql());
        // $sql = sprintf($sql, ...$getBindings);

        $query2 = DB::table(DB::raw('('.$sql.') as tb'))
        ->select($select_concat_arr)->toSql();

        $query3 = DB::select($query2, $getBindings);
        $log = DB::getQueryLog();
        // dd($log[0]['bindings']);
        // $log[1]['bindings'][0] = '`'. str_replace('.','`.`',$log[0]['bindings'][1]) . '`';
        // $data = DB::select($log[0]['query'], [$log[0]['bindings'][0], $log[0]['bindings'][1]]);
        // dd(DB::getQueryLog());
        // echo $data;
        return response()->json(['status' => 200, 'msg' => "success", 'log' => $log, 'datas' => $query3]);
    }
}
