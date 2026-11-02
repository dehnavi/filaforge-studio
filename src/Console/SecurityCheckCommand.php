<?php
namespace FilaForge\Studio\Console;
use Illuminate\Console\Command;
final class SecurityCheckCommand extends Command
{
    protected $signature='filaforge:security-check';
    protected $description='Check host session and deployment security settings without changing them';
    public function handle(): int
    {
        $checks=[
            'Session cookie is HttpOnly'=>config('session.http_only')===true,
            'Session SameSite is lax or strict'=>in_array(config('session.same_site'),['lax','strict'],true),
            'Session cookie is Secure over HTTPS'=>config('session.secure')===true,
            'Application URL uses HTTPS'=>str_starts_with((string)config('app.url'),'https://'),
            'Debug output is disabled'=>config('app.debug')===false,
        ];
        foreach($checks as $label=>$ok){$ok?$this->line('PASS: '.$label):$this->warn('REVIEW: '.$label);}
        $this->line('Authentication/session rotation and revocation remain the host authentication provider responsibility. This command changes no settings.');
        return in_array(false,$checks,true)?self::FAILURE:self::SUCCESS;
    }
}
