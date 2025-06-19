<?php

namespace App\Dtos\Utilities;

use Spatie\LaravelData\Data;
use Spatie\LaravelData\Optional;

class ServiceProviderDto extends Data
{
    public function __construct(
        public string $name,
        public string $description,
        public bool $status
    ) {}
}
