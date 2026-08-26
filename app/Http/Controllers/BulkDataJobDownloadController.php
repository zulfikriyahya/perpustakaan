<?php

namespace App\Http\Controllers;

use App\Enums\StatusBulkJob;
use App\Models\BulkDataJob;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BulkDataJobDownloadController extends Controller
{
    public function __invoke(BulkDataJob $bulkDataJob): StreamedResponse|Response
    {
        abort_unless(auth()->user()?->hasRole('super_admin'), 403);
        abort_unless($bulkDataJob->status === StatusBulkJob::Selesai && $bulkDataJob->file_path, 404);

        return response()->streamDownload(function () use ($bulkDataJob) {
            echo Storage::disk('local')->get($bulkDataJob->file_path);
        }, basename($bulkDataJob->file_path));
    }
}
