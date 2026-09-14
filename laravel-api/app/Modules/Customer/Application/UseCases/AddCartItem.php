<?php
namespace App\Modules\Customer\Application\UseCases;
use App\Modules\Auth\Domain\Contracts\AuthenticationServiceInterface;
use App\Modules\Auth\Domain\Exceptions\AuthenticationException;
use App\Modules\Customer\Domain\Contracts\CartRepositoryInterface;
final class AddCartItem { public function __construct(private readonly AuthenticationServiceInterface $auth,private readonly CartRepositoryInterface $cart){} public function execute(int $productId,?int $variantId,int $quantity): object{$u=$this->auth->user();if(!$u)throw new AuthenticationException('Unauthenticated.');return $this->cart->addItem($u->id,$productId,$variantId,$quantity);} }
