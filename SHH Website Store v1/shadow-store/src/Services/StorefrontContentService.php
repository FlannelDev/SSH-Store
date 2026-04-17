<?php

namespace App\Plugins\ShadowStore\Services;

use App\Plugins\ShadowStore\Models\MediaAsset;
use App\Plugins\ShadowStore\Models\StoreSetting;
use Illuminate\Support\Arr;

class StorefrontContentService
{
    private const DEFAULT_BLOCK_ORDER = [
        'home_rich_content',
        'value_props',
        'catalog_access',
        'featured_products',
        'catalog',
        'dedicated_cta',
        'trust_cards',
    ];

    public function getStoreHome(): array
    {
        return [
            'kicker' => (string) StoreSetting::getValue('home_kicker', 'Built for communities that hate lag'),
            'title' => (string) StoreSetting::getValue('home_title', 'Game servers with dedicated-grade headroom and zero bargain-bin packaging.'),
            'subtitle' => (string) StoreSetting::getValue('home_subtitle', 'Launch Arma Reforger, survival worlds, and performance-heavy stacks across a catalog of 200+ supported games on Ryzen 9 9950X3D infrastructure with sensible density, fast storage, and clean billing.'),
            'primary_cta_label' => (string) StoreSetting::getValue('home_primary_cta_label', 'Browse Game Servers'),
            'primary_cta_url' => (string) StoreSetting::getValue('home_primary_cta_url', '#catalog'),
            'secondary_cta_label' => (string) StoreSetting::getValue('home_secondary_cta_label', 'Explore Dedicated Machines'),
            'secondary_cta_url' => (string) StoreSetting::getValue('home_secondary_cta_url', '/store/dedicated'),
            'content' => (string) StoreSetting::getValue('home_content', ''),
            'media_asset_id' => $this->normalizeNullableInt(StoreSetting::getValue('home_media_asset_id')),
            'media' => $this->resolveMediaAsset(StoreSetting::getValue('home_media_asset_id')),
        ];
    }

    public function getFooterNotice(): array
    {
        return [
            'title' => (string) StoreSetting::getValue('footer_notice_title', 'Servers are in partnership with Thunder Buddies Studio'),
            'body' => (string) StoreSetting::getValue('footer_notice_body', 'Support provided by Thunder Buddies Studio. Servers by Shadow Haven.'),
        ];
    }

    public function getDedicatedPage(): array
    {
        return [
            'title' => (string) StoreSetting::getValue('dedicated_title', 'Dedicated Servers'),
            'subtitle' => (string) StoreSetting::getValue('dedicated_subtitle', 'Browse live dedicated server inventory and order directly through the embedded provisioning catalog.'),
            'content' => (string) StoreSetting::getValue('dedicated_content', ''),
            'media_asset_id' => $this->normalizeNullableInt(StoreSetting::getValue('dedicated_media_asset_id')),
            'media' => $this->resolveMediaAsset(StoreSetting::getValue('dedicated_media_asset_id')),
        ];
    }

    public function getMsaPage(): array
    {
        return [
            'title' => (string) StoreSetting::getValue('msa_title', 'Master Services Agreement'),
            'content' => (string) StoreSetting::getValue('msa_content', ''),
        ];
    }

