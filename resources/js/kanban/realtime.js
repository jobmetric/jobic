import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

/** Authorize every connection with current session credentials and report subscription health. */
export function connectKanban(config, invalidate, status) {
    if (!config?.enabled) { status('disabled'); return () => {}; }
    let disposed=false, subscribed=false, retryTimer, authReason=null;
    const pending=new Set();
    status('connecting','connecting');
    const pusher=new Pusher(config.key,{
        wsHost:config.host,wsPort:config.port,wssPort:config.port,forceTLS:config.scheme==='https',enabledTransports:['ws','wss'],cluster:'',
        channelAuthorization:{customHandler:async ({socketId,channelName},callback)=>{
            authReason=null;status('connecting','subscribing');
            const controller=new AbortController();pending.add(controller);
            const timeout=setTimeout(()=>controller.abort(),10000);
            try {
                const response=await fetch(config.authUrl,{method:'POST',credentials:'same-origin',signal:controller.signal,headers:{Accept:'application/json','Content-Type':'application/x-www-form-urlencoded','X-Requested-With':'XMLHttpRequest','X-CSRF-TOKEN':document.querySelector('meta[name="csrf-token"]')?.content||''},body:new URLSearchParams({socket_id:socketId,channel_name:channelName})});
                const token=response.headers.get('X-CSRF-TOKEN');if(token)document.querySelector('meta[name="csrf-token"]')?.setAttribute('content',token);
                if(!response.ok)throw Object.assign(new Error('Channel authorization failed'),{status:response.status});
                const data=await response.json();if(!data.auth)throw new Error('Invalid channel authorization');
                if(!disposed)callback(null,data);
            } catch(error) {
                if(!disposed){authReason=[401,403,419].includes(error.status)?'authorization':'network';status('error',authReason);callback(error,null);}
            } finally {clearTimeout(timeout);pending.delete(controller);}
        }},
    });
    const echo=new Echo({broadcaster:'reverb',client:pusher});
    pusher.connection.bind('state_change',({current})=>{
        if(disposed)return;
        if(current==='connected')status('connecting','subscribing');
        else status(['failed','unavailable','disconnected'].includes(current)?'error':'connecting',current==='connecting'?'connecting':'network');
    });
    pusher.connection.bind('error',()=>{if(!disposed)status('error','network');});
    const subscribe=()=>{
        const channel=echo.private(config.channel);
        channel.listen('.kanban.invalidated',invalidate).subscribed(()=>{
            if(disposed)return;clearTimeout(retryTimer);status('connected','connected');if(subscribed)invalidate();subscribed=true;
        }).error(error=>{
            if(disposed)return;status('error',authReason||([401,403,419].includes(error?.status)?'authorization':'network'));
            clearTimeout(retryTimer);retryTimer=setTimeout(()=>{if(disposed)return;echo.leave(config.channel);subscribe();},15000);
        });
    };
    subscribe();
    const reconnect=()=>{if(!disposed&&navigator.onLine){pusher.connect();if(pusher.connection.state==='connected'){echo.leave(config.channel);subscribe();}}};
    window.addEventListener('online',reconnect);
    return ()=>{disposed=true;clearTimeout(retryTimer);pending.forEach(controller=>controller.abort());window.removeEventListener('online',reconnect);echo.leave(config.channel);echo.disconnect();};
}
