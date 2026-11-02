<div class="ff-media-backdrop" x-show="htmlImportOpen" x-cloak x-trap.inert.noscroll="htmlImportOpen" x-on:keydown.escape.stop="if(!htmlImportBusy)htmlImportOpen=false">
<section class="ff-media-dialog ff-import-dialog" role="dialog" aria-modal="true" aria-labelledby="ff-import-title">
<header class="ff-import-header"><h2 id="ff-import-title"><span class="ff-heading-icon" x-html="iconSvg('file-code')"></span>{{ __('filaforge::studio.import_html') }}</h2><button type="button" class="ff-close-button" x-on:click="htmlImportOpen=false" :disabled="htmlImportBusy" aria-label="{{ __('filaforge::studio.close') }}"><span x-html="iconSvg('xmark')"></span></button></header>
<div class="ff-import-body"><p class="ff-import-intro">{{ __('filaforge::studio.import_intro') }}</p>
<label class="ff-source-field"><span>{{ __('filaforge::studio.import_code') }}</span><textarea dir="ltr" x-model="htmlSource" maxlength="262144" placeholder="<section>
  <h1>Hello, world!</h1>
  <p>...</p>
</section>" :disabled="htmlImportBusy" spellcheck="false"></textarea></label>
<details class="ff-import-help"><summary>{{ __('filaforge::studio.import_details') }}</summary><p>{{ __('filaforge::studio.import_help') }}</p></details>
<p class="ff-import-error" role="alert" x-show="htmlImportError" x-text="htmlImportError"></p>
<ul class="ff-import-warnings" x-show="htmlImportWarnings.length"><template x-for="warning in htmlImportWarnings"><li x-text="warning"></li></template></ul></div>
<footer class="ff-import-footer"><p>{{ __('filaforge::studio.import_review') }}</p><div><button type="button" class="ff-btn" x-on:click="htmlImportOpen=false" :disabled="htmlImportBusy">{{ __('filaforge::studio.cancel') }}</button><button type="button" class="ff-btn ff-primary" x-on:click="importHtmlContent()" :disabled="htmlImportBusy||!htmlSource.trim()"><span class="ff-button-icon" x-html="iconSvg('arrow-right-arrow-left')"></span>{{ __('filaforge::studio.import_convert') }}</button></div></footer>
</section></div>