    public function getHeaderSettings(): array
    {
        $logoAssetId = $this->normalizeNullableInt(StoreSetting::getValue('header_logo_asset_id'));

        return [
            'badge_text' => (string) StoreSetting::getValue('header_badge_text', 'SH'),
            'logo_asset_id' => $logoAssetId,
            'logo_url' => (string) StoreSetting::getValue('header_logo_url', ''),
            'resolved_logo_url' => $this->resolveMediaAssetUrl($logoAssetId, (string) StoreSetting::getValue('header_logo_url', '')),
            'brand_name' => (string) StoreSetting::getValue('header_brand_name', 'Shadow Haven Hosting'),
            'brand_tagline' => (string) StoreSetting::getValue('header_brand_tagline', 'Game infrastructure'),
            'store_label' => (string) StoreSetting::getValue('header_store_label', 'Game Servers'),
            'store_url' => (string) StoreSetting::getValue('header_store_url', '/store'),
            'dedicated_label' => (string) StoreSetting::getValue('header_dedicated_label', 'Dedicated'),
            'dedicated_url' => (string) StoreSetting::getValue('header_dedicated_url', '/store/dedicated'),
            'msa_label' => (string) StoreSetting::getValue('header_msa_label', 'MSA'),
            'msa_url' => (string) StoreSetting::getValue('header_msa_url', '/store/msa'),
            'wiki_label' => (string) StoreSetting::getValue('header_wiki_label', 'Wiki'),
            'wiki_url' => (string) StoreSetting::getValue('header_wiki_url', '/wiki'),
            'discord_label' => (string) StoreSetting::getValue('header_discord_label', 'Discord'),
            'discord_url' => (string) StoreSetting::getValue('header_discord_url', 'https://discord.gg/AqCVPtpgYQ'),
            'nav_style' => (string) StoreSetting::getValue('header_nav_style', 'soft'),
        ];
    }

    public function getAnnouncementSettings(): array
    {
        return [
            'enabled' => $this->toBool(StoreSetting::getValue('announcement_enabled', false)),
            'text' => (string) StoreSetting::getValue('announcement_text', ''),
            'link_label' => (string) StoreSetting::getValue('announcement_link_label', ''),
            'link_url' => (string) StoreSetting::getValue('announcement_link_url', ''),
            'style' => (string) StoreSetting::getValue('announcement_style', 'accent'),
        ];
    }

    public function getPromoSettings(): array
    {
        return [
            'enabled' => $this->toBool(StoreSetting::getValue('promo_enabled', false)),
            'text' => (string) StoreSetting::getValue('promo_text', ''),
            'button_label' => (string) StoreSetting::getValue('promo_button_label', ''),
            'button_url' => (string) StoreSetting::getValue('promo_button_url', ''),
            'style' => (string) StoreSetting::getValue('promo_style', 'cyan'),
        ];
    }

    public function getBackgroundSettings(): array
    {
        $assetId = $this->normalizeNullableInt(StoreSetting::getValue('store_background_image_asset_id'));

        return [
            'color_start' => (string) StoreSetting::getValue('store_background_color_start', '#091220'),
            'color_end' => (string) StoreSetting::getValue('store_background_color_end', '#050b16'),
            'image_asset_id' => $assetId,
            'image_url' => (string) StoreSetting::getValue('store_background_image_url', ''),
            'resolved_image_url' => $this->resolveMediaAssetUrl($assetId, (string) StoreSetting::getValue('store_background_image_url', '')),
            'overlay_opacity' => (float) StoreSetting::getValue('store_background_overlay_opacity', '0.72'),
        ];
    }

    public function getHomepageBlocks(): array
    {
        $defaults = $this->getDefaultBlocks();
        $storedOrder = json_decode((string) StoreSetting::getValue('homepage_block_order', json_encode(self::DEFAULT_BLOCK_ORDER)), true);
        $order = collect(is_array($storedOrder) ? $storedOrder : self::DEFAULT_BLOCK_ORDER)
            ->filter(fn ($id) => array_key_exists((string) $id, $defaults))
            ->values()
            ->all();

        foreach (array_keys($defaults) as $id) {
            if (!in_array($id, $order, true)) {
                $order[] = $id;
            }
        }

        $blocks = [];

        foreach ($order as $id) {
            $blocks[] = $defaults[$id];
        }

        return $blocks;
    }

    public function getEditorState(): array
    {
        return [
            'header' => array_merge($this->getHeaderSettings(), [
                'announcement' => $this->getAnnouncementSettings(),
                'promo' => $this->getPromoSettings(),
            ]),
            'background' => $this->getBackgroundSettings(),
            'hero' => $this->getStoreHome(),
            'footer_notice' => $this->getFooterNotice(),
            'homepage_blocks' => $this->getHomepageBlocks(),
        ];
    }

