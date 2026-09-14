<?php
namespace App\Modules\Customer\Application\UseCases;
use App\Modules\Auth\Domain\Contracts\AuthenticationServiceInterface;use App\Modules\Auth\Domain\Exceptions\AuthenticationException;use App\Modules\Customer\Domain\Contracts\AddressRepositoryInterface;
final class DeleteAddress{public function __construct(private readonly AuthenticationServiceInterface $auth,private readonly AddressRepositoryInterface $addresses){}public function execute(int $id):void{$user=$this->auth->user();if(!$user)throw new AuthenticationException('Unauthenticated.');$this->addresses->delete($this->addresses->findForUser($user->id,$id));}}
