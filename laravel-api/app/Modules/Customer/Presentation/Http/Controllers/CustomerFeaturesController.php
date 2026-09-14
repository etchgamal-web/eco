<?php
namespace App\Modules\Customer\Presentation\Http\Controllers;
use App\Http\Controllers\Controller;use App\Modules\Customer\Domain\ValueObjects\AddressData;use App\Modules\Customer\Application\UseCases\AddCartItem;use App\Modules\Customer\Application\UseCases\ClearCart;use App\Modules\Customer\Application\UseCases\CreateAddress;use App\Modules\Customer\Application\UseCases\DeleteAddress;use App\Modules\Customer\Application\UseCases\GetCart;use App\Modules\Customer\Application\UseCases\GetDefaultAddress;use App\Modules\Customer\Application\UseCases\GetCustomerOrders;use App\Modules\Customer\Application\UseCases\ListAddresses;use App\Modules\Customer\Application\UseCases\ManageCustomerNotifications;use App\Modules\Customer\Application\UseCases\ManageCustomerPreferences;use App\Modules\Customer\Application\UseCases\ManageCustomerWishlist;use App\Modules\Customer\Application\UseCases\RemoveCartItem;use App\Modules\Customer\Application\UseCases\UpdateCartItem;use App\Modules\Customer\Application\UseCases\UpdateAddress;use App\Modules\Customer\Presentation\Http\Requests\AddressRequest;use App\Modules\Customer\Presentation\Http\Requests\CartAccessRequest;use App\Modules\Customer\Presentation\Http\Requests\CartItemRequest;use App\Modules\Customer\Presentation\Http\Requests\NotificationRequest;use App\Modules\Customer\Presentation\Http\Requests\OrdersRequest;use App\Modules\Customer\Presentation\Http\Requests\PreferencesRequest;use App\Modules\Customer\Presentation\Http\Requests\ProductRequest;use Illuminate\Http\JsonResponse;
final class CustomerFeaturesController extends Controller{
 public function addresses(AddressRequest $r,ListAddresses $u):JsonResponse{return response()->json(['data'=>$u->execute()]);}
 public function defaultAddress(AddressRequest $r,GetDefaultAddress $u):JsonResponse{return response()->json(['data'=>$u->execute()]);}
 public function addAddress(AddressRequest $r,CreateAddress $u):JsonResponse{return response()->json(['data'=>$u->execute(AddressData::fromArray($r->validated()))],201);}
 public function updateAddress(AddressRequest $r,int $id,UpdateAddress $u):JsonResponse{return response()->json(['data'=>$u->execute($id,AddressData::fromArray($r->validated()))]);}
 public function deleteAddress(AddressRequest $r,int $id,DeleteAddress $u):JsonResponse{$u->execute($id);return response()->json(null,204);}
 public function orders(OrdersRequest $r,GetCustomerOrders $u):JsonResponse{return response()->json(['data'=>$u->execute()]);}
 public function cart(CartAccessRequest $r,GetCart $u):JsonResponse{return response()->json(['data'=>$u->execute()]);}
 public function addCartItem(CartItemRequest $r,AddCartItem $u):JsonResponse{$d=$r->validated();return response()->json(['data'=>$u->execute((int)$d['product_id'],isset($d['variant_id'])?(int)$d['variant_id']:null,(int)$d['quantity'])],201);}
 public function updateCartItem(CartItemRequest $r,UpdateCartItem $u):JsonResponse{$d=$r->validated();return response()->json(['data'=>$u->execute((int)$d['product_id'],isset($d['variant_id'])?(int)$d['variant_id']:null,(int)$d['quantity'])]);}
 public function removeCartItem(CartItemRequest $r,int $productId,RemoveCartItem $u,?int $variantId=null):JsonResponse{return response()->json(['data'=>$u->execute($productId,$variantId)]);}
 public function clearCart(CartAccessRequest $r,ClearCart $u):JsonResponse{return response()->json(['data'=>$u->execute()]);}
 public function wishlist(ProductRequest $r,ManageCustomerWishlist $u):JsonResponse{return response()->json(['data'=>$u->list()]);}
 public function addWishlist(ProductRequest $r,ManageCustomerWishlist $u):JsonResponse{return response()->json(['data'=>$u->add((int)$r->validated('product_id'))],201);}
 public function removeWishlist(ProductRequest $r,int $productId,ManageCustomerWishlist $u):JsonResponse{$u->remove($productId);return response()->json(null,204);}
 public function preferences(PreferencesRequest $r,ManageCustomerPreferences $u):JsonResponse{return response()->json(['data'=>$u->show()]);}
 public function updatePreferences(PreferencesRequest $r,ManageCustomerPreferences $u):JsonResponse{return response()->json(['data'=>$u->update($r->validated('data'))]);}
 public function notifications(NotificationRequest $r,ManageCustomerNotifications $u):JsonResponse{return response()->json(['data'=>$u->list()]);}
 public function readNotification(NotificationRequest $r,int $id,ManageCustomerNotifications $u):JsonResponse{return response()->json(['data'=>$u->read($id)]);}
}
