<?php
namespace App\Modules\Customer\Application\UseCases;
use App\Modules\Auth\Domain\Contracts\AuthenticationServiceInterface;use App\Modules\Auth\Domain\Exceptions\AuthenticationException;use App\Modules\Customer\Domain\Contracts\CustomerPreferencesRepositoryInterface;
final class ManageCustomerPreferences {public function __construct(private readonly AuthenticationServiceInterface $auth,private readonly CustomerPreferencesRepositoryInterface $repo){}private function id():int{$u=$this->auth->user();if(!$u)throw new AuthenticationException('Unauthenticated.');return $u->id;}public function show(){return $this->repo->get($this->id());}public function update(array $data){return $this->repo->update($this->id(),$data);} }
