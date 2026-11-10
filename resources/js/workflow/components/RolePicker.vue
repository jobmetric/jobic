<script setup>
import {computed, nextTick, onMounted, ref, watch} from 'vue';
import {enhanceSelects} from '../../common/selects';
const props = defineProps({modelValue:{type:Array,default:()=>[]}, roles:{type:Array,default:()=>[]}, disabled:Boolean, copy:{type:Object,required:true}});
const emit = defineEmits(['update:modelValue']);
const root=ref(null);const select=ref(null);
const selected=computed(()=>props.roles.filter(role=>props.modelValue.map(Number).includes(Number(role.id))));
const available=computed(()=>props.roles.filter(role=>!props.modelValue.map(Number).includes(Number(role.id))));
function add(event){const id=Number(event.target.value);if(id&&!props.modelValue.map(Number).includes(id))emit('update:modelValue',[...props.modelValue.map(Number),id]);event.target.value='';}
onMounted(()=>enhanceSelects(root.value));
watch(available,()=>nextTick(()=>{if(window.jQuery)window.jQuery(select.value).val('').trigger('change.select2');}));
</script>
<template><div ref="root" class="rf-role-picker"><select ref="select" :disabled="disabled" :aria-label="copy.transition_roles" @change="add"><option value="">{{ copy.select_role }}</option><option v-for="role in available" :key="role.id" :value="role.id">{{ role.name }}</option></select><ul v-if="selected.length"><li v-for="role in selected" :key="role.id"><span>{{ role.name }}</span><button type="button" :disabled="disabled" :aria-label="`${copy.remove_role}: ${role.name}`" @click="emit('update:modelValue',modelValue.map(Number).filter(id=>id!==Number(role.id)))">×</button></li></ul></div></template>
<style>.rf-role-picker{min-width:0;width:100%}.rf-role-picker ul{display:flex;flex-wrap:wrap;gap:7px;margin-top:10px}.rf-role-picker li{display:flex;align-items:center;gap:10px;max-width:100%;padding:5px 10px;background:color-mix(in srgb,#2eae98 10%,transparent);color:var(--rf-text,#243654);border:1px solid color-mix(in srgb,#2eae98 25%,transparent);border-radius:8px;font-size:12px}.rf-role-picker li span{overflow-wrap:anywhere}.rf-role-picker li button{border:0;background:none;color:inherit;cursor:pointer;font-size:18px}.rf-role-picker .select2-container{max-width:100%}</style>
