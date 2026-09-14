<?php
namespace App\Modules\Customer\Application\UseCases;
use App\Modules\Auth\Domain\Contracts\AuthenticationServiceInterface;use App\Modules\Auth\Domain\Exceptions\AuthenticationException;use App\Modules\Customer\Domain\ValueObjects\AddressData;use App\Modules\Customer\Domain\Contracts\AddressRepositoryInterface;
final class UpdateAddress{public function __construct(private readonly AuthenticationServiceInterface $auth,private readonly AddressRepositoryInterface $addresses){}public function execute(int $id,AddressData $data){$user=$this->auth->user();if(!$user)throw new AuthenticationException('Unauthenticated.');return $this->addresses->update($this->addresses->findForUser($user->id,$id),$data->toArray());}}
