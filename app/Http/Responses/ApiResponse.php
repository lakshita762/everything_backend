<?php

namespace App\Http\Responses;

trait ApiResponse
{
    protected function success($data = null, string $message = 'OK', int $status = 200)
    {
        return response()->json(['success'=>true,'message'=>$message,'data'=>$data], $status);
    }

    protected function error(string $message='Error', int $status=400, $errors=null)
    {
        return response()->json(['success'=>false,'message'=>$message,'errors'=>$errors], $status);
    }
}
