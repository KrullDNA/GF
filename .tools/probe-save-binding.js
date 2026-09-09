(function(){
const btn=document.getElementById('ajax-save-form-menu-bar')||document.querySelector('button.update-form');
const kf=window.kform||{};
const inst=kf.instances||{};
const cfg=window.kform_admin_config||null;
const R=[
 {check:'save button in the page', value:btn?(btn.id||btn.className):'MISSING'},
 {check:'button variant', value:btn&&btn.id==='ajax-save-form-menu-bar'?'ajax (handler comes from a chunk)':'legacy onclick="SaveForm()"'},
 {check:'kform global', value:typeof window.kform},
 {check:'kform.instances keys', value:Object.keys(inst).join(', ')||'(none)'},
 {check:'adminFormSaver bound', value:inst.adminFormSaver?'YES':'NO - the saver chunk never ran'},
 {check:'kform_admin_config', value:cfg?'present':'MISSING'},
 {check:'config.admin_save_form', value:cfg&&cfg.admin_save_form?'present':'MISSING'},
 {check:'config endpoint action', value:cfg&&cfg.admin_save_form&&cfg.admin_save_form.endpoints&&cfg.admin_save_form.endpoints.admin_save_form?JSON.stringify(cfg.admin_save_form.endpoints.admin_save_form.action):'n/a'},
];
console.table(R,['check','value']);

const chunkFails=performance.getEntriesByType('resource')
  .filter(r=>/\/assets\/js\/dist\/.*\.min\.js/.test(r.name))
  .map(r=>({file:r.name.split('/').pop(),transferred:r.transferSize,decoded:r.decodedBodySize,from:r.transferSize===0&&r.decodedBodySize>0?'BROWSER CACHE':'network'}));
console.log('%cHow this page load got each script:','font-weight:bold');
console.table(chunkFails,['file','transferred','decoded','from']);

if(!inst.adminFormSaver){
 console.log('%cThe Save button has no handler attached. Everything else is irrelevant until the chunks load.','font-weight:bold;color:#c00');
 const cached=chunkFails.filter(r=>r.from==='BROWSER CACHE');
 if(cached.length)console.log(cached.length+' of those came from the browser cache rather than the network.');
}else{
 console.log('%cThe saver is bound. Clicking Save should now work.','font-weight:bold;color:#080');
}
})();
