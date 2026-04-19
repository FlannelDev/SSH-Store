<?php

namespace ShhStore\Livewire;

use Livewire\Component;
use ShhStore\Models\StoreProduct;

class ProductDetail extends Component
{
    public StoreProduct $product;
    public string $billingCycle = 'monthly';

    public function mount(string $slug): void
    {
        $this->product = StoreProduct::visible()
            ->where('slug', $slug)
            ->with('category')
            ->firstOrFail();
    }

    public function getActivePrice(): string
    {
        return '$' . number_format($this->product->calculatePrice($this->billingCycle), 2);
    }

    public function render()
    {
        $relatedProducts = StoreProduct::visible()
            ->where('category_id', $this->product->category_id)
            ->where('id', '!=', $this->product->id)
            ->orderBy('price_monthly')
            ->limit(4)
            ->get();

        return view('shh-store::livewire.product-detail', [
            'relatedProducts' => $relatedProducts,
            'activePrice' => $this->getActivePrice(),
        ])->layout('shh-store::components.layouts.store', ['title' => $this->product->name]);
    }
}
