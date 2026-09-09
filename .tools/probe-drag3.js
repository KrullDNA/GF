(function(){
const $=window.jQuery;
const say=(m,d)=>console.log('%c'+m,'color:#06c;font-weight:bold',d===undefined?'':d);

// 1. Is the layout editor initialised more than once? Each copy gets its own
//    $elem, and only the copy whose draggable ran has it set.
const loads=performance.getEntriesByType('resource')
  .filter(r=>/layout_editor|form_editor/.test(r.name))
  .map(r=>r.name.split('/').pop());
const ev=$._data(document,'events');
say('editor scripts loaded',loads.length?loads:'(none seen - may predate the probe)');
say('kform_field_added handlers',(ev&&ev.kform_field_added?ev.kform_field_added.length:0));

// 2. The fix, applied live. Capture the drop target before the editor's own
//    handler wipes the indicator, then put the field where it belongs.
let pending=null;
$(document).on('kform_field_added.kdnafix',function(e,form,field){
  const ind=$('#indicator');
  const t=ind.data('target');
  pending=(t&&t.length&&document.contains(t[0]))?{id:field.id,target:t,where:ind.data('where')}:null;
  if(pending)say('captured drop target for field '+field.id,(t[0].id||'(no id)')+' / '+pending.where);
});
const list=ev&&ev.kform_field_added;
if(list&&list.length>1){list.unshift(list.pop());say('fix handler moved to front','ok');}

$(document).on('kform_field_added.kdnafixafter',function(e,form,field){
  if(!pending||pending.id!==field.id)return;
  setTimeout(function(){
    const $f=$('#field_'+field.id);
    const before=$('#kform_fields').children().index($f);
    if(!$f.length||!document.contains(pending.target[0])){pending=null;return;}
    if(pending.where==='top'||pending.where==='left'){$f.insertBefore(pending.target);}
    else{$f.insertAfter(pending.target);}
    const after=$('#kform_fields').children().index($f);
    say('MOVED field '+field.id+': position '+before+' -> '+after,
        'relative to '+(pending.target[0].id||'(no id)')+' ('+pending.where+')');
    pending=null;
  },0);
});

say('ready','drag a field into the middle of the form');
})();
