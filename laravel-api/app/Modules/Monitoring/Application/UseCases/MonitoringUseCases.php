<?php
namespace App\Modules\Monitoring\Application\UseCases;
use App\Modules\Monitoring\Domain\Contracts\MonitoringRepositoryInterface;
final class GetMonitoringSettings { public function __construct(private readonly MonitoringRepositoryInterface $repo){} public function execute():array{return $this->repo->settings();} }
final class UpdateMonitoringSetting { public function __construct(private readonly MonitoringRepositoryInterface $repo){} public function execute(string $type,int $days,bool $enabled):array{return $this->repo->saveSetting($type,$days,$enabled);} }
final class DetectDelayedOrders { public function __construct(private readonly MonitoringRepositoryInterface $repo){} public function execute():array{return $this->repo->detect();} }
final class GetDelayedOrders { public function __construct(private readonly MonitoringRepositoryInterface $repo){} public function execute(array $filters):array{return $this->repo->delayed($filters);} }
final class AcknowledgeOperationalAlert { public function __construct(private readonly MonitoringRepositoryInterface $repo){} public function execute(int $id,int $userId):mixed{return $this->repo->acknowledge($id,$userId);} }
final class ResolveOperationalAlert { public function __construct(private readonly MonitoringRepositoryInterface $repo){} public function execute(int $id,int $userId):mixed{return $this->repo->resolve($id,$userId);} }
