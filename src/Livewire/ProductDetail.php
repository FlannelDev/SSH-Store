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
        return match ($this->billingCycle) {
            'quarterly' => $this->product->price_quarterly
                ? '$' . number_format((float) $this->product->price_quarterly, 2)
                : '$' . number_format((float) $this->product->price_monthly * 3, 2),
            'annually' => $this->product->price_annually
                ? '$' . number_format((float) $this->product->price_annually, 2)
                : '$' . number_format((float) $this->product->price_monthly * 12, 2),
            default => '$' . number_format((float) $this->product->price_monthly, 2),
        };
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
