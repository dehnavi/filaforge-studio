// Generate the compiled runtime with FILAFORGE_RUNTIME_OUTPUT=/tmp/filaforge-runtime.js php ... phpunit,
// then run: node tests/Browser/authoring-runtime.cjs /tmp/filaforge-runtime.js
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const { webcrypto } = require('node:crypto');
const storage = new Map(), timers = new Map(); let nextTimer = 0;
const context = {window:{},crypto:webcrypto,console,sessionStorage:{getItem:key=>storage.get(key)||null,setItem:(key,value)=>storage.set(key,value),removeItem:key=>storage.delete(key)},setTimeout:fn=>{timers.set(++nextTimer,fn);return nextTimer},clearTimeout:id=>timers.delete(id)};
vm.createContext(context); vm.runInContext(fs.readFileSync(process.argv[2], 'utf8'), context);
const clone = value => JSON.parse(JSON.stringify(value));
const node = (id='text') => ({id,type:'text',widgetVersion:1,props:{text:'Original'},children:[],styles:[],responsive:[],bindings:[],visibility:[],meta:[]});
const initial = () => ({document:{schemaVersion:1,locale:'en',direction:'ltr',root:{...node('root'),type:'container',props:{direction:'column'},children:[node()]}},version:0,recoveryKey:'test-page',clipboardKey:'test-user',autosave:true,autosaveDelay:500});
let calls=0;
function editor(wire={saveDocument:async()=>({version:++calls})}) {
 const state=context.window.filaforgeEditor(initial(),wire);state.port={postMessage(message){if(message.type==='flush')state.flushResolve?.()}};state.$refs={};state.$nextTick=fn=>fn();state.selected='text';return state;
}
(async()=>{
 let state=editor();state.find('text').meta={locked:true};
 assert.equal(state.commit(()=>state.find('text').props.text='Blocked'),false);
 assert.equal(state.find('text').props.text,'Original');
 state.find('text').meta={editable:['text']};
 assert.equal(state.commit(()=>state.find('text').props.text='Allowed'),true);
 assert.equal(state.commit(()=>state.find('text').styles={color:'#112233'}),false);
 assert.equal(state.find('text').props.text,'Allowed');
 state.structureManager=true;assert.equal(state.commit(()=>state.find('text').meta=[]),true);
 state.designTokens={brand:{property:'color',value:'#112233'}};state.find('text').styles={color:{token:'brand'}};
 assert.equal(state.resolvedCanvasDocument().root.children[0].styles.color,'#112233');
 assert.equal(state.find('text').styles.color.token,'brand');
 state=editor();state.commit(()=>state.find('text').props.text='Recover me');
 let recovered=editor();recovered.readRecovery();assert.ok(recovered.recovery);recovered.recoverDraft();assert.equal(recovered.find('text').props.text,'Recover me');
 recovered=editor();recovered.version=9;recovered.readRecovery();recovered.recoverDraft();assert.equal(recovered.find('text').props.text,'Original');
 recovered.discardRecovery();
 let resolveSave;state=editor({saveDocument:()=>new Promise(resolve=>resolveSave=resolve)});
 state.commit(()=>state.find('text').props.text='Sending');const saving=state.save();assert.equal(state.busy,true);await new Promise(setImmediate);
 state.commit(()=>state.find('text').props.text='Typed during save');resolveSave({version:1});await saving;
 assert.equal(state.version,1);assert.equal(state.dirty,true);assert.equal(JSON.parse(storage.get('test-page')).version,1);
 state=editor({saveDocument:async()=>({error:'Document conflict'})});state.dirty=true;await state.save();assert.equal(state.autosavePaused,true);assert.equal(state.dirty,true);
 let saveAttempts=0;state=editor({saveDocument:async()=>{saveAttempts++;if(saveAttempts===1)throw Error('Offline');return {version:1}}});state.dirty=true;
 await state.save();assert.equal(state.autosavePaused,true);await state.save();assert.equal(state.autosavePaused,false);assert.equal(state.dirty,false);
 state=editor();state.find('text').type='shared-section';state.find('text').props={key:'cta',values:[]};state.setSectionOverride('headline','Instance text');assert.equal(state.find('text').props.values.headline,'Instance text');state.setSectionOverride('headline',undefined);assert.equal(Object.hasOwn(state.find('text').props.values,'headline'),false);
 state=editor();state.structureManager=true;state.copy();const second=editor();second.structureManager=true;second.paste();assert.equal(second.document.root.children.length,2);assert.notEqual(second.document.root.children[1].id,'text');
 let repeaterCalls=0;state=editor({makeRepeater:async(source,fields,query,columns)=>{repeaterCalls++;assert.equal(source,'catalog');assert.equal(columns,3);return {node:{...node('loop'),type:'query-loop',props:{provider:'catalog',query,emptyText:''},children:[node('row')]}}}});
 state.repeaterSource='catalog';state.repeaterFields=['title'];state.sources=[{name:'catalog',maxLimit:12}];await state.addRepeater();assert.equal(repeaterCalls,1);assert.equal(state.selected,'loop');assert.equal(state.repeaterBusy,false);
 console.log('PASS: protected edits, token resolution, recovery, stale recovery, in-flight saves, conflicts, offline retry, cross-page clipboard and repeater insertion.');
})().catch(error=>{console.error(error);process.exitCode=1});
