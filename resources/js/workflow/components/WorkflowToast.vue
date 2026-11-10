<script setup>
import {onMounted,onBeforeUnmount} from 'vue';
import {iconMarkup} from '../../common/icons';
import {announce} from '../../common/toast';
import '../../../css/workflow-shell.css';
defineProps({closeLabel:{type:String,default:'Close'}});
function forwarded(event){if(typeof event.detail?.message==='string')announce(event.detail.message,event.detail.error===true);}
onMounted(()=>window.addEventListener('jobic:workflow:toast',forwarded));
onBeforeUnmount(()=>window.removeEventListener('jobic:workflow:toast',forwarded));
</script>
<template>
    <div class="ui-toast cms-toast rf-workspace-toast" data-ui-toast data-workflow-toast role="status" aria-live="polite" aria-atomic="true" popover="manual" hidden>
        <span class="rf-toast-status"><span class="rf-toast-success" v-html="iconMarkup('check-circle',20)"/><span class="rf-toast-error" v-html="iconMarkup('info',20)"/></span>
        <span data-ui-toast-message></span><button type="button" data-toast-dismiss :aria-label="closeLabel"><span v-html="iconMarkup('x',16)"/></button>
    </div>
</template>
