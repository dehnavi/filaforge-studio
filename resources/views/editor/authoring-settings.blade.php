<style>.ff-layout-tools{display:flex;flex-wrap:wrap;gap:7px;margin-top:12px}.ff-layout-tools .ff-btn{flex:1;min-width:92px;font-size:11px}.ff-layout-tools .ff-btn[aria-pressed=true]{border-color:var(--ff-accent);background:#eeedff;color:#4338ca}</style>
<div x-show="find(selected)?.type==='html'" class="ff-data-fields">
<label class="ff-source-field"><span>{{ __('filaforge::studio.editor_ui.html_insert_tag') }}</span><select x-on:change="insertHtmlTag($event.target.value); $event.target.value=''">
<option value="">{{ __('filaforge::studio.editor_ui.html_choose_tag') }}</option>
@foreach (['article', 'section', 'header', 'footer', 'aside', 'figure', 'figcaption', 'div', 'p', 'span', 'h2', 'h3', 'strong', 'em', 'ul', 'ol', 'li', 'blockquote'] as $tag)
<option value="{{ $tag }}">&lt;{{ $tag }}&gt;</option>
@endforeach
</select></label>
<label class="ff-source-field"><span>HTML</span><textarea class="ff-search" rows="12" dir="ltr" maxlength="100000" x-ref="htmlSourceEditor" x-model="draftProps.html"></textarea></label>
<p class="ff-muted">{{ __(config('filaforge.embeds.scripts', false) ? 'filaforge::studio.builder.html_scripts_help' : 'filaforge::studio.builder.html_help') }}</p>
<button type="button" class="ff-btn ff-primary" x-on:click="applyHtml()"><span class="ff-button-icon" x-html="iconSvg('check')"></span>{{ __('filaforge::studio.builder.apply_html') }}</button>
</div>
<div x-show="find(selected)?.type==='embed'" class="ff-data-fields">
<label class="ff-source-field"><span>{{ __('filaforge::studio.editor_ui.embed_code') }}</span><textarea class="ff-search" rows="5" dir="ltr" spellcheck="false" maxlength="20000" x-model="draftProps.code" :disabled="embedBusy" aria-describedby="ff-embed-help" x-on:input="embedNotice='';embedError=''"></textarea></label>
<label class="ff-source-field"><span>{{ __('filaforge::studio.editor_ui.embed_title') }}</span><input maxlength="200" x-model="draftProps.title" :disabled="embedBusy"></label>
<p class="ff-muted" id="ff-embed-help">{{ __('filaforge::studio.editor_ui.embed_help') }}</p>
<p class="ff-muted" role="status" x-show="embedNotice" x-text="embedNotice"></p>
<p class="ff-muted" role="alert" style="color:#b42318" x-show="embedError" x-text="embedError"></p>
<div class="ff-actions">
<button type="button" class="ff-btn ff-primary" x-on:click="applyEmbed()" :disabled="embedBusy"><span class="ff-button-icon" x-html="iconSvg('check')"></span><span x-text="embedBusy ? @js(__('filaforge::studio.editor_ui.embed_checking')) : @js(__('filaforge::studio.editor_ui.embed_apply'))"></span></button>
<button type="button" class="ff-btn" x-show="find(selected)?.props.code && draftProps.code===find(selected)?.props.code && draftProps.title===find(selected)?.props.title" x-on:click="togglePreview()" :disabled="embedBusy || previewBusy">{{ __('filaforge::studio.editor_ui.embed_preview') }}</button>
</div>
</div>
<div x-show="find(selected)?.type==='shared-section'" class="ff-data-fields">
<label class="ff-source-field"><span>{{ __('filaforge::studio.builder.shared_section') }}</span><select :value="find(selected)?.props.key" x-on:change="commit(()=>{find(selected).props.key=$event.target.value;find(selected).props.values={}})"><template x-for="section in sectionItems" :key="section.key"><option :value="section.key" x-text="section.label"></option></template></select></label>
<p class="ff-muted">{{ __('filaforge::studio.builder.shared_help') }}</p>
<template x-for="(field,key) in (sectionItems.find(section=>section.key===find(selected)?.props.key)?.fields||{})" :key="key"><div class="ff-data-fields">
<label class="ff-builder-check"><input type="checkbox" :checked="Object.hasOwn(find(selected)?.props.values||{},key)" x-on:change="setSectionOverride(key,$event.target.checked?(field.type==='number'?0:field.type==='boolean'?false:''):undefined)"><span x-text="field.label"></span></label>
<label class="ff-source-field" x-show="Object.hasOwn(find(selected)?.props.values||{},key)"><span x-text="field.label"></span><input :type="field.type==='number'?'number':field.type==='boolean'?'checkbox':'text'" :checked="!!find(selected)?.props.values?.[key]" :value="find(selected)?.props.values?.[key]??''" maxlength="20000" x-on:change="setSectionOverride(key,field.type==='number'?Number($event.target.value):field.type==='boolean'?$event.target.checked:$event.target.value)"></label>
</div></template>
</div>
<div x-show="customInspector" wire:key="custom-inspector-{{ $this->customWidgetType }}">
{{ $this->customForm }}
<button type="button" class="ff-btn ff-primary" x-on:click="applyCustom()"><span class="ff-button-icon" x-html="iconSvg('check')"></span>{{ $ffEditor('apply_changes') }}</button>
</div>
<details class="ff-settings-group">
<summary><span class="ff-settings-label"><span class="ff-heading-icon" x-html="iconSvg('palette')"></span>{{ __('filaforge::studio.builder.tokens') }}</span><span class="ff-settings-chevron" x-html="iconSvg('chevron-down')"></span></summary><div class="ff-settings-content">
<template x-for="property in ['backgroundColor','color','borderColor','fontFamily','borderRadius']" :key="property"><label class="ff-source-field" x-show="tokensFor(property).length"><span x-text="builderPropertyLabel(property)"></span><select :value="find(selected)?.styles?.[property]?.token||''" x-on:change="commit(()=>{const node=find(selected);if($event.target.value)node.styles[property]={token:$event.target.value};else delete node.styles[property]})"><option value="">{{ __('filaforge::studio.data_default') }}</option><template x-for="[key,token] in tokensFor(property)" :key="key"><option :value="key" x-text="token.label"></option></template></select></label></template>
<template x-for="property in ['padding','gap','fontSize','maxWidth']" :key="property"><label class="ff-source-field" x-show="tokensFor(property).length"><span x-text="builderPropertyLabel(property)"></span><select :value="find(selected)?.responsive?.[device]?.[property]?.token||''" x-on:change="commit(()=>{const node=find(selected);node.responsive[device]??={};if($event.target.value)node.responsive[device][property]={token:$event.target.value};else delete node.responsive[device][property]})"><option value="">{{ __('filaforge::studio.data_default') }}</option><template x-for="[key,token] in tokensFor(property)" :key="key"><option :value="key" x-text="token.label"></option></template></select></label></template>
<div class="ff-actions"><button type="button" class="ff-btn" x-on:click="copyStyle()"><span class="ff-button-icon" x-html="iconSvg('copy')"></span>{{ __('filaforge::studio.builder.copy_style') }}</button><button type="button" class="ff-btn" x-on:click="pasteStyle()" :disabled="!styleClipboard"><span class="ff-button-icon" x-html="iconSvg('paste')"></span>{{ __('filaforge::studio.builder.paste_style') }}</button></div>
</div></details>
<div class="ff-inspector-card" x-show="['section','card'].includes(find(selected)?.type)||columnNode()">
<div class="ff-style-heading"><div><h2>{{ __('filaforge::studio.editor_ui.visual_layout_title') }}</h2><p class="ff-muted">{{ __('filaforge::studio.editor_ui.visual_layout_hint') }}</p></div></div>
<div class="ff-layout-tools" x-show="['section','card'].includes(find(selected)?.type)">
<button type="button" class="ff-btn" :aria-pressed="find(selected)?.props.tone==='white'" x-on:click="visualAction(selected,'tone:white')">{{ __('filaforge::studio.editor_ui.white') }}</button><button type="button" class="ff-btn" :aria-pressed="find(selected)?.props.tone==='soft'" x-on:click="visualAction(selected,'tone:soft')">{{ __('filaforge::studio.editor_ui.soft') }}</button><button type="button" class="ff-btn" :aria-pressed="find(selected)?.props.tone==='brand'" x-on:click="visualAction(selected,'tone:brand')">{{ __('filaforge::studio.editor_ui.brand') }}</button><button type="button" class="ff-btn" x-on:click="visualAction(selected,'padding-down')">{{ __('filaforge::studio.editor_ui.spacing_down') }}</button><button type="button" class="ff-btn" x-on:click="visualAction(selected,'padding-up')">{{ __('filaforge::studio.editor_ui.spacing_up') }}</button>
</div>
<p class="ff-muted" x-show="['section','card'].includes(find(selected)?.type)" x-text="@js(__('filaforge::studio.editor_ui.field_padding'))+': '+(find(selected)?.props.padding??'')+' px'"></p>
<div class="ff-layout-tools" x-show="columnNode()"><button type="button" class="ff-btn ff-primary" x-on:click="addColumn()" :disabled="!columnNode()||columnNode().children.length>=6">＋ {{ __('filaforge::studio.editor_ui.add_column') }}</button><button type="button" class="ff-btn" x-on:click="removeColumn()" :disabled="!columnNode()||columnNode().children.length<=1">− {{ __('filaforge::studio.editor_ui.remove_column') }}</button></div>
<p class="ff-muted" x-show="columnNode()" x-text="(columnNode()?.children.length||0)+' '+@js(__('filaforge::studio.editor_ui.columns_count'))"></p>
</div>
<details class="ff-settings-group" x-show="structureManager"><summary><span class="ff-settings-label"><span class="ff-heading-icon" x-html="iconSvg('lock')"></span>{{ __('filaforge::studio.builder.editing_rules') }}</span><span class="ff-settings-chevron" x-html="iconSvg('chevron-down')"></span></summary><div class="ff-settings-content">
<label class="ff-source-field"><span>{{ __('filaforge::studio.builder.layer_name') }}</span><input maxlength="100" :value="find(selected)?.meta?.label||''" x-on:change="setLayerMeta('label',$event.target.value)"></label>
<label class="ff-builder-check"><input type="checkbox" :checked="!!find(selected)?.meta?.locked" x-on:change="setLayerMeta('locked',$event.target.checked||null)"><span>{{ __('filaforge::studio.builder.lock_element') }}</span></label>
<label class="ff-builder-check"><input type="checkbox" :checked="Array.isArray(find(selected)?.meta?.editable)" x-on:change="setLayerMeta('editable',$event.target.checked?[]:null)"><span>{{ __('filaforge::studio.builder.content_only') }}</span></label>
<div x-show="Array.isArray(find(selected)?.meta?.editable)"><p class="ff-muted">{{ __('filaforge::studio.builder.exposed_help') }}</p><template x-for="key in Object.keys(find(selected)?.props||{}).filter(key=>key!=='formatted')" :key="key"><label class="ff-builder-check"><input type="checkbox" :checked="find(selected)?.meta?.editable?.includes(key)" x-on:change="exposeField(key,$event.target.checked)"><span x-text="builderPropertyLabel(key)"></span></label></template></div>
</div></details>
