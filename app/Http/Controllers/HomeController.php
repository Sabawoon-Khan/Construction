<?php

namespace App\Http\Controllers;

use App\Models\OrgSetting;
use App\Models\PortfolioProject;
use App\Support\PortfolioPresenter;
use App\Support\Seo;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    private const FEATURED_LIMIT = 6;

    /** Dedicated marketing hero — never portfolio media. */
    private const MARKETING_HERO =
        'https://images.unsplash.com/photo-1545324418-cc1a3fa10c00?auto=format&fit=crop&w=1800&q=82';

    public function __invoke(): Response
    {
        $portfolio = PortfolioProject::query()
            ->published()
            ->ordered()
            ->with(['media' => fn ($q) => $q->orderByDesc('is_cover')->orderBy('sort_order')->orderBy('id')])
            ->limit(self::FEATURED_LIMIT)
            ->get();

        $props = [
            'portfolio' => PortfolioPresenter::cards($portfolio),
        ];

        if (config('kit.features.seo')) {
            $props['documentSeo'] = Seo::document([
                'title' => OrgSetting::appName(),
                'description' => config('kit.seo.default_description'),
                'path' => '/',
                'image' => self::MARKETING_HERO,
                'site_name_only' => true,
            ]);
        }

        return Inertia::render('Welcome', $props);
    }
}
