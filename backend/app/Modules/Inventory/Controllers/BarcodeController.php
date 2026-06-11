<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Controllers;

use App\Http\Controllers\BaseController;
use App\Modules\Inventory\Models\ProductBarcode;
use App\Modules\Inventory\Resources\ProductResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BarcodeController extends BaseController
{
    /**
     * GET /api/inventory/barcodes/lookup?barcode={barcode}
     *
     * Resolves a scanned barcode string to its parent product.
     * Used by the POS scanner endpoint.
     */
    public function lookup(Request $request): JsonResponse
    {
        $request->validate([
            'barcode' => ['required', 'string', 'max:100'],
        ]);

        $barcodeRecord = ProductBarcode::where('barcode', $request->input('barcode'))
            ->with(['product.category', 'product.barcodes', 'product.variants'])
            ->firstOrFail();

        return $this->successResponse(new ProductResource($barcodeRecord->product));
    }
}
