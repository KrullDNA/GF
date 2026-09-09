(function(){
const $=window.jQuery;
if(!$){console.log('jQuery missing');return;}
const C='#kform_fields';
const ids=()=>$(C).children().map(function(){return this.id||(this.hasAttribute('data-js-field-loading-placeholder')?'[loading]':'['+this.tagName.toLowerCase()+']');}).get();
const say=(m,d)=>console.log('%c'+m,'color:#06c;font-weight:bold',d===undefined?'':d);

say('container found', $(C).length+' element(s), '+$(C).children().length+' children');
say('order now', ids().join(' | '));

// The number that decides everything: what index the new field is created at.
if(typeof window.StartAddField==='function'){
  const orig=window.StartAddField;
  window.StartAddField=function(type,index){
    const ind=$('#indicator');
    say('StartAddField('+type+', index='+index+')',{
      indicatorInDom: ind.length>0,
      indicatorTarget: ind.length?(ind.data('target')&&ind.data('target').length?(ind.data('target')[0].id||'(no id)'):'NONE'):'n/a',
      indicatorWhere: ind.length?(ind.data('where')||'NONE'):'n/a'
    });
    return orig.apply(this,arguments);
  };
  say('StartAddField wrapped','ok');
}else{say('StartAddField MISSING','the editor inline script did not load');}

// Did the mover run, and did it have something to move to?
$(document).on('kform_field_added.kdnaprobe',function(e,form,field){
  const at=$(C).children().index($('#field_'+field.id));
  say('kform_field_added: field '+field.id+' ('+field.type+') ended at position '+at,ids().join(' | '));
});

// Watch the drop indicator for the whole drag.
let seen=false,lastTarget='';
$(document).on('mousemove.kdnaprobe',function(){
  const ind=$('#indicator');
  if(!ind.length)return;
  const t=ind.data('target');
  const label=t&&t.length?(t[0].id||'(no id)'):'NONE';
  if(label!==lastTarget){lastTarget=label;seen=true;say('indicator target -> '+label,'where: '+(ind.data('where')||'NONE'));}
});

$(document).on('mouseup.kdnaprobe',function(){
  setTimeout(function(){
    const ind=$('#indicator');
    say('at drop',{indicatorInDom:ind.length>0,everHadTarget:seen,lastTarget:lastTarget||'NONE'});
  },0);
});

const obs=new MutationObserver(function(){say('order changed',ids().join(' | '));});
if($(C).length)obs.observe($(C)[0],{childList:true});
window.KDNADragProbe={stop:function(){obs.disconnect();$(document).off('.kdnaprobe');say('probe stopped');}};

console.log('%cNow drag a field from the sidebar and drop it in the MIDDLE of the form. Then paste everything printed.','font-weight:bold;font-size:13px');
})();
