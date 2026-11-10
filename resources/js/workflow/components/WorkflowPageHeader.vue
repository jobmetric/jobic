<script setup>
import {iconMarkup} from '../../common/icons';
import '../../../css/workflow-shell.css';
defineProps({title:{type:String,required:true},backUrl:{type:String,default:null},backLabel:{type:String,default:''},settingsLabel:{type:String,default:''},busy:Boolean});
const emit=defineEmits(['settings']);
</script>
<template>
    <header class="rf-header">
        <div class="rf-header__left">
            <a v-if="backUrl" class="rf-header__back" :href="backUrl" :aria-label="backLabel" :title="backLabel"><span class="rf-back-icon" v-html="iconMarkup('arrow-left',18)" /></a>
            <div class="rf-header__titles"><h1 class="rf-header__title">{{ title }}</h1></div>
        </div>
        <div v-if="$slots.center" class="rf-header__center"><slot name="center" /></div>
        <div v-if="settingsLabel || $slots.actions || $slots['before-settings']" class="rf-header__right">
            <slot name="before-settings" />
            <button v-if="settingsLabel" type="button" class="rf-btn rf-btn--compact rf-settings-trigger" :disabled="busy" :aria-label="settingsLabel" :title="settingsLabel" @click="emit('settings')"><span v-html="iconMarkup('settings',18)" /></button>
            <slot name="actions" />
        </div>
    </header>
</template>
