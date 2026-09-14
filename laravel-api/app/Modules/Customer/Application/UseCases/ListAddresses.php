<?php
namespace App\Modules\Customer\Application\UseCases;
use App\Modules\Auth\Domain\Contracts\AuthenticationServiceInterface;use App\Modules\Customer\Domain\Contracts\AddressRepositoryInterface;use App\Modules\Auth\Domain\Exceptions\AuthenticationException;
final class ListAddresses{public function __construct(private readonly AuthenticationServiceInterface $auth,private readonly AddressRepositoryInterface $addresses){}public function execute():iterable{$user=$this->auth->user();if(!$user)throw new AuthenticationException('Unauthenticated.');return $this->addresses->listForUser($user->id);}}