    public function getMediaAssets(): array
    {
        return MediaAsset::query()
            ->orderByDesc('updated_at')
            ->get()
            ->map(fn (MediaAsset $asset) => [
                'id' => $asset->id,
                'name' => $asset->name,
                'alt_text' => $asset->alt_text,
                'url' => $asset->public_url,
            ])
            ->values()
            ->all();
    }

    public function saveHeader(array $data): void
    {
        foreach ([
            'header_badge_text' => Arr::get($data, 'badge_text', 'SH'),
            'header_logo_asset_id' => $this->normalizeNullableInt(Arr::get($data, 'logo_asset_id')),
            'header_logo_url' => Arr::get($data, 'logo_url', ''),
            'header_brand_name' => Arr::get($data, 'brand_name', 'Shadow Haven Hosting'),
            'header_brand_tagline' => Arr::get($data, 'brand_tagline', 'Game infrastructure'),
            'header_store_label' => Arr::get($data, 'store_label', 'Game Servers'),
            'header_store_url' => Arr::get($data, 'store_url', '/store'),
            'header_dedicated_label' => Arr::get($data, 'dedicated_label', 'Dedicated'),
            'header_dedicated_url' => Arr::get($data, 'dedicated_url', '/store/dedicated'),
            'header_msa_label' => Arr::get($data, 'msa_label', 'MSA'),
            'header_msa_url' => Arr::get($data, 'msa_url', '/store/msa'),
            'header_wiki_label' => Arr::get($data, 'wiki_label', 'Wiki'),
            'header_wiki_url' => Arr::get($data, 'wiki_url', '/wiki'),
            'header_discord_label' => Arr::get($data, 'discord_label', 'Discord'),
            'header_discord_url' => Arr::get($data, 'discord_url', 'https://discord.gg/AqCVPtpgYQ'),
            'header_nav_style' => Arr::get($data, 'nav_style', 'soft'),
        ] as $key => $value) {
            StoreSetting::setValue($key, (string) $value);
        }

        $announcement = (array) Arr::get($data, 'announcement', []);
        $promo = (array) Arr::get($data, 'promo', []);

        StoreSetting::setValue('announcement_enabled', Arr::get($announcement, 'enabled') ? '1' : '0');
        StoreSetting::setValue('announcement_text', (string) Arr::get($announcement, 'text', ''));
        StoreSetting::setValue('announcement_link_label', (string) Arr::get($announcement, 'link_label', ''));
        StoreSetting::setValue('announcement_link_url', (string) Arr::get($announcement, 'link_url', ''));
        StoreSetting::setValue('announcement_style', (string) Arr::get($announcement, 'style', 'accent'));

        StoreSetting::setValue('promo_enabled', Arr::get($promo, 'enabled') ? '1' : '0');
        StoreSetting::setValue('promo_text', (string) Arr::get($promo, 'text', ''));
        StoreSetting::setValue('promo_button_label', (string) Arr::get($promo, 'button_label', ''));
        StoreSetting::setValue('promo_button_url', (string) Arr::get($promo, 'button_url', ''));
        StoreSetting::setValue('promo_style', (string) Arr::get($promo, 'style', 'cyan'));
    }

    public function saveBackground(array $data): void
    {
        StoreSetting::setValue('store_background_color_start', (string) Arr::get($data, 'color_start', '#091220'));
        StoreSetting::setValue('store_background_color_end', (string) Arr::get($data, 'color_end', '#050b16'));
        StoreSetting::setValue('store_background_image_asset_id', $this->normalizeNullableInt(Arr::get($data, 'image_asset_id')));
        StoreSetting::setValue('store_background_image_url', (string) Arr::get($data, 'image_url', ''));
        StoreSetting::setValue('store_background_overlay_opacity', (string) Arr::get($data, 'overlay_opacity', '0.72'));
    }

