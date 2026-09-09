(function(){
const K='kdna_save_debug';
const log=(m)=>{try{const a=JSON.parse(sessionStorage.getItem(K)||'[]');a.push(m);sessionStorage.setItem(K,JSON.stringify(a.slice(-60)));}catch(e){}console.log(m);};
const prev=(()=>{try{return JSON.parse(sessionStorage.getItem(K)||'[]');}catch(e){return[];}})();
if(prev.length){console.log('%c--- from before the last page load ---','color:#888');prev.forEach(l=>console.log(l));}
try{sessionStorage.removeItem(K);}catch(e){}

const d=(el)=>el?el.tagName.toLowerCase()+(el.id?'#'+el.id:'')+(el.className&&el.className.baseVal===undefined&&typeof el.className==='string'?'.'+el.className.trim().split(/\s+/).join('.'):''):'null';
const R=[];
const chk=(what,ok,detail)=>{R.push({check:what,result:ok?'ok':'PROBLEM',detail:detail||''});};

// 1. The pieces SaveForm needs.
chk('SaveForm() defined',typeof window.SaveForm==='function',typeof window.SaveForm);
chk('UpdateFormObject() defined',typeof window.UpdateFormObject==='function',typeof window.UpdateFormObject);
chk('ValidateForm() defined',typeof window.ValidateForm==='function',typeof window.ValidateForm);
chk('form object present',typeof window.form==='object'&&window.form!==null,window.form?('id '+window.form.id+', '+((window.form.fields||[]).length)+' fields'):'missing');
chk('kdna_vars present',typeof window.kdna_vars==='object'&&window.kdna_vars!==null,typeof window.kdna_vars);
const meta=document.getElementById('kform_meta');
chk('#kform_meta exists',!!meta,d(meta));
const frm=document.getElementById('kform_update');
chk('#kform_update is a form',!!frm&&frm.tagName==='FORM',d(frm));

// 2. The button, and whether the click can actually reach it.
let btn=document.querySelector('button.update-form');
if(!btn)btn=[...document.querySelectorAll('button,input,a')].find(el=>((el.getAttribute('onclick')||'')+(el.getAttribute('onkeypress')||'')).indexOf('SaveForm')>-1);
chk('Save button found',!!btn,d(btn));
if(btn){
  btn.scrollIntoView({block:'center'});
  const r=btn.getBoundingClientRect();
  const cx=r.left+r.width/2, cy=r.top+r.height/2;
  const stack=(document.elementsFromPoint?document.elementsFromPoint(cx,cy):[document.elementFromPoint(cx,cy)]).filter(Boolean);
  const top=stack[0];
  const reaches=top===btn||btn.contains(top)||top.contains(btn);
  chk('click lands on the button',reaches,reaches?d(top):'the click hits '+d(top)+' instead');
  if(!reaches){
    console.log('%cSomething is covering the Save button. Stack at that point, front to back:','font-weight:bold;color:#c00');
    stack.slice(0,6).forEach(el=>{const s=getComputedStyle(el);
      console.log('   '+d(el)+'   position:'+s.position+'  z-index:'+s.zIndex+'  opacity:'+s.opacity+'  pointer-events:'+s.pointerEvents+'  '+Math.round(el.getBoundingClientRect().width)+'x'+Math.round(el.getBoundingClientRect().height));});
  }
}

// 3. Wrap the chain so the real exception is printed instead of swallowed.
['UpdateFormObject','ValidateForm','SaveForm'].forEach(function(n){
  const orig=window[n];
  if(typeof orig!=='function')return;
  window[n]=function(){
    log('-> '+n+'() called');
    try{const out=orig.apply(this,arguments);log('   '+n+'() returned '+JSON.stringify(out));return out;}
    catch(e){log('%c   '+n+'() THREW: '+(e&&e.message)+'\n'+(e&&e.stack));throw e;}
  };
});
if(frm){frm.addEventListener('submit',function(){log('-> #kform_update submitted, meta length '+(meta?String(meta.value||'').length:'n/a'));},true);}

// 4. Are the editor's chunks actually being served now?
const CH=['scripts-admin.form-editor.3501ffb814082667f9e9.min.js','scripts-admin.editor-button.c4d64aa0864322512862.min.js','scripts-admin.form-switcher.7d8eedf956e251813228.min.js','scripts-admin.embed-form.2b167a2b1fef5cdeea8a.min.js','940.78d83617d10157680121.min.js','281.05f0bafa828833bb53a5.min.js'];
const BASE=location.origin+'/wp-content/plugins/kdna-forms/assets/js/dist/';
Promise.all(CH.map(n=>fetch(BASE+n,{cache:'reload',credentials:'omit'}).then(r=>r.text().then(t=>({chunk:n,status:r.status,html:/^\s*</.test(t),bytes:t.length}))).catch(e=>({chunk:n,status:'ERR',html:false,bytes:0}))))
.then(rows=>{
  console.table(R,['check','result','detail']);
  console.log('%cEditor chunks, requested with no cache:','font-weight:bold');
  console.table(rows,['chunk','status','bytes','html']);
  const bad=rows.filter(r=>r.html||r.status!==200);
  console.log(bad.length?('%c'+bad.length+' chunk(s) still served as HTML - the cache has not cleared'):'%call chunks served as JavaScript','font-weight:bold;color:'+(bad.length?'#c00':'#080'));
  console.log('%cNow click Save Form. Every step prints here. If nothing prints, the click never reached the button.','font-weight:bold');
});
})();
