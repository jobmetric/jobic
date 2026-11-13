<script setup>
import {nextTick,onMounted,ref,watch} from 'vue';
import {iconMarkup} from '../../common/icons';
import {enhanceSelects} from '../../common/selects';
import '../../../css/workflow-shell.css';
const props=defineProps({open:Boolean,title:{type:String,required:true},intro:{type:String,default:''},closeLabel:{type:String,required:true},busy:Boolean,icon:{type:String,default:'settings'}});
const emit=defineEmits(['close']);
const dialog=ref(null);
function showModal(){ if(!dialog.value?.open) dialog.value?.showModal(); nextTick(()=>enhanceSelects(dialog.value)); }
function close(){ if(dialog.value?.open) dialog.value.close(); }
function requestClose(){ if(!props.busy) close(); }
function backdrop(event){ if(event.target!==dialog.value)return; const rect=dialog.value.getBoundingClientRect(); if(event.clientX<rect.left||event.clientX>rect.right||event.clientY<rect.top||event.clientY>rect.bottom)requestClose(); }
watch(()=>props.open,open=>{if(open)showModal();else close();},{flush:'post'});
onMounted(()=>{if(props.open)showModal();});
defineExpose({showModal,close});
</script>
<template>
    <dialog ref="dialog" class="rf-settings-dialog" :aria-label="title" @close="emit('close')" @cancel="busy && $event.preventDefault()" @click="backdrop">
        <header class="rf-settings-dialog__head">
            <span class="rf-settings-dialog__icon" v-html="iconMarkup(props.icon,20)"/>
            <div><h2>{{ title }}</h2><p v-if="intro">{{ intro }}</p></div>
            <slot name="header-actions" />
            <button type="button" class="rf-mapping__close" :disabled="busy" :aria-label="closeLabel" @click="requestClose"><span v-html="iconMarkup('x',18)"/></button>
        </header>
        <div class="rf-settings-dialog__body"><slot /></div>
        <footer class="rf-settings-dialog__foot"><slot name="footer" /></footer>
    </dialog>
</template>
