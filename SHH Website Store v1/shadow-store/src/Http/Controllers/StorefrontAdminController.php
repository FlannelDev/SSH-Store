<?php

namespace App\Plugins\ShadowStore\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Plugins\ShadowStore\Models\MediaAsset;
use App\Plugins\ShadowStore\Services\StorefrontContentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class StorefrontAdminController extends Controller
{
    public function __construct(private StorefrontContentService $storefrontContent)
    {
    }

    public function updateSection(Request $request, string $section): JsonResponse
    {
        $this->authorizeAdmin();

        $payload = (array) $request->input('data', []);

        match ($section) {
            'header' => $this->storefrontContent->saveHeader($payload),
            'background' => $this->storefrontContent->saveBackground($payload),
            'hero' => $this->storefrontContent->saveHero($payload),
            'footer-notice' => $this->storefrontContent->saveFooterNotice($payload),
            default => abort(404),
        };

        return response()->json(['ok' => true]);
    }

    public function updateBlock(Request $request, string $block): JsonResponse
    {
        $this->authorizeAdmin();

        $this->storefrontContent->saveBlock($block, (array) $request->input('data', []));

        return response()->json(['ok' => true]);
    }

    public function reorderBlocks(Request $request): JsonResponse
    {
        $this->authorizeAdmin();

        $order = $this->storefrontContent->saveBlockOrder((array) $request->input('order', []));

        return response()->json(['ok' => true, 'order' => $order]);
    }

    public function uploadMedia(Request $request): JsonResponse
    {
        $this->authorizeAdmin();

        $validator = Validator::make($request->all(), [
            'file' => ['required', 'image', 'max:10240'],
            'name' => ['nullable', 'string', 'max:255'],
            'alt_text' => ['nullable', 'string', 'max:255'],
        ], [
            'file.required' => 'Choose an image before uploading.',
            'file.image' => 'The selected file must be a valid image format.',
            'file.max' => 'The image must be smaller than 10 MB.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'ok' => false,
                'message' => $validator->errors()->first() ?: 'Upload validation failed.',
                'errors' => $validator->errors()->toArray(),
            ], 422);
        }

        $validated = $validator->validated();

        $file = $validated['file'];
        $filename = Str::random(24) . '.' . $file->getClientOriginalExtension();
        $path = $file->storeAs('store/media', $filename, 'public');
        $assetName = trim((string) ($validated['name'] ?? ''));

        $asset = MediaAsset::query()->create([
            'name' => $assetName !== '' ? $assetName : pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME),
            'file_path' => $path,
            'alt_text' => $validated['alt_text'] ?? null,
        ]);

        return response()->json([
            'ok' => true,
            'asset' => [
                'id' => $asset->id,
                'name' => $asset->name,
                'alt_text' => $asset->alt_text,
                'url' => $asset->public_url,
            ],
        ]);
    }

    private function authorizeAdmin(): void
    {
        abort_unless(auth()->check() && auth()->user()->isAdmin(), 403);
    }
}