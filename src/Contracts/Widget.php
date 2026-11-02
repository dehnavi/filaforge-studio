<?php
namespace FilaForge\Studio\Contracts;
interface Widget
{
    public function name(): string;
    public function version(): int;
    public function defaults(): array;
    public function acceptsChildren(): bool;
    public function validate(array $props): void;
    public function view(): string;
}
