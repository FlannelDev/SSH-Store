<?php

namespace App\Plugins\ShadowStore\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Plugins\ShadowStore\Services\StorefrontContentService;
use App\Plugins\ShadowStore\Models\Product;
use App\Plugins\ShadowStore\Models\DedicatedMachine;
use Illuminate\Http\Request;

class StoreController extends Controller
{
    public function __construct(private StorefrontContentService $storefrontContent)
    {
    }

    public function index()
    {
        $featuredProducts = Product::with('imageAsset')->where('is_active', true)
            ->where('is_featured', true)
            ->orderBy('sort_order')
            ->get();

        $products = Product::with('imageAsset')->where('is_active', true)
            ->orderBy('sort_order')
            ->get()
            ->groupBy('category');

        $storeHome = $this->storefrontContent->getStoreHome();
        $footerNotice = $this->storefrontContent->getFooterNotice();
        $storeBackground = $this->storefrontContent->getBackgroundSettings();
        $homepageBlocks = $this->storefrontContent->getHomepageBlocks();
        $storeEditorState = $this->storefrontContent->getEditorState();
        $mediaAssets = $this->storefrontContent->getMediaAssets();

        return view('shadow-store::pages.storefront', compact('featuredProducts', 'products', 'storeHome', 'footerNotice', 'storeBackground', 'homepageBlocks', 'storeEditorState', 'mediaAssets'));
    }

    public function show(Product $product)
    {
        if (!$product->is_active) {
            abort(404);
        }

        $product->load('egg.variables');

        // Load related same-game products for the plan-type switcher
        $relatedProducts = Product::where('game', $product->game)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        return view('shadow-store::pages.product', compact('product', 'relatedProducts'));
    }

    public function cart()
    {
        return view('shadow-store::pages.cart');
    }

    public function checkout()
    {
        return view('shadow-store::pages.checkout');
    }

    public function dedicated(Request $request)
    {
        $query = DedicatedMachine::where('is_active', true);
        
        if ($request->has('type')) {
            $query->where('cpu_type', $request->type);
        }
        
        $machines = $query->orderBy('sell_price')->get();
        
        $featured = DedicatedMachine::where('is_active', true)
            ->where('is_featured', true)
            ->orderBy('sell_price')
            ->get();

        $dedicatedPage = $this->storefrontContent->getDedicatedPage();
        
        return view('shadow-store::pages.dedicated', compact('machines', 'featured', 'dedicatedPage'));
    }

    public function showDedicated(string $slug)
    {
        $machine = DedicatedMachine::where('slug', $slug)
            ->where('is_active', true)
            ->firstOrFail();
        
        return view('shadow-store::pages.dedicated-show', compact('machine'));
    }

    public function msa()
    {
        $msaPage = $this->storefrontContent->getMsaPage();

        return view('shadow-store::pages.msa', compact('msaPage'));
    }
}
