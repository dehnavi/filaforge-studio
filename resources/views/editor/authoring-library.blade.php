<details class="ff-section ff-repeater-library" :open="repeaterOpen" x-on:toggle="repeaterOpen=$event.target.open">
<summary><span class="ff-settings-label"><span class="ff-heading-icon" x-html="iconSvg('database')"></span>{{ __('filaforge::studio.builder.repeater') }}</span><span class="ff-settings-chevron" x-html="iconSvg('chevron-down')"></span></summary>
<div class="ff-data-fields ff-repeater-fields">
<p class="ff-muted">{{ __('filaforge::studio.builder.repeater_help') }}</p>
<label class="ff-source-field"><span>{{ __('filaforge::studio.data_source') }}</span><select x-model="repeaterSource" x-on:change="repeaterFields=[]"><option value="">{{ __('filaforge::studio.builder.choose_source') }}</option><template x-for="source in sources" :key="source.name"><option :value="source.name" x-text="source.label"></option></template></select></label>
<fieldset x-show="repeaterSource"><legend>{{ __('filaforge::studio.builder.card_fields') }}</legend><template x-for="(field,key) in (source(repeaterSource)?.fields||{})" :key="key"><label class="ff-builder-check"><input type="checkbox" :value="key" x-model="repeaterFields"><span x-text="field.label||key"></span></label></template></fieldset>
<label class="ff-source-field"><span>{{ __('filaforge::studio.builder.columns') }}</span><input type="number" min="1" max="6" x-model.number="repeaterColumns"></label>
<button type="button" class="ff-btn ff-primary" x-on:click="addRepeater()" :disabled="!repeaterSource||!repeaterFields.length||repeaterBusy"><span class="ff-button-icon" x-html="iconSvg('circle-plus')"></span>{{ __('filaforge::studio.builder.add_repeater') }}</button>
</div></details>
