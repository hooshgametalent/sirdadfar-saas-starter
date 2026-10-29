<?php

namespace App\Http\Controllers;

use App\Models\DriverDocument;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Streams a driver document from the private disk. Only the owning driver
 * and admins or operators (DriverDocumentPolicy::view) may download it.
 */
class DriverDocumentController extends Controller
{
    public function show(DriverDocument $document): StreamedResponse
    {
        Gate::authorize('view', $document);

        $disk = Storage::disk(config('freight.documents.disk'));

        abort_unless($disk->exists($document->path), 404);

        return $disk->response($document->path, $document->original_name, [
            'Content-Type' => $document->mime,
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
