/** Compare saved editor content without volatile Editor.js metadata or generated block identifiers. */
export function editorContentSnapshot(value) {
    let document;
    try { document=JSON.parse(value); } catch { return value; }
    if(!document || !Array.isArray(document.blocks))return value;
    const empty=document.blocks.length===1&&document.blocks[0].type==='paragraph'&&String(document.blocks[0].data?.text||'').trim()==='';
    const blocks=(empty?[]:document.blocks).map(({id,...block})=>block);
    const {time,version,...content}=document;
    const stable=value=>Array.isArray(value)?value.map(stable):value&&typeof value==='object'?Object.fromEntries(Object.keys(value).sort().map(key=>[key,stable(value[key])])):value;
    return JSON.stringify(stable({...content,blocks}));
}
