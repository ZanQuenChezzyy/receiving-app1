<?php

namespace App\Providers;

use App\Filament\Pages\Auth\EditProfile;
use App\Filament\Pages\Dashboard;
use App\Filament\Resources\ApprovalVpKembaliDetailResource;
use App\Filament\Resources\DeliveryOrderReceiptDetailResource;
use App\Filament\Resources\DeliveryOrderReceiptResource;
use App\Filament\Resources\GoodsReceiptSlipDetailResource;
use App\Filament\Resources\GoodsReceiptSlipResource;
use App\Filament\Resources\LocationResource;
use App\Filament\Resources\PermissionResource;
use App\Filament\Resources\PurchaseOrderTerbitResource;
use App\Filament\Resources\ReturnDeliveryToVendorDetailResource;
use App\Filament\Resources\ReturnDeliveryToVendorResource;
use App\Filament\Resources\RoleResource;
use App\Filament\Resources\TransmittalKembaliDetailResource;
use App\Filament\Resources\TransmittalKembaliResource;
use App\Filament\Resources\TransmittalKirimResource;
use App\Filament\Resources\UserResource;
use App\Models\ApprovalVpKembali;
use App\Models\ApprovalVpKirim;
use App\Models\MaterialIssuedRequest;
use App\Models\MaterialIssuedRequestDetail;
use App\Models\TransmittalGudangKirim;
use App\Models\TransmittalGudangKirimDetail;
use App\Models\TransmittalGudangTerima;
use App\Models\TransmittalGudangTerimaDetail;
use App\Models\WarehouseLocation;
use Filament\Support\Facades\FilamentView;
use Filament\View\PanelsRenderHook;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Illuminate\View\View;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        FilamentView::registerRenderHook('panels::body.end', fn(): string => Blade::render("@vite('resources/js/app.js')"));
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // if (config('app.env') === 'local') {
        //     URL::forceScheme('https');
        // }
        // Vite Hot Reload
        FilamentView::registerRenderHook(
            PanelsRenderHook::BODY_END,
            fn(): string => Blade::render("@vite('resources/js/app.js')")
        );

        // Navigation Top User Card
        FilamentView::registerRenderHook(
            PanelsRenderHook::SIDEBAR_NAV_START,
            fn(): View => view('filament.user-card')
        );

        // Footer
        FilamentView::registerRenderHook(
            PanelsRenderHook::FOOTER,
            fn(): View => view('filament.footer'),
            // Render The Footer for Pages or Resource
            scopes: [
                Dashboard::class,
                EditProfile::class,
                UserResource::class,
                RoleResource::class,
                PermissionResource::class,
                ApprovalVpKembaliDetailResource::class,
                ApprovalVpKembali::class,
                ApprovalVpKirim::class,
                DeliveryOrderReceiptDetailResource::class,
                DeliveryOrderReceiptResource::class,
                GoodsReceiptSlipDetailResource::class,
                GoodsReceiptSlipResource::class,
                LocationResource::class,
                MaterialIssuedRequestDetail::class,
                MaterialIssuedRequest::class,
                PurchaseOrderTerbitResource::class,
                ReturnDeliveryToVendorDetailResource::class,
                ReturnDeliveryToVendorResource::class,
                TransmittalGudangKirimDetail::class,
                TransmittalGudangKirim::class,
                TransmittalGudangTerimaDetail::class,
                TransmittalGudangTerima::class,
                TransmittalKembaliDetailResource::class,
                TransmittalKembaliResource::class,
                TransmittalKirimResource::class,
                WarehouseLocation::class,
            ]
        );
    }
}