    public function saveHero(array $data): void
    {
        StoreSetting::setValue('home_kicker', (string) Arr::get($data, 'kicker', ''));
        StoreSetting::setValue('home_title', (string) Arr::get($data, 'title', ''));
        StoreSetting::setValue('home_subtitle', (string) Arr::get($data, 'subtitle', ''));
        StoreSetting::setValue('home_primary_cta_label', (string) Arr::get($data, 'primary_cta_label', ''));
        StoreSetting::setValue('home_primary_cta_url', (string) Arr::get($data, 'primary_cta_url', ''));
        StoreSetting::setValue('home_secondary_cta_label', (string) Arr::get($data, 'secondary_cta_label', ''));
        StoreSetting::setValue('home_secondary_cta_url', (string) Arr::get($data, 'secondary_cta_url', ''));
        StoreSetting::setValue('home_media_asset_id', $this->normalizeNullableInt(Arr::get($data, 'media_asset_id')));
    }

    public function saveFooterNotice(array $data): void
    {
        StoreSetting::setValue('footer_notice_title', (string) Arr::get($data, 'title', ''));
        StoreSetting::setValue('footer_notice_body', (string) Arr::get($data, 'body', ''));
    }

    public function saveBlock(string $blockId, array $data): void
    {
        if ($blockId === 'home_rich_content') {
            StoreSetting::setValue('home_content', (string) Arr::get($data, 'settings.content', ''));

            return;
        }

        $defaults = $this->getDefaultBlocks();

        if (!array_key_exists($blockId, $defaults)) {
            return;
        }

        $payload = [
            'enabled' => (bool) Arr::get($data, 'enabled', true),
            'settings' => Arr::get($data, 'settings', []),
        ];

        StoreSetting::setValue('homepage_block_' . $blockId, json_encode($payload, JSON_UNESCAPED_SLASHES));
    }

    public function saveBlockOrder(array $order): array
    {
        $allowed = array_keys($this->getDefaultBlocks());
        $normalized = collect($order)
            ->filter(fn ($id) => in_array((string) $id, $allowed, true))
            ->values()
            ->all();

        foreach ($allowed as $id) {
            if (!in_array($id, $normalized, true)) {
                $normalized[] = $id;
            }
        }

        StoreSetting::setValue('homepage_block_order', json_encode($normalized, JSON_UNESCAPED_SLASHES));

        return $normalized;
    }

