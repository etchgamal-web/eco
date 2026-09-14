<?php
namespace App\Modules\Customer\Application\UseCases;
use App\Modules\Auth\Domain\Contracts\AuthenticationServiceInterface;use App\Modules\Auth\Domain\Exceptions\AuthenticationException;use App\Modules\Customer\Domain\Contracts\CustomerOrderRepositoryInterface;
final class GetCustomerOrders { public function __construct(private readonly AuthenticationServiceInterface $auth,private readonly CustomerOrderRepositoryInterface $repo){} public function execute():iterable{$u=$this->auth->user();if(!$u)throw new AuthenticationException('Unauthenticated.');return $this->repo->listForUser($u->id);} }
