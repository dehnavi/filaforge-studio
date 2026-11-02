<?php
namespace FilaForge\Studio\DynamicData;
use Closure;
use InvalidArgumentException;
final class Formatters
{
    private array $custom=[];
    public function names(): array {return array_merge(['text','number','money','date','persian_date','persian_number'],array_keys($this->custom));}
    public function register(string $name, Closure $formatter): void
    {
        if (!preg_match('/^[a-z][a-z0-9_]{0,40}$/D',$name)||in_array($name,$this->names(),true)) throw new InvalidArgumentException('Invalid or duplicate formatter.');
        $this->custom[$name]=$formatter;
    }
    private function persianDate(mixed $value): string
    {
        if(!class_exists(\IntlDateFormatter::class)||trim((string)$value)===''||mb_strlen((string)$value)>100)throw new InvalidArgumentException('Persian dates require ext-intl and a valid date.');
        $date=new \DateTimeImmutable((string)$value);
        $formatter=new \IntlDateFormatter('fa_IR@calendar=persian',\IntlDateFormatter::NONE,\IntlDateFormatter::NONE,'Asia/Tehran',\IntlDateFormatter::TRADITIONAL,'yyyy/MM/dd');
        $result=$formatter->format($date);if(!is_string($result))throw new InvalidArgumentException('Invalid Persian date.');return $result;
    }
    public function format(mixed $value,string $name,string $locale): string
    {
        if (!is_scalar($value)&&$value!==null) throw new InvalidArgumentException('Formatter values must be scalar.');
        $number=fn()=>is_numeric($value)&&is_finite((float)$value)?(float)$value:throw new InvalidArgumentException('Expected number.');
        $text=match($name){
            'text'=>(string)$value,
            'number'=>number_format($number(),0,'.',','),
            'money'=>number_format($number(),2,'.',','),
            'persian_number'=>strtr((string)$value,['0'=>'۰','1'=>'۱','2'=>'۲','3'=>'۳','4'=>'۴','5'=>'۵','6'=>'۶','7'=>'۷','8'=>'۸','9'=>'۹']),
            'persian_date'=>$this->persianDate($value),
            'date'=>trim((string)$value)!==''&&mb_strlen((string)$value)<=100?(new \DateTimeImmutable((string)$value))->format('Y-m-d'):throw new InvalidArgumentException('Invalid date.'),
            default=>isset($this->custom[$name])?($this->custom[$name])($value,$locale):throw new InvalidArgumentException('Unknown formatter.'),
        };
        // Formatters return text, never Htmlable or executable markup.
        if (!is_string($text)||mb_strlen($text)>20000) throw new InvalidArgumentException('Invalid formatter output.');
        return $text;
    }
}
