<?php
namespace FilaForge\Studio\DynamicData;
use FilaForge\Studio\Models\PageLocale;
use Illuminate\Contracts\Auth\Authenticatable;
final readonly class DataContext
{
    /** Tenant, record and identity come from trusted host code, never document JSON. */
    public function __construct(
        public string $locale = 'en', public ?PageLocale $page = null,
        public ?string $tenantKey = null, public ?string $recordKey = null,
        public ?Authenticatable $user = null, public bool $publicAudience = true,
        public array $pagination = [], public ?string $rowProvider = null, public ?array $row = null,
    ) {}
    public function withRow(string $provider, array $row): self
    {
        return new self($this->locale,$this->page,$this->tenantKey,$this->recordKey,$this->user,$this->publicAudience,$this->pagination,$provider,$row);
    }
    public function pageNumber(string $node): int
    {
        $page = $this->pagination[$node] ?? 1;
        return filter_var($page,FILTER_VALIDATE_INT,['options'=>['min_range'=>1,'max_range'=>1000]]) ?: 1;
    }
}
