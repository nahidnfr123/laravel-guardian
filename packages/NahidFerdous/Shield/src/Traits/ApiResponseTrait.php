<?php

namespace NahidFerdous\Shield\Traits;

trait ApiResponseTrait
{
    protected function success($message, $data = null, $status = 200): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
            //            'user_time' => Carbon::now()->format('Y-m-d H:i:s'),
            //            'server_time' => now(),
        ], $status);
    }

    /**
     * Process failures and ensure an appropriate response and status code is returned.
     *
     * @param  null  $errors
     */
    protected function failure($message, int $status = 400, $errors = null): \Illuminate\Http\JsonResponse
    {
        $statusCode = ($status >= 100 && $status < 600) ? $status : 500;

        $response = [
            'success' => false,
            'message' => $message,
        ];
        if ($errors) {
            if (is_array($errors)) {
                $response['errors'] = $errors;
            } elseif (is_string($errors)) {
                $response['error'] = $errors;
            } else {
                $response['error'] = $errors;
            }
        }

        return response()->json($response, $statusCode);
    }
}
