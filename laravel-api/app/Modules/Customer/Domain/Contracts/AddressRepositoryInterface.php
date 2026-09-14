<?php
namespace App\Modules\Customer\Domain\Contracts;
interface AddressRepositoryInterface{public function listForUser(int $userId):iterable;public function defaultForUser(int $userId):?object;public function findForUser(int $userId,int $addressId): object;public function createForUser(int $userId,array $data): object;public function update(object $address,array $data): object;public function delete(object $address):void;}
