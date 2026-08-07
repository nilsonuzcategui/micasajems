<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Models\SuscriptorPush;
use App\Request;
use App\Response;

final class SuscripcionController
{
    public function log(): void
    {
        $all = Request::all();
        $sendpulseId = isset($all['sendpulse_id']) ? trim((string)$all['sendpulse_id']) : null;
        $id = SuscriptorPush::log($sendpulseId ?: null);
        Response::ok(['id' => $id, 'message' => 'Suscripción registrada']);
    }
}