<template x-if="find(selected)?.type==='progress'"><div>
<label class="ff-source-field"><span>{{ __('filaforge::studio.progress_label') }}</span><input type="text" maxlength="200" :value="find(selected).props.label" x-on:change="setFlow('label',$event.target.value)"></label>
<label class="ff-source-field"><span>{{ __('filaforge::studio.progress_value') }} <b x-text="find(selected).props.value+'%'"></b></span><input type="range" min="0" max="100" step="1" :value="find(selected).props.value" x-on:change="setFlow('value',Number($event.target.value))"></label>
<label class="ff-source-field"><span>{{ __('filaforge::studio.progress_color') }}</span><input type="color" :value="find(selected).props.color" x-on:change="setFlow('color',$event.target.value)"></label>
<label class="ff-source-field"><span>{{ __('filaforge::studio.progress_height') }}</span><input type="number" min="8" max="40" :value="find(selected).props.height" x-on:change="setFlow('height',Math.max(8,Math.min(40,Number($event.target.value))))"></label>
<label class="ff-source-field"><span>{{ __('filaforge::studio.progress_show_value') }}</span><input type="checkbox" :checked="find(selected).props.showValue" x-on:change="setFlow('showValue',$event.target.checked)"></label>
</div></template>
<template x-if="find(selected)?.type==='wizard'"><div>
<label class="ff-source-field"><span>{{ __('filaforge::studio.wizard_previous') }}</span><input type="text" maxlength="200" :value="find(selected).props.previous" x-on:change="setFlow('previous',$event.target.value)"></label>
<label class="ff-source-field"><span>{{ __('filaforge::studio.wizard_next') }}</span><input type="text" maxlength="200" :value="find(selected).props.next" x-on:change="setFlow('next',$event.target.value)"></label>
<label class="ff-source-field"><span>{{ __('filaforge::studio.wizard_start') }}</span><select :value="find(selected).props.start" x-on:change="setFlow('start',Number($event.target.value))"><template x-for="(step,index) in find(selected).children" :key="step.id"><option :value="index" :selected="find(selected).props.start===index" x-text="step.props.title"></option></template></select></label>
<div class="ff-data-group"><template x-for="step in find(selected).children" :key="step.id"><button type="button" class="ff-picker-option" x-on:click="select(step.id)" x-text="step.props.title"></button></template></div>
<button type="button" class="ff-btn ff-picker-launch" :disabled="find(selected).children.length>=12" x-on:click="addWizardStep()">{{ __('filaforge::studio.wizard_add_step') }}</button>
</div></template>
<template x-if="find(selected)?.type==='wizard-step'"><label class="ff-source-field"><span>{{ __('filaforge::studio.wizard_step') }}</span><input type="text" maxlength="200" :value="find(selected).props.title" x-on:change="setFlow('title',$event.target.value)"></label></template>
