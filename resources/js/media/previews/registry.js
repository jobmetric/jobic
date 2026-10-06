const drivers = new Map();

/** Register trusted application code. The returned disposer runs before navigation/close. */
export function registerPreviewDriver(name, {matches, load, priority = 0}) {
    if (!name || typeof matches !== 'function' || typeof load !== 'function') throw new TypeError('Invalid preview driver');
    drivers.set(name, {name, matches, load, priority});
    return () => drivers.delete(name);
}

export function resolvePreviewDriver(item) {
    return [...drivers.values()].sort((a,b) => b.priority - a.priority).find(driver => driver.matches(item));
}

for (const kind of ['audio', 'video', 'pdf']) {
    const loaders = {audio: () => import('./audio'), video: () => import('./video'), pdf: () => import('./pdf')};
    registerPreviewDriver(kind, {
        matches: item => kind === 'pdf' ? item.extension?.toLowerCase() === 'pdf' : item.group === kind,
        load: async () => {
            const {default: methods} = await loaders[kind]();
            const title = kind[0].toUpperCase()+kind.slice(1);
            return {mount: async (manager,item) => {
                Object.assign(manager, methods);
                const pending = manager[`render${title}Preview`](item);
                const state = manager[`${kind}PreviewState`];
                await pending;
                return () => { if(manager[`${kind}PreviewState`] === state) manager[`destroy${title}Preview`]?.(); };
            }};
        },
    });
}
registerPreviewDriver('text', {matches:item=>['txt','md','log','json'].includes(item.extension?.toLowerCase()),load:()=>import('./text')});
registerPreviewDriver('model', {matches:item=>['stl','obj'].includes(item.extension?.toLowerCase()),load:()=>import('./model')});
registerPreviewDriver('office', {matches:item=>['docx','pptx'].includes(item.extension?.toLowerCase()),load:()=>import('./office')});
registerPreviewDriver('image', {matches:item=>item.group==='image',load:()=>import('./image')});
registerPreviewDriver('spreadsheet', {matches:item=>['xls','xlsx','csv'].includes(item.extension?.toLowerCase()),load:()=>import('./spreadsheet')});
registerPreviewDriver('archive', {matches:item=>item.extension?.toLowerCase()==='zip',load:()=>import('./archive')});
window.dispatchEvent(new CustomEvent('jobic:media-preview-registry', {detail: {registerPreviewDriver}}));
window.JobicMediaPreviews = Object.freeze({register:registerPreviewDriver});
