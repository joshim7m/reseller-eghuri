<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\ExportCatalogJob;
use App\Jobs\ImportCatalogJob;
use App\Models\CatalogExport;
use App\Models\CatalogImport;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CatalogController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/Settings/Catalog', [
            'exports' => CatalogExport::with('user')->latest()->limit(10)->get(),
            'imports' => CatalogImport::with('user')->latest()->limit(10)->get(),
        ]);
    }

    public function export(Request $request): RedirectResponse
    {
        $export = CatalogExport::create([
            'user_id' => $request->user()->id,
        ]);

        ExportCatalogJob::dispatch($export->id);

        return back()->with('success', 'Catalog export started.');
    }

    public function import(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'file' => 'required|file|mimes:zip,csv,xlsx,xls|max:204800',
        ]);

        $file = $request->file('file');
        $storedPath = $file->store('catalog/imports/original', 'local');

        $import = CatalogImport::create([
            'user_id' => $request->user()->id,
            'original_name' => $file->getClientOriginalName(),
            'file_path' => $storedPath,
        ]);

        ImportCatalogJob::dispatch($import->id);

        return back()->with('success', 'Catalog import started.');
    }

    public function status(): JsonResponse
    {
        return response()->json([
            'exports' => CatalogExport::latest()->limit(10)->get(),
            'imports' => CatalogImport::latest()->limit(10)->get(),
        ]);
    }

    public function download(CatalogExport $export): StreamedResponse
    {
        abort_unless($export->status === CatalogExport::STATUS_COMPLETED && $export->file_path, 404);

        return Storage::disk('local')->download($export->file_path, 'catalog-exports-'.$export->id.'.zip');
    }
}
