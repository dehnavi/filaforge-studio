<?php
namespace FilaForge\Studio\Policies;
final class PageLocalePolicy {
    public function before($user, string $ability): ?bool { return $user->can('filaforge.'.$ability) ? true : null; }
    public function viewAny($user): bool { return false; }
    public function view($user,$page): bool { return false; }
    public function create($user): bool { return false; }
    public function update($user,$page): bool { return false; }
    public function delete($user,$page): bool { return false; }
    public function publish($user,$page): bool { return false; }
}
