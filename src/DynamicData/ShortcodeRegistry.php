<?php
namespace FilaForge\Studio\DynamicData;
use Closure;
use Illuminate\Support\Facades\Validator;
use InvalidArgumentException;
final class ShortcodeRegistry
{
    private array $components=[];
    public function register(string $name,string $label,array $rules,Closure $build,?Closure $authorize = null,bool $public = false): void
    {
        if(!preg_match('/^[a-z][a-z0-9-]{0,63}$/D',$name)||isset($this->components[$name]))throw new InvalidArgumentException('Invalid shortcode component.');
        $this->components[$name]=compact('label','rules','build','authorize','public');
    }
    public function catalog(): array {return array_map(fn($name,$d)=>['name'=>$name,'label'=>$d['label'],'arguments'=>array_keys($d['rules'])],array_keys($this->components),array_values($this->components));}
    public function validate(string $name,array $arguments): void
    {
        $definition=$this->components[$name]??throw new InvalidArgumentException('Unknown shortcode component.');
        if(array_diff(array_keys($arguments),array_keys($definition['rules']))||Validator::make($arguments,$definition['rules'])->fails())throw new InvalidArgumentException('Invalid shortcode arguments.');
        foreach($arguments as $value)if((!is_scalar($value)&&$value!==null)||(is_string($value)&&mb_strlen($value)>1000))throw new InvalidArgumentException('Invalid shortcode argument.');
    }
    public function build(string $name,array $arguments,DataContext $context): array
    {
        $this->validate($name,$arguments);
        $definition=$this->components[$name];
        if (($context->publicAudience&&!$definition['public'])||(!$definition['public']&&$context->user===null)||$definition['authorize']===null||!($definition['authorize'])($context)) return \FilaForge\Studio\Core\DocumentValidator::empty($context->locale)['root'];
        return ($this->components[$name]['build'])($arguments,$context);
    }
}
