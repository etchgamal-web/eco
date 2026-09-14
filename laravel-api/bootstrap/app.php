<?php
use App\Models\Setting;
use App\Http\Middleware\AssignCorrelationId;
use App\Modules\Catalog\Domain\Exceptions\AttributeNotFoundException;
use App\Modules\Catalog\Domain\Exceptions\AttributeValueNotFoundException;
use App\Modules\Catalog\Domain\Exceptions\BrandNotFoundException;
use App\Modules\Catalog\Domain\Exceptions\BusinessRuleException;
use App\Modules\Catalog\Domain\Exceptions\CategoryNotFoundException;
use App\Modules\Catalog\Domain\Exceptions\ProductNotFoundException;
use App\Modules\Catalog\Domain\Exceptions\VariantNotFoundException;
use App\Modules\Auth\Domain\Exceptions\AuthenticationException as DomainAuthenticationException;
use App\Modules\Auth\Domain\Exceptions\AuthorizationException as DomainAuthorizationException;
use App\Modules\Customer\Domain\Exceptions\CustomerNotFoundException;
use App\Modules\Customer\Domain\Exceptions\CustomerFeatureNotFoundException;
use App\Modules\Customer\Domain\Exceptions\AddressNotFoundException;
use App\Modules\Settings\Domain\Exceptions\SettingsNotFoundException;
use App\Modules\Staff\Domain\Exceptions\StaffNotFoundException;
use App\Modules\Staff\Domain\Exceptions\StaffActionNotAllowedException;
use App\Modules\Inventory\Domain\Exceptions\InventoryNotFoundException;
use App\Modules\Inventory\Domain\Exceptions\InsufficientStockException;
use App\Modules\Inventory\Domain\Exceptions\InvalidStockAdjustmentException;
use App\Modules\Order\Domain\Exceptions\CheckoutException;
use App\Modules\Order\Domain\Exceptions\InvalidOrderStatusTransitionException;
use App\Modules\Order\Domain\Exceptions\OrderNotFoundException;
use App\Modules\Order\Domain\Exceptions\OrderActionNotAllowedException;
use App\Modules\Payment\Domain\Exceptions\PaymentAlreadyProcessedException;
use App\Modules\Payment\Domain\Exceptions\PaymentException;
use App\Modules\Payment\Domain\Exceptions\PaymentFailedException;
use App\Modules\Payment\Domain\Exceptions\PaymentNotFoundException;
use App\Modules\Payment\Domain\Exceptions\PaymentAmountMismatchException;
use App\Modules\Payment\Domain\Exceptions\InvalidPaymentTransitionException;
use App\Modules\Payment\Domain\Exceptions\PaymentInProgressException;
use App\Modules\Shipping\Domain\Exceptions\InvalidShippingAddressException;
use App\Modules\Shipping\Domain\Exceptions\ShippingException;
use App\Modules\Shipping\Domain\Exceptions\ShippingRateNotFoundException;
use App\Modules\Shipping\Domain\Exceptions\ShipmentNotFoundException;
use App\Modules\Shipping\Domain\Exceptions\InvalidShipmentTransitionException;
use App\Modules\Customer\Domain\Exceptions\CartException;
use App\Modules\Customer\Domain\Exceptions\CartItemNotFoundException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Console\Scheduling\Schedule;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
return Application::configure(basePath: dirname(__DIR__))
 ->withRouting(web:__DIR__.'/../routes/web.php',api:__DIR__.'/../routes/api.php',commands:__DIR__.'/../routes/console.php',health:'/up')
 ->withCommands([__DIR__.'/../app/Console/Commands/BackupDatabase.php',__DIR__.'/../app/Console/Commands/VerifyBackup.php',__DIR__.'/../app/Console/Commands/DispatchOutbox.php',__DIR__.'/../app/Console/Commands/ReconcileStalePayments.php',__DIR__.'/../app/Console/Commands/VerifyProviderSandbox.php',__DIR__.'/../app/Console/Commands/ReconcileStaleShipments.php'])
 ->withMiddleware(function(Middleware $middleware):void{$middleware->append(AssignCorrelationId::class);$middleware->api(append:['throttle:api']);})
 ->withSchedule(function(Schedule $schedule):void{$time='00:30';$backupEnabled=(bool) config('backup.enabled',false);$backupTime=(string) config('backup.schedule','02:00');try{$configured=Setting::query()->where('key','cart.abandoned_scan_time')->value('value');if(is_string($configured)&&preg_match('/^([01]\d|2[0-3]):[0-5]\d$/',$configured))$time=$configured;$backup=Setting::query()->where('key','backup.enabled')->first();if($backup)$backupEnabled=(bool)$backup->getTypedValue();$backupSchedule=Setting::query()->where('key','backup.schedule')->value('value');if(is_string($backupSchedule)&&preg_match('/^([01]\d|2[0-3]):[0-5]\d$/',$backupSchedule))$backupTime=$backupSchedule;}catch(\Throwable $e){}$schedule->command('cart:mark-abandoned')->dailyAt($time)->withoutOverlapping();$schedule->command('outbox:dispatch')->everyMinute()->withoutOverlapping();$schedule->command('payments:reconcile')->everyFiveMinutes()->withoutOverlapping();$schedule->command('shipments:reconcile')->everyTenMinutes()->withoutOverlapping();if($backupEnabled)$schedule->command('backup:database')->dailyAt($backupTime)->withoutOverlapping()->onOneServer();})
 ->withExceptions(function(Exceptions $exceptions):void{
  $exceptions->shouldRenderJsonWhen(fn(Request $request)=>$request->is('api/*')||$request->expectsJson());
  $exceptions->render(function(AuthenticationException $e,Request $r){if($r->is('api/*'))return response()->json(['message'=>'Unauthenticated.'],401);});
  $exceptions->render(function(DomainAuthenticationException $e,Request $r){if($r->is('api/*'))return response()->json(['message'=>$e->getMessage()],401);});
  $exceptions->render(function(DomainAuthorizationException|StaffActionNotAllowedException $e,Request $r){if($r->is('api/*'))return response()->json(['message'=>$e->getMessage()],403);});
  $exceptions->render(function(BusinessRuleException $e,Request $r){if($r->is('api/*'))return response()->json(['message'=>$e->getMessage()],409);});
  $exceptions->render(function(InsufficientStockException|InvalidStockAdjustmentException $e,Request $r){if($r->is('api/*'))return response()->json(['message'=>$e->getMessage()],422);});
  $exceptions->render(function(CheckoutException $e,Request $r){if($r->is('api/*'))return response()->json(['message'=>$e->getMessage()],422);});
  $exceptions->render(function(PaymentAlreadyProcessedException|PaymentInProgressException|InvalidPaymentTransitionException|InvalidOrderStatusTransitionException|InvalidShipmentTransitionException|OrderActionNotAllowedException $e,Request $r){if($r->is('api/*'))return response()->json(['message'=>$e->getMessage()],409);});
  $exceptions->render(function(PaymentAmountMismatchException|PaymentFailedException|InvalidShippingAddressException|ShippingException|CartException $e,Request $r){if($r->is('api/*'))return response()->json(['message'=>$e->getMessage()],422);});
  $exceptions->render(function(PaymentException $e,Request $r){if($r->is('api/*'))return response()->json(['message'=>$e->getMessage()],422);});
  $exceptions->render(function(PaymentNotFoundException|ShippingRateNotFoundException|ShipmentNotFoundException|OrderNotFoundException|ProductNotFoundException|VariantNotFoundException|AttributeNotFoundException|AttributeValueNotFoundException|BrandNotFoundException|CategoryNotFoundException|SettingsNotFoundException|CustomerNotFoundException|CustomerFeatureNotFoundException|AddressNotFoundException|StaffNotFoundException|InventoryNotFoundException $e,Request $r){if($r->is('api/*'))return response()->json(['message'=>$e->getMessage()],404);});
  $exceptions->render(function(ValidationException $e,Request $r){if($r->is('api/*'))return response()->json(['message'=>'Validation failed.','errors'=>$e->errors()],422);});
  $exceptions->render(function(NotFoundHttpException $e,Request $r){if($r->is('api/*'))return response()->json(['message'=>'Resource not found.'],404);});
  $exceptions->render(function(HttpExceptionInterface $e,Request $r){if($r->is('api/*'))return response()->json(['message'=>$e->getStatusCode()===403?'Forbidden.':'Request failed.'],$e->getStatusCode());});
  $exceptions->render(function(\Throwable $e,Request $r){if($r->is('api/*')){report($e);return response()->json(['message'=>'An internal server error occurred.'],500);}});
 })->create();
