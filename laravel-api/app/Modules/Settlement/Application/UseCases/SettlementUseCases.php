<?php
namespace App\Modules\Settlement\Application\UseCases;
use App\Modules\Settlement\Domain\Contracts\SettlementRepositoryInterface;
final class ImportSettlement { public function __construct(private readonly SettlementRepositoryInterface $repo){} public function execute(string $path,string $provider,?string $from,?string $to):mixed{return $this->repo->import($path,$provider,$from,$to);} }
final class GetSettlement { public function __construct(private readonly SettlementRepositoryInterface $repo){} public function execute(int $id):mixed{return $this->repo->show($id);} }
final class GetSettlementItems { public function __construct(private readonly SettlementRepositoryInterface $repo){} public function execute(int $id):mixed{return $this->repo->items($id);} }
final class FinalizeSettlement { public function __construct(private readonly SettlementRepositoryInterface $repo){} public function execute(int $id):mixed{return $this->repo->finalize($id);} }
final class GetSettlementSettings { public function __construct(private readonly SettlementRepositoryInterface $repo){} public function execute():array{return $this->repo->settings();} }
final class UpdateSettlementSetting { public function __construct(private readonly SettlementRepositoryInterface $repo){} public function execute(string $key,mixed $value):array{return $this->repo->saveSetting($key,$value);} }
