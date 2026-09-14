<?php
namespace App\Modules\Customer\Application\UseCases;
use App\Modules\Auth\Domain\Contracts\AuthenticationServiceInterface;use App\Modules\Auth\Domain\Exceptions\AuthenticationException;use App\Modules\Customer\Domain\Contracts\AddressRepositoryInterface;use App\Modules\Customer\Domain\Exceptions\AddressNotFoundException;
final class GetDefaultAddress{public function __construct(private readonly AuthenticationServiceInterface $auth,private readonly AddressRepositoryInterface $addresses){}public function execute(){$user=$this->auth->user();if(!$user)throw new AuthenticationException('Unauthenticated.');$address=$this->addresses->defaultForUser($user->id);if(!$address)throw new AddressNotFoundException('Default address not found.');return $address;}}
