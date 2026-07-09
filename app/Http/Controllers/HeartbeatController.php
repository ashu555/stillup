<?php

namespace App\Http\Controllers;

use App\Actions\RecordHeartbeatAction;
use Illuminate\Http\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

class HeartbeatController extends Controller
{
    public function __invoke(string $token, RecordHeartbeatAction $action): Response
    {
        try {
            $result = $action->execute($token);
        } catch (HttpException $exception) {
            return response($exception->getMessage(), $exception->getStatusCode());
        }

        return response()->noContent($result['status']);
    }
}
