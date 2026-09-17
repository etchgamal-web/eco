<?php
namespace App\Modules\AI\Domain\Exceptions;
use RuntimeException;
class AiProviderException extends RuntimeException {public function __construct(string $message='AI provider request failed.',private readonly string $errorCode='AI_PROVIDER_ERROR',private readonly int $httpStatus=503,private readonly bool $retryable=true,?\Throwable $previous=null){parent::__construct($message,0,$previous);} public function errorCode():string{return $this->errorCode;} public function httpStatus():int{return $this->httpStatus;} public function retryable():bool{return $this->retryable;}}