    private function getDefaultBlocks(): array
    {
        $defaults = [
            'home_rich_content' => [
                'id' => 'home_rich_content',
                'label' => 'Rich Content',
                'enabled' => filled($this->getStoreHome()['content']),
                'settings' => [
                    'content' => $this->getStoreHome()['content'],
                ],
            ],
            'value_props' => [
                'id' => 'value_props',
                'label' => 'Value Props',
                'enabled' => true,
                'settings' => [
                    'cards' => [
                        ['kicker' => 'Fast launch', 'title' => 'Minutes', 'body' => 'Provisioning and checkout are wired to minimize the gap between payment and playable server.'],
                        ['kicker' => 'Clean pricing', 'title' => 'No noise', 'body' => 'Straightforward catalog, visible monthly costs, and consistent billing access for account owners and subusers.'],
                        ['kicker' => 'Game catalog', 'title' => '200+', 'body' => 'A large supported game list means the landing page can sell breadth as well as performance for niche communities.'],
                        ['kicker' => 'Real hardware', 'title' => 'X3D', 'body' => 'High-cache CPU allocation and NVMe storage tuned for titles that punish weak single-core performance.'],
                        ['kicker' => 'Operational view', 'title' => 'Unified', 'body' => 'Cart, billing, payments, and post-purchase server management sit in one storefront path.'],
                    ],
                ],
            ],
            'catalog_access' => [
                'id' => 'catalog_access',
                'label' => 'Catalog Access Strip',
                'enabled' => true,
                'settings' => [
                    'kicker' => 'Catalog access',
                    'body' => 'Jump directly into game hosting or move up to full dedicated machines.',
                    'primary_label' => 'Game Servers',
                    'primary_url' => '/store',
                    'secondary_label' => 'Dedicated Machines',
                    'secondary_url' => '/store/dedicated',
                ],
            ],
            'featured_products' => [
                'id' => 'featured_products',
                'label' => 'Featured Products',
                'enabled' => true,
                'settings' => [
                    'kicker' => 'Featured drops',
                    'title' => 'Priority configurations worth opening first',
                    'body' => 'These are the highlighted products in the catalog right now, surfaced for users who want the quickest path to a proven configuration.',
                ],
            ],
            'catalog' => [
                'id' => 'catalog',
                'label' => 'Catalog',
                'enabled' => true,
                'settings' => [
                    'kicker' => 'Catalog',
                    'title' => 'Browse by game family',
                    'body' => 'Each section below preserves the existing store organization, but the page is now tuned to feel like a storefront instead of a generic admin card grid.',
                ],
            ],
            'dedicated_cta' => [
                'id' => 'dedicated_cta',
                'label' => 'Dedicated CTA',
                'enabled' => true,
                'settings' => [
                    'kicker' => 'Dedicated fleet',
                    'title' => 'Need bare metal instead of a single game instance?',
                    'body' => 'Move to a full dedicated machine when you need multiple workloads, deeper control, or room to run larger communities without fighting shared limits.',
                    'button_label' => 'View Dedicated Servers ->',
                    'button_url' => '/store/dedicated',
                    'stats' => [
                        ['label' => 'Configurations', 'value' => '40+', 'body' => 'Broad hardware spread for custom needs'],
                        ['label' => 'Starting price', 'value' => '$23', 'body' => 'Entry point for full machine ownership'],
                        ['label' => 'Provisioning', 'value' => '10min', 'body' => 'Rapid deployment for ready-to-use hardware'],
                        ['label' => 'Locations', 'value' => '3', 'body' => 'US regions for lower-latency placement'],
                    ],
                ],
            ],
            'trust_cards' => [
                'id' => 'trust_cards',
                'label' => 'Trust Cards',
                'enabled' => true,
                'settings' => [
                    'cards' => [
                        ['icon' => 'bolt', 'title' => 'Instant deployment', 'body' => 'Payment, billing, and provisioning are connected so users spend less time waiting on manual handling after checkout.'],
                        ['icon' => 'shield', 'title' => 'Protected by default', 'body' => 'DDoS mitigation and hardened infrastructure are treated as the baseline, not an upsell line item buried later in checkout.'],
                        ['icon' => 'star', 'title' => 'Premium hardware, stated plainly', 'body' => 'Ryzen 9 9950X3D, fast memory, and NVMe-backed storage are front-and-center so the storefront sells capability instead of vague marketing.'],
                    ],
                ],
            ],
        ];

        foreach ($defaults as $id => $block) {
            if ($id === 'home_rich_content') {
                continue;
            }

            $stored = json_decode((string) StoreSetting::getValue('homepage_block_' . $id, ''), true);

            if (is_array($stored)) {
                $defaults[$id]['enabled'] = (bool) Arr::get($stored, 'enabled', $block['enabled']);
                $defaults[$id]['settings'] = array_replace_recursive($block['settings'], (array) Arr::get($stored, 'settings', []));
            }
        }

        return $defaults;
    }

    private function resolveMediaAssetUrl(?int $assetId, string $fallbackUrl = ''): ?string
    {
        if ($assetId) {
            return $this->resolveMediaAsset($assetId)['url'] ?? $fallbackUrl;
        }

        return filled($fallbackUrl) ? $fallbackUrl : null;
    }

    private function resolveMediaAsset(mixed $id): ?array
    {
        if (blank($id)) {
            return null;
        }

        $asset = MediaAsset::query()->find((int) $id);

        if (!$asset) {
            return null;
        }

        return [
            'id' => $asset->id,
            'url' => $asset->public_url,
            'name' => $asset->name,
            'alt_text' => $asset->alt_text,
        ];
    }

    private function normalizeNullableInt(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (int) $value;
    }

    private function toBool(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        return in_array((string) $value, ['1', 'true', 'on', 'yes'], true);
    }
}