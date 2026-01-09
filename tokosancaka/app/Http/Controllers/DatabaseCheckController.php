<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Throwable;

class DatabaseCheckController extends Controller
{
    public function index()
    {
        $database = DB::getDatabaseName();
        $status = [
            'database' => $database,
            'connection' => 'OK',
            'tables' => [],
        ];

        try {
            $tables = DB::select('SHOW TABLES');
            $tableKey = 'Tables_in_' . $database;

            foreach ($tables as $table) {
                $tableName = $table->$tableKey;
                try {
                    $count = DB::table($tableName)->count();
                    $status['tables'][] = [
                        'name' => $tableName,
                        'status' => 'OK',
                        'rows' => $count,
                    ];
                } catch (Throwable $e) {
                    $status['tables'][] = [
                        'name' => $tableName,
                        'status' => 'ERROR',
                        'rows' => 0,
                        'error' => $e->getMessage(),
                    ];
                }
            }

        } catch (Throwable $e) {
            $status['connection'] = 'ERROR: ' . $e->getMessage();
        }

        return response()->json($status);
    }
}
