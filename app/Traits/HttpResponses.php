<?php

namespace App\Traits;

trait HttpResponses {
    protected function response_success ($data = [], $message = '', $status_code = 200)
    {
        return response()->json([
            'status' => $status_code,
            'message' => $message,
            'data' => $data,
        ], $status_code);
    }

    protected function response_error($message = '', $errors = [], $status_code)
    {
        return response()->json([
            'status' => $status_code,
            'message' => $message,
            'errors' => $errors,
        ], $status_code);
    }
}
