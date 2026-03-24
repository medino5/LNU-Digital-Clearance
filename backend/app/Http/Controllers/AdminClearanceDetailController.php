<?php

namespace App\Http\Controllers;

use App\Models\Clearance;
use App\Support\AdminClearanceDetailBuilder;

class AdminClearanceDetailController extends Controller
{
    public function __construct(
        protected AdminClearanceDetailBuilder $detailBuilder,
    ) {
    }

    public function show(Clearance $clearance)
    {
        if ($clearance->status !== Clearance::STATUS_COMPLETED) {
            abort(404, 'Completed clearance record not found.');
        }

        return response()->json([
            'data' => $this->detailBuilder->build($clearance),
        ]);
    }
}
