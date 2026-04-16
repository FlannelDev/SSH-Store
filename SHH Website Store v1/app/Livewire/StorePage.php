<?php

namespace App\Livewire;

use App\Models\Category;
use App\Models\Product;
use Livewire\Component;

class StorePage extends Component
{
    public string $search = '';
    public string $selectedCategory = '';
    public string $sortBy = 'price_asc';
    public bool $featuredOnly = false;

    public function updatedSearch(): void
    {
        // Livewire handles reactivity automatically
    }

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
        $categories = Category::where('is_visible', true)
            ->withCount(['products' => fn ($q) => $q->visible()])
            ->orderBy('sort_order')
            ->get();

        $featuredProducts = Product::visible()
            ->featured()
            ->with('category')
            ->orderBy('sort_order')
            ->limit(8)
            ->get();

        $productsQuery = Product::visible()->with('category');

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

        return view('livewire.store-page', [
            'categories' => $categories,
            'featuredProducts' => $featuredProducts,
            'products' => $products,
        ])->layout('components.layouts.store', ['title' => 'Store']);
    }
}
