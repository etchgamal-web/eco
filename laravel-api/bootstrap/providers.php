<?php
use App\Modules\Auth\AuthServiceProvider;
use App\Modules\Catalog\CatalogServiceProvider;
use App\Modules\Customer\CustomerServiceProvider;
use App\Modules\Settings\SettingsServiceProvider;
use App\Modules\Staff\StaffServiceProvider;
use App\Modules\Inventory\InventoryServiceProvider;
use App\Modules\Order\OrderServiceProvider;
use App\Modules\Payment\PaymentServiceProvider;
use App\Modules\Shipping\ShippingServiceProvider;
use App\Modules\Promotion\PromotionServiceProvider;
use App\Modules\Tax\TaxServiceProvider;
use App\Modules\Shared\SharedServiceProvider;
use App\Providers\AppServiceProvider;
return [AppServiceProvider::class,AuthServiceProvider::class,CatalogServiceProvider::class,CustomerServiceProvider::class,SettingsServiceProvider::class,StaffServiceProvider::class,InventoryServiceProvider::class,OrderServiceProvider::class,PaymentServiceProvider::class,ShippingServiceProvider::class,PromotionServiceProvider::class,TaxServiceProvider::class,SharedServiceProvider::class];
