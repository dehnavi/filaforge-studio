(()=>{
if(!customElements.get('ff-slides'))customElements.define('ff-slides',class extends HTMLElement{
 connectedCallback(){if(this.ready)return;this.ready=true;this.index=0;this.track=this.querySelector('.ff-slide-track');this.items=[...this.track.children];this.status=this.querySelector('[data-status]');this.querySelector('[data-next]').onclick=()=>this.go(1);this.querySelector('[data-prev]').onclick=()=>this.go(-1);this.addEventListener('keydown',e=>{if(e.key==='ArrowRight'||e.key==='ArrowLeft'){e.preventDefault();this.go(e.key==='ArrowLeft'?1:-1)}});this.tabIndex=0;this.track.addEventListener('scroll',()=>{clearTimeout(this.scrollTimer);this.scrollTimer=setTimeout(()=>{const r=this.track.getBoundingClientRect();let best=Infinity;this.items.forEach((el,i)=>{const c=el.getBoundingClientRect();const distance=Math.abs(getComputedStyle(this).direction==='rtl'?r.right-c.right:c.left-r.left);if(distance<best){best=distance;this.index=i}});this.update()},80)});this.addEventListener('mouseenter',()=>this.pause());this.addEventListener('mouseleave',()=>this.play());this.addEventListener('focusin',()=>this.pause());this.addEventListener('focusout',()=>this.play());this.update();this.play()}
 update(){this.status.textContent=this.items.length?(this.index+1)+' / '+this.items.length:'بدون اسلاید';this.querySelector('[data-next]').disabled=this.items.length<2;this.querySelector('[data-prev]').disabled=this.items.length<2}
 go(delta){if(!this.items.length)return;this.index=(this.index+delta+this.items.length)%this.items.length;const item=this.items[this.index],r=this.track.getBoundingClientRect(),ir=item.getBoundingClientRect();const offset=getComputedStyle(this).direction==='rtl'?ir.right-r.right:ir.left-r.left;this.track.scrollBy({left:offset,behavior:matchMedia('(prefers-reduced-motion: reduce)').matches?'instant':'smooth'});this.update()}
 pause(){clearInterval(this.timer)}
 play(){this.pause();if(this.dataset.autoplay==='true'&&!matchMedia('(prefers-reduced-motion: reduce)').matches)this.timer=setInterval(()=>{if(!document.hidden&&!this.contains(document.activeElement))this.go(1)},Number(this.dataset.interval)||5000)}
 disconnectedCallback(){this.pause();clearTimeout(this.scrollTimer)}
});
if(!window.ffCommentsBound){window.ffCommentsBound=true;document.addEventListener('submit',async e=>{const form=e.target.closest('form[data-ff-comment]');if(!form)return;e.preventDefault();const button=form.querySelector('button[type=submit]'),status=form.querySelector('[data-comment-status]');button.disabled=true;status.textContent=form.dataset.sending||'…';try{const response=await fetch(form.action,{method:'POST',body:new FormData(form),headers:{Accept:'application/json'}});const result=await response.json();if(!response.ok)throw new Error('Comment request failed');status.textContent=result.message;form.reset()}catch(error){status.textContent=form.dataset.failed||'Error'}finally{button.disabled=false}})}
})();
// Each wizard owns only its direct panels; nested wizards remain independent.
(()=>{
const boot=()=>document.querySelectorAll('[data-ff-wizard]').forEach(root=>{
 if(root.dataset.ffReady)return;root.dataset.ffReady='true';
 const nav=Array.from(root.children).find(el=>el.hasAttribute('data-ff-step-nav'));
 const holder=Array.from(root.children).find(el=>el.hasAttribute('data-ff-step-panels'));
 const controls=Array.from(root.children).find(el=>el.hasAttribute('data-ff-step-controls'));
 if(!nav||!holder||!controls)return;
 const panels=Array.from(holder.children).filter(el=>el.hasAttribute('data-ff-step'));
 const buttons=Array.from(nav.querySelectorAll('[data-ff-step-button]'));
 const prev=controls.querySelector('[data-ff-step-prev]'),next=controls.querySelector('[data-ff-step-next]'),status=controls.querySelector('[data-ff-step-status]');
 if(!panels.length)return;let current=Math.min(Number(root.dataset.start)||0,panels.length-1);
 const show=(index,focus=false)=>{current=Math.max(0,Math.min(index,panels.length-1));panels.forEach((panel,i)=>panel.hidden=i!==current);buttons.forEach((button,i)=>{if(i===current)button.setAttribute('aria-current','step');else button.removeAttribute('aria-current')});prev.disabled=current===0;next.disabled=current===panels.length-1;status.textContent=(current+1)+' / '+panels.length;if(focus)panels[current].focus()};
 buttons.forEach((button,i)=>button.addEventListener('click',()=>show(i,true)));
 prev.addEventListener('click',()=>show(current-1,true));next.addEventListener('click',()=>show(current+1,true));
 nav.hidden=false;controls.hidden=false;show(current);
});
if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',boot,{once:true});else boot();
})();
(()=>{
const boot=()=>document.querySelectorAll('[data-ff-menu]').forEach(root=>{
 if(root.dataset.ffMenuReady)return;root.dataset.ffMenuReady='true';
 const breakpoint={mobile:640,tablet:1024,never:0}[root.dataset.collapseAt]??1024;
 const shell=root.querySelector('.ff-menu-shell'),compact=matchMedia('(max-width:'+breakpoint+'px)'),hover=matchMedia('(hover:hover) and (pointer:fine)');
 const menus=[...root.querySelectorAll('[data-ff-dropdown]')];let resizeFrame=0;
 const close=except=>menus.forEach(item=>{if(item!==except&&!item.contains(except))item.open=false});
 const reset=panel=>{panel.classList.remove('ff-scroll-panel');panel.style.transform='';panel.style.maxHeight='';panel.style.overflowY=''};
 const position=detail=>{
  const panel=detail.querySelector(':scope > .ff-dropdown-panel');if(!panel)return;reset(panel);
  if(compact.matches||panel.parentElement.closest('.ff-scroll-panel'))return;
  const gutter=12,maxHeight=Math.max(44,innerHeight-gutter*2);let rect=panel.getBoundingClientRect();
  if(rect.height>maxHeight){panel.classList.add('ff-scroll-panel');panel.style.maxHeight=maxHeight+'px';panel.style.overflowY='auto';rect=panel.getBoundingClientRect()}
  const viewport=document.documentElement.clientWidth;
  const parentPanel=panel.parentElement.closest('.ff-dropdown-panel');let left=rect.left;
  if(parentPanel){const item=detail.closest('.ff-menu-item').getBoundingClientRect();let side=parentPanel.dataset.ffSide||(getComputedStyle(root).direction==='rtl'?'left':'right');const wanted=side==='right'?item.right:item.left-rect.width;const alternative=side==='right'?item.left-rect.width:item.right;if(wanted>=gutter&&wanted+rect.width<=viewport-gutter)left=wanted;else if(alternative>=gutter&&alternative+rect.width<=viewport-gutter){left=alternative;side=side==='right'?'left':'right'}panel.dataset.ffSide=side}else panel.dataset.ffSide=getComputedStyle(root).direction==='rtl'?'left':'right';
  const dx=Math.max(gutter,Math.min(left,viewport-gutter-rect.width))-rect.left;
  const dy=Math.max(gutter,Math.min(rect.top,innerHeight-gutter-rect.height))-rect.top;
  panel.style.transform='translate('+dx+'px,'+dy+'px)';
 };
 const setMode=()=>{shell.open=!compact.matches;menus.forEach(item=>{item.open=false;const panel=item.querySelector(':scope > .ff-dropdown-panel');if(panel)reset(panel)})};
 setMode();compact.addEventListener('change',setMode);
 const reposition=()=>{cancelAnimationFrame(resizeFrame);resizeFrame=requestAnimationFrame(()=>menus.filter(item=>item.open).forEach(position))};
 window.addEventListener('resize',reposition);window.addEventListener('scroll',reposition,{passive:true});
 menus.forEach(detail=>{
  const item=detail.closest('.ff-menu-item');
  item.addEventListener('pointerenter',event=>{if(event.pointerType!=='mouse'||compact.matches||!hover.matches)return;close(detail);if(!detail.open)detail.dataset.ffOpenMode='hover';detail.open=true;position(detail)});
  item.addEventListener('pointerleave',()=>{if(!compact.matches&&!item.contains(document.activeElement))detail.open=false});
  detail.querySelector('summary').addEventListener('click',event=>{if(detail.open&&detail.dataset.ffOpenMode==='hover')event.preventDefault();detail.dataset.ffOpenMode='click'});
  detail.addEventListener('toggle',()=>{if(detail.open)position(detail);else delete detail.dataset.ffOpenMode});
 });
 root.addEventListener('keydown',event=>{if(event.key!=='Escape')return;const detail=event.target.closest('[data-ff-dropdown]');if(detail){detail.open=false;detail.querySelector('summary').focus()}else{close(null);if(compact.matches){shell.open=false;shell.querySelector('summary').focus()}}event.preventDefault()});
 // Window blur has no related target; do not hide a nested control before its click lands.
 root.addEventListener('focusout',event=>{if(event.relatedTarget&&!root.contains(event.relatedTarget))close(null)});
 document.addEventListener('pointerdown',event=>{if(!root.contains(event.target)){close(null);if(compact.matches)shell.open=false}});
});
if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',boot,{once:true});else boot();
})();
