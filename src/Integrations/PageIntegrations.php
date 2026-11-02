<?php
namespace FilaForge\Studio\Integrations;
use FilaForge\Studio\Models\PageLocale;
use FilaForge\Studio\Rendering\HtmlOptions;
use Illuminate\Support\Facades\Gate;
use InvalidArgumentException;
final class PageIntegrations
{
    public function normalize(mixed $settings): array
    {
        if(!is_array($settings)||array_diff(array_keys($settings),['gtm','head','bodyEnd']))throw new InvalidArgumentException('Invalid page integrations.');
        $normalized=[];
        foreach($settings as $key=>$value){
            if(!is_string($value)||strlen($value)>20000)throw new InvalidArgumentException('Invalid page script.');
            $value=trim($value);if($value==='')continue;
            if($key==='gtm'&&!preg_match('/^GTM-[A-Z0-9]{4,20}$/D',$value))throw new InvalidArgumentException('Invalid Google Tag Manager ID.');
            if($key!=='gtm'&&preg_match('~<\s*/?\s*script\b~i',$value))throw new InvalidArgumentException('Enter JavaScript without script tags.');
            $normalized[$key]=$value;
        }
        ksort($normalized);return $normalized;
    }
    public function authorizeChange(PageLocale $page,array $before,array $after):void
    {
        $before=$this->normalize($before['integrations']??[]);$after=$this->normalize($after['integrations']??[]);
        if($before===$after)return;
        Gate::authorize('filaforge.manageScripts',$page);
        if(isset($after['gtm'])&&!config('filaforge.integrations.gtm_enabled',true))throw new InvalidArgumentException('Google Tag Manager is disabled.');
        if((isset($after['head'])||isset($after['bodyEnd']))&&!config('filaforge.integrations.custom_scripts_enabled',false))throw new InvalidArgumentException('Custom page scripts are disabled.');
    }
    public function authorizePublication(PageLocale $page,array $document):void
    {
        if($this->normalize($document['integrations']??[])!==[])Gate::authorize('filaforge.manageScripts',$page);
    }
    /** Intentionally executable output: opt-in host config + privileged writes, never sanitized user HTML. */
    public function markup(array $document,HtmlOptions $options):array
    {
        $empty=['head'=>'','bodyStart'=>'','bodyEnd'=>''];if(!$options->includePageScripts)return $empty;
        $settings=$this->normalize($document['integrations']??[]);$result=$empty;$nonce=$options->scriptNonce;
        $attribute=$nonce===null?'':' nonce="'.e($nonce).'"';
        if(isset($settings['gtm'])&&config('filaforge.integrations.gtm_enabled',true)){
            $id=json_encode($settings['gtm'],JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_THROW_ON_ERROR);
            $result['head']='<script'.$attribute.'>(function(){window.dataLayer=window.dataLayer||[];window.dataLayer.push({"gtm.start":Date.now(),event:"gtm.js"});var tag=document.createElement("script");tag.async=true;tag.src="https://www.googletagmanager.com/gtm.js?id="+'.$id.';'.($nonce===null?'':'tag.nonce='.json_encode($nonce,JSON_THROW_ON_ERROR).';').'document.head.appendChild(tag);})();</script>';
            $result['bodyStart']='<noscript><iframe src="https://www.googletagmanager.com/ns.html?id='.e($settings['gtm']).'" height="0" width="0" style="display:none;visibility:hidden" title="Google Tag Manager"></iframe></noscript>';
        }
        if(config('filaforge.integrations.custom_scripts_enabled',false))foreach(['head','bodyEnd'] as $slot)if(isset($settings[$slot]))$result[$slot].='<script'.$attribute.'>'.$settings[$slot].'</script>';
        return $result;
    }
}
