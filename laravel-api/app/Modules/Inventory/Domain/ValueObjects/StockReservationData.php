<?php
namespace App\Modules\Inventory\Domain\ValueObjects;
final readonly class StockReservationData{public function __construct(public int $productId,public ?int $variantId,public int $quantity){}public static function fromArray(array $data):self{return new self((int)$data['product_id'],isset($data['variant_id'])?(int)$data['variant_id']:null,(int)$data['quantity']);}}
