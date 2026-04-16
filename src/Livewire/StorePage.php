<?php

namespace ShhStore\Livewire;

use Livewire\Component;
use ShhStore\Models\StoreCategory;
use ShhStore\Models\StoreProduct;

class StorePage extends Component
{
    public string $search = '';
    public string $selectedCategory = '';
    public string $sortBy = 'price_asc';
    public bool $featuredOnly = false;

    public function selectCategory(string $slug): void
    {
        $this->selectedCategory = $this->selectedCategory === $slug ? '' : $slug;
    }

    public function clearFilters(): void
    {
        $this->search = '';
        $this->selectedCategory = '';
        $this->sortBy = 'price_asc';
        $this->featuredOnly = false;
    }

    public function render()
    {
        $categories = StoreCategory::where('is_visible', true)
            ->withCount(['products' => fn ($q) => $q->visible()])
            ->orderBy('sort_order')
            ->get();

        $featuredProducts = StoreProduct::visible()
            ->featured()
            ->with('category')
            ->orderBy('sort_order')
            ->limit(8)
            ->get();

        $productsQuery = StoreProduct::visible()->with('category');

        if ($this->search) {
            $productsQuery->where(function ($q) {
                $q->where('name', 'like', "%{$this->search}%")
                  ->orWhere('description', 'like', "%{$this->search}%")
                  ->orWhere('tier', 'like', "%{$this->search}%");
            });
        }

        if ($this->selectedCategory) {
            $productsQuery->whereHas('category', fn ($q) => $q->where('slug', $this->selectedCategory));
        }

        if ($this->featuredOnly) {
            $productsQuery->featured();
        }

        $productsQuery = match ($this->sortBy) {
            'price_desc' => $productsQuery->orderByDesc('price_monthly'),
            'name_asc' => $productsQuery->orderBy('name'),
            'name_desc' => $productsQuery->orderByDesc('name'),
            default => $productsQuery->orderBy('price_monthly'),
        };

        $products = $productsQuery->get();

        return view('shh-store::livewire.store-page', [
            'categories' => $categories,
            'featuredProducts' => $featuredProducts,
            'products' => $products,
        ])->layout('shh-store::components.layouts.store', ['title' => 'Store']);
    }
}
