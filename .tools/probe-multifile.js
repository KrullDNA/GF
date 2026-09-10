(function(){
const $=window.jQuery;
const say=(m,d)=>console.log('%c'+m,'color:#06c;font-weight:bold',d===undefined?'':d);

const cb=$('#field_multiple_files');
const ft=$('#field_type');
const sel=$('.field_selected').attr('id')||'(none selected)';
const fieldId=sel.indexOf('field_')===0?sel.replace('field_',''):null;

say('selected field', sel);
say('checkbox found', cb.length?'yes':'NO - open a File Upload field first');
say('checkbox disabled', cb.prop('disabled'));
say('field type dropdown disabled', ft.prop('disabled'));
say('submitted_fields_loaded', typeof submitted_fields_loaded!=='undefined'?submitted_fields_loaded:'(variable missing)');
say('submitted_fields', typeof submitted_fields!=='undefined'?JSON.stringify(submitted_fields):'(variable missing)');
if(fieldId!==null&&typeof has_entry==='function'){
  say('has_entry('+fieldId+')',has_entry(fieldId));
}

// Ask the endpoint directly and show exactly what comes back.
const fd=new FormData();
fd.append('action','kdna_get_submitted_fields');
fd.append('form_id',(window.form&&window.form.id)||0);
fd.append('nonce', $('script').length ? (function(){
  const m=document.documentElement.innerHTML.match(/kdna_get_submitted_fields'\s*\)?\s*;?[\s\S]{0,80}?'([a-f0-9]{10})'/);
  return m?m[1]:'';
})() : '');
say('re-issuing the endpoint request','(nonce may not be recoverable from here; a 403 below is expected in that case)');
fetch(ajaxurl,{method:'POST',body:fd})
  .then(r=>r.text().then(t=>{
    say('endpoint HTTP '+r.status,(r.headers.get('content-type')||'').split(';')[0]);
    say('endpoint body',t.length>300?t.slice(0,300)+'…':t);
  }))
  .catch(e=>say('endpoint request FAILED',String(e&&e.message||e)));

console.log('%cThe two answers:','font-weight:bold');
console.log('  submitted_fields_loaded true  + this field id IS in submitted_fields  -> correctly locked, the field has entries');
console.log('  submitted_fields_loaded false                                          -> the check never completed and every field is locked by default');
})();
