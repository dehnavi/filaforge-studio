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
 // Column groups: only an empty column container is removable, the group controls follow a selected column, and quick changes keep the inspector draft in step.
 const column=(id,children=[])=>({...node(id),type:'container',props:{direction:'column'},children});
 const card={...node('card'),type:'card',props:{tone:'white',padding:24,align:'start'},children:[node('card-text')]};
 const group={...node('group'),type:'columns',props:{columns:2,gap:24},children:[column('c1',[node('c1-text')]),column('c2',[node('c2-text')])]};
 const legacy={...node('legacy'),type:'columns',props:{columns:2,gap:24},children:[{...node('old-heading'),type:'heading',props:{text:'Heading',tag:'h2'}},node('old-text')]};
 state=editor();state.document.root.children=[clone(group),legacy,clone(card)];
 state.selected='legacy';state.removeColumn();assert.equal(state.find('legacy').children.length,2,'legacy content widgets must not count as empty columns');assert.match(state.status,/empty columns/);
 state.selected='group';state.removeColumn();assert.equal(state.find('group').children.length,2,'a group without an empty column keeps its columns');assert.match(state.status,/empty columns/);
 state.addColumn();assert.equal(state.find('group').children.length,3);assert.equal(state.find('group').props.columns,3);assert.equal(state.find(state.selected).type,'container');
 for(let i=0;i<5;i++)state.addColumn();assert.equal(state.find('group').children.length,6,'the inspector button keeps adding while the new column is selected, up to six');assert.equal(state.find('group').props.columns,6);
 state.addColumn();assert.equal(state.find('group').children.length,6);
 state.removeColumn();assert.equal(state.find('group').children.length,5,'the last empty column is removed');assert.equal(state.find('group').props.columns,5);assert.equal(state.selected,'group');assert.equal(state.status,'Column removed.');
 state=editor();state.document.root.children=[clone(group)];state.selected='group';state.addColumn();state.moveNode(state.find('group').children[2].id,'c2','before');
 state.selected='group';state.removeColumn();assert.deepEqual(state.find('group').children.map(c=>c.id),['c1','c2'],'an empty column is removed even when the last column has content');
 state=editor();state.document.root.children=[clone(group)];state.structureManager=true;state.selected='group';state.addColumn();state.select(state.find('group').children[2].id);state.setLayerMeta('locked',true);state.structureManager=false;
 state.selected='group';state.removeColumn();assert.equal(state.find('group').children.length,3,'a locked column is not removed');assert.notEqual(state.status,'Column removed.','a rejected removal must not report success');
 state=editor();state.document.root.children=[clone(card)];state.selected='card';state.visualAction('card','tone:soft');state.visualAction('card','padding-up');
 assert.equal(state.find('card').props.tone,'soft');assert.equal(state.draftProps.tone,'soft','quick changes reach the inspector draft');assert.equal(state.draftProps.padding,32);
 state.draftProps.anchor='intro';state.applyProps();assert.equal(state.find('card').props.tone,'soft');assert.equal(state.find('card').props.padding,32,'Apply changes must not revert quick changes');
 for(let i=0;i<20;i++)state.visualAction('card','padding-up');assert.equal(state.find('card').props.padding,96);for(let i=0;i<20;i++)state.visualAction('card','padding-down');assert.equal(state.find('card').props.padding,8);
 // Embed codes: the editor stores exactly what the server returns and shows its notice; a rejected code leaves the element unchanged.
 const kept='<div id="80008716930"><script src="https://www.aparat.com/embed/gywe525?data[rnddiv]=80008716930" data-ff-embed=""></script></div>';
 let sent='';state=editor({sanitizeEmbed:async code=>{sent=code;return code.includes('aparat')?{code:kept,notice:'Script from www.aparat.com saved.'}:{error:'No usable iframe or external script was found in this code.'}}});
 state.document.root.children.push({...node('embed'),type:'embed',props:{code:'',title:''}});
 const pasted='<div id="80008716930"><script src="https://www.aparat.com/embed/gywe525?data[rnddiv]=80008716930"></script></div>';
 state.select('embed');state.draftProps.code=pasted;state.draftProps.title='  Setup video  ';
 await state.applyEmbed();
 assert.equal(sent,pasted);assert.equal(state.find('embed').props.code,kept,'only the server result is stored');assert.equal(state.find('embed').props.title,'Setup video');assert.equal(state.draftProps.code,kept);assert.equal(state.embedNotice,'Script from www.aparat.com saved.');assert.equal(state.embedError,'');assert.equal(state.embedBusy,false);
 state.draftProps.code='<p>hello</p>';await state.applyEmbed();
 assert.equal(state.find('embed').props.code,kept,'a rejected code does not replace the stored one');assert.match(state.embedError,/No usable iframe/);assert.equal(state.embedNotice,'');assert.equal(state.embedBusy,false);
 state.select('text');assert.equal(state.embedError,'','messages reset when another element is selected');
 console.log('PASS: protected edits, token resolution, recovery, stale recovery, in-flight saves, conflicts, offline retry, cross-page clipboard, repeater insertion, column groups, quick layout changes and embed codes.');
})().catch(error=>{console.error(error);process.exitCode=1});
